<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2019-07-14
 * Time: 01:47
 */

namespace plugin\apidoc\resource;


use League\Fractal\Manager;

use think\Collection;
use think\facade\Db;
use think\helper\Arr;
use think\helper\Str;
use think\Model;

/**
 * Class Resource
 * @package plugin\apidoc\resource
 * @method static response($data)
 */
abstract class Resource implements \JsonSerializable
{
    protected $data = [];
    protected $response = null;
    protected $example = [];
    protected $transform = [];
    public $params = [];
    protected static $tables = [];

    private function createExample($data, $parentKey=null)
    {
        if ($data instanceof Resource) {
            $example = $data->getExample();
            foreach ($example as $field => $desc) {
                if(is_null($parentKey)){
                    $this->example[$field] = $desc;
                }else{
                    $this->example[$parentKey . '.' . $field] = $desc;
                }
            }
        } elseif ($data instanceof Collection && $data->count() > 0) {
            $data = $data[0];
        }
        if ($data instanceof Model) {
            $table = $data->getTable();
            if (!isset(self::$tables[$table])) {
                self::$tables[$table]['fields'] = $data->getFields($table);
            }
            $fields = self::$tables[$table]['fields'];
            foreach ($fields as $field => $row) {
                if(is_null($parentKey)){
                    $key =$field;
                }else{
                    $key = $parentKey . '.' . $field;
                }
                if ($row['primary'] && empty($row['comment'])) {
                    if (!isset(self::$tables[$table]['primary'])) {
                        $arr = Db::query("show table status like '$table'");
                        $info = current($arr);
                        $this->example[$key] = $info['Comment'] . $field;
                        self::$tables[$table]['primary'] =$info;
                    }
                }
                if (!empty($row['comment'])) {
                    $this->example[$key] = $row['comment'];
                }
            }
        }
    }

    final function getExample()
    {
        $keys = array_keys($this->data);
        $example = [];
        foreach ($keys as $key) {
            foreach ($this->example as $field => $desc) {
                if (Str::startsWith($field, $key . '.')) {
                    $example[$field] = $desc;
                }
            }
            if (isset($this->example[$key])) {
                $example[$key] = $this->example[$key];
            }
        }
        return $example;
    }

    /**
     * 资源转换
     */
    abstract public function transform();


    /**
     * 转换值
     * @param string $key
     * @param string $desc
     * @param mixed $value
     * @return $this
     */
    protected function convert($key, $desc = null, $value = null)
    {
        if (is_null($value)) {
            $this->data[$key] = $this->$key;
        } else {
            $this->data[$key] = $value;
        }
        if (!is_null($desc)) {
            $this->example[$key] = $desc;
        }
        $this->createExample($this->data[$key], $key);
        return $this;
    }

    public static function __callStatic($name, $arguments)
    {
        return self::create()->createData(...$arguments);

    }

    public static function create(): self
    {
        return new static();
    }

    public function createData($data)
    {
        if (is_null($data)) {
            return $this;
        }
        $method = 'Item';
        try {
            if ($data instanceof Collection) {
                $method = 'Collection';
            } else {
                if (count($data) != count($data, 1)) {
                    $method = 'Collection';
                }
            }

        } catch (\Exception $exception) {

        }
        $this->example = [];
        $method = ucfirst($method);
        $class = "\\League\\Fractal\\Resource\\" . $method;
        $resource = new $class($data, [$this, 'setData']);

        $fractal = new Manager();
        $data = $fractal->createData($resource)->toArray();

        $this->response = $data['data'];
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->response;
    }

    public function __get($name)
    {
        return Arr::get($this->transform, $name);
    }

    /**
     * @param $data
     * @return array
     */
    public function setData($data): array
    {
        $this->createExample($data);
        $this->transform = $data;
        $this->transform();
        return $this->data;
    }
}
