<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\HttpException;

final class DatabaseTest extends TestCase
{
    private const FAKE_PASSWORD = 'PUBLIC_EXAMPLE_CONFIGURE_LOCALLY';

    private function makeConfig(): Config
    {
        return new Config([
            'database' => [
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'sentinel_test',
                'username' => 'sentinel_test_user',
                'password' => self::FAKE_PASSWORD,
                'charset' => 'utf8mb4',
                'timeout' => 5,
            ],
        ]);
    }

    /** In-memory SQLite stands in for MySQL so tests never touch a real server. */
    private function sqliteConnector(): callable
    {
        return static fn (string $dsn, string $user, string $pass, array $options): PDO
            => new PDO('sqlite::memory:', null, null, $options);
    }

    public function testDsnIsBuiltFromConfiguration(): void
    {
        $db = new Database($this->makeConfig());

        self::assertSame(
            'mysql:host=127.0.0.1;port=3306;dbname=sentinel_test;charset=utf8mb4',
            $db->dsn(),
        );
    }

    public function testConnectionIsLazy(): void
    {
        $db = new Database($this->makeConfig(), $this->sqliteConnector());

        self::assertFalse($db->isConnected());
        $db->connect();
        self::assertTrue($db->isConnected());
    }

    public function testRepeatedConnectCallsReuseSameInstance(): void
    {
        $db = new Database($this->makeConfig(), $this->sqliteConnector());

        self::assertSame($db->connect(), $db->connect());
    }

    public function testConnectorReceivesConfigurationValues(): void
    {
        $captured = [];
        $connector = function (string $dsn, string $user, string $pass, array $options) use (&$captured): PDO {
            $captured = [$dsn, $user, $pass];

            return new PDO('sqlite::memory:', null, null, $options);
        };

        $db = new Database($this->makeConfig(), $connector);
        $db->connect();

        self::assertSame('mysql:host=127.0.0.1;port=3306;dbname=sentinel_test;charset=utf8mb4', $captured[0]);
        self::assertSame('sentinel_test_user', $captured[1]);
        self::assertSame(self::FAKE_PASSWORD, $captured[2]);
    }

    public function testDisconnectClearsConnection(): void
    {
        $db = new Database($this->makeConfig(), $this->sqliteConnector());

        $db->connect();
        self::assertTrue($db->isConnected());

        $db->disconnect();
        self::assertFalse($db->isConnected());
    }

    public function testTransactionLifecycle(): void
    {
        $db = new Database($this->makeConfig(), $this->sqliteConnector());
        $db->execute('CREATE TABLE t (id INTEGER PRIMARY KEY, name TEXT)');

        $db->beginTransaction();
        self::assertTrue($db->inTransaction());
        $db->execute('INSERT INTO t (name) VALUES (:name)', ['name' => 'a']);
        $db->rollBack();

        self::assertFalse($db->inTransaction());
        self::assertSame([], $db->fetchAll('SELECT * FROM t'));
    }

    public function testCommitPersistsChanges(): void
    {
        $db = new Database($this->makeConfig(), $this->sqliteConnector());
        $db->execute('CREATE TABLE t (id INTEGER PRIMARY KEY, name TEXT)');

        $db->beginTransaction();
        $db->execute('INSERT INTO t (name) VALUES (:name)', ['name' => 'a']);
        $db->commit();

        self::assertCount(1, $db->fetchAll('SELECT * FROM t'));
    }

    public function testParameterizedQueryFetchAndFetchAll(): void
    {
        $db = new Database($this->makeConfig(), $this->sqliteConnector());
        $db->execute('CREATE TABLE clients (id INTEGER PRIMARY KEY, name TEXT)');
        $db->execute('INSERT INTO clients (name) VALUES (:name)', ['name' => "Acme; DROP TABLE clients;--"]);

        $row = $db->fetch('SELECT * FROM clients WHERE name = :name', ['name' => "Acme; DROP TABLE clients;--"]);
        self::assertNotNull($row);
        self::assertSame("Acme; DROP TABLE clients;--", $row['name']);

        self::assertCount(1, $db->fetchAll('SELECT * FROM clients'));
    }

    public function testFetchReturnsNullWhenNoRowMatches(): void
    {
        $db = new Database($this->makeConfig(), $this->sqliteConnector());
        $db->execute('CREATE TABLE clients (id INTEGER PRIMARY KEY, name TEXT)');

        self::assertNull($db->fetch('SELECT * FROM clients WHERE id = :id', ['id' => 999]));
    }

    public function testConnectionFailureThrowsControlledHttpException(): void
    {
        $connector = function (): PDO {
            throw new PDOException("SQLSTATE[HY000] [1045] Access denied for user 'sentinel_test_user'@'localhost' (using password: YES) with password " . self::FAKE_PASSWORD);
        };

        $db = new Database($this->makeConfig(), $connector);

        try {
            $db->connect();
            self::fail('Expected HttpException was not thrown.');
        } catch (HttpException $e) {
            self::assertSame(503, $e->statusCode());
            self::assertStringNotContainsString(self::FAKE_PASSWORD, $e->getMessage());
            self::assertStringNotContainsString('sentinel_test_user', $e->getMessage());
        }
    }
}
