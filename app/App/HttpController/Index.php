<?php

namespace App\HttpController;

use App\Model\FriendGroupModel;
use App\Model\UserModel;
use EasySwoole\Pool\Manager;
use EasySwoole\Validate\Validate;
use EasySwoole\VerifyCode\Conf;
use EasySwoole\VerifyCode\VerifyCode;

class Index extends Base
{
    /**
     * index
     *
     * @return bool|void
     * @throws \Throwable
     * @author King
     */
    public function index()
    {
        $token = $this->request()->getRequestParam('token');

        $redis = Manager::getInstance()->get('Redis')->getObj();
        $user  = $redis->get('User_token_' . $token);
        Manager::getInstance()->get('Redis')->recycleObj($redis);

        if (!$user) {
            return $this->response()->redirect('/login');
        }

        $user     = json_decode($user, true);
        $hostName = 'ws://43.226.36.49:9501';

        $this->render('index', [
            'server' => $hostName,
            'token'  => $token,
            'user'   => $user,
        ]);
    }

    /**
     * 用户登录
     *
     * @return bool|void
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @author King
     */
    public function login()
    {
        if ($this->request()->getMethod() != 'POST') {
            return $this->render('login');
        }

        $params = $this->request()->getRequestParam();

        $validate = new Validate();
        $validate->addColumn('username')->required('用户名必填');
        $validate->addColumn('password')->required('密码必填');
        if (!$validate->validate($params)) {
            return $this->writeJson(10001, $validate->getError()->__toString(), 'fail');
        }

        $user = UserModel::create()->where('username', $params['username'])->get();
        if (!$user) {
            return $this->writeJson(10001, '用户不存在');
        }

        if (!password_verify($params['password'], $user['password'])) {
            return $this->writeJson(10001, '密码输入错误');
        }

        // 生成token
        $token = md5(uniqid() . uniqid() . $user['id']);

        // 缓存token
        $redis = Manager::getInstance()->get('Redis')->getObj();
        $redis->set('User_token_' . $token, json_encode($user), 36000);
        //回收对象
        Manager::getInstance()->get('Redis')->recycleObj($redis);

        return $this->writeJson(200, '登录成功', ['token' => $token]);
    }

    /**
     * 用户注册
     *
     * @return bool|void
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @author King
     */
    public function register()
    {
        if ($this->request()->getMethod() != 'POST') {
            return $this->render('register', [
                'code_hash' => md5(uniqid() . uniqid() . time())
            ]);
        }

        $params = $this->request()->getRequestParam();

        $validate = new Validate();
        $validate->addColumn('username')->required('用户名必填');
        $validate->addColumn('password')->required('密码必填');
        $validate->addColumn('nickname')->required('昵称必填');
        $validate->addColumn('code')->required('验证码必填');
        if (!$validate->validate($params)) {
            return $this->writeJson(10001, $validate->getError()->__toString(), 'fail');
        }

        // 验证验证码
        $redis      = Manager::getInstance()->get('Redis')->getObj();
        $verifyCode = $redis->get('Code_' . $params['key']);
        Manager::getInstance()->get('Redis')->recycleObj($redis);
        if ($verifyCode != $params['code']) {
            return $this->writeJson(10001, '验证码错误', 'fail');
        }

        $user = UserModel::create()->where('username', $params['username'])->get();
        if ($user) {
            return $this->writeJson(10001, '用户名已存在', 'fail');
        }

        $userData = [
            'avatar'   => $params['avatar'],
            'nickname' => $params['nickname'],
            'username' => $params['username'],
            'password' => password_hash($params['password'], PASSWORD_DEFAULT),
            'sign'     => $params['sign'],
        ];
        $userId   = UserModel::create()->data($userData)->save();
        if (!$userId) {
            return $this->writeJson(10001, '注册失败', 'fail');
        }

        // 默认分组
        $friendGroupData = [
            'user_id'   => $userId,
            'groupname' => '默认分组'
        ];
        FriendGroupModel::create()->data($friendGroupData)->save();

        return $this->writeJson(200, '注册成功', 'fail');
    }

    /**
     * 验证码
     *
     * @throws \Throwable
     * @author King
     */
    public function getCode()
    {
        $key = $this->request()->getRequestParam('key');

        $num = mt_rand(0000, 9999);

        $redis = Manager::getInstance()->get('Redis')->getObj();
        $redis->set('Code_' . $key, $num, 1000);
        Manager::getInstance()->get('Redis')->recycleObj($redis);

        $config     = new Conf();
        $verifyCode = new VerifyCode($config);
        $this->response()->withHeader('Content-Type', 'image/png');
        $this->response()->write($verifyCode->DrawCode($num)->getImageByte());
    }

    /**
     * 退出登录
     *
     * @throws \Throwable
     * @author King
     */
    public function logout()
    {
        $token = $this->request()->getRequestParam('token');

        $redis = Manager::getInstance()->get('Redis')->getObj();
        $redis->del('User_token_' . $token);
        Manager::getInstance()->get('Redis')->recycleObj($redis);

        return $this->render('login');
    }
}