<?php

namespace Helpers\Traits;

trait Util
{
    public function contextFromFile($path) {
        return file_exists($path) ? `yaml2json ${path}` : '{}';
    }
    
    public function loadAppContext($context) {
        foreach ($context->get('app_stack') as $dir) {
            $context = $context->stack($this->contextFromFile("${dir}/config/meta.yml"));
        }
        return $context;
    }
}