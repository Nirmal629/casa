<?php

declare(strict_types=1);

use App\Services\Ledger\LedgerService;

if (!function_exists('calculateLedgerSummary')) {
    /**
     * Delegates summary calculation to shared LedgerService.
     */
    function calculateLedgerSummary($conn, $user_id, $host_id, $year = 0, $month = 0)
    {
        $service = new LedgerService();
        return $service->calculateSummary((int) $user_id, (int) $host_id, (int) $year, (int) $month);
    }
}

if (!function_exists('fetchPlayerLedgerData')) {
    /**
     * Delegates ledger data fetching to shared LedgerService.
     */
    function fetchPlayerLedgerData($conn, $user_id, $year, $month, $override_host_id = 0)
    {
        $service = new LedgerService();
        $authId = (int) ($user_id ?: ($_SESSION['user_id'] ?? 0));
        return $service->getMonthlyLedger((int) $override_host_id, (int) $user_id, (int) $year, (int) $month, 'player', $authId);
    }
}

if (!function_exists('renderPlayerSideLedgerHtml')) {
    /**
     * Renders player monthly ledger HTML via LedgerService & LedgerPresenter.
     */
    function renderPlayerSideLedgerHtml($conn, $user_id, $year, $month, $override_host_id = 0)
    {
        $service = new LedgerService();
        $authUserId = (int) ($user_id ?: ($_SESSION['user_id'] ?? 0));
        $ledger = $service->getMonthlyLedger((int) $override_host_id, (int) $user_id, (int) $year, (int) $month, 'player', $authUserId);
        $ledger['is_player_side'] = true;

        return \App\Services\Ledger\LedgerPresenter::renderMonthlyHtml($ledger);
    }
}

if (!function_exists('renderPlayerPayHtml')) {
    /**
     * Renders player payment HTML via LedgerService & LedgerPresenter.
     */
    function renderPlayerPayHtml($conn, $user_id, $year, $month, $is_player_side = false, $override_host_id = 0)
    {
        $user_id = (int) $user_id;
        $year = (int) $year;
        $month = (int) $month;
        $hostId = (int) $override_host_id;

        $authUserId = (int) ($_SESSION['user_id'] ?? ($is_player_side ? $user_id : $hostId));
        $authUserRole = $is_player_side ? 'player' : (strtolower((string) ($_SESSION['usertype'] ?? 'host')));

        $service = new LedgerService();
        $ledger = $service->getMonthlyLedger($hostId, $user_id, $year, $month, $authUserRole, $authUserId);
        $ledger['is_player_side'] = $is_player_side;

        return \App\Services\Ledger\LedgerPresenter::renderMonthlyHtml($ledger);
    }
}

if (!function_exists('formatPayAmount')) {
    /**
     * Format amount with symbol: CAD → $, INR → ₹, EUR → €, etc.
     * Uses currency mapping from scripts/import_location_data.php / assets/data/currency_data.php.
     */
    function formatPayAmount($amount, $currency = 'CAD')
    {
        static $currencyMap = null;

        if ($currencyMap === null) {
            $scriptFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'import_location_data.php';
            if (!function_exists('getLocationCurrencyMap') && is_file($scriptFile)) {
                require_once $scriptFile;
            }

            if (function_exists('getLocationCurrencyMap')) {
                $currencyMap = getLocationCurrencyMap();
            } else {
                $currencyDataFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'currency_data.php';
                if (is_file($currencyDataFile)) {
                    $currencyMap = require $currencyDataFile;
                }
            }

            if (!is_array($currencyMap)) {
                $currencyMap = [
                    'CAD' => '$',
                    'USD' => '$',
                    'INR' => '₹',
                    'EUR' => '€',
                    'GBP' => '£',
                    'AUD' => '$',
                ];
            }
        }

        $amount = (float) $amount;
        $curr = trim((string) $currency);

        if ($curr === '' || $curr === '$' || $curr === '₹' || $curr === '€' || $curr === '£' || $curr === '¥') {
            $symbol = ($curr === '') ? '$' : $curr;
            return $symbol . number_format($amount, 2);
        }

        $code = strtoupper($curr);
        $symbol = $currencyMap[$code] ?? null;

        if ($symbol === null || $symbol === '') {
            $symbol = ($code === 'INR') ? '₹' : '$';
        }

        return $symbol . number_format($amount, 2);
    }
}

