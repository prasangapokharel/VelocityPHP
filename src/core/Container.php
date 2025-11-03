<?php
/**
 * VelocityPhp Dependency Injection Container
 * Service binding, resolution, singleton, factory patterns, auto-wiring
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Core;

use ReflectionClass;
use ReflectionParameter;

class Container
{
    private static $instance;
    private $bindings = [];
    private $singletons = [];
    private $instances = [];
    private $aliases = [];
    
    private function __construct()
    {
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function bind($abstract, $concrete = null, $shared = false)
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }
        
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared
        ];
        
        return $this;
    }
    
    public function singleton($abstract, $concrete = null)
    {
        return $this->bind($abstract, $concrete, true);
    }
    
    public function instance($abstract, $instance)
    {
        $this->instances[$abstract] = $instance;
        return $this;
    }
    
    public function alias($abstract, $alias)
    {
        $this->aliases[$alias] = $abstract;
        return $this;
    }
    
    public function make($abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($abstract);
        
        // Check if instance already exists
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        // Check if singleton exists
        if (isset($this->singletons[$abstract])) {
            return $this->singletons[$abstract];
        }
        
        // Get concrete implementation
        $concrete = $this->getConcrete($abstract);
        
        // Build instance
        $object = $this->build($concrete, $parameters);
        
        // Store singleton if needed
        if (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared']) {
            $this->singletons[$abstract] = $object;
        }
        
        return $object;
    }
    
    public function resolve($abstract, array $parameters = [])
    {
        return $this->make($abstract, $parameters);
    }
    
    private function getAlias($abstract)
    {
        return $this->aliases[$abstract] ?? $abstract;
    }
    
    private function getConcrete($abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }
        
        return $abstract;
    }
    
    private function build($concrete, array $parameters = [])
    {
        // If it's a closure, call it
        if ($concrete instanceof \Closure) {
            return $concrete($this, $parameters);
        }
        
        // If it's an interface, try to resolve from bindings
        if (interface_exists($concrete)) {
            throw new \Exception("Cannot resolve interface {$concrete} without binding");
        }
        
        // Try to resolve class
        if (!class_exists($concrete)) {
            throw new \Exception("Class {$concrete} not found");
        }
        
        $reflector = new ReflectionClass($concrete);
        
        // If class is not instantiable, throw exception
        if (!$reflector->isInstantiable()) {
            throw new \Exception("Class {$concrete} is not instantiable");
        }
        
        // Get constructor
        $constructor = $reflector->getConstructor();
        
        // If no constructor, create instance
        if ($constructor === null) {
            return new $concrete();
        }
        
        // Resolve constructor parameters
        $dependencies = $this->resolveDependencies($constructor->getParameters(), $parameters);
        
        // Create instance with dependencies
        return $reflector->newInstanceArgs($dependencies);
    }
    
    private function resolveDependencies(array $parameters, array $provided = [])
    {
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            
            // Use provided parameter if available
            if (isset($provided[$name])) {
                $dependencies[] = $provided[$name];
                continue;
            }
            
            // Try to resolve from type hint
            $dependency = $this->resolveDependency($parameter);
            $dependencies[] = $dependency;
        }
        
        return $dependencies;
    }
    
    private function resolveDependency(ReflectionParameter $parameter)
    {
        $type = $parameter->getType();
        
        // If no type hint, try default value
        if ($type === null) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            throw new \Exception("Cannot resolve dependency: {$parameter->getName()}");
        }
        
        // If type is a class, resolve it
        $typeName = $type->getName();
        if (!class_exists($typeName) && !interface_exists($typeName)) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            throw new \Exception("Cannot resolve dependency: {$typeName}");
        }
        
        // Try to resolve from container
        try {
            return $this->make($typeName);
        } catch (\Exception $e) {
            // If resolution fails, try default value
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            throw $e;
        }
    }
    
    public function factory($abstract, callable $factory)
    {
        return $this->bind($abstract, function($container) use ($factory) {
            return $factory($container);
        }, false);
    }
    
    public function has($abstract)
    {
        $abstract = $this->getAlias($abstract);
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
    
    public function flush()
    {
        $this->bindings = [];
        $this->singletons = [];
        $this->instances = [];
        $this->aliases = [];
    }
}

