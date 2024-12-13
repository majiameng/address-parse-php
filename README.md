# 收货地址智能解析（纯PHP版）

本项目包含2个功能
- 把字符串解析成姓名、收货电话、邮编、身份证号、收货地址
- 把收货地址解析成省、市、区县、街道地址
- 支持提取虚拟号码有分机号的情况（适用美团，拼多多等需要保护客户隐私的情况 2022.11.13更新）

特色是：***简单易用***

该项目依然采用的是，统计特征分析，然后以最大的概率来匹配，得出大概率的解。因此只能解析中文的收货信息，不能保证100%解析成功，但是从生产环境的使用情况来看，解析成功率保持在96%以上，就算是百度基于人工智能的地址识别，经我实测，也是有一定的不能识别的情况。


## 1.安装
> composer require tinymeng/intelligent-parse  -vvv

PHP 请安装并开启 mbstring 扩展

### 使用
so easy；
```php
$address = new \tinymeng\parse\Address();
$res = $address->parse("收货地址张三收货地址：成都市武侯区美领馆路11号附2号 617000  136-3333-6666");
var_dump($res);
```
结果为：
```
array(12) {
  ["province_id"]=>
  int(0)
  ["province"]=>
  string(0) ""
  ["city_id"]=>
  int(0)
  ["city"]=>
  string(9) "成都市"
  ["region_id"]=>
  int(0)
  ["region"]=>
  string(9) "武侯区"
  ["street"]=>
  string(24) "美领馆路11号附2号"
  ["postcode"]=>
  string(6) "617000"
  ["name"]=>
  string(6) "张三"
  ["mobile"]=>
  string(11) "13633336666"
  ["idcard"]=>
  string(0) ""
  ["address"]=>
  string(42) "成都市武侯区美领馆路11号附2号"
}
```
