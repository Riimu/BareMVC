<?php

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../src');
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . DIRECTORY_SEPARATOR . 'controller');
spl_autoload_register();

$mvc = new \Riimu\BareMVC\BareMVC();
$mvc->run();
