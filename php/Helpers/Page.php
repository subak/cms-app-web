<?php

namespace Helpers;

class Page
{
  use Traits\View, Traits\Content, Traits\Util;

  protected $context;
  
    public function __construct($context)
    {
        parse_str($context->get('query'), $query);
        $this->context = $context
            ->stack(`yaml2json $(ls -1 */config/meta.yml | head -1)`)
            ->stack(`yaml2json content/meta.yml`)
            ->stack($query ? json_encode($query) : '{}');
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