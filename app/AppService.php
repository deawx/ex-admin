<?php
declare (strict_types = 1);

namespace app;

use Carbon\Carbon;
use think\Service;


/**
 * 应用服务类
 */
class AppService extends Service
{
    public function register()
    {
        // 服务注册

        //时间类设置中国
        Carbon::setLocale('zh');

        // 定时任务
		Crontab::handle();

    }

    public function boot()
    {
        // 服务启动
    }
}
