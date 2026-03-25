<?php
declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Repositories\DocumentRepository;
use Illuminate\Http\UploadedFile;

readonly class DocumentService
{
    public function __construct(
        private DocumentRepository $repository
    )
    {
    }

    public function processDocument(array $request): array
    {
        $files = is_array($request['file']) ? $request['file'] : [$request['file']];

        $documents = [];
        foreach ($files as $file) {
            $documents[] = $this->process(file: $file, title: $request['title']);
        }

        return $documents;
    }

    private function process(UploadedFile $file, string $title): Document
    {
        $path = $file->store(path: 'documents');

        $document = $this->repository->create(data: [
            'title' => $title,
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => 'pending',
        ]);

        ProcessDocumentJob::dispatch($document->id);

        return $document;
    }
}
