<?php
global $conn;

$dayColors = $dayColors ?? [
    'SUNDAY'    => 'day-SUNDAY',
    'MONDAY'    => 'day-MONDAY',
    'TUESDAY'   => 'day-TUESDAY',
    'WEDNESDAY' => 'day-WEDNESDAY',
    'THURSDAY'  => 'day-THURSDAY',
    'FRIDAY'    => 'day-FRIDAY',
    'SATURDAY'  => 'day-SATURDAY'
];
?>
<div class="custom_card">
    <h6 class="card_heading">Scheduled Games (Grouped by Weekday)</h6>
    <?php if (!empty($eventsByDay)): ?>
        <?php foreach ($eventsByDay as $day => $day_events): ?>
            <?php if (count($day_events) === 0) continue; ?>

            <h5 class="text-uppercase mt-4 fw-bold border-bottom pb-2"><?= htmlspecialchars((string)$day) ?></h5>
            <div class="discoverGames_wraper hostWrapper">

                <?php foreach ($day_events as $event): ?>
                    <?php
                    $event_id = $event['ID'];
                    $event_venue = htmlspecialchars((string) $event['EVENT_VENUE'], ENT_QUOTES, 'UTF-8');
                    $event_day = strtoupper(trim((string) $event['DAY']));
                    $event_cost = $event['EVENT_COST'];
                    $costNum = (float) $event_cost;
                    $formattedCost = ($costNum > 0) ? number_format($costNum, 2) : (string) $event_cost;
                    $raw_currency = $event['EVENT_CURRENCY'];
                    $event_currency = ($raw_currency === 'CAD' || empty($raw_currency)) ? '$' : $raw_currency;
                    $event_category = htmlspecialchars((string) $event['EVENT_CATEGORY'], ENT_QUOTES, 'UTF-8');
                    $gender_category = htmlspecialchars((string) $event['GENDER_CATEGORY'], ENT_QUOTES, 'UTF-8');
                    $gender_skill_level = htmlspecialchars((string) $event['GENDER_SKILL_LEVEL'], ENT_QUOTES, 'UTF-8');
                    $event_description = html_entity_decode((string) $event['EVENT_DESCRIPTION'], ENT_QUOTES, 'UTF-8');
                    $event_message = html_entity_decode((string) $event['EVENT_MESSAGE'], ENT_QUOTES, 'UTF-8');
                    $event_type = htmlspecialchars((string) $event['EVENT_TYPE'], ENT_QUOTES, 'UTF-8');

                    $displayNote = !empty($event_message) ? $event_message : $event_description;

                    $jsonStringy = htmlspecialchars(json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                    $effectiveUserId = !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : ($userId ?? 0);
                    $selectJoin = ($conn && !empty($effectiveUserId)) ? mysqli_query($conn, "SELECT 1 FROM ca_gamejoin_default WHERE USER_ID='$effectiveUserId' AND GAME_ID='{$event['ID']}' LIMIT 1") : false;
                    $countRows = ($selectJoin && mysqli_num_rows($selectJoin) > 0) ? 1 : 0;

                    $dayColorClass = $dayColors[$event_day] ?? 'day-' . $event_day;
                    $safe_desc = htmlspecialchars($event_description, ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="discoverGames_card_sub scheduled-game-card">
                        <div class="card-header-row">
                            <h4 class="game-title" title="<?= $event_category ?> – <?= $gender_category ?>">
                                <?= $event_category ?> – <?= $gender_category ?>
                            </h4>
                            <div class="header-actions">
                                <span class="info-badge" title="<?= $safe_desc ?>" data-bs-toggle="tooltip" data-bs-placement="top">i</span>
                                <button 
                                    type="button"
                                    class="btn join_btn join-action-btn <?= $countRows > 0 ? 'btn-danger btn-cancel' : 'btn-primary btn-join' ?>" 
                                    data-id="<?= $event_id ?>" 
                                    data-joined="<?= $countRows > 0 ? '1' : '0' ?>">
                                    <?= $countRows > 0 ? 'Cancel' : 'Join' ?>
                                </button>
                                <input type="hidden" id="data_<?= $event_id ?>" value="<?= $jsonStringy ?>"/>
                                <input type="hidden" id="user_<?= $event_id ?>" value="<?= $effectiveUserId ?>"/>
                            </div>
                        </div>

                        <div class="game-time-bar <?= $dayColorClass ?>">
                            <?= $event_day ?> – <?= date('h:i A', strtotime($event['EVENT_TIME'])) ?> – <?= date('h:i A', strtotime($event['TO_TIME'])) ?>
                        </div>

                        <div class="location-row text-truncate">
                            <i class="fa-solid fa-location-dot"></i>
                            <span><?= $event_venue ?></span>
                        </div>

                        <div class="message-row">
                            <?= htmlspecialchars($displayNote, ENT_QUOTES, 'UTF-8') ?>
                        </div>

                        <div class="card-footer-row">
                            <div class="level-pill">
                                Level: <?= $gender_skill_level ?>
                            </div>
                            <div class="footer-right-badges">
                                <span class="badge-type <?= $event_type === 'Invite' ? 'type-invite' : '' ?>">
                                    <?= $event_type ?>
                                </span>
                                <span class="badge-price">
                                    <?= $event_currency ?><?= $formattedCost ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No events found for your skill level.</p>
    <?php endif; ?>
</div>
