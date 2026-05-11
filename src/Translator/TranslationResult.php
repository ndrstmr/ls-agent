<?php

declare(strict_types=1);

namespace App\Translator;

final readonly class TranslationResult
{
    public function __construct(
        public string $originalText,
        public string $translatedText,
        public string $model,
        public int $promptTokens,
        public int $completionTokens,
        public float $durationMs,
        public ?array $qualityCheck = null,
    ) {
    }
}
