<?php
declare (strict_types=1);

namespace plugin\apidoc;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Eadmin\component\Component;
use Eadmin\plugin\PlugServiceProvider;
use Eadmin\form\Form;
use think\facade\Cache;
use think\facade\View;

/**
 * @property service\Service $service 服务
 */
class ApidocService extends PlugServiceProvider
{
    /**
     * 注册服务
     *
     * @return mixed
     */
    public function register()
    {
        $this->app->route->get('apidoc/css/tailwind', function () {
            $css = file_get_contents(__DIR__ . '/view/css/tailwind.min.css');
            return response($css)->header([
                'content-type'=>'text/css; charset=utf-8',
                'Cache-Control'=>'max-age=160',
            ]);
        });
        $this->app->route->get('apidoc/css/androidstudio', function () {
            $css = file_get_contents(__DIR__ . '/view/css/androidstudio.min.css');
            return response($css)->header([
                'content-type'=>'text/css; charset=utf-8',
                'Cache-Control'=>'max-age=160',
            ]);
        });
        $this->app->route->get('apidoc/css/atom-one-light', function () {
            $css = file_get_contents(__DIR__ . '/view/css/atom-one-light.css');
            return response($css)->header([
                'content-type'=>'text/css; charset=utf-8',
                'Cache-Control'=>'max-age=160',
            ]);
        });

        $this->app->route->get('apidoc/js/highlight', function () {
            $css = file_get_contents(__DIR__ . '/view/js/highlight.min.js');
            return response($css)->header([
                'content-type'=>'application/javascript; charset=utf-8',
                'Cache-Control'=>'max-age=160',
            ]);
        });
        $this->app->route->get('apidoc/js/vue', function () {
            $css = file_get_contents(__DIR__ . '/view/js/vue.js');
            return response($css)->header([
                'content-type'=>'application/javascript; charset=utf-8',
                'Cache-Control'=>'max-age=160',
            ]);
        });
        $this->app->route->get('apidoc/js/index', function () {
            $css = file_get_contents(__DIR__ . '/view/js/index.js');
            return response($css)->header([
                'content-type'=>'application/javascript; charset=utf-8',
                'Cache-Control'=>'max-age=160',
            ]);
        });
        $this->app->route->get('apidoc/js/axios', function () {
            $css = file_get_contents(__DIR__ . '/view/js/axios.min.js');
            return response($css)->header([
                'content-type'=>'application/javascript; charset=utf-8',
                'Cache-Control'=>'max-age=160',
            ]);
        });
        $this->app->route->get('apidoc/clear',function (){
            Cache::delete('plugin_apidoc');
        });
    }

    /**
     * 执行服务
     *
     * @return mixed
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * 菜单
     * @return array
     */
    public function menus(): array
    {
        return [];
    }

    /**
     * 设置
     * @return Component
     */
    public function setting()
    {
        $form = new Form(__DIR__ . '/config.php');
        $form->title('配置');
        return $form;
    }
}
