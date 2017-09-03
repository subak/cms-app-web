<?php

namespace Helpers;

class Page
{
    use Traits\View, Traits\Content, Traits\Util;

    protected $context;
  
    public function __construct($context)
    {
        parse_str($context->get('query'), $query);
        $context = $this->loadAppContext($context);
        $content_dir = $context->get('content_dir');
        $base_dir = dirname($content_dir);
        $base_dir = $base_dir === '.' ? $content_dir : $base_dir;
        $this->context = $context
            ->stack(`yaml2json ${content_dir}/${base_dir}.yml`)
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