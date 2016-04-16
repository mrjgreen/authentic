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
        $bytes = random_bytes($length * 2);

        $safeEncode = str_replace(array('/', '+', '='), '', base64_encode($bytes));

        return substr($safeEncode, 0, $length);
    }
}
