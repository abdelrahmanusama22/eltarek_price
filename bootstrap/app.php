<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
$tempPath = __DIR__ . '/../storage/app/temp';
if (!file_exists($tempPath)) {
    @mkdir($tempPath, 0775, true);
}
putenv("TMP=" . $tempPath);
putenv("TEMP=" . $tempPath);
putenv("TMPDIR=" . $tempPath);
// ------------------------
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('sales') || $request->is('sales/*')) {
                return route('sales.login');
            }
            // For admin and others, let Filament or default handle it, or fallback to standard login
            return route('filament.admin.auth.login'); 
        });

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (Throwable $e) {
            if (request()->is('livewire/upload-file*')) {
                \Illuminate\Support\Facades\Log::error('Livewire Upload Failed:', [
                    'message' => $e->getMessage(),
                    'class' => get_class($e),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
