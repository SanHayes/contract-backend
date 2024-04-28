<?php

use think\facade\Lang;
use xtype\Ethereum\Utils;

if (!function_exists('__')) {
    /**
     * 获取语言变量值
     * @param string $name 语言变量名
     * @param array $vars 动态变量值
     * @return mixed
     */
    function __(string $name, ...$vars)
    {
        if (is_numeric($name) || !$name) {
            return $name;
        }
        return Lang::get($name, $vars);
    }
}

if (!function_exists('getTron')) {
    function getTron()
    {

    }
}

if (!function_exists('com_dw')) {
    /**
     * @param $num
     * @return float|int
     */
    function com_dw($num)
    {
        return floor($num * 1000000) / 1000000;
    }
}

if (!function_exists('eth_weiToEth')) {
    /**
     * @param $balance
     * @param $wei
     * @return string|null
     */
    function eth_weiToEth($balance, $wei = 6)
    {
        $isUsdt = false;
        if ($wei == 6) {
            $isUsdt = true;
        }
        return weiToEth($balance, false, $isUsdt);
    }
}

if (!function_exists('weiToEth')) {
    /**
     * wei转ether
     */
    function weiToEth($value, $hex = false, $usdt = false)
    {
        if (strtolower(substr($value, 0, 2)) == '0x') {
            $value = Utils::hexToDec(Utils::remove0x($value));
        }
        if ($usdt) {
            $value = bcdiv($value, '1000000', 6);
        } else {
            $value = bcdiv($value, '1000000000000000000', 18);
        }
        if ($hex) {
            return '0x' . Utils::decToHex($value);
        }
        return $value;
    }
}

if (!function_exists('ethFill0')) {
    function ethFill0($string)
    {
        $str = Utils::decToHex($string);
        return fill0(Utils::remove0x($str));
    }
}

if (!function_exists('fill0')) {
    function fill0($str, $bit = 64)
    {
        if (!strlen($str)) {
            return "";
        }
        $str_len = strlen($str);
        $zero = '';
        for ($i = $str_len; $i < $bit; $i++) {
            $zero .= "0";
        }
        return $zero . $str;
    }
}

if (!function_exists('thinkDecrypt')) {
    /**
     * 解密
     * @param string $data 密文
     * @return false|string
     */
    function thinkDecrypt($data)
    {
        $key = md5(config('encrypt_key'));
        $data = str_replace(['-', '_'], ['+', '/'], $data);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        $data = base64_decode($data);
        $expire = substr($data, 0, 10);
        $data = substr($data, 10);

        if ($expire > 0 && $expire < time()) {
            return '';
        }
        $x = 0;
        $len = strlen($data);
        $l = strlen($key);
        $char = $str = '';

        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= substr($key, $x, 1);
            $x++;
        }

        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            } else {
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }
        return base64_decode($str);
    }
}

if (!function_exists('thinkEncrypt')) {
    /**
     * 加密
     * @param string $data 明文
     * @param int $expire 过期时间
     * @return array|string|string[]
     */
    function thinkEncrypt($data, $expire = 60)
    {
        $key = md5(config('encrypt_key'));
        $data = base64_encode((string)$data);
        $x = 0;
        $len = strlen($data);
        $l = strlen($key);
        $char = '';

        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= substr($key, $x, 1);
            $x++;
        }

        $str = sprintf('%010d', $expire ? $expire + time() : 0);

        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
        }
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($str));
    }
}

if (!function_exists('strHide')) {
    /**
     * 隐藏部分字符串 支持多字节字符
     *
     * @param string $str 要处理的字符串
     * @param int $start 开始隐藏位置
     * @param int $length 隐藏长度 (不支持负数)
     * @param string $mask 掩码 默认 *
     * @return string
     */
    function strHide($str, $start = 0, $length = 1, $mask = '*')
    {
        return substr_replace($str, str_pad('', 4, $mask), strlen(mb_substr($str, 0, $start)),
            strlen(mb_substr($str, $length + 1)));
    }
}

if (!function_exists('format_number')) {
    /**
     * @title 自定义保留几位小数方法
     * @param string $value
     * @param int $num 默认保留两位小数
     * @return string
     */
    function format_number($value, $num = 2)
    {
        $array = explode('.', $value);
        if (isset($array[0]) && isset($array[1])) {
            return $array[0] . '.' . substr($array[1], 0, $num);
        }
        return $value;
    }
}

if (!function_exists('is_mobile')) {

    function is_mobile($string)
    {
        if (!empty($string)) {
            return preg_match('/^1[3|4|5|6|7|8|9][0-9]\d{8}$/', $string);
        }
        return FALSE;
    }
}