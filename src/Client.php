<?php

namespace B3it\XmlRpc;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\CustomNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class Client
{

    public function __construct(
        protected ClientInterface                  $client,
        protected readonly RequestFactoryInterface $requestFactory,
        protected readonly StreamFactoryInterface  $streamFactory,
        protected string                           $url,
        protected ?SerializerInterface             $serializer = null,
        protected string                           $contentType = 'text/xml'
    )
    {
        $this->serializer = $serializer ?? $this->__createSerializer();
    }

    /**
     * @param string $methodName
     * @param DataValue[]|array $params
     * @param array $serializerContext
     * @return Response
     * @throws ClientExceptionInterface
     */
    public function sendRequest(string $methodName, array $params = [], array $serializerContext = []): Response
    {
        $request = new Request($methodName, $params);
        $body = $this->serializer->serialize($request, 'xml', array_merge([
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            XmlEncoder::FORMAT_OUTPUT => true,
            XmlEncoder::ROOT_NODE_NAME => 'methodCall',
        ], $serializerContext));
        $httpRequest = $this->requestFactory->createRequest('POST', $this->url)
            ->withHeader('Content-Type', $this->contentType)
            ->withBody($this->streamFactory->createStream($body));
        $httpResponse = $this->client->sendRequest($httpRequest);
        $resultBody = $httpResponse->getBody()->getContents();
        return $this->deserializeResponse($resultBody, $serializerContext);
    }

    public function deserializeResponse(string $body, array $serializerContext = []): Response
    {
        return $this->serializer->deserialize($body, Response::class, 'xml', $serializerContext);
    }

    protected function __createSerializer(): SerializerInterface
    {
        $encoders = [new XmlEncoder()];
        $normalizers = [
            new DateTimeNormalizer(),
            new CustomNormalizer(),
            new ArrayDenormalizer()
        ];
        return new Serializer($normalizers, $encoders);
    }
}