<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Livewire\Mechanisms\HandleComponents\CorruptComponentPayloadException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (CorruptComponentPayloadException $e, $request) {
            foreach ((array) $request->input('components', []) as $c) {
                $snap = json_decode($c['snapshot'] ?? '', true) ?: [];

                logger()->warning('Livewire corrupt snapshot', [
                    'component'     => $snap['memo']['name'] ?? 'unknown',
                    'calls'         => array_map(fn($x) => $x['method'] ?? null, $c['calls'] ?? []),
                    'ua'            => $request->userAgent(),
                    'ip'            => $request->ip(),
                    'session'       => $request->hasSession() ? substr($request->session()->getId(), 0, 8) : null,
                    'key_fp'        => substr(hash('sha256', (string) config('app.key')), 0, 8),
                    'base_path'     => base_path(),
                    'config_cached' => file_exists(base_path('bootstrap/cache/config.php')),
                    'reencode_same' => json_encode(json_decode($c['snapshot'] ?? '', true)) === ($c['snapshot'] ?? ''),
                    'raw_len'       => strlen($c['snapshot'] ?? ''),
                    'referer'       => $request->headers->get('referer'),
                ]);
            }

            return response('Page expired', 419);
        });
    })->create();
