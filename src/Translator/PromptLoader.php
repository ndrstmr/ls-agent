<?php

declare(strict_types=1);

namespace App\Translator;

final readonly class PromptLoader
{
    public function __construct(
        private string $promptDir,
        private string $version,
    ) {
    }

    public function load(string $name): string
    {
        $path = sprintf('%s/%s/%s.txt', $this->promptDir, $this->version, $name);

        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('Prompt file not found: %s', $path));
        }

        $content = file_get_contents($path);
        if (false === $content || '' === trim($content)) {
            throw new \RuntimeException(sprintf('Prompt file is empty or unreadable: %s', $path));
        }

        return rtrim($content);
    }
}
