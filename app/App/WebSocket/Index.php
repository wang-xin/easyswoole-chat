<?php
/**
 * Created by PhpStorm.
 * User: King
 * Date: 2020/03/17
 * Time: 18:20
 */

namespace App\WebSocket;

use App\Model\FriendModel;
use App\Model\OfflineMessageModel;
use App\Model\SystemMessageModel;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\FastCache\Cache;
use EasySwoole\Pool\Manager;
use EasySwoole\Socket\AbstractInterface\Controller;

class Index extends Controller
{
    public function chatMessage()
    {
        $info = $this->caller()->getArgs()['data'];

        $token = $info['token'];
        $redis = Manager::getInstance()->get('Redis')->getObj();
        $user  = $redis->get('User_token_' . $token);
        Manager::getInstance()->get('Redis')->recycleObj($redis);

        $user = json_decode($user, true);
        if (!$user) {
            $data = [
                'type' => 'tokenExpired'
            ];
            $this->response()->setMessage(json_encode($data));

            return;
        }


    }

    /**
     * 添加好友
     *
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     * @author King
     */
    public function addFriend()
    {
        $info = $this->caller()->getArgs();

        $redis = Manager::getInstance()->get('Redis')->getObj();
        $user  = $redis->get('User_token_' . $info['token']);
        Manager::getInstance()->get('Redis')->recycleObj($redis);

        $user = json_decode($user, true);
        if (!$user) {
            $data = [
                'type' => 'tokenExpired'
            ];
            $this->response()->setMessage(json_encode($data));

            return;
        }

        $friendId = $info['to_user_id'];
        $isFriend = FriendModel::create()->where('user_id', $user['id'])->where('friend_id', $friendId)->get();
        if ($isFriend) {
            $data = [
                'type' => 'layer',
                'code' => 500,
                'msg'  => '对方已经是你的好友，不可重复添加'
            ];
            $this->response()->setMessage(json_encode($data));

            return;
        }

        if ($friendId == $user['id']) {
            $data = [
                'type' => 'layer',
                'code' => 500,
                'msg'  => '不能添加自己为好友'
            ];
            $this->response()->setMessage(json_encode($data));

            return;
        }

        $systemMessageData = [
            'user_id'  => $friendId,
            'from_id'  => $user['id'],
            'group_id' => $info['to_friend_group_id'],
            'remark'   => $info['remark'],
            'type'     => 0,
            'time'     => time(),
        ];
        SystemMessageModel::create()->data($systemMessageData)->save();

        $count = SystemMessageModel::create()->where('user_id', $friendId)->where('read', 0)->count();
        $data  = [
            'type'  => 'msgBox',
            'count' => $count
        ];

        $fd = Cache::getInstance()->get('uid_' . $friendId);
        if (!$fd) {
            $offlineMessageData = [
                'user_id' => $friendId,
                'data'    => json_encode($data)
            ];
            OfflineMessageModel::create()->data($offlineMessageData)->save();

            return;
        }

        $server = ServerManager::getInstance()->getSwooleServer();
        $server->push($fd, json_encode($data));
    }

    /**
     * 拒绝用户添加
     *
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     * @author King
     */
    public function refuseFriend()
    {
        $info = $this->caller()->getArgs();

        $systemMessageId = $info['id'];
        $systemMessage   = SystemMessageModel::create()->where('id', $systemMessageId)->get();
        if (!$systemMessage) {
            // TODO 错误处理
        }

        // 查询被拒绝的人有多少条未读信息
        $count = SystemMessageModel::create()->where('user_id', $systemMessage['from_id'])->where('read', 0)->count();
        $data  = [
            'type'  => 'msgBox',
            'count' => $count
        ];

        $fd = Cache::getInstance()->get('uid_' . $systemMessage['from_id']);
        if ($fd) {
            ServerManager::getInstance()->getSwooleServer()->push($fd, json_encode($data));
        }
    }
}