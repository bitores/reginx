<?php
/**
 * 数据过滤
 * @copyright reginx.com
 * $Id: filter.class.php 8897 2015-11-30 12:55:03Z reginx $
 */
class filter {

    /**
     * 数据验证规则
     *
     * @var unknown_type
     */
    public static $rules = array(
        // 键名小写
        'require'     => '/^.+$/s',
        'email'       => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
        'phone'       => '/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/',
        'mobile'      => '/^((\(\d{2,3}\))|(\d{3}\-))?1\d{10}$/',
        'url'         => '/^https?:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',
        'currency'    => '/^\d+(\.\d+)?$/',
        'number'      => '/^\d+$/',
        'zip'         => '/^[1-9]\d{5}$/',
        'qq'          => '/^[1-9]\d{4,12}$/',
        'integer'     => '/^[-\+]?\d+$/',
        'double'      => '/^[-\+]?\d+(\.\d+)?$/',
        'english'     => '/^[A-Za-z]+$/',
        'uid'         => '/^[A-Za-z0-9_]{2,30}$/',
        'passwd'      => '/^[^\s]{2,37}$/',
        'all'         => '/^.*$/',
        'price'       => '/^\d+(\.\d+)?$/',
        'cn_id'       => '/^\d{6}(18|19|20)?\d{2}(0[1-9]|1[012])(0[1-9]|[12]\d|3[01])\d{3}(\d|X)$/i'
    );


    /**
     * 判断是否为 手机号码
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function ismobile ($str) {
        return preg_match(self::$rules['mobile'], $str);
    }

    /**
     * 检测是否是个有效的正浮点值
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function ispositivefloat ($str) {
        if (preg_match(self::$rules['currency'], $str)) {
            return $str > 0;
        }
        return false;
    }

    /**
     * 检测是否是个有效的价格值
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function ispositiveprice ($str) {
        if (preg_match(self::$rules['currency'], $str)) {
            return $str > 0;
        }
        return false;
    }


    /**
     * 检测是否是个有效的正整数值
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function ispositiveint ($str) {
        if (preg_match(self::$rules['number'], $str)) {
            return $str > 0;
        }
        return false;
    }

    /**
     * 获取 正则表达式
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function regexp ($str) {
        return filter_var($str, FILTER_VALIDATE_REGEXP);
    }

    /**
     * 获取 url
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function url ($str) {
        return filter_var($str, FILTER_VALIDATE_URL);
    }

    /**
     * 获取 IP
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function ip ($str) {
        return filter_var($str, FILTER_VALIDATE_IP);
    }

    /**
     * 获取 Email
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function email ($str) {
        return filter_var($str, FILTER_VALIDATE_EMAIL);
    }

    /**
     * 原始内容
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function source ($str) {
        return $str;
    }


    /**
     * 获取安全文本
     *
     * @param unknown_type $str
     */
    public static function text ($str) {
        $str = trim($str);
        if (!empty($str)) {
            $str = htmlspecialchars(
                    preg_replace('/\&.+?\;/i', '', self::rmtags($str)),
                    ENT_QUOTES, 'utf-8');
        }
        return $str;
    }

    /**
     * 过滤html标签
     *
     * @param unknown_type $str
     */
    public static function rmtags ($str) {
        if (empty($str) && !is_numeric($str)) {
            return '';
        }
        $str = htmlspecialchars_decode($str);
        $str = preg_replace('/\<style(.*?)>.*?\<\/style\>/is', '', $str);
        $str = preg_replace('/\<script(.*?)>.*?\<\/script\>/is', '', $str);
        $str = preg_replace('/\<iframe(.*?)>.*?\<\/iframe\>/is', '', $str);
        $str = strip_tags($str);
        return trim($str);
    }

    /**
     * 去除指定的html标签
     *
     * @param unknown_type $str
     */
    public static function cleartags ($str, $tags = 'div,ul,li,embed,span,a') {
        $str = htmlspecialchars_decode($str);
        $str = preg_replace('/\<\!\-\-.*?\-\->/is', '', $str);
        $str = preg_replace('/\<style(.*?)>.*?\<\/style\>/is', '', $str);
        $str = preg_replace('/\<script(.*?)>.*?\<\/script\>/is', '', $str);
        $str = preg_replace('/\<iframe(.*?)>.*?\<\/iframe\>/is', '', $str);
        $str = preg_replace(
                '/\<\/?\s*(' . str_replace(',', '|', $tags) . ').*?\>/is', '',
                $str);
        return trim($str);
    }

    /**
     * 默认html过滤
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function html ($str) {
        $str = htmlspecialchars_decode($str);
        $str = preg_replace('/\<style(.*?)>.*?\<\/style\>/is', '', $str);
        $str = preg_replace('/\<script(.*?)>.*?\<\/script\>/is', '', $str);
        $str = preg_replace('/javascript\:.*?[\"\']?/is', '#', $str);
        $str = htmlspecialchars($str, ENT_QUOTES, 'utf-8');
        return $str;
    }

    /**
     * 默认过滤
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function normal ($str) {
        return htmlspecialchars(self::rmtags($str), ENT_QUOTES, 'utf-8');
    }

    /**
     * 转换成整数
     *
     * @param unknown_type $str
     */
    public static function int ($str) {
        return intval($str);
    }

    /**
     * 转换成时间戳
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function timestamp ($str) {
        if (!is_numeric($str) && ($str = strtotime($str)) === false) {
            $str = 0;
        }
        return $str;
    }

    /**
     * 原始数据
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function code ($str) {
        return htmlspecialchars(htmlspecialchars_decode(trim($str)), ENT_QUOTES, 'utf-8');
    }

    /**
     * 是否是合法的账号格式 (支持邮箱, 中文, 英文,数字等非特殊字符组合)
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function isaccount ($str) {
        return !preg_match(
                '/(\s|\"|\#|\$|\%|\&|\'|\*|\+|\,|\/|\;|\<|\=|\>|\\\|\^|\`|\||\~|\:\(|\)|\[|\]|\?)+/',
                $str);
    }

    /**
     * 安全过滤
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function ult ($str) {
        return preg_replace(
                '/(\s|\"|\#|\$|\%|\&|\'|\*|\+|\,|\/|\;|\<|\=|\>|\@|\\\|\^|\`|\||\~|:)+/',
                '', self::text($str));
    }
}
?>