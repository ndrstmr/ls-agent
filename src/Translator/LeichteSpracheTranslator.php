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
            $this->auditTrail?->logEvent($request->traceId, 'quality_started', [
                'status' => 'started',
            ]);

            $qualityCheckResult = $this->qualityCheckTool->check($translatedText);

            $this->auditTrail?->logEvent($request->traceId, 'quality_completed', [
                'status' => 'completed',
                'score' => (int) ($qualityCheckResult['score'] ?? 0),
                'issues_count' => count((array) ($qualityCheckResult['issues'] ?? [])),
            ]);
        }

        return new TranslationResult(
            traceId: $request->traceId,
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
