<?php

declare(strict_types=1);

namespace App\Controller;

use App\Audit\AuditTrailService;
use App\Form\TranslateFormType;
use App\Translator\LeichteSpracheTranslator;
use App\Translator\TranslationRequest;
use Steg\Exception\StegException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class TranslateController extends AbstractController
{
    public function __construct(
        private readonly LeichteSpracheTranslator $translator,
        private readonly AuditTrailService $auditTrail,
    ) {
    }

    #[Route('/', name: 'app_translate', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $form = $this->createForm(TranslateFormType::class, [
            'originalText' => '',
            'temperature' => 0.3,
            'maxTokens' => 2048,
        ]);

        $form->handleRequest($request);

        $result = null;
        $error = null;

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{originalText: string, temperature: float|string, maxTokens: int|string, qualityCheck?: bool} $data */
            $data = $form->getData();

            $traceId = Uuid::v7()->toRfc4122();

            $this->auditTrail->logEvent($traceId, 'translate_started', [
                'status' => 'started',
                'input_hash' => $this->auditTrail->hashText($data['originalText']),
                'input_length' => mb_strlen($data['originalText']),
                'quality_check_enabled' => (bool) ($data['qualityCheck'] ?? false),
            ]);

            $translationRequest = new TranslationRequest(
                traceId: $traceId,
                originalText: $data['originalText'],
                temperature: (float) $data['temperature'],
                maxTokens: (int) $data['maxTokens'],
                qualityCheck: (bool) ($data['qualityCheck'] ?? false),
            );

            try {
                $result = $this->translator->translate($translationRequest);

                $this->auditTrail->logEvent($traceId, 'translate_completed', [
                    'status' => 'completed',
                    'model' => $result->model,
                    'duration_ms' => $result->durationMs,
                    'prompt_tokens' => $result->promptTokens,
                    'completion_tokens' => $result->completionTokens,
                    'output_hash' => $this->auditTrail->hashText($result->translatedText),
                    'output_length' => mb_strlen($result->translatedText),
                ]);
            } catch (StegException $e) {
                $error = $e->getMessage();

                $this->auditTrail->logEvent($traceId, 'translate_failed', [
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        return $this->render('translate/index.html.twig', [
            'form' => $form,
            'result' => $result,
            'error' => $error,
        ]);
    }
}
