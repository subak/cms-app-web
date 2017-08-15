#!/usr/bin/env php
<?php

set_include_path(join(PATH_SEPARATOR, [
  get_include_path(), 'app/php', 'web/php', 'html']));

require_once 'web/php/function.php';
if ($path = stream_resolve_include_path('app/php/function.php')) {
  require_once $path;
}

$context = json_decode(end($_SERVER["argv"]), true);
reset($_SERVER["argv"]);

function search_class_file($name) {
  $file_name = str_replace('\\', '/', ltrim($name, '\\'));
  return stream_resolve_include_path($file_name.'.php');
}

spl_autoload_register(function ($name)
{
  return ($path = search_class_file($name)) ?
    include $path : false;
});

if (array_key_exists('helper', $context)) {
  $helper = $context['helper'];
} else {
  $helper = preg_replace(
    '@^(?:[^/]*/)*([^/.]+)(?:\.[^.]*)*$@','\1',
    $context['view']);
  $helper = ucwords($helper, '_-');
  $helper = str_replace(['-', '_'], ['\\', ''], $helper);
  if (!search_class_file("\\Helpers\\${helper}")) {
    $helper = 'Page';
  }
}

$klass = "\\Helpers\\${helper}";
$helper = new $klass($context);
$helper->include(preg_replace('@\.([^.]+)$@', '.\1.php', $context['view']));

exit(0);
