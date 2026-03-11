<?php
/**
 * CryptoService
 * Simple wrapper for fetching CoinGecko price data with a tiny file cache
 */

namespace App\Services;

class CryptoService
{
    protected $cacheFile;
    protected $cacheTtl = 60; // seconds

    public function __construct()
    {
        // storage path (create if missing)
        $this->cacheFile = realpath(__DIR__ . '/../../storage') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'coingecko_cache.json';
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }

    /**
     * Get prices for given coin ids
     * @param array $ids
     * @param string $vs_currency
     * @return array
     */
    public function getPrices(array $ids = ['bitcoin', 'ethereum'], $vs_currency = 'usd')
    {
        $ids = array_filter(array_map('trim', $ids));
        if (empty($ids)) {
            return [];
        }

        $key = implode(',', $ids) . '|' . $vs_currency;

        // try cache
        $cache = [];
        if (file_exists($this->cacheFile)) {
            $raw = @file_get_contents($this->cacheFile);
            $cache = $raw ? json_decode($raw, true) : [];
            if (isset($cache[$key]) && (time() - ($cache[$key]['fetched_at'] ?? 0)) < $this->cacheTtl) {
                return $cache[$key]['data'];
            }
        }

        // Build CoinGecko URL
        $idsParam = implode(',', $ids);
        $url = 'https://api.coingecko.com/api/v3/simple/price?ids=' . urlencode($idsParam) . '&vs_currencies=' . urlencode($vs_currency) . '&include_24hr_change=true&include_last_updated_at=true';

        // fetch
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $resp = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);
            if ($resp === false) {
                return ['error' => 'Failed to fetch from CoinGecko: ' . $err];
            }
        } else {
            $resp = @file_get_contents($url);
            if ($resp === false) {
                return ['error' => 'Failed to fetch from CoinGecko (file_get_contents)'];
            }
        }

        $data = json_decode($resp, true);
        if ($data === null) {
            return ['error' => 'Invalid JSON response from CoinGecko'];
        }

        // store in cache
        $cache[$key] = [
            'fetched_at' => time(),
            'data' => $data
        ];
        @file_put_contents($this->cacheFile, json_encode($cache));

        return $data;
    }
}
