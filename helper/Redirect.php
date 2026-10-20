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
        self::to(UrlHelper::indexUrl());
    }

    public static function toController($controller, $method)
    {
        self::to(UrlHelper::controllerUrl($controller, $method));
    }
}
