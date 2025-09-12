<?php

ini_set('memory_limit', '-1');
if (phpversion('xdebug')) {
  ini_set('xdebug.max_nesting_level', 1024);
}

#### Find primary autoloader
$autoloaders = array(
  implode(DIRECTORY_SEPARATOR, array(dirname(__DIR__), 'vendor', 'autoload.php')),
  implode(DIRECTORY_SEPARATOR, array(dirname(dirname(dirname(dirname(__DIR__)))), 'vendor', 'autoload.php')),
);
foreach ($autoloaders as $autoloader) {
  if (file_exists($autoloader)) {
    $loader = require $autoloader;
    break;
  }
}

if (!isset($loader)) {
  die("Failed to find autoloader");
}

#### Extra - Register classes in "tests" directory
$loader->addPsr4('Civi\\SmartyUp\\', __DIR__);
