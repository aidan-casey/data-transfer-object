<?php

namespace Spatie\DataTransferObject\Errors;

use Exception;
use Spatie\DataTransferObject\DataTransferObject;
use Spatie\DataTransferObject\Validation\ValidationResult;

class AggregatedErrorHandler implements Handler
{
    private array $errors = [];

    private string $scope;

    public function flush(): void
    {
        $this->errors = [];
        unset($this->scope);
    }

    public function scopeOrAcquiesceTo(string $hash): void
    {
        if (! isset($this->scope)) {
            $this->scope = $hash;
        }
    }

    public function isScopedTo(string $hash): bool
    {
        return isset($this->scope) && $this->scope == $hash;
    }

    public function report(DataTransferObject $dtoContext, string $propertyName, ValidationResult $validationResult): void
    {
        $this->errors[$dtoContext::class][$propertyName][] = $validationResult;
        $this->dtoContext = $dtoContext;
    }

    public function handle(): void
    {
        if (count($this->errors)) {
            $string = PHP_EOL;

            foreach ($this->errors as $context => $properties) {
                foreach ($properties as $property => $propertyErrors) {
                    foreach ($propertyErrors as $error) {
                        $string .= "[{$context}::\${$property}] {$error->message}" . PHP_EOL;
                    }
                }
            }

            throw new Exception($string);
        }
    }
}
