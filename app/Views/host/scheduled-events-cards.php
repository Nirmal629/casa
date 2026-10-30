<?php

$isPlayer = isset($isPlayer) ? (bool) $isPlayer : (isset($userType) ? strcasecmp($userType, 'Player') === 0 : (isset($_SESSION['usertype']) && strcasecmp($_SESSION['usertype'], 'Player') === 0));

if (!isset($events) || !is_array($events)) {
    $currentYear = $year ?? (int) date('Y');
    $currentMonth = $month ?? (int) date('n');
    $eventCategory = $eventCategory ?? null;
    $hostController = new \App\Controllers\HostController();
    $hostId = (int) ($hostId ?? ($_SESSION['user_id'] ?? 0));
    $events = $hostId > 0 ? $hostController->getEventData($hostId, $currentYear, $currentMonth, $eventCategory, $isPlayer)
        : [];
}

if (!empty($events)):
    foreach ($events as $event):
        $event_id = (int) $event['ID'];
        $host_name = $event['HOST_NAME'] ?? '';

        $eventDateStr = $event['EVENT_DATE'] ?? date('Y-m-d');
        $eventStartTimeStr = $event['EVENT_TIME'] ?? '00:00:00';
        $eventEndTimeStr = $event['TO_TIME'] ?? '';

        $eventDateTime = strtotime($eventDateStr . ' ' . $eventStartTimeStr);
        $event_date_formatted = date('D, d M Y • h:i A', $eventDateTime);
        $event_toTime_formatted = !empty($eventEndTimeStr) ? date('h:i A', strtotime($eventEndTimeStr)) : '';

        // Expiration check via unified timezone-aware HostController/EventModel
        $isExpired = isset($hostController) && method_exists($hostController, 'isEventExpired')
            ? $hostController->isEventExpired($event)
            : (time() >= (!empty($eventEndTimeStr) ? (strtotime($eventDateStr . ' ' . $eventEndTimeStr) + (strtotime($eventDateStr . ' ' . $eventEndTimeStr) < $eventDateTime ? 86400 : 0)) : ($eventDateTime + 7200)));


        // Slot Tag Calculation (e.g. WEEKEND MORNING, WEEKDAY EVENING)
        $dayOfWeek = (int) date('w', $eventDateTime);
        $isWeekend = ($dayOfWeek === 0 || $dayOfWeek === 6);
        $hourOfDay = (int) date('H', $eventDateTime);
        $periodOfDay = $hourOfDay < 12 ? 'MORNING' : ($hourOfDay < 17 ? 'AFTERNOON' : 'EVENING');
        $slotLabel = ($isWeekend ? 'WEEKEND ' : 'WEEKDAY ') . $periodOfDay;

        $event_venue = $event['EVENT_VENUE'] ?? 'Venue';
        $event_cost = $event['EVENT_COST'] ?? '0';
        $event_court_raw = trim((string) ($event['EVENT_DISCOUNT'] ?? ''));
        $courts_confirmed = (int) ($event['COURTS_CONFIRMED'] ?? 0);
        if (!empty($event_court_raw) && $event_court_raw !== '0') {
            $event_court = preg_replace('/^courts?[:\s]*/i', '', $event_court_raw);
        } elseif ($courts_confirmed > 0) {
            $event_court = (string) $courts_confirmed;
        } else {
            $event_court = 'NA';
        }

        $raw_currency = $event['EVENT_CURRENCY'] ?? '';
        $event_currency = ($raw_currency === 'CAD' || empty($raw_currency)) ? '$' : $raw_currency;
        $event_category = $event['EVENT_CATEGORY'] ?? 'Game';
        $gender_category = $event['GENDER_CATEGORY'] ?? 'Open';
        $gender_skill_level = $event['GENDER_SKILL_LEVEL'] ?? 'All';
        $event_type = $event['EVENT_TYPE'] ?? 'Public';

        $event_canceldate = !empty($event['CANCEL_DATE']) ? date('D, d M Y', strtotime($event['CANCEL_DATE'])) : (!empty($event['EVENT_DATE']) ? date('D, d M Y', strtotime($event['EVENT_DATE'])) : '');
        $event_cancelTime = !empty($event['CANCEL_TIME']) ? date('h:i A', strtotime($event['CANCEL_TIME'])) : '';
        $freezeDisplay = trim($event_canceldate . ($event_cancelTime !== '' ? ' • ' . $event_cancelTime : ''));
        if (empty($freezeDisplay)) {
            $freezeDisplay = 'At match start';
        }

        $jsonStringy = htmlspecialchars(
            json_encode($event, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
            ENT_QUOTES,
            'UTF-8'
        );

        $words = explode(" ", (string) ($event['EVENT_MESSAGE'] ?? ''));
        $wrapped_message = implode(" ", array_slice($words, 0, 9));
        if (count($words) > 9) {
            $wrapped_message .= " ...";
        }

        $countTotalJoin = (int) ($event['total_join'] ?? 0);
        $countTotalConfirmed = (int) ($event['total_confirmed'] ?? 0);
        $eventData = $event['formatted_event_data'] ?? '';

        // Status text & badge logic
        $confirmedCourts = is_numeric($event_court) ? (int) $event_court : 0;
        $spotsConfirmedCourt = $confirmedCourts * 6;
        $courtStatusText = !empty($event['JOIN_MESSAGE'])
            ? (string) $event['JOIN_MESSAGE']
            : ($confirmedCourts > 0 ? "{$confirmedCourts} court" . ($confirmedCourts > 1 ? 's' : '') . " confirmed ({$spotsConfirmedCourt} max capacity)" : "0 court confirmed, open spots available for first court");
        $courtStatusBadge = $isExpired ? 'COMPLETED' : ($countTotalConfirmed >= 4 ? 'COURT ACTIVE' : 'PENDING MIN 4');

        // Player specific calculations
        $userJoinedCount = (int) ($event['user_joined_count'] ?? 0);
        $userConfirmed = (string) ($event['user_confirmed'] ?? '');
        $userStatus = (string) ($event['user_join_status'] ?? '');
        $isUserJoinedActive = ($userJoinedCount > 0 && $userStatus === 'Y');

        $bgClass = '';
        if ($isUserJoinedActive) {
            if ($userConfirmed === 'Y') {
                $bgClass = '#1987543b';
            } else {
                $bgClass = '#ffc10738';
            }
        }

        if ($isExpired) {
            if ($isUserJoinedActive) {
                $action = "<span class='badge bg-secondary session-rsvp-btn session-btn-completed' title='You attended this session'><i class='fa-solid fa-circle-check me-1'></i>Completed</span>";
            } else {
                $action = "<span class='badge bg-secondary session-rsvp-btn session-btn-expired' title='This session has ended and is expired'><i class='fa-solid fa-ban me-1'></i>Expired</span>";
            }
        } else {
            if ($event_type === "Public") {
                $action = "<button type='button' class='" . ($isUserJoinedActive ? 'actionC btn-cancel' : 'actionJC btn-join') . " session-rsvp-btn' data-id='$event_id'>" . ($isUserJoinedActive ? '<i class="fa-solid fa-xmark me-1"></i>Cancel' : '<i class="fa-solid fa-circle-plus me-1"></i>Join Game') . "</button>";
            } else {
                if ($userJoinedCount > 0) {
                    if ($userStatus === 'N') {
                        $action = "<button type='button' class='actionAC session-rsvp-btn btn-accept' data-id='$event_id'><i class='fa-solid fa-check me-1'></i>Accept</button>";
                    } else {
                        $action = "<button type='button' class='actionC session-rsvp-btn btn-cancel' data-id='$event_id'><i class='fa-solid fa-xmark me-1'></i>Cancel</button>";
                    }
                } else {
                    $action = "<span class='badge bg-secondary session-rsvp-btn' style='cursor:default;'>Not Invited</span>";
                }
            }
        }
        $costFormatted = !empty($event_cost) ? htmlspecialchars($event_currency . ' ' . (is_numeric($event_cost) ? number_format((float) $event_cost, 2) : $event_cost)) : 'Free';
        ?>
        <article class='discoverGames_card <?= $isPlayer ? 'player_cards sports-session-card' : '' ?> session-card <?= $isExpired ? 'session-card-expired' : '' ?>' data-event-id='<?= $event_id ?>' style='<?= (!empty($bgClass) && $isPlayer) ? 'background-color:' . $bgClass . ';' : '' ?>'>
            <!-- Top Sport Header Bar -->
            <header class='session-card-header'>
                <div class='session-card-title-wrap'>
                    <?php if ($isExpired): ?>
                        <span class='session-expired-badge' title='Event Concluded / Expired'>
                            <i class='fa-solid fa-clock-rotate-left'></i>
                            <span>EXPIRED</span>
                        </span>
                    <?php else: ?>
                        <span class='session-live-ping' title='Active Session'>
                            <span class='ping-wave'></span>
                            <span class='ping-dot'></span>
                        </span>
                    <?php endif; ?>
                    <h2 class='session-card-title' title='<?= htmlspecialchars((string) $event_category . (!empty($gender_category) ? ' – ' . $gender_category : '')) ?>'>
                        <?= htmlspecialchars((string) $event_category) ?><?= !empty($gender_category) ? ' – ' . htmlspecialchars((string) $gender_category) : '' ?>
                    </h2>
                    <?php if (!$isPlayer): ?>
                        <span class='session-role-badge'>Host</span>
                    <?php endif; ?>
                </div>

                <?php if ($isPlayer): ?>
                    <!-- Player Header Action -->
                    <div class="session-header-right d-flex align-items-center gap-1">
                        <input type="hidden" id="data_<?= $event_id ?>" value="<?= $jsonStringy ?>" />
                        <input type="hidden" id="user_<?= $event_id ?>" value="<?= htmlspecialchars((string) ($_SESSION['user_id'] ?? '')) ?>" />
                        <input type="hidden" id="event_data_<?= $event_id ?>" value="<?= htmlspecialchars((string) $eventData, ENT_QUOTES) ?>" />
                    </div>
                <?php else: ?>
                    <!-- Modern Floating Action Dock (Host Actions) -->
                    <nav class='session-action-dock' aria-label='Session Actions'>
                        <!-- Invite / Add Players -->
                        <?php if ($isExpired): ?>
                            <span class='dock-btn dock-btn-invite disabled' title='Cannot invite to an expired session'>
                                <i class='fa fa-user-plus'></i>
                            </span>
                        <?php else: ?>
                            <span class='dock-btn dock-btn-invite view_btn' data-id='<?= $event_id ?>' title='Invite Players'>
                                <i class='fa fa-user-plus'></i>
                            </span>
                        <?php endif; ?>
                        <!-- Duplicate / Copy Details -->
                        <a href='javascript:void(0)' class='dock-btn dock-btn-copy copy_event' data-id='<?= $event_id ?>' title='Copy Details'>
                            <i class='fa-regular fa-copy'></i>
                        </a>
                        <!-- Edit Match -->
                        <a href='javascript:void(0)' class='dock-btn dock-btn-edit edit_btn' data-id='<?= $event_id ?>' title='Edit Session'>
                            <i class='fa-regular fa-pen-to-square'></i>
                        </a>
                        <!-- Delete Match -->
                        <a href='javascript:void(0)' class='dock-btn dock-btn-delete delete_btn' data-id='<?= $event_id ?>' title='Delete Session'>
                            <i class='fa-regular fa-trash-can'></i>
                        </a>

                        <!-- Hidden Payload Data for Existing Modals & Handlers -->
                        <input type='hidden' id='data_<?= $event_id ?>' value='<?= $jsonStringy ?>'/>
                        <input type='hidden' id='event_data_<?= $event_id ?>' value='<?= htmlspecialchars((string) $eventData, ENT_QUOTES) ?>'/>
                    </nav>
                <?php endif; ?>
            </header>

            <!-- Card Body Content -->
            <div class='session-card-body'>
        
                <!-- Top Columns: Slot Pass / Roster Locks & Stats Boxes -->
                <div class='session-card-columns'>
            
                    <!-- Left Column: Slot & Venue + Roster Locks -->
                    <div class='session-col-left'>
                        <!-- Weekend Slot Pass -->
                        <div class='session-slot-pass'>
                            <div class='session-slot-header'>
                                <div class='session-slot-badge'>
                                    <i class='fa-regular fa-clock' style='font-size: 9px;'></i>
                                    <span><?= htmlspecialchars($slotLabel) ?></span>
                                </div>
                                <div class='session-venue-pill' title='<?= htmlspecialchars((string) $event_venue) ?>'>
                                    <i class='fa-solid fa-location-dot' style='color: #e11d48; font-size: 9px;'></i>
                                    <span><?= htmlspecialchars((string) $event_venue) ?></span>
                                </div>
                            </div>
                            <p class='session-schedule-text'>
                                <?= $event_date_formatted ?> <?= $event_toTime_formatted !== '' ? 'to ' . $event_toTime_formatted : '' ?>
                            </p>
                        </div>

                        <!-- Roster Locks Banner -->
                        <div class='session-roster-locks'>
                            <div class='session-lock-icon'>
                                <i class='fa-solid fa-lock'></i>
                            </div>
                            <div class='session-locks-content'>
                                <p class='session-locks-title'>
                                    ROSTER LOCKS: <span><?= $event_canceldate ?><?= $event_cancelTime !== '' ? ', ' . $event_cancelTime : '' ?></span>
                                </p>
                                <p class='session-locks-sub'>(Free cancellation closes)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Joined & Confirm Stats + Action Area -->
                    <div class='session-col-right'>
                        <div class='session-stats-row'>
                            <!-- JOINED Box -->
                            <div class='session-stat-card session-stat-joined' tabindex='0' data-bs-toggle='popover' data-bs-trigger='hover' data-bs-placement='top' data-bs-content='Total Joined: <?= $countTotalJoin ?>'>
                                <span class='stat-label'>JOINED</span>
                                <span class='stat-value'><?= $countTotalJoin ?></span>
                            </div>
                            <!-- CONFIRM Box -->
                            <div class='session-stat-card session-stat-confirm' tabindex='0' data-bs-toggle='popover' data-bs-trigger='hover' data-bs-placement='top' data-bs-content='Total Confirmed: <?= $countTotalConfirmed ?>'>
                                <span class='stat-label'>CONFIRM</span>
                                <span class='stat-value'><?= $countTotalConfirmed ?></span>
                            </div>
                        </div>

                        <?php if ($isPlayer): ?>
                            <!-- Player Actions Strip -->
                            <div class="session-player-actions">
                                <div class="session-player-spot-badge">
                                    <div class="session-player-spot-text">
                                        <i class="fa-solid fa-users" style="color: #2563eb;"></i>
                                        <span>Players: <strong style="color: #1e3a8a;"><?= $countTotalJoin ?></strong> joined</span>
                                    </div>
                                    <div class="view_btnn session-player-eye-btn" title="View Joined Players" data-id="<?= $event_id ?>" style="cursor: pointer;">
                                        <i class="fa-regular fa-eye"></i>
                                    </div>
                                </div>

                                <?= $action ?>
                            </div>
                        <?php else: ?>
                            <!-- Host Players Button (Triggers Existing View Joined Modal) -->
                            <button type='button' class='session-players-btn joined_btn' data-id='<?= $event_id ?>' title='View Joined Players'>
                                <span>Players</span>
                                <i class='fa-regular fa-eye'></i>
                            </button>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- Info Strip: Player Payment / Host Note -->
                <?php if ($isPlayer): ?>
                    <div class="session-info-strip" title="<?= htmlspecialchars(!empty($event['EVENT_MESSAGE']) ? $event['EVENT_MESSAGE'] : 'Feather birdie included. Pay at casaclubpayment1@gmail.com') ?>">
                        <i class="fa-solid fa-circle-info session-info-icon"></i>
                        <div class="session-info-text">
                            <span class="session-info-desc">
                                <?php
                                $rawMsg = !empty($event['EVENT_MESSAGE']) ? $event['EVENT_MESSAGE'] : 'Feather birdie included. Pay at casaclubpayment1@gmail.com';
                                $cleanMsg = rtrim($rawMsg, '.');
                                if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $cleanMsg, $emailMatches)) {
                                    $foundEmail = $emailMatches[1];
                                    $beforeEmail = substr($cleanMsg, 0, strpos($cleanMsg, $foundEmail));
                                    $afterEmail = substr($cleanMsg, strpos($cleanMsg, $foundEmail) + strlen($foundEmail));
                                    echo htmlspecialchars($beforeEmail);
                                    echo '<span class="session-email-copy copy-email-btn" data-email="' . htmlspecialchars($foundEmail, ENT_QUOTES) . '" title="Click to copy email">';
                                    echo '<span>' . htmlspecialchars($foundEmail) . '</span>';
                                    echo ' <i class="fa-regular fa-copy"></i>';
                                    echo '</span>';
                                    echo htmlspecialchars($afterEmail);
                                } else {
                                    echo htmlspecialchars($cleanMsg);
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                <?php elseif (!empty($wrapped_message)): ?>
                    <div class='session-info-strip'>
                        <i class='fa-solid fa-circle-info'></i>
                        <span title='<?= htmlspecialchars((string) ($event['EVENT_MESSAGE'] ?? '')) ?>'>
                            <?= htmlspecialchars($wrapped_message) ?>
                        </span>
                    </div>
                <?php endif; ?>

                <!-- 4-Badge Grid: Level, Court, Cost, Type -->
                <div class='session-badges-grid'>
                    <div class='session-badge-item session-badge-level' title='Skill Level'>
                        <i class='fa-solid fa-gauge' style='color: #64748b; font-size: 10px;'></i>
                        <span><?= htmlspecialchars((string) $gender_skill_level) ?></span>
                    </div>
                    <div class='session-badge-item session-badge-court' title='Court Assignment'>
                        <i class='fa-solid fa-table-cells-large' style='color: #64748b; font-size: 10px;'></i>
                        <span>Court: <?= htmlspecialchars((string) $event_court) ?></span>
                    </div>
                    <div class='session-badge-item session-badge-price' title='Session Cost'>
                        <i class='fa-solid fa-ticket' style='color: #059669; font-size: 10px;'></i>
                        <span><?= $costFormatted ?></span>
                    </div>
                    <div class='session-badge-item session-badge-type <?= strtolower((string) $event_type) === 'public' ? 'public' : 'invite' ?>' title='Event Type'>
                        <i class='fa-solid <?= strtolower((string) $event_type) === 'public' ? 'fa-globe' : 'fa-lock' ?>' style='font-size: 9px;'></i>
                        <span><?= htmlspecialchars((string) $event_type) ?></span>
                    </div>
                </div>

                <!-- Bottom Court Status Bar with Radar Pulse -->
                <?php if ($isExpired): ?>
                    <div class='session-court-status session-court-status-expired'>
                        <div class='session-court-status-left'>
                            <i class='fa-solid fa-flag-checkered' style='color: #64748b; font-size: 11px; margin-right: 4px;'></i>
                            <span class='session-court-status-text session-court-status-expired-text'>
                                <?= $confirmedCourts > 0 ? "Session ended • {$confirmedCourts} court" . ($confirmedCourts > 1 ? 's' : '') . " completed" : "Session ended • Concluded" ?>
                            </span>
                        </div>
                        <span class='session-court-badge session-court-badge-expired'>
                            COMPLETED
                        </span>
                    </div>
                <?php else: ?>
                    <div class='session-court-status'>
                        <div class='session-court-status-left'>
                            <span class='session-radar-beacon'>
                                <span class='radar-ping'></span>
                                <span class='radar-core'></span>
                            </span>
                            <span class='session-court-status-text'>
                                <?= htmlspecialchars($courtStatusText) ?>
                            </span>
                        </div>
                        <span class='session-court-badge'>
                            <?= htmlspecialchars($courtStatusBadge) ?>
                        </span>
                    </div>
                <?php endif; ?>

            </div>
        </article>
        <?php
    endforeach;
else:
    ?>
    <div class='empty-schedule-card text-center w-100 py-4 px-3 my-2'
        style='max-width: 440px; margin: 15px auto !important; background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%); border: 1.5px dashed #cbd5e1; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);'>
        <div class='empty-icon-wrap mx-auto mb-2 d-flex align-items-center justify-content-center'
            style='width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%); color: #0284c7; box-shadow: 0 2px 8px rgba(2,132,199,0.15);'>
            <svg xmlns='http://www.w3.org/2000/svg' width='22' height='22' fill='currentColor' viewBox='0 0 16 16'>
                <path d='M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z'/>
                <path d='M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1z'/>
            </svg>
        </div>
        <h6 class='fw-bold mb-1' style='color: #1e293b; font-size: 0.95rem; letter-spacing: -0.2px;'>No Events Found</h6>
        <p class='text-muted mb-2' style='font-size: 0.82rem; line-height: 1.45; max-width: 320px; margin: 0 auto;'>No games scheduled for this selection. Try changing the category or month.</p>
        <div class='d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill' style='background: #f1f5f9; color: #64748b; font-size: 0.72rem; font-weight: 500; border: 1px solid #e2e8f0;'>
            <span>📅 Check back soon for new games</span>
        </div>
    </div>
<?php endif; ?>
