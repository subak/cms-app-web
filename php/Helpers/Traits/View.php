<?php

namespace Helpers\Traits;

trait View {
    public function tag($tag, $content = null, $option = array(), $args = array())
    {
        $tags = array('br', 'img', 'hr', 'meta', 'input', 'embed', 'area', 'base', 'col', 'keygen', 'link', 'param', 'source');

        if (is_array($content)) {
            $attr = $this->attr($content);
            $content = "";
        } else {
            if (is_object($content) && is_callable($content)) {
                $attr = $this->attr($option);
                ob_start();
                $content($args);
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

    public function linkTo($content, $uri, $option = array(), $args = array(), ?string $base_uri = null)
    {
        $option['href'] = $this->rel($uri, $base_uri);

        if ($this->context->get('local')) {
            if ($uri[-1] === "/") {
                $option['href'] .= 'index.html';
            }
        }

        if ($query = $this->context->get('query')) {
            $option['href'] .= "?${query}";
        }

        return $this->tag('a', $content, $option, $args);
    }

    public function linkToIf($condition, $content, $path, $option = array(), $args = array())
    {
        $result = "";
        if ($condition) {
            $result = $this->linkTo($content, $path, $option, $args);
        }
        return $result;
    }

    public function urlFor($path)
    {
        return $this->context->get('scheme') . '://' . $this->context->get('host') . $path;
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

    public function each($array, $closure, $tag = null, $args = [])
    {
        if ($array) {
            ob_start();
            if ($tag) {
                echo "<$tag>";
            }
            foreach ($array as $key => $value) {
                $closure($key, $value, $args);
            }
            if ($tag) {
                echo "</$tag>";
            }
            return ob_get_clean();
        }
        return null;
    }

  public function listDirectoriesWith($target, $closure)
  {
      $result = "";
      $content_dir = $this->context->get('content_dir');

      foreach ( scandir("${content_dir}/${target}") as $name) {
          $dir = "${target}/${name}";
          $path = "${content_dir}/${dir}";
          if (is_dir($path) and !in_array($name, ['.','..'])) {
              $context = $this->context->stack($this->getContextFromFilename($path)->dump(), -1);
              if ($context->get('display') ?? true) {
                  ob_start();
                  echo $closure($dir, $context);
                  $result .= ob_get_clean();
              }
          }
      }
      return $result;
  }
    
    public function listDocumentsWith($target, $name, $closure, $sort_by="path", $desc=false)
    {
        $result = "";
        $content_dir = $this->context->get('content_dir');
        $desc_filter = $desc ? '| reverse' : '';
        foreach ( json_decode(`list_documents.sh ${content_dir}/${target} ${name} | jq 'sort_by(.${sort_by}) ${desc_filter}'`) as $item ) {
            $filename = basename(preg_replace("@^${content_dir}@", "", $item->path));
            $context = $this->context
                ->stack($this->getContextFromFilename($filename)->dump())
                ->stack(json_encode($item));
            if ($context->get('display') ?? true) {
                ob_start();
                echo $closure($filename, $context);
                $result .= ob_get_clean();
            }
        }
        return $result;
    }

}