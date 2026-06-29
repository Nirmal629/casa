# Aritra's Development Changelog

## 2026-06-23 - Joined Players Modal Updates

- **Files modified**: `host-scheduled-game.php`, `subscription-list.php`, `player-monthly-subscription.php`, `api/view_joined_all.php`, `api/view_joined_all_default.php`
- **Why**: The user requested that the modal dialog title for viewing joined players be renamed to 'Joined Players' and the player list be displayed in a tabular format showing Sl No, Name, Gender, and Level.
- **How/What**: Replaced the text `<h6 class="customModal_head">View All Player's Joined</h6>` with `<h6 class="customModal_head">Joined Players</h6>` across all files that contained the modal structure. Updated the PHP output in both `view_joined_all.php` and `view_joined_all_default.php` to output a Bootstrap `<table>` instead of nested `<div>`s, mapping the fetched player data into `<tr>` and `<td>` rows.

## 2026-06-23 - Invite/Add Players Modal Updates

- **Files modified**: `api/view_joined_invited.php`
- **Why**: You requested the 'Invite/Add Players' list to also be converted to a clean tabular format, and you wanted the 'Confirm' feature added back as sleek modern icons instead of bulky switches/text.
- **How/What**: Wrapped the PHP `while` loops for both public and private events into Bootstrap `<table>` structures. Replaced the old "Add Player" text block with two columns: "Add" and "Confirm". Used hidden `<input type="checkbox">` toggles mapped directly to FontAwesome icons (`fa-user-plus` toggling to `fa-user-check` for Add, and `fa-check` toggling to `fa-check-double` for Confirm). This seamlessly ties into the existing AJAX logic without requiring javascript changes while delivering a beautiful, minimalist UI.

## 2026-06-23 - Mobile Readability Optimizations for Modal Tables

- **Files modified**: `api/view_joined_invited.php`, `api/view_joined_all.php`, `api/view_joined_all_default.php`
- **Why**: You noted that the new tabular layout for Add/Invite players and Joined players was too cramped on mobile screens.
- **How/What**: Compressed the table column headers to save horizontal space. Renamed 'Sl No' to '#', 'Gender' to 'G', 'Level' to 'Lvl', and used explicit icon headers for 'Add' and 'Confirm'. Set fixed minimum column widths where appropriate and reduced the base table font size from 0.9rem to 0.85rem.
- **Bonus Mobile Fix**: I also added the `text-nowrap` class to the Bootstrap tables across all three files (`view_joined_invited.php`, `view_joined_all.php`, `view_joined_all_default.php`). This ensures that if the content *still* doesn't fit horizontally on extremely small screens, the browser will allow smooth horizontal scrolling rather than squishing and wrapping the text into an unreadable vertical column.

## 2026-06-23 - Master Configuration Architecture Decision

- **Files modified**: `ca_master_config_migration.sql` (Created)
- **Why**: We needed to centralize dropdowns (like Country, Province, City) so they aren't hardcoded in the codebase, enabling Casa Club to easily expand to new regions (e.g., India, Uttar Pradesh).
- **How/What**: I decided to implement a strict hierarchy using a `PARENT_ID` column in the `ca_master_config` table. 
## 2026-06-23 - Player Activity Tracking & View Player Access Restrictions

- **Files modified**: `api/dbConnection.php`, `includes/Auth/login.php`, `logout.php`, `api/join_event.php`, `api/join_event_default.php`, `api/view_joined_all.php`, `api/view_joined_all_default.php`, `api/view_joined_invited.php`, `api/save_payment.php`, `api/play_filter_schedule.php`
- **Why**: The meeting notes specified two immediate requirements: (1) Tracking exactly what players do behind the scenes without them seeing it, and (2) Blocking players from seeing who joined an event until they actually join the event themselves.
- **How/What**: 
  1. I wrote a global `logPlayerActivity()` function and injected it into `dbConnection.php` so it's universally accessible. I then hooked it into Login, Logout, Game Joins, Viewing Players, and Payments.
  2. For the "View Players" restriction: I updated the Player's Discovery/Schedule feed (`play_filter_schedule.php`). If they haven't joined the game, the View button is now completely removed and replaced with a red italicized prompt: *"Please join the game to see further details."*
