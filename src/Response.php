<?php

namespace B3it\XmlRpc;

use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizableInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class Response implements DenormalizableInterface
{
    /**
     * @var DataValue[]
     */
    public array $params = [];

    /**
     * @throws ExceptionInterface
     */
    public function denormalize(DenormalizerInterface $denormalizer, float|int|bool|array|string $data, ?string $format = null, array $context = []): void
    {
        if (isset($data['params'])) {
            $data = $data['params'];
        }
        // AS_COLLECTION makes more problems
        if (isset($data['param']['value'])) {
            $this->params []= $denormalizer->denormalize($data['param']['value'], DataValue::class, $format, $context);
        } else {
            foreach ($data['param'] as $param) {
                $this->params []= $denormalizer->denormalize($param['value'], DataValue::class, $format, $context);
            }
        }
    }

    public function toPHP(): array
    {
        return array_map(fn (DataValue $item) => $item->toPHP(), $this->params);
    }

    public function toAssocArray(): array
    {
        return array_column($this->toPHP(), 1, 0);
    }
}