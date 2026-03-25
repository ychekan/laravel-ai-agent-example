<?php
declare(strict_types=1);

namespace App\Services;

class PromptHelperService
{
    public static function load(string $name, array $vars = []): array|false|string
    {
        $template = file_get_contents(
            resource_path(path: "prompts/{$name}.txt")
        );

        foreach ($vars as $k => $v) {
            $template = str_replace(search: "{{$k}}", replace: $v, subject: $template);
        }

        return $template;
    }
}
