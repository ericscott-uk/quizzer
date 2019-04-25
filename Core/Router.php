<?php

declare(strict_types = 1);

namespace Core;

use \Exception;
use App\Config;
use Core\RequestMethod;

/**
 * Router class
 *
 * PHP version 7.2
 */
class Router {

    // Associative array of routes
    protected $routes = [];
    // Parameters from the matched route
    protected $params = [];

    /**
     * Add a route to the routes array
     *
     * @param string $route  - The route URL
     * @param array  $params - Parameters (controller, action, etc.)
     * @param RequestMethod $requestMethod - HTTP request method
     *
     * @return void
     */
    public function add(string $route, array $params = [], $requestMethod = RequestMethod::GET) {
        $route = preg_replace('/\//', '\\/', $route);
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $route);
        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);
        $route = '/^' . $route . '$/i';

        $this->routes[$route] = [
            "params" => $params,
            "method" => $requestMethod
        ];
    }

    public function get(string $route, array $params = []){
        $route = preg_replace('/\//', '\\/', $route);
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $route);
        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);
        $route = '/^' . $route . '$/i';
        $this->routes[$route] = [
            "params" => $params,
            "method" => RequestMethod::GET
        ];
    }

    public function post(string $route, array $params = []){
        $route = preg_replace('/\//', '\\/', $route);
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $route);
        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);
        $route = '/^' . $route . '$/i';
        $this->routes[$route] = [
            "params" => $params,
            "method" => RequestMethod::POST
        ];
    }

    /**
     * Match the route to the routes in the routes array, setting the $params
     * property if a route is found
     *
     * @param string $url - The route URL
     *
     * @throws Exception from Utilities if an unhandled type is passed into Utilities::isEmpty()
     *
     * @return boolean - True if match found, false if not
     */
    private function match(string $url) : bool {
        $new   = rtrim($url, '/');
        $rchar = substr($url, -1);

        if(!Utilities::isEmpty($url) && Config::USE_URL_TRAILING_SLASH && $rchar !== '/') {
            // redirect to url with /
            $url = $url . '/';
            header('Location: ' . Utilities::getDomain() . '/' . $url, true, 301);
            exit;
        } elseif(!Utilities::isEmpty($url) && $rchar === '/') {
            // redirect to url without /
            header('Location: ' . Utilities::getDomain() . '/' . $new, true, 301);
            exit;
        }

        foreach($this->routes as $route => $params) {
            if(preg_match($route, $new, $matches) && $_SERVER['REQUEST_METHOD'] == $params['method']) {
                foreach($matches as $key => $match) {
                    if(is_string($key)) {
                        $params['params'][$key] = $match;
                    }
                }

                $this->params = $params['params'];
                return true;
            }
        }

        return false;
    }

    /**
     * Dispatch the route, creating the controller object and returning the action
     *
     * @param string $url - The route URL
     *
     * @throws Exception
     *
     * @return void
     */
    public function dispatch(string $url) {
        $url = Utilities::removeQueryStringVars($url);

        if($this->match($url)) {
            $controller = $this->params['controller'];
            $controller = Utilities::convertToStudlyCaps($controller);
            $controller = $this->getNamespace() . $controller;

            if(class_exists($controller)) {
                $controller_object = new $controller($this->params);

                $action = $this->params['action'];
                $action = Utilities::convertToCamelCase($action);

                if(preg_match('/action$/i', $action) == 0) {
                    $controller_object->$action();
                } else {
                    throw new Exception("Method $action in controller $controller not found", 404);
                }
            } else {
                throw new Exception("Controller class $controller not found", 404);
            }
        } else {
            $url = ($url == "" ? "/" : $url);
            throw new Exception("Route not found for $url on {$_SERVER['REQUEST_METHOD']}", 404);
        }
    }

    /**
     * Get the namespace for the controller class. The namespace defined in the
     * route parameters is added if present.
     *
     * @return string - The request URL
     */
    private function getNamespace() : string {
        $namespace = 'App\Controllers\\';

        if(array_key_exists('namespace', $this->params)) {
            $namespace .= $this->params['namespace'] . '\\';
        }

        return $namespace;
    }
}
