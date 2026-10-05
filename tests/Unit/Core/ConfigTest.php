<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\Config;

final class ConfigTest extends TestCase
{
    public function testNestedDotNotationLookup(): void
    {
        $config = new Config(['database' => ['host' => '127.0.0.1', 'port' => 3306]]);

        self::assertSame('127.0.0.1', $config->get('database.host'));
        self::assertSame(3306, $config->get('database.port'));
    }

    public function testDefaultIsReturnedForMissingKey(): void
    {
        $config = new Config(['app' => ['name' => 'Sentinel']]);

        self::assertSame('fallback', $config->get('app.missing', 'fallback'));
        self::assertNull($config->get('app.missing'));
    }

    public function testHasDistinguishesMissingFromNullValues(): void
    {
        $config = new Config(['jwt' => ['secret' => null]]);

        self::assertTrue($config->has('jwt.secret'));
        self::assertFalse($config->has('jwt.issuer'));
    }

    public function testDrillingIntoNonArrayValueReturnsDefault(): void
    {
        $config = new Config(['app' => ['name' => 'Sentinel']]);

        self::assertSame('fallback', $config->get('app.name.nested', 'fallback'));
    }

    public function testSetWritesNestedValue(): void
    {
        $config = new Config();
        $config->set('app.debug', true);

        self::assertTrue($config->get('app.debug'));
    }

    public function testFromDirectoryLoadsPhpFilesKeyedByFilename(): void
    {
        $dir = sys_get_temp_dir() . '/sentinel-config-test-' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/app.php', "<?php\nreturn ['name' => 'Test App'];\n");

        try {
            $config = Config::fromDirectory($dir);

            self::assertSame('Test App', $config->get('app.name'));
        } finally {
            unlink($dir . '/app.php');
            rmdir($dir);
        }
    }
}
