<?php

class ContextJq
{
    private $stack = [];
    
    public function __construct(string ...$args)
    {
        $this->stack = $args;
    }

    public function query($query)
    {
        $json = join(' + ', $this->stack);
        return json_decode(shell("jq '${json} | ${query}'", '{}'));
    }
    
    public function queryAll($query)
    {
        $res = array_map(function ($json) use ($query) {
            return json_decode(shell("jq '${query}'", $json));    
        }, $this->stack);
        return array_filter($res);
    }
    
    public function get($key)
    {
        return $this->query(".${key}");
    }
    
    public function push(string $json)
    {
        return new self(...array_merge($this->stack, [$json]));
    }
    
    public function unshift(string $json)
    {
        return new self(...array_merge([$json], $this->stack));
    }
}