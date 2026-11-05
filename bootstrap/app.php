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
                $raw = $c['snapshot'] ?? '';
                $snap = json_decode($raw, true);

                logger()->warning('Livewire corrupt snapshot', [
                    'component'   => $snap['memo']['name'] ?? 'unparseable',
                    'prop_keys'   => array_keys($snap['data'] ?? []),
                    'json_error'  => json_last_error_msg(),
                    'valid_utf8'  => mb_check_encoding($raw, 'UTF-8'),
                    'size_bytes'  => strlen($raw),
                    'url'         => $request->headers->get('referer'),
                ]);
            }

            return response('Page expired', 419);
        });
    })->create();
