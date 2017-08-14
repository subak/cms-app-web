<?php

class Context
{
  private $stack;

  public function __construct($stack=[]) {
    $this->stack = $stack;
  }

  public function get($key, $desc=true, $multiple=false) {
    $stack = $desc ? array_reverse($this->stack) : $this->stack;
    if ($multiple) {
      $values = [];
      foreach ( $stack as $obj) {
        $value = $this->filter($key, $obj['context']);
        if (!is_null($value)) {
          $values[] = $value;
        }
      }
      return $values;
    } else {
      $value = null;
      foreach ( $stack as $obj) {
        $value = $this->filter($key, $obj['context']);
        if (!is_null($value)) {
          break;
        }
      }
      return $value;
    }
  }

  public function get_by_name($key, $name) {
    $index = $this->find_stack($name);
    return is_null($index) ? null : $this->filter($key, $this->stack[$index]['context']);
  }

  protected function key2path($key) {
    return preg_replace('@\.([^.]+)@', "['\\1']", '.'.ltrim($key, '.'));
  }

  public function set($key, $value, $name=null) {
    $index = is_null($name) ? (count($this->stack) - 1) : $this->find_stack($name);
    $path = $this->key2path($key);
    @eval('@$this->stack[$index]["context"]'.$path.'=$value;');
  }

  private function find_stack($name) {
    $index = array_search($name, array_column($this->stack, 'name'));
    if (array_key_exists($index, $this->stack)) {
      return $index;
    } else {
      return null;
    }
  }

  private function filter($key, $context) {
    $value = null;
    $path = $this->key2path($key);
    @eval('@$value=@$context'.$path.';');
    return $value;
  }

  public function stack($stack=null) {
    if (is_null($stack)) {
      return $this->stack;
    } else {
      $this->stack = $stack;
    }
  }

  public function register($context, $name, $prepend=false) {
    $func = $prepend ? 'array_unshift' : 'array_push';
    $func($this->stack, ['context' => $context, 'name' => $name]);
  }

  public function unregister($name) {
    if ($index = $this->find_stack($name)) {
      $context = $this->stack[$index]['context'];
      array_splice($this->stack, $index, $length);
      return $context;
    } else {
      return null;
    }
  }

  public function pop() {
    array_pop($this->stack);
  }

  public function insert_before($ref, $context, $name) {
    $index = $this->find_stack($ref);
    if (is_int($index)) {
      $this->splice($index, $context, $name);
    } else {
      throw new Exception();
    }
  }

  public function insert_after($ref, $context, $name) {
    $index = $this->find_stack($ref);
    if (is_int($index)) {
      $this->splice($index + 1, $context, $name);
    } else {
      throw new Exception();
    }
  }

  private function splice($index, $context, $name) {
    return array_splice($this->stack, $index, 0,
      [['context' => $context, 'name' => $name]]);
  }

  public function replace($context, $name) {
    $index = $this->find_stack($name);
    if (is_int($index)) {
      $old = $this->stack[$index]['context'];
      $this->stack[$index]['context'] = $context;
      return $old;
    } else {
      throw new Exception();
    }
  }

}