<?php
declare(strict_types=1);
namespace Sentinel\Services;
interface WebhookTransport {public function send(string $url,string $body,array $headers):array;}
