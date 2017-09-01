<?php

namespace Helpers;

class Dev
{
    use Traits\View, Traits\Content, Traits\Util;

    public function __construct($context) {
        $context = new \ContextJq(json_encode($context));
        
        eval(\Psy\sh());
        
//        self::page_context()->register(yaml_parse_file(trim(`ls -1 */config/meta.yml | head -1`)), 'app');
//        self::page_context()->register(yaml_parse_file('content/meta.yml'), 'content');
//        self::page_context()->register($context, 'handler');
    }
    
}