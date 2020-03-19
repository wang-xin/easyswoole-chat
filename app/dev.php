<?php
return [
    'SERVER_NAME' => "EasySwoole",
    'MAIN_SERVER' => [
        'LISTEN_ADDRESS' => '0.0.0.0',
        'PORT'           => 9501,
        'SERVER_TYPE'    => EASYSWOOLE_WEB_SOCKET_SERVER, //可选为 EASYSWOOLE_SERVER  EASYSWOOLE_WEB_SERVER EASYSWOOLE_WEB_SOCKET_SERVER,EASYSWOOLE_REDIS_SERVER
        'SOCK_TYPE'      => SWOOLE_TCP,
        'RUN_MODEL'      => SWOOLE_PROCESS,
        'SETTING'        => [
            'worker_num'    => 8,
            'reload_async'  => true,
            'max_wait_time' => 3
        ],
        'TASK'           => [
            'workerNum'     => 4,
            'maxRunningNum' => 128,
            'timeout'       => 15
        ]
    ],
    'TEMP_DIR'    => null,
    'LOG_DIR'     => null,
    'REDIS'       => [
        'host' => '43.226.36.49',
        'port' => 63790,
        'auth' => '',
        'db'   => 0,
    ],
    'MYSQL'       => [
        'host'     => '43.226.36.49',
        'port'     => 33060,
        'user'     => 'root',
        'password' => '123456',
        'database' => 'chat',
        'timeout'  => 5,
        'charset'  => 'utf8mb4',
    ],
];
