<?php

declare(strict_types=1);

namespace App\Audit;

final readonly class AuditTrailService
{
    public function __construct(
        private string $projectDir,
        private string $shareDir,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function logEvent(string $traceId, string $eventType, array $context = []): void
    {
        $event = [
            'trace_id' => $traceId,
            'event_type' => $eventType,
            'created_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ] + $context;

        $line = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (false === $line) {
            return;
        }

        $path = $this->auditLogPath();
        $dir = \dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0o775, true);
        }

        $handle = fopen($path, 'ab');
        if (false === $handle) {
            return;
        }

        try {
            if (flock($handle, LOCK_EX)) {
                fwrite($handle, $line . PHP_EOL);
                fflush($handle);
                flock($handle, LOCK_UN);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function readEvents(?string $traceId = null, int $limit = 200): array
    {
        $path = $this->auditLogPath();
        if (!is_file($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (false === $lines) {
            return [];
        }

        $events = [];
        foreach (array_reverse($lines) as $line) {
            $decoded = json_decode($line, true);
            if (!is_array($decoded)) {
                continue;
            }

            if (null !== $traceId && ($decoded['trace_id'] ?? null) !== $traceId) {
                continue;
            }

            $events[] = $decoded;
            if (count($events) >= $limit) {
                break;
            }
        }

        return $events;
    }

    public function hashText(string $text): string
    {
        return hash('sha256', $text);
    }

    private function auditLogPath(): string
    {
        return sprintf('%s/%s/audit-trail.ndjson', rtrim($this->projectDir, '/'), trim($this->shareDir, '/'));
    }
}
