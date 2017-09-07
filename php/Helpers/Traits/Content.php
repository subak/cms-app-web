<?php

namespace Helpers\Traits;

trait Content {
    protected function detectDocument($file_name)
    {
        $info = pathinfo($file_name);
        if (!array_key_exists('extension', $info)) {
            $path = trim(`find -L ${file_name}.* -type f | egrep '(\.md|\.rst|\.adoc)' | head -n 1`);
            if (!$path) {
                throw new \Exception("file_name: ${file_name}");
            }
            $info = pathinfo($path);
        }
        return "${info['dirname']}/${info['basename']}";
    }

    protected function relContentDir($path)
    {
        return preg_replace('@^' . $this->context->get('content_dir') . '/@', '', dirname($path));
    }

    protected function relDir($path, $uri)
    {
        $rel_http_dir = str_repeat('../', substr_count($uri, '/') - 1) . "./";
        $rel_content_dir = $this->relContentDir($path);
        $rel_dir = str_replace('/', '\/', "${rel_http_dir}${rel_content_dir}/");
        return $rel_dir;
    }

    protected function relFilter($rel_dir, $context)
    {
        $assets_ptn = implode('|', $context->get('resources'));
        return <<<EOF
 | sed -r 's/"([^/]+)\.(${assets_ptn})"/"${rel_dir}\\1.\\2"/'
EOF;
    }

    protected function docFilter($ext, $context)
    {
        $body_method = "${ext}Body";
        $full_method = "${ext}Full";
        $excerpt_method = "${ext}Excerpted";

        if ($including_title = $context->get('including_title')) {
            return $this->$full_method();
        } else {
            if ($excerpt = $context->get('excerpt')) {
                return $this->$excerpt_method($excerpt);
            } else {
                return $this->$body_method();
            }
        }
    }

    protected function adocFull()
    {

    }

    protected function mdFull()
    {

    }

    protected function adocBody()
    {
        return "| pup --pre 'body > :not(#header)' | pup --pre 'body > :not(#footer)'";
    }

    protected function mdBody()
    {
        return "| pup --pre 'body > :not(h1)'";
    }

    protected function adocExcerpted($length)
    {
        $selectors = [];
        for ($i = 1; $i <= $length; $i++) {
            $selectors[] = "#content > :nth-child(${i})";
        }
        $selector = join(',', $selectors);
        return "| pup --pre '${selector}'";
    }

    protected function mdExcerpted($length)
    {
        $selectors = [];
        for ($i = 1; $i <= $length; $i++) {
            $pos = $i + 1;
            $selectors[] = "body > :nth-child(${pos})";
        }
        $selector = join(',', $selectors);
        return "| pup --pre '${selector}'";
    }

    protected function adocOption($context)
    {
        $attributes = $context->get('asciidoctor.attributes');
        $requires = $context->get('asciidoctor.requires');

        $options = [];

        if (!is_null($attributes)) {
            foreach ($attributes as $key => $val) {
                $options[] = "-a ${key}=${val}";
            }
        }

        if (!is_null($requires)) {
            foreach ($requires as $require) {
                $options[] = "-r ${require}";
            }
        }

        return join(' ', $options);
    }

    protected function mdOption($context)
    {
        $option = $context->query('.pandoc.md');
        $from = $option->from;
        $to = $option->to;
        return "-f ${from} -t ${to}";
    }

    protected function docOption($ext, $context)
    {
        $method = "${ext}Option";
        return $this->$method($context);
    }

    /**
     * @param string $path
     * @param string $dst_dir
     * @param string $strip_dir
     * @return string
     */
    protected function adocGen(string $path, string $dst_dir, string $strip_dir): string
    {
        $src_dir = dirname($path);
        fputs(STDERR, `cpr.sh ${src_dir} ${dst_dir} ${strip_dir}`);
        return str_replace($strip_dir, $dst_dir, $path);
    }

    /**
     * @param string $file_name
     * @param string $before_context
     * @param string $after_context
     * @return string
     */
    public function loadDocument(string $file_name, string $before_context='{}', string $after_context='{}'): string
    {
        return $this->loadDocumentWith($file_name, function ($doc, $context) { echo $doc; }, $before_context, $after_context);
    }

    /**
     * @param string $file_name
     * @param callable $closure
     * @param string $before_context
     * @param string $after_context
     * @return string
     */
    public function loadDocumentWith(string $file_name, callable $closure, string $before_context = '{}', string $after_context = '{}'): string
    {
        $content_dir = $this->context->get('content_dir');
        $tmp_dir = $this->context->get('tmp_dir');
        $strip_dir = $content_dir;

        $path = $this->detectDocument("${content_dir}/${file_name}");
        $info = pathinfo($path);
        $ext = $info['extension'];

        $context = $this->context
            ->stack($this->getContextFromFilename($file_name)
                ->unstack()->unstack()->dump(), -1)
            ->stack($before_context, -1)
            ->stack(\Context::fromPath("${content_dir}/${file_name}.yml"), -1)
            ->stack($after_context, -1);

        $option = $this->docOption($ext, $context);
        $filter = $this->docFilter($ext, $context);
        $rel_dir = $this->relDir($path, $context->get('uri'));
        $filter .= $this->relFilter($rel_dir, $context);

        switch ($ext) {
            case 'adoc':
                if (($requires = $context->get('asciidoctor.requires'))
                    && is_int(array_search('asciidoctor-diagram', $requires))) {
                    $strip_dir = $tmp_dir;
                    $path = $this->adocGen($path, $strip_dir, $content_dir);
                }
                $cmd = "asciidoctor ${option} -o - ${path} ${filter}";
                break;
            case 'md':
                $cmd = "pandoc ${option} ${path} ${filter}";
                break;
        }

        ob_start();
        echo $closure(`${cmd}`, $context);
        $result = ob_get_clean();

        if ($out_dir = $context->get('out_dir')) {
            $this->buildContentResource($path, $out_dir, $strip_dir);
        }

        return $result;
    }

    protected function buildContentResource($path, $dst_dir, $strip_dir)
    {
        $context = $this->context->stack(\Context::fromPath(preg_replace('@\.[^.]+$@', '.yml', $path)));
        $resources = '\.' . implode('$|\.', $context->get('resources')) . '$';

        $src_dir = dirname($path);
        fputs(STDERR, `cpr.sh ${src_dir} ${dst_dir} ${strip_dir} '${resources}'`);
    }
}
