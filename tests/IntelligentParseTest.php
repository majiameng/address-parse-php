<?php
use PHPUnit\Framework\TestCase;
use tinymeng\parse\Address;

/**
 * IntelligentParseTest
 */
class IntelligentParseTest extends TestCase
{
    public function testAddress()
    {
        $address = new Address();
        $res = $address->parse("收货地址张三收货地址：成都市武侯区美领馆路11号附2号 617000  136-3333-6666");
//        $res = $address->parse("江西省 南昌市 东湖区 董家窑街道 八一大道央央春天8栋1602 万茜13064133333");
        var_dump($res);
    }

}