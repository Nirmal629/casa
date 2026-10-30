<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class ReviewModel extends Model
{
    protected string $table = 'ca_reviews';
    protected string $primaryKey = 'ID';

    protected array $fillable = [
        'USER_ID', 'PLAYER_ROLE', 'MESSAGE', 'RATING', 'STATUS', 'DATE_CREATED'
    ];

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    public function getActiveReviews(int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.MESSAGE, r.PLAYER_ROLE, r.RATING, u.NAME, u.PROFILE_IMAGE
             FROM ca_reviews r
             JOIN ca_users u ON r.USER_ID = u.ID
             WHERE r.STATUS = 'Active'
             ORDER BY r.DATE_CREATED DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Insert or update a player review
     */
    public function saveReview(int $userId, string $userType, int $rating, string $message, ?int $reviewId = null): array
    {
        if ($userId <= 0) {
            return ['status' => 'error', 'message' => 'Please login to submit a review.'];
        }

        if ($rating < 1 || $rating > 5) {
            $rating = 5;
        }

        $allowed_tags = '<b><strong><i><em><u><s><strike><p><h1><h2><h3><ul><ol><li><a><hr><br>';
        $clean_message = strip_tags($message, $allowed_tags);

        if (trim(strip_tags($clean_message)) === '' && trim($clean_message) === '') {
            return ['status' => 'error', 'message' => 'Please write a review message.'];
        }

        try {
            if ($reviewId && $reviewId > 0) {
                // Verify that review belongs to current user
                $checkStmt = $this->db->prepare("SELECT ID FROM ca_reviews WHERE ID = ? AND USER_ID = ?");
                $checkStmt->execute([$reviewId, $userId]);
                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if (!$existing) {
                    return ['status' => 'error', 'message' => 'Review not found or unauthorized.'];
                }

                $stmt = $this->db->prepare("UPDATE ca_reviews SET RATING = ?, MESSAGE = ? WHERE ID = ? AND USER_ID = ?");
                $stmt->execute([$rating, $clean_message, $reviewId, $userId]);

                return [
                    'status' => 'success',
                    'message' => 'Your review has been updated successfully.',
                    'action' => 'update',
                    'review_id' => $reviewId
                ];
            } else {
                $stmt = $this->db->prepare("INSERT INTO ca_reviews (USER_ID, PLAYER_ROLE, RATING, MESSAGE, STATUS, DATE_CREATED) VALUES (?, ?, ?, ?, 'Active', NOW())");
                $stmt->execute([$userId, $userType, $rating, $clean_message]);
                $newId = (int) $this->db->lastInsertId();

                return [
                    'status' => 'success',
                    'message' => 'Thank you! Your review has been submitted successfully.',
                    'action' => 'insert',
                    'review_id' => $newId
                ];
            }
        } catch (\PDOException $e) {
            \error_log("ReviewModel saveReview error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
}

