<?php

namespace Spatie\DataTransferObject;

use ReflectionClass;
use ReflectionProperty;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Casters\DataTransferObjectCaster;
use Spatie\DataTransferObject\Errors\Manager;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;
use Spatie\DataTransferObject\Reflection\DataTransferObjectClass;

#[CastWith(DataTransferObjectCaster::class)]
abstract class DataTransferObject
{
    protected Manager $errorManager;

    protected array $exceptKeys = [];

    protected array $onlyKeys = [];

    public function __construct(...$args)
    {
        $errorManager = $this->getErrorManager();
        $errorManager->scopeOrAcquiesceTo(spl_object_hash($this));

        if (is_array($args[0] ?? null)) {
            $args = $args[0];
        }

        $class = new DataTransferObjectClass($this);

        foreach ($class->getProperties() as $property) {
            $property->setValue($args[$property->name] ?? $this->{$property->name} ?? null);

            unset($args[$property->name]);
        }

        if ($class->isStrict() && count($args)) {
            throw UnknownProperties::new(static::class, array_keys($args));
        }

        $class->validate();

        if ($errorManager->isScopedTo(spl_object_hash($this))) {
            $errorManager->handle();
            $errorManager->flush();
        }
    }

    public static function arrayOf(array $arrayOfParameters): array
    {
        return array_map(
            fn (mixed $parameters) => new static($parameters),
            $arrayOfParameters
        );
    }

    public function all(): array
    {
        $data = [];

        $class = new ReflectionClass(static::class);

        $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $data[$property->getName()] = $property->getValue($this);
        }

        return $data;
    }

    public function only(string ...$keys): static
    {
        $dataTransferObject = clone $this;

        $dataTransferObject->onlyKeys = [...$this->onlyKeys, ...$keys];

        return $dataTransferObject;
    }

    public function except(string ...$keys): static
    {
        $dataTransferObject = clone $this;

        $dataTransferObject->exceptKeys = [...$this->exceptKeys, ...$keys];

        return $dataTransferObject;
    }

    public function clone(...$args): static
    {
        return new static(...array_merge($this->toArray(), $args));
    }

    public function toArray(): array
    {
        if (count($this->onlyKeys)) {
            $array = Arr::only($this->all(), $this->onlyKeys);
        } else {
            $array = Arr::except($this->all(), $this->exceptKeys);
        }

        $array = $this->parseArray($array);

        return $array;
    }

    public function getErrorManager(): Manager
    {
        if (! isset($this->errorManager)) {
            $this->errorManager = new Manager;
        }

        return $this->errorManager;
    }

    protected function parseArray(array $array): array
    {
        foreach ($array as $key => $value) {
            if ($value instanceof DataTransferObject) {
                $array[$key] = $value->toArray();

                continue;
            }

            if (! is_array($value)) {
                continue;
            }

            $array[$key] = $this->parseArray($value);
        }

        return $array;
    }
}
