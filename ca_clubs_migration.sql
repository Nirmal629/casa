-- ============================================================
-- Casa Club: ca_clubs table migration
-- Run this in phpMyAdmin → casa_test database → SQL tab
-- ============================================================

CREATE TABLE IF NOT EXISTS `ca_clubs` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `host_id`     INT(11)       NOT NULL,
  `club_name`   VARCHAR(150)  NOT NULL,
  `game_type`   VARCHAR(50)   NOT NULL DEFAULT 'Badminton',
  `category`    VARCHAR(100)  DEFAULT NULL,
  `country`     VARCHAR(100)  DEFAULT NULL,
  `province`    VARCHAR(100)  DEFAULT NULL,
  `city`        VARCHAR(100)  DEFAULT NULL,
  `club_info`   TEXT          DEFAULT NULL,
  `schedule`    TEXT          DEFAULT NULL,
  `cost_info`   TEXT          DEFAULT NULL,
  `logo`        VARCHAR(255)  DEFAULT NULL,
  `status`      ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_host_id` (`host_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Seed Data: 3 test clubs
-- IMPORTANT: Replace the host_id values (1, 2, 3) with real
-- Host user IDs from your local ca_users table.
-- To find them: SELECT ID, NAME, USERTYPE FROM ca_users WHERE USERTYPE = 'Host';
-- ============================================================

INSERT INTO `ca_clubs`
  (`host_id`, `club_name`, `game_type`, `category`, `country`, `province`, `city`, `club_info`, `schedule`, `cost_info`, `status`)
VALUES
(
  1,
  'Casa Badminton Club',
  'Badminton',
  'Recreational',
  'Canada',
  'Ontario',
  'Toronto',
  '<p>What We Offer:</p><ul><li><strong>Family & Couples Games</strong> – We encourage husbands and wives, parents and kids, and entire families to enjoy the sport together.</li><li><strong>Skill-Level Based Matches</strong> – Players grouped into beginner, intermediate, and advanced levels for fair, balanced games.</li><li><strong>Mixed-Gender & Mixed-Level Games</strong> – Fun matches where men, women, and players of different levels can team up.</li><li><strong>Community Spirit</strong> – A place where sportsmanship, teamwork, and enjoyment come first.</li></ul><p>Come join us, pick up your racket, and be part of the Casa family!</p>',
  '<p>📅 <strong>Monday</strong> – 8:30pm–10:30pm | Intermediate/Intermediate+ | Epic Venue</p><p>📅 <strong>Wednesday</strong> – 6:00pm–8:00pm | Intermediate/Intermediate+ | Epic Venue</p><p>📅 <strong>Friday</strong> – 6:00pm–8:00pm | Intermediate/Intermediate+ | Hymus Venue</p><p>📅 <strong>Saturday</strong> – 9:30pm–11:30pm | Intermediate+ | Epic Venue</p>',
  '<p><strong>Men Double:</strong></p><ul><li>Price = (Court Cost + Birdie cost) / no. of players</li><li>4 players: $25 each | 5 players: $22 each | 6 players: $20 each</li></ul><p><strong>Women Double:</strong></p><ul><li>4 players: $18 each | 5 players: $15 each | 6 players: $14 each</li></ul><p>Note: The portal dynamically adjusts the price based on confirmed players.</p>',
  'Active'
),
(
  2,
  'Birdie Busters Club',
  'Badminton',
  'Competitive',
  'Canada',
  'Ontario',
  'Mississauga',
  '<p>Birdie Busters is a competitive badminton club for intermediate and advanced players looking for a serious game environment.</p>',
  '<p>📅 <strong>Tuesday</strong> – 7:00pm–9:00pm | Intermediate+ | Venue TBD</p><p>📅 <strong>Saturday</strong> – 10:00am–12:00pm | Advanced | Venue TBD</p>',
  '<p>4 players: $28 each | 6 players: $22 each</p>',
  'Active'
),
(
  3,
  'Net Ninjas Club',
  'Badminton',
  'Recreational',
  'Canada',
  'Ontario',
  'Brampton',
  '<p>Net Ninjas is a fun, social badminton club for all levels. Beginners welcome!</p>',
  NULL,
  NULL,
  'Active'
);
