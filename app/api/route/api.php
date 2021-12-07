<?php

use think\facade\Route;
use think\facade\Request;


//接口版本控制
$version = Request::header('version', 'v1');
$method = Request::method();
$method = strtolower($method);
//兼容快捷路由和按请求方式访问
$pathArr = explode('/', Request::pathinfo());
$function = '';
$rule = '';
if (count($pathArr) > 1) {
    $function = '<function>';
    $rule = '/';
}
$route = $version . '.<controller>/' . $method . $function;
$rule = '<controller>' . $rule . $function;
//刷新token
Route::post('refreshToken', function () {
    $tokens = \Eadmin\support\Token::refresh();
    if ($tokens) {
        return json(['code' => 200, 'data' => $tokens]);
    } else {
        return json(['code' => 6001, 'message' => 'token无法续期']);
    }
});
//上传文件
Route::post('upload',function(\think\Request $request){
$file  = $request->file('file');
    $url = \Eadmin\service\FileService::instance()->upload($file,null,'',config('admin.uploadDisks'));
    if($url === false){
        return json(['code' => 5005, 'message' => '上传失败']);
    }else{
        return json(['code' => 200, 'data' => $url]);
    }
});

//无需验证控制器,验证单独添加auth中间件
Route::group(function () use ($rule, $route) {
    Route::any($rule, $route);
})->middleware(['throttle']);


