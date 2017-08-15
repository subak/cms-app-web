class Router
  def initialize(routes)
    @routes = routes.map do |route|
      route[:_names] = route['match'].scan(/\?\<([^>]+)\>/).flatten
      route
    end
  end

  def detect(path)
    match = nil
    route = @routes.find do |route|
      pattern = Regexp.new(route['match'])
      match = pattern.match(path)
    end

    return nil unless match

    res = {}

    route[:_names].each do |name|
      name = name.to_sym

      begin
        match[name]
      rescue
      else
        res[name.to_s] = match[name]
      end
    end

    route = route.dup
    route.delete(:_names)

    res['uri'] = path unless res['uri']
    res['status'] = 200 unless res['status']

    res.merge(route)
  end
end