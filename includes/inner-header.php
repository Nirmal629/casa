<?php
require_once __DIR__ . '/../init.php';

$conn = getDbConnection();
$user = getCurrentUser($conn);

if (!isset($_SESSION['user_id']) || !$_SESSION['user_id']) {
    header('location:index.php');
    exit;
}

$currentInnerPage = basename($_SERVER['SCRIPT_NAME'] ?? '');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "header-links.php"; ?>
    <style>
        .main_header.innerpageHeader {
            background-color: #fff !important;
            border-bottom: 1px solid #e2e8f0 !important;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08) !important;
            padding: 0 !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 1000 !important;
        }
        .main_header.innerpageHeader .cust_container { padding: 0 20px !important; }
        .main_header.innerpageHeader .wraper {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            height: 60px !important;
            gap: 12px !important;
        }

        /* LOGO */
        .site-header-left { display: flex; align-items: center; gap: 10px; }
        .main_header .Logo_area img { height: 36px !important; width: auto !important; }

        /* NAV LINKS */
        .innerpagemenu_wrap {
            display: flex !important;
            align-items: center !important;
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
            gap: 4px !important;
        }
        .innerpagemenu_wrap .nav_link {
            display: inline-flex !important;
            align-items: center !important;
            padding: 6px 14px !important;
            font-size: 0.78rem !important;
            font-weight: 700 !important;
            color: #475569 !important;
            text-decoration: none !important;
            border-radius: 8px !important;
            border: 1px solid transparent !important;
            letter-spacing: 0.04em !important;
            transition: all 0.2s !important;
            background: transparent !important;
            text-transform: uppercase !important;
        }
        .innerpagemenu_wrap .nav_link:hover {
            color: #0f172a !important;
            background: #f1f5f9 !important;
            border-color: #e2e8f0 !important;
        }
        .innerpagemenu_wrap .nav_link.active {
            color: #0067b7 !important;
            background: #eff6ff !important;
            border-color: #bfdbfe !important;
        }

        /* PROFILE CHIP */
        .nav-profile-chip, .nav-profile-chip * {
            box-sizing: border-box !important;
        }
        .nav-profile-chip, .nav-profile-chip *:not(i) {
            font-family: 'Figtree', 'DM Sans', system-ui, -apple-system, sans-serif !important;
        }
        .nav-profile-chip { position: relative; }
        .nav-chip-trigger {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 12px 5px 5px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }
        .nav-chip-trigger:hover { border-color: #0067b7; background: #eff6ff; }

        .nav-chip-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            border: 2px solid #0067b7;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #0067b7, #6366f1);
            color: #fff;
            font-size: 0.85rem;
            font-weight: 800;
        }
        .nav-chip-avatar img {
            width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block;
        }
        .nav-chip-text { display: flex; flex-direction: column; }
        .nav-chip-name {
            font-size: 0.78rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
            white-space: nowrap;
        }
        .nav-chip-role {
            font-size: 0.58rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #0067b7;
            line-height: 1;
        }
        .nav-chip-role.host   { color: #7c3aed; }
        .nav-chip-role.trainer{ color: #059669; }

        .nav-chip-arrow {
            color: #94a3b8;
            font-size: 0.65rem;
            transition: transform 0.2s;
            margin-left: 2px;
        }
        .nav-profile-chip.open .nav-chip-arrow { transform: rotate(180deg); }

        /* Dropdown */
        .nav-chip-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            min-width: 210px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.2s cubic-bezier(0.16,1,0.3,1);
            z-index: 2000;
            overflow: hidden;
        }
        .nav-profile-chip.open .nav-chip-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Dropdown header */
        .ncd-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 16px 12px;
            border-bottom: 1px solid #1a3660;
        }
        .ncd-header-avatar {
            width: 44px; height: 44px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #38bdf8;
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            color: #fff; font-size: 1.1rem; font-weight: 800;
        }
        .ncd-header-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }
        .ncd-header-info { flex: 1; min-width: 0; }
        .ncd-name { font-size: 0.85rem; font-weight: 700; color: #f1f5f9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ncd-badge {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.55rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            background: rgba(56,189,248,0.1);
            border: 1px solid rgba(56,189,248,0.25);
            color: #38bdf8;
        }
        .ncd-badge.host    { background: rgba(167,139,250,0.1); border-color: rgba(167,139,250,0.25); color: #a78bfa; }
        .ncd-badge.trainer { background: rgba(52,211,153,0.1); border-color: rgba(52,211,153,0.25); color: #34d399; }

        /* Dropdown links */
        .ncd-links { padding: 6px; display: flex; flex-direction: column; gap: 2px; }
        .ncd-links a {
            display: grid;
            grid-template-columns: 20px 1fr;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #475569;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
            white-space: nowrap;
        }
        .ncd-links a:hover { background: #f1f5f9; color: #0f172a; text-decoration: none; }
        .ncd-links a i {
            width: 20px;
            text-align: center;
            font-size: 0.82rem;
            color: #94a3b8;
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .ncd-links a:hover i { color: #0067b7; }
        .ncd-links .ncd-logout { color: #ef4444 !important; }
        .ncd-links .ncd-logout i { color: #ef4444 !important; }
        .ncd-links .ncd-logout:hover { background: rgba(239,68,68,0.06) !important; color: #ef4444 !important; }
        .ncd-divider { height: 1px; background: #e2e8f0; margin: 4px 2px; }

        @media(max-width:768px){
            .innerpagemenu_wrap { display: none !important; }
            .nav-chip-name { max-width: 80px; overflow: hidden; text-overflow: ellipsis; }
        }
    </style>
</head>

<body>
    <!------Main Header------->
    <section class="main_header innerpageHeader" id="main_Header">
        <div class="cust_container">
            <div class="wraper">

                <!-- LEFT: Logo -->
                <div class="site-header-left">
                    <a href="index.php">
                        <figure class="Logo_area m-0">
                            <img src="assets/images/logo/Final-Logo.png" class="img-fluid" alt="logo" />
                        </figure>
                    </a>
                </div>

                <!-- CENTER: Nav Links -->
                <ul class="innerpagemenu_wrap">
                    <?php if (strcasecmp($_SESSION['usertype'], 'Player') === 0): ?>
                        <li><a href="player-hub.php" class="nav_link btn <?php echo $currentInnerPage === 'player-hub.php' ? 'active' : ''; ?>">
                            <span>The Player Hub</span></a></li>
                    <?php endif; ?>
                    <?php if (strcasecmp($_SESSION['usertype'], 'Host') === 0 || strcasecmp($_SESSION['usertype'], 'Trainer') === 0): ?>
                        <li><a href="host-dashboard.php" class="nav_link btn <?php echo $currentInnerPage === 'host-dashboard.php' ? 'active' : ''; ?>">
                            <span>Dashboard</span></a></li>
                    <?php endif; ?>
                    <li><a href="product-listing.php" class="nav_link btn <?php echo $currentInnerPage === 'product-listing.php' ? 'active' : ''; ?>">
                        <span>Casa Store</span></a></li>
                </ul>

                <!-- RIGHT: Profile Chip -->
                <?php
                    $userDisplayName = htmlspecialchars($_SESSION['name'] ?? 'User');
                    $userType        = $_SESSION['usertype'] ?? 'Player';
                    $userInitial     = strtoupper(substr($userDisplayName, 0, 1));
                    $profileImg      = !empty($user['PROFILE_IMAGE']) ? 'profile_img/' . htmlspecialchars($user['PROFILE_IMAGE']) : '';
                    $chipRoleClass   = strcasecmp($userType, 'Host') === 0 ? 'host' : (strcasecmp($userType, 'Trainer') === 0 ? 'trainer' : '');
                ?>
                <div class="nav-profile-chip" id="navProfileChip">
                    <div class="nav-chip-trigger" id="navChipTrigger">
                        <div class="nav-chip-avatar">
                            <?php if ($profileImg): ?>
                                <img src="<?= $profileImg ?>" alt="avatar" onerror="this.parentElement.innerHTML='<?= $userInitial ?>'">
                            <?php else: ?>
                                <?= $userInitial ?>
                            <?php endif; ?>
                        </div>
                        <div class="nav-chip-text">
                            <span class="nav-chip-name"><?= $userDisplayName ?></span>
                            <span class="nav-chip-role <?= $chipRoleClass ?>"><?= htmlspecialchars($userType) ?></span>
                        </div>
                        <i class="fa-solid fa-chevron-down nav-chip-arrow"></i>
                    </div>

                    <div class="nav-chip-dropdown" id="navChipDropdown">
                        <div class="ncd-links">
                            <a href="player-profile.php">
                                <i class="fa-regular fa-user"></i> My Profile
                            </a>
                            <a href="my-order.php">
                                <i class="fa-solid fa-bag-shopping"></i> My Orders
                            </a>
                            <?php if (strcasecmp($userType, 'Player') === 0): ?>
                            <a href="player-hub.php">
                                <i class="fa-solid fa-house"></i> Player Hub
                            </a>
                            <?php elseif (strcasecmp($userType, 'Host') === 0 || strcasecmp($userType, 'Trainer') === 0): ?>
                            <a href="host-dashboard.php">
                                <i class="fa-solid fa-gauge"></i> Host Dashboard
                            </a>
                            <?php endif; ?>
                            <div class="ncd-divider"></div>
                            <a href="logout.php" class="ncd-logout">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ── Profile Chip Toggle ──
            var chip    = document.getElementById('navProfileChip');
            var trigger = document.getElementById('navChipTrigger');
            if (chip && trigger) {
                trigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    chip.classList.toggle('open');
                });
                document.addEventListener('click', function (e) {
                    if (!chip.contains(e.target)) chip.classList.remove('open');
                });
            }
        });
    </script>

    <div id="profileModal" class="editProfileModal d-none">
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-body">
                    <!-- Profile Picture -->
                    <div class="profile-pic-section">
                        <div class="profile-pic-container">
                            <div class="profile-pic">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <div class="pic-actions">
                            <button class="pic-btn" title="Edit Picture">
                                <i class="fas fa-pencil"></i>
                            </button>
                            <button class="pic-btn delete" title="Delete Picture">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Form Fields -->
                    <form id="profileForm">
                        <!-- Name (Non-editable) -->
                        <div class="form-group">
                            <label class="form-label">Name</label>
                            <input type="text" class="field-input-readonly" value="John David Smith" readonly>
                        </div>

                        <!-- Email (Non-editable) + Permission -->
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <div style="display: flex; gap: 12px; align-items: flex-start;">
                                <input type="email" class="field-input-readonly" style="flex: 1;"
                                    value="john.smith@example.com" readonly>
                                <div class="permission-group" style="margin-top: 3px; gap: 12px;">
                                    <div class="radio-wrapper">
                                        <input type="radio" id="emailYes" name="emailPermission" value="yes" checked>
                                        <label for="emailYes">Yes</label>
                                    </div>
                                    <div class="radio-wrapper">
                                        <input type="radio" id="emailNo" name="emailPermission" value="no">
                                        <label for="emailNo">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact (Non-editable) + Permissions -->
                        <div class="form-group">
                            <label class="form-label">Contact</label>
                            <input type="text" class="field-input-readonly" value="+1 (555) 123-4567" readonly
                                style="margin-bottom: 12px;">

                            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                <div class="permission-group">
                                    <div class="radio-wrapper">
                                        <input type="radio" id="callYes" name="callPermission" value="yes" checked>
                                        <label for="callYes">Call</label>
                                    </div>
                                    <div class="radio-wrapper">
                                        <input type="radio" id="callNo" name="callPermission" value="no">
                                        <label for="callNo">No Call</label>
                                    </div>
                                </div>

                                <div class="permission-group">
                                    <div class="radio-wrapper">
                                        <input type="radio" id="textYes" name="textPermission" value="yes" checked>
                                        <label for="textYes">Text</label>
                                    </div>
                                    <div class="radio-wrapper">
                                        <input type="radio" id="textNo" name="textPermission" value="no">
                                        <label for="textNo">No Text</label>
                                    </div>
                                </div>

                                <div class="permission-group">
                                    <div class="radio-wrapper">
                                        <input type="radio" id="chatYes" name="chatPermission" value="yes" checked>
                                        <label for="chatYes">Chat</label>
                                    </div>
                                    <div class="radio-wrapper">
                                        <input type="radio" id="chatNo" name="chatPermission" value="no">
                                        <label for="chatNo">No Chat</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DOB (Editable) -->
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <div class="editable-field">
                                <input type="date" class="field-input" id="dobInput" disabled value="1990-05-15">
                                <button type="button" class="edit-btn" id="dobEditBtn" onclick="toggleEdit('dob')">
                                    <i class="fas fa-pencil"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Gender (Non-editable) -->
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <input type="text" class="field-input-readonly" value="Male" readonly>
                        </div>

                        <!-- Level (Editable) -->
                        <div class="form-group">
                            <label class="form-label">Level</label>
                            <div class="editable-field">
                                <select class="field-select" id="levelInput" disabled>
                                    <option>Select Level</option>
                                    <option selected>Intermediate</option>
                                    <option>Beginner</option>
                                    <option>Advanced</option>
                                    <option>Expert</option>
                                </select>
                                <button type="button" class="edit-btn" id="levelEditBtn" onclick="toggleEdit('level')">
                                    <i class="fas fa-pencil"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Location (Editable) -->
                        <div class="form-group">
                            <label class="form-label">Location</label>
                            <div class="select-group">
                                <div class="editable-field">
                                    <select class="field-select" id="countryInput" disabled>
                                        <option>Country</option>
                                        <option selected>Canada</option>
                                        <option>United States</option>
                                        <option>United Kingdom</option>
                                        <option>Australia</option>
                                    </select>
                                    <button type="button" class="edit-btn" id="countryEditBtn"
                                        onclick="toggleEdit('country')">
                                        <i class="fas fa-pencil"></i>
                                    </button>
                                </div>

                                <div class="editable-field">
                                    <select class="field-select" id="provinceInput" disabled>
                                        <option>Province</option>
                                        <option selected>Ontario</option>
                                        <option>British Columbia</option>
                                        <option>Alberta</option>
                                        <option>Quebec</option>
                                    </select>
                                    <button type="button" class="edit-btn" id="provinceEditBtn"
                                        onclick="toggleEdit('province')">
                                        <i class="fas fa-pencil"></i>
                                    </button>
                                </div>

                                <div class="editable-field">
                                    <select class="field-select" id="cityInput" disabled>
                                        <option>City</option>
                                        <option selected>Toronto</option>
                                        <option>Vancouver</option>
                                        <option>Calgary</option>
                                        <option>Montreal</option>
                                    </select>
                                    <button type="button" class="edit-btn" id="cityEditBtn"
                                        onclick="toggleEdit('city')">
                                        <i class="fas fa-pencil"></i>
                                    </button>
                                </div>

                                <div class="editable-field">
                                    <select class="field-select" id="areaInput" disabled>
                                        <option>Area</option>
                                        <option selected>Downtown</option>
                                        <option>Midtown</option>
                                        <option>Uptown</option>
                                        <option>Suburbs</option>
                                    </select>
                                    <button type="button" class="edit-btn" id="areaEditBtn"
                                        onclick="toggleEdit('area')">
                                        <i class="fas fa-pencil"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="handleCancel()">Cancel</button>
                    <button class="btn btn-primary" onclick="handleSubmit()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>