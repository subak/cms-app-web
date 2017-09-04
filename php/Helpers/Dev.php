<?php

namespace Helpers;

class Dev
{
    use Traits\View, Traits\Content, Traits\Util;

    public function __construct($context) {
        //eval(\Psy\sh());
    }
}