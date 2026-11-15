<?php

declare(strict_types=1);

namespace App\Controllers\Player;

use App\Core\Controller;
use App\Models\EventModel;
use App\Models\Player\GameJoinModel;
use PDO;

class PlayerSubscriptionController extends Controller
{
    protected EventModel $eventModel;
    protected GameJoinModel $gameJoinModel;

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct();
        $this->eventModel    = new EventModel($pdo);
        $this->gameJoinModel = new GameJoinModel($pdo);
    }

    /**
     * Prepares subscription events grouped by weekday with player joined status.
     */
    public function getSubscriptionViewData(int $userId, int $hostId, string $vlevel, string $gender): array
    {
        $subscriptionEvents   = $this->eventModel->getDefaultSubscriptionEvents($hostId, $vlevel, $gender);
        $joinedDefaultGameIds = $this->gameJoinModel->getJoinedDefaultGameIds($userId);

        // Prepare hashtable for O(1) lookup efficiency inside loop
        $joinedIds = array_fill_keys(array_map('intval', $joinedDefaultGameIds), true);

        $eventsByDay = [
            'SUNDAY'    => [],
            'MONDAY'    => [],
            'TUESDAY'   => [],
            'WEDNESDAY' => [],
            'THURSDAY'  => [],
            'FRIDAY'    => [],
            'SATURDAY'  => []
        ];

        foreach ($subscriptionEvents as $event) {
            $day = strtoupper(trim((string)($event['DAY'] ?? '')));
            if (isset($eventsByDay[$day])) {
                $event['is_joined']  = isset($joinedIds[(int)($event['ID'] ?? 0)]);
                $eventsByDay[$day][] = $event;
            }
        }

        return [
            'subscriptionEventsByDay' => $eventsByDay,
            'eventsByDay'             => $eventsByDay,
            'joinedDefaultGameIds'    => $joinedDefaultGameIds,
        ];
    }
}
