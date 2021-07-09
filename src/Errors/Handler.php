<?php

namespace Spatie\DataTransferObject\Errors;

use Spatie\DataTransferObject\DataTransferObject;
use Spatie\DataTransferObject\Validation\ValidationResult;

interface Handler
{
    public function flush(): void;

    public function scopeOrAcquiesceTo(string $hash): void;
    
    public function isScopedTo(string $hash): bool;

    public function report(DataTransferObject $dtoContext, string $propertyName, ValidationResult $validationResult): void;

    public function handle(): void;
}
