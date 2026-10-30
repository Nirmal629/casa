

(function () {
    'use strict';

    function getApiUrl(endpoint) {
        if (typeof BASE_URL !== 'undefined' && BASE_URL) {
            var base = BASE_URL.endsWith('/') ? BASE_URL : BASE_URL + '/';
            return base + 'api/' + endpoint;
        }
        if (window.location.pathname.includes('/host/') || window.location.pathname.includes('/Views/')) {
            return '../api/' + endpoint;
        }
        return '/api/' + endpoint;
    }

    var ratingModalInstance = null;
    function getRatingModal() {
        var ratingModalEl = document.getElementById('ratingModal');
        if (!ratingModalEl) return null;
        if (ratingModalInstance) return ratingModalInstance;

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            if (typeof bootstrap.Modal.getOrCreateInstance === 'function') {
                ratingModalInstance = bootstrap.Modal.getOrCreateInstance(ratingModalEl);
            } else {
                ratingModalInstance = bootstrap.Modal.getInstance(ratingModalEl) || new bootstrap.Modal(ratingModalEl);
            }
            return ratingModalInstance;
        }
        return null;
    }

    function getShell() {
        return document.querySelector('.player-stats-shell');
    }

    function getSearchInput() {
        var shell = getShell();
        return (shell && (shell.querySelector('#playerStatsSearch') || shell.querySelector('#search') || shell.querySelector('.search-box-wrap input')))
            || document.getElementById('playerStatsSearch')
            || document.getElementById('search');
    }

    function getCurrentHostId() {
        var shellEl = getShell();
        var modalEl = document.getElementById('ratingModal');
        var hostId = shellEl?.dataset?.hostId || modalEl?.dataset?.hostId || window.currentHostId || '0';
        return parseInt(hostId, 10);
    }

    var cachedPlayersList = [];
    var currentPage = 1;
    var perPage = 10;
    var currentStatsXhr = null;
    var searchDebounceTimer = null;
    var hasSaved = false;
    var isSaving = false;
    var hasInitialLoaded = false;

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function getRankBadgeHtml(rankVal, prevRankVal) {
        var num = parseInt(rankVal, 10);
        if (!num || num <= 0) return '—';
        var badges = {
            1: '<span class="rank-badge rank-1" title="Rank #1">🥇 #1</span>',
            2: '<span class="rank-badge rank-2" title="Rank #2">🥈 #2</span>',
            3: '<span class="rank-badge rank-3" title="Rank #3">🥉 #3</span>'
        };
        var baseBadge = badges[num] || `<span class="rank-badge rank-other" title="Rank #${num}">#${num}</span>`;

        var prev = parseInt(prevRankVal, 10);
        var trendHtml = '';
        if (prev && prev > 0 && prev !== num) {
            if (num < prev) {
                // Better position (e.g. was #5, now #3 -> promoted by 2)
                var diff = prev - num;
                trendHtml = `<span class="rank-trend up" title="Promoted from #${prev} (+${diff})">▲${diff}</span>`;
            } else {
                // Worse position (e.g. was #3, now #4 -> demoted by 1)
                var diff = num - prev;
                trendHtml = `<span class="rank-trend down" title="Demoted from #${prev} (-${diff})">▼${diff}</span>`;
            }
        }

        return `<div class="rank-wrap">${baseBadge}${trendHtml}</div>`;
    }

    function getStatusBadgeHtml(status) {
        var st = status || 'New';
        var badgeText = st;
        if (st === 'Active') badgeText = '🔥 Active';
        else if (st === 'Regular') badgeText = '🔥 Regular';
        else if (st === 'Inactive') badgeText = '💤 Inactive';
        return `<span class="status ${escapeHtml(st)}">${badgeText}</span>`;
    }

    // Render pagination controls via reusable CasaPagination helper
    function renderGenericPagination(totalItems, page, limit) {
        var Pager = window.CasaPagination || window.Pagination;
        if (!Pager) return;

        var shell = getShell();
        var countTarget = (shell && shell.querySelector('#count')) || '#count';

        Pager.render(countTarget, {
            total: totalItems,
            page: page,
            perPage: limit,
            itemName: 'players',
            onPageChange: function (targetPage) {
                if (targetPage > 0 && targetPage !== currentPage) {
                    currentPage = targetPage;
                    renderTableRows(currentPage, perPage);
                }
            },
            onPerPageChange: function (newLimit) {
                perPage = newLimit;
                renderTableRows(1, perPage);
            }
        });
    }

    // Render an individual table row HTML
    function renderPlayerRowHtml(p, slNo) {
        var pId = parseInt(p.player_id, 10);
        var pName = p.player_name || p.player || '';
        var pLevel = p.verified_level || p.skill_level || 'Beginner';
        var pillClass = String(pLevel).toLowerCase().replace(/[\s+]/g, '');
        var pGender = p.gender || 'Male';
        var pRankNum = parseInt(p.current_ranking || p.rank, 10) || 0;
        var pPrevRankNum = parseInt(p.prev_ranking, 10) || 0;
        var rankHtml = getRankBadgeHtml(pRankNum, pPrevRankNum);

        var mon = parseInt(p.Monday || p.mon || 0, 10);
        var tue = parseInt(p.Tuesday || p.tue || 0, 10);
        var wed = parseInt(p.Wednesday || p.wed || 0, 10);
        var thu = parseInt(p.Thursday || p.thu || 0, 10);
        var fri = parseInt(p.Friday || p.fri || 0, 10);
        var sat = parseInt(p.Saturday || p.sat || 0, 10);
        var sun = parseInt(p.Sunday || p.sun || 0, 10);

        var totalGames = parseInt(p.total_games || 0, 10);
        var lastPlayed = p.last_played ? escapeHtml(p.last_played) : '—';
        var daysAgo = (p.last_played !== null && p.days_since_last_game !== undefined && p.days_since_last_game !== null)
            ? parseInt(p.days_since_last_game, 10)
            : (p.days_ago !== undefined && p.days_ago !== null ? parseInt(p.days_ago, 10) : '—');
        var status = p.computed_status || p.status || 'New';
        var preferred = p.computed_preferred || p.preferred || 'Saturday';

        return `
          <tr data-name="${escapeHtml(pName.toLowerCase())}" data-level="${escapeHtml(pLevel)}" data-status="${escapeHtml(status)}" data-gender="${escapeHtml(pGender)}">
            <td class="text-center text-muted fw-semibold">${slNo}</td>
            <td><div class="name">${escapeHtml(pName)}</div></td>
            <td><span class="pill ${pillClass}">${escapeHtml(pLevel)}</span></td>
            <td class="text-center">${rankHtml}</td>
            <td class="text-center day ${mon ? 'has' : ''}">${mon}</td>
            <td class="text-center day ${tue ? 'has' : ''}">${tue}</td>
            <td class="text-center day ${wed ? 'has' : ''}">${wed}</td>
            <td class="text-center day ${thu ? 'has' : ''}">${thu}</td>
            <td class="text-center day ${fri ? 'has' : ''}">${fri}</td>
            <td class="text-center day ${sat ? 'has' : ''}">${sat}</td>
            <td class="text-center day ${sun ? 'has' : ''}">${sun}</td>
            <td class="text-center fw-bold text-primary">${totalGames}</td>
            <td class="text-center">${lastPlayed}</td>
            <td class="text-center">${daysAgo}</td>
            <td class="text-center">${getStatusBadgeHtml(status)}</td>
            <td>${escapeHtml(preferred)}</td>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-outline-primary edit-btn trigger-edit-rating" data-id="${pId}" data-name="${escapeHtml(pName)}" data-status="${escapeHtml(status)}" title="Edit Rating">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                  <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5h6a.5.5 0 0 0 0-1h-6A1.5 1.5 0 0 0 1 2.5z"/>
                </svg>
              </button>
            </td>
          </tr>`;
    }

    // Render table rows slice and update pagination
    function renderTableRows(page, limit) {
        var shell = getShell();
        var rowsContainer = (shell && shell.querySelector('#rows')) || document.getElementById('rows');
        if (!rowsContainer) return;

        var totalItems = cachedPlayersList.length;
        if (totalItems === 0) {
            rowsContainer.innerHTML = '<tr class="no-data-row"><td colspan="17" class="empty">No players found matching current filters.</td></tr>';
            renderGenericPagination(0, 1, limit);
            return;
        }

        var Pager = window.CasaPagination || window.Pagination;
        var paged = Pager ? Pager.paginate(cachedPlayersList, page, limit) : {
            items: cachedPlayersList.slice((page - 1) * limit, page * limit),
            page: page,
            startIdx: (page - 1) * limit
        };

        currentPage = paged.page;

        var html = '';
        paged.items.forEach(function (p, idx) {
            var slNo = paged.startIdx + idx + 1;
            html += renderPlayerRowHtml(p, slNo);
        });

        rowsContainer.innerHTML = html;
        renderGenericPagination(totalItems, currentPage, limit);
    }

    // Fetch player stats from backend API via AJAX
    function fetchPlayerStats(resetPage) {
        if (resetPage) currentPage = 1;

        var shell = getShell();
        var rowsContainer = (shell && shell.querySelector('#rows')) || document.getElementById('rows');
        if (rowsContainer && cachedPlayersList.length === 0) {
            rowsContainer.innerHTML = `
                <tr class="loading-row">
                    <td colspan="17" class="text-center py-5 text-secondary">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                        Loading player activity &amp; ratings...
                    </td>
                </tr>`;
        }

        if (currentStatsXhr && currentStatsXhr.readyState !== 4) {
            currentStatsXhr.abort();
            currentStatsXhr = null;
        }

        var searchEl = getSearchInput();
        var levelEl = (shell && shell.querySelector('#levelFilter')) || document.getElementById('levelFilter');
        var statusEl = (shell && shell.querySelector('#statusFilter')) || document.getElementById('statusFilter');
        var genderEl = (shell && shell.querySelector('#genderFilter')) || document.getElementById('genderFilter');

        var q = (searchEl?.value || '').trim();
        var level = (levelEl?.value || '').trim();
        var status = (statusEl?.value || '').trim();
        var gender = (genderEl?.value || '').trim();
        var hostId = getCurrentHostId();

        var params = {
            host_id: hostId,
            search: q,
            skill_level: level,
            status: status,
            gender: gender,
            format: 'json'
        };

        var $ = window.jQuery || window.$;
        if ($) {
            currentStatsXhr = $.post(getApiUrl('get-player-stats'), params, function (res) {
                hasInitialLoaded = true;
                if (res && res.success && Array.isArray(res.data)) {
                    cachedPlayersList = res.data;
                    renderTableRows(currentPage, perPage);
                } else {
                    cachedPlayersList = [];
                    renderTableRows(1, perPage);
                }
            }, 'json').fail(function (jqXHR, textStatus) {
                if (textStatus === 'abort') return;
                hasInitialLoaded = true;
                if (rowsContainer) {
                    rowsContainer.innerHTML = '<tr class="no-data-row"><td colspan="17" class="empty text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Unable to load player stats. Please try again.</td></tr>';
                }
                renderGenericPagination(0, 1, perPage);
            });
        } else {
            fetch(getApiUrl('get-player-stats'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(params).toString()
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                hasInitialLoaded = true;
                if (res && res.success && Array.isArray(res.data)) {
                    cachedPlayersList = res.data;
                    renderTableRows(currentPage, perPage);
                } else {
                    cachedPlayersList = [];
                    renderTableRows(1, perPage);
                }
            })
            .catch(function () {
                hasInitialLoaded = true;
                if (rowsContainer) {
                    rowsContainer.innerHTML = '<tr class="no-data-row"><td colspan="17" class="empty text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Unable to load player stats. Please try again.</td></tr>';
                }
                renderGenericPagination(0, 1, perPage);
            });
        }
    }

    // Reusable Helper: Update Modal Header Badges
    function updateModalHeader(pName, curLevel, curRank) {
        var modalTitleEl = document.getElementById('modalTitle');
        if (!modalTitleEl) return;

        var lvl = curLevel || 'Beginner';
        var pillClass = String(lvl).toLowerCase().replace(/[\s+]/g, '');
        var rankBadge = curRank ? `<span class="ms-2 align-middle" style="vertical-align: middle;">${getRankBadgeHtml(curRank)}</span>` : '';

        modalTitleEl.innerHTML = `Update ${escapeHtml(pName)}’s Rating <span class="pill ${pillClass} align-middle ms-2" style="font-size: 0.7rem; vertical-align: middle;">${escapeHtml(lvl)}</span>${rankBadge}`;
    }

    // Populate ranking options (1 to 50) directly in memory without extra network calls
    function populateRankOptions(selectedRank) {
        var newRankEl = document.getElementById('newRank');
        if (!newRankEl) return;

        var total = Math.max(50, parseInt(selectedRank, 10) || 0);
        var opts = '<option value="">Select rank</option>';
        for (var i = 1; i <= total; i++) {
            var sel = (selectedRank == i) ? 'selected' : '';
            opts += `<option value="${i}" ${sel}>${i}</option>`;
        }
        newRankEl.innerHTML = opts;
    }

    // Open Editor Modal with race condition prevention
    function openRatingModal(pId, pName, pStatus) {
        hasSaved = false;
        var ratedPlayerIdInput = document.getElementById('rated_player_id');
        if (ratedPlayerIdInput) ratedPlayerIdInput.value = pId;

        var modalTitleEl = document.getElementById('modalTitle');
        if (modalTitleEl) modalTitleEl.innerHTML = `Update ${escapeHtml(pName)}’s Rating`;

        var modalSubtitleEl = document.getElementById('modalSubtitle');
        if (modalSubtitleEl) modalSubtitleEl.textContent = pStatus || '';

        var feedbackEl = document.getElementById('feedback');
        if (feedbackEl) feedbackEl.innerHTML = '';

        var newLevelEl = document.getElementById('newLevel');
        if (newLevelEl) newLevelEl.value = '';

        var newRankEl = document.getElementById('newRank');
        if (newRankEl) newRankEl.innerHTML = '<option value="">Select rank</option>';

        // Ensure modal is under body so backdrop won't trap it
        var ratingModalEl = document.getElementById('ratingModal');
        if (ratingModalEl && ratingModalEl.parentNode !== document.body) {
            document.body.appendChild(ratingModalEl);
        }

        var modal = getRatingModal();
        if (modal) {
            modal.show();
        } else if (typeof window.jQuery !== 'undefined' && window.jQuery('#ratingModal').modal) {
            window.jQuery('#ratingModal').modal('show');
        } else if (ratingModalEl) {
            ratingModalEl.classList.add('show');
            ratingModalEl.style.display = 'block';
            ratingModalEl.removeAttribute('aria-hidden');
            ratingModalEl.setAttribute('aria-modal', 'true');
            if (!document.getElementById('ratingModalFallbackBackdrop')) {
                var backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.id = 'ratingModalFallbackBackdrop';
                document.body.appendChild(backdrop);
            }
        }

        // Populate rating modal fields directly from cached player list
        var player = cachedPlayersList.find(function (item) {
            return String(item.player_id) === String(pId);
        });

        var curLevel = player ? (player.verified_level || player.skill_level || 'Beginner') : 'Beginner';
        var curRank = player ? (player.current_ranking || player.rank) : null;

        updateModalHeader(pName, curLevel, curRank);

        var newLvl = document.getElementById('newLevel');
        if (newLvl) newLvl.value = curLevel;

        populateRankOptions(curRank);
    }

    // Attach click listeners on edit buttons with full delegation
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.trigger-edit-rating') || e.target.closest('.edit-btn');
        if (btn && btn.dataset && btn.dataset.id) {
            e.preventDefault();
            var pId = btn.dataset.id;
            var pName = btn.dataset.name || btn.closest('tr')?.querySelector('.name')?.textContent?.trim() || '';
            var pStatus = btn.dataset.status || '';
            openRatingModal(pId, pName, pStatus);
        }

        var dismissBtn = e.target.closest('[data-bs-dismiss="modal"]');
        if (dismissBtn) {
            var ratingModalEl = document.getElementById('ratingModal');
            if (ratingModalEl && ratingModalEl.style.display === 'block') {
                ratingModalEl.classList.remove('show');
                ratingModalEl.style.display = 'none';
                var backdrop = document.getElementById('ratingModalFallbackBackdrop');
                if (backdrop) backdrop.remove();
            }
        }

    });

    // Init function
    function init() {
        var ratingModalEl = document.getElementById('ratingModal');
        if (ratingModalEl) {
            // Move modal to body to prevent stacking context clipping
            if (ratingModalEl.parentNode !== document.body) {
                document.body.appendChild(ratingModalEl);
            }

            ratingModalEl.addEventListener('hidden.bs.modal', function () {
                if (hasSaved) {
                    hasSaved = false;
                    fetchPlayerStats(false);
                }
            });
        }

        // Attach debounced search listener (250ms)
        var searchInput = getSearchInput();
        if (searchInput) {
            ['input', 'change', 'keyup', 'search'].forEach(function (evtName) {
                searchInput.addEventListener(evtName, function () {
                    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
                    searchDebounceTimer = setTimeout(function () {
                        fetchPlayerStats(true);
                    }, 250);
                });
            });
        }

        // Attach dropdown change listeners
        var shell = getShell();
        ['levelFilter', 'statusFilter', 'genderFilter'].forEach(function (id) {
            var el = (shell && shell.querySelector('#' + id)) || document.getElementById(id);
            if (el) {
                el.addEventListener('change', function () {
                    fetchPlayerStats(true);
                });
            }
        });

        var newLevelEl = document.getElementById('newLevel');
        if (newLevelEl) {
            newLevelEl.addEventListener('change', function () {
                var curRank = document.getElementById('newRank')?.value;
                populateRankOptions(curRank);
            });
        }

        var ratingFormEl = document.getElementById('ratingForm');
        if (ratingFormEl) {
            ratingFormEl.addEventListener('submit', function (e) {
                e.preventDefault();
                if (isSaving) return;

                var saveBtn = document.getElementById('saveBtn');
                isSaving = true;
                if (saveBtn) {
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Saving...';
                }

                var $ = window.jQuery || window.$;
                var formData = $ ? $(this).serialize() : new FormData(ratingFormEl);

                var onSuccess = function (res) {
                    if (res.success) {
                        hasSaved = true;
                        var feedbackEl = document.getElementById('feedback');
                        if (feedbackEl) {
                            feedbackEl.className = 'small mt-3';
                            feedbackEl.innerHTML = `<div class="alert alert-success py-2 px-3 mb-0 d-flex align-items-center"><i class="bi bi-check-circle-fill me-2 fs-6"></i>${escapeHtml(res.message || 'Rating saved successfully!')}</div>`;
                        }

                        // Refresh background table via AJAX without closing modal
                        fetchPlayerStats(false);

                        var pId = document.getElementById('rated_player_id')?.value;
                        var newLvl = document.getElementById('newLevel')?.value || '';
                        var newRnk = document.getElementById('newRank')?.value || '';
                        var pName = document.querySelector(`.trigger-edit-rating[data-id="${pId}"]`)?.dataset.name || '';
                        updateModalHeader(pName, newLvl, newRnk);
                    } else {
                        var feedbackEl = document.getElementById('feedback');
                        if (feedbackEl) {
                            feedbackEl.className = 'small mt-3';
                            feedbackEl.innerHTML = `<div class="alert alert-danger py-2 px-3 mb-0 d-flex align-items-center"><i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>${escapeHtml(res.message || 'Failed to save rating.')}</div>`;
                        }
                    }
                };

                var onError = function () {
                    var feedbackEl = document.getElementById('feedback');
                    if (feedbackEl) {
                        feedbackEl.className = 'small mt-3';
                        feedbackEl.innerHTML = `<div class="alert alert-danger py-2 px-3 mb-0 d-flex align-items-center"><i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>Server error while saving rating.</div>`;
                    }
                };

                var onComplete = function () {
                    isSaving = false;
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Save Rating';
                    }
                };

                if ($) {
                    $.post(getApiUrl('save-rating-by-host'), formData, onSuccess, 'json')
                        .fail(onError)
                        .always(onComplete);
                }
            });
        }

        // Auto-initialize Casa dropdowns if available
        if (window.CasaDropdown && typeof window.CasaDropdown.initAll === 'function') {
            window.CasaDropdown.initAll(document.querySelector('.player-stats-shell') || document);
        }

        var statsTabBtn = document.getElementById('playerstats-tab');
        if (statsTabBtn) {
            statsTabBtn.addEventListener('shown.bs.tab', function () {
                if (window.CasaDropdown && typeof window.CasaDropdown.initAll === 'function') {
                    window.CasaDropdown.initAll(document.querySelector('.player-stats-shell') || document);
                }
                if (!hasInitialLoaded || cachedPlayersList.length === 0) {
                    fetchPlayerStats(true);
                }
            });
        }

        // Initial fetch
        fetchPlayerStats(true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
