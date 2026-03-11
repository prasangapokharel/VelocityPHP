<?php
/**
 * Crypto Controller
 * Fetches crypto prices and returns view data (AJAX-friendly)
 */

namespace App\Controllers;

use App\Services\CryptoService;

class CryptoController extends BaseController
{
    /**
     * Show crypto prices
     * @param array $params
     * @param bool $isAjax
     */
    public function index($params, $isAjax)
    {
        $idsParam = $this->get('ids', 'bitcoin,ethereum');
        $ids = array_filter(array_map('trim', explode(',', $idsParam)));
        $vs_currency = $this->get('vs_currency', 'usd');

        $service = new CryptoService();
        $prices = $service->getPrices($ids, $vs_currency);

        if ($isAjax) {
            // Return a rendered partial for AJAX
            return $this->view('crypto/index', ['prices' => $prices], 'Crypto Prices');
        }

        // For full page requests, router will render layout; return null
        return null;
    }
}
