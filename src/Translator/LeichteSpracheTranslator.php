<?php

declare(strict_types=1);

namespace App\Translator;

use App\Audit\AuditTrailService;
use Steg\Client\InferenceClientInterface;
use Steg\Model\ChatMessage;
use Steg\Model\CompletionOptions;

final readonly class LeichteSpracheTranslator
{
    public function __construct(
        private InferenceClientInterface $client,
        private PromptLoader $prompts,
        private ?AuditTrailService $auditTrail = null,
    ) {
    }

    public function translate(TranslationRequest $request): TranslationResult
    {
        $systemPrompt = $this->prompts->load('translate');

        $options = CompletionOptions::leichteSprache()
            ->withTemperature($request->temperature)
            ->withMaxTokens($request->maxTokens);

        $response = $this->client->complete(
            [
                ChatMessage::system($systemPrompt),
                ChatMessage::user($request->originalText),
            ],
            $options,
        );

        $translatedText = trim($response->content);

        $this->auditTrail?->logEvent($request->traceId, 'translate_api_call', [
            'status' => 'completed',
            'system_prompt' => $systemPrompt,
            'user_prompt' => $request->originalText,
            'api_response' => $response->content,
            'model' => $response->model,
            'finish_reason' => $response->finishReason,
            'prompt_tokens' => $response->promptTokens,
            'completion_tokens' => $response->completionTokens,
            'duration_ms' => $response->durationMs,
        ]);

        return new TranslationResult(
            traceId: $request->traceId,
            originalText: $request->originalText,
            translatedText: $translatedText,
            model: $response->model,
            promptTokens: $response->promptTokens,
            completionTokens: $response->completionTokens,
            durationMs: $response->durationMs,
        );
    }
}
