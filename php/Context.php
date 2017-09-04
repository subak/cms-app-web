<?php

class Context
{
    private $_stack = [];
    
    public function __construct(string ...$args)
    {
        $this->_stack = $args;
    }

    public function query($query)
    {
        $json = join(' + ', $this->_stack);
        return json_decode(shell("jq '${json} | ${query}'", '{}'));
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
    
    public function stack(string $json, ?int $offset=null)
    {
        if(!json_decode($json)) {
            throw new \Exception("json: ${json}");
        }

        if ($offset) {
            if ($offset === 0) {
                return new self(...array_merge([$json], $this->_stack));
            } else if($offset >= 1) {
                return new self(...array_merge(
                    array_slice($this->_stack, 0, $offset),
                    [$json],
                    array_slice($this->stack, $offset)));
            } else {
                return new self(...array_merge(
                    array_slice($this->_stack, 0, $offset),
                    [$json],
                    array_slice($this->_stack, $offset)));
            }
        } else {
            return new self(...array_merge($this->_stack, [$json]));
        }
    }
    
    public function unstack()
    {
        return new self(...array_slice($this->_stack, 0, -1));
    }
}