<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $stateful = config('sanctum.stateful', []);
        $request = request();

        $requestHost = $request->getHost();
        if ($requestHost) {
            $stateful[] = $requestHost;
            $stateful[] = $requestHost . ':' . $request->getPort();
        }

        $origin = $request->headers->get('origin');
        if ($origin) {
            $host = parse_url($origin, PHP_URL_HOST);
            $port = parse_url($origin, PHP_URL_PORT);
            if ($host) {
                $stateful[] = $host;
                if ($port) {
                    $stateful[] = $host . ':' . $port;
                }
            }
        }

        config(['sanctum.stateful' => array_values(array_unique(array_filter($stateful)))]);
    }
}