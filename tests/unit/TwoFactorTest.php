<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Two_factor.php';

final class TwoFactorTest extends TestCase
{
    /** RFC 6238 appendix B secret ("12345678901234567890") in base32. */
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    private Two_factor $two_factor;

    protected function setUp(): void
    {
        $this->two_factor = new Two_factor();
    }

    public function testRfcReferenceCodesMatchTheirTimeStep(): void
    {
        // Six-digit truncations of the SHA-1 reference values in RFC 6238 appendix B.
        self::assertSame(1, $this->two_factor->matching_step(self::RFC_SECRET, '287082', 59));
        self::assertSame(37037036, $this->two_factor->matching_step(self::RFC_SECRET, '081804', 1111111109));
        self::assertSame(41152263, $this->two_factor->matching_step(self::RFC_SECRET, '005924', 1234567890));
        self::assertSame(66666666, $this->two_factor->matching_step(self::RFC_SECRET, '279037', 2000000000));
    }

    public function testOneStepOfClockDriftIsTolerated(): void
    {
        self::assertSame(1, $this->two_factor->matching_step(self::RFC_SECRET, '287082', 59 + 30));
        self::assertSame(1, $this->two_factor->matching_step(self::RFC_SECRET, '287082', 59 - 30));
        self::assertNull($this->two_factor->matching_step(self::RFC_SECRET, '287082', 59 + 60));
    }

    public function testMalformedCodesNeverMatch(): void
    {
        foreach (['', '28708', '2870822', 'abcdef', '287 08'] as $code) {
            self::assertNull($this->two_factor->matching_step(self::RFC_SECRET, $code, 59), $code);
        }
        self::assertSame(1, $this->two_factor->matching_step(self::RFC_SECRET, '287 082', 59));
    }

    public function testNewSecretsHave160BitsInBase32(): void
    {
        $secret = $this->two_factor->new_secret();

        self::assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
        self::assertNotSame($secret, $this->two_factor->new_secret());
    }

    public function testProvisioningUriNamesTheAccountAndIssuer(): void
    {
        $uri = $this->two_factor->provisioning_uri(self::RFC_SECRET, 'ada@example.test', 'Acme: Admin');

        self::assertStringStartsWith('otpauth://totp/', $uri);
        self::assertStringContainsString('secret=' . self::RFC_SECRET, $uri);
        self::assertStringContainsString('ada%40example.test', $uri);
        self::assertStringContainsString('issuer=Acme%20%20Admin', $uri);
    }

    public function testQrCodeIsAnSvgDataUri(): void
    {
        $uri = $this->two_factor->qr_data_uri('otpauth://totp/test?secret=' . self::RFC_SECRET);

        self::assertStringStartsWith('data:image/svg+xml;base64,', $uri);
        self::assertStringContainsString('<svg', (string) base64_decode(substr($uri, 26), true));
    }

    public function testRecoveryCodesAreUniqueAndUnambiguous(): void
    {
        $codes = $this->two_factor->recovery_codes(10);

        self::assertCount(10, array_unique($codes));
        foreach ($codes as $code) {
            self::assertMatchesRegularExpression('/^[a-hjkmnp-z2-9]{5}-[a-hjkmnp-z2-9]{5}$/', $code);
        }
    }

    public function testRecoveryCodesMatchHoweverTheyAreTyped(): void
    {
        self::assertSame('abcdefghjk', $this->two_factor->normalize_recovery_code(' ABCDE-FGHJK '));
        self::assertSame('abcdefghjk', $this->two_factor->normalize_recovery_code('abcde fghjk'));
    }
}
