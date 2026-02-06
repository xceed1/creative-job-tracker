<?php
// helpers/nav_counters.php

declare(strict_types=1);

function getNavCounters(PDO $pdo, string $role): array
{
    return match ($role) {

        'TRAFFIC' => [
            'new_jobs' => (int)$pdo->query("
                SELECT COUNT(*) 
                FROM job_orders jo
                JOIN job_status js ON jo.status_id = js.id
                WHERE js.status_code = 'NEW'
            ")->fetchColumn(),

            'action_required' => (int)$pdo->query("
                SELECT COUNT(DISTINCT jo.id)
                FROM job_orders jo
                JOIN job_status js ON jo.status_id = js.id
                WHERE js.status_code = 'COMPLETED'
            ")->fetchColumn(),
        ],

        default => [],
    };
}
