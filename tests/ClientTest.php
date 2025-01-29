<?php

namespace B3it\XmlRpc\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientExceptionInterface;
use B3it\XmlRpc\Client;

class ClientTest extends AbstractTestCase
{

    protected ?Client $client = null;

    /**
     * @throws ClientExceptionInterface
     */
    public function testServiceCall()
    {
        $response = $this->client->sendRequest($_ENV['CLIENT_METHOD'], [
            $_ENV['CLIENT_REQUEST_PARAM1'],
            $_ENV['CLIENT_REQUEST_PARAM2'],
            '',
            '',
            '',
            ''
        ]);
        // some data might need to deserialize twice!
        $data = $response->toPHP()[0];
        $response = $this->client->deserializeResponse($data);
        $responseData = $response->toAssocArray();
        self::assertNotEmpty($responseData);
        self::assertArrayHasKey('ErrorCode', $responseData);
    }


    protected function setUp(): void
    {
        parent::setUp();


        $psr18Client = new GuzzleClient();
        $psr7Factory = new HttpFactory();

        $this->client = new Client($psr18Client, $psr7Factory, $psr7Factory, $this->getSerializer(), $_ENV['CLIENT_URL']);
    }
}