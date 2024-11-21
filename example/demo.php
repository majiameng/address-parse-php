<?php
$address = new \tinymeng\parse\Address();
$res = $address->parse("收货地址张三收货地址：成都市武侯区美领馆路11号附2号 617000  136-3333-6666");

var_dump($res);
