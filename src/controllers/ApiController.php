<?php
/**
 * API Controller
 * Handles AJAX API endpoints
 * 
 * @package NativeMVC
 */

namespace App\Controllers;

class ApiController extends BaseController
{
    /**
     * Test endpoint
     */
    public function test($params, $isAjax)
    {
        return $this->jsonSuccess('API test successful', [
            'timestamp' => time(),
            'message' => 'The AJAX framework is working perfectly!'
        ]);
    }
    
    /**
     * Get users list
     */
    public function getUsers($params, $isAjax)
    {
        // Example: Fetch from database
        $users = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com']
        ];
        
        return $this->jsonSuccess('Users retrieved', $users);
    }
    
    /**
     * Search endpoint
     */
    public function search($params, $isAjax)
    {
        $query = $this->get('q', '');
        
        // Perform search logic here
        
        return $this->jsonSuccess('Search completed', [
            'query' => $query,
            'results' => []
        ]);
    }
}
