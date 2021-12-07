<?php

namespace app;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 添加自定义异常处理机制
        if (app('http')->getName() == 'api') {
            if ($e instanceof ValidateException) {
                return json(['code' => 422, 'message' => $e->getMessage()]);
            } elseif ($e instanceof HttpResponseException) {

            } elseif ($e instanceof ModelNotFoundException) {
                return json(['code' => 423, 'message' => $e->getModel() . '数据不存在']);
            } else {
                $response = Response::create($this->convertExceptionToArray($e), 'json');
                return $response->code(500);
            }
        }
        // 其他错误交给系统处理
        return parent::render($request, $e);
    }

    /**
     * 收集异常数据
     * @param Throwable $exception
     * @return array
     */
    protected function convertExceptionToArray(Throwable $exception): array
    {
        $data = parent::convertExceptionToArray($exception); // TODO: Change the autogenerated stub
        if (isset($data['traces'])) {
            $data['traces'][0]['trace'] = collect($data['traces'][0]['trace'])->map(function ($item) {
                $item['source'] = [];
                if (isset($item['file'])) {
                    $item['source'] = $this->getSourceCodeArray($item['file'], $item['line']);
                }
                return $item;
            });
        }
        return $data;
    }

    /**
     * 获取出错文件内容
     * 获取错误的前9行和后9行
     * @param string $file 文件
     * @param int $line 行号
     * @return array
     */
    public function getSourceCodeArray($file, $line)
    {
        // 读取前9行和后9行

        $first = ($line - 9 > 0) ? $line - 9 : 1;

        try {
            $contents = file($file) ?: [];
            $source = [
                'first' => $first,
                'source' => array_slice($contents, $first - 1, 19),
            ];
        } catch (\Exception $e) {
            $source = [];
        }

        return $source;
    }
}