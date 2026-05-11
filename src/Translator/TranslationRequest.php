<?php

declare(strict_types=1);

namespace App\Translator;

final readonly class TranslationRequest
{
    public function __construct(
        public string $originalText,
        public string $traceId = '',
        public float $temperature = 0.3,
        public int $maxTokens = 2048,
        public bool $qualityCheck = false,
    ) {
    }
}
