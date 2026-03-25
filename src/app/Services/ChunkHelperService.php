<?php
declare(strict_types=1);

namespace App\Services;

class ChunkHelperService
{
    public static function split(string $text, int $chunkSize = 800, int $overlap = 120): array
    {
        $text = preg_replace(pattern: '/\s+/', replacement: ' ', subject: trim($text));

        if ($text === '') {
            return [];
        }

        $length = mb_strlen($text);
        $chunks = [];
        $start = 0;

        while ($start < $length) {
            $piece = mb_substr($text, $start, $chunkSize);
            $chunks[] = trim($piece);

            if (($start + $chunkSize) >= $length) {
                break;
            }

            $start += ($chunkSize - $overlap);
        }

        return array_values(array_filter($chunks));
    }
}
