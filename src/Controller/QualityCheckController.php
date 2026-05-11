<?php

declare(strict_types=1);

namespace App\Controller;

use App\Audit\AuditTrailService;
use App\Translator\QualityCheckTool;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class QualityCheckController extends AbstractController
{
    public function __construct(
        private readonly QualityCheckTool $qualityCheckTool,
        private readonly AuditTrailService $auditTrail,
    ) {
    }

    #[Route('/quality-check', name: 'app_quality_check', methods: ['POST'])]
    public function check(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Ungültige Anfrage.'], 400);
        }

        $translatedText = trim((string) ($data['translatedText'] ?? ''));
        $traceId = trim((string) ($data['traceId'] ?? ''));

        if ('' === $translatedText) {
            return $this->json(['error' => 'Kein Text übergeben.'], 400);
        }

        if ('' !== $traceId) {
            $this->auditTrail->logEvent($traceId, 'quality_started', ['status' => 'started']);
        }

        $result = $this->qualityCheckTool->check($translatedText);

        if ('' !== $traceId) {
            $this->auditTrail->logEvent($traceId, 'quality_completed', [
                'status' => 'completed',
                'score' => $result['score'],
                'system_prompt' => $result['prompt'] ?? '',
                'user_prompt' => $translatedText,
                'api_response' => $result['raw_response'] ?? '',
            ]);
        }

        // Strip internal fields before sending to frontend
        unset($result['prompt'], $result['raw_response']);

        return $this->json($result);
    }
}
