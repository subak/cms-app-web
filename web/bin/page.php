#!/usr/bin/env php
<?php
require_once 'web/php/function.php';

$context = json_decode(end($_SERVER["argv"]), true);
reset($_SERVER["argv"]);

spl_autoload_register(function ($className)
{
  require_once 'web/php/class/' . $className . '.php';
  return true;
});

$page= new $context['class']($context);
$page->render();

exit(0);