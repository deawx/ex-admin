<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'default'     => env('QUEUE.DRIVER', 'sync'),
    'connections' => [
        'sync'     => [
            'type' => 'sync',
        ],
        'database' => [
            'type'       => 'database',
            'queue'      => 'default',
            'table'      => env('QUEUE.TABLE', 'system_jobs'),
            'connection' => null,
        ],
        'redis'    => [
            'type'       => 'redis',
            'queue'      => 'default',
            'host'       => env('REDIS.HOST', '127.0.0.1'),
            'port'       => env('REDIS.PORT', 6379),
            'password'   => env('REDIS.PASSWORD', ''),
            'select'     => env('REDIS.DATABASE', 0),
            'timeout'    => env('REDIS.TIMEOUT', 60),
            'persistent' => env('REDIS.PERSISTENT', false),
        ],
    ],
    'failed'      => [
        'type'  => 'none',
        'table' => 'failed_jobs',
    ],
];
