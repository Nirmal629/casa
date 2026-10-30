<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Controller;
use PDO;
use PDOException;
use Throwable;

class HomeController extends Controller
{
    private const int PRODUCT_LIMIT = 12;
    private const int REVIEW_LIMIT = 20;

    // Asset directory paths and fallback images
    private const string TOURNAMENT_BANNER_DIR = 'admin/assets/images/tournaments_banner/';
    private const string DEFAULT_TOURNAMENT_IMG = 'assets/images/default-tournament.jpg';
    private const string PRODUCT_IMAGE_DIR      = 'admin/';
    private const string DEFAULT_PRODUCT_IMG    = 'assets/images/product/badminton1.jpg';

    private readonly PDO $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getPdo();
    }

    //Landing page.

    public function index(): void
    {
        $this->redirectAuthenticatedUser();

        $banners = $this->getHeroBanners();
        $tournaments = $this->getUpcomingTournaments();
        $products = $this->getStoreProducts();
        $stats = $this->getPlaygroundStats();
        $reviews = $this->getCommunityReviews();
        $posters = $this->getPosters();

        require_once __DIR__ . '/../home.php';
    }

    //Redirect authenticated users to their respective dashboard.

    private function redirectAuthenticatedUser(): void
    {
        \App\Helpers\Session::start();

        if (empty($_SESSION['user_id'])) {
            return;
        }

        $userType = strtolower(trim((string) ($_SESSION['usertype'] ?? '')));

        switch ($userType) {
            case 'host':
            case 'trainer':
                header('Location: /host/host-dashboard.php');
                exit;

            case 'player':
                header('Location: /player/player-hub.php');
                exit;

            default:
                // Invalid/corrupted authentication session.
                $_SESSION = [];

                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();

                    setcookie(
                        session_name(),
                        '',
                        time() - 42000,
                        $params['path'],
                        $params['domain'],
                        $params['secure'],
                        $params['httponly']
                    );
                }

                session_destroy();
        }
    }
    private function getHeroBanners(): array
    {
        $sql = "
            SELECT *
            FROM ca_herobanners
            WHERE status = 1
            ORDER BY id DESC
        ";
        return $this->fetchAll($sql);
    }
    private function getUpcomingTournaments(): array
    {
        $sql = "
            SELECT
                e.ID,
                e.HOST_NAME,
                e.CUP_NAME,
                e.CUP_NAME AS TOURNAMENT_NAME,
                e.EVENT_DESCRIPTION,
                e.EVENT_DESCRIPTION AS TAGLINE,
                e.GENDER_CATEGORY,
                e.EVENT_TYPE,
                e.EVENT_CATEGORY,
                e.EVENT_DATE,
                e.EVENT_TIME,
                e.EVENT_TIME AS START_TIME,
                e.EVENT_VENUE,
                e.EVENT_COST,
                e.SHUTTLE_TYPE,
                e.CANCEL_DATE,
                e.CANCEL_DATE AS REGISTRATION_CLOSE_DATE,
                e.CANCEL_TIME,
                e.CANCEL_TIME AS REGISTRATION_CLOSE_TIME,
                b.IMGAE AS IMAGE,
                (
                    SELECT COUNT(*)
                    FROM to_teams t
                    WHERE t.TOURNAMENT_ID = e.ID
                ) AS joined_count
            FROM to_tournaments e
            LEFT JOIN to_tournamet_banners b ON b.EVENTS_ID = e.ID
            WHERE e.STATUS = 'Active'
            ORDER BY e.ID DESC
        ";

        $rows = $this->fetchAll($sql);

        $tz = new \DateTimeZone('America/New_York');
        $now = new \DateTime('now', $tz);
        $projectRoot = dirname(__DIR__, 2);

        $tournaments = [];
        foreach ($rows as $row) {
            $name = trim((string)($row['CUP_NAME'] ?? '')) ?: trim((string)($row['HOST_NAME'] ?? ''));
            $desc = trim(strip_tags((string)($row['EVENT_DESCRIPTION'] ?? '')));
            $words = preg_split('/\s+/', $desc, -1, PREG_SPLIT_NO_EMPTY);
            $tagline = implode(' ', array_slice($words ?: [], 0, 5)) . (count($words ?: []) > 5 ? '...' : '');

            $date = !empty($row['EVENT_DATE']) ? date('M d, Y', strtotime($row['EVENT_DATE'])) : '';
            $time = !empty($row['EVENT_TIME']) ? date('h:i A', strtotime($row['EVENT_TIME'])) : '';

            $image = trim((string)($row['IMAGE'] ?? $row['IMGAE'] ?? ''));
            $banner = $image ? self::TOURNAMENT_BANNER_DIR . basename($image) : '';
            $img = ($banner && is_file($projectRoot . '/' . $banner)) ? $banner : self::DEFAULT_TOURNAMENT_IMG;

            $open = true;
            if (!empty($row['CANCEL_DATE'])) {
                try {
                    $cancel = new \DateTime(
                        $row['CANCEL_DATE'] . ' ' . ($row['CANCEL_TIME'] ?? '10:00:00'),
                        $tz
                    );
                    $open = $now < $cancel;
                } catch (Throwable $e) {
                    $open = true;
                }
            }

            $row['display_name'] = $name;
            $row['display_tagline'] = $tagline;
            $row['formatted_date'] = $date;
            $row['formatted_time'] = $time;
            $row['image_path'] = $img;
            $row['is_open'] = $open;
            $row['gender_cat'] = (string)($row['GENDER_CATEGORY'] ?? '');
            $row['event_type'] = (string)($row['EVENT_TYPE'] ?? '');
            $row['event_cat'] = (string)($row['EVENT_CATEGORY'] ?? '');
            $row['venue'] = (string)($row['EVENT_VENUE'] ?? '');
            $row['shuttle'] = (string)($row['SHUTTLE_TYPE'] ?? 'Feather');
            $row['fee_formatted'] = number_format((float)($row['EVENT_COST'] ?? 0), 2);
            $row['teams_joined'] = (int)($row['joined_count'] ?? 0);

            $tournaments[] = $row;
        }

        return $tournaments;
    }

    // Fetch latest store products.
    private function getStoreProducts(): array
    {
        try {
            $sql = "
                SELECT
                    ID,
                    PRODUCT_NAME,
                    PRICE,
                    IMAGE,
                    TNAME
                FROM ca_products
                ORDER BY ID DESC
                LIMIT " . self::PRODUCT_LIMIT;

            $rows = $this->fetchAll($sql);
            $projectRoot = dirname(__DIR__, 2);

            $products = [];
            foreach ($rows as $product) {
                $name = (string)($product['PRODUCT_NAME'] ?? '');
                $desc = !empty($product['TNAME']) ? (string)$product['TNAME'] : 'Free Delivery';
                $price = number_format((float)($product['PRICE'] ?? 0), 2);

                $image = trim((string)($product['IMAGE'] ?? ''));
                $image = $image ? self::PRODUCT_IMAGE_DIR . ltrim($image, '/') : '';
                $imagePath = ($image && is_file($projectRoot . '/' . $image)) ? $image : self::DEFAULT_PRODUCT_IMG;

                $product['display_name'] = $name;
                $product['display_desc'] = $desc;
                $product['formatted_price'] = $price;
                $product['image_path'] = $imagePath;

                $products[] = $product;
            }

            return $products;
        } catch (Throwable $e) {
            error_log('Error fetching store products: ' . $e->getMessage());
            return [];
        }
    }

    // Fetch playground statistics.
    private function getPlaygroundStats(): array
    {
        $sql = "
        SELECT
            COALESCE(SUM(CASE WHEN USERTYPE = 'Player' AND LOG_STATUS = 'Y' THEN 1 ELSE 0 END), 0) AS total_players,
            COALESCE(SUM(CASE WHEN USERTYPE = 'Host' AND LOG_STATUS = 'Y' THEN 1 ELSE 0 END), 0) AS total_clubs,
            (SELECT COUNT(*) FROM ca_events WHERE STATUS = 'Completed') AS total_sessions
        FROM ca_users
    ";

        try {
            $row = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);

            $players = (int) ($row['total_players'] ?? 0);
            $clubs = (int) ($row['total_clubs'] ?? 0);
            $sessions = (int) ($row['total_sessions'] ?? 0);

            return [
                ['icon' => 'fa-users', 'count' => $players, 'label' => 'Total Players'],
                ['icon' => 'fa-building', 'count' => $clubs, 'label' => 'Total Clubs'],
                ['icon' => 'fa-play', 'count' => $sessions, 'label' => 'Total Sessions'],
                ['icon' => 'fa-fire', 'count' => $sessions * 12, 'label' => 'Total Matches'],
            ];
        } catch (PDOException $e) {
            $this->logDatabaseError(__METHOD__, $e);
            return $this->emptyStats();
        }
    }

 // Fetch active community reviews.
 
    private function getCommunityReviews(): array
    {
        $sql = "
            SELECT
                r.MESSAGE,
                r.PLAYER_ROLE,
                r.RATING,
                u.NAME,
                u.PROFILE_IMAGE
            FROM ca_reviews r
            INNER JOIN ca_users u
                ON u.ID = r.USER_ID
            WHERE r.STATUS = 'Active'
            ORDER BY r.DATE_CREATED DESC
            LIMIT " . self::REVIEW_LIMIT;

        return $this->fetchAll($sql);
    }

    private function getPosters(): array
    {
        try {
            $sql = "
                SELECT media_url
                FROM ca_landing_page_media
                WHERE media_type = 'poster'
                  AND is_active = 1
            ";

            $result = $this->fetchAll($sql);

            return $result;
        } catch (Throwable $e) {
            error_log('Error fetching posters: ' . $e->getMessage());
            return [];
        }
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logDatabaseError(__METHOD__, $e);

            return [];
        }
    }

   // Execute SELECT query and return a single column.
    
    private function fetchColumn(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $this->logDatabaseError(__METHOD__, $e);

            return [];
        }
    }

    /**
     * Empty playground statistics.
     */
    private function emptyStats(): array
    {
        return [
            [
                'icon' => 'fa-users',
                'count' => 0,
                'label' => 'Total Players',
            ],
            [
                'icon' => 'fa-building',
                'count' => 0,
                'label' => 'Total Clubs',
            ],
            [
                'icon' => 'fa-play',
                'count' => 0,
                'label' => 'Total Sessions',
            ],
            [
                'icon' => 'fa-fire',
                'count' => 0,
                'label' => 'Total Matches',
            ],
        ];
    }

    /**
     * Log database exceptions without exposing
     * sensitive database information to users.
     */
    private function logDatabaseError(string $context, PDOException $exception): void
    {
        error_log(
            sprintf(
                '[%s] Database error: %s',
                $context,
                $exception->getMessage()
            )
        );
    }
}