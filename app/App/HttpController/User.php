<?php
/**
 * Created by PhpStorm.
 * User: King
 * Date: 2020/03/18
 * Time: 17:32
 */

namespace App\HttpController;

use App\Model\FriendGroupModel;
use App\Model\FriendModel;
use App\Model\GroupMemberModel;
use App\Model\GroupModel;
use App\Model\SystemMessageModel;
use App\Model\UserModel;
use EasySwoole\Pool\Manager;

class User extends Base
{
    public function userInfo()
    {
        $token = $this->request()->getRequestParam('token');

        $redis = Manager::getInstance()->get('Redis')->getObj();
        $user  = $redis->get('User_token_' . $token);
        Manager::getInstance()->get('Redis')->recycleObj($redis);

        if (!$user) {
            return $this->writeJson(10001, '获取用户信息失败');
        }

        $user = json_decode($user, true);

        // 我的群
        $groups = GroupMemberModel::create()->alias('gm')->join('`group` as g', 'gm.group_id = g.id')
            ->field('g.id,g.groupname,g.avatar')
            ->where('gm.user_id', $user['id'])
            ->all();
        if ($groups) {
            foreach ($groups as $key => $group) {
                $groups[$key]['groupname'] = $group['groupname'] . '(' . $group['id'] . ')';
            }
        }

        // 我的好友分组
        $friendGroups = FriendGroupModel::create()->field('id,groupname')->where('user_id', $user['id'])->all();
        if ($friendGroups) {
            foreach ($friendGroups as $k => $v) {
                // 小组里面的好友
                $friendGroups[$k]['list'] = FriendModel::create()->alias('f')
                    ->field('u.nickname as username,u.id,u.avatar,u.sign,u.status')
                    ->join('user as u', 'u.id = f.user_id')
                    ->where('f.user_id', $user['id'])
                    ->where('f.friend_group_id', $v['id'])
                    ->order('status', 'desc')
                    ->all();
            }
        }

        $data = [
            'mine'   => [
                'username' => $user['nickname'] . '(' . $user['id'] . ')',
                'id'       => $user['id'],
                'status'   => $user['status'],
                'sign'     => $user['sign'],
                'avatar'   => $user['avatar']
            ],
            "friend" => $friendGroups,
            "group"  => $groups
        ];
        return $this->writeJson(0, 'success', $data);
    }

    /**
     * 查找好友，查找群
     *
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     * @author King
     */
    public function find()
    {
        $params  = $this->request()->getRequestParam();
        $type    = isset($params['type']) ? $params['type'] : '';
        $keyword = isset($params['wd']) ? $params['wd'] : '';

        $userList = $groupList = [];
        switch ($type) {
            case 'user':
                $userList = UserModel::create()->field('id,nickname,avatar')->where('id', '%' . $keyword . '%', 'like')->all();
                break;
            case 'group':
                $groupList = GroupModel::create()->field('id,groupname,avatar')->where('id', '%' . $keyword . '%', 'like')->all();
                break;
        }

        return $this->render('find', [
            'type'       => $type,
            'wd'         => $keyword,
            'user_list'  => $userList,
            'group_list' => $groupList,
        ]);
    }

    /**
     * 好友添加提醒
     *
     * @return bool|void
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @author King
     */
    public function messageBox()
    {
        $params = $this->request()->getRequestParam();
        $token  = $params['token'];

        $redis = Manager::getInstance()->get('Redis')->getObj();
        $user  = $redis->get('User_token_' . $token);
        Manager::getInstance()->get('Redis')->recycleObj($redis);

        $user = json_decode($user, true);
        if (!$user) {
            return $this->writeJson(10001, '获取用户信息失败');
        }

        // 系统消息标记已读
        SystemMessageModel::create()->where('user_id', $user['id'])->update(['read' => 1]);

        $list = SystemMessageModel::create()->alias('sm')
            ->join('user as u', 'sm.user_id = u.id')
            ->field('sm.id,u.id as uid,u.avatar,u.nickname,sm.remark,sm.time,sm.type,sm.group_id,sm.status')
            ->where('user_id', $user['id'])
            ->order('sm.id', 'desc')
            ->limit(50)
            ->all();
        if ($list) {
            foreach ($list as $key => $item) {
                $list[$key]['time'] = $this->_time_tran($item['time']);
            }
        }

        return $this->render('message_box', [
            'list' => $list
        ]);
    }

    private function _time_tran($the_time)
    {
        $now_time = time();
        $dur      = $now_time - $the_time;
        if ($dur <= 0) {
            $mas = '刚刚';
        } else {
            if ($dur < 60) {
                $mas = $dur . '秒前';
            } else {
                if ($dur < 3600) {
                    $mas = floor($dur / 60) . '分钟前';
                } else {
                    if ($dur < 86400) {
                        $mas = floor($dur / 3600) . '小时前';
                    } else {
                        if ($dur < 259200) { //3天内
                            $mas = floor($dur / 86400) . '天前';
                        } else {
                            $mas = date("Y-m-d H:i:s", $the_time);
                        }
                    }
                }
            }
        }
        return $mas;
    }

    /**
     * 同意添加好友
     *
     * @return bool
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @author King
     */
    public function addFriend()
    {
        $params          = $this->request()->getRequestParam();
        $token           = $params['token'];
        $systemMessageId = $params['id'];

        $redis = Manager::getInstance()->get('Redis')->getObj();
        $user  = $redis->get('User_token_' . $token);
        Manager::getInstance()->get('Redis')->recycleObj($redis);

        $user = json_decode($user, true);
        if (!$user) {
            return $this->writeJson(10001, '获取用户信息失败');
        }

        $systemMessage = SystemMessageModel::create()->where('id', $systemMessageId)->get();
        $isFriend      = FriendModel::create()->where('user_id', $user['id'])->where('friend_id', $systemMessage['user_id'])->get();
        if ($isFriend) {
            return $this->writeJson(10001, '已经是好友了');
        }

        // 互为好友
        $friendData = [
            [
                'user_id'         => $systemMessage['user_id'],
                'friend_id'       => $systemMessage['from_id'],
                'friend_group_id' => $params['group_id'],
            ],
            [
                'user_id'         => $systemMessage['from_id'],
                'friend_id'       => $systemMessage['user_id'],
                'friend_group_id' => $systemMessage['group_id'],
            ]
        ];
        FriendModel::create()->saveAll($friendData);

        // 系统消息标记已同意
        SystemMessageModel::create()->where('id', $systemMessageId)->update(['status' => 1]);

        $systemMessageData = [
            'user_id' => $systemMessage['from_id'],
            'from_id' => $systemMessage['user_id'],
            'type'    => 1,
            'status'  => 1,
            'time'    => time()
        ];
        SystemMessageModel::create()->data($systemMessageData)->save();

        $friendInfo = UserModel::create()->where('id', $systemMessage['from_id'])->get();
        $data       = [
            'type'     => 'friend',
            'avatar'   => $friendInfo['avatar'],
            'username' => $friendInfo['username'],
            'groupid'  => $params['group_id'],
            'id'       => $user['id'],
            'sign'     => $friendInfo['sign'],
        ];

        return $this->writeJson(200, '添加成功', $data);
    }


}