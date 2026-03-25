<?php
declare(strict_types=1);

namespace App\Services;

use Spatie\PdfToText\Pdf;

class ParserHelperService
{
    public static function parse(string $fullPath): string
    {
        if (!file_exists(filename: $fullPath)) {
            throw new \RuntimeException(message: "File not found: {$fullPath}");
        }

        $extension = strtolower(pathinfo(path: $fullPath, flags: PATHINFO_EXTENSION));

        return match ($extension) {
            'txt', 'md' => trim(file_get_contents(filename: $fullPath)),
            'pdf' => self::parsePdf(fullPath: $fullPath),
            default => throw new \RuntimeException(message: "Unsupported file type: {$extension}"),
        };
    }

    protected static function parsePdf(string $fullPath): string
    {
        $text = Pdf::getText(pdf: $fullPath, binPath: '/usr/bin/pdftotext');

        if ($text !== '') {
            return $text;
        }

        throw new \RuntimeException(message: 'PDF has no extractable text');
    }
}
