<?php

namespace Core;

/*! \addtogroup Core
 * @{
 */

/******************************************************************************/
class Validate
{
    /**
     * Validate a Fully Qualified Domain Name (FQDN).
     *
     * @param  string $domain         string to validate as FQDN
     * @param  bool   $allow_wildcard if true, allows *-wildcard at start of the domain, default is false
     * @return bool   true when ok, false otherwise
     */
    public static function FQDN($domain, $allow_wildcard = false)
    {
        if ($allow_wildcard and substr($domain, 0, 2) == '*.') {
            $domain = substr($domain, 2);
        }

        $pattern = '/(?=^.{1,254}$)(^(?:(?!\d|-)[a-zA-Z0-9\-_]{1,63}(?<!-)\.?)+(?:[a-zA-Z]{2,})$)/i';
        if (!strpbrk($domain, '.')) {
            return false;
        }
        return !empty($domain) && preg_match($pattern, $domain) > 0;
    }

    /**
     * Validate simple slug. Allow only lower case ascii, numbers and underscore.
     *
     * @param  string $slug slug to validate
     * @return bool   true when ok, false otherwise
     */
    public static function slug($slug)
    {
        $pattern = '/^[a-z][a-z_0-9]*$/';
        return !empty($slug) && preg_match($pattern, $slug) > 0;
    }

    /**
     * Validate email.
     */
    public static function email($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check whether or not an array is associative.
     *
     * @param  array $value array to check
     * @return bool  true if associative, false if not
     */
    public static function assoc($array)
    {
        foreach (array_keys($array) as $k => $v) {
            if ($k !== $v) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validate UUID.
     * @param  $uuid   UUID string
     * @return boolean true if UUID, false if not
     */
    public static function UUID($uuid)
    {
        return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?' .
            '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
    }
}

/*! @} endgroup Core */
