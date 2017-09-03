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

    public function include(string $path) {
        $context = $this->context;
        $this->context = $context->stack('{"view": "'.$path.'"}', -1);

        if ($real_path = stream_resolve_include_path($path)) {
            include($real_path);
            $this->context = $context;
        } else {
            throw new \Exception("include: ${path}");
        }
    }

  protected function is_dir($uri) {
    return substr($uri, -1) === '/';
  }
}