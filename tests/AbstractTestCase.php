<?php

namespace B3it\XmlRpc\Tests;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\CustomNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractTestCase extends TestCase
{

    protected SerializerInterface|NormalizerInterface|null $serializer = null;

    protected function getSerializer(): SerializerInterface|NormalizerInterface
    {
        if (isset($this->serializer)) {
            return $this->serializer;
        }

        $encoders = [new XmlEncoder()];
        $normalizers = [
            new DateTimeNormalizer(),
            new CustomNormalizer(),
            new ArrayDenormalizer()
        ];
        return $this->serializer = new Serializer($normalizers, $encoders);
    }

    protected function serialize($data, array $context = []): string
    {
        return $this->getSerializer()->serialize($data, 'xml', array_merge($context, [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            XmlEncoder::FORMAT_OUTPUT => true,
            #XmlEncoder::REMOVE_EMPTY_TAGS => true
        ]));
    }

    protected function deserialize(string $data, string $type, array $context = [])
    {
        return $this->getSerializer()->deserialize($data, $type, 'xml', $context);
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // parse .env file
        Dotenv::createImmutable(__DIR__)->load();
    }
}