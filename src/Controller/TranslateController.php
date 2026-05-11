<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\TranslateFormType;
use App\Translator\LeichteSpracheTranslator;
use App\Translator\TranslationRequest;
use App\Translator\TranslationResult;
use Steg\Exception\StegException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TranslateController extends AbstractController
{
    public function __construct(
        private readonly LeichteSpracheTranslator $translator,
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

            $translationRequest = new TranslationRequest(
                originalText: $data['originalText'],
                temperature: (float) $data['temperature'],
                maxTokens: (int) $data['maxTokens'],
                qualityCheck: (bool) ($data['qualityCheck'] ?? false),
            );

            try {
                $result = $this->translator->translate($translationRequest);
            } catch (StegException $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render('translate/index.html.twig', [
            'form' => $form,
            'result' => $result,
            'error' => $error,
        ]);
    }
}
