<?php
$eventsByDay = $eventsByDay ?? [];
$venues = $venues ?? [];
$categories = $categories ?? [];
$userType = (string) ($userType ?? ($_SESSION['usertype'] ?? ''));

$subUserModel = $subUserModel ?? new \App\Models\UserModel();
$subGenders = $subUserModel->getAllUniqueGenders();
if (!in_array('Mix', $subGenders, true)) {
    $subGenders[] = 'Mix';
}
$subLevels = $subUserModel->getAllUniqueVerifiedLevels();
if (!in_array('Mix', $subLevels, true)) {
    $subLevels[] = 'Mix';
}
$cardCssPath = __DIR__ . '/../css/sports-session-card.css';
$cardCssVer = file_exists($cardCssPath) ? (string) filemtime($cardCssPath) : '1.0';
?>
<link rel="stylesheet" href="/app/Views/css/sports-session-card.css?v=<?= $cardCssVer ?>">

<section class="weekly-schedule-section">
    <div class="custom_card">
        <h6 class="card_heading mb-3">Weekly Game Schedule</h6>

        <?php foreach ($eventsByDay as $day => $dayEvents): ?>
            <?php if (!empty($dayEvents)): ?>
                <div class="mb-4">
                    <h4 class="text-uppercase fw-bold mb-3"><?= htmlspecialchars((string) $day) ?></h4>
                    <div class="discoverGames_wraper subscriptionWrapper">
                        <?php foreach ($dayEvents as $event): 
                            $event_id = (int) ($event['ID'] ?? 0);
                            $event_day = strtoupper(trim((string) ($event['DAY'] ?? $day)));
                            $event_category = $event['EVENT_CATEGORY'] ?? 'Game';
                            $gender_category = $event['GENDER_CATEGORY'] ?? '';
                            $event_message = html_entity_decode((string) ($event['EVENT_MESSAGE'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $event_description = html_entity_decode((string) ($event['EVENT_DESCRIPTION'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $jsonStringy = htmlspecialchars(
                                json_encode($event, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),
                                ENT_QUOTES,
                                'UTF-8'
                            );

                            $eventTimeFrom = !empty($event['EVENT_TIME']) ? date('h:i A', strtotime($event['EVENT_TIME'])) : '00:00';
                            $eventTimeTo = !empty($event['TO_TIME']) ? date('h:i A', strtotime($event['TO_TIME'])) : '';
                            $event_venue = $event['EVENT_VENUE'] ?? 'Venue';
                            $event_cost = $event['EVENT_COST'] ?? '0';
                            $event_currency_raw = trim((string) ($event['EVENT_CURRENCY'] ?? ''));
                            $event_currency = ($event_currency_raw === 'CAD' || empty($event_currency_raw)) ? '$' : $event_currency_raw;
                            $event_type = $event['EVENT_TYPE'] ?? 'Public';
                            $gender_skill_level = $event['GENDER_SKILL_LEVEL'] ?? 'All';

                            // Court extraction
                            $event_court_raw = trim((string) ($event['EVENT_DISCOUNT'] ?? ($event['COURTS_CONFIRMED'] ?? '')));
                            if (!empty($event_court_raw) && $event_court_raw !== '0') {
                                $event_court = preg_replace('/^courts?[:\s]*/i', '', $event_court_raw);
                            } else {
                                $event_court = '1';
                            }
                            $confirmedCourts = is_numeric($event_court) ? (int) $event_court : 1;

                            // Slot Tag Calculation (e.g. WEEKDAY EVENING, WEEKEND MORNING)
                            $isWeekend = in_array($event_day, ['SATURDAY', 'SUNDAY'], true);
                            $startHour = !empty($event['EVENT_TIME']) ? (int) date('H', strtotime($event['EVENT_TIME'])) : 18;
                            $periodOfDay = $startHour < 12 ? 'MORNING' : ($startHour < 17 ? 'AFTERNOON' : 'EVENING');
                            $slotLabel = ($isWeekend ? 'WEEKEND ' : 'WEEKDAY ') . $periodOfDay;

                            // Day Abbreviation for stat box (MON, TUE, WED, etc.)
                            $dayAbbr = substr($event_day, 0, 3);

                            // Cancel / freeze time display nicely capitalized
                            $cancelTimeRaw = $event['CANCEL_TIME'] ?? '';
                            $cancelTimeFormatted = !empty($cancelTimeRaw) ? date('h:i A', strtotime($cancelTimeRaw)) : '';
                            $dayNicename = ucfirst(strtolower($event_day));
                            $freezeDisplay = $cancelTimeFormatted !== '' ? "Every {$dayNicename}, {$cancelTimeFormatted}" : "Every {$dayNicename} (Prior to start)";

                            // Cost formatted
                            $costFormatted = !empty($event_cost) && (float) $event_cost > 0
                                ? htmlspecialchars($event_currency . ' ' . (is_numeric($event_cost) ? number_format((float) $event_cost, 2) : $event_cost))
                                : 'Free';

                            // Court status text
                            $courtStatusText = $confirmedCourts > 1
                                ? "{$confirmedCourts} courts configured for recurring games"
                                : "{$confirmedCourts} court configured for recurring game";
                        ?>

                        <article class="discoverGames_card discoverGames_card_sub session-card sports-session-card" data-event-id="<?= $event_id ?>">
                            <!-- Top Sport Header Bar -->
                            <header class="session-card-header">
                                <div class="session-card-title-wrap">
                                    <span class="session-live-ping" title="Active Weekly Template">
                                        <span class="ping-wave"></span>
                                        <span class="ping-dot"></span>
                                    </span>
                                    <h2 class="session-card-title" title="<?= htmlspecialchars((string) $event_category . (!empty($gender_category) ? ' – ' . $gender_category : '')) ?>">
                                        <?= htmlspecialchars((string) $event_category) ?><?= !empty($gender_category) ? ' – ' . htmlspecialchars((string) $gender_category) : '' ?>
                                    </h2>
                                    <span class="session-role-badge">WEEKLY</span>
                                </div>

                                <!-- Floating Action Dock -->
                                <nav class="session-action-dock" aria-label="Template Actions">
                                    <span class="dock-btn dock-btn-info" title="<?= htmlspecialchars((string) $event_description, ENT_QUOTES) ?>" style="cursor: help;">
                                        <i class="fa-solid fa-info"></i>
                                    </span>
                                    <span class="dock-btn dock-btn-invite view_btn" data-id="<?= $event_id ?>" title="Invite Players" style="cursor: pointer;">
                                        <i class="fa fa-user-plus"></i>
                                    </span>
                                    <a href="javascript:void(0)" class="dock-btn dock-btn-edit edit_btn" data-id="<?= $event_id ?>" title="Edit Template">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    <!-- Hidden Payload Data for Modals & Handlers -->
                                    <input type="hidden" id="data_<?= $event_id ?>" value="<?= $jsonStringy ?>"/>
                                </nav>
                            </header>

                            <!-- Card Body Content -->
                            <div class="session-card-body">
                                <!-- Top Columns: Slot Pass / Roster Locks & Stats Boxes -->
                                <div class="session-card-columns">
                                    <!-- Left Column: Slot & Venue + Roster Locks -->
                                    <div class="session-col-left">
                                        <!-- Slot Pass -->
                                        <div class="session-slot-pass">
                                            <div class="session-slot-header">
                                                <div class="session-slot-badge">
                                                    <i class="fa-regular fa-clock" style="font-size: 9px;"></i>
                                                    <span><?= htmlspecialchars($slotLabel) ?></span>
                                                </div>
                                                <div class="session-venue-pill" title="<?= htmlspecialchars((string) $event_venue) ?>">
                                                    <i class="fa-solid fa-location-dot" style="color: #e11d48; font-size: 9px;"></i>
                                                    <span><?= htmlspecialchars((string) $event_venue) ?></span>
                                                </div>
                                            </div>
                                            <p class="session-schedule-text">
                                                Every <?= htmlspecialchars(ucfirst(strtolower($event_day))) ?> • <?= $eventTimeFrom ?><?= $eventTimeTo !== '' ? ' to ' . $eventTimeTo : '' ?>
                                            </p>
                                        </div>

                                        <!-- Roster Locks Banner -->
                                        <div class="session-roster-locks">
                                            <div class="session-lock-icon">
                                                <i class="fa-solid fa-lock"></i>
                                            </div>
                                            <div class="session-locks-content">
                                                <p class="session-locks-title">
                                                    ROSTER LOCKS: <span><?= htmlspecialchars($freezeDisplay) ?></span>
                                                </p>
                                                <p class="session-locks-sub">(Free cancellation closes)</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Column: Day, Recurs & Create Action -->
                                    <div class="session-col-right">
                                        <div class="session-stats-row">
                                            <!-- Day Stat Card -->
                                            <div class="session-stat-card session-stat-joined" title="Template Recurring Day">
                                                <span class="stat-label">DAY</span>
                                                <span class="stat-value" style="font-size: 16px; font-weight: 800; letter-spacing: 0.5px;"><?= htmlspecialchars($dayAbbr) ?></span>
                                            </div>
                                            <!-- Recurs Stat Card -->
                                            <div class="session-stat-card session-stat-confirm" title="Recurrence Frequency">
                                                <span class="stat-label">RECURS</span>
                                                <span class="stat-value" style="font-size: 13px; font-weight: 800; letter-spacing: 0.5px;">WEEKLY</span>
                                            </div>
                                        </div>

                                        <!-- Create Game Button -->
                                        <button type="button" id="create_btn" class="session-players-btn session-create-sub-btn btn-success" data-id="<?= $event_id ?>" title="Create Game from this Template">
                                            <i class="fa-solid fa-bolt me-1"></i>
                                            <span>Create</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Info Strip: Note / Payment details -->
                                <?php if (!empty($event_message)): ?>
                                    <div class="session-info-strip" title="<?= htmlspecialchars($event_message, ENT_QUOTES) ?>">
                                        <i class="fa-solid fa-circle-info session-info-icon"></i>
                                        <div class="session-info-text">
                                            <span class="session-info-desc">
                                                <?php
                                                $cleanMsg = rtrim($event_message, '.');
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
                                <?php endif; ?>

                                <!-- 4-Badge Grid: Level, Court, Cost, Type -->
                                <div class="session-badges-grid">
                                    <div class="session-badge-item session-badge-level" title="Skill Level">
                                        <i class="fa-solid fa-gauge" style="color: #64748b; font-size: 10px;"></i>
                                        <span><?= htmlspecialchars((string) $gender_skill_level) ?></span>
                                    </div>
                                    <div class="session-badge-item session-badge-court" title="Court Count">
                                        <i class="fa-solid fa-table-cells-large" style="color: #64748b; font-size: 10px;"></i>
                                        <span>Court: <?= htmlspecialchars((string) $event_court) ?></span>
                                    </div>
                                    <div class="session-badge-item session-badge-price" title="Session Cost">
                                        <i class="fa-solid fa-ticket" style="color: #059669; font-size: 10px;"></i>
                                        <span><?= $costFormatted ?></span>
                                    </div>
                                    <div class="session-badge-item session-badge-type <?= strtolower((string) $event_type) === 'public' ? 'public' : 'invite' ?>" title="Event Type">
                                        <i class="fa-solid <?= strtolower((string) $event_type) === 'public' ? 'fa-globe' : 'fa-lock' ?>" style="font-size: 9px;"></i>
                                        <span><?= htmlspecialchars((string) $event_type) ?></span>
                                    </div>
                                </div>

                                <!-- Bottom Court Status Bar with Radar Pulse -->
                                <div class="session-court-status">
                                    <div class="session-court-status-left">
                                        <span class="session-radar-beacon">
                                            <span class="radar-ping"></span>
                                            <span class="radar-core"></span>
                                        </span>
                                        <span class="session-court-status-text">
                                            <?= htmlspecialchars($courtStatusText) ?>
                                        </span>
                                    </div>
                                    <span class="session-court-badge">
                                        TEMPLATE ACTIVE
                                    </span>
                                </div>
                            </div>
                        </article>

                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>



<!-----View-modal------->
<section class="customModal_wrap hostgameview_modal_sub">
    <div class="customModal_body">
        <h6 class="customModal_head">View Game</h6>
        <button type="button" class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="row align-items-end g-2">
            <div class="col-md-3 col-12">
                <input type="text" name="search" id="search" placeholder="Enter text to search" class="form-text" />
                <input type="hidden" id="playdt" data-game-id="" data-host-id="" />
            </div>
        
            <div class="col-md-3 col-12">
                <label for="sgenderCategoryy" class="form-label">Gender Category<span>*</span></label>
                <select class="form-select" id="sgenderCategoryy">
                    <?php foreach ($subGenders as $gOpt): ?>
                        <option value="<?= htmlspecialchars($gOpt) ?>"><?= htmlspecialchars($gOpt === 'Kid' ? 'Kids' : $gOpt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        
            <div class="col-md-3 col-12">
                <label for="sgenderSkillLevell" class="form-label">Gender Skill Level<span>*</span></label>
                <select class="form-select" id="sgenderSkillLevell">
                    <?php foreach ($subLevels as $lvlOpt): ?>
                        <option value="<?= htmlspecialchars($lvlOpt) ?>"><?= htmlspecialchars($lvlOpt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="customModal_content">
            <hr />
            <div class="playerList-container" id="playerList_sub"></div>
        </div>
    </div>
</section>

<!---view joined --->
<section class="customModal_wrap hostgameviewjoined_modal">
    <div class="customModal_body">
        <h6 class="customModal_head">Joined Players</h6>
        <button type="button" class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="customModal_content">
            <hr />
            <div class="playerList-container" id="playerList_joined"></div>
        </div>
    </div>
</section>

<!-----Edit-modal--------->
<section class="customModal_wrap hostgameupdate_modal">
    <div class="customModal_body">
        <h6 class="customModal_head">Create Game</h6>
        <button type="button" class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="customModal_content">
            <form>
                <div class="row">
                    <div class="col-md-6 col-12 mb-3">
                        <label for="host-nameee" class="form-label">Host Name<span>*</span></label>
                        <input type="text" class="form-control" id="host-nameee" placeholder="Enter Full Name" value="">
                        <input type="hidden" id="evnt_id" value=""/>
                        <input type="hidden" id="evnt_idd" value=""/>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventCountryyy" class="form-label">Event Country<span>*</span></label>
                        <input type="text" class="form-control" id="eventCountryyy" placeholder="Event Country">
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventProvinceee" class="form-label">Event Province<span>*</span></label>
                        <input type="text" class="form-control" id="eventProvinceee" placeholder="Event Province">
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventCityyy" class="form-label">Event City<span>*</span></label>
                        <input type="text" class="form-control" id="eventCityyy" placeholder="Event City">
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventCurrencyyy" class="form-label">Event Currency<span>*</span></label>
                        <select class="form-select form-control" id="eventCurrencyyy" name="event_currency" required>
                            <option value="INR">INR</option>
                            <option value="CAD">CAD</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventVenueee" class="form-label">Event Venue<span>*</span></label>
                        <select class="form-select form-control" id="eventVenueee" name="eventVenue" required>
                            <?php foreach ($venues as $venue):
                                $venueName = trim((string) ($venue['NAME'] ?? $venue));
                                if ($venueName === '') continue;
                                $selected = ($venueName === (string) $event_venue) ? 'selected' : '';
                            ?>
                                <option value="<?= htmlspecialchars($venueName, ENT_QUOTES, 'UTF-8') ?>" <?= $selected ?>><?= htmlspecialchars($venueName, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventCategoryyy" class="form-label">Event Category<span>*</span></label>
                        <select class="form-select form-control" id="eventCategoryyy" aria-label="">
                            <?php if ($userType === 'Host'): ?>
                                <?php foreach ($categories as $category):
                                    $categoryName = trim((string) ($category['NAME'] ?? $category));
                                    if ($categoryName === '') continue;
                                    $selected = ($categoryName === (string) $event_category) ? 'selected' : '';
                                ?>
                                    <option value="<?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?>" <?= $selected ?>><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            <?php elseif ($userType === 'Trainer'): ?>
                                <option value="Badminton Training">Badminton Training</option>
                                <option value="Tennis Training">Tennis Training</option>
                                <option value="Cricket Training">Cricket Training</option>
                                <option value="Football Training">Football Training</option>
                                <option value="Badminton Game and Training">Badminton Game + Training</option>
                            <?php else: ?>
                                <option disabled selected>Please select a valid user type</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="genderCategoryyy" class="form-label">Gender Category<span>*</span></label>
                        <select class="form-select form-control" id="genderCategoryyy" aria-label="">
                            <option selected value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Mix">Mix</option>
                            <option value="Kid">Kids</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="genderSkillLevelll" class="form-label">Gender Skill Level<span>*</span></label>
                        <select class="form-select form-control" id="genderSkillLevelll" aria-label="">
                            <option selected value="Beginner">Beginner</option>
                            <option value="Amateur">Amateur</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Intermediate +">Intermediate+</option>
                            <option value="Advance">Advance</option>
                            <option value="Mix">Mix</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventTypeee" class="form-label">Event Type<span>*</span></label>
                        <select class="form-select form-control" id="eventTypeee" aria-label="">
                            <option selected value="Public">Public</option>
                            <option value="Invite">Invite Only</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventDateee" class="form-label">Event Date<span>*</span></label>
                        <input type="date" class="form-control" id="eventDateee" placeholder="Event Date">
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="eventTimeee" class="form-label">From Time<span>*</span></label>
                        <select class="form-control" id="eventTimeee" name="event_time" required></select>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="toTimeee" class="form-label">To Time<span>*</span></label>
                        <select class="form-control" id="toTimeee" name="to_Time" required></select>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="freezeDateee" class="form-label">Freeze Date<span>*</span></label>
                        <input type="date" class="form-control" id="freezeDateee" placeholder="Freeze Date">
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="freezeTimeee" class="form-label">Freeze Time<span>*</span></label>
                        <select class="form-control" id="freezeTimeee" name="freeze_time" required></select>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventDescriptionnn" class="form-label">Event Description<span>*</span></label>
                        <textarea class="form-control" id="eventDescriptionnn" rows="5" placeholder="Bring own bat, shoe, guards....."></textarea>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="eventMessageee" class="form-label">Event Message<span>*</span></label>
                        <textarea class="form-control" id="eventMessageee" rows="5" placeholder="For Participants to put note....."></textarea>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="eventCosttt" class="form-label">Event Cost ($)<span>*</span></label>
                        <input type="text" class="form-control" id="eventCosttt" placeholder="Event Cost">
                    </div>

                    <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-4">
                        <label for="eventDiscounttt" class="form-label">Event Court<span>*</span></label>
                        <input type="text" class="form-control" id="eventDiscounttt" placeholder="Event Court">
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="statuseventt" class="form-label">Status<span>*</span></label>
                        <select class="form-select form-control" id="statuseventt" aria-label="">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="facilitycostt" class="form-label">Event Facility Cost ($)</label>
                        <input type="text" class="form-control" id="facilitycostt" placeholder="Event Facility Cost">
                    </div>

                    <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-4">
                        <label for="accessoriesCostt" class="form-label">Event Accessories Cost</label>
                        <input type="text" class="form-control" id="accessoriesCostt" placeholder="Event Accessories Cost">
                    </div>

                    <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-4">
                        <label for="snackscostt" class="form-label">Event Snacks Cost</label>
                        <input type="text" class="form-control" id="snackscostt" placeholder="Event Snacks Cost">
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="eventtotalCosttt" class="form-label">Total Event Cost ($)</label>
                        <input type="text" class="form-control" id="eventtotalCosttt" placeholder="Event Total Cost">
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="eventtotalplayerCosttt" class="form-label">Total Player Cost ($)</label>
                        <input type="text" class="form-control" id="eventtotalplayerCosttt" placeholder="Event Total Player Cost">
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label for="profitlosss" class="form-label">Profit Loss</label>
                        <input type="text" class="form-control" id="profitlosss" placeholder="Profit Loss">
                    </div>

                    <input type="hidden" id="event_sub_id">

                    <div class="col-auto m-auto">
                        <button type="button" class="btn btn-primary" id="save_btn_subs">Create Game</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
(function ($) {
    function populateTimeDropdown(selectId) {
        const select = document.getElementById(selectId);
        if (!select || select.options.length > 0) return;
        const fragment = document.createDocumentFragment();
        for (let h = 0; h < 24; h++) {
            const hour = h < 10 ? "0" + h : h;
            for (let m = 0; m < 60; m += 30) {
                const minute = m < 10 ? "0" + m : m;
                const timeValue = `${hour}:${minute}`;
                fragment.appendChild(new Option(timeValue, timeValue));
            }
        }
        select.appendChild(fragment);
    }

    populateTimeDropdown("freezeTimeee");
    populateTimeDropdown("eventTimeee");
    populateTimeDropdown("toTimeee");

    function getNextDayOfWeek(dayIndex) {
        const date = new Date();
        const currentDay = date.getDay(); // Sunday=0 ... Saturday=6
        let diff = (dayIndex + 7 - currentDay) % 7;
        if (diff === 0) diff = 7; // always next, not today
        date.setDate(date.getDate() + diff);
        return date.toISOString().split("T")[0]; // yyyy-mm-dd
    }

    const dayMap = {
        "MONDAY": 1,
        "TUESDAY": 2,
        "WEDNESDAY": 3,
        "THURSDAY": 4,
        "FRIDAY": 5,
        "SATURDAY": 6,
        "SUNDAY": 7
    };

    $(document).off('click.subEdit', '.discoverGames_card_sub .edit_btn').on('click.subEdit', '.discoverGames_card_sub .edit_btn', function () {
        const id = $(this).attr('data-id');
        const data = $('#data_' + id).val();
        if (!data) return;

        let parseData;
        try {
            parseData = typeof data === 'object' ? data : JSON.parse(data);
        } catch (e) {
            console.error('Error parsing event data:', e);
            return;
        }

        $('.hostgameupdate_modal').addClass('open');
        $('#event_sub_id').val(id);
        $('#host-nameee').val(parseData.HOST_NAME || '');
        $('#eventCountryyy').val(parseData.EVENT_COUNTRY || '');
        $('#eventProvinceee').val(parseData.EVENT_PROVINCE || '');
        $('#eventCityyy').val(parseData.EVENT_CITY || '');
        $('#eventCurrencyyy').val(parseData.EVENT_CURRENCY || '');
        $('#eventVenueee').val($.trim(parseData.EVENT_VENUE || ''));
        $('#eventCategoryyy').val(parseData.EVENT_CATEGORY || '');
        $('#genderCategoryyy').val(parseData.GENDER_CATEGORY || '');
        $('#genderSkillLevelll').val(parseData.GENDER_SKILL_LEVEL || '');
        $('#eventTypeee').val(parseData.EVENT_TYPE || '');

        setTimeout(() => {
            const eventTimeFormatted = (parseData.EVENT_TIME || '').slice(0, 5); 
            const toTimeFormatted = (parseData.TO_TIME || '').slice(0, 5);
            const freezeTimeFormatted = (parseData.CANCEL_TIME || '').slice(0, 5);

            if (eventTimeFormatted) $('#eventTimeee').val(eventTimeFormatted).trigger('change');
            if (toTimeFormatted) $('#toTimeee').val(toTimeFormatted).trigger('change');
            if (freezeTimeFormatted) $('#freezeTimeee').val(freezeTimeFormatted).trigger('change');
        }, 100); 

        $('#eventCosttt').val(parseData.EVENT_COST || '');
        $('#eventDiscounttt').val(parseData.EVENT_DISCOUNT || '');
        $('#eventDescriptionnn').val(parseData.EVENT_DESCRIPTION || '');
        $('#eventMessageee').val(parseData.EVENT_MESSAGE || '');
        $('#statuseventt').val(parseData.STATUS || '');
        $('#evnt_idd, #evnt_id').val(parseData.ID || '');
        $('#facilitycostt').val(parseData.FACILITY_COST || '');
        $('#accessoriesCostt').val(parseData.ACCESSORIES_COST || '');
        $('#snackscostt').val(parseData.SNACKS_COST || '');
        $('#eventtotalCosttt').val(parseData.TOTAL_EVENT_COST || '');
        $('#eventtotalplayerCosttt').val(parseData.TOTAL_PLAYER_COST || '');
        $('#profitlosss').val(parseData.PROFIT_LOSS || '');
        
        const dayName = parseData.DAY ? parseData.DAY.toUpperCase().trim() : "";
        if (dayMap.hasOwnProperty(dayName)) {
            const targetDay = dayMap[dayName];
            const nextDate = getNextDayOfWeek(targetDay);
            $('#eventDateee').val(nextDate);
            if (targetDay === 6) {
                $('#freezeDateee').val(getNextDayOfWeek(5)); // Saturday freeze on Friday
            } else {
                $('#freezeDateee').val(nextDate);
            }
        }
    });
})(jQuery);
</script>
