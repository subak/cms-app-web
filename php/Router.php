<?php

class Router
{
  private $routes;

  public function __construct($routes)
  {
    $this->routes = $routes;
  }

  public function detect($path) {
    $context = null;

    foreach ($this->routes as $route) {
      $ptn = "@${route['match']}@";

      if (preg_match($ptn, $path, $matches)) {
        foreach ( $matches as $key => $val ) {
          if (is_string($key)) {
            $route[$key] = $val;
          }
        }

        $context = $route;

        if (array_key_exists('uri', $context)) {
          $context['uri'] = $path;
        }

        break;
      }
    }

    return $context;
  }
}
