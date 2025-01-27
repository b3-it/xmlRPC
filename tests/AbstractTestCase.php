<?php

namespace B3it\XmlRpc\Tests;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Mapping\Loader\LoaderChain;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\CustomNormalizer;
use Symfony\Component\Serializer\Normalizer\DateIntervalNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
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

        $loaders = new LoaderChain([
            new AttributeLoader()
        ]);

        $classMetadataFactory = new ClassMetadataFactory($loaders);
        $nameConverter = new MetadataAwareNameConverter($classMetadataFactory);

        //$objectNormalizer = new ObjectNormalizer($classMetadataFactory, $nameConverter);
        $encoders = [new XmlEncoder()];
        $normalizers = [
            new DateTimeNormalizer(),
            new DateTimeZoneNormalizer(),
            new DateIntervalNormalizer(),
            new CustomNormalizer(),
            new ArrayDenormalizer(),
            //$objectNormalizer,
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