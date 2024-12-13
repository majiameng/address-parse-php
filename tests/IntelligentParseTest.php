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
//        $res = $address->parse("徐喆成，18466658543-9407，上海 上海市 普陀区 长征镇 真光路962弄真源小区137号602");
        var_dump($res);

    }

}