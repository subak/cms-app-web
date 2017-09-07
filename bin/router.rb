#!/usr/bin/env ruby

require 'json'
require 'yaml'

$:.push(Dir.pwd)
require 'web/ruby/router.rb'

puts Router.new(YAML.load_file ENV['APP_ROUTES'])
         .detect(ARGV.last)
         .merge({:app_stack => ENV.fetch('APP_STACK', 'web').split.reverse,
                 :content_dir => ENV.fetch('CONTENT_DIR', 'content'),
                 :html_dir => ENV.fetch('HTML_DIR', 'html'),
                 :context_auto => ENV.fetch('CONTEXT_AUTO', 'meta.yml')})
         .merge(JSON.parse ENV.fetch('CONTEXT', '{}'))
         .to_json
