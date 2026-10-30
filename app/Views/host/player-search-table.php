<?php

$isJoinedOnly = !empty($isJoinedOnly) || (($viewMode ?? '') === 'joined');
$joinedList = $joined ?? ($players['joined'] ?? []);
$notJoinedList = $notJoined ?? ($players['not_joined'] ?? []);
$playerList = $isJoinedOnly
    ? $joinedList
    : (!empty($joinedList) || !empty($notJoinedList)
        ? array_merge($joinedList, $notJoinedList)
        : (isset($players[0]) ? $players : []));

if (!empty($playerList)):
    $i = 1;
    $eventCost = (float) ($event['EVENT_COST'] ?? ($eventCost ?? ($playerList[0]['EVENT_COST'] ?? 0)));
    $rawCurr = (string) ($event['EVENT_CURRENCY'] ?? ($eventCurrency ?? ($playerList[0]['EVENT_CURRENCY'] ?? '$')));
    $normCurr = ($rawCurr === 'CAD' || empty($rawCurr)) ? '$' : $rawCurr;
    $eventCurrency = htmlspecialchars($normCurr);
    $escGameId = htmlspecialchars((string) ($gameId ?? ''));
    $escHostId = htmlspecialchars((string) ($hostId ?? ''));

    if ($isJoinedOnly):
        ?>
        <div class='table-responsive'>
            <table class='table table-sm table-bordered mt-2 text-center align-middle text-nowrap' style='font-size: 0.85rem;'>
                <thead class='table-light'>
                    <tr>
                        <th style='width: 30px;'>#</th>
                        <th class='text-start'>Name</th>
                        <th style='width: 70px;'>Gender</th>
                        <th style='width: 80px;'>Amount</th>
                        <th style='width: 80px;'>Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($playerList as $player):
                        $displayName = htmlspecialchars((string) ($player['DISPLAY_NAME'] ?? $player['NAME'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $displayGender = htmlspecialchars((string) ($player['DISPLAY_GENDER'] ?? $player['GENDER'] ?? '-'), ENT_QUOTES, 'UTF-8');
                        $displayLevel = htmlspecialchars((string) ($player['DISPLAY_LEVEL'] ?? $player['LEVEL'] ?? 'N/A'), ENT_QUOTES, 'UTF-8');
                        $rawPlayerCurr = (string) ($player['EVENT_CURRENCY'] ?? $eventCurrency);
                        $curr = htmlspecialchars(($rawPlayerCurr === 'CAD' || empty($rawPlayerCurr)) ? '$' : $rawPlayerCurr);
                        $rawPrice = $player['JOINED_PRICE'] ?? $player['EFFECTIVE_PRICE'] ?? $eventCost;
                        $priceFormatted = is_numeric($rawPrice) ? number_format((float) $rawPrice, 2, '.', '') : (string) $rawPrice;
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td class='text-start fw-bold'><?= $displayName ?></td>
                            <td><?= $displayGender ?></td>
                            <td class='fw-semibold text-success'><?= $curr . ' ' . $priceFormatted ?></td>
                            <td><span class='badge bg-info'><?= $displayLevel ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else:

        $level_map = [
            'Beginner' => 'Beg',
            'Amateur' => 'Ama',
            'Intermediate' => 'Int',
            'Intermediate +' => 'Int+',
            'Intermediate+' => 'Int+',
            'Advance' => 'Adv',
            'Advanced' => 'Adv',
            'Pro' => 'Pro',
        ];

        $gender_map = [
            'Male' => 'M',
            'Female' => 'F',
            'Kid' => 'K',
        ];
        ?>
        <div class='table-responsive'>
            <table class='table table-sm table-bordered mt-2 text-center align-middle text-nowrap' style='font-size: 0.85rem;'>
                <thead class='table-light'>
                    <tr>
                        <th style='width: 30px;'>#</th>
                        <th class='text-start'>Name</th>
                        <th style='width: 30px;' title='Gender'>G</th>
                        <th style='width: 105px; min-width: 105px;'>Amount</th>
                        <th style='width: 40px;' title='Level'>Lvl</th>
                        <th style='width: 35px;' title='Add/Invite Player'><i class='fa-solid fa-user-plus'></i></th>
                        <th style='width: 35px;' title='Confirm Player'><i class='fa-solid fa-check-double'></i></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($playerList as $player):
                        $is_joined = ($player['IS_JOINED'] ?? 0) == 1;
                        $rawPrice = $player['EFFECTIVE_PRICE'] ?? ($is_joined ? ($player['JOINED_PRICE'] ?? $eventCost) : $eventCost);
                        $price = is_numeric($rawPrice) ? number_format((float) $rawPrice, 2, '.', '') : (string) $rawPrice;
                        $checked = $is_joined ? 'checked' : '';
                        $checked_con = ($player['CONFIRMED'] ?? '') === 'Y' ? 'checked' : '';
                        $playerId = (int) $player['ID'];

                        $displayName = $player['DISPLAY_NAME'] ?? '';
                        if ($displayName === '') {
                            $displayName = formatShortName((string) ($player['NAME'] ?? ''));
                        }
                        $displayName = htmlspecialchars($displayName);

                        $displayLevel = $player['DISPLAY_LEVEL'] ?? ($level_map[trim((string) ($player['VERIFIED_LEVEL'] ?? ($player['LEVEL'] ?? '')))] ?? 'N/A');
                        $displayGender = $player['DISPLAY_GENDER'] ?? ($gender_map[trim((string) ($player['GENDER'] ?? ''))] ?? '-');
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td class='text-start fw-bold'
                                title='<?= htmlspecialchars((string) ($player['EMAIL'] ?? '')) ?: "" ?>'>
                                <?= $displayName ?></td>
                            <td><?= htmlspecialchars((string) $displayGender) ?></td>
                            <td>
                                <div class='d-flex align-items-center justify-content-center gap-1' style='width: 90px; margin: 0 auto;'>
                                    <input type='number' step='0.01' min='0'
                                        class='form-control form-control-sm player-price-input text-center px-1 <?= $is_joined ? "is-joined" : "" ?>'
                                        id='price_<?= $playerId ?>' data-user-id='<?= $playerId ?>' data-game-id='<?= $escGameId ?>'
                                        value='<?= $price ?>' style='width: 62px; height: 26px; font-size: 12px; font-weight: 600;'
                                        title='Player Fee' />
                                    <?php if ($is_joined): ?>
                                        <button type='button'
                                            class='btn btn-sm btn-outline-success save-price p-0 d-flex align-items-center justify-content-center'
                                            data-user-id='<?= $playerId ?>' data-game-id='<?= $escGameId ?>'
                                            style='width: 22px; height: 22px;' title='Save Price'>
                                            <i class='fa-solid fa-check' style='font-size: 9px;'></i>
                                        </button>
                                    <?php else: ?>
                                        <span class='p-0' style='width: 22px; height: 22px; visibility: hidden;' aria-hidden='true'></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><span class='badge bg-info'><?= htmlspecialchars((string) $displayLevel) ?></span></td>
                            <td>
                                <label class='m-0 p-0 d-flex align-items-center justify-content-center' style='cursor: pointer;'
                                    title='Add Player'>
                                    <input type='checkbox' class='invite-checkbox d-none' data-type='Public'
                                        data-user-id='<?= $playerId ?>' data-game-id='<?= $escGameId ?>'
                                        data-host-id='<?= $escHostId ?>' data-price-id='<?= $price ?>'
                                        data-currency-id='<?= $eventCurrency ?>' <?= $checked ?>
                                        onchange='this.nextElementSibling.className = this.checked ? "fa-solid fa-user-check fs-5 text-success" : "fa-solid fa-user-plus fs-5 text-secondary";' />
                                    <i class='<?= $checked ? "fa-solid fa-user-check fs-5 text-success" : "fa-solid fa-user-plus fs-5 text-secondary" ?>'
                                        style='transition: 0.2s;'></i>
                                </label>
                            </td>
                            <td>
                                <label class='m-0 p-0 d-flex align-items-center justify-content-center' style='cursor: pointer;'
                                    title='Confirm Player'>
                                    <input type='checkbox' class='invite-checkboxx d-none' data-type='Public'
                                        data-user-id='<?= $playerId ?>' data-game-id='<?= $escGameId ?>'
                                        data-host-id='<?= $escHostId ?>' data-price-id='<?= $price ?>'
                                        data-currency-id='<?= $eventCurrency ?>' <?= $checked_con ?>
                                        onchange='this.nextElementSibling.className = this.checked ? "fa-solid fa-check-double fs-5 text-primary" : "fa-solid fa-check fs-5 text-secondary";' />
                                    <i class='<?= $checked_con ? "fa-solid fa-check-double fs-5 text-primary" : "fa-solid fa-check fs-5 text-secondary" ?>'
                                        style='transition: 0.2s;'></i>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php else: ?>
    <input type='hidden' class='invite-checkbox' data-game-id='<?= htmlspecialchars((string) ($gameId ?? '')) ?>'
        data-host-id='<?= htmlspecialchars((string) ($hostId ?? '')) ?>' />
    <p class='text-center text-muted my-3'>No players found.</p>
<?php endif; ?>