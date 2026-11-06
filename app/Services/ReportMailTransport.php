<?php
declare(strict_types=1);
namespace Sentinel\Services;
interface ReportMailTransport
{
    public function configured(): bool;
    public function send(string $to,string $subject,string $body,string $messageKey): void;
}
