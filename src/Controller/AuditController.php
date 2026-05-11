<?php

declare(strict_types=1);

namespace App\Controller;

use App\Audit\AuditTrailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuditController extends AbstractController
{
    public function __construct(
        private readonly AuditTrailService $auditTrail,
    ) {
    }

    #[Route('/audit', name: 'app_audit', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $traceId = trim((string) $request->query->get('trace_id', ''));
        if ('' === $traceId) {
            $traceId = null;
        }

        $events = $this->auditTrail->readEvents($traceId, 250);

        return $this->render('audit/index.html.twig', [
            'events' => $events,
            'traceId' => $traceId,
        ]);
    }

    #[Route('/audit-events', name: 'app_audit_events', methods: ['GET'])]
    public function events(Request $request): JsonResponse
    {
        $traceId = trim((string) $request->query->get('trace_id', ''));
        if ('' === $traceId) {
            $traceId = null;
        }

        $events = $this->auditTrail->readEvents($traceId, 100);

        return $this->json($events);
    }
}
