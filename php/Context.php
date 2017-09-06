<?php

require_once __DIR__.'/function.php';

class Context
{
    private $_stack = [];
    
    public function __construct(?string ...$args)
    {
        $this->_stack = $args;
    }

    public function dump()
    {
        return $this->_stack;
    }

    public function query($query)
    {
        $json = join(' ', $this->_stack);
        return json_decode(shell("jq -s add | jq '${query}'", $json));
    }

    public function queryAll($query)
    {
        $res = array_map(function ($json) use ($query) {
            return json_decode(shell("jq '${query}'", $json));    
        }, $this->_stack);
        return array_filter($res);
    }
    
    public function get($key)
    {
        return $this->query(".${key}");
    }
    
    public function stack($json, ?int $offset=null)
    {
        $stack = is_array($json) ? $json : [$json];

        foreach ($stack as $json) {
            if(!json_decode($json)) {
                throw new \Exception("json: ${json}");
            }
        }

        if ($offset) {
            if ($offset === 0) {
                return new self(...array_merge($stack, $this->_stack));
            } else if($offset >= 1) {
                return new self(...array_merge(
                    array_slice($this->_stack, 0, $offset),
                    $stack,
                    array_slice($this->_stack, $offset)));
            } else {
                return new self(...array_merge(
                    array_slice($this->_stack, 0, $offset),
                    $stack,
                    array_slice($this->_stack, $offset)));
            }
        } else {
            return new self(...array_merge($this->_stack, $stack));
        }
    }
    
    public function unstack()
    {
        return new self(...array_slice($this->_stack, 0, -1));
    }
}