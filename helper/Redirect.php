<?php

class Redirect
{
    public static function to($url)
    {
        header("Location: $url");
        exit();
    }

    public static function toIndex()
    {
        self::to('/');
    }
}
