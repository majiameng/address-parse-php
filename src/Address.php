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

    /**
     * __construct
     */
    public function __construct()
    {
        $this->loadArea();
    }

    /**
     * @param $province
     * @param $city
     * @param $region
     * @return $this
     */
    public function setArea($province=[],$city=[],$region=[])
    {
        if(!empty($province)) $this->province = $province;
        if(!empty($city)) $this->city = $city;
        if(!empty($region)) $this->region = $region;
        return $this;
    }

    /**
     * @return array
     */
    public function getArea()
    {
        return [
            'province'=>$this->province,
            'city'=>$this->city,
            'region'=>$this->region,
        ];
    }

    /**
     * Author: JiaMeng <666@majiameng.com>
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
        $result = [
            'province_id'   => 0,// 省编码
            'province'      => '',// 省
            'city_id'       => 0,// 市编码
            'city'          => '',// 市
            'region_id'     => 0,// 区编码
            'region'        => '',// 区
            'street'        => '',// 街道
            'address'       => $string,// 地址
        ];

        // 根据统计规律分析出二三级地址+街道地址
        $ruleAnalysisData = $this->ruleAnalysis($result['address']);
        $result = array_merge($result, $ruleAnalysisData);
        // 智能解析出省市区
        $result = $this->parseAddressDetail($result);

        // 解析用户信息
        if ($user) {
            $decompose = $this->decompose($result,$string);
            $result = array_merge($result, $decompose);
        }

        // 清理街道信息
        $result['street'] = str_replace(
            [$result['region'], $result['city'], $result['province'],$result['name'],$result['mobile'],$result['idcard'],$result['postcode']],
            '',
            $result['street']
        );
        $result['address'] = $result['province'] . $result['city'] . $result['region'] . $result['street'];

        return $result;
    }

    /**
     * 分离手机号(座机)，身份证号，姓名等用户信息
     * Author: JiaMeng <666@majiameng.com>
     * @param $result
     * @param $string
     * @return string[]
     */
    private function decompose($result,$string)
    {
        $compose = [
            'postcode'      => '',// 邮政编码
            'name'          => '',// 名字
            'mobile'        => '',// 手机号
            'idcard'        => '',// 身份证号
            'address'       => '',// 地址
        ];

        // 清除省市
        $addressArray = [];
        if(!empty($result['province'])) $addressArray[] = $result['province'];
        if(!empty($result['city'])) $addressArray[] = $result['city'];
        if(!empty($result['region'])) $addressArray[] = $result['region'];
        $string = str_replace($addressArray, '', $string);

        //1. 过滤掉收货地址中的常用说明字符，排除干扰词
        $string = preg_replace(
            "/收货地址|详细地址|地址|收货人|收件人|收货|邮编|电话|身份证号码|身份证号|身份证|手机号码|所在地区|：|:|；|;|，|,|。|\.|“|”|\"/",
            ' ',
            $string
        );

        //2. 把空白字符(包括空格\r\n\t)都换成一个空格,去除首位空格
        $string = trim(preg_replace('/\s{1,}/', ' ', $string));

        //3. 去除手机号码中的短横线 如136-3333-6666 主要针对苹果手机
        $string = preg_replace('/0-|0?(\d{3})-(\d{4})-(\d{4})/', '$1$2$3', $string);

        //4. 提取中国境内身份证号码
        preg_match('/\d{18}|\d{17}X/i', $string, $match);
        if ($match) {
            $compose['idcard'] = strtoupper($match[0]);
            $string = str_replace($match[0], '', $string);
        }

        //5. 提取11位手机号码或者7位以上座机号
        preg_match('/\d{7,11}[\-_]\d{2,6}|\d{7,11}|\d{3,4}-\d{6,8}/', $string, $match);
        if ($match && $match[0]) {
            $compose['mobile'] = $match[0];
            $string = str_replace($match[0], '', $string);
        }

        //6. 提取6位邮编 邮编也可用后面解析出的省市区地址从数据库匹配出
        preg_match('/\d{6}/', $string, $match);
        if ($match && $match[0]) {
            $compose['postcode'] = $match[0];
            $string = str_replace($match[0], '', $string);
        }

        //再次把2个及其以上的空格合并成一个，并首位TRIM
        $string = trim(preg_replace('/ {2,}/', ' ', $string));

        //按照空格切分 长度长的为地址 短的为姓名 因为不是基于自然语言分析，所以采取统计学上高概率的方案
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
        return $compose;
    }

    /**
     * 根据统计规律分析出二三级地址+街道地址
     * Author: JiaMeng <666@majiameng.com>
     * @param $addr
     * @return array
     */
    private function ruleAnalysis($addr)
    {
        $addr = str_replace([' ', ','], ['', ''], $addr);
        $addr_origin = $addr;
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
     * @return array
     */
    private function parseAddressDetail($result)
    {
        if ($result['region'] != '') {
            $regionMatches = [];
            foreach ($this->region as $id => $v) {
                if (mb_strpos($v['name'], $result['region']) !== false) {
                    $regionMatches[$id] = $v;
                }
            }

            if (!empty($regionMatches) && count($regionMatches) > 1) {
                if ($result['city']) {
                    $cityMatches = [];
                    foreach ($this->city as $id => $v) {
                        if (mb_strpos($v['name'], $result['city']) !== false) {
                            $cityMatches[$id] = $v;
                        }
                    }

                    if (!empty($cityMatches)) {
                        foreach ($regionMatches as $v) {
                            if (isset($cityMatches[$v['pid']])) {
                                $result['city_id'] = $cityMatches[$v['pid']]['id'];
                                $result['city'] = $cityMatches[$v['pid']]['name'];
                                $result['region_id'] = $v['id'];
                                $result['region'] = $v['name'];
                                $result['province_id'] = $cityMatches[$v['pid']]['pid'];
                                $result['province'] = $this->province[$result['province_id']]['name'];
                            }
                        }
                    }
                }
            } else if ($regionMatches && count($regionMatches) == 1) {
                foreach ($regionMatches as $v) {
                    $result['city_id'] = $v['pid'];
                    $result['region_id'] = $v['id'];
                    $result['region'] = $v['name'];
                }
                $city = $this->city[$result['city_id']];
                $province = $this->province[$city['pid']];
                $result['province_id'] = $city['pid'];
                $result['province'] = $province['name'];
                $result['city'] = $city['name'];
            } else if (empty($regionMatches) && $result['city'] == $result['region']) {
                foreach ($this->city as $id => $v) {
                    if (mb_strpos($v['name'], $result['city']) !== false) {
                        $result['city_id'] = $v['id'];
                        $result['city'] = $v['name'];
                        $result['province_id'] = $v['pid'];
                        $result['province'] = $this->province[$result['province_id']]['name'];
                        break;
                    }
                }
            }
        }
        return $result;
    }
}