<?php

namespace B3it\XmlRpc;

use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class Request implements NormalizableInterface
{
    /**
     * @param DataValue[]|array $params
     */
    public function __construct(
        protected string $methodName,
        public array $params = []
    )
    {
        $this->params = array_map(fn($o) => DataValue::mapPHP($o), $this->params);
    }

    /**
     * @throws ExceptionInterface
     */
    public function normalize(NormalizerInterface $normalizer, ?string $format = null, array $context = []): array
    {
        $params = [];
        foreach ($this->params as $param) {
            $params['param'][]['value'] = $normalizer->normalize($param, $format, $context);
        }
        return [
            'methodName' => $this->methodName,
            'params' => !empty($params) ? $params : []
        ];
    }
}