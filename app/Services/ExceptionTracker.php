<?php

namespace App\Services;

use App\Models\AppException;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ExceptionTracker
{
    protected static bool $recording = false;

    public static function record(Throwable $e): void
    {
        if (self::$recording) {
            return;
        }

        self::$recording = true;

        try {
            $request = app()->runningInConsole() ? null : app('request');

            AppException::create([
                'class' => get_class($e),
                'message' => mb_substr($e->getMessage() !== '' ? $e->getMessage() : '(no message)', 0, 2000),
                'trace' => self::buildTrace($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_method' => $request?->method(),
                'request_url' => $request?->fullUrl(),
                'request_ip' => $request?->ip(),
                'user_id' => Auth::check() ? Auth::id() : null,
                'environment' => app()->environment(),
            ]);
        } catch (Throwable) {
            // Never let exception capture break the application.
        } finally {
            self::$recording = false;
        }
    }

    protected static function buildTrace(Throwable $e): array
    {
        $frames = array_slice($e->getTrace(), 0, 50);

        return array_map(fn (array $frame): array => [
            'file' => $frame['file'] ?? null,
            'line' => $frame['line'] ?? null,
            'function' => $frame['function'] ?? null,
            'class' => $frame['class'] ?? null,
        ], $frames);
    }
}
