<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Secret_box.php';

final class SecretBoxTest extends TestCase
{
    private const APP_KEY = 'abababababababababababababababababababababababababababababababab';

    private Secret_box $box;

    protected function setUp(): void
    {
        putenv('APP_KEY=' . self::APP_KEY);
        $this->box = new Secret_box();
    }

    protected function tearDown(): void
    {
        putenv('APP_KEY=' . self::APP_KEY);
    }

    public function testSealedValuesOpenForTheSamePurpose(): void
    {
        $sealed = $this->box->seal('smtp secret', 'smtp');

        self::assertStringStartsWith('v2:', $sealed);
        self::assertStringNotContainsString('smtp secret', $sealed);
        self::assertSame('smtp secret', $this->box->open($sealed, 'smtp'));
        self::assertSame('', $this->box->open($this->box->seal('', 'smtp'), 'smtp'));
    }

    public function testEverySealUsesAFreshNonce(): void
    {
        self::assertNotSame($this->box->seal('same', 'smtp'), $this->box->seal('same', 'smtp'));
    }

    public function testValuesNeverOpenForAnotherPurpose(): void
    {
        self::assertNull($this->box->open($this->box->seal('secret', 'smtp'), 'totp'));
    }

    public function testDamagedOrForeignValuesDoNotOpen(): void
    {
        $raw = base64_decode(substr($this->box->seal('secret', 'smtp'), 3), true);
        $raw[strlen($raw) - 1] = chr(ord($raw[strlen($raw) - 1]) ^ 1);

        self::assertNull($this->box->open('v2:' . base64_encode($raw), 'smtp'));
        self::assertNull($this->box->open('v2:not base64!', 'smtp'));
        self::assertNull($this->box->open('v2:' . base64_encode('short'), 'smtp'));
        self::assertNull($this->box->open('legacy-value', 'smtp'));
        self::assertNull($this->box->open(null, 'smtp'));
    }

    public function testAnotherAppKeyCannotOpenValues(): void
    {
        $sealed = $this->box->seal('secret', 'smtp');
        putenv('APP_KEY=' . str_repeat('cd', 32));

        self::assertNull($this->box->open($sealed, 'smtp'));
    }

    public function testPurposeKeysAreDistinctAndNeverTheAppKeyItself(): void
    {
        $smtp = $this->box->key('smtp');

        self::assertSame(32, strlen($smtp));
        self::assertSame($smtp, $this->box->key('smtp'));
        self::assertNotSame($smtp, $this->box->key('rate_limiter'));
        self::assertNotSame(hex2bin(self::APP_KEY), $smtp);
    }

    public function testMalformedAppKeyIsRejected(): void
    {
        putenv('APP_KEY=not-a-key');

        $this->expectException(RuntimeException::class);
        $this->box->key('smtp');
    }
}
