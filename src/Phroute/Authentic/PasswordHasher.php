<?php namespace Phroute\Authentic;

class PasswordHasher {

	public function __construct()
	{
		// Install https://github.com/ircmaxell/password_compat/issues/10
		if ( ! function_exists('password_hash'))
		{
			throw new \RuntimeException('The function password_hash() does not exist, your PHP environment is probably incompatible. Try running [vendor/ircmaxell/password-compat/version-test.php] to check compatibility or use an alternative hashing strategy.');
		}
	}

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
			throw new \RuntimeException('Error generating hash from string, your PHP environment is probably incompatible. Try running [vendor/ircmaxell/password-compat/version-test.php] to check compatibility or use an alternative hashing strategy.');
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
