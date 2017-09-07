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
}