<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('base_url')) {
    // Stands in for CodeIgniter's url_helper, which needs a booted application.
    function base_url($uri = ''): string
    {
        return 'https://app.example.test/' . ltrim((string) $uri, '/');
    }
}

require_once APPPATH . 'helpers/asset_helper.php';

final class AssetUrlTest extends TestCase
{
    public function testExistingFilesCarryTheirModificationTime(): void
    {
        $expected = 'https://app.example.test/assets/auth.css?v=' . filemtime(FCPATH . 'assets/auth.css');

        self::assertSame($expected, asset_url('assets/auth.css'));
        self::assertSame($expected, asset_url('/assets/auth.css'));
    }

    public function testMissingFilesAreLinkedWithoutAVersion(): void
    {
        self::assertSame('https://app.example.test/assets/missing.css', asset_url('assets/missing.css'));
    }
}
