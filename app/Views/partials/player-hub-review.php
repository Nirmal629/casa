<?php
$reviewUserName = !empty($player_prefs['NAME']) ? $player_prefs['NAME'] : ($_SESSION['name'] ?? 'Mr. Lorem Ipsum');
$reviewInitials = trim((string) $reviewUserName) !== '' ? \App\Helpers\Session::getUserInitials($reviewUserName) : 'ML';
$reviewCurrentDate = date('d.m.y');

$_rawProfileImg = !empty($player_prefs['PROFILE_IMAGE']) ? $player_prefs['PROFILE_IMAGE'] : ($_SESSION['profileImage'] ?? '');
$profileFile    = !empty($_rawProfileImg) ? "profile_img/" . ltrim(basename($_rawProfileImg), '/\\') : "";
$reviewUserImg  = (!empty($profileFile) && file_exists(__DIR__ . '/../../../' . $profileFile))
    ? '/' . $profileFile
    : ($user_profile_img ?? '/assets/images/profile.jpg');

// Reviews passed from PlayerController
$hub_reviews = $reviews ?? [];

?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,opsz,wght@0,8..60,400;0,8..60,600;0,8..60,700;1,8..60,400;1,8..60,600&display=swap');

    /* Review Modal Theming & Controls */
    #reviewmodal {
        z-index: 1065 !important;
        --color-bg: #f3f2f2;
        --color-surface: #eae9e9;
        --color-text: #201e1d;
        --color-accent: #0088b0;
        --color-accent-600: #1186ac;
        --color-accent-700: #006786;
        --color-accent-100: #e9f8ff;
        --color-accent-2-600: #d82071;
        --color-divider: rgba(32, 30, 29, 0.16);
        --color-neutral-100: #f8f4f4;
        --color-neutral-300: #d7d3d3;
        --font-heading: "Source Serif 4", Georgia, serif;
        --font-heading-weight: 600;
        --font-body: "Source Serif 4", Georgia, serif;
        --space-1: 5px;
        --space-2: 10px;
        --space-3: 15px;
        --space-4: 20px;
        --radius-sm: 1px;
        --radius-md: 2px;
        --radius-lg: 4px;
        --shadow-lg: 0 12px 32px rgba(45, 43, 43, 0.22);
    }
    #reviewmodal .modal-dialog.review-bs-dialog {
        max-width: 360px !important;
        width: 360px !important;
        margin: 1.75rem auto;
    }
    #reviewmodal .modal-content.review-bs-content {
        background: var(--color-surface) !important;
        color: var(--color-text) !important;
        font-family: var(--font-body) !important;
        border: none !important;
        border-radius: var(--radius-lg) !important;
        box-shadow: var(--shadow-lg) !important;
        padding: var(--space-4) !important;
        display: flex !important;
        flex-direction: column !important;
        gap: var(--space-3) !important;
    }
    #reviewmodal .review-bs-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--color-accent-100);
        color: var(--color-accent-700);
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: var(--font-heading);
        font-weight: 600;
        font-size: 14px;
        flex-shrink: 0;
    }
    #reviewmodal .review-bs-name {
        font-family: var(--font-heading);
        font-weight: 600;
        font-size: 16px;
        line-height: 1.2;
        color: var(--color-text);
    }
    #reviewmodal .review-bs-date {
        display: flex;
        align-items: center;
        gap: var(--space-1);
        margin-top: 2px;
        font-size: 12px;
        opacity: 0.6;
        color: var(--color-text);
        font-family: var(--font-body);
    }
    #reviewmodal .review-bs-close {
        width: 28px !important;
        height: 28px !important;
        min-height: 28px !important;
        color: var(--color-accent) !important;
        background: transparent !important;
        border: none !important;
        padding: 0 !important;
        cursor: pointer;
        border-radius: var(--radius-md);
    }
    #reviewmodal .review-bs-rating-heading {
        font-family: var(--font-heading);
        font-weight: 600;
        font-size: 13px;
        color: var(--color-text);
    }
    #reviewmodal .review-bs-status-text {
        font-size: 12px;
        opacity: 0.6;
        min-width: 64px;
        text-align: right;
        color: var(--color-text);
        font-family: var(--font-body);
    }
    #reviewmodal .review-star-btn {
        background: none !important;
        border: none !important;
        padding: 2px !important;
        cursor: pointer;
        color: var(--color-neutral-300);
        line-height: 0 !important;
        transition: color 0.15s ease, transform 0.1s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    #reviewmodal .review-star-btn svg { display: block; }
    #reviewmodal .review-star-btn.is-active,
    #reviewmodal .review-star-btn.is-hover { color: #E8A339 !important; }

    #reviewmodal .review-bs-toolbar {
        display: flex;
        gap: 3px;
        flex-wrap: wrap;
        align-items: center;
        padding: 6px var(--space-2);
        background: var(--color-surface);
        border-radius: var(--radius-md);
        border: 1px solid rgba(32, 30, 29, 0.08);
    }
    #reviewmodal .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
        text-decoration: none;
        font-family: var(--font-heading);
        font-weight: var(--font-heading-weight);
        font-size: 14px;
        line-height: 1.2;
        color: var(--color-text);
        background: transparent;
        border: 1px solid transparent;
        padding: var(--space-2) calc(var(--space-3) * 1.2);
        border-radius: var(--radius-md);
        transition: all 0.15s ease;
    }
    #reviewmodal .btn-icon {
        width: 22px;
        height: 22px;
        padding: 0;
        flex-shrink: 0;
    }
    #reviewmodal .btn-primary {
        background: var(--color-accent) !important;
        color: var(--color-bg) !important;
        border: 1px solid transparent !important;
    }
    #reviewmodal .btn-primary:hover  { background: var(--color-accent-600) !important; color: var(--color-bg) !important; }
    #reviewmodal .btn-primary:active { background: var(--color-accent-700) !important; }
    #reviewmodal .btn-secondary {
        border: 1px solid var(--color-divider) !important;
        background: transparent !important;
        color: var(--color-text) !important;
    }
    #reviewmodal .btn-secondary:hover  { background: rgba(32, 30, 29, 0.07) !important; }
    #reviewmodal .btn-secondary:active { background: rgba(32, 30, 29, 0.14) !important; }
    #reviewmodal .btn-ghost {
        color: var(--color-accent) !important;
        padding-inline: var(--space-1);
    }
    #reviewmodal .btn-ghost:hover  { background: rgba(0, 136, 176, 0.10) !important; }
    #reviewmodal .btn-ghost:active { background: rgba(0, 136, 176, 0.18) !important; }
    #reviewmodal .input {
        width: 100%;
        min-height: 36px;
        padding: 6px 10px;
        font: inherit;
        font-size: 14px;
        color: var(--color-text);
        caret-color: var(--color-accent);
        background: var(--color-surface);
        border: 1px solid var(--color-divider);
        border-radius: var(--radius-md);
        box-sizing: border-box;
    }
    #reviewmodal .input:hover          { border-color: rgba(32, 30, 29, 0.45); }
    #reviewmodal .input:focus-visible,
    #reviewmodal .input:focus          { border-color: var(--color-accent); outline: none; }
    #reviewmodal .review-bs-editor:empty:before {
        content: attr(data-placeholder);
        color: rgba(32, 30, 29, 0.65);
        font-family: var(--font-body);
        pointer-events: none;
        display: block;
    }

    #reviewSuccessToast {
        position: fixed;
        top: 24px;
        right: 24px;
        background: #0f172a;
        color: #f8fafc;
        border: 1px solid rgba(255, 255, 255, 0.15);
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35);
        display: flex;
        align-items: center;
        gap: 10px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        font-size: 14px;
        font-weight: 500;
        z-index: 999999;
        opacity: 0;
        transform: translateY(-15px);
        transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1), transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        pointer-events: none;
    }
    #reviewSuccessToast.show {
        opacity: 1;
        transform: translateY(0);
    }
</style>

<!-- ═══════════════════════════════════════════════════════════════════════
     REVIEW SECTION  —  Slider + Write-Review button
═══════════════════════════════════════════════════════════════════════ -->
<div class="review">
    <h2>✨ WE VALUE YOUR VOICE ✨</h2>
    <div class="reviewSlider reviewSlick">
        <?php if (!empty($hub_reviews)): ?>
            <?php foreach ($hub_reviews as $rRow):
                $rName   = !empty($rRow['NAME'])        ? $rRow['NAME']        : 'Player';
                $rRole   = !empty($rRow['PLAYER_ROLE']) ? $rRow['PLAYER_ROLE'] : 'Member';
                $rRating = intval($rRow['RATING'] ?? 5);
                $rDate   = !empty($rRow['DATE_CREATED']) ? date('d.m.Y', strtotime($rRow['DATE_CREATED'])) : '';

                $profileFile = !empty($rRow['PROFILE_IMAGE']) ? "profile_img/" . ltrim(basename($rRow['PROFILE_IMAGE']), '/\\') : "";
                $rUserImg    = (!empty($profileFile) && file_exists(__DIR__ . '/../../../' . $profileFile))
                    ? '/' . $profileFile
                    : "/assets/images/profile.jpg";

                $rInitials = trim((string) $rName) !== '' ? \App\Helpers\Session::getUserInitials((string) $rName) : 'P';
            ?>
            <div class="reviewSlide" data-review-id="<?= $rRow['ID'] ?>">
                <div class="reviewCard">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;gap:10px;">
                        <div style="display:flex;align-items:center;gap:10px;overflow:hidden;">
                            <?php if (!empty($rUserImg)): ?>
                                <img src="<?= htmlspecialchars($rUserImg) ?>" alt="<?= htmlspecialchars($rName) ?>"
                                     style="width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0;border:1.5px solid #0088b0;"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div style="display:none;width:38px;height:38px;border-radius:50%;background:#e9f8ff;color:#006786;font-weight:700;font-size:13px;align-items:center;justify-content:center;flex-shrink:0;">
                                    <?= htmlspecialchars($rInitials) ?>
                                </div>
                            <?php else: ?>
                                <div style="width:38px;height:38px;border-radius:50%;background:#e9f8ff;color:#006786;font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <?= htmlspecialchars($rInitials) ?>
                                </div>
                            <?php endif; ?>
                            <div style="text-align:left;overflow:hidden;">
                                <h6 style="margin:0;font-weight:700;font-size:14px;color:#0f172a;line-height:1.2;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;">
                                    <?= htmlspecialchars($rName) ?>
                                </h6>
                                <small style="color:#64748b;font-size:11px;display:block;">
                                    <?= htmlspecialchars($rRole) ?><?= $rDate ? ' • ' . $rDate : '' ?>
                                </small>
                            </div>
                        </div>
                        <?php $isMyReview = (!empty($current_user_id) && (int)($rRow['USER_ID'] ?? 0) === (int)$current_user_id); ?>
                        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                            <div class="reviewCardStars" style="color:#E8A339;font-size:14px;letter-spacing:1px;display:flex;align-items:center;">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <?= ($s <= $rRating) ? '★' : '<span style="color:#cbd5e1;">★</span>' ?>
                                <?php endfor; ?>
                            </div>
                            <?php if ($isMyReview): ?>
                                <button type="button" class="btn-edit-review"
                                        data-bs-toggle="modal"
                                        data-bs-target="#reviewmodal"
                                        data-id="<?= $rRow['ID'] ?>"
                                        data-rating="<?= $rRating ?>"
                                        onclick="if(window.openEditReviewModal){ window.openEditReviewModal('<?= $rRow['ID'] ?>', <?= $rRating ?>, this); return false; }"
                                        title="Edit Review">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    <span>Edit</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="reviewTextDiv" style="text-align:left;color:#334155;font-size:14px;line-height:1.5;">
                        <?= $rRow['MESSAGE'] ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="reviewSlide">
                <div class="reviewCard">
                    <div class="reviewTextDiv">
                        <p>You haven't submitted any reviews yet. Share your experience with Casa!</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <button type="button" class="writeReviewBtn" data-bs-toggle="modal" data-bs-target="#reviewmodal" onclick="if(window.openWriteReviewModal){ window.openWriteReviewModal(); return false; }">Please Write a Review</button>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     REVIEW MODAL
═══════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="reviewmodal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="reviewmodalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered review-bs-dialog">
        <div class="modal-content review-bs-content">

            <!-- User Header Row -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:var(--space-3)">
                <div style="display:flex;align-items:center;gap:var(--space-3)">
                    <div class="review-bs-avatar">
                        <?php if (!empty($reviewUserImg)): ?>
                            <img src="<?= htmlspecialchars($reviewUserImg) ?>" alt="<?= htmlspecialchars($reviewUserName) ?>"
                                 style="width:100%;height:100%;border-radius:50%;object-fit:cover;display:block;"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;">
                                <?= htmlspecialchars($reviewInitials) ?>
                            </span>
                        <?php else: ?>
                            <?= htmlspecialchars($reviewInitials) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="review-bs-name"><?= htmlspecialchars($reviewUserName) ?></div>
                        <div class="review-bs-date">
                            <svg width="12" height="12" viewBox="0 0 256 256" fill="currentColor" style="flex-shrink:0">
                                <path d="M208,32H184V24a8,8,0,0,0-16,0v8H88V24a8,8,0,0,0-16,0v8H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM72,48v8a8,8,0,0,0,16,0V48h80v8a8,8,0,0,0,16,0V48h24V80H48V48Zm136,160H48V96H208V208Z"></path>
                            </svg>
                            <?= $reviewCurrentDate ?>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-ghost btn-icon review-bs-close" data-bs-dismiss="modal" aria-label="Close" onclick="if(window.closeReviewModal){ window.closeReviewModal(); }">
                    <svg width="14" height="14" viewBox="0 0 256 256" fill="currentColor">
                        <path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"></path>
                    </svg>
                </button>
            </div>

            <!-- Rating Row -->
            <div style="display:flex;align-items:center;justify-content:space-between;gap:var(--space-2)">
                <span class="review-bs-rating-heading">Your Rating</span>
                <div style="display:flex;align-items:center;gap:var(--space-3)">
                    <div style="display:flex;gap:4px" id="reviewStars">
                        <?php for ($sv = 1; $sv <= 5; $sv++): ?>
                        <button type="button" aria-label="Rate <?= $sv ?>" class="review-star-btn is-active" data-value="<?= $sv ?>">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2.5 L14.6 8.9 L21.5 9.5 L16.2 14.1 L17.8 20.8 L12 17.3 L6.2 20.8 L7.8 14.1 L2.5 9.5 L9.4 8.9 Z"></path>
                            </svg>
                        </button>
                        <?php endfor; ?>
                    </div>
                    <span id="reviewRatingText" class="review-bs-status-text">Excellent</span>
                    <input type="hidden" name="rating"    id="reviewRatingInput" value="5">
                    <input type="hidden" name="review_id" id="reviewIdInput"     value="">
                    <input type="hidden" name="user_id"   id="reviewUserIdInput" value="<?= htmlspecialchars((string)($reviewUserId ?? ($current_user_id ?? ($_SESSION['user_id'] ?? '')))) ?>">
                    <input type="hidden" name="csrf_token" id="reviewCsrfInput"  value="<?= htmlspecialchars(\App\Helpers\Session::getCsrfToken()) ?>">
                </div>
            </div>

            <!-- Toolbar Grid -->
            <div class="review-bs-toolbar">
                <button type="button" class="btn btn-secondary btn-icon tb-btn-act" data-command="bold"          style="width:22px;height:22px;font-family:var(--font-heading);font-weight:700;font-size:11px"              title="Bold">B</button>
                <button type="button" class="btn btn-secondary btn-icon tb-btn-act" data-command="italic"        style="width:22px;height:22px;font-family:var(--font-heading);font-style:italic;font-size:11px"          title="Italic">I</button>
                <button type="button" class="btn btn-secondary btn-icon tb-btn-act" data-command="underline"     style="width:22px;height:22px;font-family:var(--font-heading);text-decoration:underline;font-size:11px"  title="Underline">U</button>
                <button type="button" class="btn btn-secondary btn-icon tb-btn-act" data-command="strikeThrough" style="width:22px;height:22px;font-family:var(--font-heading);text-decoration:line-through;font-size:11px" title="Strikethrough">S</button>
                <span style="width:1px;height:14px;background:var(--color-divider)"></span>
                <button type="button" class="btn btn-secondary btn-icon tb-btn-act" data-command="justifyLeft"   aria-label="Align left"   title="Align left"   style="width:22px;height:22px;display:flex;flex-direction:column;gap:2px;padding:6px"><span style="height:1.5px;background:currentColor;width:100%"></span><span style="height:1.5px;background:currentColor;width:65%"></span><span style="height:1.5px;background:currentColor;width:85%"></span></button>
                <button type="button" class="btn btn-secondary btn-icon tb-btn-act" data-command="justifyCenter" aria-label="Align center" title="Align center" style="width:22px;height:22px;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px"><span style="height:1.5px;background:currentColor;width:100%"></span><span style="height:1.5px;background:currentColor;width:65%"></span><span style="height:1.5px;background:currentColor;width:85%"></span></button>
                <button type="button" class="btn btn-secondary btn-icon tb-btn-act" data-command="justifyRight"  aria-label="Align right"  title="Align right"  style="width:22px;height:22px;display:flex;flex-direction:column;align-items:flex-end;gap:2px;padding:6px"><span style="height:1.5px;background:currentColor;width:100%"></span><span style="height:1.5px;background:currentColor;width:65%"></span><span style="height:1.5px;background:currentColor;width:85%"></span></button>
                <button type="button" class="btn btn-secondary btn-icon tb-btn-act" data-command="justifyFull"   aria-label="Justify"      title="Justify"      style="width:22px;height:22px;display:flex;flex-direction:column;gap:2px;padding:6px"><span style="height:1.5px;background:currentColor;width:100%"></span><span style="height:1.5px;background:currentColor;width:100%"></span><span style="height:1.5px;background:currentColor;width:100%"></span></button>
                <span style="width:1px;height:14px;background:var(--color-divider)"></span>
                <button type="button" class="btn btn-secondary btn-icon tb-btn-act" data-command="insertUnorderedList" aria-label="Bulleted list"  title="Bulleted list"  style="width:22px;height:22px;display:flex;flex-direction:column;gap:2px;padding:6px"><span style="height:1.5px;background:currentColor;width:100%;border-radius:2px"></span><span style="height:1.5px;background:currentColor;width:100%;border-radius:2px"></span></button>
                <button type="button" class="btn btn-secondary btn-icon tb-btn-act" data-command="insertOrderedList"   aria-label="Numbered list" title="Numbered list" style="width:22px;height:22px;font-family:var(--font-body);font-size:9px;line-height:1">1.</button>
                <span style="width:1px;height:14px;background:var(--color-divider)"></span>
                <button type="button" class="btn btn-secondary btn-icon tb-btn-act" data-command="formatBlock" data-value="h1" style="width:22px;height:22px;font-family:var(--font-heading);font-weight:600;font-size:9px" title="Heading 1">H1</button>
                <button type="button" class="btn btn-secondary btn-icon tb-btn-act" data-command="formatBlock" data-value="h2" style="width:22px;height:22px;font-family:var(--font-heading);font-weight:600;font-size:9px" title="Heading 2">H2</button>
                <button type="button" class="btn btn-secondary btn-icon tb-btn-act" data-command="formatBlock" data-value="p"  style="width:22px;height:22px;font-family:var(--font-body);font-size:10px"                 title="Paragraph">P</button>
                <span style="width:1px;height:14px;background:var(--color-divider)"></span>
                <button type="button" class="btn btn-secondary btn-icon tb-btn-act" data-command="createLink" aria-label="Link"        title="Link"        style="width:22px;height:22px">
                    <svg width="11" height="11" viewBox="0 0 256 256" fill="currentColor"><path d="M228.6,152.71,201,180.28a48,48,0,0,1-67.9-67.89l27.71-27.71a8,8,0,0,1,11.31,11.31L144.42,123.7a32,32,0,0,0,45.24,45.25l27.6-27.61a32,32,0,0,0-45.25-45.24,8,8,0,0,1-11.31-11.32,48,48,0,0,1,67.9,67.9ZM115.31,144.42a8,8,0,0,0,0-11.31,32,32,0,0,1,0-45.25l27.6-27.6a32,32,0,0,1,45.25,45.24,8,8,0,0,0,11.31,11.32,48,48,0,0,0-67.9-67.9L104,76.32a48,48,0,0,0,0,67.9,8,8,0,0,0,11.32.2Z"></path></svg>
                </button>
                <button type="button" class="btn btn-secondary btn-icon tb-btn-act" data-command="unlink"     aria-label="Remove link" title="Remove link" style="width:22px;height:22px;position:relative">
                    <svg width="11" height="11" viewBox="0 0 256 256" fill="currentColor"><path d="M228.6,152.71,201,180.28a48,48,0,0,1-67.9-67.89l27.71-27.71a8,8,0,0,1,11.31,11.31L144.42,123.7a32,32,0,0,0,45.24,45.25l27.6-27.61a32,32,0,0,0-45.25-45.24,8,8,0,0,1-11.31-11.32,48,48,0,0,1,67.9,67.9ZM115.31,144.42a8,8,0,0,0,0-11.31,32,32,0,0,1,0-45.25l27.6-27.6a32,32,0,0,1,45.25,45.24,8,8,0,0,0,11.31,11.32,48,48,0,0,0-67.9-67.9L104,76.32a48,48,0,0,0,0,67.9,8,8,0,0,0,11.32.2Z"></path></svg>
                    <span style="position:absolute;top:3px;left:3px;width:16px;height:1.5px;background:var(--color-accent-2-600);transform:rotate(-40deg)"></span>
                </button>
            </div>

            <!-- Editor -->
            <div class="input review-bs-editor" id="reviewMessageEditor" contenteditable="true"
                 data-placeholder="Write your review here…"
                 style="min-height:90px;max-height:220px;overflow-y:auto;resize:vertical;font-family:var(--font-body);font-size:14px;line-height:1.5;outline:none;"></div>

            <!-- Footer -->
            <div style="display:flex;justify-content:flex-end;gap:var(--space-2)">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="if(window.closeReviewModal){ window.closeReviewModal(); }">Cancel</button>
                <button type="button" class="btn btn-primary"   id="reviewSubmitBtn">Submit Review</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     REVIEW  —  JavaScript  (self-contained DOMContentLoaded)
═══════════════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    function ensureModalInBody() {
        var modal = document.getElementById('reviewmodal');
        if (modal && modal.parentNode !== document.body) {
            document.body.appendChild(modal);
        }
        return modal;
    }

    function getModalInstance(modalEl) {
        if (!modalEl) return null;
        if (window.bootstrap && bootstrap.Modal) {
            return (typeof bootstrap.Modal.getOrCreateInstance === 'function')
                ? bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: 'static', keyboard: false })
                : (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false }));
        }
        return null;
    }

    function openModalDirectly() {
        var modalEl = ensureModalInBody();
        if (!modalEl) return;

        // Clean any stray backdrops that may have gotten desynchronized
        document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
        document.body.classList.remove('modal-open');

        var inst = getModalInstance(modalEl);
        if (inst) {
            inst.show();
            return;
        }

        var $ = window.jQuery;
        if ($ && typeof $(modalEl).modal === 'function') {
            $(modalEl).modal('show');
            return;
        }

        modalEl.classList.add('show');
        modalEl.style.display = 'block';
        document.body.classList.add('modal-open');
    }

    window.closeReviewModal = function () {
        var modalEl = document.getElementById('reviewmodal');
        if (!modalEl) return;

        var inst = (window.bootstrap && bootstrap.Modal) ? bootstrap.Modal.getInstance(modalEl) : null;
        if (inst) {
            inst.hide();
        } else {
            var $ = window.jQuery;
            if ($ && typeof $(modalEl).modal === 'function') {
                $(modalEl).modal('hide');
            } else {
                modalEl.classList.remove('show');
                modalEl.style.display = 'none';
            }
        }

        setTimeout(function () {
            document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        }, 200);
    };

    // Rating star helpers
    var labels = ['', 'Poor', 'Fair', 'Good', 'Very good', 'Excellent'];
    var currentRating = 5;

    function paintStars(val) {
        var stars = document.querySelectorAll('#reviewStars .review-star-btn');
        stars.forEach(function (star) {
            var starVal = parseInt(star.getAttribute('data-value'), 10);
            if (val > 0) star.classList.toggle('is-hover', starVal <= val);
            else         star.classList.remove('is-hover');
        });
    }

    function setModalRating(val) {
        currentRating = Math.min(5, Math.max(1, parseInt(val, 10) || 5));
        var ratingInput = document.getElementById('reviewRatingInput');
        var ratingText  = document.getElementById('reviewRatingText');
        var stars       = document.querySelectorAll('#reviewStars .review-star-btn');

        if (ratingInput) ratingInput.value = currentRating;
        if (ratingText)  ratingText.textContent = labels[currentRating] || 'Select a rating';
        stars.forEach(function (s) {
            var sVal = parseInt(s.getAttribute('data-value'), 10);
            s.classList.toggle('is-active', sVal <= currentRating);
            s.classList.remove('is-hover');
        });
    }

    function resetModalForNew() {
        var reviewIdInput = document.getElementById('reviewIdInput');
        var editor        = document.getElementById('reviewMessageEditor');
        var submitBtn     = document.getElementById('reviewSubmitBtn');

        if (reviewIdInput) reviewIdInput.value = '';
        setModalRating(5);
        if (editor) editor.innerHTML = '';
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Review';
        }
    }

    window.openWriteReviewModal = function () {
        resetModalForNew();
        openModalDirectly();
    };

    window.openEditReviewModal = function (id, rating, btn) {
        var reviewIdInput = document.getElementById('reviewIdInput');
        var editor        = document.getElementById('reviewMessageEditor');
        var submitBtn     = document.getElementById('reviewSubmitBtn');

        if (reviewIdInput) reviewIdInput.value = id || '';
        setModalRating(rating || 5);

        var slide = null;
        if (btn && btn.closest) {
            slide = btn.closest('.reviewSlide');
        }
        if (!slide && id) {
            slide = document.querySelector('.reviewSlide[data-review-id="' + id + '"]');
        }
        var msgHtml = '';
        if (slide) {
            var textDiv = slide.querySelector('.reviewTextDiv');
            if (textDiv) {
                msgHtml = textDiv.innerHTML.trim();
            }
        }
        if (editor) editor.innerHTML = msgHtml;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Update Review';
        }
        openModalDirectly();
    };

    // Capture phase document click listener to guarantee clicks always trigger
    document.addEventListener('click', function (e) {
        var editBtn = e.target.closest ? e.target.closest('.btn-edit-review') : null;
        if (editBtn) {
            e.preventDefault();
            e.stopPropagation();
            var id = editBtn.getAttribute('data-id');
            var rating = editBtn.getAttribute('data-rating') || 5;
            window.openEditReviewModal(id, rating, editBtn);
            return;
        }

        var writeBtn = e.target.closest ? e.target.closest('.writeReviewBtn') : null;
        if (writeBtn) {
            e.preventDefault();
            e.stopPropagation();
            window.openWriteReviewModal();
            return;
        }
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        var $ = window.jQuery;
        ensureModalInBody();

        // Star events
        var stars = document.querySelectorAll('#reviewStars .review-star-btn');
        var ratingText = document.getElementById('reviewRatingText');

        stars.forEach(function (star) {
            var val = parseInt(star.getAttribute('data-value'), 10);
            star.addEventListener('mouseenter', function () {
                paintStars(val);
                if (ratingText) ratingText.textContent = labels[val] || 'Select a rating';
            });
            star.addEventListener('click', function () { setModalRating(val); });
        });

        var starsWrap = document.getElementById('reviewStars');
        if (starsWrap) {
            starsWrap.addEventListener('mouseleave', function () {
                paintStars(0);
                if (ratingText) ratingText.textContent = currentRating ? labels[currentRating] : 'Select a rating';
            });
        }

        // Toolbar
        document.querySelectorAll('#reviewmodal .tb-btn-act').forEach(function (btn) {
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
                var command = btn.getAttribute('data-command');
                var value   = btn.getAttribute('data-value') || null;
                var editor  = document.getElementById('reviewMessageEditor');
                if (command === 'createLink') {
                    var url = prompt('Enter the link URL:', 'https://');
                    if (url) document.execCommand(command, false, url);
                } else if (command === 'formatBlock') {
                    document.execCommand(command, false, '<' + value + '>');
                } else if (command) {
                    document.execCommand(command, false, value);
                }
                if (editor) editor.focus();
            });
        });

        // Bootstrap modal event listeners
        var reviewModalElement = document.getElementById('reviewmodal');
        if (reviewModalElement) {
            reviewModalElement.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                var editBtn = trigger ? (trigger.closest ? trigger.closest('.btn-edit-review') : (trigger.classList && trigger.classList.contains('btn-edit-review') ? trigger : null)) : null;
                if (editBtn) {
                    var id = editBtn.getAttribute('data-id');
                    var rating = editBtn.getAttribute('data-rating') || 5;
                    window.openEditReviewModal(id, rating, editBtn);
                } else {
                    // Only reset if reviewIdInput is empty (not already prefilled by openEditReviewModal)
                    var reviewIdInput = document.getElementById('reviewIdInput');
                    if (!reviewIdInput || !reviewIdInput.value) {
                        resetModalForNew();
                    }
                }
            });

            reviewModalElement.addEventListener('hidden.bs.modal', function () {
                document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
                resetModalForNew();
            });
        }

        // Helpers
        function escapeHtml(text) {
            if (!text) return '';
            var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function showReviewToast(msg, isError) {
            var toast = document.getElementById('reviewSuccessToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'reviewSuccessToast';
                document.body.appendChild(toast);
            }
            var iconHtml = isError
                ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'
                : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M20 6 9 17l-5-5"/></svg>';
            toast.innerHTML = iconHtml + ' <span>' + (msg || (isError ? 'An error occurred.' : 'Review submitted successfully!')) + '</span>';
            toast.classList.add('show');
            setTimeout(function () { toast.classList.remove('show'); }, 3500);
        }

        // Live DOM update functions
        var currentUserData = {
            name:     <?= json_encode($reviewUserName) ?>,
            role:     <?= json_encode(!empty($player_prefs['LEVEL']) ? $player_prefs['LEVEL'] : 'Player') ?>,
            img:      <?= json_encode($reviewUserImg) ?>,
            initials: <?= json_encode($reviewInitials) ?>
        };

        function updateReviewSlideDirectly(reviewId, rating, cleanMessage) {
            var rVal = parseInt(rating, 10) || 5;
            var starHtml = '';
            for (var s = 1; s <= 5; s++) {
                starHtml += (s <= rVal) ? '★' : '<span style="color:#cbd5e1;">★</span>';
            }
            var slide = $ ? $('.reviewSlide[data-review-id="' + reviewId + '"]') : null;
            if (slide && slide.length) {
                slide.find('.reviewCardStars').html(starHtml);
                slide.find('.reviewTextDiv').html(cleanMessage);
                slide.find('.btn-edit-review').attr('data-rating', rVal).data('rating', rVal);
            }
        }

        function addReviewSlideDirectly(rating, cleanMessage, reviewId) {
            var rVal = parseInt(rating, 10) || 5;
            var starHtml = '';
            for (var s = 1; s <= 5; s++) {
                starHtml += (s <= rVal) ? '★' : '<span style="color:#cbd5e1;">★</span>';
            }
            var currentDateStr = new Date().toLocaleDateString('en-GB', {day:'2-digit',month:'2-digit',year:'numeric'}).replace(/\//g, '.');

            var authorImgHtml = currentUserData.img
                ? '<img src="' + escapeHtml(currentUserData.img) + '" alt="' + escapeHtml(currentUserData.name) + '" style="width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0;border:1.5px solid #0088b0;" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">' +
                  '<div style="display:none;width:38px;height:38px;border-radius:50%;background:#e9f8ff;color:#006786;font-weight:700;font-size:13px;align-items:center;justify-content:center;flex-shrink:0;">' + escapeHtml(currentUserData.initials) + '</div>'
                : '<div style="width:38px;height:38px;border-radius:50%;background:#e9f8ff;color:#006786;font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">' + escapeHtml(currentUserData.initials) + '</div>';

            var editBtnHtml = '<button type="button" class="btn-edit-review" data-bs-toggle="modal" data-bs-target="#reviewmodal" data-id="' + (reviewId || '') + '" data-rating="' + rVal + '" onclick="if(window.openEditReviewModal){ window.openEditReviewModal(\'' + (reviewId || '') + '\', ' + rVal + ', this); return false; }" title="Edit Review"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg><span>Edit</span></button>';

            var slideHtml = '<div class="reviewSlide" data-review-id="' + (reviewId || '') + '"><div class="reviewCard"><div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;gap:10px;"><div style="display:flex;align-items:center;gap:10px;overflow:hidden;">' + authorImgHtml + '<div style="text-align:left;overflow:hidden;"><h6 style="margin:0;font-weight:700;font-size:14px;color:#0f172a;line-height:1.2;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;">' + escapeHtml(currentUserData.name) + '</h6><small style="color:#64748b;font-size:11px;display:block;">' + escapeHtml(currentUserData.role) + ' • ' + currentDateStr + '</small></div></div><div style="display:flex;align-items:center;gap:8px;flex-shrink:0;"><div class="reviewCardStars" style="color:#E8A339;font-size:14px;letter-spacing:1px;display:flex;align-items:center;">' + starHtml + '</div>' + editBtnHtml + '</div></div><div class="reviewTextDiv" style="text-align:left;color:#334155;font-size:14px;line-height:1.5;">' + cleanMessage + '</div></div></div>';

            if ($) {
                var $slider = $('.reviewSlick');
                if ($slider.length) {
                    if ($slider.hasClass('slick-initialized')) $slider.slick('unslick');
                    $slider.find('.reviewSlide').each(function () {
                        if ($(this).text().indexOf("haven't submitted any reviews") !== -1) $(this).remove();
                    });
                    $slider.prepend(slideHtml);
                    $slider.slick({
                        infinite: false, slidesToShow: 1, slidesToScroll: 1,
                        dots: false, arrows: true, accessibility: false,
                        prevArrow: '<button class="slide-arrow prev-arrow"></button>',
                        nextArrow: '<button class="slide-arrow next-arrow"></button>'
                    });
                    $slider.slick('slickGoTo', 0, true);
                    $slider.slick('setPosition');
                }
            }
        }

        // Submit review
        var submitBtn = document.getElementById('reviewSubmitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var ratingInput   = document.getElementById('reviewRatingInput');
                var reviewIdInput = document.getElementById('reviewIdInput');
                var editor        = document.getElementById('reviewMessageEditor');

                var rating      = ratingInput ? ratingInput.value : 5;
                var message     = editor ? editor.innerHTML.trim() : '';
                var textContent = editor ? editor.innerText.trim() : '';

                if (!textContent && (!message || message === '<br>' || message === '<div><br></div>')) {
                    showReviewToast('Please write your review before submitting.', true);
                    return;
                }

                var revId = reviewIdInput ? reviewIdInput.value : '';
                submitBtn.disabled    = true;
                submitBtn.textContent = revId ? 'Updating...' : 'Submitting...';

                var formData = new FormData();
                if (revId) formData.append('review_id', revId);
                var userIdInput = document.getElementById('reviewUserIdInput');
                if (userIdInput && userIdInput.value) formData.append('user_id', userIdInput.value);
                formData.append('rating',  rating);
                formData.append('message', message);

                var csrfTokenEl = document.getElementById('reviewCsrfInput');
                var csrfToken = (csrfTokenEl && csrfTokenEl.value) ? csrfTokenEl.value :
                                (document.querySelector('input[name="csrf_token"]') ? document.querySelector('input[name="csrf_token"]').value : '<?= \App\Helpers\Session::getCsrfToken() ?>');
                if (csrfToken) formData.append('csrf_token', csrfToken);

                var saveApiUrl = <?= json_encode(($pathPrefix ?? '../') . 'api/save_review.php') ?>;
                fetch(saveApiUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'Accept': 'application/json'
                    }
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        submitBtn.disabled    = false;
                        submitBtn.textContent = revId ? 'Update Review' : 'Submit Review';
                        if (data.status === 'success') {
                            window.closeReviewModal();
                            revId ? updateReviewSlideDirectly(revId, rating, message)
                                  : addReviewSlideDirectly(rating, message, data.review_id);
                            resetModalForNew();
                            showReviewToast(data.message || 'Saved successfully!', false);
                        } else {
                            showReviewToast(data.message || 'Failed to submit review.', true);
                        }
                    })
                    .catch(function (err) {
                        console.error('Save review error:', err);
                        submitBtn.disabled    = false;
                        submitBtn.textContent = revId ? 'Update Review' : 'Submit Review';
                        showReviewToast('Something went wrong. Please try again.', true);
                    });
            });
        }
    });
})();
</script>
