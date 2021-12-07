<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2021-12-04
 * Time: 15:44
 */

namespace plugin\apidoc\validate;

/**
 * Class Validator
 * @package plugin\apidoc\validate
 * @method $this require() 验证必须
 * @method $this number() 验证是否为纯数字
 * @method $this integer() 验证是否为整数
 * @method $this float() 验证是否为浮点数字
 * @method $this boolean() 验证是否布尔值
 * @method $this bool() 验证是否布尔值
 * @method $this email() 验证是否为email地址
 * @method $this date() 验证是否是否为有效的日期
 * @method $this alpha() 验证是否纯字母
 * @method $this alphaNum() 验证是否字母和数字
 * @method $this alphaDash() 验证是否字母和数字，下划线_及破折号-
 * @method $this chs() 验证只能是汉字
 * @method $this chsAlpha() 验证只能是汉字、字母
 * @method $this chsAlphaNum() 验证只能是汉字、字母和数字
 * @method $this chsDash() 验证只能是汉字、字母、数字和下划线_及破折号-
 * @method $this cntrl() 验证只能是控制字符（换行、缩进、空格）
 * @method $this graph() 验证某个字段的值只能是可打印字符（空格除外）
 * @method $this print() 验证某个字段的值只能是可打印字符（包括空格）
 * @method $this lower() 验证只能是小写字符
 * @method $this upper() 验证只能是大写字符
 * @method $this space() 验证只能是空白字符（包括缩进，垂直制表符，换行符，回车和换页字符）
 * @method $this xdigit() 验证只能是十六进制字符串
 * @method $this activeUrl() 验证有效的域名或者IP
 * @method $this url() 验证有效的URL地址
 * @method $this ip() 验证有效的IP地址
 * @method $this mobile() 验证有效的手机
 * @method $this idCard() 验证有效的身份证格式
 * @method $this macAddr() 验证有效的MAC地址
 * @method $this zip() 验证有效的邮政编码
 * @method $this accepted() 验证是否为 yes, on, 或是 1,这在确认"服务条款"是否同意时很有用
 * @method $this dateFormat(string  $format) 验证是否为指定格式的日期y-m-d
 * @method $this after(string $date) 验证某个字段的值是否在某个日期之后 after('2016-3-18')
 * @method $this before(string $date) 验证某个字段的值是否在某个日期之前 before('2016-3-18')
 * @method $this expire(string $start,string $end) 验证当前操作（注意不是某个值）是否在某个有效日期之内 expire('2016-3-18','2016-3-19')
 * @method $this in(mixed $value) 验证是否在某个范围
 * @method $this notIn(mixed $value) 验证不在某个范围
 * @method $this max(int $value) 验证值的最大长度
 * @method $this min(int $value) 验证值的最小长度
 * @method $this allowIp(mixed $value) 验证当前请求的IP是否在某个范围
 * @method $this denyIp(mixed $value) 验证当前请求的IP是否禁止访问
 * @method $this between(int $start,int $end) 验证是否在某个区间
 * @method $this confirm(string $field) 验证是否和另外一个字段的值一致
 * @method $this different(string $field) 验证是否和另外一个字段的值不一致
 * @method $this eq(int $value) 验证是否等于某个值
 * @method $this egt(int $value) 验证是否大于等于某个值
 * @method $this gt(int $value) 验证是否大于某个值
 * @method $this elt(int $value) 验证是否小于等于某个值
 * @method $this lt(int $value) 验证是否小于某个值
 * @method $this filter(string $value) filter_var进行验证
 * @method $this regex(string $value) 正则验证
 * @method $this unique(string $table,string $field = '',$except = '',$pk = '') 验证值是否为唯一的
 * @method $this requireIf(string $field,mixed $value) 验证值等于某个值的时候必须
 * @method $this requireWith(string $field) 验证某个字段有值的时候必须
 * @method $this requireWithout(string $field) 验证某个字段没有值的时候必须
 * @method $this file() 验证是否是一个上传文件
 * @method $this fileExt(string $ext) 验证上传文件后缀
 * @method $this fileMime(string $value) 验证上传文件类型
 * @method $this fileSize(string $value) 验证上传文件大小
 * @method $this image($width = '',$height = '',$type = '') 验证是否是一个图像文件，width height和type都是可选，width和height必须同时定义
 * @method $this length(int $start,int $end = '') 验证长度是否在某个范围 或者指定长度 length(5)
 */
class Validator
{
    protected $rule = [];
    protected $field;
    protected $desc;
    public function __construct($field,$desc)
    {
        $this->field = $field;
        $this->desc = $desc;
    }
    public function getRule(){
        $key = $this->field.'|'.$this->desc;
        return [$key=>implode('|',$this->rule)];
    }
    public function __call($method, $arguments)
    {
        if(empty($arguments)){
            $this->rule[] = $method;
        }else{
            if(is_array($arguments[0])) {
                $arguments = $arguments[0];
            }
            $rule = implode(',',$arguments);
            $this->rule[] = "$method:".$rule;
        }
        return $this;
    }

}
