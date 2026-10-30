<?php

declare(strict_types=1);

/**
 * Returns currency symbol map extracted from location data or currency cache.
 * Can be safely called from application helpers or controllers.
 *
 * @return array<string, string> ISO currency code => currency symbol
 */
if (!function_exists('getLocationCurrencyMap')) {
    function getLocationCurrencyMap(): array
    {
        static $map = null;
        if ($map !== null) {
            return $map;
        }

        $dataDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'data';
        $currencyFile = $dataDir . DIRECTORY_SEPARATOR . 'currency_data.php';

        if (is_file($currencyFile)) {
            $data = require $currencyFile;
            if (is_array($data)) {
                $map = $data;
                return $map;
            }
        }

        $locationFile = $dataDir . DIRECTORY_SEPARATOR . 'location_data.php';
        if (is_file($locationFile)) {
            $locations = require $locationFile;
            if (is_array($locations)) {
                $extracted = [];
                foreach ($locations as $loc) {
                    $c = strtoupper(trim((string) ($loc['currency'] ?? '')));
                    $s = trim((string) ($loc['currency_symbol'] ?? ''));
                    if ($c !== '' && $s !== '' && !isset($extracted[$c])) {
                        $extracted[$c] = $s;
                    }
                }
                if (!empty($extracted)) {
                    $map = $extracted;
                    return $map;
                }
            }
        }

        $map = [
            'CAD' => '$',
            'USD' => '$',
            'INR' => '₹',
            'EUR' => '€',
            'GBP' => '£',
            'AUD' => '$',
            'NZD' => '$',
            'SGD' => '$',
            'JPY' => '¥',
            'CNY' => '¥',
        ];
        return $map;
    }
}

/**
 * Executes location and currency data import from remote source.
 */
if (!function_exists('importLocationData')) {
    function importLocationData(string $sourceUrl = 'https://github.com/dr5hn/countries-states-cities-database/releases/latest/download/json-countries+states+cities.json.gz'): void
    {
        $outputDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'data';
        $outputFile = $outputDirectory . DIRECTORY_SEPARATOR . 'location_data.php';
        $currencyOutputFile = $outputDirectory . DIRECTORY_SEPARATOR . 'currency_data.php';

        if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0755, true) && !is_dir($outputDirectory)) {
            throw new RuntimeException("Unable to create {$outputDirectory}");
        }

        $ch = curl_init($sourceUrl);
        if ($ch === false) {
            throw new RuntimeException('Unable to initialise cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => '', // transparently decode gzip
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT        => 300,
            CURLOPT_USERAGENT      => 'staging-location-import/1.0',
        ]);

        $payload = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        // curl_close() is deprecated as of PHP 8.5; the handle is released automatically.
        unset($ch);

        if ($payload === false || $status < 200 || $status >= 300) {
            throw new RuntimeException("Location download failed (HTTP {$status}): {$error}");
        }

        $countries = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        unset($payload);

        if (!is_array($countries)) {
            throw new RuntimeException('The downloaded location data is not a JSON array.');
        }

        $locations = [];
        $currencyMap = [];

        foreach ($countries as $country) {
            if (!is_array($country)) {
                continue;
            }

            $countryName = trim((string) ($country['name'] ?? ''));
            $countryIso = strtoupper(trim((string) ($country['iso2'] ?? $country['iso3'] ?? '')));
            $currency = trim((string) ($country['currency'] ?? ''));
            $currencySymbol = trim((string) ($country['currency_symbol'] ?? ''));
            $firstTimezone = $country['timezones'][0] ?? [];
            $countryTimezoneOffset = is_array($firstTimezone)
                ? trim((string) ($firstTimezone['gmtOffsetName'] ?? $firstTimezone['gmtOffset'] ?? ''))
                : '';

            if ($countryName === '' || $countryIso === '') {
                continue;
            }

            if ($currency !== '' && $currencySymbol !== '') {
                $cCode = strtoupper($currency);
                if (!isset($currencyMap[$cCode])) {
                    $currencyMap[$cCode] = $currencySymbol;
                }
            }

            foreach (($country['states'] ?? []) as $state) {
                if (!is_array($state)) {
                    continue;
                }

                $province = trim((string) ($state['name'] ?? ''));
                if ($province === '') {
                    continue;
                }

                foreach (($state['cities'] ?? []) as $city) {
                    $cityName = is_array($city)
                        ? trim((string) ($city['name'] ?? ''))
                        : trim((string) $city);

                    if ($cityName === '') {
                        continue;
                    }

                    // Prefer a city-level timezone when the source provides one;
                    // otherwise fall back to the country's first declared timezone.
                    $cityTimezone = is_array($city) ? ($city['timezone'] ?? ($city['timezones'][0] ?? null)) : null;
                    if (is_array($cityTimezone)) {
                        $cityTimezone = $cityTimezone['gmtOffsetName'] ?? $cityTimezone['gmtOffset'] ?? '';
                    }
                    $timezoneOffset = trim((string) ($cityTimezone ?: $countryTimezoneOffset));

                    $locations[] = [
                        'country'        => $countryName,
                        'country_iso'    => $countryIso,
                        'currency'       => $currency,
                        'currency_symbol'=> $currencySymbol,
                        'timezone_offset'=> $timezoneOffset,
                        'province'       => $province,
                        'city'           => $cityName,
                    ];
                }
            }
        }

        $php = "<?php\n\nreturn " . var_export($locations, true) . ";\n";
        if (file_put_contents($outputFile, $php, LOCK_EX) === false) {
            throw new RuntimeException("Unable to write {$outputFile}");
        }

        $currencyPhp = "<?php\n\ndeclare(strict_types=1);\n\n/**\n * Currency Symbol Mapping (ISO 4217 Code => Symbol)\n * Generated by scripts/import_location_data.php\n */\nreturn " . var_export($currencyMap, true) . ";\n";
        if (file_put_contents($currencyOutputFile, $currencyPhp, LOCK_EX) === false) {
            throw new RuntimeException("Unable to write {$currencyOutputFile}");
        }

        printf("Wrote %d flat location rows to %s\n", count($locations), $outputFile);
        printf("Wrote %d currencies to %s\n", count($currencyMap), $currencyOutputFile);
    }
}

// Execute import only when running directly (CLI or direct web invocation)
$isDirectExecution = empty(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1))
    || (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__);

if ($isDirectExecution) {
    importLocationData();
}

