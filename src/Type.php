<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Carbon\CarbonImmutable;
use DateTime;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Type\Binary;
use Flat3\Lodata\Type\Boolean;
use Flat3\Lodata\Type\Byte;
use Flat3\Lodata\Type\Collection;
use Flat3\Lodata\Type\Date;
use Flat3\Lodata\Type\DateTimeOffset;
use Flat3\Lodata\Type\Decimal;
use Flat3\Lodata\Type\Double;
use Flat3\Lodata\Type\Duration;
use Flat3\Lodata\Type\Guid;
use Flat3\Lodata\Type\Int16;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\Int64;
use Flat3\Lodata\Type\SByte;
use Flat3\Lodata\Type\Single;
use Flat3\Lodata\Type\Stream;
use Flat3\Lodata\Type\String_;
use Flat3\Lodata\Type\TimeOfDay;
use Flat3\Lodata\Type\UInt16;
use Flat3\Lodata\Type\UInt32;
use Flat3\Lodata\Type\UInt64;
use Flat3\Lodata\Type\Untyped;
use TypeError;

/**
 * Type
 * @method static PrimitiveType binary() Binary type
 * @method static PrimitiveType boolean() Boolean type
 * @method static PrimitiveType byte() Byte type
 * @method static PrimitiveType collection() Collection type
 * @method static PrimitiveType date() Date type
 * @method static PrimitiveType datetimeoffset() DateTimeOffset type
 * @method static PrimitiveType decimal() Decimal type
 * @method static PrimitiveType double() Double type
 * @method static PrimitiveType duration() Duration type
 * @method static PrimitiveType guid() GUID type
 * @method static PrimitiveType int16() Int16 type
 * @method static PrimitiveType int32() Int32 type
 * @method static PrimitiveType int64() Int64 type
 * @method static PrimitiveType sbyte() SByte type
 * @method static PrimitiveType single() Single type
 * @method static PrimitiveType stream() Stream type
 * @method static PrimitiveType string() String type
 * @method static PrimitiveType timeofday() TimeOfDay type
 * @method static PrimitiveType uint16() UInt16 type
 * @method static PrimitiveType uint32() UInt32 type
 * @method static PrimitiveType uint64() UInt64 type
 * @package Flat3\Lodata
 */
abstract class Type
{
    /**
     * The factory class name to generate instances of this type
     * @var Primitive|string $factory Factory class
     */
    protected $factory;

    /**
     * Generate a new type container
     * @param  string  $name  Type name
     * @return PrimitiveType OData primitive type
     */
    public static function __callStatic($name, $arguments): PrimitiveType
    {
        $resolver = [
            'binary' => Binary::class,
            'boolean' => Boolean::class,
            'byte' => Byte::class,
            'collection' => Collection::class,
            'date' => Date::class,
            'datetimeoffset' => DateTimeOffset::class,
            'decimal' => Decimal::class,
            'double' => Double::class,
            'duration' => Duration::class,
            'guid' => Guid::class,
            'int16' => Int16::class,
            'int32' => Int32::class,
            'int64' => Int64::class,
            'sbyte' => SByte::class,
            'single' => Single::class,
            'stream' => Stream::class,
            'string' => String_::class,
            'timeofday' => TimeOfDay::class,
            'uint16' => UInt16::class,
            'uint32' => UInt32::class,
            'uint64' => UInt64::class,
        ];

        if (!array_key_exists($name, $resolver)) {
            throw new InternalServerErrorException('invalid_type', 'An invalid type was requested: '.$name);
        }

        return new PrimitiveType($resolver[$name]);
    }

    /**
     * Generate a new primitive instance of this type from the provided value
     * @param  mixed|null  $value  Value
     * @return Primitive OData primitive
     */
    abstract public function instance($value = null);

    /**
     * Get the OpenAPI schema for this type
     * @return array
     */
    abstract public function getOpenAPISchema(): array;

    /**
     * Return a type object based on the provided value
     * @param  mixed  $value  PHP value
     * @return Type OData type representation
     */
    public static function fromInternalValue($value): Type
    {
        if (is_object($value)) {
            return self::fromInternalType(get_class($value));
        }

        return self::fromInternalType(gettype($value));
    }

    /**
     * Cast a PHP type to an OData type
     * @param  string  $type  PHP type
     * @return Type OData type representation
     */
    public static function fromInternalType(string $type): Type
    {
        switch ($type) {
            case 'void':
            case 'string':
            case 'NULL':
                return self::string();

            case 'float':
            case 'double':
                return self::double();

            case 'int':
            case 'integer':
                return self::int64();

            case 'bool':
            case 'boolean':
                return self::boolean();

            case is_a($type, DateTime::class, true):
                return self::datetimeoffset();

            case 'array':
            case 'stdClass':
                return new Untyped();

            case CarbonImmutable::class:
                return new PrimitiveType(DateTimeOffset::class);
        }

        throw new TypeError('Could not cast the provided internal type');
    }

    /**
     * Return whether the provided class name represents instances of this type
     * @param  string  $class
     * @return bool
     */
    public function is(string $class): bool
    {
        return is_a($this->factory, $class, true);
    }
}
