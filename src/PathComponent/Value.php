<?php

namespace Flat3\OData\PathComponent;

use Flat3\OData\Controller\Transaction;
use Flat3\OData\Exception\Internal\PathNotHandledException;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\NoContentException;
use Flat3\OData\Interfaces\EmitInterface;
use Flat3\OData\Interfaces\PipeInterface;
use Flat3\OData\PrimitiveType;
use Flat3\OData\Transaction\MediaType;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Value implements PipeInterface, EmitInterface
{
    /** @var PrimitiveType $primitive */
    protected $primitive;

    public function __construct(PrimitiveType $primitive)
    {
        $this->primitive = $primitive;
    }

    public static function pipe(
        Transaction $transaction,
        string $pathComponent,
        ?PipeInterface $argument
    ): ?PipeInterface {
        if ($pathComponent !== '$value') {
            throw new PathNotHandledException();
        }

        if (!$argument instanceof PrimitiveType) {
            throw new BadRequestException('bad_value_argument',
                '$value must be passed a primitive value');
        }

        return new static($argument);
    }

    public function response(Transaction $transaction): StreamedResponse
    {
        $requestedFormat = $transaction->getRequestedContentType();

        if ($requestedFormat) {
            $transaction->sendContentType(MediaType::factory()->parse($requestedFormat));
        } else {
            $transaction->negotiateContentTypeText();
        }

        if (null === $this->primitive->get()) {
            throw new NoContentException('null_value');
        }

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emit($transaction);
        });
    }

    public function emit(Transaction $transaction): void
    {
        $transaction->outputRaw($this->primitive->get());
    }
}