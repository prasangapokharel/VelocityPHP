<?php
/**
 * VelocityPhp Route Class
 * Individual route definition with middleware and parameters
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Utils;

class Route
{
    private $uri;
    private $methods = [];
    private $action;
    private $name;
    private $middleware = [];
    private $parameters = [];
    private $where = [];
    private $prefix = '';
    
    public function __construct($methods, $uri, $action)
    {
        $this->methods = is_array($methods) ? $methods : [$methods];
        $this->uri = $uri;
        $this->action = $action;
    }
    
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }
    
    public function middleware($middleware)
    {
        $middleware = is_array($middleware) ? $middleware : [$middleware];
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }
    
    public function where($parameter, $pattern = null)
    {
        if (is_array($parameter)) {
            $this->where = array_merge($this->where, $parameter);
        } else {
            $this->where[$parameter] = $pattern;
        }
        return $this;
    }
    
    public function prefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }
    
    public function getUri()
    {
        return $this->prefix . $this->uri;
    }
    
    public function getMethods()
    {
        return $this->methods;
    }
    
    public function getAction()
    {
        return $this->action;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getMiddleware()
    {
        return $this->middleware;
    }
    
    public function getParameters()
    {
        return $this->parameters;
    }
    
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }
    
    public function getWhere()
    {
        return $this->where;
    }
    
    public function matches($uri, $method)
    {
        if (!in_array($method, $this->methods)) {
            return false;
        }
        
        $pattern = $this->getPattern();
        return preg_match($pattern, $uri, $matches) === 1;
    }
    
    /**
     * Get regex pattern for route matching
     * Public so RouteCollection can use it for parameter extraction
     */
    public function getPattern()
    {
        $pattern = $this->getUri();
        
        // Replace route parameters with regex patterns
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\?}/', '(?P<$1>[^/]*)', $pattern);
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+):(.+?)\}/', '(?P<$1>$2)', $pattern);
        
        // Apply where constraints
        foreach ($this->where as $param => $constraint) {
            $pattern = str_replace('(?P<' . $param . '>[^/]+)', '(?P<' . $param . '>' . $constraint . ')', $pattern);
        }
        
        return '#^' . $pattern . '$#';
    }
}

