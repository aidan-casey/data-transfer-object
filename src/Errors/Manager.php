<?php

namespace Spatie\DataTransferObject\Errors;

use Spatie\DataTransferObject\DataTransferObject;
use Spatie\DataTransferObject\Validation\ValidationResult;

class Manager implements Handler
{
    private static Handler $handler;

    public static function registerHandler(Handler $handler): void
    {
        static::$handler = $handler;
    }

    public static function handler(): Handler
    {
        if (! isset(static::$handler)) {
            static::registerHandler(new DefaultErrorHandler);
        }

        return static::$handler;
    }

    public function flush(): void
    {
        static::handler()->flush();
    }

    public function report(DataTransferObject $dtoContext, string $propertyName, ValidationResult $result): void {
        static::handler()->report($dtoContext, $propertyName, $result);
    }

    public function scopeOrAcquiesceTo(string $hash): void
    {
        static::handler()->scopeOrAcquiesceTo($hash);
    }

    public function isScopedTo(string $hash): bool
    {
        return static::handler()->isScopedTo($hash);
    }

    public function handle(): void
    {
        static::handler()->handle();
    }
}
