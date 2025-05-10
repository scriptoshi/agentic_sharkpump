<?php

namespace App;

use Illuminate\Support\Facades\Route as RouteFacade;

class Route
{
    public static function launchpad(): string
    {
        $launchpad = RouteFacade::current()->parameter('launchpad');
        $session = session('launchpad');
        if (!$session && $launchpad)  session(['launchpad' => $launchpad]);
        if (($session && $launchpad) && $session !== $launchpad) session(['launchpad' => $launchpad]);
        return  $launchpad ?? $session;
    }

    public static function parameter($name)
    {
        return RouteFacade::current()->parameter($name);
    }
}
