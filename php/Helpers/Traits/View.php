<?php

namespace Helpers\Traits;

trait View {
  public function tag($tag, $content=null, $option=array(), $args=array()) {
    $tags = array('br','img','hr','meta','input','embed','area','base','col','keygen','link','param','source');

    if (is_array($content)) {
      $attr = $this->attr($content);
      $content = "";
    } else if (is_object($content) && is_callable($content)) {
      $attr = $this->attr($option);
      ob_start();
      $content($args);
      $content = ob_get_clean();
    } else {
      $attr = $this->attr($option);
    }

    if (in_array($tag, $tags)) {
      return "<${tag}${attr}>";
    } else {
      return "<${tag}${attr}>${content}</${tag}>";
    }
  }

  protected function attr($array) {
    $attributes = array();
    foreach ($array as $name => $value) {
      if (is_array($value)) {
        $value = join(" ", $value);
      }
      $attributes[] = "${name}=\"${value}\"";
    }
    $attr = empty($attributes) ? "" : " ".join(" ", $attributes);
    return $attr;
  }

  public function link_to($content, $path, $option=array(), $args=array()) {
    $option['href'] = $this->rel($path);

    if ( $this->context('local') ) {
      $context = $this->router()->detect($path);
      if ($this->is_dir($context['uri'])) {
        $option['href'] .= 'index.html';
      }
    }

    if ($query = $this->context('query')) {
      $option['href'] .= "?${query}";
    }

    return $this->tag('a', $content, $option, $args);
  }

  public function link_to_if($condition, $content, $path, $option=array(), $args=array()) {
    $result = "";
    if ($condition) {
      $result = $this->link_to($content, $path, $option, $args);
    }
    return $result;
  }

  public function url_for($path) {
    return $this->context('scheme').'://'.$this->context('host').$path;
  }

  public function rel($path) {
    $level = substr_count($this->context('uri'), "/");
    $path = preg_replace('@^/@', './', $path);
    for ($i=1; $i < $level; $i++) {
      $path = '../'.$path;
    }
    return $path;
  }

  public function each($array, $closure, $tag=null, $args=[]) {
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

  public function list_directories_with($target, $closure) {
    $result = "";
    $index = 1;
    foreach ( scandir($target) as $name) {
      $dir = "${target}/${name}";
      if (is_dir($dir) and !in_array($name, ['.','..'])) {
        ob_start();
        echo $closure($dir, $index++);
        $result .= ob_get_clean();
      }
    }
    return $result;
  }



}