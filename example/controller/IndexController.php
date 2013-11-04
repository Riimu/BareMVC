<?php

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class IndexController extends \Riimu\BareMVC\AbstractController
{
    public function setUp()
    {
        parent::setUp();
        $this->view->setView('template');
    }

    public function indexAction()
    {
        $this->view->setSubView('content', 'index');
        $this->view->hello = 'world';
    }

    public function otherAction()
    {
        $this->view->setSubView('content', 'other');
    }
}
