<?php

declare(strict_types=1);

if (!function_exists('e')) {
    /** Global HTML escaping helper. */
    function e(mixed $value): string {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('get_club_logo_url')) {
    /** Resolve club logo URL or default fallback. */
    function get_club_logo_url($logo): string
    {
        $logo = trim((string) $logo);
        $prefix = '../';
        if ($logo === '') {
            return "{$prefix}assets/images/logo/Final-Logo.png";
        }

        $cleanLogo = basename(ltrim($logo, '/\\'));
        $baseDir   = __DIR__ . '/../../';

        $logoPathMap = [
            'uploads/clubs/'      => "{$baseDir}uploads/clubs/{$cleanLogo}",
            'assets/images/logo/' => "{$baseDir}assets/images/logo/{$cleanLogo}",
            ''                    => "{$baseDir}{$cleanLogo}",
        ];

        $escapedLogo = htmlspecialchars($cleanLogo, ENT_QUOTES, 'UTF-8');

        foreach ($logoPathMap as $urlPath => $fullFileSystemPath) {
            if (file_exists($fullFileSystemPath)) {
                return "{$prefix}{$urlPath}{$escapedLogo}";
            }
        }

        return "{$prefix}uploads/clubs/{$escapedLogo}";
    }
}

if (!function_exists('getPaymentState')) {
    /** Determine normalized payment state: 'unpaid', 'pending', 'paid', 'not_joined'. */
    function getPaymentState(array $row): string {
        $isJoined = (int) ($row['is_joined'] ?? 0);
        if ($isJoined <= 0) {
            return 'not_joined';
        }

        $payStatus = $row['pay_status'] ?? '';
        $payApproved = $row['pay_approved'] ?? '';

        if ($payStatus === 'Y') {
            return !empty($payApproved) ? 'paid' : 'pending';
        }

        return 'unpaid';
    }
}

if (!function_exists('resolveTournamentImage')) {
    /** Resolve image path with static caching to minimize file_exists checks. */
    function resolveTournamentImage(?string $image, string $prefix, string $baseDir): string {
        if (!empty($image) && (strpos($image, 'http://') === 0 || strpos($image, 'https://') === 0)) {
            return e($image);
        }

        $cleanBanner = !empty($image) ? basename(ltrim($image, '/\\')) : '';
        if ($cleanBanner !== '') {
            static $bannerCache = [];
            if (isset($bannerCache[$cleanBanner])) {
                return $prefix . $bannerCache[$cleanBanner];
            }

            $searchPaths = [
                'admin/assets/images/tournaments_banner/',
                'assets/images/tournaments_banner/',
                'uploads/tournaments/'
            ];

            foreach ($searchPaths as $path) {
                if (file_exists($baseDir . $path . $cleanBanner)) {
                    $relPath = $path . e($cleanBanner);
                    $bannerCache[$cleanBanner] = $relPath;
                    return $prefix . $relPath;
                }
            }
        }

        return $prefix . 'assets/images/default.jpg';
    }
}

if (!function_exists('formatEventQuote')) {
    /** Trim description into an 8-word quote snippet. */
    function formatEventQuote(?string $description): string {
        $words = explode(' ', strip_tags($description ?? ''));
        $slice = array_slice($words, 0, 8);
        return e(implode(' ', $slice) . (count($words) > 8 ? '...' : ''));
    }
}

if (!function_exists('prepareTournamentData')) {
    /** Centralized tournament data preparation. */
    function prepareTournamentData(array $row, string $prefix, string $baseDir, DateTimeZone $tzEst, DateTimeImmutable $nowEst): array {
        $eventDate = !empty($row['EVENT_DATE']) ? date("d M Y", strtotime($row['EVENT_DATE'])) : '';
        $eventTime = !empty($row['EVENT_TIME']) ? date("h:i A", strtotime($row['EVENT_TIME'])) : '';

        // Registration Open / Closed
        $isRegistrationOpen = true;
        if (!empty($row['CANCEL_DATE'])) {
            try {
                $cTime = !empty($row['CANCEL_TIME']) ? $row['CANCEL_TIME'] : '10:00:00';
                $cancelEst = new DateTimeImmutable($row['CANCEL_DATE'] . ' ' . $cTime, $tzEst);
                if ($nowEst >= $cancelEst) {
                    $isRegistrationOpen = false;
                }
            } catch (\Throwable) {
                $isRegistrationOpen = true;
            }
        }

        // Draw Live / Announcement Date
        $isDrawLive = true;
        $drawDisplayDate = '';
        if (!empty($row['DRAW_ANNOUNCEMENT'])) {
            try {
                $drawEst = new DateTimeImmutable($row['DRAW_ANNOUNCEMENT'] . ' 10:00:00', $tzEst);
                $drawDisplayDate = $drawEst->format('d M Y');
                if ($nowEst < $drawEst) {
                    $isDrawLive = false;
                }
            } catch (\Throwable) {
                $isDrawLive = true;
            }
        }

        $cupName = e($row['CUP_NAME'] ?: ($row['HOST_NAME'] ?? ''));
        $joinedCount = (int) ($row['joined_count'] ?? 0);
        $maxTeams = (isset($row['MAX_TEAMS']) && !empty($row['MAX_TEAMS'])) ? e((string) $row['MAX_TEAMS']) : '';

        return [
            'id'                 => (int) ($row['ID'] ?? 0),
            'cupName'            => $cupName,
            'eventCategory'      => e($row['EVENT_CATEGORY'] ?? ''),
            'genderCategory'     => e($row['GENDER_CATEGORY'] ?? ''),
            'eventType'          => e($row['EVENT_TYPE'] ?? ''),
            'eventVenue'         => e($row['EVENT_VENUE'] ?? ''),
            'joinedCount'        => $joinedCount,
            'maxTeams'           => $maxTeams,
            'eventCost'          => e((string) ($row['EVENT_COST'] ?? $row['TOTAL_PLAYER_COST'] ?? $row['AMOUNT'] ?? '40')),
            'shuttleType'        => e((string) ($row['SHUTTLE_TYPE'] ?? 'Feather')),
            'amount'             => e((string) ($row['AMOUNT'] ?? '40.00')),
            'quote'              => formatEventQuote($row['EVENT_DESCRIPTION'] ?? ''),
            'date'               => $eventDate,
            'time'               => $eventTime,
            'imgPath'            => resolveTournamentImage($row['IMGAE'] ?? '', $prefix, $baseDir),
            'paymentState'       => getPaymentState($row),
            'isRegistrationOpen' => $isRegistrationOpen,
            'isDrawLive'         => $isDrawLive,
            'drawDisplayDate'    => e($drawDisplayDate),
        ];
    }
}

if (!function_exists('render_scrollable_modal')) {
    /** Generic Scrollable Modal Component Helper. */
    function render_scrollable_modal(string $modalId, string $defaultTitle, string $dialogClass = ''): void {
        $labelId = $modalId . 'Label';
        $bodyId  = $modalId . 'Body';
        ?>
        <div class="modal fade" id="<?= $modalId ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="<?= $labelId ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable <?= $dialogClass ?>">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="<?= $labelId ?>"><?= htmlspecialchars($defaultTitle, ENT_QUOTES, 'UTF-8') ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="<?= $bodyId ?>">
                        <p class="text-muted text-center my-3">Loading...</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
