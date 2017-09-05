#!/usr/bin/env ruby

require 'json'
require 'yaml'

$:.push(Dir.pwd)
require 'web/ruby/router.rb'

puts Router.new(YAML.load_file ENV['APP_ROUTES'])
         .detect(ARGV.last).merge({:app_stack => ENV['APP_STACK'].split.reverse}).to_json
