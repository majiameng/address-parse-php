<?php

use PHPUnit\Framework\TestCase;
use tinymeng\parse\Address;

class AddressTest extends TestCase
{
    protected $address;

    protected function setUp(): void
    {
        $this->address = new Address();
    }

    public function testParse()
    {
        $addressString = "广东省广州市天河区天河路123号";
        $result = $this->address->parse($addressString);

        $this->assertEquals('广东省', $result['province']);
        $this->assertEquals('广州市', $result['city']);
        $this->assertEquals('天河区', $result['region']);
        $this->assertEquals('天河路123号', $result['street']);
    }

    public function testDecompose()
    {
        $addressString = "广东省广州市天河区天河路123号 张三 13800138000 440000";
        $result = $this->address->parse($addressString, true);

        $this->assertEquals('张三', $result['name']);
        $this->assertEquals('13800138000', $result['mobile']);
        $this->assertEquals('440000', $result['postcode']);
        $this->assertEquals('天河路123号', $result['street']);
    }
}
