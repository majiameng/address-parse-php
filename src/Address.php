<?php
namespace tinymeng\parse;

/**
 * 地址智能解析
 * Author: JiaMeng <666@majiameng.com>
 */
class Address
{
    /**
     * @var
     */
    private $province;
    private $city;
    private $region;

    public function __construct()
    {
        $this->loadArea();
    }

    public function setArea($list)
    {
        $this->area = $list;
        return $this;
    }

    public function getArea()
    {
        return $this->area;
    }

    /**
     * Author: JiaMeng <666@majiameng.com>
     * @throws \Exception
     */
    private function loadArea()
    {
        $this->province = require_once __DIR__ . '/lib/province.php';
        $this->city = require_once __DIR__ . '/lib/city.php';
        $this->region = require_once __DIR__ . '/lib/region.php';
    }

    /**
     * 智能解析
     * @param $string
     * @param $user
     * @return array
     * Author: JiaMeng <666@majiameng.com>
     */
    public function parse($string, $user = true)
    {
        $result = [];
        if ($user) {
            $decompose = $this->decompose($string);
            $result = array_merge($result, $decompose);
        } else {
            $result['address'] = $string;
        }

        $fuzz = $this->fuzz($result['address']);
        $parse = $this->parseAddressDetail($fuzz['province'], $fuzz['city'], $fuzz['region']);

        $result['province'] = $parse['province'] ?? '';
        $result['city'] = $parse['city'] ?? '';
        $result['region'] = $parse['region'] ?? '';
        $result['street'] = $fuzz['street'] ?? '';

        // 清理街道信息
        $result['street'] = str_replace(
            [$result['region'], $result['city'], $result['province']],
            ['', '', ''],
            $result['street']
        );

        return $result;
    }

    /**
     * 分离手机号(座机)，身份证号，姓名等用户信息
     * Author: JiaMeng <666@majiameng.com>
     * @param $string
     * @return array
     */
    private function decompose($string)
    {

        $compose = array();

        $search = array('收货地址', '详细地址', '地址', '收货人', '收件人', '收货', '所在地区', '邮编', '电话', '手机号码','身份证号码', '身份证号', '身份证', '：', ':', '；', ';', '，', ',', '。');
        $replace = array(' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ');
        $string = str_replace($search, $replace, $string);
        $string = preg_replace('/\s{1,}/', ' ', trim($string));


        // 匹配身份证、手机号和邮政编码
        preg_match('/\d{18}|\d{17}X/i', $string, $match);
        if ($match) {
            $compose['idn'] = strtoupper($match[0]);
            $string = str_replace($match[0], '', $string);
        }

        preg_match('/\d{7,11}[\-_]\d{2,6}|\d{7,11}|\d{3,4}-\d{6,8}/', $string, $match);
        if ($match && $match[0]) {
            $compose['mobile'] = $match[0];
            $string = str_replace($match[0], '', $string);
        }

        preg_match('/\d{6}/', $string, $match);
        if ($match && $match[0]) {
            $compose['postcode'] = $match[0];
            $string = str_replace($match[0], '', $string);
        }

        $string = trim(preg_replace('/ {2,}/', ' ', $string));

        $split_arr = explode(' ', $string);
        if (count($split_arr) > 1) {
            $compose['name'] = $split_arr[0];
            foreach ($split_arr as $value) {
                if (strlen($value) < strlen($compose['name'])) {
                    $compose['name'] = $value;
                }
            }
            $string = trim(str_replace($compose['name'], '', $string));
        }

        $compose['address'] = trim($string);
        var_dump($compose);die;

        return $compose;
    }

    /**
     * 根据统计规律分析出二三级地址
     * Author: JiaMeng <666@majiameng.com>
     * @param $addr
     * @return array
     */
    private function fuzz($addr)
    {
        $addr_origin = $addr;
        $addr = str_replace([' ', ','], ['', ''], $addr);
        $addr = str_replace('自治区', '省', $addr);
        $addr = str_replace('自治州', '州', $addr);

        $addr = str_replace('小区', '', $addr);
        $addr = str_replace('校区', '', $addr);

        $province = '';
        $city = '';
        $region = '';
        $street = '';

        if (mb_strpos($addr, '县') !== false && mb_strpos($addr, '县') < floor((mb_strlen($addr) / 3) * 2) || (mb_strpos($addr, '区') !== false && mb_strpos($addr, '区') < floor((mb_strlen($addr) / 3) * 2)) || mb_strpos($addr, '旗') !== false && mb_strpos($addr, '旗') < floor((mb_strlen($addr) / 3) * 2)) {

            if (mb_strstr($addr, '旗')) {
                $deep3_keyword_pos = mb_strpos($addr, '旗');
                $region = mb_substr($addr, $deep3_keyword_pos - 1, 2);
            }
            if (mb_strstr($addr, '区')) {
                $deep3_keyword_pos = mb_strpos($addr, '区');

                if (mb_strstr($addr, '市')) {
                    $city_pos = mb_strpos($addr, '市');
                    $zone_pos = mb_strpos($addr, '区');
                    $region = mb_substr($addr, $city_pos + 1, $zone_pos - $city_pos);
                } else {
                    $region = mb_substr($addr, $deep3_keyword_pos - 2, 3);
                }
            }
            if (mb_strstr($addr, '县')) {
                $deep3_keyword_pos = mb_strpos($addr, '县');

                if (mb_strstr($addr, '市')) {
                    $city_pos = mb_strpos($addr, '市');
                    $zone_pos = mb_strpos($addr, '县');
                    $region = mb_substr($addr, $city_pos + 1, $zone_pos - $city_pos);
                } else {

                    if (mb_strstr($addr, '自治县')) {
                        $region = mb_substr($addr, $deep3_keyword_pos - 6, 7);
                        if (in_array(mb_substr($region, 0, 1), ['省', '市', '州'])) {
                            $region = mb_substr($region, 1);
                        }
                    } else {
                        $region = mb_substr($addr, $deep3_keyword_pos - 2, 3);
                    }
                }
            }
            $street = mb_substr($addr_origin, $deep3_keyword_pos + 1);
        } else {
            if (mb_strripos($addr, '市')) {

                if (mb_substr_count($addr, '市') == 1) {
                    $deep3_keyword_pos = mb_strripos($addr, '市');
                    $region = mb_substr($addr, $deep3_keyword_pos - 2, 3);
                    $street = mb_substr($addr_origin, $deep3_keyword_pos + 1);
                } else if (mb_substr_count($addr, '市') >= 2) {
                    $deep3_keyword_pos = mb_strripos($addr, '市');
                    $region = mb_substr($addr, $deep3_keyword_pos - 2, 3);
                    $street = mb_substr($addr_origin, $deep3_keyword_pos + 1);
                }
            } else {
                $street = $addr;
            }
        }

        if (mb_strpos($addr, '市') || mb_strstr($addr, '盟') || mb_strstr($addr, '州')) {
            if ($tmp_pos = mb_strpos($addr, '市')) {
                $city = mb_substr($addr, $tmp_pos - 2, 3);
            } else if ($tmp_pos = mb_strpos($addr, '盟')) {
                $city = mb_substr($addr, $tmp_pos - 2, 3);
            } else if ($tmp_pos = mb_strpos($addr, '州')) {
                if ($tmp_pos = mb_strpos($addr, '自治州')) {
                    $city = mb_substr($addr, $tmp_pos - 4, 5);
                } else {
                    $city = mb_substr($addr, $tmp_pos - 2, 3);
                }
            }
        }

        return array(
            'province' => $province,
            'city' => $city,
            'region' => $region,
            'street' => $street,
        );
    }

    /**
     * 智能解析出省市区+街道地址
     * Author: JiaMeng <666@majiameng.com>
     * @param $province
     * @param $city
     * @param $region
     * @return array
     */
    private function parseAddressDetail($province, $city, $region)
    {
        $result = [
            'province' => '',
            'city' => '',
            'region' => ''
        ];
        if ($region != '') {
            $area3_matches = [];
            foreach ($this->region as $id => $v) {
                if (mb_strpos($v['name'], $region) !== false) {
                    $area3_matches[$id] = $v;
                }
            }

            if ($area3_matches && count($area3_matches) > 1) {
                if ($city) {
                    foreach ($this->city as $id => $v) {
                        if (mb_strpos($v['name'], $city) !== false) {
                            $area2_matches[$id] = $v;
                        }
                    }

                    if ($area2_matches) {
                        foreach ($area3_matches as $id => $v) {

                            if (isset($area2_matches[$v['pid']])) {
                                $result['city'] = $area2_matches[$v['pid']]['name'];
                                $result['region'] = $v['name'];
                                $province_id = $area2_matches[$v['pid']]['pid'];
                                $result['province'] = $this->province[$province_id]['name'];
                            }
                        }
                    }
                } else {
                    $result['region'] = $region;
                }
            } else if ($area3_matches && count($area3_matches) == 1) {
                foreach ($area3_matches as $id => $v) {
                    $city_id = $v['pid'];
                    $result['region'] = $v['name'];
                }
                $city = $this->city[$city_id];
                $province = $this->province[$city['pid']];

                $result['province'] = $province['name'];
                $result['city'] = $city['name'];
            } else if (empty($area3_matches) && $city == $region) {

                foreach ($this->city as $id => $v) {
                    if (mb_strpos($v['name'], $city) !== false) {
                        $area2_matches[$id] = $v;
                        $province_id = $v['pid'];
                        $result['city'] = $v['name'];
                    }
                }

                $result['province'] = $this->province[$province_id]['name'];
            }
        }
        return $result;
    }
}