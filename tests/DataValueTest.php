<?php

namespace B3it\XmlRpc\Tests;

use B3it\XmlRpc\DataType;
use B3it\XmlRpc\DataValue;
use B3it\XmlRpc\Request;
use B3it\XmlRpc\Response;
use DateTime;
use DateTimeInterface;
use Exception;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

class DataValueTest extends AbstractTestCase
{
    /**
     * @throws Exception
     */
    public function testNormalizeDateTime()
    {
        $time = '2024-11-07T10:19:59';
        $value = new DataValue(new DateTime($time));

        $str = $this->serialize($value);
        self::assertXmlStringEqualsXmlFile(__DIR__ . '/assets/datetime.xml', $str);

    }

    /**
     * @throws Exception
     */
    public function testNormalizeDateTimeMapped()
    {
        $time = '2024-11-07T10:19:59';
        $value = DataValue::mapPHP(new DateTime($time));

        $str = $this->serialize($value);
        self::assertXmlStringEqualsXmlFile(__DIR__ . '/assets/datetime.xml', $str);
    }

    public function testDeserializeDateTime()
    {
        $str = file_get_contents(__DIR__ . '/assets/datetime.xml');

        /**
         * @var $val DataValue
         */
        $val = $this->deserialize($str, DataValue::class);
        self::assertInstanceOf(DateTimeInterface::class, $val->toPHP());
    }

    public function testSerializeArray()
    {
        $data = new DataValue([
            new DataValue('UstId_1'),
            new DataValue('DE123456789')
        ]);

        $str = $this->serialize($data);
        self::assertXmlStringEqualsXmlFile(__DIR__ . '/assets/array.xml', $str);
    }
    public function testSerializeArrayMapped()
    {
        $data = DataValue::mapPHP([
            'UstId_1',
            'DE123456789'
        ]);

        $str = $this->serialize($data);
        self::assertXmlStringEqualsXmlFile(__DIR__ . '/assets/array.xml', $str);
    }

    public function testSerializeStruct()
    {
        $data = new DataValue([
            'foo' => new DataValue(1),
            'bar' => new DataValue(2)
        ], DataType::STRUCT);
        $str = $this->serialize($data);
        self::assertXmlStringEqualsXmlFile(__DIR__ . '/assets/struct.xml', $str);
    }
    public function testSerializeStructMapped()
    {
        $data = DataValue::mapPHP([
            'foo' => 1,
            'bar' => 2,
        ]);
        $str = $this->serialize($data);
        self::assertXmlStringEqualsXmlFile(__DIR__ . '/assets/struct.xml', $str);
    }
    public function testDeserializeStruct()
    {
        $str = file_get_contents(__DIR__ . '/assets/struct.xml');

        /**
         * @var $val DataValue
         */
        $val = $this->deserialize($str, DataValue::class);
        self::assertSame(['foo' => 1, 'bar' => 2], $val->toPHP());
    }

    public function testDeserializeArray(): void
    {
        $str = file_get_contents(__DIR__ . '/assets/array.xml');

        /**
         * @var $val DataValue
         */
        $val = $this->deserialize($str, DataValue::class);
        self::assertSame(['UstId_1', 'DE123456789'], $val->toPHP());
    }

    public function testSerializeBase64()
    {
        $val = DataValue::mapPHP("you can't read this!", DataType::BASE64);
        $str = $this->serialize($val);
        self::assertXmlStringEqualsXmlFile(__DIR__ . '/assets/base64.xml', $str);
    }

    public function testDeserializeBase64()
    {
        $str = file_get_contents(__DIR__ . '/assets/base64.xml');

        /**
         * @var $val DataValue
         */
        $val = $this->deserialize($str, DataValue::class);

        self::assertSame("you can't read this!", $val->toPHP());
    }

    public function testSerializeRequest()
    {
        $request = new Request('examples.getStateName', [DataValue::mapPHP(40, DataType::INTEGER_4)]);
        $str = $this->serialize($request, [XmlEncoder::ROOT_NODE_NAME => "methodCall"]);
        self::assertXmlStringEqualsXmlFile(__DIR__ . '/assets/request.xml', $str);
    }

    public function testDeserializeResponse()
    {
        $str = file_get_contents(__DIR__ . '/assets/response.xml');
        /**
         * @var $response Response
         */
        $response = $this->deserialize($str, Response::class);

        $data = $response->toAssocArray();
        self::assertNotEmpty($data);
        self::assertArrayHasKey('UstId_1', $data);
    }
}
