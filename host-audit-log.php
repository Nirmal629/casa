<?php
/**
 * host-audit-log.php — Host-only User Activity Analytics with full drill-down.
 *
 * Route-level authorization runs BEFORE any output so the redirect is real.
 * Every api/audit/* endpoint re-enforces Host-only + host-scoping server-side.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || strcasecmp(trim($_SESSION['usertype'] ?? ''), 'Host') !== 0) {
    header('Location: index.php');
    exit;
}

include "includes/inner-header.php";
$HOST_NAME = htmlspecialchars($_SESSION['name'] ?? 'Host');
?>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<style>
    #auditRoot, .audit-layer { font-family: 'Inter', 'DM Sans', system-ui, -apple-system, "Segoe UI", sans-serif; color: #0f172a; }
    #auditRoot *, #auditRoot *::before, #auditRoot *::after,
    .audit-layer *, .audit-layer *::before, .audit-layer *::after { box-sizing: border-box; }
    .apexcharts-tooltip { font-size: 12px; }
    #auditRoot table, .audit-layer table { border-collapse: separate; border-spacing: 0; }
    .audit-scrollbar::-webkit-scrollbar { width: 8px; height: 8px; }
    .audit-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
    .audit-slide { transition: transform .26s cubic-bezier(.4,0,.2,1); }
    .audit-clickable { cursor: pointer; }
    .apexcharts-bar-area, .apexcharts-pie-area { cursor: pointer; }
    @keyframes auditFade { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
    .audit-fade { animation: auditFade .3s ease both; }
</style>

<section class="bothSide_gap">
    <div class="cust_container">
        <div id="auditRoot" class="max-w-[1400px] mx-auto">

            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3 mb-3">
                <div>
                    <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wider text-teal-600 mb-1">
                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-teal-50 text-teal-600">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                        </span>Host Analytics
                    </div>
                    <h1 class="text-2xl font-extrabold text-slate-900 m-0">Audit Log &amp; User Activity</h1>
                    <p class="text-[13px] text-slate-500 mt-1 mb-0">What players actually do with <span class="font-semibold text-slate-700"><?= $HOST_NAME ?></span>'s games — click any number, chart or user to investigate.</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <div id="rangeChips" class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 text-xs font-semibold shadow-sm">
                        <button data-range="today" class="px-2.5 py-1.5 rounded-md text-slate-500">Today</button>
                        <button data-range="7d"   class="px-2.5 py-1.5 rounded-md text-slate-500">7&nbsp;days</button>
                        <button data-range="30d"  class="px-2.5 py-1.5 rounded-md text-slate-500">30&nbsp;days</button>
                        <button data-range="90d"  class="px-2.5 py-1.5 rounded-md text-slate-500">90&nbsp;days</button>
                        <button data-range="all"  class="px-2.5 py-1.5 rounded-md bg-teal-600 text-white">All&nbsp;time</button>
                        <button data-range="custom" class="px-2.5 py-1.5 rounded-md text-slate-500">Custom</button>
                    </div>
                    <div id="customRange" class="hidden items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs shadow-sm">
                        <input type="date" id="crFrom" class="h-[26px] bg-slate-50 border border-slate-200 rounded px-1.5 text-[11px] text-slate-700 outline-none">
                        <span class="text-slate-400">to</span>
                        <input type="date" id="crTo" class="h-[26px] bg-slate-50 border border-slate-200 rounded px-1.5 text-[11px] text-slate-700 outline-none">
                        <button id="crApply" class="h-[26px] px-2 rounded bg-teal-600 text-white text-[11px] font-semibold">Apply</button>
                    </div>
                    <button id="refreshBtn" title="Refresh" class="inline-flex items-center justify-center h-[34px] w-[34px] rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-teal-600 hover:border-teal-300 shadow-sm">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                    </button>
                </div>
            </div>

            <!-- Breadcrumb / context -->
            <div id="breadcrumb" class="hidden items-center flex-wrap gap-1.5 text-[12px] mb-3 rounded-lg bg-slate-100/70 border border-slate-200 px-3 py-2"></div>

            <!-- KPI cards -->
            <div id="kpiRow" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 mb-4">
                <div class="h-[96px] rounded-xl bg-white border border-slate-200 animate-pulse"></div>
                <div class="h-[96px] rounded-xl bg-white border border-slate-200 animate-pulse"></div>
                <div class="h-[96px] rounded-xl bg-white border border-slate-200 animate-pulse"></div>
                <div class="h-[96px] rounded-xl bg-white border border-slate-200 animate-pulse"></div>
                <div class="h-[96px] rounded-xl bg-white border border-slate-200 animate-pulse"></div>
                <div class="h-[96px] rounded-xl bg-white border border-slate-200 animate-pulse"></div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-4">
                <div class="xl:col-span-2 rounded-xl bg-white border border-slate-200 shadow-sm p-4">
                    <div class="flex items-center justify-between mb-1">
                        <h2 class="text-sm font-bold text-slate-800 m-0">User activity trend</h2>
                        <span class="text-[11px] text-slate-400" id="trendRangeLabel"></span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-0 mb-1">Click a point to see who was active that day</p>
                    <div id="chartTrend" class="min-h-[260px]"></div>
                </div>
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4">
                    <h2 class="text-sm font-bold text-slate-800 mb-1 mt-0">Behaviour distribution</h2>
                    <p class="text-[11px] text-slate-400 mt-0 mb-1">Click a slice to filter the table</p>
                    <div id="chartBehaviour" class="min-h-[260px]"></div>
                </div>
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4">
                    <h2 class="text-sm font-bold text-slate-800 mb-1 mt-0">Game engagement</h2>
                    <p class="text-[11px] text-slate-400 mt-0 mb-1"><span id="engRangeLabel"></span> · click a bar for the top games</p>
                    <div id="chartEngagement" class="min-h-[240px]"></div>
                </div>
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4">
                    <h2 class="text-sm font-bold text-slate-800 mb-1 mt-0">View → Join funnel</h2>
                    <p class="text-[11px] text-slate-400 mt-0 mb-1" id="funnelRangeLabel"></p>
                    <div id="funnel" class="pt-2"></div>
                </div>
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4">
                    <h2 class="text-sm font-bold text-slate-800 mb-1 mt-0">Activity by time of day</h2>
                    <p class="text-[11px] text-slate-400 mt-0 mb-1">America/Toronto</p>
                    <div id="chartTime" class="min-h-[240px]"></div>
                </div>
            </div>

            <!-- User table -->
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden" id="userTableCard">
                <div class="p-3 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 m-0">User activity</h2>
                        <p class="text-[11px] text-slate-500 mt-0.5 mb-0" id="tableMeta">&nbsp;</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <input id="searchInput" type="text" placeholder="Search user / email / id"
                                   class="h-[34px] w-[200px] bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 text-xs outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500">
                        </div>
                        <select id="behaviourFilter" class="h-[34px] bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-teal-500 cursor-pointer">
                            <option value="all">All behaviours</option>
                            <option value="Highly Engaged">Highly Engaged</option>
                            <option value="Returning">Returning</option>
                            <option value="Browsing Only">Browsing Only</option>
                            <option value="High Abandonment">High Abandonment</option>
                            <option value="Low Activity">Low Activity</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                        <select id="sortSelect" class="h-[34px] bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-teal-500 cursor-pointer">
                            <option value="last_active|desc">Recently active</option>
                            <option value="engagement|desc">Engagement ↓</option>
                            <option value="joins|desc">Joins ↓</option>
                            <option value="views|desc">Views ↓</option>
                            <option value="completed|desc">Completed ↓</option>
                            <option value="abandoned|desc">Abandoned ↓</option>
                            <option value="name|asc">Name A–Z</option>
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto audit-scrollbar">
                    <table class="w-full text-sm min-w-[900px]">
                        <thead>
                            <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500 bg-slate-50/70 border-b border-slate-200">
                                <th class="px-4 py-2.5 font-bold">User</th>
                                <th class="px-3 py-2.5 font-bold">Behaviour</th>
                                <th class="px-3 py-2.5 font-bold">Last active</th>
                                <th class="px-3 py-2.5 font-bold text-center">Viewed</th>
                                <th class="px-3 py-2.5 font-bold text-center">Joined</th>
                                <th class="px-3 py-2.5 font-bold text-center">Completed</th>
                                <th class="px-3 py-2.5 font-bold text-center">Abandoned</th>
                                <th class="px-3 py-2.5 font-bold">Engagement</th>
                                <th class="px-3 py-2.5 font-bold text-right">Journey</th>
                            </tr>
                        </thead>
                        <tbody id="userRows" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
                <div id="tablePager" class="flex items-center justify-between gap-2 p-3 border-t border-slate-100 text-xs text-slate-500"></div>
            </div>

            <p class="text-[11px] text-slate-400 mt-3">
                View &amp; browse counters started when this feature was enabled and grow as players use the platform. Joins, completions and drop-off are computed from your full game history.
            </p>
        </div>
    </div>
</section>

<!-- ============ LAYER 1 · big user modal ============ -->
<div id="umOverlay" class="audit-layer fixed inset-0 bg-slate-900/50 z-[1200] hidden"></div>
<div id="umModal" class="audit-layer audit-slide fixed inset-y-0 right-0 w-full max-w-[900px] bg-slate-50 z-[1201] shadow-2xl translate-x-full flex flex-col">
    <div id="umHeader" class="shrink-0 bg-white border-b border-slate-200 px-5 py-3.5"></div>
    <div id="umBody" class="flex-1 overflow-y-auto audit-scrollbar px-5 py-4 space-y-4"></div>
</div>

<!-- ============ LAYER 2 · list drill panel (game→users / day→users) ============ -->
<div id="dpOverlay" class="audit-layer fixed inset-0 bg-slate-900/50 z-[1300] hidden"></div>
<div id="dpPanel" class="audit-layer audit-slide fixed inset-y-0 right-0 w-full max-w-[520px] bg-white z-[1301] shadow-2xl translate-x-full flex flex-col">
    <div id="dpHeader" class="shrink-0 border-b border-slate-200 px-4 py-3"></div>
    <div id="dpBody" class="flex-1 overflow-y-auto audit-scrollbar px-4 py-3"></div>
    <div id="dpPager" class="shrink-0 border-t border-slate-100 px-4 py-2.5 text-xs text-slate-500 flex items-center justify-between"></div>
</div>

<!-- ============ LAYER 3 · mini card (event detail / user↔game) ============ -->
<div id="mpOverlay" class="audit-layer fixed inset-0 bg-slate-900/50 z-[1400] hidden grid place-items-center p-4">
    <div id="mpCard" class="bg-white rounded-2xl shadow-2xl w-full max-w-[440px] max-h-[85vh] overflow-y-auto audit-scrollbar audit-fade"></div>
</div>

<script>
(function () {
    "use strict";
    const API = 'api/audit/';
    const $  = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

    const RANGE_NAME = { today:'today', yesterday:'yesterday', '7d':'last 7 days', '30d':'last 30 days', '90d':'last 90 days', all:'all time', custom:'custom range' };
    const state = {
        range: 'all', cFrom: '', cTo: '',
        search: '', behaviour: 'all', sort: 'last_active', dir: 'desc',
        page: 1, perPage: 20,
        um: null,        // { userId, tlPage, tlActivity, tlGame, gamesPage }
        dp: null,        // { kind:'game'|'day', id/date, page, sort }
    };
    // params for any range-aware endpoint
    function rp(extra){
        const p = { range: state.range, ...(extra || {}) };
        if (state.range === 'custom') { p.from = state.cFrom; p.to = state.cTo; }
        return p;
    }
    function rangeLabel(){
        if (state.range === 'custom' && state.cFrom && state.cTo) return `${state.cFrom} → ${state.cTo}`;
        return RANGE_NAME[state.range] || state.range;
    }
    const charts = {};

    const TONE = {
        green:{chip:'bg-emerald-50 text-emerald-700 ring-emerald-600/20',dot:'#059669'},
        red:{chip:'bg-red-50 text-red-700 ring-red-600/20',dot:'#dc2626'},
        amber:{chip:'bg-amber-50 text-amber-700 ring-amber-600/20',dot:'#d97706'},
        blue:{chip:'bg-blue-50 text-blue-700 ring-blue-600/20',dot:'#2563eb'},
        neutral:{chip:'bg-slate-100 text-slate-600 ring-slate-500/20',dot:'#64748b'},
    };
    const BEHAVIOUR_TONE = { 'Highly Engaged':'green','Returning':'blue','Browsing Only':'amber','High Abandonment':'red','Low Activity':'neutral','Inactive':'neutral' };
    const PALETTE = ['#0d9488','#2563eb','#f59e0b','#dc2626','#94a3b8','#64748b'];

    /* ---------- helpers ---------- */
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const nfmt = n => (n == null ? '–' : Intl.NumberFormat().format(n));
    const pct  = n => (n == null ? '–' : n + '%');
    function parseDate(iso){ return iso ? new Date(String(iso).replace(' ','T')) : null; }
    function timeAgo(iso){
        const d = parseDate(iso); if (!d) return 'never';
        const s = (Date.now() - d.getTime())/1000;
        if (s < 60) return 'just now';
        if (s < 3600) return Math.floor(s/60)+'m ago';
        if (s < 86400) return Math.floor(s/3600)+'h ago';
        if (s < 2592000) return Math.floor(s/86400)+'d ago';
        return d.toLocaleDateString(undefined,{month:'short',day:'numeric',year:'numeric'});
    }
    const fmtDateTime = iso => { const d=parseDate(iso); return d ? d.toLocaleString(undefined,{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit'}) : '–'; };
    const fmtDate = iso => { const d=parseDate(iso); return d ? d.toLocaleDateString(undefined,{month:'short',day:'numeric',year:'numeric'}) : '–'; };
    const fmtTime = iso => { const d=parseDate(iso); return d ? d.toLocaleTimeString(undefined,{hour:'numeric',minute:'2-digit'}) : ''; };
    function dayLabel(iso){
        const d = parseDate(iso), t = new Date(); const same = x => x.toDateString();
        if (same(d)===same(t)) return 'Today';
        t.setDate(t.getDate()-1);
        if (same(d)===same(t)) return 'Yesterday';
        return d.toLocaleDateString(undefined,{weekday:'short',month:'short',day:'numeric',year:'numeric'});
    }
    function mins(m){ if (m==null) return '–'; if (m<60) return m+'m'; const h=Math.floor(m/60); return h+'h '+(m%60)+'m'; }
    async function api(path, params){
        const qs = new URLSearchParams(Object.fromEntries(Object.entries(params||{}).filter(([,v])=>v!=null&&v!==''))).toString();
        const res = await fetch(API + path + (qs?'?'+qs:''), { credentials:'same-origin' });
        if (res.status === 401 || res.status === 403) {
            const j = await res.json().catch(()=>({}));
            throw Object.assign(new Error('forbidden'), { forbidden:true, message:j.message });
        }
        return res.json();
    }
    function behaviourChip(b){
        const t = TONE[BEHAVIOUR_TONE[b]||'neutral'];
        return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset ${t.chip}">
            <span class="h-1.5 w-1.5 rounded-full" style="background:${t.dot}"></span>${esc(b)}</span>`;
    }
    function avatar(u, size = 8){
        const initial = esc((u.name||'?').trim().charAt(0).toUpperCase());
        const cls = `h-${size} w-${size} rounded-full`;
        return u.avatar
            ? `<img src="${esc(u.avatar)}" class="${cls} object-cover ring-1 ring-slate-200" onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'${cls} bg-teal-50 text-teal-700 grid place-items-center text-xs font-bold',textContent:'${initial}'}))">`
            : `<div class="${cls} bg-teal-50 text-teal-700 grid place-items-center text-xs font-bold">${initial}</div>`;
    }
    function statusPill(s){
        if (!s) return '';
        const m = { Completed:'green', Active:'blue', Cancelled:'red', Inactive:'neutral' };
        const t = TONE[m[s]||'neutral'];
        return `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide ring-1 ring-inset ${t.chip}">${esc(s)}</span>`;
    }
    function empty(msg){ return `<div class="py-10 text-center text-slate-400 text-sm">${esc(msg)}</div>`; }
    function pager(p, onGo){
        const { page, pages, total } = p;
        const btn = (lbl, to, on) => `<button data-to="${to}" ${on?'':'disabled'} class="pgb px-2 py-1 rounded-md border border-slate-200 font-semibold ${on?'text-slate-600 hover:bg-slate-50':'text-slate-300'}">${lbl}</button>`;
        const el = document.createElement('div');
        el.className = 'flex items-center justify-between gap-2 w-full';
        el.innerHTML = `<span>${nfmt(total)} total · page ${page}/${pages}</span>
            <span class="flex gap-1">${btn('« First',1,page>1)}${btn('‹ Prev',page-1,page>1)}${btn('Next ›',page+1,page<pages)}${btn('Last »',pages,page<pages)}</span>`;
        el.querySelectorAll('.pgb').forEach(b => b.onclick = () => { if (!b.disabled) onGo(+b.dataset.to); });
        return el;
    }

    /* ================= URL / breadcrumb ================= */
    function syncUrl(push){
        const p = new URLSearchParams();
        if (state.range !== 'all') p.set('range', state.range);
        if (state.range === 'custom' && state.cFrom && state.cTo) { p.set('from', state.cFrom); p.set('to', state.cTo); }
        if (state.behaviour !== 'all') p.set('behaviour', state.behaviour);
        if (state.um) p.set('user', state.um.userId);
        if (state.um && state.um.tlGame) p.set('game', state.um.tlGame);
        if (!state.um && state.dp && state.dp.kind === 'game') p.set('game', state.dp.id);
        if (!state.um && state.dp && state.dp.kind === 'day') p.set('day', state.dp.date);
        const url = location.pathname + (p.toString() ? '?' + p.toString() : '');
        try { if (push) history.pushState({}, '', url); else history.replaceState({}, '', url); } catch (e) { /* sandboxed / file:// */ }
        renderBreadcrumb();
    }
    function dpLabel(dp){
        if (!dp) return null;
        if (dp.kind === 'game')     return 'Game — ' + (dp.title || '#' + dp.id);
        if (dp.kind === 'day')      return 'Active — ' + dp.date;
        if (dp.kind === 'topgames') return 'Top games — ' + dp.metric;
        return null;
    }
    function renderBreadcrumb(){
        const bc = $('#breadcrumb');
        const parts = [];
        parts.push({ label: rangeLabel(), act: () => { closeAll(); } });
        if (state.behaviour !== 'all') parts.push({ label: state.behaviour, act: () => { closeAll(); } });
        if (!state.um && state.dp) {
            const l = dpLabel(state.dp);
            if (l) parts.push({ label: l, act: null });
        }
        if (state.um) {
            const l = dpLabel(state.dp);
            if (l) parts.push({ label: l, act: () => { closeUserModal(); } });
            parts.push({ label: state.um.name || ('User #' + state.um.userId), act: null });
        }
        if (parts.length <= 1) { bc.classList.add('hidden'); return; }
        bc.classList.remove('hidden'); bc.classList.add('flex');
        bc.innerHTML = parts.map((p, i) => {
            const sep = i ? `<svg class="text-slate-300" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="m9 18 6-6-6-6"/></svg>` : '';
            const last = i === parts.length - 1;
            const inner = `<span class="${last ? 'font-bold text-slate-800' : 'font-semibold text-slate-500'} ${p.act ? 'audit-clickable hover:text-teal-600' : ''}">${esc(p.label)}</span>`;
            return sep + (p.act ? `<a class="bc-link" data-i="${i}">${inner}</a>` : inner);
        }).join('');
        bc.querySelectorAll('.bc-link').forEach(a => a.onclick = () => parts[+a.dataset.i].act && parts[+a.dataset.i].act());
    }

    /* ================= KPI ================= */
    const KPI_DEFS = [
        { key:'total_users',     label:'Total users',    sub:'players in your base', tone:'slate',   drill:() => { setBehaviour('all'); scrollTable(); } },
        { key:'active_users_7d', label:'Active · 7 days', sub:'seen this week',       tone:'teal',    drill:() => { state.sort='last_active'; state.dir='desc'; $('#sortSelect').value='last_active|desc'; setBehaviour('all'); scrollTable(); } },
        { key:'active_today',    label:'Active today',    sub:'so far today',         tone:'teal',    drill:() => openDayUsers(todayStr()) },
        { key:'games_joined',    label:'Games joined',    sub:'', tone:'emerald',     drill:() => openTopGames('joins') },
        { key:'games_completed', label:'Games completed', sub:'', tone:'emerald',     drill:() => openTopGames('completed') },
        { key:'view_join_rate',  label:'View → Join',     sub:'', tone:'amber',       drill:() => openTopGames('views') },
    ];
    function todayStr(){ const s = summaryCache; return (s && s.range && s.range.to) ? s.range.to : new Date().toISOString().slice(0,10); }
    let summaryCache = null;
    function renderKpis(k, rangeName){
        const ring = { slate:'ring-transparent', teal:'ring-teal-500/10', blue:'ring-blue-500/10', emerald:'ring-emerald-500/10', amber:'ring-amber-500/10' };
        const accent = { slate:'#64748b', teal:'#0d9488', blue:'#2563eb', emerald:'#059669', amber:'#d97706' };
        $('#kpiRow').innerHTML = KPI_DEFS.map((d, i) => {
            let val, sub = d.sub || rangeName;
            if (d.key === 'view_join_rate') { val = k.view_join_rate == null ? '–' : k.view_join_rate + '%'; sub = k.view_join_rate == null ? 'awaiting view data' : 'conversion · ' + rangeName; }
            else val = nfmt(k[d.key]);
            return `<button data-kpi="${i}" class="kpi-card text-left audit-fade rounded-xl bg-white border border-slate-200 shadow-sm p-3 ring-1 ring-inset ${ring[d.tone]} hover:border-teal-300 hover:shadow transition">
                <div class="flex items-center justify-between">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">${esc(d.label)}</div>
                    <svg class="text-slate-300" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 7h10v10"/><path d="M7 17 17 7"/></svg>
                </div>
                <div class="mt-1 text-[22px] font-extrabold tabular-nums" style="color:${accent[d.tone]}">${esc(val)}</div>
                <div class="text-[11px] text-slate-400 mt-0.5 truncate">${esc(sub)}</div>
            </button>`;
        }).join('');
        $$('.kpi-card').forEach(b => b.onclick = () => KPI_DEFS[+b.dataset.kpi].drill());
    }

    /* ================= charts ================= */
    function mkChart(el, opt){
        if (charts[el]) { charts[el].updateOptions(opt, true, true); return; }
        charts[el] = new ApexCharts($('#' + el), opt);
        charts[el].render();
    }
    let trendDays = [];
    function renderTrend(daily){
        trendDays = daily;
        mkChart('chartTrend', {
            chart: { type:'area', height:270, toolbar:{show:false}, fontFamily:'inherit', animations:{enabled:false},
                events: { markerClick: (e, ctx, { dataPointIndex }) => { const d = trendDays[dataPointIndex]; if (d) openDayUsers(d.date, d.label); } } },
            series: [
                { name:'Active users', data: daily.map(d => d.active_users) },
                { name:'Game views',  data: daily.map(d => d.views) },
                { name:'Joins',       data: daily.map(d => d.joins) },
            ],
            colors: ['#0d9488','#2563eb','#059669'],
            fill: { type:'gradient', gradient:{ shadeIntensity:1, opacityFrom:0.35, opacityTo:0.03, stops:[0,90] } },
            stroke: { width:2, curve:'smooth' },
            markers: { size:0, hover:{ size:5 } },
            dataLabels: { enabled:false },
            grid: { borderColor:'#f1f5f9', strokeDashArray:4 },
            xaxis: { categories: daily.map(d => d.label), tickAmount:8, labels:{ style:{ colors:'#94a3b8', fontSize:'11px' } }, axisBorder:{show:false}, axisTicks:{show:false} },
            yaxis: { labels:{ style:{ colors:'#94a3b8', fontSize:'11px' }, formatter:v => Math.round(v) } },
            legend: { position:'top', horizontalAlign:'left', fontSize:'12px', markers:{ radius:4 } },
        });
    }
    function renderBehaviour(dist){
        const nz = dist.filter(d => d.value > 0);
        const src = nz.length ? nz : dist;
        mkChart('chartBehaviour', {
            chart: { type:'donut', height:270, fontFamily:'inherit', animations:{enabled:false},
                events: { dataPointSelection: (e, ctx, { dataPointIndex }) => { const lbl = src[dataPointIndex] && src[dataPointIndex].label; if (lbl) setBehaviour(lbl, true); } } },
            series: src.map(d => d.value),
            labels: src.map(d => d.label),
            colors: src.map(d => PALETTE[dist.findIndex(x => x.label === d.label)] || '#94a3b8'),
            dataLabels: { enabled:false },
            stroke: { width:2, colors:['#fff'] },
            legend: { position:'bottom', fontSize:'11px', itemMargin:{ vertical:2 } },
            plotOptions: { pie:{ donut:{ size:'68%', labels:{ show:true, total:{ show:true, label:'Players', fontSize:'12px', color:'#64748b', formatter:w => w.globals.seriesTotals.reduce((a,b)=>a+b,0) } } } } },
        });
    }
    function renderEngagement(t){
        const metrics = ['views','joins','completed','abandoned'];
        mkChart('chartEngagement', {
            chart: { type:'bar', height:250, toolbar:{show:false}, fontFamily:'inherit', animations:{enabled:false},
                events: { dataPointSelection: (e, ctx, { dataPointIndex }) => openTopGames(metrics[dataPointIndex]) } },
            series: [{ name:'Games', data:[t.viewed, t.joined, t.completed, t.abandoned] }],
            colors: ['#2563eb','#0d9488','#059669','#dc2626'],
            plotOptions: { bar:{ distributed:true, borderRadius:6, columnWidth:'52%' } },
            dataLabels: { enabled:true, style:{ fontSize:'11px', colors:['#fff'] } },
            legend: { show:false },
            grid: { borderColor:'#f1f5f9', strokeDashArray:4 },
            xaxis: { categories:['Viewed','Joined','Completed','Abandoned'], labels:{ style:{ colors:'#94a3b8', fontSize:'11px' } }, axisBorder:{show:false}, axisTicks:{show:false} },
            yaxis: { labels:{ style:{ colors:'#94a3b8', fontSize:'11px' }, formatter:v => Math.round(v) } },
        });
    }
    function renderTime(tod){
        mkChart('chartTime', {
            chart: { type:'bar', height:250, toolbar:{show:false}, fontFamily:'inherit', animations:{enabled:false} },
            series: [{ name:'Events', data: tod.map(d => d.value) }],
            colors: ['#0d9488'],
            plotOptions: { bar:{ borderRadius:6, columnWidth:'45%' } },
            dataLabels: { enabled:false },
            grid: { borderColor:'#f1f5f9', strokeDashArray:4 },
            xaxis: { categories: tod.map(d => d.label), labels:{ style:{ colors:'#94a3b8', fontSize:'11px' } }, axisBorder:{show:false}, axisTicks:{show:false} },
            yaxis: { labels:{ style:{ colors:'#94a3b8', fontSize:'11px' }, formatter:v => Math.round(v) } },
        });
    }
    function renderFunnel(steps){
        const max = Math.max(1, ...steps.map(s => s.value));
        const cols = ['#2563eb','#0ea5e9','#0d9488','#059669'];
        $('#funnel').innerHTML = steps.map((s, i) => {
            const prev = i ? steps[i-1].value : s.value;
            // only show a % when the previous stage is a valid superset (avoids
            // nonsense while view tracking is still catching up with join history)
            const conv = (i && prev >= s.value && prev > 0) ? Math.round(s.value / prev * 100) : null;
            const drillMetric = i >= 2 ? (i === 2 ? 'joins' : 'completed') : (i === 1 ? 'views' : null);
            return `<div class="mb-2.5 ${drillMetric ? 'audit-clickable fn-step' : ''}" ${drillMetric ? `data-metric="${drillMetric}"` : ''}>
                <div class="flex items-center justify-between text-[11px] mb-1">
                    <span class="font-semibold text-slate-600">${esc(s.stage)}</span>
                    <span class="tabular-nums text-slate-500">${nfmt(s.value)}${conv !== null ? ` · <span class="font-semibold" style="color:${conv>=40?'#059669':conv>=15?'#d97706':'#dc2626'}">${conv}%</span>` : ''}</span>
                </div>
                <div class="h-3 rounded-md bg-slate-100 overflow-hidden"><div class="h-full rounded-md" style="width:${Math.max(3, s.value/max*100)}%;background:${cols[i]}"></div></div>
            </div>`;
        }).join('');
        $$('.fn-step').forEach(el => el.onclick = () => openTopGames(el.dataset.metric));
    }

    /* ================= main table ================= */
    function setBehaviour(b, scroll){
        state.behaviour = b; state.page = 1;
        $('#behaviourFilter').value = b;
        loadTable(); syncUrl(true);
        if (scroll) scrollTable();
    }
    function scrollTable(){ $('#userTableCard').scrollIntoView({ behavior:'smooth', block:'start' }); }
    function userRowHtml(u){
        const m = u.metrics;
        const abCls = m.abandoned > 0 ? 'text-red-600 font-bold' : 'text-slate-400';
        const eng = u.engagement;
        const col = eng >= 66 ? '#059669' : eng >= 33 ? '#f59e0b' : '#94a3b8';
        return `<tr class="hover:bg-slate-50/70 audit-clickable u-row" data-user="${u.id}">
            <td class="px-4 py-2.5"><div class="flex items-center gap-2.5">${avatar(u)}
                <div class="min-w-0">
                    <div class="font-semibold text-slate-800 text-[13px] truncate max-w-[190px]">${esc(u.name)}</div>
                    <div class="text-[11px] text-slate-400 truncate max-w-[190px]">${esc(u.email || ('#'+u.id))}</div>
                </div></div></td>
            <td class="px-3 py-2.5">${behaviourChip(u.behaviour)}</td>
            <td class="px-3 py-2.5 text-[12px] text-slate-500 whitespace-nowrap">${esc(timeAgo(u.last_active))}</td>
            <td class="px-3 py-2.5 text-center tabular-nums text-slate-600">${nfmt(m.views)}</td>
            <td class="px-3 py-2.5 text-center tabular-nums font-semibold text-slate-700">${nfmt(m.joins)}</td>
            <td class="px-3 py-2.5 text-center tabular-nums text-emerald-600 font-semibold">${nfmt(m.completed)}</td>
            <td class="px-3 py-2.5 text-center tabular-nums ${abCls}">${nfmt(m.abandoned)}</td>
            <td class="px-3 py-2.5"><div class="flex items-center gap-2 w-[110px]">
                <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden"><div class="h-full rounded-full" style="width:${eng}%;background:${col}"></div></div>
                <span class="text-[11px] font-bold text-slate-600 tabular-nums w-6 text-right">${eng}</span></div></td>
            <td class="px-3 py-2.5 text-right"><span class="inline-flex items-center gap-1 text-[12px] font-semibold text-teal-600">Investigate
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></span></td>
        </tr>`;
    }
    function renderTable(payload){
        const rows = payload.data || [];
        const p = payload.pagination;
        $('#tableMeta').textContent = `${nfmt(p.total)} user${p.total===1?'':'s'} · page ${p.page} of ${p.pages}` + (state.behaviour!=='all' ? ` · ${state.behaviour}` : '');
        $('#userRows').innerHTML = rows.length
            ? rows.map(userRowHtml).join('')
            : `<tr><td colspan="9" class="px-4 py-10 text-center text-slate-400 text-sm">No users match these filters.</td></tr>`;
        const canPrev = p.page > 1, canNext = p.page < p.pages;
        const pgb = (id, px, lbl, on) => `<button id="${id}" ${on?'':'disabled'} class="${px} py-1 rounded-md border border-slate-200 font-semibold ${on?'text-slate-600 hover:bg-slate-50':'text-slate-300'}">${lbl}</button>`;
        $('#tablePager').innerHTML = `<span>Showing ${rows.length ? (p.page-1)*p.per_page+1 : 0}–${(p.page-1)*p.per_page+rows.length} of ${nfmt(p.total)}</span>
            <div class="flex items-center gap-1.5">
                ${pgb('pgFirst','px-2','«',canPrev)}
                ${pgb('pgPrev','px-2.5','Prev',canPrev)}
                ${pgb('pgNext','px-2.5','Next',canNext)}
                ${pgb('pgLast','px-2','»',canNext)}
            </div>`;
        const go = pg => { state.page = pg; loadTable(); };
        const P = $('#tablePager');
        [['pgFirst', 1], ['pgPrev', Math.max(1, p.page-1)], ['pgNext', p.page+1], ['pgLast', p.pages]]
            .forEach(([id, to]) => { const b = P.querySelector('#' + id); if (b) b.onclick = () => go(to); });
        $$('.u-row').forEach(r => r.onclick = () => openUserModal(+r.dataset.user));
    }

    /* ================= LAYER 1 · user modal ================= */
    function openUserModal(userId, contextGameId){
        umDestroyCharts();
        state.um = { userId, tlPage: 1, tlActivity: '', tlGame: contextGameId ? +contextGameId : 0, gamesPage: 1, name: null };
        $('#umOverlay').classList.remove('hidden');
        $('#umModal').classList.remove('translate-x-full');
        document.body.style.overflow = 'hidden';
        $('#umBody').innerHTML = `<div class="py-16 text-center text-slate-400 text-sm">Loading user…</div>`;
        $('#umHeader').innerHTML = `<div class="h-10"></div>`;
        loadUserDetail();   // builds #umBody, then kicks off the timeline once its nodes exist
        syncUrl(true);
    }
    function closeUserModal(){
        umDestroyCharts();
        state.um = null;
        $('#umOverlay').classList.add('hidden');
        $('#umModal').classList.add('translate-x-full');
        if (!state.dp) document.body.style.overflow = '';
        syncUrl(true);
    }
    async function loadUserDetail(){
        umDestroyCharts();
        let d;
        try { d = await api('user_detail.php', rp({ user_id: state.um.userId })); }
        catch (e) { if (e.forbidden) { $('#umBody').innerHTML = empty(e.message || 'Not permitted.'); return; } throw e; }
        if (d.error) { $('#umBody').innerHTML = empty(d.message || 'Could not load user.'); return; }
        state.um.name = d.user.name;
        renderBreadcrumb();

        const u = d.user, k = u.kpis, br = u.behaviour_reasons || { label: u.behaviour, reasons: [] };
        $('#umHeader').innerHTML = `
            <div class="flex items-start gap-3">
                ${avatar(u, 12)}
                <div class="min-w-0 flex-1">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-teal-600">User activity</div>
                    <div class="text-lg font-extrabold text-slate-900 truncate">${esc(u.name)} <span class="text-slate-300 font-semibold text-sm">#${u.id}</span></div>
                    <div class="text-[12px] text-slate-400 truncate">${esc(u.email || '')}</div>
                    <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                        ${behaviourChip(br.label)}
                        <span class="text-[11px] ${u.account_active ? 'text-emerald-600' : 'text-slate-400'} font-semibold">${u.account_active ? '● Active account' : '○ Inactive account'}</span>
                        <span class="text-[11px] text-slate-400">Last active ${esc(timeAgo(k.last_active))}</span>
                    </div>
                </div>
                <button id="umClose" class="shrink-0 h-8 w-8 grid place-items-center rounded-lg text-slate-400 hover:bg-slate-100">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>`;
        $('#umClose').onclick = closeUserModal;

        const kpiCards = [
            ['Sessions', nfmt(k.sessions)], ['Total time', mins(k.session_minutes)],
            ['Games viewed', nfmt(k.games_viewed)], ['Unique games', nfmt(k.unique_games_viewed)],
            ['Joined', nfmt(k.games_joined)], ['Completed', nfmt(k.games_completed)],
            ['Abandoned', nfmt(k.games_abandoned)], ['View → Join', k.view_join_rate == null ? '–' : k.view_join_rate + '%'],
            ['Active days', nfmt(k.active_days)], ['First seen', k.first_seen ? fmtDate(k.first_seen) : '–'],
        ];
        const reasons = br.reasons && br.reasons.length ? `<div class="text-[11px] text-slate-500 mt-1">Why: ${esc(br.reasons.join('; '))}</div>` : '';

        $('#umBody').innerHTML = `
            <div class="rounded-xl bg-white border border-slate-200 p-3">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Summary · ${esc(RANGE_NAME[d.range.key] || d.range.key)} where dated</div>
                <div class="grid grid-cols-3 sm:grid-cols-5 gap-3">
                    ${kpiCards.map(([l,v]) => `<div><div class="text-[15px] font-extrabold tabular-nums text-slate-800">${v}</div><div class="text-[10px] uppercase tracking-wide text-slate-400">${l}</div></div>`).join('')}
                </div>
                ${reasons}
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <div class="rounded-xl bg-white border border-slate-200 p-3">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Activity over time · by ${esc(d.range.granularity)}</div>
                    <div id="umTrend" class="min-h-[190px]"></div>
                </div>
                <div class="rounded-xl bg-white border border-slate-200 p-3">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Game engagement</div>
                    <div id="umEng" class="min-h-[190px]"></div>
                </div>
                <div class="rounded-xl bg-white border border-slate-200 p-3 lg:col-span-2">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Activity breakdown · ${esc(RANGE_NAME[d.range.key] || d.range.key)}</div>
                    <div id="umBreak" class="min-h-[150px]"></div>
                </div>
            </div>

            <div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
                <div class="flex items-center justify-between px-3 py-2 border-b border-slate-100">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Games interacted with</div>
                    <div class="text-[11px] text-slate-400">${nfmt(d.games.pagination.total)} games</div>
                </div>
                <div id="umGames" class="text-sm"></div>
                <div id="umGamesPager" class="px-3 py-2 border-t border-slate-100 text-[11px] text-slate-500"></div>
            </div>

            <div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
                <div class="px-3 py-2 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Activity timeline</div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <select id="tlRange" class="h-[28px] bg-slate-50 border border-slate-200 rounded-md px-2 text-[11px] font-semibold text-slate-600 outline-none cursor-pointer">
                            <option value="">All dates</option>
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="7d">Last 7 days</option>
                            <option value="30d">Last 30 days</option>
                        </select>
                        <select id="tlActivity" class="h-[28px] bg-slate-50 border border-slate-200 rounded-md px-2 text-[11px] font-semibold text-slate-600 outline-none cursor-pointer">
                            <option value="">All activity</option>
                            <option value="LOGIN">Login</option>
                            <option value="GAME_VIEWED">Game view</option>
                            <option value="GAME_LIST_VIEWED">Browsed list</option>
                            <option value="JOIN_GAME">Game join</option>
                            <option value="LEAVE_GAME">Game leave</option>
                        </select>
                        <select id="tlGame" class="h-[28px] bg-slate-50 border border-slate-200 rounded-md px-2 text-[11px] font-semibold text-slate-600 outline-none cursor-pointer max-w-[160px]">
                            <option value="0">All games</option>
                        </select>
                    </div>
                </div>
                <div id="umFeed" class="px-4 py-3 max-h-[420px] overflow-y-auto audit-scrollbar"></div>
                <div id="umFeedMore" class="px-3 py-2 border-t border-slate-100 hidden">
                    <button id="umFeedMoreBtn" class="w-full h-8 rounded-md border border-slate-200 text-[12px] font-semibold text-slate-600 hover:bg-slate-50">Load more</button>
                </div>
            </div>`;

        // charts — render on the next frame so the grid has settled (correct width/axis)
        requestAnimationFrame(() => {
            if (!state.um) return;
            umTrendChart(d.trend);
            umEngChart(d.engagement);
            umBreakChart(d.activity_breakdown);
        });
        // games table
        renderUmGames(d.games);
        // timeline filter options (games list)
        const gsel = $('#tlGame');
        gsel.innerHTML = `<option value="0">All games</option>` + d.games.data.map(g => `<option value="${g.id}">${esc((g.name||'').slice(0,28))}</option>`).join('');
        $('#tlRange').value = state.um.tlRange || '';
        $('#tlActivity').value = state.um.tlActivity || '';
        $('#tlGame').value = state.um.tlGame || '0';
        $('#tlRange').onchange   = e => { state.um.tlRange = e.target.value; state.um.tlPage = 1; loadUserTimeline(); };
        $('#tlActivity').onchange = e => { state.um.tlActivity = e.target.value; state.um.tlPage = 1; loadUserTimeline(); };
        $('#tlGame').onchange    = e => { state.um.tlGame = +e.target.value; state.um.tlPage = 1; loadUserTimeline(); syncUrl(true); };
        // nodes exist now — load the timeline
        state.um.tlPage = 1;
        loadUserTimeline();
    }
    // one managed instance per slot — destroy-before-recreate so re-opening the
    // modal / changing range never stacks zombie charts on top of each other
    const umCharts = {};
    function umDestroyCharts(){
        Object.keys(umCharts).forEach(k => { try { umCharts[k].destroy(); } catch (e) {} delete umCharts[k]; });
    }
    function umChart(key, sel, opt){
        try { if (umCharts[key]) umCharts[key].destroy(); } catch (e) {}
        const el = $(sel);
        if (!el) return;
        el.innerHTML = '';
        umCharts[key] = new ApexCharts(el, opt);
        umCharts[key].render();
    }
    function umTrendChart(trend){
        umChart('trend', '#umTrend', {
            chart: { type:'area', height:200, width:'100%', toolbar:{show:false}, animations:{enabled:false}, fontFamily:'inherit', parentHeightOffset:0 },
            series: [ { name:'Views', data: trend.map(t => t.views) }, { name:'Joins', data: trend.map(t => t.joins) }, { name:'Logins', data: trend.map(t => t.logins) } ],
            colors: ['#2563eb','#059669','#94a3b8'],
            fill: { type:'gradient', gradient:{ opacityFrom:0.3, opacityTo:0.02 } },
            stroke: { width:2, curve:'smooth' },
            dataLabels: { enabled:false },
            grid: { borderColor:'#f1f5f9', strokeDashArray:4, padding:{ left:4, right:8, top:0 } },
            xaxis: { type:'category', categories: trend.map(t => t.label), tickAmount: Math.min(4, Math.max(2, trend.length - 1)),
                labels:{ show:true, rotate:-25, rotateAlways:false, trim:false, hideOverlappingLabels:true, style:{ colors:'#94a3b8', fontSize:'10px' } },
                axisBorder:{show:false}, axisTicks:{show:false} },
            yaxis: { forceNiceScale:true, labels:{ style:{ colors:'#94a3b8', fontSize:'10px' }, formatter:v => Math.round(v) } },
            legend: { position:'top', horizontalAlign:'left', fontSize:'11px', markers:{ radius:3 }, itemMargin:{ horizontal:6 } },
        });
    }
    function umEngChart(e){
        const data = [e.viewed, e.joined, e.completed, e.abandoned];
        umChart('eng', '#umEng', {
            chart: { type:'bar', height:200, width:'100%', toolbar:{show:false}, animations:{enabled:false}, fontFamily:'inherit', parentHeightOffset:0 },
            series: [{ name:'Games', data }],
            colors: ['#2563eb','#0d9488','#059669','#dc2626'],
            plotOptions: { bar:{ distributed:true, horizontal:true, borderRadius:4, barHeight:'58%' } },
            dataLabels: { enabled:true, style:{ fontSize:'10px', colors:['#fff'] }, offsetX: 2,
                formatter:(v) => v > 0 ? v : '' },
            legend: { show:false },
            grid: { borderColor:'#f1f5f9', strokeDashArray:4, padding:{ left:4, right:12 } },
            xaxis: { categories:['Viewed','Joined','Completed','Abandoned'], labels:{ style:{ colors:'#94a3b8', fontSize:'10px' } }, axisBorder:{show:false}, axisTicks:{show:false} },
            yaxis: { labels:{ style:{ colors:'#64748b', fontSize:'11px' } } },
            tooltip: { y:{ formatter:v => v + ' game' + (v === 1 ? '' : 's') } },
        });
    }
    function umBreakChart(bd){
        if (!bd.length) { const el = $('#umBreak'); if (el) el.innerHTML = empty('No activity in this period.'); return; }
        umChart('break', '#umBreak', {
            chart: { type:'bar', height:Math.max(140, bd.length*36), width:'100%', toolbar:{show:false}, animations:{enabled:false}, fontFamily:'inherit', parentHeightOffset:0 },
            series: [{ name:'Count', data: bd.map(b => b.value) }],
            colors: bd.map(b => (TONE[b.tone] || TONE.neutral).dot),
            plotOptions: { bar:{ distributed:true, horizontal:true, borderRadius:4, barHeight:'55%' } },
            dataLabels: { enabled:true, style:{ fontSize:'10px', colors:['#fff'] }, offsetX:2, formatter:(v) => v > 0 ? v : '' },
            legend: { show:false },
            grid: { borderColor:'#f1f5f9', strokeDashArray:4, padding:{ left:4, right:12 } },
            xaxis: { categories: bd.map(b => b.label), labels:{ style:{ colors:'#94a3b8', fontSize:'10px' } } },
            yaxis: { labels:{ style:{ colors:'#64748b', fontSize:'11px' } } },
        });
    }
    function renderUmGames(g){
        const rows = g.data || [];
        $('#umGames').innerHTML = rows.length ? `
            <table class="w-full text-[12px]">
                <thead><tr class="text-left text-[10px] uppercase tracking-wider text-slate-400 bg-slate-50/70">
                    <th class="px-3 py-1.5">Game</th><th class="px-2 py-1.5 text-center">Views</th>
                    <th class="px-2 py-1.5 text-center">Joined</th><th class="px-2 py-1.5 text-center">Done</th>
                    <th class="px-2 py-1.5 text-center">Aband.</th><th class="px-2 py-1.5">Last</th><th class="px-2 py-1.5"></th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                ${rows.map(x => `<tr class="hover:bg-slate-50/70 audit-clickable ug-row" data-game="${x.id}">
                    <td class="px-3 py-1.5"><div class="font-semibold text-slate-700 truncate max-w-[200px]">${esc(x.name)}</div>
                        <div class="text-[10px] text-slate-400">${esc(x.category||'')} ${x.status?'· '+esc(x.status):''}</div></td>
                    <td class="px-2 py-1.5 text-center tabular-nums">${nfmt(x.views)}</td>
                    <td class="px-2 py-1.5 text-center">${x.joined ? '✓' : '—'}</td>
                    <td class="px-2 py-1.5 text-center text-emerald-600">${x.completed ? '✓' : '—'}</td>
                    <td class="px-2 py-1.5 text-center text-red-500">${x.abandoned ? '✓' : '—'}</td>
                    <td class="px-2 py-1.5 text-[11px] text-slate-400 whitespace-nowrap">${esc(timeAgo(x.last_activity))}</td>
                    <td class="px-2 py-1.5 text-right"><span class="text-teal-600 text-[11px] font-semibold">Detail ›</span></td>
                </tr>`).join('')}
                </tbody>
            </table>` : empty('No games interacted with yet.');
        const p = g.pagination;
        $('#umGamesPager').innerHTML = '';
        if (p.pages > 1) $('#umGamesPager').appendChild(pager(p, pg => { state.um.gamesPage = pg; loadUserGamesPage(pg); }));
        $$('.ug-row').forEach(r => r.onclick = () => openUserGame(state.um.userId, +r.dataset.game));
    }
    async function loadUserGamesPage(pg){
        const d = await api('user_detail.php', rp({ user_id: state.um.userId, game_page: pg }));
        if (!d.error) renderUmGames(d.games);
    }
    async function loadUserTimeline(append){
        if (!state.um) return;
        if (!append) $('#umFeed').innerHTML = `<div class="py-8 text-center text-slate-300 text-sm">Loading…</div>`;
        const params = { user_id: state.um.userId, page: state.um.tlPage, per_page: 40 };
        if (state.um.tlActivity) params.activity = state.um.tlActivity;
        if (state.um.tlGame) params.game_id = state.um.tlGame;
        if (state.um.tlRange) params.range = state.um.tlRange;
        let d;
        try { d = await api('user_timeline.php', params); } catch (e) { return; }
        if (d.error) { $('#umFeed').innerHTML = empty(d.message || 'Error'); return; }
        const feed = d.feed || [];
        let html = '';
        if (!feed.length && state.um.tlPage === 1) {
            html = empty('No activity for this filter.');
        } else {
            let lastDay = append ? ($('#umFeed').dataset.lastday || '') : '';
            feed.forEach(f => {
                const dl = dayLabel(f.at);
                if (dl !== lastDay) { html += `<div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mt-4 mb-2 first:mt-0">${esc(dl)}</div>`; lastDay = dl; }
                const t = TONE[f.tone] || TONE.neutral;
                html += `<div class="relative pl-6 pb-3 audit-clickable ev-row" data-ev="${f.id}" data-game="${f.game_id || 0}">
                    <span class="absolute left-[3px] top-1.5 h-2 w-2 rounded-full ring-4 ring-white" style="background:${t.dot}"></span>
                    <span class="absolute left-[7px] top-3 bottom-0 w-px bg-slate-100"></span>
                    <div class="flex items-baseline justify-between gap-2">
                        <span class="text-[13px] font-semibold text-slate-700">${esc(f.label)}${f.game_name ? ` · <span class="font-normal text-teal-600">${esc(f.game_name)}</span>` : ''}</span>
                        <span class="text-[11px] text-slate-400 shrink-0 tabular-nums">${esc(fmtTime(f.at))}</span>
                    </div>
                    ${f.game_category ? `<div class="text-[11px] text-slate-400">${esc(f.game_category)}</div>` : ''}
                </div>`;
            });
            $('#umFeed').dataset.lastday = lastDay;
        }
        if (append) $('#umFeed').insertAdjacentHTML('beforeend', html);
        else $('#umFeed').innerHTML = html;
        const pg = d.pagination || {};
        const more = pg.page < pg.pages;
        $('#umFeedMore').classList.toggle('hidden', !more);
        if (more) $('#umFeedMoreBtn').onclick = () => { state.um.tlPage++; loadUserTimeline(true); };
        $$('#umFeed .ev-row').forEach(r => r.onclick = () => {
            const gid = +r.dataset.game;
            if (gid) openUserGame(state.um.userId, gid); else openEvent(+r.dataset.ev);
        });
    }

    /* ================= LAYER 2 · list drill panel ================= */
    function openDrillShell(){
        $('#dpOverlay').classList.remove('hidden');
        $('#dpPanel').classList.remove('translate-x-full');
        document.body.style.overflow = 'hidden';
    }
    function closeDrill(){
        state.dp = null;
        $('#dpOverlay').classList.add('hidden');
        $('#dpPanel').classList.add('translate-x-full');
        if (!state.um) document.body.style.overflow = '';
        syncUrl(true);
    }
    async function openTopGames(metric){
        openDrillShell();
        state.dp = { kind: 'topgames', metric };
        $('#dpHeader').innerHTML = drillHead(`Top games — ${metric}`, rangeLabel());
        $('#dpBody').innerHTML = `<div class="py-8 text-center text-slate-300 text-sm">Loading…</div>`;
        $('#dpPager').innerHTML = '';
        wireDrillClose();
        renderBreadcrumb();
        let d;
        try { d = await api('top_games.php', rp({ metric, limit: 25 })); } catch (e) { $('#dpBody').innerHTML = empty(e.message || 'Error'); return; }
        const games = d.games || [];
        $('#dpBody').innerHTML = games.length ? games.map((g, i) => `
            <div class="flex items-center gap-3 py-2.5 border-b border-slate-100 audit-clickable tg-row" data-game="${g.id}" data-title="${esc(g.name)}">
                <span class="w-6 text-center text-[12px] font-bold text-slate-300">${i+1}</span>
                <div class="min-w-0 flex-1">
                    <div class="text-[13px] font-semibold text-slate-800 truncate">${esc(g.name)}</div>
                    <div class="text-[11px] text-slate-400">${esc(g.category||'')} ${g.status?'· '+esc(g.status):''} · ${g.unique_users} user${g.unique_users===1?'':'s'}</div>
                </div>
                <div class="text-right"><div class="text-[15px] font-extrabold tabular-nums text-slate-800">${nfmt(g.value)}</div>
                    <div class="text-[10px] uppercase tracking-wide text-slate-400">${esc(metric)}</div></div>
            </div>`).join('') : empty(metric === 'views' ? 'No game views recorded in this period yet.' : 'Nothing in this period.');
        $$('.tg-row').forEach(r => r.onclick = () => openGameUsers(+r.dataset.game, r.dataset.title));
    }
    async function openGameUsers(gameId, title, page){
        openDrillShell();
        state.dp = { kind: 'game', id: gameId, title, page: page || 1 };
        wireDrillClose();
        $('#dpBody').innerHTML = `<div class="py-8 text-center text-slate-300 text-sm">Loading…</div>`;
        let d;
        try { d = await api('game_users.php', rp({ game_id: gameId, page: state.dp.page, per_page: 15 })); }
        catch (e) { $('#dpBody').innerHTML = empty(e.message || 'Error'); return; }
        if (d.error) { $('#dpBody').innerHTML = empty(d.message); return; }
        state.dp.title = d.game.name;
        renderBreadcrumb();
        const s = d.summary;
        $('#dpHeader').innerHTML = drillHead(d.game.name, `${esc(d.game.category||'')} ${d.game.status?'· '+esc(d.game.status):''}`) + `
            <div class="grid grid-cols-4 gap-2 mt-2 text-center">
                ${[['Views',s.views],['Viewers',s.unique_viewers],['Joins',s.joins],['Completed',s.completed]].map(([l,v]) =>
                    `<div><div class="text-[15px] font-extrabold tabular-nums text-slate-800">${nfmt(v)}</div><div class="text-[10px] uppercase tracking-wide text-slate-400">${l}</div></div>`).join('')}
            </div>`;
        const rows = d.data || [];
        $('#dpBody').innerHTML = rows.length ? rows.map(u => `
            <div class="flex items-center gap-3 py-2.5 border-b border-slate-100 audit-clickable gu-row" data-user="${u.id}">
                ${avatar(u)}
                <div class="min-w-0 flex-1">
                    <div class="text-[13px] font-semibold text-slate-800 truncate">${esc(u.name)}</div>
                    <div class="text-[11px] text-slate-400 truncate">${u.views} view${u.views===1?'':'s'}${u.first_viewed?` · first ${esc(fmtDate(u.first_viewed))}`:''}</div>
                </div>
                <div class="flex items-center gap-1.5 text-[10px] font-bold">
                    ${u.joined ? '<span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700">JOINED</span>' : ''}
                    ${u.completed ? '<span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800">DONE</span>' : ''}
                    ${u.abandoned ? '<span class="px-1.5 py-0.5 rounded bg-red-50 text-red-700">LEFT</span>' : ''}
                </div>
            </div>`).join('') : empty('No users found.');
        $('#dpPager').innerHTML = '';
        if (d.pagination.pages > 1) $('#dpPager').appendChild(pager(d.pagination, pg => openGameUsers(gameId, title, pg)));
        $$('.gu-row').forEach(r => r.onclick = () => openUserModal(+r.dataset.user, gameId));
    }
    async function openDayUsers(date, label, page){
        openDrillShell();
        state.dp = { kind: 'day', date, page: page || 1 };
        wireDrillClose();
        $('#dpHeader').innerHTML = drillHead('Active users', label || date);
        $('#dpBody').innerHTML = `<div class="py-8 text-center text-slate-300 text-sm">Loading…</div>`;
        renderBreadcrumb();
        let d;
        try { d = await api('day_users.php', { date, page: state.dp.page, per_page: 15 }); }
        catch (e) { $('#dpBody').innerHTML = empty(e.message || 'Error'); return; }
        if (d.error) { $('#dpBody').innerHTML = empty(d.message); return; }
        $('#dpHeader').innerHTML = drillHead('Active users', `${esc(d.day)} · ${nfmt(d.total)} user${d.total===1?'':'s'}`);
        const rows = d.data || [];
        $('#dpBody').innerHTML = rows.length ? rows.map(u => `
            <div class="flex items-center gap-3 py-2.5 border-b border-slate-100 audit-clickable gu-row" data-user="${u.id}">
                ${avatar(u)}
                <div class="min-w-0 flex-1">
                    <div class="text-[13px] font-semibold text-slate-800 truncate">${esc(u.name)}</div>
                    <div class="text-[11px] text-slate-400 truncate">${u.events} event${u.events===1?'':'s'} · ${u.views} view${u.views===1?'':'s'} · ${u.joins} join${u.joins===1?'':'s'}</div>
                </div>
                <div class="text-[11px] text-slate-400 whitespace-nowrap">${esc(fmtTime(u.first_at))}–${esc(fmtTime(u.last_at))}</div>
            </div>`).join('') : empty('No active users that day.');
        $('#dpPager').innerHTML = '';
        if (d.pagination.pages > 1) $('#dpPager').appendChild(pager(d.pagination, pg => openDayUsers(date, label, pg)));
        $$('.gu-row').forEach(r => r.onclick = () => openUserModal(+r.dataset.user));
    }
    function drillHead(title, sub){
        return `<div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <div class="text-[10px] font-bold uppercase tracking-widest text-teal-600">Drill-down</div>
                <div class="text-[15px] font-extrabold text-slate-900 truncate">${esc(title)}</div>
                ${sub ? `<div class="text-[11px] text-slate-400 truncate">${sub}</div>` : ''}
            </div>
            <button id="dpClose" class="shrink-0 h-8 w-8 grid place-items-center rounded-lg text-slate-400 hover:bg-slate-100">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button></div>`;
    }
    function wireDrillClose(){ const b = $('#dpClose'); if (b) b.onclick = closeDrill; }

    /* ================= LAYER 3 · mini cards ================= */
    function openMini(html){
        $('#mpCard').innerHTML = html;
        $('#mpOverlay').classList.remove('hidden');
        const c = $('#mpCard').querySelector('.mp-close');
        if (c) c.onclick = closeMini;
    }
    function closeMini(){ $('#mpOverlay').classList.add('hidden'); $('#mpCard').innerHTML = ''; }
    async function openEvent(id){
        openMini(`<div class="p-6 text-center text-slate-400 text-sm">Loading event…</div>`);
        let d;
        try { d = await api('event.php', { id }); } catch (e) { openMini(miniShell('Event', empty(e.message || 'Error'))); return; }
        if (d.error) { openMini(miniShell('Event', empty(d.message))); return; }
        const e = d.event, t = TONE[e.tone] || TONE.neutral;
        const kv = (k, v) => v ? `<div class="flex justify-between gap-3 py-1 text-[12px]"><span class="text-slate-400">${k}</span><span class="font-semibold text-slate-700 text-right">${v}</span></div>` : '';
        openMini(miniShell('Event detail', `
            <div class="flex items-center gap-2 mb-2">
                <span class="h-2.5 w-2.5 rounded-full" style="background:${t.dot}"></span>
                <span class="text-[15px] font-extrabold text-slate-900">${esc(e.label)}</span>
            </div>
            ${kv('When', esc(fmtDateTime(e.at)))}
            ${kv('User', esc(e.user.name) + ' <span class="text-slate-300">#'+e.user.id+'</span>')}
            ${e.game ? kv('Game', `<a class="text-teal-600 mp-game" data-user="${e.user.id}" data-game="${e.game.id}">${esc(e.game.name)} ›</a>`) : ''}
            ${e.game ? kv('Game status', statusPill(e.game.status)) : ''}
            ${kv('Detail', esc(e.description || ''))}
            ${kv('Previous action', d.previous ? esc(d.previous.label) + ' <span class="text-slate-400">'+esc(fmtTime(d.previous.at))+'</span>' : '<span class="text-slate-300">none</span>')}
            ${kv('Next action', d.next ? esc(d.next.label) + ' <span class="text-slate-400">'+esc(fmtTime(d.next.at))+'</span>' : '<span class="text-slate-300">none</span>')}
            ${e.ip ? kv('IP', esc(e.ip)) : ''}
            <div class="mt-3 flex gap-2">
                ${d.previous ? `<button class="mp-nav flex-1 h-8 rounded-md border border-slate-200 text-[12px] font-semibold text-slate-600 hover:bg-slate-50" data-id="${d.previous.id}">‹ Previous</button>` : ''}
                ${d.next ? `<button class="mp-nav flex-1 h-8 rounded-md border border-slate-200 text-[12px] font-semibold text-slate-600 hover:bg-slate-50" data-id="${d.next.id}">Next ›</button>` : ''}
            </div>`));
        $$('#mpCard .mp-nav').forEach(b => b.onclick = () => openEvent(+b.dataset.id));
        const g = $('#mpCard .mp-game');
        if (g) g.onclick = () => { closeMini(); openUserGame(+g.dataset.user, +g.dataset.game); };
    }
    async function openUserGame(userId, gameId){
        openMini(`<div class="p-6 text-center text-slate-400 text-sm">Loading…</div>`);
        let d;
        try { d = await api('user_game.php', { user_id: userId, game_id: gameId }); }
        catch (e) { openMini(miniShell('User × Game', empty(e.message || 'Error'))); return; }
        if (d.error) { openMini(miniShell('User × Game', empty(d.message))); return; }
        const g = d.game, r = d.relationship;
        const row = (k, v) => `<div class="flex justify-between gap-3 py-1 text-[12px]"><span class="text-slate-400">${k}</span><span class="font-semibold text-slate-700 text-right">${v}</span></div>`;
        const flag = (b, on, off) => b ? `<span class="text-emerald-600 font-bold">${on}</span>` : `<span class="text-slate-400">${off}</span>`;
        openMini(miniShell('What this user did with the game', `
            <div class="text-[15px] font-extrabold text-slate-900">${esc(g.name)}</div>
            <div class="text-[11px] text-slate-400 mb-2">${esc(g.category||'')} ${g.gender?'· '+esc(g.gender):''} ${g.date?'· '+esc(fmtDate(g.date)):''} · ${statusPill(g.status)}</div>
            <div class="grid grid-cols-3 gap-2 text-center my-2">
                <div><div class="text-[16px] font-extrabold text-slate-800 tabular-nums">${nfmt(r.view_count)}</div><div class="text-[10px] uppercase text-slate-400">Views</div></div>
                <div><div class="text-[16px] font-extrabold text-slate-800 tabular-nums">${nfmt(r.sessions)}</div><div class="text-[10px] uppercase text-slate-400">Sessions</div></div>
                <div><div class="text-[16px] font-extrabold text-slate-800 tabular-nums">${r.time_spent_min == null ? '–' : mins(r.time_spent_min)}</div><div class="text-[10px] uppercase text-slate-400">Time span</div></div>
            </div>
            ${row('First viewed', r.first_viewed ? esc(fmtDateTime(r.first_viewed)) : '<span class="text-slate-300">never</span>')}
            ${row('Last activity', r.last_activity ? esc(fmtDateTime(r.last_activity)) : '–')}
            ${row('Joined', flag(r.joined, 'Yes', 'No') + (r.joined_at ? ' <span class="text-slate-400">'+esc(fmtDate(r.joined_at))+'</span>' : ''))}
            ${row('Left', flag(r.left, 'Yes', 'No') + (r.left_at ? ' <span class="text-slate-400">'+esc(fmtDate(r.left_at))+'</span>' : ''))}
            ${row('Completed', flag(r.completed, 'Yes', 'No'))}
            ${row('Abandoned', r.abandoned ? '<span class="text-red-600 font-bold">Yes</span>' : '<span class="text-slate-400">No</span>')}
            ${d.events.length ? `<div class="mt-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Recent events</div>
                <div class="mt-1 space-y-1">${d.events.map(ev => `<div class="flex justify-between text-[11px]"><span class="text-slate-600">${esc(ev.label)}</span><span class="text-slate-400">${esc(fmtDateTime(ev.at))}</span></div>`).join('')}</div>` : ''}
            <button class="mp-openuser mt-3 w-full h-8 rounded-md bg-teal-600 text-white text-[12px] font-semibold" data-user="${userId}">Open full user activity</button>`));
        const ou = $('#mpCard .mp-openuser');
        if (ou) ou.onclick = () => { closeMini(); openUserModal(+ou.dataset.user, gameId); };
    }
    function miniShell(title, inner){
        return `<div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 sticky top-0 bg-white">
            <div class="text-[11px] font-bold uppercase tracking-widest text-teal-600">${esc(title)}</div>
            <button class="mp-close h-7 w-7 grid place-items-center rounded-md text-slate-400 hover:bg-slate-100">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button></div><div class="p-4">${inner}</div>`;
    }

    function closeAll(){ closeMini(); closeDrill(); closeUserModal(); }

    /* ================= loaders ================= */
    let tableTimer;
    async function loadTable(){
        $('#userRows').innerHTML = `<tr><td colspan="9" class="px-4 py-8 text-center text-slate-300 text-sm">Loading…</td></tr>`;
        try {
            const d = await api('users.php', { page: state.page, per_page: state.perPage, search: state.search, behaviour: state.behaviour, sort: state.sort, dir: state.dir });
            if (d.error) { $('#userRows').innerHTML = `<tr><td colspan="9" class="px-4 py-8 text-center text-red-500 text-sm">${esc(d.message)}</td></tr>`; return; }
            renderTable(d);
        } catch (e) {
            $('#userRows').innerHTML = `<tr><td colspan="9" class="px-4 py-8 text-center text-red-500 text-sm">${esc(e.message || 'Failed to load')}</td></tr>`;
        }
    }
    async function loadAnalytics(){
        const rn = rangeLabel();
        $('#trendRangeLabel').textContent = rn;
        $('#engRangeLabel').textContent = rn;
        $('#funnelRangeLabel').textContent = rn;
        try {
            const [s, t] = await Promise.all([ api('summary.php', rp()), api('trend.php', rp()) ]);
            if (s.error || t.error) return;
            summaryCache = s;
            renderKpis(s.kpis, rn);
            renderBehaviour(s.behaviour_distribution);
            renderEngagement(s.engagement_totals);
            renderFunnel(s.funnel);
            renderTrend(t.daily);
            renderTime(t.time_of_day);
        } catch (e) {
            if (e.forbidden) document.body.innerHTML = '<div style="padding:60px;text-align:center;font-family:sans-serif;color:#334155">Audit Log is available to Host accounts only.</div>';
        }
    }
    function reloadAll(){ loadAnalytics(); state.page = 1; loadTable(); }

    /* ================= events ================= */
    function setRangeChip(range){
        state.range = range;
        $$('#rangeChips button').forEach(x => { const on = x.dataset.range === range; x.classList.toggle('bg-teal-600', on); x.classList.toggle('text-white', on); x.classList.toggle('text-slate-500', !on); });
        $('#customRange').classList.toggle('hidden', range !== 'custom');
        $('#customRange').classList.toggle('flex', range === 'custom');
    }
    function applyRange(){
        loadAnalytics();
        if (state.um) loadUserDetail();
        syncUrl(true);
    }
    $('#rangeChips').addEventListener('click', e => {
        const b = e.target.closest('button[data-range]'); if (!b) return;
        setRangeChip(b.dataset.range);
        if (b.dataset.range === 'custom') {
            const t = summaryCache && summaryCache.range ? summaryCache.range.to : new Date().toISOString().slice(0,10);
            if (!state.cTo) { state.cTo = t; $('#crTo').value = t; }
            if (!state.cFrom) { const d = new Date(t); d.setDate(d.getDate()-13); state.cFrom = d.toISOString().slice(0,10); $('#crFrom').value = state.cFrom; }
            return; // wait for Apply
        }
        applyRange();
    });
    $('#crApply').onclick = () => {
        const f = $('#crFrom').value, t = $('#crTo').value;
        if (!f || !t) return;
        state.cFrom = f > t ? t : f;
        state.cTo   = f > t ? f : t;
        $('#crFrom').value = state.cFrom; $('#crTo').value = state.cTo;
        state.range = 'custom';
        applyRange();
    };
    $('#refreshBtn').onclick = reloadAll;
    $('#searchInput').addEventListener('input', e => { state.search = e.target.value.trim(); state.page = 1; clearTimeout(tableTimer); tableTimer = setTimeout(loadTable, 300); });
    $('#behaviourFilter').addEventListener('change', e => setBehaviour(e.target.value));
    $('#sortSelect').addEventListener('change', e => { [state.sort, state.dir] = e.target.value.split('|'); state.page = 1; loadTable(); });
    $('#umOverlay').onclick = closeUserModal;
    $('#dpOverlay').onclick = closeDrill;
    $('#mpOverlay').addEventListener('click', e => { if (e.target.id === 'mpOverlay') closeMini(); });
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (!$('#mpOverlay').classList.contains('hidden')) closeMini();
        else if (!$('#dpOverlay').classList.contains('hidden')) closeDrill();
        else if (!$('#umOverlay').classList.contains('hidden')) closeUserModal();
    });
    window.addEventListener('popstate', () => applyUrl(false));

    /* ================= URL bootstrap ================= */
    function applyUrl(initial){
        const q = new URLSearchParams(location.search);
        const range = q.get('range') || 'all';
        if (range === 'custom') {
            state.cFrom = q.get('from') || '';
            state.cTo   = q.get('to') || '';
            $('#crFrom').value = state.cFrom;
            $('#crTo').value = state.cTo;
        }
        setRangeChip(RANGE_NAME[range] ? range : 'all');
        state.behaviour = q.get('behaviour') || 'all';
        $('#behaviourFilter').value = state.behaviour;
        if (initial) reloadAll(); else { loadAnalytics(); state.page = 1; loadTable(); }
        // close everything then re-open per url
        state.um = null; state.dp = null;
        $('#umOverlay').classList.add('hidden'); $('#umModal').classList.add('translate-x-full');
        $('#dpOverlay').classList.add('hidden'); $('#dpPanel').classList.add('translate-x-full');
        closeMini();
        document.body.style.overflow = '';
        const user = +q.get('user'), game = +q.get('game'), day = q.get('day');
        if (user && game) openUserModal(user, game);
        else if (user) openUserModal(user);
        else if (game) openGameUsers(game);
        else if (day) openDayUsers(day);
        renderBreadcrumb();
    }

    applyUrl(true);
})();
</script>

<?php include "includes/footer.php"; ?>
