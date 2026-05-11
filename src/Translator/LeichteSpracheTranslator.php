<?php

declare(strict_types=1);

namespace App\Translator;

use Steg\Client\InferenceClientInterface;
use Steg\Model\ChatMessage;
use Steg\Model\CompletionOptions;

final readonly class LeichteSpracheTranslator
{
    public function __construct(
        private InferenceClientInterface $client,
        private PromptLoader $prompts,
        private ?QualityCheckTool $qualityCheckTool = null,
    ) {
    }

    public function translate(TranslationRequest $request): TranslationResult
    {
        $options = CompletionOptions::leichteSprache()
            ->withTemperature($request->temperature)
            ->withMaxTokens($request->maxTokens);

        $response = $this->client->complete(
            [
                ChatMessage::system($this->prompts->load('translate')),
                ChatMessage::user($request->originalText),
            ],
            $options,
        );

        $translatedText = trim($response->content);
        $qualityCheckResult = null;

        // Optional: Qualitätsprüfung nach Übersetzung
        if ($request->qualityCheck && null !== $this->qualityCheckTool) {
            $qualityCheckResult = $this->qualityCheckTool->check($translatedText);
        }

        return new TranslationResult(
            originalText: $request->originalText,
            translatedText: $translatedText,
            model: $response->model,
            promptTokens: $response->promptTokens,
            completionTokens: $response->completionTokens,
            durationMs: $response->durationMs,
            qualityCheck: $qualityCheckResult,
        );
    }
}
