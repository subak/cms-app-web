<?php

namespace Helpers\Traits;

trait Content {
  protected function doc_context($file_name, $name='doc') {
    $context = new \Context(\Helpers\Page::page_context()->stack());
    $context->insert_before('handler', $this->doc_metadata($file_name), $name);
    return $context;
  }

  protected function doc_metadata($file_name) {
    $path = "${file_name}.yml";

    if (file_exists($path)) {
      return yaml_parse_file($path);
    } else {
      return [];
    }
  }

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

  protected function rel_filter($rel_dir, $assets) {
    $assets_ptn = implode('|', $assets);
    return <<<EOF
 | sed -r 's/"([^/]+)\.(${assets_ptn})"/"${rel_dir}\\1.\\2"/'
EOF;
  }

  protected function doc_filter($ext, $opts) {
    $body_method = "${ext}_body";
    $full_method = "${ext}_full";
    $excerpt_method = "${ext}_excerpted";

    if (array_key_exists('including_title', $opts) && $opts['including_title']) {
      return $this->$full_method();
    } else if (array_key_exists('excerpt', $opts) && !is_null($opts['excerpt'])) {
      return $this->$excerpt_method($opts['excerpt']);
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
    $meta = $context->get('pandoc.md');
    return "-f ${meta['from']} -t ${meta['to']}";
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
    $this->exec("cpr.sh ${src_dir} ${dst_dir} ${strip_dir}");
    return str_replace($strip_dir, $dst_dir, $path);
  }

  public function load_document($file_name, $uri, $params=[]) {
    $path = $this->detect_document($file_name);
    $info = pathinfo($path);
    $ext = $info['extension'];
    $context = $this->doc_context($file_name);
    $rel_dir = $this->rel_dir($path, $uri);
    $assets = $context->get('resources');

    $option = $this->doc_option($ext, $context);
    $filter = $this->doc_filter($ext, $params);
    $filter .= $this->rel_filter($rel_dir, $assets);

    $content_dir = $context->get('content_dir');
    $tmp_dir = $context->get('tmp_dir');
    
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
    if ($out_dir=$context->get('out_dir')) {
      $this->build_content_resource(dirname($path), $out_dir, $tmp_dir);
    }
    return `${cmd}`;
  }

  public function load_document_with($file_name, $uri, $closure, $takeover=[]) {
    $path = $this->detect_document($file_name);
    $info = pathinfo($path);
    $ext = $info['extension'];

    $context = $this->context();

    $context->insert_before('handler', $this->doc_metadata($file_name), 'doc');

    $rel_dir = $this->rel_dir($path, $uri);
    $assets = $context->get('resources');

    $option = $this->doc_option($ext, $context);
    $filter = $this->doc_filter($ext, []);
    $filter .= $this->rel_filter($rel_dir, $assets);

    $content_dir = $context->get('content_dir');
    $tmp_dir = $context->get('tmp_dir');
    
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
    echo $closure(`${cmd}`, $takeover);
    $result = ob_get_clean();

    if ($out_dir=$context->get('out_dir')) {
      $this->build_content_resource(dirname($path), $out_dir, $tmp_dir);
    }
    
    $context->unregister('doc');
    
    return $result;
  }
  
  protected function build_content_resource($src_dir, $dst_dir, $strip_dir) {
    $context = $this->doc_context($path);
    $resources = '\.'.implode('$|\.', $context->get('resources')).'$';

    return $this->exec("cpr.sh ${src_dir} ${dst_dir} ${strip_dir} '${resources}'");
  }
}
