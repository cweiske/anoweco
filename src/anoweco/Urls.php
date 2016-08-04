<?php
namespace anoweco;

class Urls
{
    public static function comment($id)
    {
        return '/comment/' . intval($id) . '.htm';
    }

    public static function user($id)
    {
        return '/user/' . intval($id) . '.htm';
    }

    public static function userImg($rowUser = null)
    {
        if ($rowUser !== null && $rowUser->user_imageurl != '') {
            return $rowUser->user_imageurl;
        }
        return static::full('/img/anonymous.svg');
    }

    public static function full($str)
    {
        if (!isset($_SERVER['REQUEST_SCHEME'])) {
            $_SERVER['REQUEST_SCHEME'] = 'http';
        }
        return $_SERVER['REQUEST_SCHEME'] . '://'
            . $_SERVER['HTTP_HOST']
            . $str;
    }
}
?>
