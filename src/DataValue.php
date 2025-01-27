<?php

namespace B3it\XmlRpc;

use DateTimeInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizableInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DataValue implements NormalizableInterface, DenormalizableInterface
{
    const DATETIME_FORMAT = 'Y-m-d\TH:i:s';


    public function __construct(
        protected mixed $data = null,
        protected ?DataType $type = null,
    )
    {
    }

    /**
     * @throws ExceptionInterface
     */
    public function normalize(NormalizerInterface $normalizer, ?string $format = null, array $context = []): array
    {

        $type = $this->type ?? self::guessType($this->data);

        $value = match ($type) {
            DataType::ARRAY => ['data' => [
                'value' => $normalizer->normalize($this->data, $format, $context)
            ]],
            DataType::STRUCT => ['member' => array_map(fn ($key, $value) => [
                'name' => $key,
                'value' => $normalizer->normalize($value, $format, $context)
            ], array_keys($this->data), array_values($this->data))],
            DataType::BASE64 => base64_encode($this->data),
            DataType::BOOLEAN => (boolean)$this->data,
            DataType::DATETIME => $normalizer->normalize($this->data, $format, array_merge($context, [
                DateTimeNormalizer::FORMAT_KEY => self::DATETIME_FORMAT
            ])),
            DataType::DOUBLE => (double)$this->data,
            DataType::STRING => (string)$this->data,
            DataType::NULL => null,
            DataType::INTEGER_4,
            DataType::INTEGER_8,
            DataType::INTEGER => (integer)$this->data
        };

        return [$type->value => $value];
    }

    static protected function guessType(mixed $data): DataType
    {
        if (is_array($data)) {
            return self::onlyNumKeys($data) ? DataType::ARRAY : DataType::STRUCT;
        }
        if ($data instanceof DateTimeInterface) {
            return DataType::DATETIME;
        }
        else if (is_null($data)) {
            return DataType::NULL;
        } else if (is_string($data)) {
            return DataType::STRING;
        } else if (is_int($data)) {
            return DataType::INTEGER;
        } else if (is_float($data)) {
            return DataType::DOUBLE;
        }

        return DataType::STRING;
    }

    /**
     * @throws ExceptionInterface
     */
    public function denormalize(DenormalizerInterface $denormalizer, float|int|bool|array|string $data, ?string $format = null, array $context = []): void
    {
        foreach ($data as $key => $value) {
            $this->type = DataType::from($key);
            switch ($this->type) {
                case DataType::STRING:
                    $this->data = (string) $value;
                    break;
                case DataType::ARRAY:
                    $this->data = $denormalizer->denormalize($value['data']['value'], DataValue::class . '[]', $format, $context);
                    break;
                case DataType::STRUCT:
                    $result = [];
                    foreach ($value['member'] as $member) {
                        $result[$member['name']] = $denormalizer->denormalize($member['value'], DataValue::class, $format, $context);
                    }
                    $this->data = $result;
                    break;
                case DataType::BASE64:
                    $this->data = base64_decode($value);
                    break;
                case DataType::BOOLEAN:
                    $this->data = boolval($value);
                    break;
                case DataType::DATETIME:
                    $this->data = $denormalizer->denormalize($value, DateTimeInterface::class, $format, array_merge($context, [
                        DateTimeNormalizer::FORMAT_KEY => self::DATETIME_FORMAT
                    ]));
                    break;
                case DataType::INTEGER:
                case DataType::INTEGER_4:
                case DataType::INTEGER_8:
                    $this->data = (integer) $value;
                    break;
                case DataType::DOUBLE:
                    $this->data = (double) $value;
                    break;
                case DataType::NULL:
                    $this->data = null;
                    break;
            }
        }
    }

    public function toPHP()
    {
        if (is_array($this->data)) {
            return array_map(fn (DataValue $item) => $item->toPHP(), $this->data);
        }
        return $this->data;
    }

    public static function mapPHP(mixed $data, DataType $type = null): self
    {
        if ($data instanceof self) {
            return $data;
        }
        if (is_array($data)) {
            return new self(array_map([DataValue::class, 'mapPHP'], $data));
        }
        return new self($data, $type);
    }


    private static function onlyNumKeys(array $arr): bool
    {
        return count(array_filter(array_keys($arr), is_int(...))) === count(array_keys($arr));
    }
}