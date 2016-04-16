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
        return substr(bin2hex(random_bytes($length)), 0, $length);
    }
}
