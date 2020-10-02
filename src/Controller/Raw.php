<?php

namespace Flat3\OData\Controller;

use Flat3\OData\Exception\Protocol\NoContentException;
use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\Expression\Lexer;

class Raw extends Primitive
{
    public const path = parent::path.Lexer::PATH_SEPARATOR.'\$value';

    public function handle(): void
    {
        $transaction = $this->transaction;
        $response = $transaction->getResponse();

        $requestedFormat = $transaction->getFormat();

        if ($requestedFormat) {
            $transaction->setContentType($requestedFormat->getOriginal());
        } else {
            $transaction->setContentTypeText();
        }

        $primitive = $this->entitySet->getPrimitive($transaction, $this->id, $this->property);

        if (null === $primitive) {
            throw new NotFoundException();
        }

        if (null === $primitive->getInternalValue()) {
            throw new NoContentException();
        }

        $response->setCallback(function () use ($transaction, $primitive) {
            $transaction->outputRaw((string) $primitive->getInternalValue());
        });
    }
}
