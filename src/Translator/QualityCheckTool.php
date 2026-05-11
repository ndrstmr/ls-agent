<?php

declare(strict_types=1);

namespace App\Translator;

use Steg\Client\InferenceClientInterface;
use Steg\Model\ChatMessage;
use Steg\Model\CompletionOptions;

/**
 * QualityCheckTool — prüft Leichte-Sprache-Texte gegen die DIN SPEC 33429.
 *
 * Wird als optionaler zweiter Modell-Call nach der Übersetzung aufgerufen
 * wenn qualityCheck=true im Request. Nutzt den quality-check.txt Prompt
 * und gibt Score + Issues + Summary als strukturiertes JSON zurück.
 *
 * @return array{score: int, issues: list<array{rule: string, severity: string, text: string, suggestion: string}>, summary: string}
 */
final readonly class QualityCheckTool
{
    public function __construct(
        private InferenceClientInterface $client,
        private PromptLoader $prompts,
    ) {
    }

    /**
     * @return array{score: int, issues: list<array>, summary: string}
     */
    public function check(string $translatedText): array
    {
        if ('' === trim($translatedText)) {
            return ['score' => 0, 'issues' => [], 'summary' => 'Kein Text zum Prüfen übergeben.'];
        }

        try {
            $systemPrompt = $this->prompts->load('quality-check');

            $response = $this->client->complete(
                [
                    ChatMessage::system($systemPrompt),
                    ChatMessage::user($translatedText),
                ],
                CompletionOptions::precise()->withMaxTokens(1024),
            );

            $content = trim($response->content);

            // JSON-Extraktion: Modelle schreiben manchmal ```json ... ``` drum herum
            if (preg_match('/\{.*\}/s', $content, $matches)) {
                $content = $matches[0];
            }

            $result = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($result)) {
                return ['score' => 0, 'issues' => [], 'summary' => 'Qualitätsprüfung: Ungültige Antwort vom Modell.'];
            }

            return [
                'score' => (int) ($result['score'] ?? 0),
                'issues' => (array) ($result['issues'] ?? []),
                'summary' => (string) ($result['summary'] ?? ''),
            ];
        } catch (\Throwable $e) {
            return ['score' => 0, 'issues' => [], 'summary' => 'Qualitätsprüfung fehlgeschlagen: ' . $e->getMessage()];
        }
    }
}
