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

    public function find()
    {
        $params  = $this->request()->getRequestParam();
        $type    = isset($params['type']) ? $params['type'] : '';
        $keyword = isset($params['wd']) ? $params['wd'] : '';

        $userList = $groupList = [];
        switch ($type) {
            case 'user':
                $userList = UserModel::create()->field('id,nickname,avatar')->where('id', '%' . $keyword . '%')->all();
                break;
            case 'group':
                $groupList = GroupMemberModel::create()->field('id,groupname,avatar')->where('id', '%' . $keyword . '%')->all();
                break;
            default:
                // error
                // return $this->writeJson(10001, '参数错误');
        }

        return $this->render('find', [
            'type'       => $type,
            'wd'         => $keyword,
            'user_list'  => $userList,
            'group_list' => $groupList,
        ]);
    }
}