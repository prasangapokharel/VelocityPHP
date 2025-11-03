<?php
/**
 * VelocityPhp Middleware Interface
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Middleware;

interface MiddlewareInterface
{
    public function handle($request, $next);
}

