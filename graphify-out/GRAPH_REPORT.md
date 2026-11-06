# Graph Report - staging  (2026-11-06)

## Corpus Check
- Large corpus: 673 files · ~518,687 words. Semantic extraction will be expensive (many Claude tokens). Consider running on a subfolder.

## Summary
- 2618 nodes · 4536 edges · 395 communities (84 shown, 311 thin omitted)
- Extraction: 90% EXTRACTED · 10% INFERRED · 0% AMBIGUOUS · INFERRED: 476 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Host Management & Events
- Host Management & Events
- Tournament & Scoring Engine
- Core Framework & Routing
- Tournament & Scoring Engine
- Tournament & Scoring Engine
- Tournament & Scoring Engine
- Tournament & Scoring Engine
- Player Accounts & Gameplay
- POS & Club Administration
- Pagination Module
- Ledger & Financial Transactions
- POS & Club Administration
- Core Framework & Routing
- CI/CD & Testing Workflows
- Database Models & Queries
- Session Module
- Ledger & Financial Transactions
- Ledger & Financial Transactions
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- Tournament & Scoring Engine
- Host Management & Events
- Core Framework & Routing
- Tournament & Scoring Engine
- Http_Regression Module
- Tournament & Scoring Engine
- POS & Club Administration
- POS & Club Administration
- Homecontroller Module
- Transport Test Module
- Tournament & Scoring Engine
- POS & Club Administration
- Tournament & Scoring Engine
- Rememberme Module
- Database Models & Queries
- Host Management & Events
- Host Management & Events
- POS & Club Administration
- POS & Club Administration
- Tournament & Scoring Engine
- Admin Portal Operations
- Authentication & Sessions
- Admin Portal Operations
- Admin Portal Operations
- Admin Portal Operations
- Authentication & Sessions
- Database Models & Queries
- Calendar_Helper Module
- Helpers Module
- Validators Module
- Dynamic Event Calendar Module
- _Bootstrap Module
- Database Models & Queries
- Tournament & Scoring Engine
- Tournament & Scoring Engine
- Tournament & Scoring Engine
- Admin Portal Operations
- Admin Portal Operations
- Admin Portal Operations
- Manual Test Logger Module
- POS & Club Administration
- Admin Portal Operations
- Activity Tracker Module
- Player Accounts & Gameplay
- POS & Club Administration
- Tournament & Scoring Engine
- Tournament & Scoring Engine
- Host Management & Events
- _Security Module
- Loginthrottle Module
- Player Accounts & Gameplay
- Regression Module
- _Scope Module
- Player Accounts & Gameplay
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- Tournament & Scoring Engine
- Tournament & Scoring Engine
- Mailer Module
- Regression Module
- POS & Club Administration
- Tournament & Scoring Engine
- Tournament & Scoring Engine
- Tournament & Scoring Engine
- Tournament & Scoring Engine
- Httplogger Module
- Security Transport Module
- Casa Dropdown Module
- POS & Club Administration
- POS & Club Administration
- Admin Portal Operations
- Tournament & Scoring Engine
- Core Framework & Routing
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- Admin Portal Operations
- Admin Portal Operations
- Db Module
- Bridie Module
- Club Module
- Description Module
- Eventcategory Module
- User Module
- Userprofile Module
- Venue Module
- Tournament & Scoring Engine
- UI Templates & Views
- Init Module
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- Dbconnection Module
- Import_Location_Data Module
- Host Management & Events
- UI Templates & Views
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- Dbconnection Module
- Notification Module
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- Admin Portal Operations
- Admin Portal Operations
- Admin Portal Operations
- Gameautomation Module
- Column Module
- Table Module
- Host Management & Events
- UI Templates & Views
- UI Templates & Views
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration
- POS & Club Administration

## God Nodes (most connected - your core abstractions)
1. `HostController` - 59 edges
2. `Clubadmin` - 57 edges
3. `Model` - 49 edges
4. `LedgerModel` - 48 edges
5. `EventModel` - 41 edges
6. `Pagination` - 39 edges
7. `Session` - 38 edges
8. `PlayerController` - 37 edges
9. `Database` - 30 edges
10. `UserModel` - 30 edges

## Surprising Connections (you probably didn't know these)
- `formatPayAmount()` --calls--> `getLocationCurrencyMap()`  [INFERRED]
  app/Helpers/player_ledger_helper.php → scripts/import_location_data.php
- `resolveNavbarStoreLabel()` --calls--> `ClubModel`  [INFERRED]
  app/tests/navbar_dynamic_label_test.php → app/Models/ClubModel.php
- `audit_shape_row()` --calls--> `audit_behaviour()`  [INFERRED]
  api/audit/_metrics.php → api/audit/_bootstrap.php
- `audit_behaviour_reasons()` --calls--> `audit_behaviour()`  [INFERRED]
  api/audit/user_detail.php → api/audit/_bootstrap.php
- `{closure#1}()` --calls--> `audit_type_label()`  [INFERRED]
  api/audit/event.php → api/audit/_scope.php

## Import Cycles
- None detected.

## Communities (395 total, 311 thin omitted)

### Community 0 - "Host Management & Events"
Cohesion: 0.05
Nodes (4): HostController, PlayerSubscriptionController, EventModel, GameJoinModel

### Community 1 - "Host Management & Events"
Cohesion: 0.11
Nodes (56): api(), applyRange(), applyUrl(), avatar(), behaviourChip(), closeAll(), closeDrill(), closeMini() (+48 more)

### Community 2 - "Tournament & Scoring Engine"
Cohesion: 0.10
Nodes (51): addSetBoardPoint(), applyMatchConfigToState(), applySetLimitVisibility(), buildScoreAnnouncement(), buildServeAnnouncement(), clonePlayableState(), courtSideForTeam(), currentServer() (+43 more)

### Community 3 - "Core Framework & Routing"
Cohesion: 0.06
Nodes (5): {closure#2}(), Request, Response, Router, TestLoggerService

### Community 4 - "Tournament & Scoring Engine"
Cohesion: 0.10
Nodes (44): addSetBoardPoint(), applyMatchConfigToState(), applySetLimitVisibility(), buildScoreAnnouncement(), buildServeAnnouncement(), clonePlayableState(), courtSideForTeam(), currentServer() (+36 more)

### Community 5 - "Tournament & Scoring Engine"
Cohesion: 0.10
Nodes (44): addSetBoardPoint(), applyMatchConfigToState(), applySetLimitVisibility(), buildScoreAnnouncement(), buildServeAnnouncement(), clonePlayableState(), courtSideForTeam(), currentServer() (+36 more)

### Community 6 - "Tournament & Scoring Engine"
Cohesion: 0.10
Nodes (43): addSetBoardPoint(), applyMatchConfigToState(), applySetLimitVisibility(), buildScoreAnnouncement(), buildServeAnnouncement(), clonePlayableState(), courtSideForTeam(), currentServer() (+35 more)

### Community 7 - "Tournament & Scoring Engine"
Cohesion: 0.14
Nodes (45): addClass(), addStyle(), appendChild(), delayForVoice(), disableElement(), enableElement(), enableFullIntervalInput(), enableMidIntervalInput() (+37 more)

### Community 8 - "Player Accounts & Gameplay"
Cohesion: 0.08
Nodes (10): PlayerController, auditResolveGameHost(), auditResolveHostId(), logPlayerActivity(), e(), formatEventQuote(), getPaymentState(), prepareTournamentData() (+2 more)

### Community 9 - "POS & Club Administration"
Cohesion: 0.07
Nodes (19): checkVisibility(), startCounting(), dt(), e(), emit(), En, Ft(), _getHandlersByEventName() (+11 more)

### Community 10 - "Pagination Module"
Cohesion: 0.09
Nodes (3): {closure#1}(), paginate(), Pagination

### Community 11 - "Ledger & Financial Transactions"
Cohesion: 0.10
Nodes (6): calculateLedgerSummary(), fetchPlayerLedgerData(), renderPlayerPayHtml(), renderPlayerSideLedgerHtml(), LedgerModel, LedgerService

### Community 12 - "POS & Club Administration"
Cohesion: 0.06
Nodes (12): ajaxConvert(), ajaxHandleResponses(), computeStyleTests(), dataAttr(), done(), finalPropName(), getData(), leverageNative() (+4 more)

### Community 13 - "Core Framework & Routing"
Cohesion: 0.08
Nodes (9): Model, ExpenseModel, HostPlayerCarryForwardModel, PlayerClubStatusModel, ReviewModel, TournamentPaymentModel, TournamentTeamModel, TournamentUserModel (+1 more)

### Community 14 - "CI/CD & Testing Workflows"
Cohesion: 0.06
Nodes (33): runs-on, services, steps, APP_URL, HEADLESS, MYSQL_ALLOW_EMPTY_PASSWORD, MYSQL_DATABASE, jobs (+25 more)

### Community 19 - "POS & Club Administration"
Cohesion: 0.11
Nodes (13): App, ContentSecurityPolicy, Cors, CURLRequest, Email, Encryption, Feature, Generators (+5 more)

### Community 20 - "POS & Club Administration"
Cohesion: 0.13
Nodes (4): Clubadmin, PosBrandModel, PosCategoryModel, PosSizeModel

### Community 21 - "POS & Club Administration"
Cohesion: 0.13
Nodes (3): PaymentModel, View, MembershipModel

### Community 23 - "POS & Club Administration"
Cohesion: 0.11
Nodes (5): Admin, Auth, BaseController, Home, Auth

### Community 24 - "Tournament & Scoring Engine"
Cohesion: 0.14
Nodes (18): deleteTournament(), detailItem(), fillForm(), getFilteredRows(), getRowSearchText(), htmlEscape(), initCustomTable(), normalizeSkill() (+10 more)

### Community 25 - "Host Management & Events"
Cohesion: 0.09
Nodes (3): ClubModel, PlayerLogModel, resolveNavbarStoreLabel()

### Community 27 - "Core Framework & Routing"
Cohesion: 0.12
Nodes (4): HostSubscriptionController, Controller, HostSubscriptionModel, TournamentModel

### Community 28 - "Tournament & Scoring Engine"
Cohesion: 0.11
Nodes (13): allowedQuarterGroups, applyStoredSetConfig(), battleTournamentDate(), battleTournamentTime(), groupLeagueSetsSelect, groupSelects, groupsRequiredInput, inferGroupsRequiredFromTeams() (+5 more)

### Community 30 - "Http_Regression Module"
Cohesion: 0.14
Nodes (3): launch(), port(), lint()

### Community 31 - "Tournament & Scoring Engine"
Cohesion: 0.11
Nodes (13): allowedQuarterGroups, applyStoredSetConfig(), battleTournamentDate(), battleTournamentTime(), groupLeagueSetsSelect, groupSelects, groupsRequiredInput, inferGroupsRequiredFromTeams() (+5 more)

### Community 32 - "POS & Club Administration"
Cohesion: 0.19
Nodes (3): Session, BookingModel, CourtModel

### Community 33 - "POS & Club Administration"
Cohesion: 0.10
Nodes (20): suggest, ext-curl, ext-dom, ext-exif, ext-fileinfo, ext-gd, ext-imagick, ext-libxml (+12 more)

### Community 35 - "Transport Test Module"
Cohesion: 0.11
Nodes (11): assert, calls, context, events, fs, input, path, vm (+3 more)

### Community 36 - "Tournament & Scoring Engine"
Cohesion: 0.12
Nodes (9): allowedQuarterGroups, applyTournamentCourtAssignment(), battleTournamentDate(), battleTournamentTime(), groupSelects, groupsRequiredInput, inferGroupsRequiredFromTeams(), matchRuleSelects (+1 more)

### Community 38 - "Tournament & Scoring Engine"
Cohesion: 0.14
Nodes (6): applyTournamentCourtAssignment(), battleTournamentDate(), battleTournamentHasStarted(), battleTournamentTime(), inferGroupsRequiredFromTeams(), syncGroupFixturesAndStandings()

### Community 39 - "Rememberme Module"
Cohesion: 0.25
Nodes (3): env(), get_loaded_env_info(), RememberMe

### Community 42 - "Host Management & Events"
Cohesion: 0.33
Nodes (16): escapeHtml(), fetchPlayerStats(), getApiUrl(), getCurrentHostId(), getRankBadgeHtml(), getRatingModal(), getSearchInput(), getShell() (+8 more)

### Community 43 - "Host Management & Events"
Cohesion: 0.31
Nodes (16): apiCall(), currentPeriod(), doApply(), doRowAction(), esc(), load(), money(), noteBox() (+8 more)

### Community 44 - "POS & Club Administration"
Cohesion: 0.15
Nodes (4): ClubModel, CourtPriceModel, EntryModel, PaymentModel

### Community 45 - "POS & Club Administration"
Cohesion: 0.18
Nodes (17): addCombinator(), assert(), compile(), condense(), createPositionalPseudo(), elementMatcher(), find(), markFunction() (+9 more)

### Community 47 - "Tournament & Scoring Engine"
Cohesion: 0.23
Nodes (14): addTeam(), colorMap, colors, createWheel(), itemLabel(), itemValue(), rememberItemColor(), removePendingWinnerBeforeNextSpin() (+6 more)

### Community 48 - "Admin Portal Operations"
Cohesion: 0.17
Nodes (6): setFieldValue(), setProductForm(), setProductView(), setViewValue(), updateImagePreview(), updateProductTypeOptions()

### Community 51 - "Admin Portal Operations"
Cohesion: 0.18
Nodes (5): setFieldValue(), setHeroBannerModal(), setHeroBannerView(), setViewValue(), updateImagePreview()

### Community 52 - "Admin Portal Operations"
Cohesion: 0.18
Nodes (5): setAdvertisementModal(), setAdvertisementView(), setFieldValue(), setSelectValue(), setViewValue()

### Community 53 - "Admin Portal Operations"
Cohesion: 0.18
Nodes (5): setFieldValue(), setMediaModal(), setMediaView(), setViewValue(), updatePreview()

### Community 55 - "Database Models & Queries"
Cohesion: 0.19
Nodes (3): Migration, M20260926000001InitDatabase, M20261025000001CreateRememberTokensTable

### Community 56 - "Calendar_Helper Module"
Cohesion: 0.23
Nodes (10): getCurrentPeriod(), getDynamicCalendarJsPath(), getDynamicCalendarJsUrl(), getDynamicCalendarJsVersion(), getMonthOptions(), getYearOptions(), renderDynamicCalendarAssets(), renderDynamicCalendarScript() (+2 more)

### Community 57 - "Helpers Module"
Cohesion: 0.15
Nodes (6): formatDate(), formatTime(), generateBookingNo(), renderFlashToast(), renderToast(), uploadFile()

### Community 59 - "Dynamic Event Calendar Module"
Cohesion: 0.29
Nodes (13): autoInit(), getCurrentPeriod(), initDynamicEventCalendar(), onDateChange(), onFromTimeChange(), syncBounds(), validate(), initSchedulePeriod() (+5 more)

### Community 61 - "_Bootstrap Module"
Cohesion: 0.15
Nodes (4): audit_behaviour(), audit_shape_row(), audit_behaviour_reasons(), audit_user_trend()

### Community 63 - "Tournament & Scoring Engine"
Cohesion: 0.19
Nodes (7): courtDashboardDate(), courtDashboardPlayer(), courtDashboardPlayers(), courtDashboardStageRow(), courtDashboardTeamLabel(), courtDashboardTime(), courtDashboardWinner()

### Community 64 - "Tournament & Scoring Engine"
Cohesion: 0.26
Nodes (11): addTeam(), colorMap, colors, createWheel(), rememberItemColor(), removePendingWinnerBeforeNextSpin(), schedulePendingWinnerRemoval(), showResults() (+3 more)

### Community 65 - "Tournament & Scoring Engine"
Cohesion: 0.19
Nodes (7): courtDashboardDate(), courtDashboardPlayer(), courtDashboardPlayers(), courtDashboardStageRow(), courtDashboardTeamLabel(), courtDashboardTime(), courtDashboardWinner()

### Community 66 - "Admin Portal Operations"
Cohesion: 0.24
Nodes (5): addDots(), buildPageButton(), getFilteredRows(), renderPagination(), renderReviewsTable()

### Community 67 - "Admin Portal Operations"
Cohesion: 0.23
Nodes (5): buildPageButton(), generateBookingNo(), getFilteredRows(), renderOrdersTable(), renderPagination()

### Community 68 - "Admin Portal Operations"
Cohesion: 0.24
Nodes (5): addDots(), buildPageButton(), getFilteredRows(), renderPagination(), renderUsersTable()

### Community 69 - "Manual Test Logger Module"
Cohesion: 0.33
Nodes (9): cleanString(), emitLog(), generateCorrelationId(), getElementIdentifier(), getEndpointUrl(), getSessionId(), isLoggerActive(), nextSequence() (+1 more)

### Community 71 - "Admin Portal Operations"
Cohesion: 0.31
Nodes (8): addDots(), buildPageButton(), getFilteredRows(), openContactModal(), openModal(), renderContactsTable(), renderPagination(), setText()

### Community 72 - "Activity Tracker Module"
Cohesion: 0.51
Nodes (10): cleanText(), extractElementLabel(), findParentTabName(), getApiEndpoint(), getQueryParam(), handleLedgerClick(), handlePaymentButtonClick(), handleStoreProductClick() (+2 more)

### Community 73 - "Player Accounts & Gameplay"
Cohesion: 0.25
Nodes (7): addReviewSlideDirectly(), ensureModalInBody(), escapeHtml(), getModalInstance(), openModalDirectly(), resetModalForNew(), setModalRating()

### Community 74 - "POS & Club Administration"
Cohesion: 0.22
Nodes (11): buildFragment(), buildParams(), disableScript(), DOMEval(), domManip(), getAll(), isArrayLike(), remove() (+3 more)

### Community 75 - "Tournament & Scoring Engine"
Cohesion: 0.33
Nodes (6): applyConfiguredCourtsToStageMatches(), createStageMatches(), fetchMatch(), maybeCreateNextStage(), recalculateGroupStandings(), stageMatchCount()

### Community 76 - "Tournament & Scoring Engine"
Cohesion: 0.33
Nodes (6): applyConfiguredCourtsToStageMatches(), createStageMatches(), fetchMatch(), maybeCreateNextStage(), recalculateGroupStandings(), stageMatchCount()

### Community 77 - "Host Management & Events"
Cohesion: 0.22
Nodes (3): hs_eligibility(), hs_locate(), hs_period()

### Community 78 - "_Security Module"
Cohesion: 0.27
Nodes (5): apiSecurityDeny(), requireApiPost(), requireApiUser(), requireOwnedEvent(), CsrfGuard

### Community 82 - "_Scope Module"
Cohesion: 0.25
Nodes (5): {closure#1}(), audit_game_in_scope(), audit_game_name(), audit_type_label(), audit_user_in_scope()

### Community 84 - "Player Accounts & Gameplay"
Cohesion: 0.39
Nodes (7): createDayElement(), escapeLedgerHtml(), formatLedgerCurrency(), handleDayClick(), loadPlayerDetailedLedger(), renderCalendar(), renderDetailedLedgerHtml()

### Community 88 - "POS & Club Administration"
Cohesion: 0.22
Nodes (9): require-dev, codeigniter/coding-standard, fakerphp/faker, friendsofphp/php-cs-fixer, kint-php/kint, mikey179/vfsstream, nexusphp/cs-config, phpunit/phpunit (+1 more)

### Community 89 - "POS & Club Administration"
Cohesion: 0.22
Nodes (9): Animation(), camelCase(), createFxNow(), createTween(), defaultPrefilter(), fcamelCase(), getDefaultDisplay(), propFilter() (+1 more)

### Community 90 - "Tournament & Scoring Engine"
Cohesion: 0.39
Nodes (5): createStageMatches(), fetchMatch(), maybeCreateNextStage(), recalculateGroupStandings(), stageMatchCount()

### Community 91 - "Tournament & Scoring Engine"
Cohesion: 0.39
Nodes (5): createStageMatches(), fetchMatch(), maybeCreateNextStage(), recalculateGroupStandings(), stageMatchCount()

### Community 94 - "POS & Club Administration"
Cohesion: 0.25
Nodes (7): description, homepage, license, name, scripts, test, type

### Community 100 - "Security Transport Module"
Cohesion: 0.48
Nodes (4): local(), mutates(), protectForm(), token()

### Community 101 - "Casa Dropdown Module"
Cohesion: 0.48
Nodes (5): bindCustom(), closeAllDropdowns(), initAll(), initDropdown(), renderOptions()

### Community 104 - "POS & Club Administration"
Cohesion: 0.29
Nodes (7): boxModelAdjustment(), createButtonPseudo(), createInputPseudo(), curCSS(), getWidthOrHeight(), manipulationTarget(), nodeName()

### Community 113 - "POS & Club Administration"
Cohesion: 0.53
Nodes (4): getFirstChildWithTagName(), getHash(), init(), showTab()

### Community 114 - "POS & Club Administration"
Cohesion: 0.33
Nodes (6): require, ext-intl, ext-mbstring, laminas/laminas-escaper, php, psr/log

### Community 115 - "Admin Portal Operations"
Cohesion: 0.70
Nodes (4): multiselect_deselectAll(), multiselect_selectAll(), multiselect_selected(), multiselect_toggle()

### Community 116 - "Admin Portal Operations"
Cohesion: 0.70
Nodes (4): buildSidebarNav(), collapse(), ensureVisible(), expand()

### Community 126 - "Tournament & Scoring Engine"
Cohesion: 0.60
Nodes (3): calculateAccessoriesCost(), calculateHoursAndFacilityCost(), calculateTotal()

### Community 127 - "UI Templates & Views"
Cohesion: 0.60
Nodes (3): formatTime(), refreshLogs(), renderLogItems()

### Community 134 - "Dbconnection Module"
Cohesion: 0.83
Nodes (3): auditResolveGameHost(), auditResolveHostId(), logPlayerActivity()

### Community 142 - "POS & Club Administration"
Cohesion: 0.50
Nodes (3): autoload, psr-4, App\\

### Community 143 - "Dbconnection Module"
Cohesion: 0.83
Nodes (3): auditResolveGameHost(), auditResolveHostId(), logPlayerActivity()

### Community 144 - "Notification Module"
Cohesion: 0.50
Nodes (3): dismissAll, dismissBtns, notificationCards

### Community 146 - "POS & Club Administration"
Cohesion: 0.50
Nodes (4): autoload, exclude-from-classmap, psr-4, CodeIgniter\\

### Community 147 - "POS & Club Administration"
Cohesion: 0.50
Nodes (4): config, optimize-autoloader, preferred-install, sort-packages

### Community 148 - "POS & Club Administration"
Cohesion: 0.50
Nodes (4): support, forum, slack, source

### Community 179 - "POS & Club Administration"
Cohesion: 0.67
Nodes (3): Identity(), resolve(), Thrower()

## Knowledge Gaps
- **311 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `HostController` connect `Host Management & Events` to `Database Models & Queries`, `Pagination Module`, `Ledger & Financial Transactions`, `Database Models & Queries`, `Player Accounts & Gameplay`, `Ledger & Financial Transactions`, `Host Management & Events`, `Host Management & Events`, `Core Framework & Routing`, `Core Framework & Routing`?**
  _High betweenness centrality (0.029) - this node is a cross-community bridge._
- **Should `Host Management & Events` be split into smaller, more focused modules?**
  _Cohesion score 0.05293383270911361 - nodes in this community are weakly interconnected._
- **Why does `Model` connect `Core Framework & Routing` to `Host Management & Events`, `Player Accounts & Gameplay`, `Database Models & Queries`, `Ledger & Financial Transactions`, `Player Accounts & Gameplay`, `Ledger & Financial Transactions`, `POS & Club Administration`, `Host Management & Events`, `Core Framework & Routing`, `Core Framework & Routing`?**
  _High betweenness centrality (0.019) - this node is a cross-community bridge._
- **Should `Host Management & Events` be split into smaller, more focused modules?**
  _Cohesion score 0.11373260738052027 - nodes in this community are weakly interconnected._
- **Why does `ClubModel` connect `Host Management & Events` to `Host Management & Events`, `POS & Club Administration`, `POS & Club Administration`, `Player Accounts & Gameplay`, `Core Framework & Routing`, `POS & Club Administration`, `Core Framework & Routing`?**
  _High betweenness centrality (0.017) - this node is a cross-community bridge._
- **Should `Tournament & Scoring Engine` be split into smaller, more focused modules?**
  _Cohesion score 0.10064935064935066 - nodes in this community are weakly interconnected._
- **Should `Core Framework & Routing` be split into smaller, more focused modules?**
  _Cohesion score 0.0636734693877551 - nodes in this community are weakly interconnected._