# Bare bones MVC to provide controller/view structure #

This is not fully featured MVC. This probably does not provide all the features
you are looking for. In fact, this framework doesn't even understand the Model
part of MVC. The entire purpose of this MVC is to provide as simple as possible
framework for simplistic websites in order to have separate controllers and
views and a sane way to link them on web pages using mod_rewrite.

If you need anything more, I would recommend something like Symfony 2.

## Usage ##

In order to properly use this MVC, you should probably set up your .htaccess
file to redirect all calls to the index file. For example:

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)               index.php?path=$1 [L]
```

If you want to follow the default ideology of this MVC, you should have folders
like 'controller' and 'view' for controllers and views. Then, create a bootstrap
in index.php that should probably look something like this (assuming usage of
composer):

```php
<?php

require 'vendor/autoload.php';

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . DIRECTORY_SEPARATOR . 'controller');
spl_autoload_register();

$mvc = new \Riimu\BareMVC\BareMVC();
$mvc->run();
```

For example of how the controllers and views are meant to be used, see the
files in the example folder, which provides a working example.

## Credits ##

This library is copyright 2013 to Riikka Kalliomäki