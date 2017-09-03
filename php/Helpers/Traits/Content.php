<?php

namespace Helpers\Traits;

trait Content {
  protected function detect_document($file_name) {
    $info = pathinfo($file_name);
    if (!array_key_exists('extension', $info)) {
      $file_name = trim(`find -L ${file_name}.* -type f | egrep '(\.md|\.rst|\.adoc)' | head -n 1`);
      $info = pathinfo($file_name);
    }
    return "${info['dirname']}/${info['basename']}";
  }

  protected function rel_content_dir($path) {
    return preg_replace("@^content/@", '', dirname($path));
  }

  protected function rel_dir($path, $uri) {
    $rel_http_dir = str_repeat('../', substr_count($uri, '/') - 1)."./";
    $rel_content_dir = $this->rel_content_dir($path);
    $rel_dir = str_replace('/', '\/', "${rel_http_dir}${rel_content_dir}/");
    return $rel_dir;
  }

  public function doc_title($file_name) {
    $path = $this->detect_document($file_name);
    return trim(`head -1 ${path} | sed -r 's/^[#= ]*(.+)[#= ]*$/\\1/'`);
  }

  protected function rel_filter($rel_dir, $context) {
    $assets_ptn = implode('|', $context->get('resources'));
    return <<<EOF
 | sed -r 's/"([^/]+)\.(${assets_ptn})"/"${rel_dir}\\1.\\2"/'
EOF;
  }

  protected function doc_filter($ext, $context) {
    $body_method = "${ext}_body";
    $full_method = "${ext}_full";
    $excerpt_method = "${ext}_excerpted";
    
    if ($including_title = $context->get('including_title')) {
      return $this->$full_method();
    } else if ($excerpt = $context->get('excerpt')) {
      return $this->$excerpt_method($excerpt);
    } else {
      return $this->$body_method();
    }
  }

  protected function adoc_full() {

  }

  protected  function md_full() {

  }

  protected function adoc_body() {
    return "| pup --pre 'body > :not(#header)' | pup --pre 'body > :not(#footer)'";
  }

  protected function md_body() {
    return "| pup --pre 'body > :not(h1)'";
  }

  protected function adoc_excerpted($length) {
    $selectors = [];
    for ($i=1; $i<=$length; $i++) {
      $selectors[] = "#content > :nth-child(${i})";
    }
    $selector = join(',', $selectors);
    return "| pup --pre '${selector}'";
  }

  protected function md_excerpted($length) {
    $selectors = [];
    for ($i=1; $i<=$length; $i++) {
      $pos = $i + 1;
      $selectors[] = "body > :nth-child(${pos})";
    }
    $selector = join(',', $selectors);
    return "| pup --pre '${selector}'";
  }

  protected function adoc_option($context) {
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

  protected function md_option($context) {
    $option = $context->query('.pandoc.md');
    $from = $option->from;
    $to = $option->to;
    return "-f ${from} -t ${to}";
  }

  protected function doc_option($ext, $context) {
    $method = "${ext}_option";
    return $this->$method($context);
  }

  /**
   * @param string $path
   * @param string $dst_dir
   * @param string $strip_dir
   * @return string
   */
  protected function adoc_gen(string $path, string $dst_dir, string $strip_dir): string {
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
        $path = $this->detect_document($file_name);
        $info = pathinfo($path);
        $ext = $info['extension'];
        $context = $this->context;
        
        $content_dir = $context->get('content_dir');
        $tmp_dir = $context->get('tmp_dir');

        $dirs = explode('/', dirname(str_replace("${content_dir}/", '', $file_name)));
        $current = [];
        $context_paths = [];
        foreach ($dirs as $dir) {
            $current[] = $dir;
            $context_paths[] = "${content_dir}/".join('/', $current)."/${dir}.yml";
        }

        $doc_context = array_pop($context_paths);
        
        foreach ($context_paths as $context_path) {
            $context = $context->stack($this->contextFromFile($context_path), -1);
        }
        
        $context = $context
            ->stack($before_context, -1)
            ->stack($this->contextFromFile($doc_context), -1)
            ->stack($after_context, -1);

        $option = $this->doc_option($ext, $context);
        $filter = $this->doc_filter($ext, $context);
        $rel_dir = $this->rel_dir($path, $context->get('uri'));
        $filter .= $this->rel_filter($rel_dir, $context);


        switch ($ext) {
            case 'adoc':
                if (($requires = $context->get('asciidoctor.requires'))
                    && is_int(array_search('asciidoctor-diagram', $requires))) {
                    $path = $this->adoc_gen($path, $tmp_dir, $content_dir);
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
            $this->buildContentResource($path, $out_dir, $tmp_dir);
        }

        return $result;
    }

    protected function buildContentResource($path, $dst_dir, $strip_dir)
    {
        $context = $this->context->stack($this->contextFromFile(preg_replace('@\.[^.]+$@', '.yml', $path)));
        $resources = '\.' . implode('$|\.', $context->get('resources')) . '$';

        $src_dir = dirname($path);
        fputs(STDERR, `cpr.sh ${src_dir} ${dst_dir} ${strip_dir} '${resources}`);
    }
}
