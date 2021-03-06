require 'web/ruby/router.rb'

class Handler
  @@router = nil

  def call(env)
    @@router ||= Router.new(JSON.parse `yaml2json #{ENV['APP_ROUTES']}`)
    context = @@router.detect(env['PATH_INFO'])
    condition = case
                  when context.nil? then false
                  when !context['condition'].nil? then eval(context['condition'])
                  else true
                end

    status = !context.nil? && context['status'] || 200

    if (condition && status != 399)
      query = Hash[Hash[*env['QUERY_STRING'].scan(/([^=&]+)=([^=&]+)/).flatten].map{|k,v| [URI.decode_www_component(k),URI.decode_www_component(v)]}]
      context['query'] = env['QUERY_STRING'] unless env['QUERY_STRING'].empty?
      context
          .merge!({:app_stack => (ENV['APP_STACK'] || 'web').split.reverse,
                      :content_dir => ENV['CONTENT_DIR'] || 'content',
                      :html_dir => ENV['HTML_DIR'] || 'html',
                      :context_auto => ENV['CONTEXT_AUTO'] || 'meta.yml'})
          .merge!(JSON.parse ENV['CONTEXT'] || '{}')
          .merge!(JSON.parse query['context'] || '{}')
      
      cache_path = "/tmp/cms#{context['uri']}".sub(/\/$/, '/index.html')

      if context['cache'].to_s.empty? || !File.exists?(cache_path) || !`find #{context['cache']} -newer #{cache_path}`.empty?
        content_type = context['content_type'] || 'text/html; charset=utf-8'
        body = if context['body']
                 eval("<<EOF\n#{context['body']}\nEOF\n")
               else `#{context['handler']} '#{JSON.generate(context)}'` end

        if !context['cache'].to_s.empty?
          puts `mkdir -pv #{File.dirname(cache_path)}`
          open(cache_path, 'w'){|f| f.puts(body)}
        end

        [
            status,
            {
                'content-type' => content_type
            },
            [
                body
            ]
        ]
      else
        [399, {}, []]
      end
    else
      [399, {}, []]
    end
  end
end

Handler.new