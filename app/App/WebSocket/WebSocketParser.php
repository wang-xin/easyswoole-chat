<?php
/**
 * Created by PhpStorm.
 * User: King
 * Date: 2020/03/17
 * Time: 18:04
 */

namespace App\WebSocket;

use EasySwoole\Socket\AbstractInterface\ParserInterface;
use EasySwoole\Socket\Bean\Caller;
use EasySwoole\Socket\Bean\Response;

class WebSocketParser implements ParserInterface
{
    public function decode($raw, $client): ?Caller
    {
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            // 数据格式错误
        }

        $eventMap = [
            'index' => Index::class
        ];

        $caller = new Caller();
        $caller->setControllerClass($eventMap[$data['class'] ?? Index::class]);
        $caller->setAction($data['type'] ?? 'index');
        $caller->setArgs($data);

        return $caller;
    }

    public function encode(Response $response, $client): ?string
    {
        return $response->getMessage();
    }
}