<?php

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 默认缓存驱动
    'default' => env('CACHE.DRIVER', 'file'),

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => app()->getRootPath().'runtime/cache',
            // 缓存前缀
            'prefix'     => env('CACHE.PREFIX',''),
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        // 更多的缓存连接
        'redis'   =>  [
            // 驱动方式
            'type'   => 'redis',
            // 服务器地址
            'host'       => env('REDIS.HOST','127.0.0.1'),
            //密码
            'password'=>env('REDIS.PASSWORD',''),
            //端口
            'port'=>env('REDIS.PORT',6379),
            //数据库
            'select'    =>env('REDIS.DATABASE',0),
            //超时
            'timeout'=>env('REDIS.TIMEOUT',60),
            // 缓存前缀
            'prefix'     => env('CACHE.PREFIX',''),
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
        ],
    ],
];
