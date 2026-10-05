<?php

declare(strict_types=1);

namespace Sentinel\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Single reusable PDO connection for the application. Connection is created
 * lazily and cached; nothing here builds SQL from unescaped values.
 */
final class Database
{
    private ?PDO $pdo = null;

    /**
     * $connector is a test seam: it lets tests substitute the real PDO
     * constructor (e.g. with SQLite in-memory, or a throwing stub) without
     * touching application configuration or a live MySQL server.
     *
     * @param (callable(string, string, string, array<int, mixed>): PDO)|null $connector
     */
    public function __construct(
        private readonly Config $config,
        private readonly mixed $connector = null,
    ) {
    }

    /**
     * Build the MySQL/MariaDB DSN from configuration only — never from
     * caller/request input.
     */
    public function dsn(): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            (string) $this->config->get('database.host', '127.0.0.1'),
            (int) $this->config->get('database.port', 3306),
            (string) $this->config->get('database.database', ''),
            (string) $this->config->get('database.charset', 'utf8mb4'),
        );
    }

    public function connect(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->createConnection();
        }

        return $this->pdo;
    }

    public function connection(): PDO
    {
        return $this->connect();
    }

    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    public function disconnect(): void
    {
        $this->pdo = null;
    }

    public function beginTransaction(): void
    {
        $this->connect()->beginTransaction();
    }

    public function commit(): void
    {
        $this->connect()->commit();
    }

    public function rollBack(): void
    {
        $this->connect()->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->connect()->inTransaction();
    }

    /**
     * @param array<int|string, mixed> $params
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->connect()->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /**
     * @param array<int|string, mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<int|string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    private function createConnection(): PDO
    {
        $username = (string) $this->config->get('database.username', '');
        $password = (string) $this->config->get('database.password', '');
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => (int) $this->config->get('database.timeout', 5),
        ];

        $factory = $this->connector ?? static fn (string $dsn, string $user, string $pass, array $opts): PDO
            => new PDO($dsn, $user, $pass, $opts);

        try {
            return $factory($this->dsn(), $username, $password, $options);
        } catch (PDOException $e) {
            // Never surface the DSN/username/password from the underlying driver exception.
            throw new HttpException(503, 'Unable to connect to the database.', 'database_connection_failed', $e);
        }
    }
}
