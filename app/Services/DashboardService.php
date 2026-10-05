<?php

declare(strict_types=1);

namespace Sentinel\Services;

use Sentinel\Repositories\DashboardRepository;

final class DashboardService
{
    public function __construct(private readonly DashboardRepository $dashboard) {}

    /** @return array<string, mixed> */
    public function summary(): array
    {
        return [
            'metrics' => $this->dashboard->summary(date('Y-m-d')),
            'recent_activity' => $this->dashboard->recentActivity(20),
        ];
    }
}