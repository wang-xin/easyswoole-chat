<?php
/**
 * Created by PhpStorm.
 * User: King
 * Date: 2020/03/17
 * Time: 15:31
 */

namespace App\HttpController;

use App\Utility\PlatesRender;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Template\Render;

class Base extends Controller
{
    public function index()
    {
        $this->actionNotFound('index');
    }

    public function render(string $template, array $vars = [])
    {
        $engine = new PlatesRender(EASYSWOOLE_ROOT . '/App/Views');

        $render = Render::getInstance();
        $render->getConfig()->setRender($engine);

        $content = $engine->render($template, $vars);
        $this->response()->write($content);
    }

    protected function writeJson($statusCode = 200, $msg = null, $result = null)
    {
        if (!$this->response()->isEndResponse()) {
            $data = [
                "code" => $statusCode,
                "msg"  => $msg,
                "data" => $result,
            ];
            $this->response()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus($statusCode);
            return true;
        }

        return false;
    }
}