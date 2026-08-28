# CASA Games - Project Structure & Code Documentation

> **CASA Games** — একটি Badminton/Sports ম্যানেজমেন্ট প্ল্যাটফর্ম। এখানে Host, Trainer, Player — তিন ধরনের ইউজারের জন্য আলাদা ড্যাশবোর্ড ও ফিচার আছে।

---

## 📁 Root Directory Tree

```
📦 staging/
├── .env.local
├── .env.prod
├── .gitignore
├── .user.ini
├── 404.shtml
├── index.php                  # Homepage — হিরো ব্যানার, টুর্নামেন্ট, স্টোর, গ্যালারি, টেস্টিমোনিয়াল
├── init.php                   # Project Autoloader — সব হেলপার ও কনফিগ লোড করে
├── dbConnection.php           # MySQLi সংযোগ (লিগ্যাসি)
├── dbConnection_PDO.php       # PDO সংযোগ (লিগ্যাসি)
├── logout.php                 # সেশন ডেস্ট্রয় + redirect to index.php
├── home.html                  # স্ট্যাটিক HTML (সম্ভবত পুরনো ব্যাকআপ)
├── phpinfo.php                # PHP info (ডিবাগিং)
├── clear-opcache.php          # OPcache ক্লিয়ার
├── layout-styles.css          # CSS ফাইল
├── error_log                  # এরর লগ ফাইল
│
├── about-us.php               # About Us পেজ
├── contact-us.php             # Contact Form পেজ
├── privacy-policy.php         # প্রাইভেসি পলিসি
├── termsCondition.php         # টার্মস ও কন্ডিশন
├── CancellationPolicy.php     # ক্যানসেলেশন পলিসি
├── gallery.php                # গ্যালারি সেকশন
├── gallery-page.php           # ফুল গ্যালারি পেজ
├── poster.php                 # ইভেন্ট পোস্টার সেকশন
├── discover-games.php         # গেমস/ইভেন্ট ডিসকভার
├── notification.php           # নোটিফিকেশন পেজ
│
├── organisers.php             # Organiser landing page
├── players.php                # Players landing page
├── casa-trainers.php          # Trainers landing page
├── casa-clubs.php             # Clubs landing page
│
├──── Auth Related ────
├── includes/
│   ├── Auth/
│   │   ├── login.php          # লগইন ফর্ম + PHP login logic + CSRF
│   │   └── resister.php       # রেজিস্ট্রেশন মডেল ফর্ম (HTML/CSS/JS)
│   ├── header.php             # সাইটের মূল হেডার
│   ├── inner-header.php       # ভিতরের পেজের জন্য হেডার
│   ├── store-header.php       # স্টোর পেজের জন্য হেডার
│   ├── header-links.php       # <head> এর meta links
│   ├── footer.php             # সাইটের ফুটার
│   ├── footer-links.php       # ফুটারের স্ক্রিপ্ট লিংক
│
├──── Player Dashboard ────
├── player-hub.php             # প্লেয়ার হাব (মূল ড্যাশবোর্ড)
├── player-dashboard.php       # প্লেয়ার ড্যাশবোর্ড
├── player-Complete-game.php   # প্লেয়ার কমপ্লিটেড গেম লিস্ট
├── player-Upcoming-game.php   # প্লেয়ার আপকামিং গেম লিস্ট
├── player-match.php           # প্লেয়ার ম্যাচ ডিটেইলস
├── player-payment-list.php    # প্লেয়ারের পেমেন্ট লিস্ট
├── player-monthly-subscription.php # মাসিক সাবস্ক্রিপশন
├── player-profile.php         # প্লেয়ার প্রোফাইল
├── player-rating.php          # প্লেয়ার রেটিং
├── player-ratingbk.php        # রেটিং ব্যাকআপ
│
├──── Host Dashboard ────
├── host-dashboard.php         # হোস্ট ড্যাশবোর্ড
├── host-creat-game.php        # নতুন গেম/ইভেন্ট তৈরি
├── host-scheduled-game.php    # শিডিউল করা গেম লিস্ট
├── host-complete-game.php     # কমপ্লিটেড গেম লিস্ট
├── host-payment-list.php      # হোস্ট পেমেন্ট লিস্ট
├── host-player-stats.php      # প্লেয়ার স্ট্যাটস (হোস্ট ভিউ)
├── host-rating.php            # হোস্ট রেটিং
├── host-ratingbkp.php         # রেটিং ব্যাকআপ
│
├──── Trainer Dashboard ────
├── train-dashboard.php        # ট্রেইনার ড্যাশবোর্ড
├── train-game-create.php      # নতুন ট্রেনিং গেম তৈরি
├── train-scheduled-game.php   # শিডিউল করা ট্রেনিং
├── train-complete-game.php    # কমপ্লিটেড ট্রেনিং
├── train-payment-list.php     # ট্রেইনার পেমেন্ট
│
├──── Store/Shop ────
├── accessories-shop.php       # এক্সেসরিজ শপ
├── product-listing.php        # প্রোডাক্ট লিস্টিং
├── product-details-page.php   # প্রোডাক্ট ডিটেইলস
├── addToCart.php              # কার্টে যোগ করুন
├── check-out.php              # চেকআউট পেজ
├── my-order.php               # আমার অর্ডার
├── my.php                     # ইউজার অর্ডার/প্রোফাইল
├── subscription-list.php      # সাবস্ক্রিপশন লিস্ট
│
├──── Tournament ────
├── tournament-details.php     # টুর্নামেন্ট ডিটেইলস
├── tournament/
│   ├── index.php              # টুর্নামেন্ট মডিউল হোম
│   ├── court-dashboard.php    # কোর্ট ড্যাশবোর্ড (স্কোর)
│   ├── badminton-scorer.php   # ব্যাডমিন্টন স্কোরার
│   ├── battle-tournament.php  # ব্যাটল টুর্নামেন্ট
│   ├── battle-tournament_new.php # নতুন ব্যাটল টুর্নামেন্ট
│   ├── spin-wheel.php         # স্পিন হুইল (গ্রুপিং)
│   ├── api-match-score.php    # ম্যাচ স্কোর API
│   ├── error_log
│   ├── assets/                # টুর্নামেন্ট এসেটস
│   └── includes/
│       ├── header.php
│       ├── footer.php
│       ├── scorer-header.php
│       └── scorer-footer.php
│
├──── Helpers ────
├── helpers/
│   ├── session.php            # Session & Auth helpers
│   ├── helpers.php            # Common helper functions
│   ├── validators.php         # Input validation (Validator class)
│   └── mail.php               # PHPMailer wrapper class
│
├──── Config ────
├── config/
│   ├── env.php                # .env ফাইল পার্সার
│   └── database.php           # Centralized DB (MySQLi + PDO + Activity Log)
│
├──── API ────
├── api/
│   ├── helpers/
│   │   ├── gameAutomation.php     # গেম অটোমেশন (ক্রন)
│   │   └── gameAutoConfirm.php    # গেম অটো কনফার্ম
│   ├── add_user.php               # ইউজার যোগ করা
│   ├── register.php               # রেজিস্ট্রেশন API
│   ├── join_event.php             # ইভেন্ট জয়েন
│   ├── join_event_default.php     # ডিফল্ট জয়েন
│   ├── cancel_event.php           # ইভেন্ট ক্যানসেল
│   ├── cancel_event_default.php   # ডিফল্ট ক্যানসেল
│   ├── copy_event.php             # ইভেন্ট কপি
│   ├── create_event_sub.php       # সাব ইভেন্ট তৈরি
│   ├── update_event.php           # ইভেন্ট আপডেট
│   ├── delete_event.php           # ইভেন্ট ডিলিট
│   ├── rollback_event.php         # ইভেন্ট রোলব্যাক
│   ├── new-game-host.php          # নতুন গেম হোস্ট করা
│   ├── payment_action.php         # পেমেন্ট অ্যাকশন
│   ├── player_payment.php         # প্লেয়ার পেমেন্ট
│   ├── fetch_payment.php          # পেমেন্ট ফেচ
│   ├── filter_host_payment.php    # হোস্ট পেমেন্ট ফিল্টার
│   ├── filter_schedule.php        # শিডিউল ফিল্টার
│   ├── com_filter_schedule.php    # কমপ্লিটেড ফিল্টার
│   ├── play_filter_schedule.php   # প্লেয়ার ফিল্টার
│   ├── player_filter_complete.php # প্লেয়ার কমপ্লিটেড ফিল্টার
│   ├── pay_filter_complete.php    # পেমেন্ট কমপ্লিটেড ফিল্টার
│   ├── save_payment.php           # পেমেন্ট সেভ
│   ├── save_confirm.php           # কনফার্মেশন সেভ
│   ├── save_rating.php            # রেটিং সেভ (প্লেয়ার)
│   ├── save_rating_by_host.php    # রেটিং সেভ (হোস্ট)
│   ├── save_invitation.php        # ইনভাইটেশন সেভ
│   ├── save_registration.php      # রেজিস্ট্রেশন সেভ
│   ├── update_inviteJoin.php      # ইনভাইট জয়েন আপডেট
│   ├── update_paymentHis.php      # পেমেন্ট হিস্টোরি আপডেট
│   ├── update_player_premium.php  # প্রিমিয়াম আপডেট
│   ├── update_price.php           # প্রাইস আপডেট
│   ├── update_user.php            # ইউজার আপডেট
│   ├── update-automation.php      # অটোমেশন আপডেট
│   ├── updateAuto.php             # অটো আপডেট
│   ├── upload_profile_image.php   # প্রোফাইল ইমেজ আপলোড
│   ├── get_filtered_players.php   # ফিল্টার করা প্লেয়ার লিস্ট
│   ├── get_player_info.php        # প্লেয়ার ইনফো
│   ├── get_player_info_host.php   # হোস্টের জন্য প্লেয়ার ইনফো
│   ├── get_player_cost.php        # প্লেয়ার কস্ট
│   ├── get-num-player-joined.php  # জয়েন করা প্লেয়ার সংখ্যা
│   ├── get_rating_table.php       # রেটিং টেবিল
│   ├── get_ranking_range.php      # র্যাঙ্কিং রেঞ্জ
│   ├── view_player.php            # প্লেয়ার ভিউ
│   ├── view_player_pay.php        # প্লেয়ার পেমেন্ট ভিউ
│   ├── view_player_paylist.php    # পেমেন্ট লিস্ট ভিউ
│   ├── view_player_paymentHis.php # পেমেন্ট হিস্টোরি ভিউ
│   ├── view_joined_all.php        # জয়েনড অল ভিউ
│   ├── view_joined_all_default.php # জয়েনড অল ডিফল্ট
│   ├── view_joined_invited.php    # ইনভাইটেড জয়েন ভিউ
│   ├── view_game_player.php       # গেম প্লেয়ার ভিউ
│   ├── search_result.php          # সার্চ রেজাল্ট
│   ├── recalculate_event_cost.php # ইভেন্ট কস্ট পুনঃগণনা
│   ├── sync_home_clubs.php        # ক্লাব সিঙ্ক
│   └── same_paymentByhost.php     # একই হোস্টের পেমেন্ট
│
├──── Admin Panel ────
├── admin/
│   ├── index.php                  # অ্যাডমিন লগিন
│   ├── dashboard.php              # অ্যাডমিন ড্যাশবোর্ড
│   ├── header.php                 # অ্যাডমিন হেডার
│   ├── footer.php                 # অ্যাডমিন ফুটার
│   ├── sidebar.php                # অ্যাডমিন সাইডবার
│   ├── logout.php                 # অ্যাডমিন লগআউট
│   ├── dbConnection.php           # অ্যাডমিন ডিবি সংযোগ
│   │
│   ├──── User Management ────
│   ├── manage_user.php            # ইউজার ম্যানেজমেন্ট
│   ├── ###add_user.php            # ইউজার যোগ (অসম্পূর্ণ/লিগ্যাসি)
│   ├── ###edit_user.php           # ইউজার এডিট
│   ├── ###view_user.php           # ইউজার ভিউ
│   ├── add_user_form_element.php  # ইউজার ফর্ম এলিমেন্ট
│   │
│   ├──── Event/Tournament ────
│   ├── add_event.php              # ইভেন্ট যোগ
│   ├── edit_registration.php      # রেজিস্ট্রেশন এডিট
│   ├── edit_tournament.php        # টুর্নামেন্ট এডিট
│   ├── enrolled_tournaments.php   # এনরোল করা টুর্নামেন্ট
│   ├── tournaments_list.php       # টুর্নামেন্ট লিস্ট
│   ├── tournament_logindetails.php # টুর্নামেন্ট লগিন ডিটেলস
│   ├── event_category.php         # ইভেন্ট ক্যাটাগরি
│   ├── event_description.php      # ইভেন্ট বর্ণনা
│   ├── event_venue.php            # ইভেন্ট ভেন্যু
│   ├── registration_message.php   # রেজিস্ট্রেশন মেসেজ
│   │
│   ├──── Product/Store ────
│   ├── manage_products.php        # প্রোডাক্ট ম্যানেজ
│   ├── edit_products.php          # প্রোডাক্ট এডিট
│   ├── ###add_product.php         # প্রোডাক্ট যোগ
│   ├── manage_product_type.php    # প্রোডাক্ট টাইপ
│   ├── manage_order.php           # অর্ডার ম্যানেজ
│   ├── ###manual_order.php        # ম্যানুয়াল অর্ডার
│   │
│   ├──── Content Management ────
│   ├── aboutus_image.php          # About Us ইমেজ
│   ├── aboutus_poster.php         # About Us পোস্টার
│   ├── aboutus_review.php         # About Us রিভিউ
│   ├── ###aboutus_review_add.php  # রিভিউ যোগ
│   ├── ###aboutus_review_edit.php # রিভিউ এডিট
│   ├── ###aboutus_review_view.php # রিভিউ ভিউ
│   ├── contact_list.php           # কন্টাক্ট লিস্ট
│   ├── ####contactus_add.php      # কন্টাক্ট যোগ
│   ├── ####contactus_delete.php   # কন্টাক্ট ডিলিট
│   ├── herobanner_list.php        # হিরো ব্যানার লিস্ট
│   ├── media_list.php             # মিডিয়া লিস্ট
│   ├── manage_adds.php            # অ্যাড ম্যানেজ
│   ├── manage_department.php      # ডিপার্টমেন্ট ম্যানেজ
│   ├── admin_host.php             # হোস্ট ম্যানেজ
│   │
│   └── api/                       # অ্যাডমিন API
│       ├── add_tournament.php
│       ├── manage_tournament.php
│       ├── add_user.php
│       ├── update_user.php
│       ├── delete_user.php
│       ├── delete_ads.php
│       ├── delete_product.php
│       ├── sync_player.php
│       └── toggleStatus.php
│
├──── PHPMailer ────
├── PHPMailer/                    # PHPMailer লাইব্রেরি (ইমেইল)
│   ├── src/
│   ├── language/
│   └── ...
│
├──── POS (Point of Sale) ────
├── pos/
│   └── admin/                    # CodeIgniter 4 POS অ্যাপ
│       ├── app/
│       ├── public/
│       ├── system/
│       ├── writable/
│       ├── .env
│       ├── composer.json
│       └── spark
│
├──── Static Assets ────
├── assets/
│   ├── css/
│   │   ├── style.css             # মূল স্টাইলশিট
│   │   └── tournament.css        # টুর্নামেন্ট স্টাইল
│   ├── js/
│   │   ├── main.js               # জাভাস্ক্রিপ্ট
│   │   └── script.js             # স্ক্রিপ্ট
│   └── images/                   # ইমেজ ফাইল
│       ├── logo/
│       ├── gallery/
│       ├── game/
│       ├── poster/
│       ├── product/
│       ├── Icons/
│       └── ...
│
├──── Profile Images ────
├── profile_img/                  # আপলোড করা প্রোফাইল ইমেজ
│
└──── Other Files ────
├── anurag_gupta/                 # ব্যাকআপ/রেফারেন্স ফাইল (সম্ভবত)
├── delete_event.php              # ইভেন্ট ডিলিট (রুট)
└── tk.php                        # (অজানা/টেস্ট ফাইল)
```

---

## 📄 ফাইলভিত্তিক কোড বর্ণনা

### 🔷 Root Level

| ফাইল | কী করে |
|---|---|
| **`index.php`** | **হোমপেজ** — সেশন চেক করে (লগইন থাকলে Host/Player ড্যাশবোর্ডে রিডাইরেক্ট), হিরো ব্যানার (ডাটাবেজ থেকে), টুর্নামেন্ট কার্ড (PDO), স্টোর প্রোডাক্ট, গ্যালারি, About Us স্ট্যাটস, ক্যাটাগরি, টেস্টিমোনিয়াল স্লাইডার, কন্টাক্ট ফর্ম — সবকিছু এক পেজে দেখায়। |
| **`init.php`** | **অটোলোডার** — `config/env.php`, `config/database.php`, `helpers/session.php`, `helpers/helpers.php`, `helpers/validators.php` — সবগুলো লোড করে। এনভায়রনমেন্ট অনুযায়ী error reporting সেট করে। |
| **`dbConnection.php`** | **MySQLi লিগ্যাসি কানেকশন** — `env()` ফাংশন থেকে DB ক্রেডেনশিয়াল নিয়ে `$conn` অবজেক্ট তৈরি করে। |
| **`dbConnection_PDO.php`** | **PDO লিগ্যাসি কানেকশন** — DB ক্রেডেনশিয়াল নিয়ে `$pdo` অবজেক্ট তৈরি করে (prepare statement সাপোর্ট সহ)। |
| **`logout.php`** | সেশন ডেস্ট্রয় করে `index.php`-তে রিডাইরেক্ট করে। |
| **`clear-opcache.php`** | PHP OPcache ক্লিয়ার করে (ডিবাগিং টুল)। |

### 🔷 Authentication (`includes/Auth/`)

| ফাইল | কী করে |
|---|---|
| **`login.php`** | **লগইন সিস্টেম** — CSRF টোকেন জেনারেট, ইউজারনেম/পাসওয়ার্ড ভেরিফিকেশন, সফল লগইনে `$_SESSION['user_id']` ও `$_SESSION['usertype']` সেট, `ca_player_logs`-এ "Successful login" এন্ট্রি লগ করে। |
| **`resister.php`** | **রেজিস্ট্রেশন মডেল** — HTML মডেল ফর্ম আকারে, CSS স্টাইলিং, পপ-আপ মডেল আকারে কাজ করে। |

### 🔷 Helpers

| ফাইল | ফাংশন/ক্লাস | কী করে |
|---|---|---|
| **`session.php`** | `startSession()` | সেশন শুরু (if not already) |
| | `getCurrentUser($conn)` | বর্তমান ইউজারের ডাটা রিটার্ন করে `ca_users` থেকে |
| | `requireLogin($redirect)` | লগইন না থাকলে redirect করে |
| | `requireUserType($types, $redirect)` | নির্দিষ্ট ইউজার টাইপ চেক করে |
| | `getCurrentUserId()` | বর্তমান ইউজার ID রিটার্ন করে |
| | `getCurrentUserType()` | বর্তমান ইউজার টাইপ রিটার্ন করে |
| **`helpers.php`** | `checkLogin()` | `bool` রিটার্ন করে — সেশন চেক |
| | `getCurrentPage()` | বর্তমান পেজের নাম রিটার্ন করে |
| | `dbQuery()`, `dbFetchRow()` | লিগ্যাসি DB হেল্পার |
| **`validators.php`** | `Validator` class | ফর্ম ভ্যালিডেশন — `required()`, `email()`, `minLength()`, `maxLength()`, `matches()`, `isValid()` মেথড |
| **`mail.php`** | `MailHelper` class | PHPMailer র‍্যাপার — SMTP সেটআপ, `send()` মেথড ইমেইল পাঠায় |

### 🔷 Config

| ফাইল | কী করে |
|---|---|
| **`env.php`** | `.env` ফাইল পার্স করে — ফাইল থেকে লাইন বাই লাইন পড়ে `$_ENV` এবং `putenv()`-এ সেট করে। কোটেড ভ্যালু স্ট্রিপ করে। |
| **`database.php`** | **কেন্দ্রীয় DB কানেকশন** — MySQLi (`$conn`) ও PDO (`$pdo`) — দুটোই প্রদান করে। `logPlayerActivity()` ফাংশন ইউজার অ্যাক্টিভিটি লগ করে। |

### 🔷 Player Dashboard

| ফাইল | কী করে |
|---|---|
| **`player-hub.php`** | প্লেয়ারের মূল হাব — সব গেম, স্ট্যাটস, ওভারভিউ |
| **`player-dashboard.php`** | প্লেয়ার ড্যাশবোর্ড — ব্যক্তিগত ড্যাশবোর্ড |
| **`player-Complete-game.php`** | শেষ করা গেমের তালিকা দেখে |
| **`player-Upcoming-game.php`** | আসন্ন গেমের তালিকা |
| **`player-match.php`** | ম্যাচ ডিটেইলস |
| **`player-payment-list.php`** | পেমেন্ট লিস্ট ও হিস্টোরি |
| **`player-monthly-subscription.php`** | মাসিক সাবস্ক্রিপশন প্ল্যান ও পেমেন্ট |
| **`player-profile.php`** | প্রোফাইল এডিট/ভিউ |
| **`player-rating.php`** | রেটিং ও রিভিউ সিস্টেম |

### 🔷 Host Dashboard

| ফাইল | কী করে |
|---|---|
| **`host-dashboard.php`** | হোস্টের মূল ড্যাশবোর্ড |
| **`host-creat-game.php`** | নতুন গেম/ইভেন্ট তৈরি ফর্ম |
| **`host-scheduled-game.php`** | শিডিউল করা ইভেন্ট লিস্ট ও ম্যানেজ |
| **`host-complete-game.php`** | শেষ করা ইভেন্ট লিস্ট |
| **`host-payment-list.php`** | পেমেন্ট সংগ্রহ ও ট্র্যাকিং |
| **`host-player-stats.php`** | প্লেয়ারদের স্ট্যাটস দেখা |
| **`host-rating.php`** | রেটিং ও রিভিউ |

### 🔷 Trainer Dashboard

| ফাইল | কী করে |
|---|---|
| **`train-dashboard.php`** | ট্রেইনারের ড্যাশবোর্ড |
| **`train-game-create.php`** | প্রশিক্ষণ সেশন তৈরি |
| **`train-scheduled-game.php`** | শিডিউল করা প্রশিক্ষণ |
| **`train-complete-game.php`** | শেষ করা প্রশিক্ষণ |
| **`train-payment-list.php`** | পেমেন্ট লিস্ট |

### 🔷 Store Module

| ফাইল | কী করে |
|---|---|
| **`accessories-shop.php`** | এক্সেসরিজ শপ পেজ |
| **`product-listing.php`** | সব প্রোডাক্ট লিস্টিং |
| **`product-details-page.php`** | প্রোডাক্ট ডিটেইলস পেজ |
| **`addToCart.php`** | সেশন-ভিত্তিক কার্টে প্রোডাক্ট যোগ করে |
| **`check-out.php`** | চেকআউট প্রসেস (PHPMailer ইমেইল) |
| **`my-order.php`** | ইউজারের অর্ডার লিস্ট |

### 🔷 Tournament Module

| ফাইল | কী করে |
|---|---|
| **`tournament-details.php`** | টুর্নামেন্ট ডিটেইলস পেজ |
| **`tournament/court-dashboard.php`** | কোর্ট ড্যাশবোর্ড — লাইভ স্কোর ম্যানেজ |
| **`tournament/badminton-scorer.php`** | ব্যাডমিন্টন স্কোরকার্ড ইন্টারফেস |
| **`tournament/battle-tournament.php`** | ব্যাটল টুর্নামেন্ট সিস্টেম (ডাবল এলিমিনেশন) |
| **`tournament/spin-wheel.php`** | গ্রুপিং/ড্র-এর জন্য স্পিন হুইল |

### 🔷 Admin Panel

| ফাইল | কী করে |
|---|---|
| **`admin/dashboard.php`** | অ্যাডমিন ড্যাশবোর্ড |
| **`admin/manage_user.php`** | ইউজার ম্যানেজমেন্ট (CRUD) |
| **`admin/add_event.php`** | ইভেন্ট/টুর্নামেন্ট অ্যাড/এডিট |
| **`admin/edit_registration.php`** | রেজিস্ট্রেশন ফি, পেমেন্ট স্ট্যাটাস ম্যানেজ |
| **`admin/edit_tournament.php`** | টুর্নামেন্ট ডিটেইলস এডিট |
| **`admin/manage_products.php`** | প্রোডাক্ট ম্যানেজমেন্ট |
| **`admin/manage_order.php`** | অর্ডার ম্যানেজমেন্ট |
| **`admin/herobanner_list.php`** | হোমপেজ হিরো ব্যানার ম্যানেজ |
| **`admin/contact_list.php`** | কন্টাক্ট ফর্ম সাবমিশন লিস্ট |
| **`admin/aboutus_review.php`** | রিভিউ ম্যানেজমেন্ট |

### 🔷 API Files (AJAX/JSON)

| ফাইল | কী করে |
|---|---|
| **`api/join_event.php`** | প্লেয়ার ইভেন্ট জয়েন করে (AJAX) |
| **`api/cancel_event.php`** | ইভেন্ট ক্যানসেল করে |
| **`api/save_payment.php`** | পেমেন্ট সেভ/আপডেট করে (AJAX) |
| **`api/save_rating.php`** | রেটিং সেভ করে (AJAX) |
| **`api/upload_profile_image.php`** | প্রোফাইল ছবি আপলোড (AJAX) |
| **`api/save_registration.php`** | রেজিস্ট্রেশন ফর্ম ডাটা সেভ |
| **`api/search_result.php`** | সার্চ রেজাল্ট রিটার্ন (AJAX) |
| **`api/get_filtered_players.php`** | ফিল্টার করা প্লেয়ার লিস্ট (AJAX) |

---

## 👥 ইউজার টাইপ ও ওয়ার্কফ্লো

```
                    ┌──────────────┐
                    │   index.php  │ (Homepage)
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │  Login Form  │
                    │ (login.php)  │
                    └──────┬───────┘
                           │
              ┌────────────┼────────────┐
              ▼            ▼            ▼
        ┌──────────┐ ┌──────────┐ ┌──────────┐
        │  Host    │ │  Player  │ │  Trainer │
        │ Dashboard│ │   Hub    │ │ Dashboard│
        └──────────┘ └──────────┘ └──────────┘
```

---

## ⚙️ টেকনোলজি স্ট্যাক

| টেকনোলজি | ব্যবহার |
|---|---|
| **PHP** (প্রধান) | কোর ল্যাঙ্গুয়েজ (8.x) |
| **MySQL** | ডাটাবেজ (`casa_test`) |
| **MySQLi + PDO** | দুইভাবে DB সংযোগ |
| **PHPMailer** | ইমেইল পাঠানো |
| **CodeIgniter 4** | POS মডিউলে (separate) |
| **jQuery + AJAX** | API কল ও ডায়নামিক ফিচার |
| **Bootstrap 5** | ফ্রন্টএন্ড লেআউট |
| **Slick Slider** | স্লাইডার |
| **AOS (Animate On Scroll)** | অ্যানিমেশন |
| **Font Awesome 6** | আইকন |
| **.env** | এনভায়রনমেন্ট কনফিগারেশন |

---

> 📝 **নোট:** ফাইলের শুরুতে `###` বা `####` চিহ্নিত ফাইলগুলো পুরনো/লিগ্যাসি বা অসম্পূর্ণ — বর্তমানে ব্যবহৃত হচ্ছে না।

