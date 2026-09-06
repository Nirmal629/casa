<?php
/**
 * host-monthly-subscription.php
 * Tab content for the host "Subscription" (monthly) tool.
 * Renders the shell; the player list is loaded via api/host_subscription.php.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Toronto');
$msCurYear  = (int) date('Y');
$msCurMonth = (int) date('n');
$msMonths = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
?>

<style>
    /* ── self-contained: the host dashboard's global form CSS must not leak in ── */
    .msWrap, .msWrap * { box-sizing: border-box; }
    .msWrap {
        font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        color: #1e293b;
        --ms-line: #e6e8ef;
        --ms-accent: #0067b7;       /* the project's own --bs-primary (btn-primary bg) */
        --ms-accent-h: #00568f;
        --ms-ok: #198754;
        --ms-bad: #dc3545;
        --ms-warnbg: #fff3cd;
        --ms-warn: #997404;
    }
    /* hard reset — beat the host page's global input/select/button rules */
    .msWrap input, .msWrap select, .msWrap button, .msWrap textarea {
        margin: 0 !important; box-shadow: none; font-family: inherit;
        line-height: normal; letter-spacing: normal; float: none;
    }
    .msWrap input[type="number"] { -moz-appearance: textfield; appearance: textfield; }
    .msWrap input[type="number"]::-webkit-outer-spin-button,
    .msWrap input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .msWrap .ms-card {
        background: #fff;
        border: 1px solid var(--ms-line);
        border-radius: 16px;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 8px 24px -12px rgba(16, 24, 40, .12);
        overflow: hidden;
    }

    /* header */
    .msWrap .ms-head {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 14px; flex-wrap: wrap;
        padding: 16px 18px;
        background: linear-gradient(180deg, #fbfbfe, #fff);
        border-bottom: 1px solid var(--ms-line);
    }
    .msWrap .ms-title {
        display: flex; align-items: center; gap: 10px;
        font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;
    }
    .msWrap .ms-title .ms-ic {
        width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center;
        background: #e7f1ff; color: var(--ms-accent); flex-shrink: 0;
    }
    .msWrap .ms-sub { font-size: 12px; color: #64748b; margin: 5px 0 0 40px; }
    .msWrap .ms-sub b { color: #334155; }
    .msWrap .ms-btn {
        border: 1px solid var(--ms-line); background: #fff; color: #475569;
        border-radius: 9px; height: 34px; padding: 0 12px; font-size: 12.5px; font-weight: 600;
        display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: .15s;
        white-space: nowrap;
    }
    .msWrap .ms-btn:hover { border-color: #c7cbd6; color: #1e293b; background: #f8fafc; }
    .msWrap .ms-btn:disabled { opacity: .65; cursor: not-allowed; }
    .msWrap .ms-btn.primary { background: var(--ms-accent); border-color: var(--ms-accent); color: #fff; }
    .msWrap .ms-btn.primary:hover { background: var(--ms-accent-h); border-color: var(--ms-accent-h); }
    .msWrap .ms-btn.primary:disabled { background: var(--ms-accent); border-color: var(--ms-accent); }
    .msWrap .ms-btn.danger { border-color: #f1aeb5; color: var(--ms-bad); background: #fff; }
    .msWrap .ms-btn.danger:hover { background: #fdf2f3; border-color: #ea868f; }
    .msWrap .ms-btn.sm { height: 28px; padding: 0 10px; font-size: 11.5px; border-radius: 7px; }

    /* toolbar */
    .msWrap .ms-toolbar {
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        padding: 12px 18px; border-bottom: 1px solid var(--ms-line); background: #fcfcfd;
    }
    .msWrap .ms-select {
        height: 34px; border: 1px solid #d7dbe3; border-radius: 9px;
        padding: 0 30px 0 11px; font-size: 13px; font-weight: 600; color: #334155;
        background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat right 10px center;
        -webkit-appearance: none; -moz-appearance: none; appearance: none; cursor: pointer;
    }
    .msWrap .ms-select:focus { outline: none; border-color: var(--ms-accent); box-shadow: 0 0 0 3px rgba(0, 103, 183, .18); }
    .msWrap .ms-pill {
        display: inline-flex; align-items: center; gap: 5px;
        background: #f1f5f9; color: #475569;
        border-radius: 999px; padding: 4px 11px; font-size: 11.5px; font-weight: 700;
        white-space: nowrap;
    }
    .msWrap .ms-pill.green { background: #d1e7dd; color: var(--ms-ok); }
    .msWrap .ms-pill.amber { background: var(--ms-warnbg); color: var(--ms-warn); }
    .msWrap .ms-pill .dot { width: 6px; height: 6px; border-radius: 999px; background: currentColor; }
    .msWrap .ms-stats { margin-left: auto; display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
    .msWrap .ms-stat {
        display: inline-flex; align-items: baseline; gap: 5px;
        border: 1px solid var(--ms-line); border-radius: 10px; padding: 5px 11px; background: #fff;
        font-size: 11px; color: #64748b; font-weight: 600;
    }
    .msWrap .ms-stat b { font-size: 14px; color: #0f172a; font-weight: 800; }
    .msWrap .ms-stat.green b { color: var(--ms-ok); }
    .msWrap .ms-stat.accent b { color: var(--ms-accent); }

    /* bulk bar */
    .msWrap .ms-bulk {
        display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
        padding: 12px 18px; background: #f8fafc; border-bottom: 1px solid var(--ms-line);
    }
    .msWrap .ms-check { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 600; color: #334155; cursor: pointer; user-select: none; }
    .msWrap .ms-check input { width: 15px !important; height: 15px !important; accent-color: var(--ms-accent); cursor: pointer; }
    /* amount field: ONE hard-styled <input> + an absolute "$" overlay.
       No inner flex row to be broken by the host page's global input CSS. */
    .msWrap .ms-money { position: relative; display: inline-block; vertical-align: middle; width: 150px; }
    .msWrap .ms-money.wide { width: 190px; }
    .msWrap .ms-money.row  { width: 128px; }
    .msWrap .ms-money > .cur {
        position: absolute; left: 1px; top: 1px; bottom: 1px; width: 26px;
        display: flex; align-items: center; justify-content: center;
        background: #f1f5f9; color: #64748b; font-weight: 800; font-size: 12px;
        border-right: 1px solid #e2e8f0; border-radius: 8px 0 0 8px;
        pointer-events: none;
    }
    .msWrap .ms-money > input {
        display: block !important; box-sizing: border-box !important;
        width: 100% !important; height: 34px !important; line-height: 34px !important;
        margin: 0 !important; padding: 0 10px 0 34px !important;
        border: 1px solid #d7dbe3 !important; border-radius: 8px !important;
        background: #fff !important; box-shadow: none !important; outline: none !important;
        font-size: 13px !important; font-weight: 600 !important; color: #0f172a !important;
        -webkit-appearance: none !important; -moz-appearance: textfield !important; appearance: none !important;
        min-width: 0 !important; float: none !important; position: static !important;
    }
    .msWrap .ms-money > input:focus {
        border-color: var(--ms-accent) !important; box-shadow: 0 0 0 3px rgba(0, 103, 183, .18) !important;
    }
    .msWrap .ms-selcount { font-size: 12px; color: #94a3b8; font-weight: 600; }

    /* table */
    .msWrap .ms-body { padding: 4px 2px; }
    .msWrap .ms-tablewrap { overflow-x: auto; }
    .msWrap table.ms-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
    .msWrap .ms-table thead th {
        text-align: left; font-size: 10.5px; letter-spacing: .04em; text-transform: uppercase;
        font-weight: 800; color: #94a3b8; padding: 10px 14px; background: #fff;
        border-bottom: 1px solid var(--ms-line); position: sticky; top: 0; white-space: nowrap;
    }
    .msWrap .ms-table tbody td { padding: 11px 14px; border-bottom: 1px solid #f1f2f6; vertical-align: middle; }
    .msWrap .ms-table tbody tr:last-child td { border-bottom: 0; }
    .msWrap .ms-table tbody tr:hover td { background: #f9fafe; }
    .msWrap .ms-table tr.applied td { background: #f2fdf6; }
    .msWrap .ms-table tr.applied:hover td { background: #ecfbf1; }
    .msWrap .ms-c { text-align: center; }
    .msWrap .ms-r { text-align: right; }
    .msWrap .ms-player-name { font-weight: 700; color: #1e293b; }
    .msWrap .ms-player-meta { font-size: 11px; color: #94a3b8; margin-top: 1px; }
    .msWrap .ms-games {
        display: inline-flex; align-items: center; justify-content: center; min-width: 30px;
        height: 22px; padding: 0 8px; border-radius: 999px;
        background: #cfe2ff; color: #084298; font-weight: 800; font-size: 12px;
    }
    .msWrap .ms-amt-applied { font-weight: 800; color: var(--ms-ok); font-size: 13.5px; }
    .msWrap .ms-tag {
        display: inline-flex; align-items: center; gap: 4px; font-size: 9.5px; font-weight: 900;
        letter-spacing: .04em; padding: 2px 7px; border-radius: 6px; margin-left: 6px; vertical-align: middle;
    }
    .msWrap .ms-tag.lock { background: var(--ms-warnbg); color: var(--ms-warn); }
    .msWrap .ms-tag.unlock { background: #e2e8f0; color: #475569; }
    .msWrap .ms-actions { display: inline-flex; gap: 6px; justify-content: flex-end; align-items: center; }

    .msWrap .ms-loading, .msWrap .ms-empty { text-align: center; padding: 44px 20px; color: #94a3b8; }
    .msWrap .ms-empty .ms-empty-ic {
        width: 52px; height: 52px; border-radius: 14px; margin: 0 auto 10px; display: grid; place-items: center;
        background: #e7f1ff; color: var(--ms-accent);
    }
    .msWrap .ms-cb { width: 15px !important; height: 15px !important; accent-color: var(--ms-accent); cursor: pointer; vertical-align: middle; }
    .msWrap .ms-empty h4 { font-size: 14px; font-weight: 800; color: #334155; margin: 0 0 3px; }
    .msWrap .ms-empty p { font-size: 12.5px; margin: 0; }
    .msWrap .ms-spin {
        width: 26px; height: 26px; border: 3px solid #e2e8f0; border-top-color: var(--ms-accent);
        border-radius: 999px; margin: 0 auto 10px; animation: msspin .7s linear infinite;
    }
    @keyframes msspin { to { transform: rotate(360deg); } }
    .msWrap .ms-note { display: none; margin: 6px 14px 12px; font-size: 11.5px; color: #64748b; }
    .msWrap .ms-note.show { display: block; }
</style>

<div class="msWrap">
    <div class="ms-card">

        <div class="ms-head">
            <div>
                <h6 class="ms-title">
                    <span class="ms-ic">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18M8 2v4M16 2v4"/><path d="m9 16 2 2 4-4"/></svg>
                    </span>
                    Monthly Subscription
                </h6>
                <div class="ms-sub">Players who played <b>2 or more</b> completed games in the selected month.</div>
            </div>
            <button id="msRefresh" class="ms-btn" type="button" title="Refresh">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                Refresh
            </button>
        </div>

        <div class="ms-toolbar">
            <select id="msYear" class="ms-select">
                <?php for ($y = 2025; $y <= $msCurYear + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= $y === $msCurYear ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <select id="msMonth" class="ms-select">
                <?php foreach ($msMonths as $n => $nm): ?>
                    <option value="<?= $n ?>" <?= $n === $msCurMonth ? 'selected' : '' ?>><?= $nm ?></option>
                <?php endforeach; ?>
            </select>
            <span id="msPeriodBadge" class="ms-pill"><span class="dot"></span>current month</span>
            <div class="ms-stats" id="msSummary"></div>
        </div>

        <div class="ms-bulk">
            <label class="ms-check"><input type="checkbox" id="msSelectAll"> Select all eligible</label>
            <div class="ms-money wide">
                <span class="cur">$</span>
                <input type="number" min="0" step="0.01" id="msBulkAmount" placeholder="Amount for selected">
            </div>
            <button id="msApplySelected" class="ms-btn primary" type="button" disabled>Apply to selected</button>
            <span class="ms-selcount" id="msSelCount">0 selected</span>
        </div>

        <div class="ms-note" id="msNote"></div>

        <div class="ms-body" id="msTableWrap">
            <div class="ms-loading"><div class="ms-spin"></div>Loading players…</div>
        </div>
    </div>
</div>

<script>
(function () {
    "use strict";
    var API = 'api/host_subscription.php';
    var wrap = document.querySelector('.msWrap');
    if (!wrap || wrap.dataset.wired) return;
    wrap.dataset.wired = '1';

    var $ = function (s) { return wrap.querySelector(s); };
    var elYear = $('#msYear'), elMonth = $('#msMonth'), elTable = $('#msTableWrap'),
        elSummary = $('#msSummary'), elBadge = $('#msPeriodBadge'), elNote = $('#msNote'),
        elSelAll = $('#msSelectAll'), elBulk = $('#msBulkAmount'),
        elApplySel = $('#msApplySelected'), elSelCount = $('#msSelCount');

    var busy = false;
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]); }); }
    function money(n) { return '$' + Number(n || 0).toFixed(2); }

    async function apiCall(action, opts) {
        opts = opts || {};
        var url = API + '?action=' + action;
        var init = { credentials: 'same-origin' };
        if (opts.body) { init.method = 'POST'; init.headers = { 'Content-Type': 'application/json' }; init.body = JSON.stringify(opts.body); }
        else if (opts.form) { init.method = 'POST'; init.headers = { 'Content-Type': 'application/x-www-form-urlencoded' }; init.body = new URLSearchParams(opts.form).toString(); }
        var res = await fetch(url, init);
        var j = await res.json().catch(function () { return { error: 'bad', message: 'Bad response' }; });
        if (res.status === 401 || res.status === 403) { elTable.innerHTML = noteBox(j.message || 'Not permitted.'); throw new Error('forbidden'); }
        j.__status = res.status;
        return j;
    }
    function noteBox(msg) { return '<div class="ms-empty"><h4>Heads up</h4><p>' + esc(msg) + '</p></div>'; }

    function periodBadge(p) {
        elBadge.className = 'ms-pill' + (p === 'past' ? ' green' : p === 'future' ? ' amber' : '');
        elBadge.innerHTML = '<span class="dot"></span>' + (p === 'current' ? 'current month' : p === 'future' ? 'future month' : 'past month');
    }
    function renderSummary(s) {
        elSummary.innerHTML =
            '<span class="ms-stat"><b>' + s.eligible + '</b> eligible</span>' +
            '<span class="ms-stat green"><b>' + s.applied + '</b> applied</span>' +
            '<span class="ms-stat accent"><b>' + money(s.applied_total) + '</b> billed</span>';
    }

    function rowHtml(p) {
        var sub = p.subscription;
        var applied = sub && sub.status === 'APPLIED';
        var locked = applied && sub.is_locked;
        var cb = applied
            ? '<span style="color:#cbd5e1;font-weight:700;">✓</span>'
            : '<input type="checkbox" class="ms-cb" data-pid="' + p.player_id + '">';
        var amtCell, actionCell;
        if (applied) {
            amtCell = '<span class="ms-amt-applied">' + money(sub.amount) + '</span>'
                + (locked ? '<span class="ms-tag lock">LOCKED</span>' : '<span class="ms-tag unlock">UNLOCKED</span>');
            actionCell = locked
                ? '<button class="ms-btn sm ms-act" data-act="unlock" data-id="' + sub.id + '" type="button">Unlock</button>'
                : '<button class="ms-btn sm danger ms-act" data-act="rollback" data-id="' + sub.id + '" type="button">Rollback</button>'
                  + '<button class="ms-btn sm ms-act" data-act="lock" data-id="' + sub.id + '" type="button">Re-lock</button>';
        } else {
            amtCell = '<div class="ms-money row"><span class="cur">$</span>'
                + '<input type="number" min="0" step="0.01" class="ms-row-amt" data-pid="' + p.player_id + '" placeholder="0.00"></div>';
            actionCell = '<button class="ms-btn sm primary ms-apply-one" data-pid="' + p.player_id + '" type="button">Apply</button>';
        }
        return '<tr class="' + (applied ? 'applied' : '') + '">'
            + '<td class="ms-c">' + cb + '</td>'
            + '<td><div class="ms-player-name">' + esc(p.name) + '</div>'
            + '<div class="ms-player-meta">' + esc(p.email || ('#' + p.player_id)) + (p.phone ? ' · ' + esc(p.phone) : '') + '</div></td>'
            + '<td class="ms-c"><span class="ms-games">' + p.games + '</span></td>'
            + '<td class="ms-c" style="color:#64748b;">' + money(p.games_amount) + '</td>'
            + '<td>' + amtCell + '</td>'
            + '<td class="ms-r"><div class="ms-actions">' + actionCell + '</div></td>'
            + '</tr>';
    }

    function render(d) {
        periodBadge(d.period);
        renderSummary(d.summary);
        elNote.className = 'ms-note' + (d.period === 'current' ? ' show' : '');
        if (d.period === 'current') elNote.textContent = 'This month is still running — game counts will keep changing until it ends.';
        if (!d.players.length) {
            elTable.innerHTML = '<div class="ms-empty">'
                + '<div class="ms-empty-ic"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18M8 2v4M16 2v4"/></svg></div>'
                + '<h4>No eligible players</h4>'
                + '<p>No one played 2+ completed games in ' + esc(elMonth.selectedOptions[0].text) + ' ' + esc(elYear.value) + '. Try another month.</p></div>';
            syncSelCount();
            return;
        }
        elTable.innerHTML =
            '<div class="ms-tablewrap"><table class="ms-table"><thead><tr>'
            + '<th class="ms-c" style="width:44px;"></th><th>Player</th>'
            + '<th class="ms-c">Games</th><th class="ms-c">Games value</th>'
            + '<th style="width:210px;">Subscription</th><th class="ms-r" style="width:180px;">Action</th>'
            + '</tr></thead><tbody>' + d.players.map(rowHtml).join('') + '</tbody></table></div>';
        wireRows();
        syncSelCount();
    }

    function currentPeriod() { return { year: +elYear.value, month: +elMonth.value }; }

    async function load() {
        elSelAll.checked = false;
        elTable.innerHTML = '<div class="ms-loading"><div class="ms-spin"></div>Loading…</div>';
        try {
            var p = currentPeriod();
            var d = await apiCall('list', { form: { year: p.year, month: p.month } });
            if (d.error) { elTable.innerHTML = noteBox(d.message || 'Could not load.'); return; }
            render(d);
        } catch (e) { /* forbidden already shown */ }
    }

    function selectedPids() {
        return Array.prototype.map.call(wrap.querySelectorAll('.ms-cb:checked'), function (c) { return +c.dataset.pid; });
    }
    function syncSelCount() {
        var n = selectedPids().length;
        elSelCount.textContent = n + ' selected';
        elApplySel.disabled = n === 0 || busy;
    }
    function rowAmount(pid) {
        var i = wrap.querySelector('.ms-row-amt[data-pid="' + pid + '"]');
        return i ? parseFloat(i.value) : NaN;
    }

    async function doApply(items) {
        items = items.filter(function (it) { return it.player_id && it.amount > 0; });
        if (!items.length) { alert('Enter an amount (greater than 0) for the selected players.'); return; }
        if (busy) return;
        busy = true; syncSelCount();
        var p = currentPeriod();
        try {
            var d = await apiCall('apply', { body: { year: p.year, month: p.month, items: items } });
            if (d.error) { alert(d.message || 'Apply failed'); }
            else {
                var fails = Object.keys(d.results || {}).filter(function (k) { return !d.results[k].ok; })
                    .map(function (k) { return d.results[k].message; });
                if (fails.length) alert(d.message + '\n\nNot applied:\n• ' + fails.join('\n• '));
            }
        } catch (e) {} finally { busy = false; elBulk.value = ''; await load(); }
    }

    async function doRowAction(act, id) {
        if (busy) return;
        if (act === 'rollback' && !confirm('Roll back this subscription? It will be removed from that month\'s ledger.')) return;
        busy = true;
        try {
            var d = await apiCall(act, { form: { id: id } });
            if (d.error || d.__status >= 400) alert(d.message || 'Action failed');
        } catch (e) {} finally { busy = false; await load(); }
    }

    function wireRows() {
        Array.prototype.forEach.call(wrap.querySelectorAll('.ms-cb'), function (c) {
            c.addEventListener('change', syncSelCount);
        });
        Array.prototype.forEach.call(wrap.querySelectorAll('.ms-apply-one'), function (b) {
            b.addEventListener('click', function () {
                var pid = +b.dataset.pid;
                doApply([{ player_id: pid, amount: rowAmount(pid) }]);
            });
        });
        Array.prototype.forEach.call(wrap.querySelectorAll('.ms-act'), function (b) {
            b.addEventListener('click', function () { doRowAction(b.dataset.act, +b.dataset.id); });
        });
    }

    elSelAll.addEventListener('change', function () {
        Array.prototype.forEach.call(wrap.querySelectorAll('.ms-cb'), function (c) { c.checked = elSelAll.checked; });
        syncSelCount();
    });
    // typing the bulk amount fills every row's field — host can then tweak individuals
    elBulk.addEventListener('input', function () {
        var v = elBulk.value;
        Array.prototype.forEach.call(wrap.querySelectorAll('.ms-row-amt'), function (i) { i.value = v; });
    });
    elApplySel.addEventListener('click', function () {
        var items = selectedPids().map(function (pid) {
            return { player_id: pid, amount: rowAmount(pid) };
        });
        doApply(items);
    });
    elYear.addEventListener('change', load);
    elMonth.addEventListener('change', load);
    $('#msRefresh').addEventListener('click', load);

    // load when the tab is first shown (and once now in case it's already active)
    var tabBtn = document.querySelector('[data-bs-target="#MonthlySub"]');
    if (tabBtn) tabBtn.addEventListener('shown.bs.tab', function () { if (!elTable.dataset.loaded) { elTable.dataset.loaded = '1'; load(); } });
    if (document.getElementById('MonthlySub') && document.getElementById('MonthlySub').classList.contains('active')) load();
    else if (tabBtn && tabBtn.classList.contains('active')) load();
})();
</script>
