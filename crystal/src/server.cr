require "router"

class WebServer
    include Router

    def initialize
    end

    def draw_routes
        get "/accounts/filter/" do |context, params|
            context.response.content_type = "application/json"
            context.response.status_code = 400
            context.response.print "{\"accounts\":[]}"
            context
        end

        get "/accounts/group/" do |context, params|
            context.response.content_type = "application/json"
            context.response.status_code = 400
            context.response.print "{\"groups\":[]}"
            context
        end

        get "/accounts/:id/recommend/" do |context, params|
            context.response.content_type = "application/json"
            context.response.status_code = 400
            context.response.print "{\"accounts\":[]}"
            context
        end

        get "/accounts/:id/suggest/" do |context, params|
            context.response.content_type = "application/json"
            context.response.status_code = 400
            context.response.print "{\"accounts\":[]}"
            context
        end

        post "/accounts/new/" do |context, params|
            context.response.content_type = "application/json"
            context.response.status_code = 201
            context.response.print "{}"
            context
        end

        post "/accounts/likes/" do |context, params|
            context.response.content_type = "application/json"
            context.response.status_code = 202
            context.response.print "{}"
            context
        end

        post "/accounts/:id/" do |context, params|
            context.response.content_type = "application/json"
            context.response.status_code = 202
            context.response.print "{}"
            context
        end
    end

    def run
        server = HTTP::Server.new(route_handler)
        server.bind_tcp "0.0.0.0", 80
        puts "Listening on http://0.0.0.0:80"
        server.listen
    end
end

web_server = WebServer.new
web_server.draw_routes
web_server.run
