<?php

namespace Helpers;

class Page
{
  use Traits\View, Traits\Content, Traits\Util;

  protected $context;
  protected $params;
  
    public function __construct($params)
    {
        $this->params = json_encode($params->query('.'));
        $context = new \Context(`yaml2json $(ls -1 */config/meta.yml | head -1)`);
        $context = $context->stack(`yaml2json content/meta.yml`);
        $this->context = $context->stack($this->params);
    }

    protected function stack($json)
    {
        return $this->context->unstack()
            ->stack($json)
            ->stack($this->params);
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