<?php

namespace App\HttpController;

use App\Model\UserModel;
use EasySwoole\Pool\Manager;
use EasySwoole\Validate\Validate;

class Index extends Base
{
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

    public function register()
    {

    }

    public function captcha()
    {

    }

    public function logout()
    {
        $token = $this->request()->getRequestParam('token');

        $redis = Manager::getInstance()->get('Redis')->getObj();
        $redis->del('User_token_' . $token);
        Manager::getInstance()->get('Redis')->recycleObj($redis);

        return $this->render('login');
    }
}