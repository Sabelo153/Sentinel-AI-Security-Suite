<?php

declare(strict_types=1);

final class Migrator
{
    /** @param list<array{name:string,path:string}> $plan */
    public function __construct(private readonly array $plan)
    {
    }

    /** @return list<Migration> */
    public function validatedPlan(?string $until = null): array
    {
        $migrations = [];
        $foundUntil = $until === null;
        foreach ($this->plan as $item) {
            if (!is_file($item['path']) || !is_readable($item['path'])) {
                throw new RuntimeException('Schema source is missing or unreadable: ' . $item['name']);
            }
            $migrations[] = new Migration($item['name'], $item['path']);
            if ($until !== null && $item['name'] === $until) {
                $foundUntil = true;
                break;
            }
        }
        if ($migrations === []) {
            throw new RuntimeException('The canonical schema runner contains no sources.');
        }
        if (!$foundUntil) {
            throw new InvalidArgumentException('Unknown --until migration: ' . $until);
        }
        return $migrations;
    }
}
