<?php
class Session
{
    function get($var)
    {
        if (is_array($var))
            $var = implode("-", $var);

        $var = "vippy-".strtolower($var);
        if (isset($_SESSION[$var]))
            return $_SESSION[$var];
        return null;
    }

    function set($var, $val)
    {
        if (is_array($var))
            $var = implode("-",$var);

        $var = "vippy-".strtolower($var);
        $_SESSION[$var] = $val;
        return true;
    }

    function remove($var)
    {
        if (is_array($var))
            $var = implode("-",$var);

        $var = "vippy-".strtolower($var);
        $_SESSION[$var] = null;
    }


    /**
     * Get session
     * @return \Session
     */
    public static function getSession()
    {
        return new \Session();
    }
}