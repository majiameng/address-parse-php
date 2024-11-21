# 收货地址智能解析（纯PHP版）

本项目包含2个功能
- 把字符串解析成姓名、收货电话、邮编、身份证号、收货地址
- 把收货地址解析成省、市、区县、街道地址
- 支持提取虚拟号码有分机号的情况（适用美团，拼多多等需要保护客户隐私的情况 2022.11.13更新）

特色是：***简单易用***

该项目依然采用的是，统计特征分析，然后以最大的概率来匹配，得出大概率的解。因此只能解析中文的收货信息，不能保证100%解析成功，但是从生产环境的使用情况来看，解析成功率保持在96%以上，就算是百度基于人工智能的地址识别，经我实测，也是有一定的不能识别的情况。


## 1.安装
> composer require tinymeng/intelligent-parse  -vvv

### 使用
so easy；
```php
$address = new \tinymeng\parse\Address();
$res = $address->parse("收货地址张三收货地址：成都市武侯区美领馆路11号附2号 617000  136-3333-6666");
var_dump($res);
```
结果为：
```
array(7) {
  ["postcode"]=>
  string(6) "617000"
  ["name"]=>
  string(6) "张三"
  ["address"]=>
  string(56) "成都市武侯区美领馆路11号附2号 136-3333-6666"
  ["province"]=>
  string(9) "四川省"
  ["city"]=>
  string(9) "成都市"
  ["region"]=>
  string(9) "武侯区"
  ["street"]=>
  string(38) "美领馆路11号附2号 136-3333-6666"
}
```

### Star History
[![Star History Chart](https://api.star-history.com/svg?repos=majiameng/address-parse-php&type=Date)](https://star-history.com/#majiameng/address-parse-php&Date)


### 致谢
后来在网上我发现一些作者基于我的识别逻辑，写了js等版本，方便了大家，但是很少注明参考链接。