<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DocumentRequest;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;

class DocumentController extends Controller
{
    #[Post('/api/documents')]
    public function store(DocumentRequest $request, DocumentService $service): JsonResponse
    {
        $documents = $service->processDocument(request: $request->validated());

        return response()->json([
            'message' => 'Document uploaded and queued for indexing.',
            'documents' => $documents,
        ], 201);
    }

    #[Get('/api/documents/{document}')]
    public function show(Document $document): JsonResponse
    {
        return response()->json($document->load(relations: 'chunks'));
    }
}
