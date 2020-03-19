<?php

namespace EasySwoole\EasySwoole;

use App\WebSocket\WebSocketEvents;
use App\WebSocket\WebSocketParser;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\FastCache\Cache;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\DbManager;
use EasySwoole\Pool\Manager;
use EasySwoole\Redis\Config\RedisConfig;
use EasySwoole\RedisPool\RedisPool;
use EasySwoole\Socket\Dispatcher;

class EasySwooleEvent implements Event
{
    public static function initialize()
    {
        // TODO: Implement initialize() method.
        date_default_timezone_set('Asia/Shanghai');

        // Redis协程连接池
        $redisConfig = new RedisConfig(Config::getInstance()->getConf('REDIS'));
        Manager::getInstance()->register(new RedisPool($redisConfig), 'Redis');

        $dbConfig = new \EasySwoole\ORM\Db\Config(Config::getInstance()->getConf('MYSQL'));
        //连接池配置
        $dbConfig->setGetObjectTimeout(3.0);        //设置获取连接池对象超时时间
        $dbConfig->setIntervalCheckTime(30 * 1000); //设置检测连接存活执行回收和创建的周期
        $dbConfig->setMaxIdleTime(15);                   //连接池对象最大闲置时间(秒)
        $dbConfig->setMaxObjectNum(20);                //设置最大连接池存在连接对象数量
        $dbConfig->setMinObjectNum(5);                 //设置最小连接池存在连接对象数量
        DbManager::getInstance()->addConnection(new Connection($dbConfig));
    }

    public static function mainServerCreate(EventRegister $register)
    {
        // *************** websocket控制器 ***************
        // 创建一个 Dispatcher 配置
        $socketConfig = new \EasySwoole\Socket\Config();
        // 设置 Dispatcher 为 WebSocket 模式
        $socketConfig->setType(\EasySwoole\Socket\Config::WEB_SOCKET);
        // 设置解析器对象
        $socketConfig->setParser(new WebSocketParser());
        // 创建 Dispatcher 对象 并注入 config 对象
        $dispatch = new Dispatcher($socketConfig);
        // 给 server 注册相关事件 在 WebSocket 模式下  on message 事件必须注册 并且交给 Dispatcher 对象处理
        $register->set(EventRegister::onMessage, function (\swoole_websocket_server $server, \swoole_websocket_frame $frame) use ($dispatch) {
            $dispatch->dispatch($server, $frame->data, $frame);
        });
        // 注册服务事件
        $register->add(EventRegister::onOpen, [WebSocketEvents::class, 'onOpen']);
        $register->add(EventRegister::onClose, [WebSocketEvents::class, 'onClose']);

        // *************** 缓存服务 ***************
        Cache::getInstance()->setTempDir(EASYSWOOLE_TEMP_DIR)->attachToServer(ServerManager::getInstance()->getSwooleServer());
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        // TODO: Implement onRequest() method.
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
    }
}