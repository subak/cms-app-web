<?php

namespace Helpers\Traits;

trait Util
{
    public function getContextFromFilename($filename)
    {
        return \Context::fromFilename($filename,
            $this->context->get('content_dir'),
            $this->context->get('context_auto'));
    }
    
    public function array2context($opts)
    {
        if (is_array($opts)) {
            $context = $this->context->stack(json_encode($opts), -1);
        } elseif($opts instanceof \Context) {
            $context = $opts;
        } else if (is_null($opts)) {
            $context = $this->context;
        } else {
            throw new \Exception('opts: '.print_r($opts,true));
        }

        return $context;
    }
}