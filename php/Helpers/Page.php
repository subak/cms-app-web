<?php

namespace Helpers;

class Page
{
    use Traits\View, Traits\Content, Traits\Util;

    protected $context;
  
    public function __construct($context)
    {
        parse_str($context->get('query'), $query);

        $this->context = array_reduce($context->get('app_stack'), function($context, $app) {
            return $context->stack(\Context::fromPath("${app}/config/" . $context->get('context_auto')));
        }, $context);

        $this->context = $this->context
            ->stack($this->getContextFromFilename('')->dump())
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