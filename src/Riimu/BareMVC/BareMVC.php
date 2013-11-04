<?php

namespace Riimu\BareMVC;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class BareMVC
{
    private $router;

    public function __construct()
    {
        $this->router = new Router();
    }

    public function run()
    {
        $path = $this->router->getRequestRoute(true);

        $controller = new $path['class'];
        $controller->setRouter($this->router);
        $controller->setUp();
        $return = call_user_func(array($controller, $path['method']), $path['params']);
        $controller->tearDown($return);
    }
}
