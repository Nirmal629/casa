<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Pagination;
use App\Helpers\Session;
use App\Models\ClubModel;
use App\Models\EventModel;
use App\Models\Player\PlayerLogModel;
use App\Models\PlayerRatingModel;
use App\Models\UserModel;
use App\Services\Ledger\LedgerPresenter;
use App\Services\Ledger\LedgerService;
use Exception;
use PDO;
use Throwable;

use function count;
use function formatShortName;
use function in_array;
use function is_array;
use function strcasecmp;
use function strtolower;
use function strtoupper;
use function trim;

/**
 * Controller for Host and Club administration, game scheduling, player management, and payments.
 * Refactored to adhere to PSR-12, single responsibility, guard clauses, and robust error handling.
 */
class HostController extends Controller
{
    protected PDO $db;
    protected UserModel $userModel;
    protected EventModel $eventModel;
    protected ClubModel $clubModel;
    protected PlayerRatingModel $playerRatingModel;
    protected PlayerLogModel $playerLogModel;

    private const array LEVEL_MAP = [
        'Beginner'       => 'Beg',
        'Amateur'        => 'Ama',
        'Intermediate'   => 'Int',
        'Intermediate +' => 'Int+',
        'Intermediate+'  => 'Int+',
        'Advance'        => 'Adv',
        'Advanced'       => 'Adv',
        'Pro'            => 'Pro',
    ];

    private const array GENDER_MAP = [
        'Male'   => 'M',
        'Female' => 'F',
        'Kid'    => 'K',
    ];

    private const array SPECIAL_LEVEL_CLAUSES = [
        'advance'        => "COALESCE(NULLIF(cu.VERIFIED_LEVEL, ''), cu.LEVEL) IN ('Advance', 'Advanced')",
        'advanced'       => "COALESCE(NULLIF(cu.VERIFIED_LEVEL, ''), cu.LEVEL) IN ('Advance', 'Advanced')",
        'intermediate +' => "COALESCE(NULLIF(cu.VERIFIED_LEVEL, ''), cu.LEVEL) IN ('Intermediate +', 'Intermediate+')",
        'intermediate+'  => "COALESCE(NULLIF(cu.VERIFIED_LEVEL, ''), cu.LEVEL) IN ('Intermediate +', 'Intermediate+')",
    ];

    private const array SPECIAL_GENDER_CLAUSES = [
        'kid'  => "cu.GENDER IN ('Kid', 'Kids')",
        'kids' => "cu.GENDER IN ('Kid', 'Kids')",
    ];

    private const array ALLOWED_PAGE_SIZES = [5, 10, 20, 50, 100];
    private const array ALLOWED_SORT_COLUMNS = ['name', 'games', 'amount', 'paid', 'due'];
    private const array DAYS_OF_WEEK = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'];

    public function __construct(?PDO $db = null)
    {
        parent::__construct();
        $this->db = $db ?? \App\Config\Database::getPdo();
        $this->userModel = new UserModel($this->db);
        $this->eventModel = new EventModel($this->db);
        $this->clubModel = new ClubModel($this->db);
        $this->playerRatingModel = new PlayerRatingModel($this->db);
        $this->playerLogModel = new PlayerLogModel($this->db);
    }

    /**
     * Backward-compatible static proxy for formatting player short names.
     */
    public static function formatShortName(string $name): string
    {
        return function_exists('formatShortName') ? formatShortName($name) : trim($name);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DASHBOARD & OVERVIEW
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Display Host Dashboard with overview stats, club settings, and scheduled events.
     */
    public function dashboard(string $view = 'host/host-dashboard'): void
    {
        $auth = $this->getAuthUser();
        $hostId = $auth['id'];

        $club = $this->clubModel->findByHostId($hostId);
        $joinType = strtoupper(trim((string) ($club['join_type'] ?? 'A')));
        $access = $this->resolveClubAccessFlags($joinType);

        $pendingRequestsCount = $access['canModerateRequests']
            ? $this->getPendingRequestsCount($hostId)
            : 0;

        $statsRows = $access['canViewSpecialTabs']
            ? $this->getPlayerStats($hostId)
            : [];

        $scheduledEvents = $this->getEventData($hostId, (int) date('Y'), (int) date('n'));
        $specialData = $this->loadDashboardSpecialData($hostId, $access['canViewSpecialTabs']);

        $uniqueLevels = $this->userModel->getAllUniqueVerifiedLevels();
        $uniqueGenders = $this->userModel->getAllUniqueGenders();

        $this->render($view, [
            'statsRows'            => $statsRows,
            'uniqueLevels'         => $uniqueLevels,
            'uniqueGenders'        => $uniqueGenders,
            'levelOrder'           => !empty($uniqueLevels) ? $uniqueLevels : ["Beginner", "Amateur", "Intermediate", "Intermediate +", "Advanced"],
            'userModel'            => $this->userModel,
            'host_id'              => $hostId,
            'canViewSpecialTabs'   => $access['canViewSpecialTabs'],
            'isSuperHost'          => $access['isSuperHost'],
            'isRequestJoinHost'    => $access['isRequestJoinHost'],
            'isAutoJoinHost'       => $access['isAutoJoinHost'],
            'canModerateRequests'  => $access['canModerateRequests'],
            'pendingRequestsCount' => $pendingRequestsCount,
            'joinType'             => $joinType,
            'club'                 => $club,
            'eventsByDay'          => $specialData['eventsByDay'],
            'venues'               => $specialData['venues'],
            'categories'           => $specialData['categories'],
            'events'               => $scheduledEvents,
            'scheduledEvents'      => $scheduledEvents,
        ]);
    }

    /**
     * Fetch filtered scheduled events via EventModel.
     */
    public function getEventData(
        int $hostId,
        ?int $year = null,
        ?int $month = null,
        ?string $eventCategory = null,
        bool $isPlayer = false,
        bool $joinedOnly = false
    ): array {
        $filters = $this->buildEventFilters($hostId, $year, $month, $eventCategory, $isPlayer, $joinedOnly);
        return $this->eventModel->getEventData($filters);
    }

    /**
     * AJAX/HTML filter for host scheduled events card view.
     */
    public function filterSchedule(...$args): void
    {
        try {
            $context = $this->resolveScheduleContext($args);

            $context['events'] = $context['hostId'] > 0
                ? $this->getEventData(
                    $context['hostId'],
                    $context['year'],
                    $context['month'],
                    $context['eventCategory'],
                    $context['isPlayer']
                )
                : [];

            $this->render('host/scheduled-events-cards', $context);
        } catch (Throwable $e) {
            \error_log('[HostController::filterSchedule Error] ' . $e->getMessage());
            $this->render('host/scheduled-events-cards', [
                'events'        => [],
                'hostId'        => 0,
                'year'          => (int) date('Y'),
                'month'         => (int) date('n'),
                'eventCategory' => '',
                'isPlayer'      => false,
            ]);
        }
    }

    /**
     * Retrieve player ratings and participation statistics.
     */
    public function getPlayerStats(array|int $options = []): array
    {
        $isJson = isset($_REQUEST['format']) && strtolower((string) $_REQUEST['format']) === 'json';

        try {
            $filters = $this->resolvePlayerStatsFilters($options);
            $rows = $this->playerRatingModel->getPlayerStats($filters);
            $response = ['success' => true, 'data' => $rows];
            $statusCode = 200;
        } catch (Throwable $e) {
            \error_log('[HostController::getPlayerStats Error] ' . $e->getMessage());
            $rows = [];
            $response = ['success' => false, 'error' => $e->getMessage(), 'data' => []];
            $statusCode = 500;
        }

        if ($isJson) {
            $this->json($response, $statusCode);
        }

        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FINANCIAL LEDGER & PAYMENTS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get detailed monthly ledger for a specific player and host.
     */
    public function getPlayerDetailedLedger(int|array $options = 0, int $playerId = 0, int $year = 0, int $month = 0): array
    {
        $auth = $this->getAuthUser();
        $filters = $this->resolveDetailedLedgerFilters($options, $playerId, $year, $month, $auth);

        $hostId = (int) ($filters['host_id'] ?? 0);
        $targetPlayerId = (int) ($filters['player_id'] ?? 0);
        $targetYear = (int) ($filters['year'] ?? (int) date('Y'));
        $targetMonth = (int) ($filters['month'] ?? (int) date('n'));
        $isPlayerSide = isset($filters['is_player_side']) ? (bool) $filters['is_player_side'] : ($auth['type'] === 'player');

        try {
            $ledgerService = new LedgerService();
            $ledger = $ledgerService->getMonthlyLedger(
                $hostId,
                $targetPlayerId,
                $targetYear,
                $targetMonth,
                $auth['type'] ?: ($isPlayerSide ? 'player' : 'host'),
                $auth['id']
            );

            $bodyHtml = LedgerPresenter::renderMonthlyHtml($ledger);
            $filterBar = LedgerPresenter::renderFilterBar($targetPlayerId, $targetYear, $targetMonth);
            $fullHtml = $filterBar . $bodyHtml;

            $result = array_merge($ledger, ['html' => $fullHtml]);

            if (isset($_REQUEST['format']) && strtolower((string) $_REQUEST['format']) === 'json') {
                $this->json(['success' => true, 'data' => $result]);
            }

            return $result;
        } catch (Throwable $e) {
            \error_log('[HostController::getPlayerDetailedLedger Error] ' . $e->getMessage());
            if (isset($_REQUEST['format']) && strtolower((string) $_REQUEST['format']) === 'json') {
                $this->json(['success' => false, 'error' => $e->getMessage(), 'data' => []], 500);
            }
            return [];
        }
    }

    /**
     * Get paginated host player payment overview and dues table.
     */
    public function getHostPaymentList(int|array $options = []): array|string
    {
        $auth = $this->getAuthUser();
        $authUserId = $auth['id'];
        $authUserType = $auth['type'];

        $hostId = $this->resolvePaymentListHostId($options, $auth);
        if ($hostId === null) {
            $this->json(['success' => false, 'message' => 'Forbidden.'], 403);
            return [];
        }

        $filterYear = isset($_REQUEST['year']) && $_REQUEST['year'] !== '' ? (int) $_REQUEST['year'] : 0;
        $filterMonth = isset($_REQUEST['month']) && $_REQUEST['month'] !== '' ? (int) $_REQUEST['month'] : 0;
        $filterPlayerId = isset($_REQUEST['player']) && $_REQUEST['player'] !== ''
            ? (int) $_REQUEST['player']
            : (isset($_REQUEST['player_id']) && $_REQUEST['player_id'] !== '' ? (int) $_REQUEST['player_id'] : null);

        if ($authUserType === 'player') {
            $filterPlayerId = $authUserId;
        }

        $searchQuery = isset($_REQUEST['search']) ? trim((string) $_REQUEST['search']) : '';
        $sortBy = isset($_REQUEST['sort_by']) ? (string) $_REQUEST['sort_by'] : 'name';
        $sortDir = isset($_REQUEST['sort_dir']) && strtolower((string) $_REQUEST['sort_dir']) === 'desc' ? 'desc' : 'asc';
        $page = isset($_REQUEST['page']) && (int) $_REQUEST['page'] > 0 ? (int) $_REQUEST['page'] : 1;
        $perPage = isset($_REQUEST['pageSize']) && in_array((int) $_REQUEST['pageSize'], self::ALLOWED_PAGE_SIZES, true) ? (int) $_REQUEST['pageSize'] : 10;

        if (!in_array($sortBy, self::ALLOWED_SORT_COLUMNS, true)) {
            $sortBy = 'name';
        }

        $isJson = (isset($_REQUEST['format']) && strtolower((string) $_REQUEST['format']) === 'json')
            || ($this->isAjaxRequest() && empty($_POST['type']));

        if ($hostId <= 0) {
            $msg = "<p class='text-center py-3 text-muted'>Please log in to view host payments.</p>";
            if ($isJson) {
                $this->json(['success' => false, 'error' => 'Unauthenticated', 'html' => $msg], 401);
            }
            echo $msg;
            return $msg;
        }

        try {
            $userRows = $this->fetchPaymentListUsers($hostId, $filterPlayerId, $searchQuery);

            if (empty($userRows)) {
                return $this->respondEmptyPaymentList($searchQuery, $perPage, $isJson);
            }

            $ledgerService = new LedgerService();
            $paginated = $this->paginatePaymentPlayers(
                $userRows,
                $ledgerService,
                $hostId,
                $filterYear,
                $filterMonth,
                $sortBy,
                $sortDir,
                $page,
                $perPage
            );

            $pagePlayers = $paginated['players'];
            /** @var Pagination $pagination */
            $pagination = $paginated['pagination'];

            $html = LedgerPresenter::renderPaymentListTableHtml(
                $pagePlayers,
                $pagination,
                $sortBy,
                $sortDir,
                self::ALLOWED_PAGE_SIZES
            );

            if ($isJson) {
                $this->json([
                    'success' => true,
                    'data'    => [
                        'players'    => $pagePlayers,
                        'total'      => $pagination->getTotal(),
                        'page'       => $pagination->getCurrentPage(),
                        'pageSize'   => $pagination->getPerPage(),
                        'totalPages' => $pagination->getTotalPages(),
                        'pagination' => $pagination->toArray(),
                    ],
                    'html'    => $html,
                ]);
                return $pagePlayers;
            }

            echo $html;
            return $html;
        } catch (Throwable $e) {
            \error_log('[HostController::getHostPaymentList Error] ' . $e->getMessage());
            $errorHtml = '<p class="text-center py-3 text-danger">Error loading payment list.</p>';
            if ($isJson) {
                $this->json(['success' => false, 'error' => $e->getMessage(), 'html' => $errorHtml], 500);
            }
            echo $errorHtml;
            return $errorHtml;
        }
    }

    /**
     * API: Update player PREMIUM badge status.
     */
    public function updatePlayerPremium(): void
    {
        $auth = $this->getAuthUser();
        $authUserId = $auth['id'];
        $authUserType = $auth['type'];
        $isJson = (isset($_REQUEST['format']) && $_REQUEST['format'] === 'json') || $this->isAjaxRequest();

        if ($authUserId <= 0) {
            $this->respondPremiumError('Unauthorized: Please log in.', 401, $isJson, 'Unauthorized');
            return;
        }

        $isAdmin = ($authUserType === 'admin');
        $isHostOrTrainer = in_array($authUserType, ['host', 'trainer'], true);

        if (!$isAdmin && !$isHostOrTrainer) {
            $this->respondPremiumError('Forbidden: Insufficient privileges.', 403, $isJson, 'Forbidden');
            return;
        }

        $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : (int) ($_REQUEST['user_id'] ?? 0);
        $isPremium = (isset($_POST['is_premium']) && strtoupper((string) $_POST['is_premium']) === 'Y') ? 'Y' : 'N';

        if ($userId <= 0) {
            $this->respondPremiumError('Invalid player ID.', 400, $isJson, 'Invalid user');
            return;
        }

        // Verify player existence and active status
        $stmtPlayer = $this->db->prepare("SELECT ID, USERTYPE, DEL_STATUS FROM ca_users WHERE ID = ? LIMIT 1");
        $stmtPlayer->execute([$userId]);
        $targetUser = $stmtPlayer->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser || ($targetUser['DEL_STATUS'] ?? '') === 'Y') {
            $this->respondPremiumError('Player not found or account is deleted.', 404, $isJson, 'Player not found');
            return;
        }

        // Host/Trainer can modify only players they are authorized to manage
        if (!$isAdmin && !$this->isAuthorizedHostForPlayer($authUserId, $userId)) {
            $this->respondPremiumError('Forbidden: You are not authorized to manage this player.', 403, $isJson, 'Forbidden');
            return;
        }

        try {
            $stmt = $this->db->prepare("UPDATE ca_users SET PREMIUM = :premium WHERE ID = :user_id AND DEL_STATUS = 'N'");
            $success = $stmt->execute([':premium' => $isPremium, ':user_id' => $userId]);

            if ($success) {
                \error_log(sprintf('[AUDIT] User %d (%s) updated PREMIUM status of player %d to %s', $authUserId, $authUserType, $userId, $isPremium));
                $this->logActivity($authUserId, 'UPDATE_PREMIUM', "Updated player {$userId} premium status to {$isPremium}", $authUserId);

                if ($isJson) {
                    $this->json(['success' => true, 'message' => 'OK']);
                }
                echo 'OK';
                return;
            }

            $this->respondPremiumError('Update failed', 500, $isJson);
        } catch (Throwable $e) {
            \error_log('[HostController::updatePlayerPremium Error] ' . $e->getMessage());
            $this->respondPremiumError('Update failed', 500, $isJson);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GAME SESSIONS & ROSTER ACTIONS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * API: Delete host scheduled event.
     */
    public function deleteEvent(): void
    {
        $auth = $this->getAuthUser();
        $authUserId = $auth['id'];
        $isAdmin = ($auth['type'] === 'admin');

        $id = $this->extractEventIdFromRequest();
        if ($id <= 0) {
            $this->json(['success' => false, 'error' => 'Invalid event ID.'], 400);
            return;
        }

        // Verify existence first
        $stmtCheck = $this->db->prepare("SELECT HOST_ID FROM ca_events WHERE ID = ? LIMIT 1");
        $stmtCheck->execute([$id]);
        $eventRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$eventRow) {
            $this->json(['success' => false, 'error' => 'Event not found or already deleted.'], 404);
            return;
        }

        $eventHostId = (int) $eventRow['HOST_ID'];
        if (!$isAdmin && ($authUserId <= 0 || $eventHostId !== $authUserId)) {
            $this->json(['success' => false, 'error' => 'Unauthorized: You do not have permission to delete this event.'], 403);
            return;
        }

        try {
            $deleted = $this->eventModel->deleteEventForHost($id, $authUserId, $isAdmin);
            if ($deleted) {
                $this->json(['success' => true, 'message' => 'Event deleted successfully.']);
            } else {
                $this->json(['success' => false, 'error' => 'Event not found or already deleted.'], 404);
            }
        } catch (Throwable $e) {
            \error_log('[HostController::deleteEvent Error] ' . $e->getMessage());
            $this->json([
                'success' => false,
                'error'   => 'An error occurred while deleting the event.',
                'debug'   => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN) ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Form / API: Create new game session.
     */
    public function createGame(): void
    {
        $auth = $this->getAuthUser();
        $userId = $auth['id'];
        $userType = $auth['type'];

        if ($userId <= 0 || !in_array($userType, ['host', 'trainer', 'admin'], true)) {
            if ($this->isAjaxRequest()) {
                $this->json(['status' => 'error', 'message' => 'Unauthorized access.'], 403);
            }
            $this->setFlashToast('error', 'Unauthorized access.');
            $this->redirect('/host/host-creat-game.php');
        }

        $appTz = env('APP_TIMEZONE', env('TIMEZONE', 'Asia/Kolkata'));
        if (!empty($appTz)) {
            @date_default_timezone_set($appTz);
        }

        $input = !empty($_POST) ? $_POST : (json_decode((string) file_get_contents('php://input'), true) ?: []);
        $data = $this->buildGameData($input, $userId);

        try {
            $eventId = $this->eventModel->create($data);
            if (!$eventId) {
                throw new Exception('Failed to create event in database.');
            }

            if ($this->isAjaxRequest()) {
                $this->json([
                    'status'   => 'success',
                    'message'  => 'New Event Created Successfully.',
                    'event_id' => $eventId,
                    'redirect' => '/host/host-scheduled-game.php',
                ]);
            }

            $this->setFlashToast('success', 'New Event Created Successfully.', '/host/host-scheduled-game.php');
            $this->redirect('/host/host-scheduled-game.php');
        } catch (Throwable $e) {
            \error_log('[HostController::createGame Error] ' . $e->getMessage());
            if ($this->isAjaxRequest()) {
                $this->json(['status' => 'error', 'message' => 'Failed to create event. ' . $e->getMessage()], 500);
            }
            $this->setFlashToast('error', 'Failed to create event. ' . $e->getMessage());
            $this->redirect('/host/host-creat-game.php');
        }
    }

    /**
     * API: Save Player Rating by Host.
     */
    public function saveRatingByHost(): void
    {
        $auth = $this->getAuthUser();
        $authUserId = $auth['id'];
        $authUserType = $auth['type'];

        if ($authUserId <= 0) {
            $this->json(['success' => false, 'message' => 'Unauthorized: Please log in.'], 401);
            return;
        }

        $isAdmin = ($authUserType === 'admin');
        $isHostOrTrainer = in_array($authUserType, ['host', 'trainer'], true);

        if (!$isAdmin && !$isHostOrTrainer) {
            $this->json(['success' => false, 'message' => 'Forbidden: Only authorized Hosts or Trainers can submit player ratings.'], 403);
            return;
        }

        $raterId = $authUserId;
        $ratedPlayerId = (int) ($_POST['rated_player_id'] ?? ($_POST['player_id'] ?? 0));
        $skillLevel = trim((string) ($_POST['skill_level'] ?? ''));
        $newRank = (int) ($_POST['ranking'] ?? ($_POST['rank'] ?? 0));

        if ($ratedPlayerId <= 0 || $skillLevel === '' || $newRank <= 0) {
            $this->json(['success' => false, 'message' => 'Please select a valid player, level, and rank.'], 400);
            return;
        }

        if ($ratedPlayerId === $authUserId) {
            $this->json(['success' => false, 'message' => 'Forbidden: You cannot rate your own account.'], 403);
            return;
        }

        // Verify player existence and active status
        $stmtPlayer = $this->db->prepare("SELECT ID, DEL_STATUS FROM ca_users WHERE ID = ? LIMIT 1");
        $stmtPlayer->execute([$ratedPlayerId]);
        $playerRow = $stmtPlayer->fetch(PDO::FETCH_ASSOC);
        if (!$playerRow || ($playerRow['DEL_STATUS'] ?? '') === 'Y') {
            $this->json(['success' => false, 'message' => 'Player not found or account is deleted.'], 404);
            return;
        }

        // Host/Trainer can modify only players/events they are authorized to manage
        if (!$isAdmin) {
            $eventId = (int) ($_POST['event_id'] ?? ($_POST['game_id'] ?? 0));
            $isAuthorized = ($eventId > 0)
                ? $this->isAuthorizedHostForEventPlayer($authUserId, $eventId, $ratedPlayerId)
                : $this->isAuthorizedHostForPlayer($authUserId, $ratedPlayerId);

            if (!$isAuthorized) {
                $msg = ($eventId > 0)
                    ? 'Forbidden: You do not own this event or player did not participate.'
                    : 'Forbidden: You are not authorized to rate this player.';
                $this->json(['success' => false, 'message' => $msg], 403);
                return;
            }
        }

        try {
            $this->db->beginTransaction();

            $this->userModel->adjustAndSaveRanking($ratedPlayerId, $skillLevel, $newRank);
            $this->playerRatingModel->saveRating($raterId, $ratedPlayerId, $skillLevel, $newRank);

            $this->db->commit();

            \error_log(sprintf('[AUDIT] User %d (%s) rated player %d as Level: %s, Rank: %d', $authUserId, $authUserType, $ratedPlayerId, $skillLevel, $newRank));
            $this->logActivity($authUserId, 'RATE_PLAYER', "Rated player {$ratedPlayerId} Level: {$skillLevel}, Rank: {$newRank}", $raterId);

            $this->json(['success' => true, 'message' => 'Rating updated successfully and rankings adjusted.']);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            \error_log('[HostController::saveRatingByHost Error] ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Failed to save rating.'], 500);
        }
    }

    /**
     * API: Search game players (Supports both "Joined Players" and "Add Players" views in JSON or HTML).
     */
    public function searchGamePlayers(): void
    {
        $body = !empty($_POST) ? $_POST : $_GET;
        $gameId = filter_var($body['game_id'] ?? $body['ID'] ?? null, FILTER_VALIDATE_INT);
        if (!$gameId) {
            $this->json(['status' => 'error', 'message' => 'Valid game_id is required.'], 400);
            return;
        }

        $auth = $this->getAuthUser();
        $authUserId = $auth['id'];
        $authUserType = $auth['type'];

        if ($authUserId <= 0) {
            $this->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
            return;
        }

        $ev = $this->eventModel->getEventById((int) $gameId);
        if (!$ev) {
            $this->json(['status' => 'error', 'message' => 'Event not found.'], 404);
            return;
        }

        $eventHostId = (int) ($ev['HOST_ID'] ?? 0);
        $isHostOfGame = in_array($authUserType, ['host', 'trainer', 'admin'], true) && ($authUserId === $eventHostId || $authUserType === 'admin');

        $uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));
        $isJoinedOnly = !empty($body['joined_only'])
            || in_array(strtolower((string) ($body['view'] ?? $body['type'] ?? '')), ['joined', 'joined_only'], true)
            || (isset($body['is_joined']) && (string) $body['is_joined'] === '1')
            || str_contains($uri, 'view_joined_all')
            || str_contains($uri, 'view_player');

        // Non-hosts can ONLY view joined players roster (never search or browse candidate players)
        if (!$isHostOfGame) {
            $isJoinedOnly = true;
        }

        $gender = trim((string) ($body['gender'] ?? $body['gender_category'] ?? $body['GENDER_CATEGORY'] ?? ''));
        $level = trim((string) ($body['verified_level'] ?? $body['skill_level'] ?? $body['level'] ?? $body['GENDER_SKILL_LEVEL'] ?? $body['gender_skill_level'] ?? ''));
        $name = trim((string) ($body['name'] ?? $body['query'] ?? $body['search'] ?? ''));

        try {
            $allPlayers = $this->fetchGamePlayersList((int) $gameId, $isJoinedOnly, $gender, $level, $name);
            $partitioned = $this->formatAndPartitionPlayers($allPlayers);
            $joined = $partitioned['joined'];
            $notJoined = $partitioned['not_joined'];

            $format = strtolower((string) ($body['format'] ?? ''));
            $isHtml = $format === 'html'
                || $format === 'table'
                || str_contains($uri, 'view_joined')
                || (!empty($body['view']) && $format !== 'json');

            if ($isHtml) {
                $eventDetails = $this->resolveGamePlayerEventDetails((int) $gameId, $allPlayers[0] ?? [], $ev);
                $this->render('host/player-search-table', [
                    'players'       => ['joined' => $joined, 'not_joined' => $notJoined],
                    'joined'        => $joined,
                    'notJoined'     => $notJoined,
                    'isJoinedOnly'  => $isJoinedOnly,
                    'viewMode'      => $isJoinedOnly ? 'joined' : 'all',
                    'gameId'        => $gameId,
                    'hostId'        => $eventDetails['hostId'],
                    'eventCost'     => $eventDetails['cost'],
                    'eventCurrency' => $eventDetails['currency'],
                    'event'         => [
                        'EVENT_COST'     => $eventDetails['cost'],
                        'EVENT_CURRENCY' => $eventDetails['currency'],
                        'HOST_ID'        => $eventDetails['hostId'],
                    ],
                ]);
                return;
            }

            $this->json([
                'status'           => 'success',
                'view'             => $isJoinedOnly ? 'joined' : 'all',
                'game_id'          => $gameId,
                'total'            => count($allPlayers),
                'joined_count'     => count($joined),
                'not_joined_count' => count($notJoined),
                'data'             => $isJoinedOnly ? $joined : $allPlayers,
                'joined'           => $joined,
                'not_joined'       => $notJoined,
            ]);
        } catch (Throwable $e) {
            \error_log('[HostController::searchGamePlayers Error] ' . $e->getMessage());
            $this->json(['status' => 'error', 'message' => 'Failed to search players.'], 500);
        }
    }

    // API: Add or Remove Player from Game (Invite/Join).
    public function saveInvitation(): void
    {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $gameId = (int) ($_POST['game_id'] ?? 0);
        $currency = trim((string) ($_POST['currency'] ?? ''));
        $price = trim((string) ($_POST['price'] ?? ''));
        $dtype = trim((string) ($_POST['dtype'] ?? 'Public'));
        $type = trim((string) ($_POST['type'] ?? ''));

        if ($userId <= 0 || $gameId <= 0) {
            $this->json(['status' => 'error', 'message' => 'Invalid request.'], 400);
            return;
        }

        $verifiedHostId = $this->getVerifiedGameHostId($gameId);
        if ($verifiedHostId === null) {
            $this->json(['status' => 'error', 'message' => 'Unauthorized: You do not own this game session.'], 403);
            return;
        }

        $hostId = $verifiedHostId;

        try {
            if ($type !== 'checked') {
                $stmtDelete = $this->db->prepare("DELETE FROM ca_gamejoin WHERE USER_ID = ? AND GAME_ID = ?");
                $stmtDelete->execute([$userId, $gameId]);

                $this->eventModel->applyAutoConfirmAndMessage($gameId, true);
                $this->json(['status' => 'success', 'message' => 'Player removed successfully.']);
                return;
            }

            $isInvite = strcasecmp($dtype, 'invite') === 0;
            $typeVal = $isInvite ? 'Invite' : 'Public';
            $statusVal = $isInvite ? 'N' : 'Y';

            $stmtCheck = $this->db->prepare("SELECT ID FROM ca_gamejoin WHERE USER_ID = ? AND GAME_ID = ? LIMIT 1");
            $stmtCheck->execute([$userId, $gameId]);
            $existingId = $stmtCheck->fetchColumn();

            if ($existingId) {
                $stmtUpdate = $this->db->prepare("UPDATE ca_gamejoin SET TYPE = ?, STATUS = ?, PRICE = ?, CURRENCY = ? WHERE ID = ?");
                $stmtUpdate->execute([$typeVal, $statusVal, $price, $currency, $existingId]);
            } else {
                $stmtInsert = $this->db->prepare("
                    INSERT INTO ca_gamejoin (USER_ID, GAME_ID, HOST_ID, PRICE, CURRENCY, TYPE, STATUS, CONFIRMED, CREATED_AT)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'N', NOW())
                ");
                $stmtInsert->execute([$userId, $gameId, $hostId, $price, $currency, $typeVal, $statusVal]);
            }

            $this->eventModel->applyAutoConfirmAndMessage($gameId, true);
            $this->json(['status' => 'success', 'message' => 'Invitation sent successfully.']);
        } catch (Throwable $e) {
            \error_log('[HostController::saveInvitation Error] ' . $e->getMessage());
            $this->json(['status' => 'error', 'message' => 'Failed to process invitation.'], 500);
        }
    }

    /**
     * API: Confirm or Unconfirm Player in Game.
     */
    public function saveConfirm(): void
    {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $gameId = (int) ($_POST['game_id'] ?? 0);
        $currency = trim((string) ($_POST['currency'] ?? ''));
        $price = trim((string) ($_POST['price'] ?? ''));
        $dtype = trim((string) ($_POST['dtype'] ?? 'Public'));
        $type = trim((string) ($_POST['type'] ?? ''));

        if ($userId <= 0 || $gameId <= 0) {
            $this->json(['status' => 'error', 'message' => 'Invalid request.'], 400);
            return;
        }

        $verifiedHostId = $this->getVerifiedGameHostId($gameId);
        if ($verifiedHostId === null) {
            $this->json(['status' => 'error', 'message' => 'Unauthorized: You do not own this game session.'], 403);
            return;
        }

        $hostId = $verifiedHostId;

        try {
            $stmtCheck = $this->db->prepare("SELECT ID FROM ca_gamejoin WHERE USER_ID = ? AND GAME_ID = ? LIMIT 1");
            $stmtCheck->execute([$userId, $gameId]);
            $existingId = $stmtCheck->fetchColumn();

            if ($type !== 'checked') {
                if (!$existingId) {
                    $this->json(['status' => 'error', 'message' => 'Player Not yet added']);
                    return;
                }

                $stmtUpdate = $this->db->prepare("UPDATE ca_gamejoin SET CONFIRMED = 'N' WHERE ID = ?");
                $stmtUpdate->execute([$existingId]);

                $this->eventModel->applyAutoConfirmAndMessage($gameId, false);
                $this->json(['status' => 'success', 'message' => 'Status Updated successfully.']);
                return;
            }

            if ($existingId) {
                $stmtUpdate = $this->db->prepare("UPDATE ca_gamejoin SET CONFIRMED = 'Y'" . ($price !== '' ? ", PRICE = ?" : "") . " WHERE ID = ?");
                $params = $price !== '' ? [$price, $existingId] : [$existingId];
                $stmtUpdate->execute($params);
            } else {
                $isInvite = strcasecmp($dtype, 'invite') === 0;
                $typeVal = $isInvite ? 'Invite' : 'Public';
                $statusVal = 'Y';
                $stmtInsert = $this->db->prepare("
                    INSERT INTO ca_gamejoin (USER_ID, GAME_ID, HOST_ID, PRICE, CURRENCY, TYPE, STATUS, CONFIRMED, CREATED_AT)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Y', NOW())
                ");
                $stmtInsert->execute([$userId, $gameId, $hostId, $price, $currency, $typeVal, $statusVal]);
            }

            $this->eventModel->applyAutoConfirmAndMessage($gameId, false);
            $this->json(['status' => 'success', 'message' => 'Status Updated successfully.']);
        } catch (Throwable $e) {
            \error_log('[HostController::saveConfirm Error] ' . $e->getMessage());
            $this->json(['status' => 'error', 'message' => 'Failed to update confirmation status.'], 500);
        }
    }

    /**
     * API: Update Joined Player Price.
     */
    public function updatePrice(): void
    {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $gameId = (int) ($_POST['game_id'] ?? 0);
        $price = trim((string) ($_POST['price'] ?? ''));

        if ($userId <= 0 || $gameId <= 0) {
            echo "error: invalid parameters";
            return;
        }

        if ($this->getVerifiedGameHostId($gameId) === null) {
            echo "error: unauthorized";
            return;
        }

        try {
            $stmt = $this->db->prepare("UPDATE ca_gamejoin SET PRICE = ? WHERE USER_ID = ? AND GAME_ID = ?");
            $stmt->execute([$price, $userId, $gameId]);

            if ($stmt->rowCount() > 0) {
                $this->eventModel->applyAutoConfirmAndMessage($gameId, false);
                echo "success";
                return;
            }

            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM ca_gamejoin WHERE USER_ID = ? AND GAME_ID = ?");
            $checkStmt->execute([$userId, $gameId]);
            echo ($checkStmt->fetchColumn() > 0) ? "success" : "error: record not found";
        } catch (Throwable $e) {
            \error_log('[HostController::updatePrice Error] ' . $e->getMessage());
            echo "error: database exception";
        }
    }

    /**
     * API: Update Event Automation Setting.
     */
    public function updateAutomation(): void
    {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true) ?: [];
        $data = !empty($json) ? $json : $_POST;

        $gameId = (int) ($data['eventID'] ?? ($data['game_id'] ?? ($data['ID'] ?? 0)));
        $rawAutomation = strtoupper(trim((string) ($data['AUTOMATION'] ?? ($data['automation'] ?? 'N'))));
        $automation = in_array($rawAutomation, ['Y', '1', 'TRUE'], true) ? 'Y' : 'N';

        if ($gameId <= 0) {
            $this->json(['status' => 'error', 'message' => 'Invalid event ID.'], 400);
            return;
        }

        if ($this->getVerifiedGameHostId($gameId) === null) {
            $this->json(['status' => 'error', 'message' => 'Unauthorized: You do not own this game session.'], 403);
            return;
        }

        try {
            $stmt = $this->db->prepare("UPDATE ca_events SET AUTOMATION = ? WHERE ID = ?");
            $stmt->execute([$automation, $gameId]);

            $this->eventModel->applyAutoConfirmAndMessage($gameId, true);

            $this->json([
                'status'     => 'success',
                'message'    => 'Automation setting updated successfully.',
                'automation' => $automation,
                'game_id'    => $gameId,
            ]);
        } catch (Throwable $e) {
            \error_log('[HostController::updateAutomation Error] ' . $e->getMessage());
            $this->json(['status' => 'error', 'message' => 'Failed to update automation.'], 500);
        }
    }

    /**
     * API: Create Event Subscription / Scheduled Game.
     */
    public function createEventSub(): void
    {
        $sessionUserId = (int) (Session::userId() ?? ($_SESSION['user_id'] ?? 0));
        if ($sessionUserId <= 0) {
            $this->json(['success' => false, 'error' => 'Authentication required.'], 401);
            return;
        }
        $hostId = $sessionUserId;

        $defaultGameId = (int) ($_POST['id'] ?? 0);
        if ($defaultGameId > 0) {
            $chkStmt = $this->db->prepare("SELECT HOST_ID FROM ca_events_default WHERE ID = ? LIMIT 1");
            $chkStmt->execute([$defaultGameId]);
            $tmplHostId = $chkStmt->fetchColumn();
            if ($tmplHostId === false || (int) $tmplHostId !== $hostId) {
                $this->json(['success' => false, 'error' => 'Unauthorized template access.'], 403);
                return;
            }
        }

        $this->db->beginTransaction();
        try {
            $sql = "INSERT INTO ca_events (
                HOST_ID, HOST_NAME, EVENT_COUNTRY, EVENT_PROVINCE, EVENT_CITY, EVENT_CURRENCY,
                EVENT_VENUE, EVENT_CATEGORY, GENDER_CATEGORY, GENDER_SKILL_LEVEL,
                EVENT_TYPE, EVENT_DATE, EVENT_TIME, TO_TIME, CANCEL_DATE, CANCEL_TIME,
                EVENT_COST, EVENT_DISCOUNT, EVENT_DESCRIPTION, EVENT_MESSAGE, STATUS,
                FACILITY_COST, ACCESSORIES_COST, SNACKS_COST, TOTAL_EVENT_COST,
                TOTAL_PLAYER_COST, PROFIT_LOSS
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?
            )";

            $stmt = $this->db->prepare($sql);
            $eventValues = $this->extractSubEventData($_POST, $hostId);
            $stmt->execute($eventValues);

            $newGameId = (int) $this->db->lastInsertId();

            if (trim((string) ($_POST['event_category'] ?? '')) === 'Badminton Game') {
                $this->updateBadmintonEventCosts(
                    $newGameId,
                    $hostId,
                    trim((string) ($_POST['event_venue'] ?? '')),
                    trim((string) ($_POST['event_time'] ?? '')),
                    trim((string) ($_POST['to_time'] ?? '')),
                    trim((string) ($_POST['event_date'] ?? ''))
                );
            }

            if ($defaultGameId > 0) {
                $this->copyTemplateJoins($newGameId, $defaultGameId);
            }

            $this->eventModel->applyAutoConfirmAndMessage($newGameId, true);

            $this->db->commit();

            $this->json([
                'success'   => true,
                'insert_id' => $newGameId,
            ]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            \error_log('[createEventSub Error] ' . $e->getMessage());
            $this->json([
                'success' => false,
                'error'   => 'Database error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE AUTH & SECURITY HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function getAuthUser(): array
    {
        $id = (int) (Session::userId() ?? ($_SESSION['user_id'] ?? 0));
        $type = strtolower(trim((string) (Session::userType() ?? ($_SESSION['usertype'] ?? ''))));
        return ['id' => $id, 'type' => $type];
    }

    private function isAjaxRequest(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))
            || (isset($this->request) && $this->request->isAjax());
    }

    private function logActivity(int $userId, string $activityType, string $description, ?int $hostId = null, ?int $gameId = null): void
    {
        try {
            $this->playerLogModel->logActivity($userId, $activityType, $description, $hostId, $gameId);
        } catch (Throwable $e) {
            \error_log('[HostController::logActivity Error] ' . $e->getMessage());
        }
    }

    private function setFlashToast(string $type, string $message, ?string $redirect = null): void
    {
        $_SESSION['flash_toast'] = [
            'type'     => $type,
            'message'  => $message,
            'redirect' => $redirect,
        ];
    }

    private function respondPremiumError(string $message, int $statusCode, bool $isJson, ?string $plainText = null): void
    {
        if ($isJson) {
            $this->json(['success' => false, 'message' => $message], $statusCode);
        }
        http_response_code($statusCode);
        echo $plainText ?? $message;
    }

    private function isAuthorizedHostForPlayer(int $hostId, int $playerId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM ca_users u WHERE u.ID = :player_id AND u.DEL_STATUS = 'N'
            AND (
                EXISTS (SELECT 1 FROM ca_gamejoin cg WHERE cg.USER_ID = u.ID AND cg.HOST_ID = :host_id1 AND cg.STATUS = 'Y')
                OR EXISTS (SELECT 1 FROM ca_expense ce WHERE ce.USER_ID = u.ID AND ce.HOST_ID = :host_id2 AND ce.STATUS = 'Y')
                OR EXISTS (SELECT 1 FROM ca_events ev JOIN ca_gamejoin cgj ON (ev.ID = cgj.EVENT_ID OR ev.ID = cgj.GAME_ID) WHERE cgj.USER_ID = u.ID AND ev.HOST_ID = :host_id3 AND ev.DEL_STATUS = 'N')
                OR EXISTS (SELECT 1 FROM ca_player_club_status pcs WHERE pcs.player_id = u.ID AND pcs.host_id = :host_id4 AND (pcs.status = 'accepted' OR pcs.status = 'Y'))
            ) LIMIT 1
        ");
        $stmt->execute([
            ':player_id' => $playerId,
            ':host_id1'  => $hostId,
            ':host_id2'  => $hostId,
            ':host_id3'  => $hostId,
            ':host_id4'  => $hostId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    private function isAuthorizedHostForEventPlayer(int $hostId, int $eventId, int $playerId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM ca_events e 
            JOIN ca_gamejoin gj ON (e.ID = gj.EVENT_ID OR e.ID = gj.GAME_ID)
            WHERE e.ID = :event_id AND e.HOST_ID = :host_id AND gj.USER_ID = :player_id AND e.DEL_STATUS = 'N'
            LIMIT 1
        ");
        $stmt->execute([
            ':event_id'  => $eventId,
            ':host_id'   => $hostId,
            ':player_id' => $playerId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    private function getVerifiedGameHostId(int $gameId): ?int
    {
        $auth = $this->getAuthUser();
        $authUserId = $auth['id'];
        $authUserType = $auth['type'];

        if ($gameId <= 0 || $authUserId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT HOST_ID FROM ca_events WHERE ID = ? LIMIT 1");
        $stmt->execute([$gameId]);
        $eventHostId = (int) ($stmt->fetchColumn() ?: 0);

        if ($eventHostId <= 0) {
            return null;
        }

        if ($authUserType === 'admin' || $eventHostId === $authUserId) {
            return $eventHostId;
        }

        return null;
    }

    private function resolvePaymentListHostId(int|array $options, array $auth): ?int
    {
        $authUserId = $auth['id'];
        $authUserType = $auth['type'];

        $hostId = is_array($options) ? (int) ($options['host_id'] ?? 0) : (int) $options;
        if ($hostId <= 0) {
            $hostId = (!empty($_REQUEST['host_id']) && in_array($authUserType, ['host', 'trainer', 'admin'], true))
                ? (int) $_REQUEST['host_id']
                : $authUserId;
        }

        if (in_array($authUserType, ['host', 'trainer'], true)) {
            return $authUserId;
        }

        if ($authUserType === 'player') {
            return (int) ($_REQUEST['host_id'] ?? $_SESSION['mapped_host_id'] ?? 0);
        }

        if ($authUserType === 'admin') {
            return $hostId;
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE DASHBOARD & DATA RETRIEVAL HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function resolveClubAccessFlags(string $joinType): array
    {
        $isSuperHost = ($joinType === 'H');
        $isRequestJoinHost = ($joinType === 'R');
        $isAutoJoinHost = ($joinType === 'A');

        return [
            'isSuperHost'         => $isSuperHost,
            'isRequestJoinHost'   => $isRequestJoinHost,
            'isAutoJoinHost'      => $isAutoJoinHost,
            'canViewSpecialTabs'  => $isSuperHost,
            'canModerateRequests' => ($isRequestJoinHost || $isSuperHost),
        ];
    }

    private function loadDashboardSpecialData(int $hostId, bool $canViewSpecialTabs): array
    {
        $eventsByDay = array_fill_keys(self::DAYS_OF_WEEK, []);
        $venues = [];
        $categories = [];

        if ($canViewSpecialTabs) {
            try {
                $eventsByDay = $this->loadDefaultEventsByDay($hostId, $eventsByDay);
                $venues = $this->db->query('SELECT NAME FROM ca_venue ORDER BY NAME ASC')->fetchAll(PDO::FETCH_ASSOC);
                $categories = $this->db->query('SELECT NAME FROM ca_event_category ORDER BY NAME ASC')->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                \error_log('[HostController::loadDashboardSpecialData Error] ' . $e->getMessage());
            }
        }

        return [
            'eventsByDay' => $eventsByDay,
            'venues'      => $venues,
            'categories'  => $categories,
        ];
    }

    private function getPendingRequestsCount(int $hostId): int
    {
        $stmtPending = $this->db->prepare("SELECT COUNT(*) FROM ca_player_club_status WHERE host_id = ? AND status = 'pending'");
        $stmtPending->execute([$hostId]);
        return (int) $stmtPending->fetchColumn();
    }

    private function loadDefaultEventsByDay(int $hostId, array $eventsByDay): array
    {
        $stmt = $this->db->prepare('SELECT * FROM ca_events_default WHERE HOST_ID = ? ORDER BY DAY, EVENT_TIME');
        $stmt->execute([$hostId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $event) {
            $day = strtoupper(trim((string) ($event['DAY'] ?? '')));
            if (isset($eventsByDay[$day])) {
                $eventsByDay[$day][] = $event;
            }
        }
        return $eventsByDay;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE SCHEDULE & FILTER HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function buildEventFilters(
        int $hostId,
        ?int $year,
        ?int $month,
        ?string $eventCategory,
        bool $isPlayer,
        bool $joinedOnly = false
    ): array {
        $gender = $isPlayer ? ($_SESSION['gender'] ?? '') : '';
        $skillLevel = $isPlayer ? (!empty($_SESSION['vlevel']) ? $_SESSION['vlevel'] : (!empty($_SESSION['level']) ? $_SESSION['level'] : '')) : '';
        $sessionUserId = (int) (Session::userId() ?? ($_SESSION['user_id'] ?? 0));

        if ($isPlayer && $sessionUserId > 0 && ($gender === '' || $skillLevel === '')) {
            $user = $this->userModel->findById($sessionUserId);
            if ($user) {
                if ($gender === '') {
                    $gender = (string) ($user['GENDER'] ?? '');
                    $_SESSION['gender'] = $gender;
                }
                if ($skillLevel === '') {
                    $skillLevel = !empty($user['VERIFIED_LEVEL']) ? (string) $user['VERIFIED_LEVEL'] : ((string) ($user['LEVEL'] ?? ''));
                    $_SESSION['vlevel'] = $skillLevel;
                }
            }
        }

        return [
            'host_id'         => $hostId,
            'year'            => $year,
            'month'           => $month,
            'event_category'  => $eventCategory,
            'is_player'       => $isPlayer,
            'joined_only'     => $joinedOnly,
            'gender'          => $gender,
            'skill_level'     => $skillLevel,
            'session_user_id' => $sessionUserId,
            'player_id'       => $sessionUserId,
        ];
    }

    private function resolveScheduleContext(array $args = []): array
    {
        try {
            [$year, $month, $eventCategory, $hostId, $isPlayer] = $this->unpackScheduleArgs($args);

            $userType = trim((string) (Session::userType() ?? ($_SESSION['usertype'] ?? '')));
            $resolvedIsPlayer = $isPlayer !== null ? $isPlayer : (strcasecmp($userType, 'Player') === 0);

            $reqHostId = (int) ($hostId ?? ($_POST['host_id'] ?? ($_POST['host'] ?? ($_GET['host_id'] ?? ($_GET['host'] ?? 0)))));
            $sessionUserId = (int) (Session::userId() ?? ($_SESSION['user_id'] ?? 0));
            $resolvedHostId = $resolvedIsPlayer
                ? ($reqHostId > 0 ? $reqHostId : (int) ($_SESSION['mapped_host_id'] ?? 0))
                : ($sessionUserId > 0 ? $sessionUserId : $reqHostId);

            if ($resolvedIsPlayer && $resolvedHostId <= 0 && $sessionUserId > 0) {
                $stmtClub = $this->db->prepare(
                    "SELECT host_id FROM ca_player_club_status WHERE player_id = ? AND (status = 'accepted' OR status = 'Y') ORDER BY id DESC LIMIT 1"
                );
                $stmtClub->execute([$sessionUserId]);
                $clubHostId = (int) ($stmtClub->fetchColumn() ?: 0);
                if ($clubHostId > 0) {
                    $resolvedHostId = $clubHostId;
                }
            }

            $resolvedYear = $year !== null ? $year : (!empty($_POST['year']) ? (int) $_POST['year'] : (!empty($_GET['year']) ? (int) $_GET['year'] : (int) date('Y')));
            $resolvedMonth = $month !== null ? $month : (!empty($_POST['month']) ? (int) $_POST['month'] : (!empty($_GET['month']) ? (int) $_GET['month'] : (int) date('n')));
            $resolvedCategory = $eventCategory !== null ? trim($eventCategory) : trim((string) ($_POST['event_category'] ?? ($_GET['event_category'] ?? ($_GET['category'] ?? ''))));

            return [
                'hostId'        => $resolvedHostId,
                'year'          => $resolvedYear,
                'month'         => $resolvedMonth,
                'eventCategory' => $resolvedCategory,
                'isPlayer'      => $resolvedIsPlayer,
            ];
        } catch (Throwable $e) {
            \error_log('[HostController::resolveScheduleContext Error] ' . $e->getMessage());
            return [
                'hostId'        => (int) (Session::userId() ?? ($_SESSION['user_id'] ?? 0)),
                'year'          => (int) date('Y'),
                'month'         => (int) date('n'),
                'eventCategory' => '',
                'isPlayer'      => false,
            ];
        }
    }

    private function unpackScheduleArgs(array $args): array
    {
        $first = $args[0] ?? null;
        if (is_array($first)) {
            return [
                isset($first['year']) ? (int) $first['year'] : null,
                isset($first['month']) ? (int) $first['month'] : null,
                $first['event_category'] ?? ($first['category'] ?? null),
                isset($first['host_id']) ? (int) $first['host_id'] : ($first['host'] ?? null),
                isset($first['is_player']) ? (bool) $first['is_player'] : null,
            ];
        }

        return [
            isset($args[0]) && $args[0] !== null ? (int) $args[0] : null,
            isset($args[1]) && $args[1] !== null ? (int) $args[1] : null,
            isset($args[2]) && $args[2] !== null ? (string) $args[2] : null,
            isset($args[3]) && $args[3] !== null ? (int) $args[3] : null,
            isset($args[4]) && $args[4] !== null ? (bool) $args[4] : null,
        ];
    }

    private function resolvePlayerStatsFilters(array|int $options = []): array
    {
        try {
            $base = is_array($options) ? $options : ['host_id' => (int) $options];
            $auth = $this->getAuthUser();
            $authUserId = $auth['id'];
            $authUserType = $auth['type'];

            $resolvedHostId = in_array($authUserType, ['host', 'trainer'], true) && $authUserId > 0
                ? $authUserId
                : (!empty($base['host_id']) ? (int) $base['host_id'] : (int) ($_REQUEST['host_id'] ?? $authUserId));

            return [
                'host_id'     => $resolvedHostId,
                'skill_level' => trim((string) ($base['skill_level'] ?? ($_REQUEST['skill_level'] ?? ($_REQUEST['level'] ?? '')))),
                'gender'      => trim((string) ($base['gender'] ?? ($_REQUEST['gender'] ?? ''))),
                'status'      => trim((string) ($base['status'] ?? ($_REQUEST['status'] ?? ''))),
                'search'      => trim((string) ($base['search'] ?? ($_REQUEST['search'] ?? ($_REQUEST['q'] ?? '')))),
                'player_id'   => (int) ($base['player_id'] ?? ($_REQUEST['player_id'] ?? 0)),
            ];
        } catch (Throwable $e) {
            \error_log('[HostController::resolvePlayerStatsFilters Error] ' . $e->getMessage());
            return [
                'host_id'     => (int) (Session::userId() ?? ($_SESSION['user_id'] ?? 0)),
                'skill_level' => '',
                'gender'      => '',
                'status'      => '',
                'search'      => '',
                'player_id'   => 0,
            ];
        }
    }

    private function buildPlayerSearchFilterClauses(string $gender, string $level, array &$params): array
    {
        $clauses = [];

        if ($gender !== '' && !in_array(strtolower($gender), ['mix', 'all'], true)) {
            $genderLower = strtolower($gender);
            if (isset(self::SPECIAL_GENDER_CLAUSES[$genderLower])) {
                $clauses[] = self::SPECIAL_GENDER_CLAUSES[$genderLower];
            } else {
                $clauses[] = "cu.GENDER = :gender";
                $params['gender'] = $gender;
            }
        }

        if ($level !== '' && !in_array(strtolower($level), ['mix', 'all'], true)) {
            $lvlLower = strtolower($level);
            if (isset(self::SPECIAL_LEVEL_CLAUSES[$lvlLower])) {
                $clauses[] = self::SPECIAL_LEVEL_CLAUSES[$lvlLower];
            } else {
                $clauses[] = "COALESCE(NULLIF(cu.VERIFIED_LEVEL, ''), cu.LEVEL) = :verified_level";
                $params['verified_level'] = $level;
            }
        }

        return $clauses;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE PAYMENT & LEDGER HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function resolveDetailedLedgerFilters(int|array $options, int $playerId, int $year, int $month, array $auth): array
    {
        if (is_array($options)) {
            return $options;
        }

        $authUserId = $auth['id'];
        $authUserType = $auth['type'];

        return [
            'host_id'        => $options > 0 ? $options : (in_array($authUserType, ['host', 'trainer'], true) ? $authUserId : (int) ($_REQUEST['host_id'] ?? 0)),
            'player_id'      => $playerId > 0 ? $playerId : (int) ($_REQUEST['player_id'] ?? ($_REQUEST['user_id'] ?? ($authUserType === 'player' ? $authUserId : 0))),
            'year'           => $year > 0 ? $year : (int) ($_REQUEST['year'] ?? (int) date('Y')),
            'month'          => $month > 0 ? $month : (int) ($_REQUEST['month'] ?? (int) date('n')),
            'is_player_side' => isset($_REQUEST['is_player_side']) ? (bool) $_REQUEST['is_player_side'] : ($authUserType === 'player'),
        ];
    }

    private function fetchPaymentListUsers(int $hostId, ?int $filterPlayerId, string $searchQuery): array
    {
        $sql = "
            SELECT DISTINCT u.ID, u.NAME, u.EMAIL, u.WHATSAPP_NUMBER, u.PROFILE_IMAGE, u.PREMIUM
            FROM ca_users u
            WHERE u.USERTYPE = 'Player'
              AND u.DEL_STATUS = 'N'
              AND (
                EXISTS (SELECT 1 FROM ca_gamejoin cg WHERE cg.USER_ID = u.ID AND cg.HOST_ID = :host_id1 AND cg.STATUS = 'Y')
                OR EXISTS (SELECT 1 FROM ca_expense ce WHERE ce.USER_ID = u.ID AND ce.HOST_ID = :host_id2 AND ce.STATUS = 'Y')
              )
        ";
        $params = [
            ':host_id1' => $hostId,
            ':host_id2' => $hostId,
        ];

        if ($filterPlayerId && $filterPlayerId > 0) {
            $sql .= " AND u.ID = :player_id";
            $params[':player_id'] = $filterPlayerId;
        }

        if ($searchQuery !== '') {
            $sql .= " AND (u.NAME LIKE :s1 OR u.EMAIL LIKE :s2 OR u.WHATSAPP_NUMBER LIKE :s3)";
            $term = "%{$searchQuery}%";
            $params[':s1'] = $term;
            $params[':s2'] = $term;
            $params[':s3'] = $term;
        }

        $sql .= " ORDER BY u.NAME ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function buildPlayerPaymentSummary(
        LedgerService $ledgerService,
        array $userData,
        int $hostId,
        int $filterYear,
        int $filterMonth
    ): array {
        $uid = (int) ($userData['ID'] ?? 0);
        $summary = $ledgerService->calculateSummary($uid, $hostId, $filterYear, $filterMonth);

        return [
            'data'        => $userData,
            'gameCount'   => (int) ($summary['games'] ?? 0),
            'totalAmount' => (float) ($summary['totalExpense'] ?? 0.0),
            'totalPaid'   => (float) ($summary['totalPaid'] ?? 0.0),
            'totalDue'    => (float) ($summary['totalDue'] ?? 0.0),
        ];
    }

    private function paginatePaymentPlayers(
        array $userRows,
        LedgerService $ledgerService,
        int $hostId,
        int $filterYear,
        int $filterMonth,
        string $sortBy,
        string $sortDir,
        int $page,
        int $perPage
    ): array {
        $totalCount = count($userRows);

        if ($sortBy === 'name') {
            if ($sortDir === 'desc') {
                $userRows = array_reverse($userRows);
            }
            $pagination = new Pagination($totalCount, $page, $perPage);
            $offset = $pagination->getOffset();
            $pageUserRows = array_slice($userRows, $offset, $perPage);

            $pagePlayers = [];
            foreach ($pageUserRows as $data) {
                $pagePlayers[] = $this->buildPlayerPaymentSummary($ledgerService, $data, $hostId, $filterYear, $filterMonth);
            }

            return ['players' => $pagePlayers, 'pagination' => $pagination];
        }

        $players = [];
        foreach ($userRows as $data) {
            $players[] = $this->buildPlayerPaymentSummary($ledgerService, $data, $hostId, $filterYear, $filterMonth);
        }

        usort($players, static function (array $a, array $b) use ($sortBy, $sortDir): int {
            $cmp = match ($sortBy) {
                'games'  => $a['gameCount'] <=> $b['gameCount'],
                'amount' => $a['totalAmount'] <=> $b['totalAmount'],
                'paid'   => $a['totalPaid'] <=> $b['totalPaid'],
                'due'    => $a['totalDue'] <=> $b['totalDue'],
                default  => strcasecmp((string) ($a['data']['NAME'] ?? ''), (string) ($b['data']['NAME'] ?? '')),
            };
            return $sortDir === 'desc' ? -$cmp : $cmp;
        });

        $paged = Pagination::paginateArray($players, $page, $perPage);
        return ['players' => $paged['items'], 'pagination' => $paged['pagination']];
    }

    private function respondEmptyPaymentList(string $searchQuery, int $perPage, bool $isJson): string
    {
        $emptyHtml = LedgerPresenter::renderEmptyPaymentListHtml($searchQuery);
        if ($isJson) {
            $this->json([
                'success' => true,
                'data'    => [
                    'players'    => [],
                    'total'      => 0,
                    'page'       => 1,
                    'pageSize'   => $perPage,
                    'totalPages' => 0,
                ],
                'html'    => $emptyHtml,
            ]);
        }

        echo $emptyHtml;
        return $emptyHtml;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE GAME DATA & ROSTER HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function extractEventIdFromRequest(): int
    {
        $id = (int) ($_POST['id'] ?? ($_POST['event_id'] ?? ($_GET['id'] ?? 0)));
        if ($id > 0) {
            return $id;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        return (int) ($data['id'] ?? ($data['event_id'] ?? ($data['body']['id'] ?? ($data['body']['event_id'] ?? 0))));
    }

    private function buildGameData(array $input, int $userId): array
    {
        return [
            'HOST_ID'            => $userId,
            'HOST_NAME'          => trim((string) ($input['host_name'] ?? ($_SESSION['name'] ?? ''))),
            'EVENT_COUNTRY'      => trim((string) ($input['event_country'] ?? '')),
            'EVENT_PROVINCE'     => trim((string) ($input['event_province'] ?? '')),
            'EVENT_CITY'         => trim((string) ($input['event_city'] ?? '')),
            'EVENT_CURRENCY'     => trim((string) ($input['event_currency'] ?? 'CAD')),
            'EVENT_VENUE'        => trim((string) ($input['event_venue'] ?? '')),
            'EVENT_CATEGORY'     => trim((string) ($input['event_category'] ?? '')),
            'GENDER_CATEGORY'    => trim((string) ($input['gender_category'] ?? '')),
            'GENDER_SKILL_LEVEL' => trim((string) ($input['gender_skill_level'] ?? '')),
            'EVENT_TYPE'         => trim((string) ($input['event_type'] ?? '')),
            'EVENT_DATE'         => trim((string) ($input['event_date'] ?? '')),
            'EVENT_TIME'         => trim((string) ($input['event_time'] ?? '')),
            'TO_TIME'            => trim((string) ($input['to_Time'] ?? $input['to_time'] ?? '')),
            'CANCEL_DATE'        => trim((string) ($input['freeze_date'] ?? '')),
            'CANCEL_TIME'        => trim((string) ($input['freeze_time'] ?? '')),
            'EVENT_COST'         => trim((string) ($input['event_cost'] ?? '0.00')),
            'EVENT_DISCOUNT'     => trim((string) ($input['event_discount'] ?? '0.00')),
            'EVENT_DESCRIPTION'  => trim((string) ($input['event_description'] ?? '')),
            'EVENT_MESSAGE'      => trim((string) ($input['event_message'] ?? '')),
            'STATUS'             => 'Active',
        ];
    }

    private function fetchGamePlayersList(
        int $gameId,
        bool $isJoinedOnly,
        string $gender,
        string $level,
        string $name
    ): array {
        $conditions = ["cu.USERTYPE = 'Player'", "cu.DEL_STATUS = 'N'"];
        $params = ['game_id' => $gameId, 'event_game_id' => $gameId];

        if ($isJoinedOnly) {
            $conditions[] = "cg.USER_ID IS NOT NULL AND cg.STATUS = 'Y'";
        } else {
            $filterClauses = $this->buildPlayerSearchFilterClauses($gender, $level, $params);
            if (!empty($filterClauses)) {
                $conditions[] = "(cg.USER_ID IS NOT NULL OR (" . implode(' AND ', $filterClauses) . "))";
            }
        }

        if ($name !== '') {
            $conditions[] = "cu.NAME LIKE :name";
            $params['name'] = '%' . $name . '%';
        }

        $where = implode(' AND ', $conditions);
        $stmt = $this->db->prepare("
            SELECT cu.ID, cu.NAME,
                COALESCE(NULLIF(cu.VERIFIED_LEVEL, ''), cu.LEVEL, 'N/A') AS LEVEL,
                cu.GENDER, cg.CONFIRMED, cg.PRICE AS JOINED_PRICE,
                IF(cg.USER_ID IS NOT NULL, 1, 0) AS IS_JOINED,
                e.EVENT_COST, e.EVENT_CURRENCY, e.HOST_ID,
                COALESCE(NULLIF(cg.PRICE, ''), e.EVENT_COST, 0) AS EFFECTIVE_PRICE
            FROM ca_users AS cu
            LEFT JOIN ca_gamejoin AS cg ON cg.USER_ID = cu.ID AND cg.GAME_ID = :game_id
            LEFT JOIN ca_events AS e ON e.ID = :event_game_id
            WHERE {$where}
            ORDER BY IS_JOINED DESC, cu.NAME ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function formatAndPartitionPlayers(array &$allPlayers): array
    {
        $joined = [];
        $notJoined = [];

        foreach ($allPlayers as &$player) {
            $player['DISPLAY_LEVEL'] = self::LEVEL_MAP[trim((string) $player['LEVEL'])] ?? ($player['LEVEL'] ?: 'N/A');
            $player['DISPLAY_GENDER'] = self::GENDER_MAP[trim((string) $player['GENDER'])] ?? ($player['GENDER'] ?: '-');
            $player['DISPLAY_NAME'] = self::formatShortName((string) $player['NAME']);

            if ((int) ($player['IS_JOINED'] ?? 0) === 1) {
                $joined[] = $player;
            } else {
                $notJoined[] = $player;
            }
        }
        unset($player);

        return ['joined' => $joined, 'not_joined' => $notJoined];
    }

    private function resolveGamePlayerEventDetails(int $gameId, array $firstPlayer, array $ev): array
    {
        $hostId = (int) ($firstPlayer['HOST_ID'] ?? ($_SESSION['user_id'] ?? 0));
        $cost = $firstPlayer['EVENT_COST'] ?? 0;
        $rawCurr = $firstPlayer['EVENT_CURRENCY'] ?? '$';
        $curr = ($rawCurr === 'CAD' || empty($rawCurr)) ? '$' : $rawCurr;

        if (empty($firstPlayer)) {
            $hostId = (int) ($ev['HOST_ID'] ?? $hostId);
            $cost = $ev['EVENT_COST'] ?? $cost;
            $rawCurr = $ev['EVENT_CURRENCY'] ?? $curr;
            $curr = ($rawCurr === 'CAD' || empty($rawCurr)) ? '$' : $rawCurr;
        }

        return [
            'hostId'   => $hostId,
            'cost'     => $cost,
            'currency' => $curr,
        ];
    }

    private function extractSubEventData(array $input, int $hostId): array
    {
        $val = static fn(string $key, string $default = ''): string => trim((string) ($input[$key] ?? $default));

        return [
            $hostId,
            $val('host_name'),
            $val('event_country'),
            $val('event_province'),
            $val('event_city'),
            $val('event_currency'),
            $val('event_venue'),
            $val('event_category'),
            $val('gender_category'),
            $val('gender_skill_level'),
            $val('event_type'),
            $val('event_date'),
            $val('event_time'),
            $val('to_time'),
            $val('freeze_date'),
            $val('freeze_time'),
            $val('event_cost', '0'),
            $val('event_discount', '0'),
            $val('event_description'),
            $val('event_message'),
            $val('status', 'Active'),
            $val('facilitycost', '0'),
            $val('accessoriesCost', '0'),
            $val('snackscost', '0'),
            $val('eventtotalCostt', '0'),
            $val('eventtotalplayerCostt', '0'),
            $val('profitloss', '0'),
        ];
    }

    private function updateBadmintonEventCosts(
        int $newGameId,
        int $hostId,
        string $venue,
        string $eventTime,
        string $toTime,
        string $eventDate
    ): void {
        $calc = $this->eventModel->calculateBadmintonCosts(
            $hostId,
            $venue,
            $eventTime,
            $toTime,
            $eventDate,
            4,
            1
        );

        $updateStmt = $this->db->prepare("
            UPDATE ca_events SET
                EVENT_COST = ?,
                FACILITY_COST = ?,
                ACCESSORIES_COST = ?,
                TOTAL_EVENT_COST = ?,
                CLUB_COST = ?,
                BIRDIE_USED = ?,
                BIRDIE_PRICE = ?,
                BIRDIE_COST = ?,
                JOIN_MESSAGE = '0 court confirmed 6 spots available for first court'
            WHERE ID = ?
        ");
        $updateStmt->execute([
            $calc['per_player_cost'],
            $calc['facility_cost'],
            $calc['accessories_cost'],
            $calc['total_event_cost'],
            $calc['club_cost'],
            $calc['birdies_used'],
            $calc['birdie_price'],
            $calc['accessories_cost'],
            $newGameId,
        ]);
    }

    private function copyTemplateJoins(int $newGameId, int $defaultGameId): void
    {
        $selectStmt = $this->db->prepare("SELECT * FROM ca_gamejoin_default WHERE GAME_ID = ?");
        $selectStmt->execute([$defaultGameId]);
        $joinedData = $selectStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (empty($joinedData)) {
            return;
        }

        $insertJoinStmt = $this->db->prepare("
            INSERT INTO ca_gamejoin (
                USER_ID, GAME_ID, HOST_ID, PRICE, CURRENCY, TYPE, STATUS, CONFIRMED, CREATED_AT
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'N', ?)
        ");
        $now = date('Y-m-d H:i:s');
        foreach ($joinedData as $row) {
            $insertJoinStmt->execute([
                (int) $row['USER_ID'],
                $newGameId,
                (int) $row['HOST_ID'],
                trim((string) ($row['PRICE'] ?? '')),
                trim((string) ($row['CURRENCY'] ?? '')),
                trim((string) ($row['TYPE'] ?? '')),
                trim((string) ($row['STATUS'] ?? '')),
                $now,
            ]);
        }
    }
}
