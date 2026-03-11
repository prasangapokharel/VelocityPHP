<?php
/**
 * VelocityPhp Dashboard Controller
 *
 * @package VelocityPhp
 */

namespace App\Controllers;

use App\Utils\Auth;
use App\Utils\Response;

class DashboardController extends BaseController
{
    /**
     * Show dashboard (requires authentication)
     */
    public function index($params, $isAjax)
    {
        if (!Auth::check()) {
            if ($isAjax) {
                return $this->jsonError('Authentication required', [], 401);
            }
            Response::redirect('/login');
        }

        return $this->view('dashboard/index', [], 'Dashboard');
    }
}
