<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Controller;
use App\Models\UserModel;
use App\Models\EventModel;
use App\Models\Tournament\TournamentModel;
use App\Models\Player\GameJoinModel;
use App\Helpers\Session;
use PDO;
use PDOException;

class PlayerController extends Controller
{
    private const string DEFAULT_PROFILE_IMG = '/assets/images/profile.jpg';
    private const string DEFAULT_PRODUCT_IMG = '/assets/images/t-shirt.jpg';
    private const string ADMIN_ASSETS_DIR = 'admin/assets/';
    private const string ADMIN_DIR = 'admin/';
    private const string PRODUCT_IMAGES_DIR = 'assets/images/product/';

    protected PDO $db;
    protected UserModel $userModel;
    protected EventModel $eventModel;
    protected TournamentModel $tournamentModel;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getPdo();
        $this->userModel = new UserModel($this->db);
        $this->eventModel = new EventModel();
        $this->tournamentModel = new TournamentModel($this->db);
    }

    // Validate Host ID
    private function isValidHost(int $hostId): bool
    {
        if ($hostId <= 0) {
            return false;
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT 1 FROM ca_users WHERE ID = ? AND LOWER(USERTYPE) IN ('host', 'trainer') LIMIT 1"
            );
            $stmt->execute([$hostId]);
            if ($stmt->fetchColumn()) {
                return true;
            }

            $stmtClub = $this->db->prepare("SELECT 1 FROM ca_clubs WHERE host_id = ? LIMIT 1");
            $stmtClub->execute([$hostId]);
            return (bool) $stmtClub->fetchColumn();
        } catch (PDOException $e) {
            error_log("isValidHost error: " . $e->getMessage());
            return false;
        }
    }

    // Resolve Host ID Context
    private function resolveHostId(int $userId, ?int $requestedHostId = null): int
    {
        $candidateHost = ($requestedHostId !== null && $requestedHostId > 0)
            ? $requestedHostId
            : (isset($_GET['host_id']) && (int) $_GET['host_id'] > 0
                ? (int) $_GET['host_id']
                : (int) ($_SESSION['mapped_host_id'] ?? 0));

        if ($candidateHost > 0 && $this->isValidHost($candidateHost)) {
            return $candidateHost;
        }

        if ($userId > 0) {
            $conn = Database::getMysqli();
            $baseDir = dirname(__DIR__, 2);
            if (file_exists($baseDir . '/app/Helpers/audit.php')) {
                require_once $baseDir . '/app/Helpers/audit.php';
            }
            if (function_exists('auditResolveHostId')) {
                $resolved = auditResolveHostId($conn, $userId);
                if ($resolved > 0 && $this->isValidHost($resolved)) {
                    return $resolved;
                }
            }
        }

        return 0;
    }

    // Resolve Ledger Period
    private function resolveLedgerPeriod(array $input): array
    {
        $month = isset($input['month']) ? max(1, min(12, (int) $input['month'])) : (int) date('n');
        $year = isset($input['year']) && (int) $input['year'] > 2000 ? (int) $input['year'] : (int) date('Y');
        return [$year, $month];
    }

    // Hub Action
    public function hub(): void
    {
        $userId = (int) (Session::userId() ?: ($_SESSION['user_id'] ?? 0));
        Session::getCsrfToken();

        $userEmail = Session::get('email', '');
        $baseDir = dirname(__DIR__, 2) . '/';

        $userInfo = $this->getUserInfo($userId);
        $clubs = $this->getClubs($userId, $userInfo);
        $upcomingTournaments = $this->getTournaments($userId, $baseDir);
        $totalSessions = $this->getTotalSessions($userId);
        [$products, $orderMap] = $this->getProductsAndOrders($userEmail, $baseDir);
        $reviews = $this->getReviews();

        $userProfileImg = !empty($userInfo->prefs['PROFILE_IMAGE']) ? $userInfo->prefs['PROFILE_IMAGE'] : Session::get('profileImage', '');
        $imagePath = $this->resolveProfileImage((string) $userProfileImg, $baseDir);

        $playerName = htmlspecialchars($userInfo->prefs['NAME'] ?? Session::get('name', 'N/A'), ENT_QUOTES, 'UTF-8');
        $playerGender = htmlspecialchars($userInfo->prefs['GENDER'] ?? Session::get('gender', 'N/A'), ENT_QUOTES, 'UTF-8');
        $playerLevel = htmlspecialchars($userInfo->prefs['VERIFIED_LEVEL'] ?? Session::get('vlevel', $userInfo->prefs['LEVEL'] ?? 'N/A'), ENT_QUOTES, 'UTF-8');
        $playerArea = htmlspecialchars($userInfo->city ?: 'N/A', ENT_QUOTES, 'UTF-8');

        $viewName = file_exists(__DIR__ . '/../Views/player/player-hub.php') ? 'player/player-hub' : 'player/hub';
        $this->render($viewName, [
            'current_user_id' => $userId,
            'user_premium' => $userInfo->premium,
            'player_prefs' => $userInfo->prefs,
            'player_game' => $userInfo->game,
            'player_country' => $userInfo->country,
            'player_province' => $userInfo->province,
            'player_city' => $userInfo->city,
            'clubs' => $clubs,
            'upcomingTournaments' => $upcomingTournaments,
            'total_sessions' => $totalSessions,
            'products' => $products,
            'orderMap' => $orderMap,
            'reviews' => $reviews,
            'isPlayer' => true,
            'user_profile_img' => $imagePath,
            'player_name' => $playerName,
            'player_gender' => $playerGender,
            'player_level' => $playerLevel,
            'player_area' => $playerArea,
        ]);
    }

    // Dashboard Action
    public function dashboard(): void
    {
        $userId = (int) (Session::userId() ?: ($_SESSION['user_id'] ?? 0));
        $hostId = $this->resolveHostId($userId);

        $upcomingTournaments = $this->tournamentModel->getUpcomingActiveTournaments();

        $vlevel = (string) ($_SESSION['vlevel'] ?? '');
        $gender = (string) ($_SESSION['gender'] ?? '');

        if (($vlevel === '' || $gender === '') && $userId > 0) {
            $uData = $this->userModel->find($userId);
            if ($uData) {
                if ($vlevel === '') {
                    $vlevel = !empty($uData['VERIFIED_LEVEL']) ? (string) $uData['VERIFIED_LEVEL'] : ((string) ($uData['LEVEL'] ?? ''));
                    $_SESSION['vlevel'] = $vlevel;
                }
                if ($gender === '') {
                    $gender = (string) ($uData['GENDER'] ?? '');
                    $_SESSION['gender'] = $gender;
                }
            }
        }

        $subController = new \App\Controllers\Player\PlayerSubscriptionController($this->db);
        $subData = $subController->getSubscriptionViewData($userId, $hostId, $vlevel, $gender);

        $clubModel = new \App\Models\ClubModel($this->db);
        $club = $hostId > 0 ? $clubModel->findByHostId($hostId) : null;
        $clubName = !empty($club['club_name']) ? (string) $club['club_name'] : null;

        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');

        $premiumStatus = $this->getPlayerPremiumStatus($userId);
        $joinedGames = $this->getPlayerHostJoinedGames($userId, $hostId);
        [$selMonth, $selYear] = $this->resolvePlayerLedgerSelectedMonth($userId, $hostId, $currentMonth, $currentYear);

        $this->render('player/player-dashboard', array_merge([
            'current_user_id' => $userId,
            'host_id' => $hostId,
            'club' => $club,
            'club_name' => $clubName,
            'upcomingTournaments' => $upcomingTournaments,
            'active_tab' => $_GET['tab'] ?? 'Upcoming',
            'premiumStatus' => $premiumStatus,
            'joined_games' => $joinedGames,
            'selMonth' => $selMonth,
            'selYear' => $selYear,
        ], $subData));
    }

    // Get Player Premium Status
    public function getPlayerPremiumStatus(int $userId): string
    {
        if ($userId <= 0) return 'Y';
        try {
            $stmt = $this->db->prepare("SELECT PREMIUM FROM ca_users WHERE ID = ? LIMIT 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['PREMIUM'] ?? 'Y';
        } catch (PDOException $e) {
            error_log("getPlayerPremiumStatus error: " . $e->getMessage());
            return 'Y';
        }
    }

    // Get Joined Games for Pay Modal Dropdown
    public function getPlayerHostJoinedGames(int $userId, int $hostId): array
    {
        if ($userId <= 0 || $hostId <= 0) return [];
        try {
            $sql = "SELECT ce.ID, ce.EVENT_DATE, ce.EVENT_TIME, ce.EVENT_VENUE, ce.HOST_NAME 
                    FROM ca_gamejoin cg
                    INNER JOIN ca_events ce ON cg.GAME_ID = ce.ID
                    WHERE cg.USER_ID = ? 
                      AND cg.STATUS = 'Y' 
                      AND (ce.STATUS != 'Cancelled' AND ce.STATUS != 'C')
                      AND ce.HOST_ID = ?
                    ORDER BY ce.EVENT_DATE DESC, ce.EVENT_TIME DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $hostId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("getPlayerHostJoinedGames error: " . $e->getMessage());
            return [];
        }
    }

    // Determine Selected Month & Year for Ledger
    public function resolvePlayerLedgerSelectedMonth(int $userId, int $hostId, int $currentMonth, int $currentYear): array
    {
        $selMonth = isset($_GET['month']) && !empty($_GET['month']) ? (int) $_GET['month'] : 0;
        $selYear = isset($_GET['year']) && !empty($_GET['year']) ? (int) $_GET['year'] : 0;

        if ($selMonth > 0 && $selYear > 0) {
            return [$selMonth, $selYear];
        }

        if ($hostId <= 0) {
            return [$currentMonth, $currentYear];
        }

        try {
            $stmtCur = $this->db->prepare("
                SELECT 1 FROM ca_gamejoin cg 
                JOIN ca_events ce ON cg.GAME_ID = ce.ID 
                WHERE cg.USER_ID = ? AND cg.STATUS = 'Y' 
                  AND (ce.STATUS != 'Cancelled' AND ce.STATUS != 'C')
                  AND ce.HOST_ID = ?
                  AND MONTH(ce.EVENT_DATE) = ? AND YEAR(ce.EVENT_DATE) = ?
                LIMIT 1
            ");
            $stmtCur->execute([$userId, $hostId, $currentMonth, $currentYear]);
            if ($stmtCur->fetchColumn()) {
                return [$currentMonth, $currentYear];
            }

            $sqlLatest = "
                SELECT MAX(txn_date) as max_date FROM (
                    SELECT ce.EVENT_DATE as txn_date FROM ca_gamejoin cg JOIN ca_events ce ON cg.GAME_ID = ce.ID WHERE cg.USER_ID = ? AND cg.STATUS = 'Y' AND (ce.STATUS != 'Cancelled' AND ce.STATUS != 'C') AND ce.HOST_ID = ?
                    UNION ALL
                    SELECT EXPENSE_DATE as txn_date FROM ca_expense WHERE USER_ID = ? AND STATUS = 'Y' AND HOST_ID = ?
                    UNION ALL
                    SELECT PAYMENT_DATE as txn_date FROM ca_payment WHERE USER_ID = ? AND STATUS != 'R' AND REVIEWED_BY = ?
                ) as txns
            ";
            $stmtLat = $this->db->prepare($sqlLatest);
            $stmtLat->execute([$userId, $hostId, $userId, $hostId, $userId, $hostId]);
            $lRow = $stmtLat->fetch(PDO::FETCH_ASSOC);
            if ($lRow && !empty($lRow['max_date'])) {
                $selMonth = (int) date('n', strtotime($lRow['max_date']));
                $selYear = (int) date('Y', strtotime($lRow['max_date']));
            }
        } catch (PDOException $e) {
            error_log("resolvePlayerLedgerSelectedMonth error: " . $e->getMessage());
        }

        if ($selMonth === 0 || $selYear === 0) {
            $selMonth = $currentMonth;
            $selYear = $currentYear;
        }

        return [$selMonth, $selYear];
    }

    // Profile Action
    public function profile(): void
    {
        $userId = (int) (Session::userId() ?: ($_SESSION['user_id'] ?? 0));
        $user = $this->userModel->findById($userId);

        $viewName = file_exists(__DIR__ . '/../Views/player/player-profile.php') ? 'player/player-profile' : 'player/profile';
        $this->render($viewName, [
            'user' => $user,
        ]);
    }

    // Cancel Event API (POST /api/cancel_event.php)
    public function cancelEvent(): void
    {
        Session::start();
        $sessionUserId = (int) (Session::userId() ?: ($_SESSION['user_id'] ?? 0));
        if ($sessionUserId <= 0) {
            $this->json(['status' => 'error', 'success' => false, 'message' => 'Authentication required.'], 401);
            return;
        }
        $userId = $sessionUserId;
        $gameId = (int) ($_POST['ID'] ?? ($_POST['game_id'] ?? 0));
        $hostId = (int) ($_POST['HOST_ID'] ?? ($_POST['host_id'] ?? 0));

        if ($gameId <= 0) {
            $this->json(['status' => 'error', 'success' => false, 'message' => 'Invalid game ID.'], 400);
            return;
        }

        $gameJoinModel = new GameJoinModel($this->db);
        $result = $gameJoinModel->cancelEvent($userId, $gameId, $hostId);
        if (($result['status'] ?? '') === 'success') {
            $result = array_merge($result, $gameJoinModel->getEventJoinStats($gameId, $userId));
        }
        $statusCode = ($result['status'] === 'success') ? 200 : 400;
        $this->json($result, $statusCode);
    }

    // Fetch Monthly Ledger API (Renders HTML or returns JSON based on format)
    public function fetchMonthlyLedger(): void
    {
        $userId = (int) (Session::userId() ?: ($_SESSION['user_id'] ?? 0));
        [$year, $month] = $this->resolveLedgerPeriod($_POST);
        $hostId = $this->resolveHostId($userId, isset($_POST['host_id']) ? (int) $_POST['host_id'] : null);

        $ledgerService = new \App\Services\Ledger\LedgerService();
        $role = strtolower((string) (Session::userType() ?: ($_SESSION['usertype'] ?? 'player')));
        $data = $ledgerService->getMonthlyLedger($hostId, $userId, $year, $month, $role, $userId);
        $data['is_player_side'] = true;

        if (ob_get_length()) {
            ob_clean();
        }

        if (isset($_POST['format']) && $_POST['format'] === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'data' => $data]);
            exit();
        }

        echo \App\Services\Ledger\LedgerPresenter::renderMonthlyHtml($data);
        exit();
    }

    // Pure JSON REST API Endpoint for Monthly Ledger
    public function fetchMonthlyLedgerData(): void
    {
        $userId = (int) (Session::userId() ?: ($_SESSION['user_id'] ?? 0));
        [$year, $month] = $this->resolveLedgerPeriod($_POST);
        $hostId = $this->resolveHostId($userId, isset($_POST['host_id']) ? (int) $_POST['host_id'] : null);

        $ledgerService = new \App\Services\Ledger\LedgerService();
        $role = strtolower((string) (Session::userType() ?: ($_SESSION['usertype'] ?? 'player')));
        $data = $ledgerService->getMonthlyLedger($hostId, $userId, $year, $month, $role, $userId);
        $data['is_player_side'] = true;

        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit();
    }

    // Submit Player Payment API Endpoint
    public function savePayment(): void
    {
        $userId = (int) (Session::userId() ?: ($_SESSION['user_id'] ?? 0));
        $role = Session::getCurrentUserType() ?? 'Player';

        $actionService = new \App\Services\Ledger\LedgerActionService();
        $ledgerService = new \App\Services\Ledger\LedgerService();

        try {
            $input = $_POST;
            $res = $actionService->recordPayment($input, $userId, $role);
            [$year, $month] = $this->resolveLedgerPeriod($_POST);
            $targetHost = (int) ($res['host_id'] ?? 0);

            $ledger = $ledgerService->getMonthlyLedger($targetHost, $userId, $year, $month, 'player', $userId);
            $res['html'] = \App\Services\Ledger\LedgerPresenter::renderMonthlyHtml($ledger);
            $this->json($res, 200);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    // Generic Payment Action Endpoint (Add Payment inline form & quick actions)
    public function paymentAction(): void
    {
        $userId = (int) (Session::userId() ?: ($_SESSION['user_id'] ?? 0));
        $role = Session::getCurrentUserType() ?? 'Player';

        $action = trim((string) ($_POST['action'] ?? 'pay'));
        $actionService = new \App\Services\Ledger\LedgerActionService();
        $ledgerService = new \App\Services\Ledger\LedgerService();

        try {
            if ($action === 'reinitiate') {
                $paymentId = (int) ($_POST['payment_id'] ?? 0);
                $details = trim((string) ($_POST['details'] ?? ''));
                $res = $actionService->reinitiatePayment($paymentId, $details, $userId, $role);
            } else {
                $input = $_POST;
                $res = $actionService->recordPayment($input, $userId, $role);
            }

            [$year, $month] = $this->resolveLedgerPeriod($_POST);
            $targetHost = (int) ($res['host_id'] ?? (isset($_POST['host_id']) ? (int) $_POST['host_id'] : 0));

            $ledger = $ledgerService->getMonthlyLedger($targetHost, $userId, $year, $month, 'player', $userId);
            $res['html'] = \App\Services\Ledger\LedgerPresenter::renderMonthlyHtml($ledger);
            $this->json($res, 200);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    // Private Hub Helpers
    private function getUserInfo(int $userId): object
    {
        $userInfo = (object) [
            'premium' => 'N',
            'prefs' => [],
            'game' => 'Badminton',
            'country' => '',
            'province' => '',
            'city' => '',
        ];

        try {
            $user_stmt = $this->db->prepare(
                "SELECT PREMIUM, GAMES, COUNTRY, PROVINCE, CITY, PROFILE_IMAGE, GENDER, VERIFIED_LEVEL, LEVEL, NAME 
                 FROM ca_users WHERE ID = ? LIMIT 1"
            );
            $user_stmt->execute([$userId]);
            $prefs = $user_stmt->fetch() ?: [];

            $userInfo->prefs = $prefs;
            $userInfo->premium = $prefs['PREMIUM'] ?? 'N';
            $userInfo->game = $prefs['GAMES'] ?? 'Badminton';
            $userInfo->country = $prefs['COUNTRY'] ?? '';
            $userInfo->province = $prefs['PROVINCE'] ?? '';
            $userInfo->city = $prefs['CITY'] ?? '';

            if (!empty($prefs['PROFILE_IMAGE'])) {
                Session::set('profileImage', $prefs['PROFILE_IMAGE']);
            }
        } catch (PDOException $e) {
            error_log("PlayerController hub user_info error: " . $e->getMessage());
        }

        return $userInfo;
    }

    private function getClubs(int $userId, object $userInfo): array
    {
        $clubs = [];
        try {
            $baseClubSql = "SELECT c.id, c.club_name, c.club_info, c.cost_info, c.schedule, c.logo, c.game_type, c.country, c.province, c.city, c.created_at, c.host_id, u.NAME as host_name, u.PROFILE_IMAGE as host_img
                            FROM ca_clubs c
                            JOIN ca_users u ON c.host_id = u.ID
                            WHERE c.status = 'Active'";

            $clubs_sql = $baseClubSql . " AND c.game_type = :game_type";
            $params = ['game_type' => $userInfo->game];

            if (!empty($userInfo->country)) {
                $clubs_sql .= " AND c.country = :country";
                $params['country'] = $userInfo->country;
            }
            if (!empty($userInfo->province)) {
                $clubs_sql .= " AND c.province = :province";
                $params['province'] = $userInfo->province;
            }
            if (!empty($userInfo->city)) {
                $clubs_sql .= " AND c.city = :city";
                $params['city'] = $userInfo->city;
            }

            $clubs_sql .= " ORDER BY c.created_at DESC LIMIT 8";
            $clubs_stmt = $this->db->prepare($clubs_sql);
            $clubs_stmt->execute($params);
            $clubs = $clubs_stmt->fetchAll();

            $fallbacks = [
                ['sql' => $baseClubSql . " AND c.game_type = :game_type ORDER BY c.created_at DESC LIMIT 8", 'params' => ['game_type' => $userInfo->game]],
                ['sql' => $baseClubSql . " ORDER BY c.created_at DESC LIMIT 8", 'params' => []],
            ];

            foreach ($fallbacks as $fb) {
                if (!empty($clubs)) {
                    break;
                }
                $stmt = $this->db->prepare($fb['sql']);
                $stmt->execute($fb['params']);
                $clubs = $stmt->fetchAll() ?: [];
            }

            if (!empty($clubs)) {
                $clubIds = array_column($clubs, 'id');
                $placeholders = implode(',', array_fill(0, count($clubIds), '?'));
                $queryParams = array_merge([$userId], $clubIds);

                $statusStmt = $this->db->prepare("SELECT club_id, status FROM ca_player_club_status WHERE player_id = ? AND club_id IN ({$placeholders})");
                $statusStmt->execute($queryParams);
                $statusMap = $statusStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

                foreach ($clubs as &$club_row) {
                    $club_row['membership_status'] = $statusMap[$club_row['id']] ?? 'not_joined';
                }
                unset($club_row);
            }
        } catch (PDOException $e) {
            error_log("PlayerController hub clubs error: " . $e->getMessage());
            $clubs = [];
        }

        return $clubs;
    }

    private function getTournaments(int $userId, string $baseDir): array
    {
        $upcomingTournaments = [];
        try {
            $pdo = $this->db;
            $tournamentQuery = function (bool $filterFutureDate) use ($pdo, $userId): array {
                $dateCondition = $filterFutureDate ? "AND e.EVENT_DATE >= CURDATE()" : "";
                $sql = "SELECT e.*, b.IMGAE, 
                        (SELECT COUNT(*) FROM to_teams WHERE TOURNAMENT_ID = e.ID) as joined_count,
                        (SELECT COUNT(*) FROM to_users tu 
                          JOIN to_teams tt ON tu.TEAM_ID = tt.ID 
                          WHERE tt.TOURNAMENT_ID = e.ID AND tu.CA_ID = :uid1) as is_joined,
                        (SELECT tp.STATUS FROM to_payments tp 
                          JOIN to_users tu ON tp.USER_ID = tu.ID
                          JOIN to_teams tt ON tu.TEAM_ID = tt.ID
                          WHERE tt.TOURNAMENT_ID = e.ID AND tu.CA_ID = :uid2 LIMIT 1) as pay_status,
                        (SELECT tp.APPROVED_BY FROM to_payments tp 
                          JOIN to_users tu ON tp.USER_ID = tu.ID
                          JOIN to_teams tt ON tu.TEAM_ID = tt.ID
                          WHERE tt.TOURNAMENT_ID = e.ID AND tu.CA_ID = :uid3 LIMIT 1) as pay_approved
                        FROM to_tournaments e 
                        LEFT JOIN to_tournamet_banners b ON e.ID = b.EVENTS_ID 
                        WHERE e.STATUS = 'Active' {$dateCondition} 
                        ORDER BY e.ID DESC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute(['uid1' => $userId, 'uid2' => $userId, 'uid3' => $userId]);
                return $stmt->fetchAll() ?: [];
            };

            $rawTournaments = $tournamentQuery(true) ?: $tournamentQuery(false);

            require_once __DIR__ . '/../Helpers/player_hub_helper.php';
            $tzEst = new \DateTimeZone('America/New_York');
            $nowEst = new \DateTimeImmutable('now', $tzEst);
            $pathPrefix = '/';

            foreach ($rawTournaments as $row) {
                $upcomingTournaments[] = prepareTournamentData($row, $pathPrefix, $baseDir, $tzEst, $nowEst);
            }
        } catch (PDOException $e) {
            error_log("PlayerController hub tournaments error: " . $e->getMessage());
            $upcomingTournaments = [];
        }

        return $upcomingTournaments;
    }

    private function getTotalSessions(int $userId): int
    {
        try {
            $stmtSession = $this->db->prepare("SELECT COUNT(*) AS total_session FROM ca_gamejoin WHERE USER_ID = ?");
            $stmtSession->execute([$userId]);
            return (int) ($stmtSession->fetchColumn() ?: 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    private function getProductsAndOrders(string $userEmail, string $baseDir): array
    {
        $products = [];
        $orderMap = [];

        try {
            $prodStmt = $this->db->prepare("SELECT ID, PRODUCT_NAME, PRICE, IMAGE, QUANTITY FROM ca_products WHERE QUANTITY IS NULL OR QUANTITY > 0 ORDER BY ID DESC LIMIT 10");
            $prodStmt->execute();
            $rawProducts = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rawProducts as $product) {
                $productName = !empty($product['PRODUCT_NAME']) ? htmlspecialchars($product['PRODUCT_NAME'], ENT_QUOTES, 'UTF-8') : 'N/A';
                $price = !empty($product['PRICE']) ? htmlspecialchars(number_format((float) $product['PRICE'], 2), ENT_QUOTES, 'UTF-8') : '0.00';
                $image = $product['IMAGE'] ?? '';

                $product['display_name'] = $productName;
                $product['formatted_price'] = $price;
                $product['image_path'] = $this->resolveProductImage((string) $image, $baseDir);

                $products[] = $product;
            }

            if (!empty($products) && !empty($userEmail)) {
                $orderSql = "
                    SELECT oi.PRODUCT_ID, oi.QUANTITY, oi.PRICE, o.ORDER_DATE, oi.STATUS
                    FROM ca_orders o
                    INNER JOIN ca_orders_item oi ON o.ORDER_ID = oi.ORDER_ID
                    INNER JOIN (
                        SELECT oi2.PRODUCT_ID, MAX(o2.ORDER_ID) as max_order_id
                        FROM ca_orders o2
                        INNER JOIN ca_orders_item oi2 ON o2.ORDER_ID = oi2.ORDER_ID
                        WHERE o2.EMAIL = :email1
                        GROUP BY oi2.PRODUCT_ID
                    ) latest ON oi.PRODUCT_ID = latest.PRODUCT_ID AND o.ORDER_ID = latest.max_order_id
                    WHERE o.EMAIL = :email2";

                $orderStmt = $this->db->prepare($orderSql);
                $orderStmt->execute(['email1' => $userEmail, 'email2' => $userEmail]);
                $ordersList = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($ordersList as $ord) {
                    $orderMap[$ord['PRODUCT_ID']] = $ord;
                }
            }
        } catch (PDOException $e) {
            error_log("PlayerController hub products/orders error: " . $e->getMessage());
        }

        return [$products, $orderMap];
    }

    private function getReviews(): array
    {
        try {
            $revStmt = $this->db->prepare(
                "SELECT r.ID, r.USER_ID, r.MESSAGE, r.PLAYER_ROLE, r.RATING, r.DATE_CREATED, u.NAME, u.PROFILE_IMAGE
                 FROM ca_reviews r
                 LEFT JOIN ca_users u ON r.USER_ID = u.ID
                 WHERE r.STATUS = 'Active'
                 ORDER BY r.DATE_CREATED DESC LIMIT 20"
            );
            $revStmt->execute();
            return $revStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("PlayerController hub reviews error: " . $e->getMessage());
            return [];
        }
    }

    private function resolveProductImage(string $image, string $baseDir): string
    {
        if (!empty($image) && (str_starts_with($image, 'http://') || str_starts_with($image, 'https://'))) {
            return htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
        }

        $cleanImg = basename(ltrim($image, '/\\'));
        $searchDirs = [self::ADMIN_ASSETS_DIR, self::ADMIN_DIR, self::PRODUCT_IMAGES_DIR];
        foreach ($searchDirs as $dir) {
            if (!empty($cleanImg) && file_exists($baseDir . $dir . $cleanImg)) {
                return '/' . $dir . htmlspecialchars($cleanImg, ENT_QUOTES, 'UTF-8');
            }
        }

        return self::DEFAULT_PRODUCT_IMG;
    }

    private function resolveProfileImage(string $rawImg, string $baseDir): string
    {
        $clean = basename(ltrim($rawImg, '/\\'));
        if (empty($clean)) {
            return self::DEFAULT_PROFILE_IMG;
        }
        foreach (['profile_img/', ''] as $subDir) {
            if (file_exists($baseDir . $subDir . $clean)) {
                return '/' . $subDir . htmlspecialchars($clean, ENT_QUOTES, 'UTF-8');
            }
        }
        return self::DEFAULT_PROFILE_IMG;
    }

    /**
     * Handle Subscription Game Join/Cancel Default Action
     * (POST /api/join_event_default.php, /api/cancel_event_default.php)
     */
    public function handleDefaultEventAction(?string $action = null): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (!$action) {
            if (str_contains($uri, 'cancel')) {
                $action = 'cancel';
            } else {
                $action = 'join';
            }
        }

        $userId = (int) ($_POST['USER_ID'] ?? ($_POST['user_id'] ?? ($_SESSION['user_id'] ?? 0)));
        $gameId = (int) ($_POST['ID'] ?? ($_POST['game_id'] ?? 0));
        $hostId = (int) ($_POST['HOST_ID'] ?? ($_POST['host_id'] ?? ($_SESSION['mapped_host_id'] ?? 0)));
        $cost = (float) ($_POST['EVENT_COST'] ?? ($_POST['cost'] ?? 0));
        $currency = trim((string) ($_POST['EVENT_CURRENCY'] ?? ($_POST['currency'] ?? 'CAD')));

        if ($userId <= 0 || $gameId <= 0) {
            http_response_code(400);
            echo "Invalid Request";
            exit;
        }

        $conn = Database::getMysqli();

        $baseDir = dirname(__DIR__, 2);
        if (file_exists($baseDir . '/app/Helpers/player_ledger_helper.php')) {
            require_once $baseDir . '/app/Helpers/player_ledger_helper.php';
        }

        if ($action === 'join') {
            $createdAt = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO ca_gamejoin_default (USER_ID, GAME_ID, HOST_ID, PRICE, CURRENCY, TYPE, STATUS, CREATED_AT) VALUES (?, ?, ?, ?, ?, 'Public', 'Y', ?)");
            $costStr = (string) $cost;
            $stmt->bind_param("iiisss", $userId, $gameId, $hostId, $costStr, $currency, $createdAt);
            $stmt->execute();

            if (function_exists('logPlayerActivity')) {
                $resolvedHost = $hostId > 0 ? $hostId : (function_exists('auditResolveGameHost') ? auditResolveGameHost($conn, $gameId) : 0);
                logPlayerActivity($conn, $userId, 'JOIN_GAME', 'Joined subscription game ID ' . $gameId, $resolvedHost, $gameId);
            }
        } else {
            $stmt = $conn->prepare("DELETE FROM ca_gamejoin_default WHERE USER_ID = ? AND GAME_ID = ?");
            $stmt->bind_param("ii", $userId, $gameId);
            $stmt->execute();

            if (function_exists('logPlayerActivity')) {
                $resolvedHost = $hostId > 0 ? $hostId : (function_exists('auditResolveGameHost') ? auditResolveGameHost($conn, $gameId) : 0);
                logPlayerActivity($conn, $userId, 'CANCEL_GAME', 'Cancelled subscription game ID ' . $gameId, $resolvedHost, $gameId);
            }
        }

        echo $this->renderScheduledGamesHtml($conn, $userId, $hostId);
        exit;
    }

    private function renderScheduledGamesHtml(\mysqli $conn, int $userId, int $hostId): string
    {
        $eventsByDay = [
            'SUNDAY'    => [],
            'MONDAY'    => [],
            'TUESDAY'   => [],
            'WEDNESDAY' => [],
            'THURSDAY'  => [],
            'FRIDAY'    => [],
            'SATURDAY'  => []
        ];

        $dayColors = [
            'SUNDAY'    => 'day-SUNDAY',
            'MONDAY'    => 'day-MONDAY',
            'TUESDAY'   => 'day-TUESDAY',
            'WEDNESDAY' => 'day-WEDNESDAY',
            'THURSDAY'  => 'day-THURSDAY',
            'FRIDAY'    => 'day-FRIDAY',
            'SATURDAY'  => 'day-SATURDAY'
        ];

        $hostFilter = $hostId > 0 ? "HOST_ID = '" . (int) $hostId . "' AND " : "";

        $vlevel = $_SESSION['vlevel'] ?? '';
        $gender = $_SESSION['gender'] ?? '';

        if (($vlevel === '' || $gender === '') && $userId > 0) {
            $userRes = mysqli_query($conn, "SELECT VERIFIED_LEVEL, LEVEL, GENDER FROM ca_users WHERE ID = '" . (int) $userId . "' LIMIT 1");
            if ($userRes && $uRow = mysqli_fetch_assoc($userRes)) {
                if ($vlevel === '') {
                    $vlevel = !empty($uRow['VERIFIED_LEVEL']) ? $uRow['VERIFIED_LEVEL'] : ($uRow['LEVEL'] ?? '');
                    $_SESSION['vlevel'] = $vlevel;
                }
                if ($gender === '') {
                    $gender = $uRow['GENDER'] ?? '';
                    $_SESSION['gender'] = $gender;
                }
            }
        }

        $vlevelEsc = mysqli_real_escape_string($conn, (string) $vlevel);
        $genderEsc = mysqli_real_escape_string($conn, (string) $gender);

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

        ob_start();
        $this->render('player/scheduled-games-cards', [
            'eventsByDay' => $eventsByDay,
            'dayColors'   => $dayColors,
            'conn'        => $conn,
            'userId'      => $userId
        ]);
        return (string) ob_get_clean();
    }

    /**
     * API: Save or Update Review
     */
    public function saveReview(): void
    {
        Session::start();
        \App\Middleware\AuthGuard::check();

        $userId = \App\Middleware\AuthGuard::id() ?: (Session::userId() ?: ((int) ($_SESSION['user_id'] ?? ($_POST['user_id'] ?? 0))));
        if ($userId <= 0) {
            $this->json(['status' => 'error', 'message' => 'Please login to submit a review.'], 401);
            return;
        }

        $rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 5;
        $message = trim((string) ($_POST['message'] ?? ''));
        $reviewId = !empty($_POST['review_id']) ? (int) $_POST['review_id'] : null;

        $userType = (string) (Session::get('usertype', $_SESSION['usertype'] ?? 'Player'));

        $reviewModel = new \App\Models\ReviewModel($this->db);
        $result = $reviewModel->saveReview($userId, $userType, $rating, $message, $reviewId);

        $status = ($result['status'] === 'success') ? 200 : 400;
        $this->json($result, $status);
    }

    
    //Handle Public/Direct Event Join (POST /api/join_event.php)
    
    public function joinEvent(): void
    {
        Session::start();
        $sessionUserId = (int) (Session::userId() ?: ($_SESSION['user_id'] ?? 0));
        if ($sessionUserId <= 0) {
            $this->json(['status' => 'error', 'success' => false, 'message' => 'Authentication required.'], 401);
            return;
        }
        $userId = $sessionUserId;
        $gameId = (int) ($_POST['ID'] ?? ($_POST['game_id'] ?? 0));
        $hostId = (int) ($_POST['HOST_ID'] ?? ($_POST['host_id'] ?? 0));
        $cost   = $_POST['EVENT_COST'] ?? ($_POST['cost'] ?? 0);
        $rawCurr = trim((string) ($_POST['EVENT_CURRENCY'] ?? ($_POST['currency'] ?? '$')));
        $currency = ($rawCurr === 'CAD' || empty($rawCurr)) ? '$' : $rawCurr;

        if ($gameId <= 0) {
            $this->json(['status' => 'error', 'success' => false, 'message' => 'Invalid game ID.'], 400);
            return;
        }

        $gameJoinModel = new GameJoinModel($this->db);
        $result = $gameJoinModel->joinEvent($userId, $gameId, $hostId, $cost, $currency, 'Public');
        if (($result['status'] ?? '') === 'success') {
            $result = array_merge($result, $gameJoinModel->getEventJoinStats($gameId, $userId));
        }
        $statusCode = ($result['status'] === 'success') ? 200 : 400;
        $this->json($result, $statusCode);
    }

    //                                                                                                                            Handle Accepting Event Invitation (POST /api/update_inviteJoin.php)
    
    public function acceptInvite(): void
    {
        Session::start();
        $sessionUserId = (int) (Session::userId() ?: ($_SESSION['user_id'] ?? 0));
        if ($sessionUserId <= 0) {
            $this->json(['status' => 'error', 'success' => false, 'message' => 'Authentication required.'], 401);
            return;
        }
        $userId = $sessionUserId;
        $gameId = (int) ($_POST['ID'] ?? ($_POST['game_id'] ?? 0));
        $hostId = (int) ($_POST['HOST_ID'] ?? ($_POST['host_id'] ?? 0));

        if ($gameId <= 0) {
            $this->json(['status' => 'error', 'success' => false, 'message' => 'Invalid game ID.'], 400);
            return;
        }

        $gameJoinModel = new GameJoinModel($this->db);
        $result = $gameJoinModel->acceptInvite($userId, $gameId, $hostId);
        if (($result['status'] ?? '') === 'success') {
            $result = array_merge($result, $gameJoinModel->getEventJoinStats($gameId, $userId));
        }
        $statusCode = ($result['status'] === 'success') ? 200 : 400;
        $this->json($result, $statusCode);
    }

    /**
     * Submit tournament payment confirmation (POST /api/player_payment.php, /api/player-payment)
     */
    public function submitTournamentPayment(): void
    {
        Session::start();
        $caId = (int) (Session::userId() ?: ($_SESSION['user_id'] ?? 0));
        if ($caId <= 0) {
            $this->json(['success' => false, 'message' => 'Session expired. Please login.'], 401);
            return;
        }

        $csrf = (string) ($this->request->get('csrf_token') ?? ($_POST['csrf_token'] ?? ''));
        if (!empty($_SESSION['csrf_token']) && $csrf !== $_SESSION['csrf_token']) {
            $this->json(['success' => false, 'message' => 'Security token mismatch.'], 403);
            return;
        }

        $tournamentId = (int) ($this->request->get('tournament_id') ?? ($_POST['tournament_id'] ?? 0));
        if ($tournamentId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid tournament ID.'], 400);
            return;
        }

        try {
            // Find participant ID in to_users
            $stmtUser = $this->db->prepare("
                SELECT tu.ID 
                FROM to_users tu 
                JOIN to_teams tt ON tu.TEAM_ID = tt.ID 
                WHERE tu.CA_ID = ? AND tt.TOURNAMENT_ID = ? 
                LIMIT 1
            ");
            $stmtUser->execute([$caId, $tournamentId]);
            $toUserId = $stmtUser->fetchColumn();

            if (!$toUserId) {
                $this->json(['success' => false, 'message' => 'Record not found. Ensure you have joined the tournament first.'], 404);
                return;
            }

            // Price verification
            $stmtPrice = $this->db->prepare("SELECT AMOUNT FROM to_tournaments WHERE ID = ? LIMIT 1");
            $stmtPrice->execute([$tournamentId]);
            $dbPrice = $stmtPrice->fetchColumn();

            $rawAmount = $this->request->get('payment_amount') ?? ($_POST['payment_amount'] ?? 0);
            $paidAmount = (float) filter_var($rawAmount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            if ($paidAmount < (float) $dbPrice) {
                $this->json(['success' => false, 'message' => 'Amount too low. Required: CAD ' . $dbPrice], 400);
                return;
            }

            $paymentDate = (string) ($this->request->get('payment_date') ?? ($_POST['payment_date'] ?? date('Y-m-d')));
            $paymentTime = (string) ($this->request->get('payment_time') ?? ($_POST['payment_time'] ?? date('H:i:s')));
            $paymentType = htmlspecialchars((string) ($this->request->get('payment_type') ?? ($_POST['payment_type'] ?? 'Interac')), ENT_QUOTES, 'UTF-8');
            $paymentDetails = htmlspecialchars((string) ($this->request->get('payment_details') ?? ($_POST['payment_details'] ?? '')), ENT_QUOTES, 'UTF-8');
            $paymentMessage = htmlspecialchars((string) ($this->request->get('payment_message') ?? ($_POST['payment_message'] ?? '')), ENT_QUOTES, 'UTF-8');

            $sql = "UPDATE to_payments SET 
                        AMOUNT = ?, 
                        PAYMENT_DATE = ?, 
                        PAYMENT_TIME = ?, 
                        PAYMENT_TYPE = ?, 
                        DETAILS = ?, 
                        MESSAGE = ?, 
                        STATUS = 'Y' 
                    WHERE USER_ID = ? AND GAME_ID = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $paidAmount,
                $paymentDate,
                $paymentTime,
                $paymentType,
                $paymentDetails,
                $paymentMessage,
                $toUserId,
                $tournamentId
            ]);

            if ($stmt->rowCount() > 0) {
                $this->json([
                    'success' => true,
                    'paid_amount' => number_format($paidAmount, 2)
                ], 200);
            } else {
                $this->json([
                    'success' => false,
                    'message' => 'No payment record found to update. Please contact support.'
                ], 400);
            }
        } catch (\Throwable $e) {
            error_log('[PlayerController::submitTournamentPayment Error] ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Filter completed games for player (POST /api/player_filter_complete.php, /api/player-filter-complete)
     */
    public function filterCompletedGames(): void
    {
        Session::start();
        $currentUserId = (int) (Session::userId() ?: ($_SESSION['user_id'] ?? ($_POST['USER_ID'] ?? 0)));

        $type = (string) ($this->request->get('type') ?? ($_POST['type'] ?? 'filter'));
        $conditions = ["e.STATUS = 'Completed'", "EXISTS (SELECT 1 FROM ca_gamejoin pgj WHERE pgj.GAME_ID = e.ID AND pgj.USER_ID = :completed_player_id AND pgj.STATUS = 'Y')"];
        $params = ['completed_player_id' => $currentUserId];

        $host = trim((string) ($this->request->get('host') ?? ($_POST['host'] ?? '')));
        if ($host !== '') {
            $conditions[] = "e.HOST_ID = :host";
            $params['host'] = $host;
        }

        if ($type === 'filter') {
            $year = trim((string) ($this->request->get('year') ?? ($_POST['year'] ?? '')));
            $month = trim((string) ($this->request->get('month') ?? ($_POST['month'] ?? '')));

            if ($year !== '') {
                $conditions[] = "YEAR(e.EVENT_DATE) = :year";
                $params['year'] = $year;
            }
            if ($month !== '') {
                $conditions[] = "MONTH(e.EVENT_DATE) = :month";
                $params['month'] = $month;
            }
        } else {
            // Reset / default: current year & month
            $currentYear = (int) date('Y');
            $currentMonth = (int) date('n');
            $conditions[] = "YEAR(EVENT_DATE) = :current_year";
            $conditions[] = "MONTH(EVENT_DATE) = :current_month";
            $params['current_year'] = $currentYear;
            $params['current_month'] = $currentMonth;
        }

        $sql = "SELECT e.* FROM ca_events e WHERE " . implode(' AND ', $conditions) . " ORDER BY e.EVENT_DATE DESC, e.EVENT_TIME DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($events)) {
            header('Content-Type: text/html; charset=utf-8');
            echo "<div class='empty-schedule-card text-center w-100 py-4 px-3 my-2' style='max-width: 440px; margin: 15px auto !important; background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%); border: 1.5px dashed #cbd5e1; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);'>
                <div class='empty-icon-wrap mx-auto mb-2 d-flex align-items-center justify-content-center' style='width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%); color: #0284c7; box-shadow: 0 2px 8px rgba(2,132,199,0.15);'>
                    <svg xmlns='http://www.w3.org/2000/svg' width='22' height='22' fill='currentColor' viewBox='0 0 16 16'>
                        <path d='M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z'/>
                        <path d='M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z'/>
                    </svg>
                </div>
                <h6 class='fw-bold mb-1' style='color: #1e293b; font-size: 0.95rem; letter-spacing: -0.2px;'>No Completed Events</h6>
                <p class='text-muted mb-2' style='font-size: 0.82rem; line-height: 1.45; max-width: 320px; margin: 0 auto;'>No completed games found for this selection.</p>
            </div>";
            return;
        }

        $joinStmt = $this->db->prepare("SELECT CONFIRMED FROM ca_gamejoin WHERE USER_ID = ? AND GAME_ID = ? LIMIT 1");
        $totalJoinStmt = $this->db->prepare("SELECT COUNT(*) FROM ca_gamejoin WHERE GAME_ID = ?");
        $totalConfStmt = $this->db->prepare("SELECT COUNT(*) FROM ca_gamejoin WHERE GAME_ID = ? AND CONFIRMED = 'Y'");

        $html = '';
        foreach ($events as $event) {
            $eventId = (int) $event['ID'];
            $hostName = htmlspecialchars($event['HOST_NAME'] ?? '', ENT_QUOTES, 'UTF-8');
            $eventDate = date('D, d M Y, h:i A', strtotime(($event['EVENT_DATE'] ?? '') . ' ' . ($event['EVENT_TIME'] ?? '')));
            $eventVenue = htmlspecialchars($event['EVENT_VENUE'] ?? '', ENT_QUOTES, 'UTF-8');
            $eventCost = htmlspecialchars((string) ($event['EVENT_COST'] ?? '0'), ENT_QUOTES, 'UTF-8');
            $eventCurrency = htmlspecialchars($event['EVENT_CURRENCY'] ?? '$', ENT_QUOTES, 'UTF-8');
            $eventCategory = htmlspecialchars($event['EVENT_CATEGORY'] ?? '', ENT_QUOTES, 'UTF-8');
            $genderCategory = htmlspecialchars($event['GENDER_CATEGORY'] ?? '', ENT_QUOTES, 'UTF-8');
            $genderSkillLevel = htmlspecialchars($event['GENDER_SKILL_LEVEL'] ?? '', ENT_QUOTES, 'UTF-8');
            $eventDescription = htmlspecialchars($event['EVENT_DESCRIPTION'] ?? '', ENT_QUOTES, 'UTF-8');
            $eventMessage = (string) ($event['EVENT_MESSAGE'] ?? '');
            $eventType = htmlspecialchars($event['EVENT_TYPE'] ?? 'Public', ENT_QUOTES, 'UTF-8');
            $eventCourt = (int) ($event['EVENT_COURT'] ?? 0);
            $eventCancelDate = !empty($event['CANCEL_DATE']) ? date('D, d M Y', strtotime((string) $event['CANCEL_DATE'])) : '';
            $eventCancelTime = !empty($event['CANCEL_TIME']) ? date('h:i A', strtotime((string) $event['CANCEL_TIME'])) : '';
            $eventToTime = !empty($event['TO_TIME']) ? date('h:i A', strtotime((string) $event['TO_TIME'])) : '';

            $jsonStringy = htmlspecialchars(json_encode($event) ?: '{}', ENT_QUOTES, 'UTF-8');
            $words = explode(" ", $eventMessage);
            $wrappedMessage = htmlspecialchars(implode(" ", array_slice($words, 0, 7)), ENT_QUOTES, 'UTF-8');
            if (count($words) > 7) {
                $wrappedMessage .= " ...";
            }

            $bgClass = '';
            if ($currentUserId > 0) {
                $joinStmt->execute([$currentUserId, $eventId]);
                $joinRow = $joinStmt->fetch(PDO::FETCH_ASSOC);
                if ($joinRow) {
                    $bgClass = ($joinRow['CONFIRMED'] === 'Y') ? '#1987543b' : '#ffc10738';
                }
            }

            $totalJoinStmt->execute([$eventId]);
            $countTotalJoin = (int) $totalJoinStmt->fetchColumn();

            $totalConfStmt->execute([$eventId]);
            $countTotalConfirmed = (int) $totalConfStmt->fetchColumn();

            $typeBadgeClass = ($eventType === 'Public') ? 'bg-info' : 'bg-success';
            $courtLabel = ($eventCourt === 0) ? 'NA' : (string) $eventCourt;

            $html .= "
            <div class='discoverGames_card player_cards' style='background-color:{$bgClass}'>
                <div class='d-flex flex-wrap align-items-center justify-content-between gap-2' style='margin-bottom: 8px; background-color: #0d6efda1; padding: 8px 10px; border-radius: 8px 8px 0px 0px;'>
                    <p class='text-white desc fw-bold m-0' style='font-size: 85%;'>{$eventCategory} - {$genderCategory}</p>
                    <div class='accessories_wrap'>
                        <span style='padding: 2px 6px;' class='btn text-white {$typeBadgeClass}'>
                            {$eventType}
                        </span>
                        <input type='hidden' id='data_{$eventId}' value='{$jsonStringy}'/>
                        <input type='hidden' id='user_{$eventId}' value='{$currentUserId}'/>
                    </div>
                </div>
                <div class='d-flex align-items-center justify-content-between gap-1 mb-2'>
                    <div>
                        <p style='color:black'><strong>Host: {$hostName}</strong></p>
                        <div class='d-flex align-items-start gap-1 mb-1 p-1' style='border: 1px solid red; border-radius: 6px;'>
                            <i class='fa-solid fa-clock' style='font-size: 80%; color: red; margin-top: 3px;'></i>
                            <h4 class='date_time m-0' style='color: red;'><span>{$eventDate}</span><span> to </span><span>{$eventToTime}</span></h4>
                        </div>
                        <p class='location mb-1'>
                            <i class='fa-solid fa-location-dot'></i>
                            {$eventVenue}
                        </p>
                        <p style='font-size:60%; margin-bottom: 5px;'>Freeze Date &amp; Time: {$eventCancelDate} {$eventCancelTime}</p>
                    </div>
                    <div class='access_datebox'>
                        <div class='d-flex align-items-center justify-content-between gap-1 mb-1'>
                            <span>Total Joined:</span>
                            <span class='slots-count badge bg-secondary m-0'
                                  tabindex='0'
                                  data-bs-toggle='popover'
                                  data-bs-trigger='click'
                                  data-bs-placement='top'
                                  data-bs-content='Total Joined: {$countTotalJoin}'>
                                {$countTotalJoin}
                            </span>
                        </div>
                        <div class='d-flex align-items-center justify-content-between gap-1 mb-1'>
                            <span>Confirmed:</span>
                            <span class='slots-count badge bg-secondary m-0'
                                  tabindex='0'
                                  data-bs-toggle='popover'
                                  data-bs-trigger='click'
                                  data-bs-placement='top'
                                  data-bs-content='Total Confirmed: {$countTotalConfirmed}'>
                                {$countTotalConfirmed}
                            </span>
                        </div>
                        <div class='d-flex align-items-center justify-content-between gap-1 mb-1'>
                            <span>Game Info:</span>
                            <span class='badge bg-info m-0' style='cursor: pointer;'
                                  tabindex='0'
                                  data-bs-toggle='popover'
                                  data-bs-trigger='click'
                                  data-bs-placement='top'
                                  data-bs-html='true'
                                  data-bs-content='{$eventDescription}'>
                                <i class='fa-solid fa-info'></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class='d-flex align-items-center' style='gap: 15px; margin-bottom: 10px;'>
                    <div class='d-flex align-items-center'>
                        <p class='gamesms_text'>{$wrappedMessage}</p>
                    </div>
                </div>
                <div class='d-flex align-items-center justify-content-start gap-1'>
                    <div class='play_status'>
                        <img src='../assets/images/Icons/SP5.png' class='img-fluid' alt='Game Icon' />
                        <span>{$genderSkillLevel}</span>
                    </div>
                    <div class='play_status d-flex flex-wrap align-items-start gap-1'>
                        <span>Court: {$courtLabel}</span>
                        <span>{$eventCurrency} {$eventCost}</span>
                    </div>
                </div>
            </div>";
        }

        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }
}



