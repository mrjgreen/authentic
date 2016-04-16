<?php namespace Phroute\Authentic;

/**
 * Generate a "random" alphanumeric string.
 *
 * @param  int $length
 * @return string
 */
class RandomStringGenerator
{
    /**
     * @param int $length
     * @return string
     */
    public function generate($length = 20)
    {
        $bytes = random_bytes($length);

        $safeEncode = bin2hex($bytes);

        return substr($safeEncode, 0, $length);
    }
}
