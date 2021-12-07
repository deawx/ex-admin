<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2019-11-18
 * Time: 22:16
 */

namespace app\middleware;


use think\exception\HttpResponseException;
use think\facade\Cache;

/**
 * 请求频率限制
 * Class Throttle
 * @package app\common\middleware
 */
class Throttle
{
    public function handle($request, \Closure $next)
    {
        //最大请求次数
        $maxAttempts = env('THROTTLE.MAX_ATTEMPTS', 60);
        //间隔时间（分钟）
        $decayMinutes = env('THROTTLE.DECAY_MINUTES', 1);
        [$bool, $header] = $this->attempt($request->ip(),$request->pathInfo(), $decayMinutes, $maxAttempts);
        if ($bool) {
            $response = $next($request);
            $response->header($header);
            return $response;
        } else {
			throw new HttpResponseException(json(['code' => 400, 'msg' => '请求过于频繁，请稍后重试']));
        }
    }
    protected function attempt($ip,$pathInfo, $decayMinutes, $maxAttempts)
    {
        $decaySecond = $decayMinutes * 60;
        $bool = true;
        $cacheKey = md5($ip.$pathInfo);
        if (Cache::has($cacheKey)) {
            $value = Cache::get($cacheKey);
            if ($value['num'] >= $maxAttempts) {
                $time = $decaySecond - (time() - $value['time']);
                if ($time <= 0) {
                    Cache::delete($cacheKey);
                    $header = [
                        'X-RateLimit-Remaining' => $maxAttempts,
                        'X-RateLimit-Limit' => $maxAttempts
                    ];
                } else {
                    $bool = false;
                    $header = [
                        'Retry-After' => $time,
                        'X-RateLimit-Remaining' => 0,
                        'X-RateLimit-Limit' => $maxAttempts
                    ];
                }
            } else {
                $value['num'] += 1;
                Cache::tag('throttle')->set($cacheKey, $value,$decaySecond);
                $header = [
                    'X-RateLimit-Remaining' => $maxAttempts - $value['num'],
                    'X-RateLimit-Limit' => $maxAttempts
                ];
            }
        } else {
            $value = [
                'num' => 1,
                'time' => time()
            ];
            Cache::tag('throttle')->set($cacheKey, $value,$decaySecond);
            $header = [
                'X-RateLimit-Remaining' => $maxAttempts - 1,
                'X-RateLimit-Limit' => $maxAttempts
            ];
        }
        return [$bool, $header];
    }

}
