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

  protected function adoc_gen($path) {
    $basename = basename($path);
    $content_dir = "public";
    $rel_content_dir = $this->rel_content_dir($path);
    $destination_dir = "${content_dir}/${rel_content_dir}";
    fputs(STDERR, `mkdir -pv ${destination_dir}`);
    $tmp_path = "${destination_dir}/${basename}";
    copy($path, $tmp_path);
    return $tmp_path;
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

    switch ($ext) {
      case 'adoc':
        if (($requires = $context->get('asciidoctor.requires'))
          && is_int(array_search('asciidoctor-diagram', $requires))) {
          $path = $this->adoc_gen($path);
        }
        $cmd = "asciidoctor ${option} -o - ${path} ${filter}";
        break;
      case 'md':
        $cmd = "pandoc ${option} ${path} ${filter}";
        break;
    }
    if ($out_dir=$context->get('out_dir')) {
      fputs(STDERR, $this->build_content_resource($path, $out_dir));
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

    switch ($ext) {
      case 'adoc':
        if (($requires = $context->get('asciidoctor.requires'))
          && is_int(array_search('asciidoctor-diagram', $requires))) {
          $path = $this->adoc_gen($path);
        }
        $cmd = "asciidoctor ${option} -o - ${path} ${filter}";
        break;
      case 'md':
        $cmd = "pandoc ${option} ${path} ${filter}";
        break;
    }

    if ($out_dir=$context->get('out_dir')) {
      fputs(STDERR, $this->build_content_resource($path, $out_dir));
    }

    ob_start();
    echo $closure(`${cmd}`, $takeover);
    $result = ob_get_clean();

    $context->unregister('doc');
    
    
    return $result;
  }
  
  protected function build_content_resource($file_name, $out_dir) {
    $content_dir = dirname($file_name);
    $rel_content_dir = preg_replace("@^[^/]+/@", '', $content_dir);
    $local_dir = "${out_dir}/${rel_content_dir}";
    $context = $this->doc_context($file_name);
    $resources = '\.'.implode('$|\.', $context->get('resources')).'$';
    $msg = '';
    $msg .= `mkdir -pv ${local_dir}`;
    $msg .= `find ${content_dir}/* -type f | egrep -E '${resources}' | xargs -I@ cp -v @ ${local_dir}`;
    return $msg;
  }
}
