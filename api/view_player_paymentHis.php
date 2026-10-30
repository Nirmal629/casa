<?php
require_once __DIR__ . '/_security.php';
requireApiUser([]);

include('dbConnection.php');
$requestedUser = filter_var($_POST['user_id'] ?? $_GET['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$requestedGame = filter_var($_POST['game_id'] ?? $_GET['game_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$payment_id = filter_var($_POST['payment_id'] ?? $_GET['payment_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$currentUserRole = strtolower(trim((string) ($_SESSION['usertype'] ?? '')));
$isAdmin = ($currentUserRole === 'admin');

if ($payment_id) {
    // Validate by payment ID
    $pStmt = $conn->prepare("SELECT ID, USER_ID, HOST_ID, GAME_ID, REVIEWED_BY FROM ca_payment WHERE ID = ? LIMIT 1");
    $pStmt->bind_param("i", $payment_id);
    $pStmt->execute();
    $pCheck = $pStmt->get_result()->fetch_assoc();
    if (!$pCheck) {
        echo '<p class="text-danger p-3">Payment record not found.</p>';
        exit;
    }

    $isOwn = ($currentUserId === (int) $pCheck['USER_ID']);
    $isHostReviewer = ($currentUserId === (int) $pCheck['HOST_ID'] || $currentUserId === (int) $pCheck['REVIEWED_BY']);

    if (!$isOwn && !$isHostReviewer && !$isAdmin) {
        if ($currentUserRole === 'host') {
            $gId = (int) ($pCheck['GAME_ID'] ?? 0);
            if ($gId > 0) {
                requireOwnedEvent($conn, $gId);
            }
        } else {
            apiSecurityDeny(403, 'Forbidden.');
        }
    }

    $whereClause = "p.ID = " . (int) $payment_id;
    if ($requestedUser && $requestedUser > 0) {
        $whereClause .= " AND p.USER_ID = " . (int) $requestedUser;
    }
} else {
    // Validate by user and optional game
    if (!$requestedUser) {
        apiSecurityDeny(400, 'Invalid request.');
    }
    if ((int) $requestedUser !== $currentUserId && !$isAdmin) {
        if ($requestedGame && $requestedGame > 0) {
            requireOwnedEvent($conn, $requestedGame);
        } else if ($currentUserRole === 'host') {
            $hCheck = $conn->prepare("SELECT 1 FROM ca_payment WHERE USER_ID = ? AND (HOST_ID = ? OR REVIEWED_BY = ?) LIMIT 1");
            $hCheck->bind_param("iii", $requestedUser, $currentUserId, $currentUserId);
            $hCheck->execute();
            if (!$hCheck->get_result()->fetch_assoc()) {
                apiSecurityDeny(403, 'Forbidden.');
            }
        } else {
            apiSecurityDeny(403, 'Forbidden.');
        }
    }

    if ($requestedGame && $requestedGame > 0) {
        $whereClause = "p.USER_ID = " . (int) $requestedUser . " AND p.GAME_ID = " . (int) $requestedGame;
    } else {
        $whereClause = "p.USER_ID = " . (int) $requestedUser;
    }
}

    $query = "SELECT p.*, u.NAME AS REVIEWED_BY_NAME
              FROM `ca_payment` p
              LEFT JOIN ca_users u ON p.REVIEWED_BY = u.ID
              WHERE $whereClause
              ORDER BY p.ID ASC";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        while ($pRow = mysqli_fetch_assoc($result)) {
            $pid = (int) $pRow['ID'];
            $pAmount = number_format((float) $pRow['AMOUNT'], 2);
            $pVenue = 'Games';
            if ((int) $pRow['GAME_ID'] > 0) {
                $vRes = mysqli_query($conn, "SELECT EVENT_VENUE FROM ca_events WHERE ID = '" . (int) $pRow['GAME_ID'] . "' LIMIT 1");
                if ($vRes && $vRow = mysqli_fetch_assoc($vRes)) {
                    if (!empty(trim((string) ($vRow['EVENT_VENUE'] ?? '')))) {
                        $pVenue = trim((string) $vRow['EVENT_VENUE']);
                    }
                }
            } elseif ((int) $pRow['GAME_ID'] < 0) {
                $vRes = mysqli_query($conn, "SELECT VENUE FROM ca_expense WHERE ID = '" . abs((int) $pRow['GAME_ID']) . "' LIMIT 1");
                if ($vRes && $vRow = mysqli_fetch_assoc($vRes)) {
                    if (!empty(trim((string) ($vRow['VENUE'] ?? '')))) {
                        $pVenue = trim((string) $vRow['VENUE']);
                    }
                }
            } elseif (!empty(trim((string) ($pRow['DETAILS'] ?? '')))) {
                $det = trim((string) $pRow['DETAILS']);
                if ($det !== 'General Payment' && $det !== 'Initial Payment Request' && $det !== '—') {
                    $pVenue = $det;
                }
            }

            $pType = htmlspecialchars($pRow['PAYMENT_TYPE'] ?? 'Interac');
            $pStatus = $pRow['STATUS'];
            $isReinitiated = (!empty($pRow['IS_REINITIATED']) || !empty($pRow['PREV_REJECTION_REASON']));

            echo "<div class='card mb-3 shadow-sm border-0 bg-light'>
                    <div class='card-header bg-white d-flex justify-content-between align-items-center py-2'>
                        <h6 class='m-0 font-weight-bold text-slate-800' style='font-size:0.95rem;'>
                            <i class='fa-solid fa-receipt text-teal-600 me-1'></i> Payment #{$pid} — {$pType}
                        </h6>
                        <div>
                            <span class='badge " . ($pStatus === 'Y' ? 'bg-success' : ($pStatus === 'N' ? 'bg-warning text-dark' : 'bg-danger')) . "'>
                                " . ($pStatus === 'Y' ? 'Approved' : ($pStatus === 'N' ? 'Pending Approval' : 'Rejected')) . "
                            </span>";
            echo "      </div>
                    </div>
                    <div class='card-body py-2 px-3'>
                        <div class='row text-muted small mb-2'>
                            <div class='col-4'><strong>Amount:</strong> \${$pAmount}</div>
                            <div class='col-4'><strong>Venue:</strong> " . htmlspecialchars($pVenue) . "</div>
                            <div class='col-4'><strong>Created:</strong> {$pRow['PAYMENT_DATE']} {$pRow['PAYMENT_TIME']}</div>
                        </div>";

            echo '<div class="table-responsive">
                    <table class="table table-sm table-bordered bg-white mb-0" style="font-size:0.82rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Step</th>
                                <th>Timestamp</th>
                                <th>Activity / Event</th>
                                <th>Host Rejection Reason / Remarks</th>
                            </tr>
                        </thead>
                        <tbody>';

            $steps = [];
            $logsQuery = "SELECT * FROM ca_player_logs 
                          WHERE (
                              (JSON_VALID(META) AND JSON_UNQUOTE(JSON_EXTRACT(META, '$.payment_id')) = '$pid')
                              OR DESCRIPTION LIKE '%payment ID $pid %'
                              OR DESCRIPTION LIKE '%payment ID $pid(%'
                              OR DESCRIPTION LIKE '%payment ID $pid'
                              OR DESCRIPTION LIKE '%Payment #$pid%'
                          )
                          ORDER BY ID ASC";
            $logsRes = mysqli_query($conn, $logsQuery);

            if ($logsRes && mysqli_num_rows($logsRes) > 0) {
                $hasSubmitLog = false;
                while ($lRow = mysqli_fetch_assoc($logsRes)) {
                    $type = $lRow['ACTIVITY_TYPE'];
                    $meta = !empty($lRow['META']) ? json_decode($lRow['META'], true) : [];
                    $time = $lRow['CREATED_AT'];
                    $desc = $lRow['DESCRIPTION'];

                    if ($type === 'SUBMIT_PAYMENT' || $type === 'PAYMENT') {
                        $hasSubmitLog = true;
                        $details = $meta['details'] ?? ($pRow['DETAILS'] ?? 'Initial Payment Request');
                        $steps[] = [
                            'time' => $time,
                            'badge' => "<span class='badge bg-primary me-1'>Submitted</span> Initial Payment Request",
                            'remarks' => htmlspecialchars($details),
                            'class' => ''
                        ];
                    } elseif ($type === 'REJECT_PAYMENT') {
                        $reason = $meta['reason'] ?? '';
                        if (empty($reason) && preg_match('/Reason:\s*(.*?)\)/i', $desc, $m)) {
                            $reason = $m[1];
                        }
                        if (empty($reason))
                            $reason = $desc;
                        $revBy = !empty($pRow['REVIEWED_BY_NAME']) ? " (Reviewed by " . htmlspecialchars($pRow['REVIEWED_BY_NAME']) . ")" : '';
                        $steps[] = [
                            'time' => $time,
                            'badge' => "<span class='badge bg-danger me-1'>Rejected by Host</span>{$revBy}",
                            'remarks' => "<strong class='text-danger'><i class='fa-solid fa-circle-exclamation me-1'></i>Host Reason:</strong> " . htmlspecialchars($reason),
                            'class' => 'table-danger'
                        ];
                    } elseif ($type === 'REINITIATE_PAYMENT') {
                        $note = $meta['details'] ?? '';
                        if (empty($note) && preg_match('/Player Reason:\s*(.*?)\)/i', $desc, $m)) {
                            $note = $m[1];
                        }
                        if (empty($note))
                            $note = 'Player resubmitted payment';
                        $steps[] = [
                            'time' => $time,
                            'badge' => "<span class='badge bg-info text-dark me-1'><i class='fa-solid fa-rotate me-1'></i>Re-initiated</span> Player resubmitted payment",
                            'remarks' => htmlspecialchars($note),
                            'class' => 'table-info'
                        ];
                    } elseif ($type === 'APPROVE_PAYMENT') {
                        $revBy = !empty($pRow['REVIEWED_BY_NAME']) ? " (Reviewed by " . htmlspecialchars($pRow['REVIEWED_BY_NAME']) . ")" : '';
                        $steps[] = [
                            'time' => $time,
                            'badge' => "<span class='badge bg-success me-1'>Approved</span> Payment Confirmed{$revBy}",
                            'remarks' => "Payment approved successfully.",
                            'class' => 'table-success'
                        ];
                    } elseif ($type === 'ROLLBACK_PAYMENT') {
                        $steps[] = [
                            'time' => $time,
                            'badge' => "<span class='badge bg-warning text-dark me-1'>Rolled Back</span>",
                            'remarks' => "Payment status rolled back by host.",
                            'class' => 'table-warning'
                        ];
                    }
                }

                if (!$hasSubmitLog) {
                    array_unshift($steps, [
                        'time' => $pRow['PAYMENT_DATE'] . ' ' . $pRow['PAYMENT_TIME'],
                        'badge' => "<span class='badge bg-primary me-1'>Submitted</span> Initial Payment Request",
                        'remarks' => htmlspecialchars($pRow['DETAILS'] ?? '—'),
                        'class' => ''
                    ]);
                }
            } else {
                // Fallback for older payments created before activity logging
                $steps[] = [
                    'time' => $pRow['PAYMENT_DATE'] . ' ' . $pRow['PAYMENT_TIME'],
                    'badge' => "<span class='badge bg-primary me-1'>Submitted</span> Initial Payment Request",
                    'remarks' => htmlspecialchars($pRow['DETAILS'] ?? '—'),
                    'class' => ''
                ];

                if (!empty($pRow['PREV_REJECTION_REASON'])) {
                    $steps[] = [
                        'time' => 'Previous Attempt',
                        'badge' => "<span class='badge bg-danger me-1'>Rejected by Host</span>",
                        'remarks' => "<strong class='text-danger'><i class='fa-solid fa-circle-exclamation me-1'></i>Host Reason:</strong> " . htmlspecialchars($pRow['PREV_REJECTION_REASON']),
                        'class' => 'table-danger'
                    ];

                    $reinitNote = !empty($pRow['MESSAGE']) ? htmlspecialchars($pRow['MESSAGE']) : 'Re-initiated for host review';
                    $steps[] = [
                        'time' => $pRow['CREATED_AT'] ?? '—',
                        'badge' => "<span class='badge bg-info text-dark me-1'><i class='fa-solid fa-rotate me-1'></i>Re-initiated</span> Player resubmitted payment",
                        'remarks' => $reinitNote,
                        'class' => 'table-info'
                    ];
                }

                if ($pStatus === 'R' && !empty($pRow['REJECTION_REASON'])) {
                    $revBy = !empty($pRow['REVIEWED_BY_NAME']) ? " (Reviewed by " . htmlspecialchars($pRow['REVIEWED_BY_NAME']) . ")" : '';
                    $steps[] = [
                        'time' => $pRow['REVIEWED_AT'] ?? 'Latest',
                        'badge' => "<span class='badge bg-danger me-1'>Rejected by Host</span>{$revBy}",
                        'remarks' => "<strong class='text-danger'><i class='fa-solid fa-circle-exclamation me-1'></i>Host Reason:</strong> " . htmlspecialchars($pRow['REJECTION_REASON']),
                        'class' => 'table-danger'
                    ];
                } elseif ($pStatus === 'Y') {
                    $revBy = !empty($pRow['REVIEWED_BY_NAME']) ? " (Reviewed by " . htmlspecialchars($pRow['REVIEWED_BY_NAME']) . ")" : '';
                    $steps[] = [
                        'time' => $pRow['REVIEWED_AT'] ?? '—',
                        'badge' => "<span class='badge bg-success me-1'>Approved</span> Payment Confirmed{$revBy}",
                        'remarks' => "Payment approved successfully.",
                        'class' => 'table-success'
                    ];
                }
            }

            foreach ($steps as $idx => $s) {
                $stepNum = $idx + 1;
                $rowClass = !empty($s['class']) ? " class='{$s['class']}'" : '';
                echo "<tr{$rowClass}>
                        <td><strong>{$stepNum}</strong></td>
                        <td>{$s['time']}</td>
                        <td>{$s['badge']}</td>
                        <td>{$s['remarks']}</td>
                      </tr>";
            }

            echo '      </tbody>
                    </table>
                  </div>
                </div>
              </div>';
        }
    } else {
        echo '<p class="text-danger p-3">No payment records found for this request.</p>';
    }
?>