<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Interfaces\QueryInterface;
use Flat3\Lodata\Model;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Tests\JsonDriver;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Illuminate\Testing\TestResponse;

class ErrorReportingTest extends TestCase
{
    public function test_error_reporting()
    {
        $this->withExceptionHandling();

        $response = $this->req(
            Request::factory()
                ->query('$format', 'xml')
        );

        $this->assertMatchesJsonSnapshot($response->streamedContent());
    }

    public function test_error_response_body()
    {
        try {
            throw NotImplementedException::factory()
                ->code('test')
                ->message('test message')
                ->target('test target')
                ->details('test details')
                ->inner('inner error');
        } catch (NotImplementedException $e) {
            $response = $e->toResponse();
            /** @noinspection PhpParamsInspection */
            $testResponse = new TestResponse($response);
            $this->assertMatchesSnapshot($testResponse->streamedContent(), new JsonDriver());
        }
    }

    public function test_stream_error()
    {
        Model::add(
            new class(
                'texts',
                Model::entitytype('text')
                    ->addProperty(DeclaredProperty::factory('a', PrimitiveType::string()))
            ) extends EntitySet implements QueryInterface {
                public function emit(Transaction $transaction): void
                {
                    $transaction->outputJsonObjectStart();
                    $transaction->outputJsonKV(['key' => 'value']);
                    throw new NotImplementedException('not_implemented', 'Error during stream');
                }

                public function query(): array
                {
                    return [];
                }
            });

        ob_start();

        $this->assertTextMetadataResponse(
            Request::factory()
                ->path('/texts'));

        $this->assertMatchesSnapshot(ob_get_clean());
    }
}
