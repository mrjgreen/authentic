<?php namespace Phroute\Authentic\Hash;

class PasswordHasher implements HasherInterface {

	/**
	 * Hash string.
	 *
	 * @param  string $string
	 * @return string
	 * @throws \RuntimeException
	 */
	public function hash($string)
	{
		if (($hash = password_hash($string, PASSWORD_DEFAULT)) === false)
		{
			throw new \RuntimeException('Error generating hash from string, your PHP environment may incompatible. Try running [vendor/ircmaxell/password-compat/version-test.php] to check compatibility.');
		}

		return $hash;
	}

	/**
	 * Check string against hashed string.
	 *
	 * @param  string  $string
	 * @param  string  $hashedString
	 * @return bool
	 */
	public function checkHash($string, $hashedString)
	{
		return password_verify($string, $hashedString);
	}

	/**
	 * Check if the algorithm of the input should be upgraded.
	 *
	 * @param  string $hashedString
	 * @return bool
	 */
	public function needsRehash($hashedString)
	{
		return password_needs_rehash($hashedString, PASSWORD_DEFAULT);
	}
}
