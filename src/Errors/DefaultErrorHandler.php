<?php

namespace Spatie\DataTransferObject\Errors;

use Spatie\DataTransferObject\DataTransferObject;
use Spatie\DataTransferObject\Exceptions\ValidationException;
use Spatie\DataTransferObject\Validation\ValidationResult;

class DefaultErrorHandler implements Handler
{
    private array $errors = [];

    private DataTransferObject $dtoContext;

    private string $scope;

    public function flush(): void
    {
        $this->errors = [];
        unset($this->scope);
    }

    public function scopeOrAcquiesceTo(string $hash): void
    {
        $this->scope = $hash;
    }

    public function isScopedTo(string $hash): bool
    {
        return isset($this->scope) && $this->scope == $hash;
    }

    public function report(DataTransferObject $dtoContext, string $propertyName, ValidationResult $validationResult): void
    {
        $this->errors[$propertyName][] = $validationResult;
        $this->dtoContext = $dtoContext;
    }

    public function handle(): void
    {
        if (count($this->errors)) {
            throw new ValidationException($this->dtoContext, $this->errors);
        }
    }
}
