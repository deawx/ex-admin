<?php

namespace plugin\apidoc\service;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\DocParser;
use Eadmin\Admin;
use Eadmin\support\Annotation;
use plugin\apidoc\validate\Validate;
use Symfony\Component\Finder\Finder;
use Symfony\Component\VarDumper\Command\Descriptor\DumpDescriptorInterface;
use think\exception\ErrorException;
use think\exception\HttpResponseException;
use think\facade\Cache;
use think\helper\Arr;
use think\helper\Str;

class Service
{
    /**
     * 扫描注释接口
     * @return array
     * @throws \ReflectionException
     */
    public function scan()
    {
        $data = Cache::get('plugin_apidoc');
        if($data){
            return $data;
        }
        $id = 1;
        $data = [];
        $path = app()->getAppPath() . 'api' . DIRECTORY_SEPARATOR . 'controller';
        $data = array_merge($data, $this->buildDoc($id, $path, "app\\api\\"));
        foreach (Admin::plug()->getServiceProviders() as $serviceProvider) {
            $path = $serviceProvider->getPath();
            $path = $path . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'api';
            $data = array_merge($data, $this->buildDoc($id, $path, $serviceProvider->getNamespace(), $serviceProvider->getName()));
        }
        $data = $this->grouping($data);
        $data = Admin::tree($data);
        Cache::set('plugin_apidoc',$data);
        return $data;
    }

    protected function findGroup($name, $data, $pid = 0)
    {
        $id = 0;
        foreach ($data as $value) {
            if ($value['type'] == 'group' && $name == $value['name']) {
                if ($value['pid'] == $pid) {
                    $id = $value['id'];
                }
            }
        }
        return $id;
    }

    protected function grouping($data)
    {
        uasort($data, function ($a, $b) {
            return mb_strlen($a['group']) > mb_strlen($b['group']);
        });

        foreach ($data as &$row) {
            if (!empty($row['group'])) {
                if ($row['group'] == '/') {
                    $row['pid'] = 0;
                    continue;
                }
                $groups = explode('/', $row['group']);
                $pid = 0;
                foreach ($groups as $name) {
                    $name = trim($name);
                    $parentId = $pid;
                    $pid = $this->findGroup($name, $data, $pid);
                    if ($pid == 0) {
                        $ids = array_column($data, 'id');
                        $id = max($ids);
                        array_push($data, [
                            'id' => ++$id,
                            'name' => $name,
                            'pid' => $parentId,
                            'group' => '',
                            'sort' => 99,
                            'type' => 'group',
                        ]);
                        $pid = $id;
                    }
                }
                $row['pid'] = $pid;
            }
        }
        uasort($data, function ($a, $b) {
            if ($a['sort'] == $b['sort']) {
                return $a['id'] > $b['id'];
            }
            {
                return $a['sort'] > $b['sort'];
            }
        });
        return $data;
    }

    protected function buildDoc(&$id, $path, $plugNamespace, $plugName = '')
    {
        $data = [];
        $except = plug()->apidoc->config('doc.except') ?? [];
        if (is_dir($path)) {

            $finder = new Finder();
            foreach ($finder->files()->in($path)->name(['*.php']) as $file) {
                $controller = str_replace('.php', '', basename($file->getFilename()));
                $dir = str_replace(app()->getRootPath(), '', $file->getPath());
                $namespace = str_replace('/', '\\', $dir) . '\\' . $controller;
                $dir = str_replace('\\', '/', $dir);
                if (in_array($namespace, $except) || Str::startsWith($dir, $except)) {
                    continue;
                }
                $reflection = new \ReflectionClass($namespace);
                $parse = Annotation::parse($reflection->getDocComment());
                $pid = 0;
                if ($parse && $parse['title']) {
                    $data[] = [
                        'id' => $id,
                        'name' => $parse['title'],
                        'pid' => 0,
                        'sort' => $parse['sort'] ?? 99,
                        'group' => $parse['group'] ?? '',
                        'type' => 'group',
                    ];
                    $pid = $id;
                    $id++;
                }
                foreach ($reflection->getMethods() as $method) {
                    if ($method->isPublic() && $method->class == $namespace) {
                        $parse = Annotation::parse($method->getDocComment());

                        if ($parse) {
                            $params = [];

                            //获取参数
                            foreach ($method->getParameters() as $index => $parameter) {
                                if ($parameter->getClass()) {
                                    $parentClass = $parameter->getClass()->getParentClass();
                                    if ($parentClass) {
                                        $name = $parentClass->getName();
                                        if ($name == 'plugin\apidoc\validate\Validate') {
                                            $class = $parameter->getClass();
                                            $instance = $class->newInstance(false);
                                            $rules = $instance->getRules();
                                            foreach ($rules as $index => $rule) {
                                                $params[$index]['param'] = $rule['field'];
                                                $params[$index]['value'] = $rule['value'];
                                                $params[$index]['desc'] = $rule['desc'];
                                            }
                                            continue;
                                        }
                                    }
                                }
                                $desc = '';
                                $value = '';
                                foreach ($parse['params'] as $param) {
                                    if ($param['var'] == $parameter->getName()) {
                                        $desc = $param['desc'];
                                        if (isset($param['value'])) {
                                            $value = $param['value'];

                                        }
                                    }
                                }
                                if ($parameter->isDefaultValueAvailable()) {
                                    $value = $parameter->getDefaultValue();
                                    $desc = "(可选)" . $desc;
                                } else {
                                    $desc = "(<span class=text-red-500>必填</span>)" . $desc;
                                }
                                $params[$index]['param'] = $parameter->getName();
                                $params[$index]['value'] = $value;
                                $params[$index]['desc'] = $desc;
                            }

                            //获取response示例

                            $controllerInstance = app()->invokeClass($namespace);
                            $response = [];
                            if (isset($parse['response'])) {
                                foreach ($parse['response'] as $parseResponse) {
                                    $resourceClass = $plugNamespace . 'resource\\' . $parseResponse['resource'];
                                    if (class_exists($resourceClass)) {
                                        //获取接口响应内容
                                        $resource = app()->make($resourceClass);
                                        $responseData = $this->getResponseData($controllerInstance, $method, $resource->params);
                                        try {
                                            $resource->transform();
                                        }catch (\Exception $e){

                                        }

                                        $example = $resource->getExample();
                                        if (count($example) == 0) {
                                            $resource->createData($responseData['data']);
                                        }
                                        $example = $resource->getExample();

                                        $response[] = [
                                            'title' => $parseResponse['desc'],
                                            'collapse' => true,
                                            'data' => $this->getExampleData($responseData, $example),
                                        ];
                                    }
                                }
                            } else {
                                $vars = array_column($params, 'value', 'param');
                                $responseData = $this->getResponseData($controllerInstance, $method, $vars);
                                $example = $reflection->getMethod('getExample')->invoke($controllerInstance);
                                $response[] = [
                                    'title' => '',
                                    'collapse' => true,
                                    'data' => $this->getExampleData($responseData, $example),
                                ];
                            }
                            //判断接口请求方法
                            $methodName = $method->getName();
                            if (Str::startsWith($methodName, 'get')) {
                                $method = 'GET';
                                $methodName = substr($methodName, 3);
                            } elseif (Str::startsWith($methodName, 'put')) {
                                $method = 'PUT';
                                $methodName = substr($methodName, 3);
                            } elseif (Str::startsWith($methodName, 'post')) {
                                $method = 'POST';
                                $methodName = substr($methodName, 4);
                            } elseif (Str::startsWith($methodName, 'delete')) {
                                $method = 'DELETE';
                                $methodName = substr($methodName, 6);
                            }
                            if (!empty($methodName)) {
                                $methodName = '/' . $methodName;
                            }
                            //组装请求接口
                            if (empty($plugName)) {
                                $url = '/api/' . $controller . $methodName;
                            } else {
                                $url = '/api/plugin/' . $plugName . '/' . $controller . $methodName;
                            }
                            $header = plug()->apidoc->config('doc.header') ?? [];
                            if (isset($parse['header'])) {
                                $header = array_merge($header, $parse['header']);
                            }
                            $data[] = [
                                'id' => $id,
                                'group' => $parse['group'] ?? '',
                                'pid' => $pid,
                                'type' => 'request',
                                'sort' => $parse['sort'] ?? 99,
                                'name' => $parse['title'],
                                'domain' => request()->domain(),
                                'url' => $url,
                                'method' => $method,
                                'header' => $header,
                                'data' => $params,
                                'response' => $response
                            ];
                            $id++;
                        }
                    }
                }
            }
        }
        return $data;
    }

    protected function getExampleData($response, $example)
    {
        $data = $response;
        $jsonStr = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        foreach ($example as $field => $desc) {
            if (strpos($field, '.')) {
                $fields = explode('.', $field);
                $field = array_pop($fields);

                $pattenPrefix = '';
                foreach ($fields as $f) {
                    $pattenPrefix .= "\"$f\":.*";
                }

                //                $patten = "/($pattenPrefix\"$field\".*)(\/\/.*)\n/Us";
                //
                //                if (preg_match($patten, $jsonStr, $arr)) {
                //
                //                    $jsonStr = preg_replace($patten, $arr[1] . "\n", $jsonStr, 1);
                //                }
                $patten = "/$pattenPrefix\"$field\"(.*)(\n)/Us";
                if (preg_match($patten, $jsonStr, $arr)) {
                    $replace = rtrim($arr[0], "\n");
                    $jsonStr = preg_replace($patten, $replace . "\t\t//" . $desc . "\n", $jsonStr, 1);
                    continue;
                }
                $patten = "/$pattenPrefix\"$field\"(.*)(,\n)/Us";

                if (preg_match($patten, $jsonStr, $arr)) {

                    $replace = rtrim($arr[0], "\n");
                    $jsonStr = preg_replace($patten, $replace . "\t\t//" . $desc . "\n", $jsonStr, 1);
                }
            } else {
                $patten = "/\"$field\":(.*)(,\n)/U";
                if (preg_match($patten, $jsonStr)) {
                    $jsonStr = preg_replace($patten, "\"$field\":$1,\t\t//" . $desc . "\n", $jsonStr, 1);
                    continue;
                }
                $patten = "/\"$field\":(.*)(\n)/U";
                if (preg_match($patten, $jsonStr, $arr)) {
                    $jsonStr = preg_replace($patten, "\"$field\":$1\t\t//" . $desc . "\n", $jsonStr, 1);
                }
            }
        }
        return rawurlencode($jsonStr);
    }

    protected function getResponseData($instance, $method, $vars = [])
    {
        $responseData = null;
        try {
            app()->invokeReflectMethod($instance, $method, $vars);
        } catch (HttpResponseException $exception) {
            $responseData = $exception->getResponse()->getData();
        }catch (\Throwable $exception) {
            $responseData = [
                'code' => 200,
                'message' => '成功',
                'data' => []
            ];
        }
        return $responseData;
    }
}
