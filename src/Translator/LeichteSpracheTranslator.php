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

        return new TranslationResult(
            originalText: $request->originalText,
            translatedText: trim($response->content),
            model: $response->model,
            promptTokens: $response->promptTokens,
            completionTokens: $response->completionTokens,
            durationMs: $response->durationMs,
        );
    }
}
