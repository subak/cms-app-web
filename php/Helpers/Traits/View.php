<?php

namespace Helpers\Traits;

trait View {
    public function tag($tag, $content = null, $option = [], $context = null)
    {
        $context = $this->array2context($context);
        $tags = array('br', 'img', 'hr', 'meta', 'input', 'embed', 'area', 'base', 'col', 'keygen', 'link', 'param', 'source');

        if (is_array($content)) {
            $attr = $this->attr($content);
            $content = "";
        } else {
            if (is_object($content) && is_callable($content)) {
                $attr = $this->attr($option);
                ob_start();
                $content($context);
                $content = ob_get_clean();
            } else {
                $attr = $this->attr($option);
            }
        }

        if (in_array($tag, $tags)) {
            return "<${tag}${attr}>";
        } else {
            return "<${tag}${attr}>${content}</${tag}>";
        }
    }

    protected function attr($array)
    {
        $attributes = array();
        foreach ($array as $name => $value) {
            if (is_array($value)) {
                $value = join(" ", $value);
            }
            $attributes[] = "${name}=\"${value}\"";
        }
        $attr = empty($attributes) ? "" : " " . join(" ", $attributes);
        return $attr;
    }

    public function linkTo($content, $uri, $attr = [], $context = null)
    {
        $context = $this->array2context($context);
        
        $attr['href'] = $this->urlFor($uri, $context);
        
        return $this->tag('a', $content, $attr, $context);
    }

    public function linkToIf($condition, $content, $path, $attr = [], $context = null)
    {
        $result = "";
        if ($condition) {
            $result = $this->linkTo($content, $path, $attr, $context);
        }
        return $result;
    }

    public function urlFor(string $uri, $context=null)
    {
        $context = $this->array2context($context);
        
        $url = $this->rel($uri, $context->get('uri'));
        
        if ($context->get('only_path') ?? true) {
            if ($context->get('local')) {
                if ($uri[-1] === "/") {
                    $url .= 'index.html';
                }
            }
            
            if ($query = $context->get('query')) {
                $url .= "?${query}";
            }
        } else {
            $url = $this->context->get('scheme') . '://' . $this->context->get('host') . $uri;
        }
        
        return $url;
    }

    public function rel($path, ?string $base_uri = null)
    {
        $level = substr_count($base_uri ?? $this->context->get('uri'), "/");
        $path = preg_replace('@^/@', './', $path);
        for ($i = 1; $i < $level; $i++) {
            $path = '../' . $path;
        }
        return $path;
    }
}