<?php

namespace Spatie\DataTransferObject\Reflection;

use ReflectionClass;
use ReflectionProperty;
use Spatie\DataTransferObject\Attributes\Strict;
use Spatie\DataTransferObject\DataTransferObject;

class DataTransferObjectClass
{
    private ReflectionClass $reflectionClass;

    private DataTransferObject $dataTransferObject;

    private bool $isStrict;

    public function __construct(DataTransferObject $dataTransferObject)
    {
        $this->reflectionClass = new ReflectionClass($dataTransferObject);
        $this->dataTransferObject = $dataTransferObject;
    }

    /**
     * @return \Spatie\DataTransferObject\Reflection\DataTransferObjectProperty[]
     */
    public function getProperties(): array
    {
        $publicProperties = array_filter(
            $this->reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC),
            fn (ReflectionProperty $property) => !$property->isStatic()
        );

        return array_map(
            fn (ReflectionProperty $property) => new DataTransferObjectProperty(
                $this->dataTransferObject,
                $property
            ),
            $publicProperties
        );
    }

    public function validate(): void
    {
        $errorManager = $this->dataTransferObject->getErrorManager();

        foreach ($this->getProperties() as $property) {
            $validators = $property->getValidators();

            foreach ($validators as $validator) {
                $result = $validator->validate($property->getValue());

                if ($result->isValid) {
                    continue;
                }

                $errorManager->report($this->dataTransferObject, $property->name, $result);
            }
        }
    }

    public function isStrict(): bool
    {
        return $this->isStrict ??= !empty($this->reflectionClass->getAttributes(Strict::class));
    }
}
