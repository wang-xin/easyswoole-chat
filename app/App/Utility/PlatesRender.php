<?php
/**
 * Created by PhpStorm.
 * User: King
 * Date: 2020/03/17
 * Time: 15:46
 */

namespace App\Utility;

use EasySwoole\Template\RenderInterface;
use League\Plates\Engine;

class PlatesRender implements RenderInterface
{
    private $views;

    private $engine;

    public function __construct($views)
    {
        $this->views = $views;

        $this->engine = new Engine($this->views);
    }

    /**
     * 渲染模板
     *
     * @param string $template
     * @param array  $data
     * @param array  $options
     *
     * @return string|null
     * @author King
     */
    public function render(string $template, array $data = [], array $options = []): ?string
    {
        // 支持模板引擎以闭包形式设置(多进程渲染时请注意进程隔离问题)
        if (isset($options['call']) && is_callable($options['call'])) {
            $options['call']($this->engine);
        }

        // 渲染并返回内容
        return $this->engine->render($template, $data);
    }

    /**
     * 渲染完成
     *
     * @param string|null $result
     * @param string      $template
     * @param array       $data
     * @param array       $options
     *
     * @author King
     */
    public function afterRender(?string $result, string $template, array $data = [], array $options = [])
    {
        // 重新创建实例
        $this->engine = new Engine($this->views);
    }

    /**
     * 异常时的操作
     *
     * @param \Throwable $throwable
     *
     * @return string
     * @author King
     */
    public function onException(\Throwable $throwable): string
    {
        return 'Error: ' . $throwable->getMessage();
    }

}