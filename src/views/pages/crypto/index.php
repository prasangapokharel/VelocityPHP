
<div class="crypto-prices max-w-6xl mx-auto px-4 py-8">
    <h2 class="text-2xl font-semibold mb-6">Crypto Prices</h2>
    <?php if (isset($prices['error'])): ?>
        <div class="error"><?php echo htmlspecialchars($prices['error']); ?></div>
    <?php elseif (empty($prices)): ?>
        <div>No data available.</div>
    <?php else: ?>
        <?php
        // Preferred order for display
        $preferred = ['usd', 'usd_market_cap', 'usd_24h_change', 'last_updated_at'];

        // Helper to format numeric values
        function fmt_number($n)
        {
            if ($n === null || $n === '') return '-';
            if (!is_numeric($n)) return htmlspecialchars((string)$n);
            $n = (float)$n;
            if (abs($n) >= 1) {
                return number_format($n, 2, '.', ',');
            }
            // for small values show more precision
            return rtrim(rtrim(number_format($n, 8, '.', ','), '0'), '.');
        }

        function fmt_marketcap($n)
        {
            if ($n === null || $n === '') return '-';
            if (!is_numeric($n)) return htmlspecialchars((string)$n);
            return number_format((float)$n, 2, '.', ',');
        }

        function fmt_change($n)
        {
            if ($n === null || $n === '') return '-';
            if (!is_numeric($n)) return htmlspecialchars((string)$n);
            return round($n, 4) . '%';
        }
        ?>

        <?php foreach ($prices as $coin => $info): ?>
            <div class="crypto-coin bg-white dark:bg-gray-900 shadow-sm rounded-lg mb-6">
                <div class="p-4 md:p-6">
                    <h3 class="text-lg font-medium mb-3"><?php echo htmlspecialchars(ucfirst($coin)); ?></h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        <?php
                        // render preferred keys first if present
                        foreach ($preferred as $key) {
                            if (array_key_exists($key, $info)) {
                                echo '<div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded">';
                                echo '<div class="text-sm text-gray-600 dark:text-gray-300 font-medium">' . htmlspecialchars(str_replace('_', ' ', $key)) . '</div>';
                                echo '<div class="text-right text-lg font-semibold text-gray-900 dark:text-white">';
                                if ($key === 'usd') echo fmt_number($info[$key]);
                                elseif ($key === 'usd_market_cap') echo fmt_marketcap($info[$key]);
                                elseif ($key === 'usd_24h_change') echo fmt_change($info[$key]);
                                else echo htmlspecialchars((string)$info[$key]);
                                echo '</div></div>';
                            }
                        }

                        // render any other keys
                        foreach ($info as $k => $v) {
                            if (in_array($k, $preferred)) continue;
                            echo '<div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded">';
                            echo '<div class="text-sm text-gray-600 dark:text-gray-300 font-medium">' . htmlspecialchars(str_replace('_', ' ', $k)) . '</div>';
                            echo '<div class="text-right text-lg font-semibold text-gray-900 dark:text-white">';
                            if (is_numeric($v)) {
                                if (stripos($k, 'market') !== false) echo fmt_marketcap($v);
                                elseif (stripos($k, 'change') !== false) echo fmt_change($v);
                                elseif (preg_match('/(_at$|updated|timestamp)/i', $k)) echo htmlspecialchars(date('Y-m-d H:i:s', (int)$v));
                                else echo fmt_number($v);
                            } else {
                                echo htmlspecialchars((string)$v);
                            }
                            echo '</div></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
