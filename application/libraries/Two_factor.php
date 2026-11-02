<?php defined('BASEPATH') OR exit('No direct script access allowed');

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use OTPHP\TOTP;
use Psr\Clock\ClockInterface;

/**
 * Time-based one-time passwords (RFC 6238: 6 digits, 30 seconds, SHA-1, as
 * authenticator apps expect) and single-use recovery codes.
 */
class Two_factor
{
	/** Codes from the previous and next 30-second step are accepted for clock drift. */
	private const DRIFT_STEPS = 1;
	private const PERIOD = 30;
	private const SECRET_BYTES = 20;
	private const RECOVERY_ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

	public function new_secret()
	{
		return TOTP::generate(new Two_factor_clock(), self::SECRET_BYTES)->getSecret();
	}

	/** otpauth:// URI that authenticator apps import from the QR code. */
	public function provisioning_uri($secret, $account, $issuer)
	{
		$totp = $this->totp($secret);
		// Colons separate issuer and account in the URI label.
		$totp->setLabel(str_replace(':', ' ', (string) $account));
		$issuer = trim(str_replace(':', ' ', (string) $issuer));
		if ($issuer !== '') {
			$totp->setIssuer($issuer);
		}
		return $totp->getProvisioningUri();
	}

	/** SVG QR code as a data: URI, which the Content-Security-Policy allows for images. */
	public function qr_data_uri($text)
	{
		$writer = new Writer(new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd()));
		return 'data:image/svg+xml;base64,' . base64_encode($writer->writeString((string) $text));
	}

	/**
	 * The time step whose code matches, or NULL. Callers store the step and
	 * reject it afterwards, so a code that was seen once cannot be replayed.
	 */
	public function matching_step($secret, $code, $now = NULL)
	{
		$code = preg_replace('/\s+/', '', (string) $code);
		if (!preg_match('/^\d{6}$/D', $code)) {
			return NULL;
		}
		$totp = $this->totp($secret);
		$now = $now === NULL ? time() : (int) $now;
		$current = intdiv($now, self::PERIOD);
		for ($step = max(0, $current - self::DRIFT_STEPS); $step <= $current + self::DRIFT_STEPS; $step++) {
			if (hash_equals($totp->at($step * self::PERIOD), $code)) {
				return $step;
			}
		}
		return NULL;
	}

	/** Random codes such as "k7mnp-x2qrt", about 49 bits each, without look-alike characters. */
	public function recovery_codes($count)
	{
		$codes = [];
		$last = strlen(self::RECOVERY_ALPHABET) - 1;
		while (count($codes) < $count) {
			$code = '';
			for ($i = 0; $i < 10; $i++) {
				$code .= self::RECOVERY_ALPHABET[random_int(0, $last)];
			}
			$codes[substr($code, 0, 5) . '-' . substr($code, 5)] = TRUE;
		}
		return array_keys($codes);
	}

	/** Lowercase, without spaces or dashes, so codes match however they are typed. */
	public function normalize_recovery_code($code)
	{
		return strtolower(preg_replace('/[\s\-]+/', '', (string) $code));
	}

	private function totp($secret)
	{
		return TOTP::createFromSecret((string) $secret, new Two_factor_clock());
	}
}

/** Wall clock for otphp, which requires a PSR-20 clock. */
final class Two_factor_clock implements ClockInterface
{
	public function now(): DateTimeImmutable
	{
		return new DateTimeImmutable();
	}
}
