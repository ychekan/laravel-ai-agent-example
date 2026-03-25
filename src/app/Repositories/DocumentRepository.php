<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Model;

class DocumentRepository
{
    public function create(array $data): Model|Document
    {
        return Document::create($data);
    }

    public function getOne(int $documentId): Model|Document
    {
        return Document::findOrFail($documentId);
    }

    public function update(Document $document, array $data): void
    {
        $document->update($data);
    }

    public function markProcessing(Document $document): void
    {
        $this->update($document, [
            'status' => 'processing',
            'error_message' => null
        ]);
    }

    public function markIndexed(Document $document): void
    {
        $this->update($document, [
            'status' => 'indexed',
            'indexed_at' => now(),
        ]);
    }

    public function markFailed(Document $document, string $error): void
    {
        $this->update($document, [
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }
}
