#!/usr/bin/env php
<?php

require_once 'web/php/Context.php';
$context = new \Context(end($_SERVER["argv"]));
reset($_SERVER["argv"]);

$app_stack = $context->get('app_stack');

set_include_path(join(PATH_SEPARATOR, array_merge([get_include_path()],
    array_map(function ($item) { return "${item}/php"; }, array_reverse($app_stack)),
    ['html'])));

foreach($app_stack as $dir){
    $path = "${dir}/php/function.php";
    if(file_exists($path)){
        require_once $path;
    }
}

function search_class_file($name) {
  $file_name = str_replace('\\', '/', ltrim($name, '\\'));
  return stream_resolve_include_path($file_name.'.php');
}

spl_autoload_register(function ($name)
{
  return ($path = search_class_file($name)) ?
    include $path : false;
});

if (!($helper = $context->query('.helper'))) {
    $helper = preg_replace(
        '@^(?:[^/]*/)*([^/.]+)(?:\.[^.]*)*$@','\1',
        $context->query('.view'));
    $helper = ucwords($helper, '_-');
    $helper = str_replace(['-', '_'], ['\\', ''], $helper);
    if (!search_class_file("\\Helpers\\${helper}")) {
        $helper = 'Page';
    } 
}

$klass = "\\Helpers\\${helper}";
$helper = new $klass($context);
$helper->include($context->query('.view').".php");

exit(0);
