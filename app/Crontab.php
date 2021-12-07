<?php

namespace app;


use Eadmin\facade\Schedule;

/**
 * 定时任务
 * Class Crontab
 */
class Crontab
{
    public static function handle()
    {


        Schedule::call('每分钟执行', function () {

        })->everyMinute();


        Schedule::call('整点清空缓存', function (){

		})->everyDayAt('00:00');

    }
}
