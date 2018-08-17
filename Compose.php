<?php

namespace Core;

/**
 * Compose different values automatically.
 */
class Compose
{
    /**
     * Create unique string.
     *
     * @param  integer $length Length of the string (default 8)
     * @param  mixed   $chars  Characters to use, defaults to a-z, A-Z and 0-9
     * @return string  Unique string
     */
    public static function unique($length = 8, $chars = false)
    {
        if (!is_string($chars)) {
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        }

        $char_count = strlen($chars) - 1;

        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= $chars[mt_rand(0, $char_count)];
        }

        return $key;
    }

    /**
     * Generate slug from a string.
     *
     * @param  string $value Original string value
     * @return mixed  Slug string or null if unable to generate.
     */
    public static function slug($value)
    {
        $encoding = mb_detect_encoding($value);
        $slug     = iconv($encoding, 'ASCII//TRANSLIT', $value);
        $slug     = preg_replace('/[^a-z0-9-_]/i', '-', $slug);
        $slug     = trim($slug, '-');
        $slug     = strtolower($slug);

        if (empty($slug)) {
            return null;
        }

        return $slug;}

    /**
     * Bytes to human readable form.
     */
    public static function bytesToHuman($bytes, $decimals = 2, $divider = 1024)
    {
        $postfixes = array(
            1000 => array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB'),
            1024 => array('B', 'kiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB'),
        );

        if (!isset($postfixes[$divider])) {
            return $bytes;
        }

        $bytes = floatval($bytes);
        $i     = 0;
        for (; $i < 7; $i++) {
            if (strlen(number_format($bytes)) <= 3) {
                break;
            }
            $bytes /= $divider;
        }

        return number_format($bytes, $i == 0 ? 0 : $decimals) . ' ' . $postfixes[$divider][$i];
    }

    /**
     * Create random (version 4) UUID.
     *
     * @return string UUID
     */
    public static function UUIDv4()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
