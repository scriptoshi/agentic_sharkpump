<?php

namespace App;

use Illuminate\Support\Facades\Route as RouteFacade;
use App\Models\Launchpad;

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

    public static function lpd(): ?string
    {
        $address = self::launchpad();
        $lpd = Launchpad::where('contract', $address)->first();
        return $lpd->symbol ?? __('xxDashboard');
    }



    public static function parameter($name)
    {
        return RouteFacade::current()->parameter($name);
    }
}
