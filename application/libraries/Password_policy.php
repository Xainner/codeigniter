<?php defined('BASEPATH') OR exit('No direct script access allowed');

use ZxcvbnPhp\Zxcvbn;

/**
 * Rejects passwords that are easy to guess even when they are long enough:
 * common passwords, keyboard patterns, sequences and the account's own data.
 */
class Password_policy
{
    /** zxcvbn score 3: about 10^8 guesses or more ("safely unguessable" online). */
    public const MIN_SCORE = 3;

    private $context = [];

    /** Names, addresses and other account details that must not make a password guessable. */
    public function set_context(array $values)
    {
        $this->context = [];
        foreach ($values as $value) {
            if (is_scalar($value) && trim((string) $value) !== '') {
                $this->context[] = (string) $value;
            }
        }
        return $this;
    }

    /** form_validation rule. Empty values are left to the required rule. */
    public function strong_enough($password)
    {
        $password = (string) $password;
        if ($password === '') {
            return TRUE;
        }
        return (new Zxcvbn())->passwordStrength($password, $this->context)['score'] >= self::MIN_SCORE;
    }
}
