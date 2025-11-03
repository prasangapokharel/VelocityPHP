<?php
/**
 * VelocityPhp View Rendering Engine
 * Template engine with variables, partials, and layouts
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Utils;

class View
{
    private static $shared = [];
    private static $sections = [];
    private static $sectionStack = [];
    private static $currentSection = null;
    
    /**
     * Render view
     */
    public static function render($view, array $data = [], $layout = null)
    {
        $data = array_merge(self::$shared, $data);
        extract($data);
        
        $viewPath = self::getViewPath($view);
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$view}");
        }
        
        ob_start();
        include $viewPath;
        $content = ob_get_clean();
        
        if ($layout !== null) {
            return self::renderWithLayout($layout, $content, $data);
        }
        
        return $content;
    }
    
    /**
     * Render view with layout
     */
    private static function renderWithLayout($layout, $content, $data = [])
    {
        extract($data);
        extract(self::$shared);
        
        $layoutPath = VIEW_PATH . '/layouts/' . $layout . '.php';
        
        if (!file_exists($layoutPath)) {
            return $content; // Return content without layout if layout not found
        }
        
        // Store content for yield
        $GLOBALS['__view_content__'] = $content;
        $GLOBALS['__view_sections__'] = self::$sections;
        
        ob_start();
        include $layoutPath;
        return ob_get_clean();
    }
    
    /**
     * Get view path
     */
    private static function getViewPath($view)
    {
        // Check if it's a full path
        if (file_exists($view)) {
            return $view;
        }
        
        // Try pages directory
        $path = VIEW_PATH . '/pages/' . $view . '/index.php';
        if (file_exists($path)) {
            return $path;
        }
        
        // Try direct file
        $path = VIEW_PATH . '/pages/' . $view . '.php';
        if (file_exists($path)) {
            return $path;
        }
        
        return $view;
    }
    
    /**
     * Share data across all views
     */
    public static function share($key, $value = null)
    {
        if (is_array($key)) {
            self::$shared = array_merge(self::$shared, $key);
        } else {
            self::$shared[$key] = $value;
        }
    }
    
    /**
     * Get shared data
     */
    public static function getShared($key = null)
    {
        if ($key === null) {
            return self::$shared;
        }
        
        return self::$shared[$key] ?? null;
    }
    
    /**
     * Include partial/view
     */
    public static function include($view, array $data = [])
    {
        $data = array_merge(self::$shared, $data);
        extract($data);
        
        $viewPath = self::getViewPath($view);
        
        if (file_exists($viewPath)) {
            include $viewPath;
        }
    }
    
    /**
     * Escape output
     */
    public static function e($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Yield section content
     */
    public static function yield($section, $default = '')
    {
        return self::$sections[$section] ?? $default;
    }
    
    /**
     * Start section
     */
    public static function section($name)
    {
        self::$currentSection = $name;
        self::$sectionStack[] = $name;
        ob_start();
    }
    
    /**
     * End section
     */
    public static function endSection()
    {
        if (empty(self::$sectionStack)) {
            return;
        }
        
        $name = array_pop(self::$sectionStack);
        self::$sections[$name] = ob_get_clean();
        self::$currentSection = end(self::$sectionStack);
    }
    
    /**
     * Start push (for stack sections)
     */
    public static function push($section)
    {
        self::$currentSection = $section;
        if (!isset(self::$sections[$section])) {
            self::$sections[$section] = [];
        }
        ob_start();
    }
    
    /**
     * End push
     */
    public static function endPush()
    {
        if (self::$currentSection === null) {
            return;
        }
        
        if (!is_array(self::$sections[self::$currentSection])) {
            self::$sections[self::$currentSection] = [];
        }
        
        self::$sections[self::$currentSection][] = ob_get_clean();
        self::$currentSection = null;
    }
    
    /**
     * Stack section content
     */
    public static function stack($section)
    {
        if (!isset(self::$sections[$section]) || !is_array(self::$sections[$section])) {
            return '';
        }
        
        return implode("\n", self::$sections[$section]);
    }
    
    /**
     * Check if section exists
     */
    public static function hasSection($section)
    {
        return isset(self::$sections[$section]);
    }
    
    /**
     * Get section content
     */
    public static function getSection($section)
    {
        return self::$sections[$section] ?? '';
    }
}

