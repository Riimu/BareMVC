<?php

namespace Riimu\BareMVC;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class AbstractController implements Controller
{
    protected $defaultAction = 'index';
    protected $router;
    protected $view;

    public function getDefaultAction()
    {
        return $this->defaultAction;
    }

    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    public function setUp()
    {
        $this->view = new View();
        $this->view->setViewFormat(dirname($_SERVER['SCRIPT_FILENAME']) .
            DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . '/%s.php');
        $this->view->link = $this->router;
    }

    public function tearDown($return)
    {
        if ($return !== false) {
            $this->view->output();
        }
    }
}
