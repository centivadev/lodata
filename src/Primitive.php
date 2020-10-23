<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Laravel;
use Flat3\Lodata\Interfaces\ArgumentInterface;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Illuminate\Support\Str;

/**
 * Class PrimitiveType
 * @package Flat3\OData
 */
abstract class Primitive implements ResourceInterface, ContextInterface, IdentifierInterface, EmitInterface, PipeInterface, ArgumentInterface
{
    const identifier = 'Edm.None';

    /** @var bool $nullable Whether the value can be made null */
    protected $nullable = true;

    /** @var Entity $entity */
    private $entity;

    /** @var Property $property */
    private $property;

    /** @var ?mixed $value Internal representation of the value */
    protected $value;

    public function __construct($value = null, bool $nullable = true)
    {
        $this->nullable = $nullable;
        $this->set($value);
    }

    abstract public function set($value);

    public static function factory($value = null, ?bool $nullable = true): self
    {
        if ($value instanceof Primitive) {
            return $value;
        }

        return new static($value, $nullable);
    }

    /**
     * Get the internal representation of the value
     *
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * Get the value as OData URL encoded
     *
     * @return string
     */
    abstract public function toUrl(): string;

    /**
     * Get the value as suitable for IEEE754 JSON encoding
     *
     * @return string
     */
    public function toJsonIeee754(): ?string
    {
        $value = $this->toJson();

        return null === $value ? null : (string) $value;
    }

    /**
     * Get the value as suitable for JSON encoding
     *
     * @return mixed
     */
    abstract public function toJson();

    /**
     * Return null or an empty value if this type cannot be made null
     *
     * @param $value
     *
     * @return mixed
     */
    public function maybeNull($value)
    {
        if (null === $value) {
            return $this->nullable ? null : $this->getEmpty();
        }

        return $value;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function setNullable(bool $nullable): self
    {
        $this->nullable = $nullable;
        return $this;
    }

    protected function getEmpty()
    {
        return '';
    }

    public function setProperty(Property $property): self
    {
        $this->property = $property;
        return $this;
    }

    public function setEntity(Entity $entity): self
    {
        $this->entity = $entity;
        return $this;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public static function pipe(
        Transaction $transaction,
        string $currentComponent,
        ?string $nextComponent,
        ?PipeInterface $argument
    ): ?PipeInterface {
        $lexer = new Lexer($currentComponent);

        try {
            $property = $lexer->identifier();
        } catch (LexerException $e) {
            throw new PathNotHandledException();
        }

        if (null === $argument) {
            throw new PathNotHandledException();
        }

        if (!$argument instanceof Entity) {
            throw new BadRequestException('bad_entity', 'Primitive must be passed an entity');
        }

        $property = $argument->getType()->getProperty($property);

        if (null === $property) {
            throw new NotFoundException('unknown_property',
                sprintf('The requested property (%s) was not known', $property));
        }

        return $argument->getPrimitive($property);
    }

    public function getName(): string
    {
        return Str::afterLast($this::identifier, '.');
    }

    public function getNamespace(): string
    {
        return Laravel::beforeLast($this::identifier, '.');
    }

    public function getResolvedName(string $namespace): string
    {
        if ($this->getNamespace() === $namespace) {
            return $this->getName();
        }

        return $this->getIdentifier();
    }

    public function getResourceUrl(): string
    {
        return Transaction::getResourceUrl().$this->getName().'()';
    }

    public function getIdentifier(): string
    {
        return $this::identifier;
    }

    public function getContextUrl(): string
    {
        if ($this->entity) {
            return sprintf(
                '%s(%s)/%s',
                $this->entity->getEntitySet()->getContextUrl(),
                $this->entity->getEntityId()->toUrl(),
                $this->property
            );
        }

        return Transaction::getContextUrl().'#'.$this->getIdentifier();
    }

    public function emit(Transaction $transaction): void
    {
        $transaction->outputRaw($this);
    }

    public function response(Transaction $transaction): Response
    {
        if (null === $this->get()) {
            throw new NoContentException('null_value');
        }

        $transaction->configureJsonResponse();

        $metadata = [
            'context' => $this->getContextUrl(),
        ];

        $metadata = $transaction->getMetadata()->filter($metadata);

        return $transaction->getResponse()->setCallback(function () use ($transaction, $metadata) {
            $transaction->outputJsonObjectStart();

            if ($metadata) {
                $transaction->outputJsonKV($metadata);
                $transaction->outputJsonSeparator();
            }

            $transaction->outputJsonKey('value');
            $transaction->outputJsonValue($this);

            $transaction->outputJsonObjectEnd();
        });
    }
}
