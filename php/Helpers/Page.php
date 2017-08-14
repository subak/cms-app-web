<?php

namespace Helpers;

class Page
{
  use Traits\View, Traits\Content;

  public function __construct($context) {
    self::page_context()->register(yaml_parse_file(trim(`ls -1 */config/meta.yml | head -1`)), 'app');
    self::page_context()->register(yaml_parse_file('content/meta.yml'), 'content');
    self::page_context()->register($context, 'handler');
  }

  protected function router() {
    static $router = null;
    if (is_null($router)) {
      $router = new \Router(yaml_parse_file(trim(`ls -1 */config/routes.yml | head -1`)));
    }
    return $router;
  }

  static public function page_context() {
    static $context = null;
    if (is_null($context)) {
      $context = new \Context();
    }
    return $context;
  }

  public function context($key=null, $desc=true, $multiple=false) {
    if (is_null($key)) {
      return self::page_context();
    } else {
      return self::page_context()->get($key, $desc, $multiple);
    }
  }

  public function include($name="include/") {
    static $current = null;
    $prefix = '';
    $suffix = '';

    if ('/' === $name[0]) {
      $name = ltrim($name, '/');
    } else if (!is_null($current)) {
      $prefix = preg_replace('@[^/]+$@', '', $current);
    }

    if ('/' === substr($name, -1)) {
      $suffix = '_'.ltrim(basename($current), '_');
    }

    $rel_path = join('/', array_filter([$prefix, $name])).$suffix;

    if ($path = stream_resolve_include_path($rel_path)) {
      $current = $rel_path;
      include($path);
      return null;
    } else {
      throw new \Exception($rel_path);
    }
  }

  protected function is_dir($uri) {
    return substr($uri, -1) === '/';
  }
}