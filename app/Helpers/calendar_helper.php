<?php

declare(strict_types=1);


if (!function_exists('getCurrentPeriod')) {
    function getCurrentPeriod(): array
    {
        return [
            'year' => (int) date('Y'),
            'month' => (int) date('n'),
        ];
    }
}

if (!function_exists('getMonthOptions')) {

    function getMonthOptions(bool $short = false): array
    {
        if ($short) {
            return [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
            ];
        }

        return [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];
    }
}

if (!function_exists('getYearOptions')) {

    function getYearOptions(?int $startYear = null, ?int $endYear = null): array
    {
        $period = getCurrentPeriod();
        $startYear = $startYear ?? ($period['year'] - 1);
        $endYear = $endYear ?? ($period['year'] + 2);
        $years = [];
        for ($y = $startYear; $y <= $endYear; $y++) {
            $years[$y] = $y;
        }
        return $years;
    }
}

if (!function_exists('renderMonthOptions')) {
    function renderMonthOptions(?int $selectedMonth = null, bool $short = true): string
    {
        $selectedMonth = $selectedMonth ?? (int) date('n');
        $months = getMonthOptions($short);
        $html = '';
        foreach ($months as $num => $name) {
            $selected = ($num === $selectedMonth) ? ' selected' : '';
            $html .= "<option value=\"{$num}\"{$selected}>{$name}</option>\n";
        }
        return $html;
    }
}

if (!function_exists('renderYearOptions')) {
    function renderYearOptions(?int $selectedYear = null, ?int $startYear = null, ?int $endYear = null): string
    {
        $selectedYear = $selectedYear ?? (int) date('Y');
        $years = getYearOptions($startYear, $endYear);
        $html = '';
        foreach ($years as $y) {
            $selected = ($y === $selectedYear) ? ' selected' : '';
            $html .= "<option value=\"{$y}\"{$selected}>{$y}</option>\n";
        }
        return $html;
    }
}

if (!function_exists('getDynamicCalendarJsPath')) {
    function getDynamicCalendarJsPath(): string
    {
        $path = __DIR__ . '/../../assets/js/dynamic-event-calendar.js';
        $real = realpath($path);
        return $real !== false ? $real : $path;
    }
}

if (!function_exists('getDynamicCalendarJsVersion')) {
    function getDynamicCalendarJsVersion(): string
    {
        $filePath = getDynamicCalendarJsPath();
        return file_exists($filePath) ? (string) filemtime($filePath) : '1.0';
    }
}

if (!function_exists('getDynamicCalendarJsUrl')) {
    function getDynamicCalendarJsUrl(?string $assetPrefix = null): string
    {
        if ($assetPrefix === null) {
            global $assetPrefix;
            if (isset($assetPrefix) && is_string($assetPrefix)) {
                $prefix = $assetPrefix;
            } else {
                $inSubfolder = in_array(basename(dirname($_SERVER['SCRIPT_FILENAME'] ?? '')), ['host', 'player'], true);
                $prefix = $inSubfolder ? '../' : '/';
            }
        } else {
            $prefix = $assetPrefix;
        }

        $ver = getDynamicCalendarJsVersion();
        if ($prefix === '' || $prefix === null) {
            return "assets/js/dynamic-event-calendar.js?v={$ver}";
        }
        return rtrim($prefix, '/') . "/assets/js/dynamic-event-calendar.js?v={$ver}";
    }
}

if (!function_exists('renderDynamicCalendarScript')) {
    /**
     * Render the HTML <script> tag for dynamic-event-calendar.js with smart cache-busting.
     */
    function renderDynamicCalendarScript(?string $assetPrefix = null, bool $defer = false): string
    {
        $url = htmlspecialchars(getDynamicCalendarJsUrl($assetPrefix), ENT_QUOTES, 'UTF-8');
        $deferAttr = $defer ? ' defer' : '';
        return "<script src=\"{$url}\"{$deferAttr}></script>\n";
    }
}

if (!function_exists('renderDynamicCalendarAssets')) {
    /**
     * Render dynamic calendar script tag for inclusion in views or layouts.
     */
    function renderDynamicCalendarAssets(?string $assetPrefix = null): string
    {
        return renderDynamicCalendarScript($assetPrefix);
    }
}

if (!function_exists('renderDynamicCalendarInit')) {
    /**
     * Programmatic initializer for dynamic-event-calendar.js if custom field selectors are needed.
     */
    function renderDynamicCalendarInit(array $options = []): string
    {
        $optJson = !empty($options) ? json_encode($options) : '{}';
        return "<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.initDynamicEventCalendar === 'function') {
        window.initDynamicEventCalendar({$optJson});
    }
});
</script>\n";
    }
}

if (!function_exists('renderSchedulePeriodInit')) {
    /**
     * Programmatic initializer for schedule year/month period selection from dynamic-event-calendar.js.
     */
    function renderSchedulePeriodInit(array $options = []): string
    {
        $optJson = !empty($options) ? json_encode($options) : '{}';
        return "<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.initSchedulePeriod === 'function') {
        window.initSchedulePeriod({$optJson});
    }
});
</script>\n";
    }
}
