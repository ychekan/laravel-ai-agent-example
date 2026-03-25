<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Repositories\DocumentRepository;
use App\Services\ChunkHelperService;
use App\Services\OllamaService;
use App\Services\ParserHelperService;
use App\Services\QdrantService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $documentId
    )
    {
    }

    /**
     * @throws \Throwable
     */
    public function handle(
        OllamaService      $ollama,
        QdrantService      $qdrant,
        DocumentRepository $repository
    ): void
    {
        $document = $repository->getOne(documentId: $this->documentId);

        try {
            $repository->markProcessing(document: $document);
            $fullPath = Storage::disk(name: 'local')->path(path: $document->file_path);
            if (!file_exists($fullPath)) {
                throw new \RuntimeException(message: 'File not found at path: ' . $fullPath);
            }
            $texts = ParserHelperService::parse(fullPath: $fullPath);

            if (empty($texts)) {
                throw new \RuntimeException(message: 'Parsed content is empty');
            }

            $document->chunks()->delete();

            $chunkText = ChunkHelperService::split(text: $texts);

            if (empty($chunkText)) {
                throw new \RuntimeException(message: 'No content to index');
            }

            $steps = array_chunk($chunkText, 100); // Process in batches of 100 chunks to avoid memory issues
            foreach ($steps as $stepIndex => $chunks) {
                $points = [];
                foreach ($chunks as $index => $chunkText) {
                    $vector = $ollama->embed(text: $chunkText);

                    $vectorId = (string)Str::uuid();

                    $points[] = [
                        'id' => $vectorId,
                        'vector' => $vector,
                        'payload' => [
                            'document_id' => $document->id,
                            'document_title' => $document->title,
                            'chunk_index' => $index + $stepIndex,
                            'text' => $chunkText,
                        ],
                    ];

                    $chunk = [
                        'chunk_index' => $index + $stepIndex,
                        'content' => $chunkText,
                        'vector_id' => $vectorId,
                        'meta' => [
                            'length' => mb_strlen($chunkText),
                        ],
                    ];

                    $document->chunks()->create(attributes: $chunk);
                }
                $qdrant->upsertBatch(points: $points);
            }

            $repository->markIndexed(document: $document);
        } catch (\Throwable $e) {
            $repository->markFailed(document: $document, error: $e->getMessage());
            logger(message: 'Error processing document ID ' . $document->id . ': ' . $e->getMessage(), context: ['exception' => $e]);
            throw new \RuntimeException(message: "Failed to process document ID {$document->id}: " . $e->getMessage(), previous: $e);
        }
    }
}
