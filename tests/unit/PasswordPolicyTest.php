<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Password_policy.php';

final class PasswordPolicyTest extends TestCase
{
	public function testCommonPasswordsAndPatternsAreRejected(): void
	{
		$policy = new Password_policy();

		foreach (['Password1234', 'qwertyuiop12', '123456789012', 'aaaaaaaaaaaa'] as $password) {
			self::assertFalse($policy->strong_enough($password), $password);
		}
	}

	public function testUnrelatedPassphrasesAreAccepted(): void
	{
		$policy = new Password_policy();

		self::assertTrue($policy->strong_enough('correct horse battery staple'));
		self::assertTrue($policy->strong_enough('VeryStrongPassword123!'));
	}

	public function testAccountDetailsMakeAPasswordWeak(): void
	{
		$policy = (new Password_policy())->set_context(['ada.lovelace@example.test', 'Lovelace', null, ['tampered']]);

		self::assertFalse($policy->strong_enough('ada.lovelace@example.test1'));
		self::assertFalse($policy->strong_enough('Lovelace2026!'));
	}

	public function testEmptyValuesAreLeftToTheRequiredRule(): void
	{
		self::assertTrue((new Password_policy())->strong_enough(''));
	}
}
