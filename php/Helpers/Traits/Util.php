<?php

namespace Helpers\Traits;

trait Util
{
    public function contextFromFile($path)
    {
        return file_exists($path) ? `yaml2json ${path}` : '{}';
    }

    public function loadAppContext($context)
    {
        foreach ($context->get('app_stack') as $dir) {
            $context = $context->stack($this->contextFromFile("${dir}/config/config.yml"));
        }
        return $context;
    }
    
    public function getContextFromFilename($filename)
    {
        $content_dir = $this->context->get('content_dir');
        $context = new \Context;

        $current = $content_dir;
        foreach ( explode('/', $filename) as $token ) {
            $current .= "/${token}";
            foreach (["${current}.yml", "${current}/meta.yml"] as $path) {
                $context = $context->stack($this->contextFromFile($path));
            }
        }

        return $context;
    }
}