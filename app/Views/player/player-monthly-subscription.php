<?php
global $conn;

$dayColors = [
    'SUNDAY'    => 'day-SUNDAY',
    'MONDAY'    => 'day-MONDAY',
    'TUESDAY'   => 'day-TUESDAY',
    'WEDNESDAY' => 'day-WEDNESDAY',
    'THURSDAY'  => 'day-THURSDAY',
    'FRIDAY'    => 'day-FRIDAY',
    'SATURDAY'  => 'day-SATURDAY'
];

if (empty($eventsByDay) && !empty($subscriptionEventsByDay)) {
    $eventsByDay = $subscriptionEventsByDay;
}

// Fallback query if eventsByDay was not passed by the controller
if (empty($eventsByDay) && isset($conn)) {
    $eventsByDay = [
        'SUNDAY'    => [],
        'MONDAY'    => [],
        'TUESDAY'   => [],
        'WEDNESDAY' => [],
        'THURSDAY'  => [],
        'FRIDAY'    => [],
        'SATURDAY'  => []
    ];

    $host_id = isset($host_id) ? (int) $host_id : (int) ($_GET['host_id'] ?? ($_SESSION['mapped_host_id'] ?? 0));
    $hostFilter = $host_id > 0 ? "HOST_ID = '$host_id' AND " : "";
    $vlevelEsc = mysqli_real_escape_string($conn, (string) ($_SESSION['vlevel'] ?? ''));
    $genderEsc = mysqli_real_escape_string($conn, (string) ($_SESSION['gender'] ?? ''));

    $sql = "SELECT * FROM ca_events_default WHERE {$hostFilter}(GENDER_SKILL_LEVEL='$vlevelEsc' OR GENDER_SKILL_LEVEL = 'Mix') AND (GENDER_CATEGORY='$genderEsc' OR GENDER_CATEGORY = 'Mix') AND EVENT_CATEGORY !='Snacks And Kerala Knook'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        while ($event = mysqli_fetch_assoc($result)) {
            $day = strtoupper(trim((string) $event['DAY']));
            if (isset($eventsByDay[$day])) {
                $eventsByDay[$day][] = $event;
            }
        }
    }
}
?>
<section class="outputHtml">
    <?php include __DIR__ . "/scheduled-games-cards.php"; ?>
</section>
