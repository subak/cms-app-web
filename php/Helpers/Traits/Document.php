<?php

namespace Helpers\Traits;

trait Document {
    public function withoutTitle($doc, $ext)
    {
        switch($ext){
            case 'md':
                $res = shell("pup --pre 'body > :not(h1)'", $doc);
                break;
            case 'adoc':
                $res = shell("pup --pre 'body > :not(#header)' | pup --pre 'body > :not(#footer)'", $doc);
                break;
            default:
                throw new \Exception("ext: ${ext}");
        }
        
        return $res;
    }
    
    public function excerpt($doc, $num, $ext)
    {
        switch ($ext) {
            case 'md':
                $selectors = [];
                for ($i = 1; $i <= $num; $i++) {
                    $selectors[] = "body > :nth-child(${i})";
                }
                $selector = join(',', $selectors);
                $res = shell("pup --pre '${selector}'", $doc);
                break;
            case 'adoc':
                $selectors = [];
                for ($i = 1; $i <= $num; $i++) {
                    $selectors[] = "#content > :nth-child(${i})";
                }
                $selector = join(',', $selectors);
                $res = shell("pup --pre '${selector}'", $doc);
                break;
            default:
                throw new \Exception("ext: ${ext}");
        }
        
        return $res;
    }
    
    protected function convert($path, $ext, $context)
    {
        $src_dir = dirname($path);
        $dst_dir = $context->get('tmp_dir');
        $strip_dir = $context->get('content_dir');
        fputs(STDERR, `cpr.sh ${src_dir} ${dst_dir} ${strip_dir}`);
        $path = preg_replace("@^${strip_dir}@", $dst_dir, $path);
        
        switch ($ext) {
            case 'md':
                $opt = $this->mdOption($context);
                $cmd = "pandoc ${opt} ${path}";
                break;
            case 'rst':
                break;
            case 'adoc':
                $opt = $this->adocOption($context);
                $cmd = "asciidoctor ${opt} -o - ${path}";
                break;
            default:
                throw new \Exception("ext: ${ext}");
        }
        
        return `${cmd}`;
    }
    
    protected function mdOption($context)
    {
        $opt = $context->query('.pandoc.md');
        $from = $opt->from;
        $to = $opt->to;
        
        return "-f ${from} -t ${to}";
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
    
    public function resolveResource($doc, $uri, $filename, $context=null)
    {
        $context = $context ?? $this->context;
        $resources = $context->get('resources');
        $rel_http_dir = str_repeat('../', substr_count($uri, '/') - 1) . "./";
        $rel_dir = $rel_http_dir.dirname($filename);
        $ptn = '"([^\"]+)\.('.join('|', $resources).')"';
        
        return preg_replace("@${ptn}@u", '"'.$rel_dir.'/$1.$2'.'"', $doc);
    }
    
    public function fromDocument(string $filename, ?callable $closure)
    {
        $path = trim(`get_path_from_filename.sh ${filename}`);
        if (!is_file($path))  {
            throw new \Exception("path: ${path}");
        }
        $info = pathinfo($path);
        $ext = $info['extension'];
        
        $context = $this->context
            ->stack($this->getContextFromFilename($filename)
                ->unstack()
                ->dump(), -1)
            ->stack(json_encode([
                'created' => trim(`created.sh ${path}`),
                'updated' => trim(`updated.sh ${path}`),
                'title' => trim(`get_doc_title.sh ${path}`),
                'ext' => $ext,
                'filename' => $filename]), -2);

        $doc = $this->convert($path, $ext, $this->context);
        
        if ($closure) {
            ob_start();
            echo $closure($doc, $context);
            $result = ob_get_clean();
        } else {
            $result = $doc;
        }

        return $result;
    }

    public function listDocuments(string $filename, ?callable $before, ?callable $loop, ?callable $after=null, $context=null)
    {
        $context = $context ?? $this->context;
        $depth = $context->get('depth') ?? 2;
        $index_name = $context->get('index_name');
        $limit = $context->get('limit');
        $sort = $context->get('sort');
        $order = $context->get('order');
        $page = $context->get('page') ?? 1;
        $content_dir = $context->get('content_dir');

        $reverse = $order === 'desc' ? ' | reverse' : '';
        $display = $context->get('out_dir') ? 'map(select(.display == false | not)) |' : '';
        $all = json_decode(`list_documents.sh ${content_dir}/${filename} ${index_name} ${depth} | jq '${display} sort_by(.${sort}) ${reverse}'`);
        
        $context = $context
            ->stack($this->getContextFromFilename($filename)->dump(), -1)
            ->stack(json_encode([
                'depth' => $depth,
                'page' => $page,
                'prev_page' => $this->prevPage($page),
                'next_page' => $this->nextPage($page, $limit, count($all))]), -1);

        $result = '';
        
        if ($before) {
            ob_start();
            echo $before($all, $context);
            $result .= ob_get_clean();
        }
        
        if ($loop) {
            if ($limit) {
                $start = ($page - 1) * $limit;
                $docs = array_slice($all, $start, $limit);
            } else {
                $docs = $all;
            }

            foreach ($docs as $doc) {
                $path = $doc->path;
                $info = pathinfo($path);
                $ext = $info['extension'];
                $dirname = preg_replace("@^${content_dir}/@", '', $info['dirname']);
                $filename = "${dirname}/${info['filename']}";
                
                $doc_context = $this->context
                    ->stack($this->getContextFromFilename($filename)
                        ->unstack()->dump(), -1)
                    ->stack(json_encode($doc), -2)
                    ->stack(json_encode([
                        'filename' => $filename,
                        'ext' => $ext,
                        'title' => trim(`get_doc_title.sh ${path}`)
                    ]), -2);
                
                ob_start();
                echo $loop($this->convert($path, $info['extension'], $doc_context), $doc_context);
                $result .= ob_get_clean();
            }
        }
        
        if ($after) {
            ob_start();
            echo $after($all, $context);
            $result .= ob_get_clean();
        }
        
        return $result;
    }

    public function prevPage($page)
    {
        $page = intval($page) - 1;
        return $page === 0 ? null : $page;
    }
    
    public function nextPage($page, $limit, $total)
    {
        $page = intval($page);
        return ($page * $limit) >= $total ? null : $page + 1;
    }
}