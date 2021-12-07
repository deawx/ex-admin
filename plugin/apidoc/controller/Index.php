<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2021-11-04
 * Time: 23:11
 */

namespace plugin\apidoc\controller;


use Eadmin\Controller;
use think\facade\View;

class Index extends Controller
{
    public function index(){
        $content = file_get_contents(__DIR__.'/../view/index.html');
        return View::display($content,[
            'data'=>plug()->apidoc->service->scan()
        ]);
    }
}
