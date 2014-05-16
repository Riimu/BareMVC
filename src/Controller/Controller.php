<?php

namespace Riimu\BareMVC\Controller;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface Controller
{
    public function getDefaultAction();
    public function setRouter(\Riimu\BareMVC\Router $router);
    public function setUp();
    public function tearDown($return);
}
