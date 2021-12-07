<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2021-11-05
 * Time: 22:26
 */

namespace plugin\apidoc\validate;

use Respect\Validation\ChainedValidator;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;
use think\exception\ValidateException;
use think\helper\Arr;


/**
 * Class Validate
 * @package plugin\apidoc\validate
 */
abstract class Validate extends \think\Validate
{
    protected $v = [];
    final public function getRules(){
        return $this->v;
    }
    abstract protected function rules();
    public function __construct()
    {
        parent::__construct();
        $this->rules();
        foreach ($this->v as $v){
            $this->rule  = $this->rule + $v['v']->getRule();
        }
        $params = $this->data();
        foreach ($params as $field => $param) {
            //支持数组验证
            if (is_array($param) && isset($param[0]) && is_array($param[0])) {
                $validateFields = [];
                $removeFields = [];
                foreach ($this->rule as $key => $rule) {
                    if (strstr($key, $field . '.')) {
                        $validateFields[] = $key;
                        $removeFields[$key] = true;
                    }
                }
                $validate = clone $this;
                if (!empty($validateFields)) {
                    foreach ($param as $item) {
                        $validateData[$field] = $item;
                        $validate->only($validateFields)->failException()->check($validateData);
                    }
                }
                $this->remove($removeFields);
            }
        }
        $this->failException()->check($params);
    }

    /**
     * 参数获取
     * @param array $only
     * @param string $method
     * @return array|mixed
     */
    public function data(array $only = [],$method = 'param')
    {
        if(empty($only)){
            return request()->except(['function', 'controller', 'action']);
        }else{
            return request()->only($only,$method);
        }
    }
    /**
     * 请求验证示例
     * @param string $field 验证字段
     * @param string $desc 描述
     * @param string $exampleValue 示例值
     * @return Validator
     */
    protected function v($field, $desc = '',$exampleValue='')
    {
        $v = new Validator($field,$desc);
        $this->v[] = [
            'v'=>$v,
            'field'=>$field,
            'desc'=>$desc,
            'value'=>$exampleValue,
        ];
        return $v;
    }

}
