<?php

declare(strict_types=1);

final readonly class Migration
{
    public function __construct(public string $name, public string $path)
    {
        if ($name === '' || !str_ends_with($name, '.sql')) {
            throw new InvalidArgumentException('Migration name must be a SQL filename.');
        }
    }
}
