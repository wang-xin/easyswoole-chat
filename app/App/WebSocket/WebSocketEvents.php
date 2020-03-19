<?php
/**
 * Created by PhpStorm.
 * User: King
 * Date: 2020/03/17
 * Time: 17:09
 */

namespace App\WebSocket;

use App\Model\FriendModel;
use App\Model\OfflineMessageModel;
use App\Model\SystemMessageModel;
use App\Model\UserModel;
use EasySwoole\FastCache\Cache;
use EasySwoole\Pool\Manager;

class WebSocketEvents
{
    public static function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {
        $token = $request->get['token'];

        $redis = Manager::getInstance()->get('Redis')->getObj();
        $user  = $redis->get('User_token_' . $token);
        Manager::getInstance()->get('Redis')->recycleObj($redis);

        $user = json_decode($user, true);
        if (!$user) {
            return $server->push($request->fd, json_encode(['type' => 'token expire']));
        }

        // 绑定 user_id 与 fd 关系
        Cache::getInstance()->set('uid_' . $user['id'], $request->fd, 3600);
        Cache::getInstance()->set('fd_' . $request->fd, $user['id'], 3600);

        // 更新个人在线状态
        UserModel::create()->where('id', $user['id'])->update(['status' => 'online']);

        $data = [
            'type'   => 'friendStatus',
            'uid'    => $user['id'],
            'status' => 'online'
        ];
        // 所有好友列表
        $friend = FriendModel::create()->where('user_id', $user['id'])->all();
        foreach ($friend as $item) {
            // 将上线状态通知在线的好友
            $fd = Cache::getInstance()->get('uid_' . $item['friend_id']);
            if ($fd) {
                $server->push($fd, json_encode($data));
            }
        }

        // 系统推送的系统消息数量
        $count = SystemMessageModel::create()->where('user_id', $user['id'])->where('read', 0)->count();
        $data  = [
            'type'  => 'msgBox',
            'count' => $count
        ];
        $server->push($request->fd, json_encode($data));

        // 我的离线消息
        $offlineMessage = OfflineMessageModel::create()->where('user_id', $user['id'])->where('status', 0)->all();
        if ($offlineMessage) {
            foreach ($offlineMessage as $item) {
                $server->push($request->fd, json_encode($item['data']));

                // 标记已读
                OfflineMessageModel::create()->where('id', $item['id'])->update(['status' => 1]);
            }
        }
    }

    public static function onClose(\swoole_websocket_server $server, int $fd, int $reactorId)
    {
        $uid = Cache::getInstance()->get('fd_' . $fd);

        $data = [
            'type'   => 'friendStatus',
            'uid'    => $uid,
            'status' => 'offline',
        ];
        // 所有好友列表
        $friend = FriendModel::create()->where('user_id', $uid)->all();
        foreach ($friend as $item) {
            // 将下线状态通知在线的好友
            $friendFd = Cache::getInstance()->get('uid_' . $item['friend_id']);
            if ($friendFd) {
                $server->push($friendFd, json_encode($data));
            }
        }

        // 解除 uid fd 映射绑定
        Cache::getInstance()->unset('uid_' . $uid);
        Cache::getInstance()->unset('fd_' . $fd);

        // 更新个人在线状态
        UserModel::create()->where('id', $uid)->update(['status' => 'offline']);
    }
}