<?php
// Single source of truth passed down from PlayerController
$player_id = (int) ($current_user_id ?? ($_SESSION['user_id'] ?? 0));
$host_id = (int) ($host_id ?? ($_SESSION['mapped_host_id'] ?? 0));

require_once __DIR__ . '/../../Helpers/player_ledger_helper.php';
require_once __DIR__ . '/../../Helpers/calendar_helper.php';


$selMonth = (int) ($selMonth ?? ($_GET['month'] ?? date('n')));
$selYear = (int) ($selYear ?? ($_GET['year'] ?? date('Y')));
$monthName = date('F', mktime(0, 0, 0, $selMonth, 1, $selYear));
$currentDate = date('Y-m-d');
$currentTime = date('H:i');
$premiumStatus = $premiumStatus ?? 'Y';
?>
<?php if ($premiumStatus != 'Y'): ?>
    <p class="premium-disabled-warning">Contact admin to enable the premium account</p>
<?php endif; ?>

<div class="pt-1 pb-1 px-3 sm:px-6 player-payment-wrapper <?= ($premiumStatus != 'Y') ? 'disabled-premium' : '' ?>">
    <div class="max-w-5xl mx-auto space-y-2">

        <!-- --- DIRECT MONTHLY LEDGER VIEW --- -->
        <div class="monthly-ledger-card">
            <div class="monthly-ledger-header">
                <div class="monthly-ledger-title-wrap">
                    <h2 id="directLedgerTitle" class="monthly-ledger-title">
                        Monthly Ledger — <?= htmlspecialchars($monthName) ?> <?= $selYear ?>
                    </h2>
                </div>
                <div class="monthly-ledger-controls d-flex align-items-center gap-2">
                    <select id="directLedgerMonth" class="form-select form-select-sm monthly-ledger-select" data-casa-dropdown data-size="sm" data-icon="fa-regular fa-calendar-days" onchange="changeDirectMonthlyLedger()">
                        <?= renderMonthOptions($selMonth, false) ?>
                    </select>
                    <select id="directLedgerYear" class="form-select form-select-sm monthly-ledger-select" data-casa-dropdown data-size="sm" data-icon="fa-regular fa-calendar" onchange="changeDirectMonthlyLedger()">
                        <?= renderYearOptions($selYear, 2024, max((int) date('Y') + 2, 2030)) ?>
                    </select>
                </div>
            </div>

            <div class="patmentTb">
                <?php echo renderPlayerSideLedgerHtml($conn, $player_id, $selYear, $selMonth, $host_id); ?>
            </div>
        </div>

        <script>
            function changeDirectMonthlyLedger() {
                const monthSelect = document.getElementById('directLedgerMonth');
                const yearSelect = document.getElementById('directLedgerYear');
                if (!monthSelect || !yearSelect) return;

                const month = monthSelect.value;
                const year = yearSelect.value;
                const monthNames = ["", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

                const titleElem = document.getElementById('directLedgerTitle');
                if (titleElem) {
                    titleElem.innerText = `Monthly Ledger — ${monthNames[parseInt(month, 10)] || ''} ${year}`;
                }

                const payYearInput = document.getElementById('payyear');
                const payMonthInput = document.getElementById('paymonth');
                if (payYearInput) payYearInput.value = year;
                if (payMonthInput) payMonthInput.value = month;

                // Keep tab=Play-Payment in URL so tab state is never lost
                const url = new URL(window.location.href);
                url.searchParams.set('tab', 'Play-Payment');
                url.searchParams.set('month', month);
                url.searchParams.set('year', year);
                window.history.replaceState({}, '', url.toString());

                if (window.CasaLedger && typeof window.CasaLedger.loadMonthlyLedger === 'function') {
                    window.CasaLedger.loadMonthlyLedger('<?= $player_id ?>', year, month);
                } else {
                    const container = document.querySelector('.monthly-ledger-card .patmentTb');
                    if (container) {
                        container.innerHTML = `<div class="flex items-center justify-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-500"></div></div>`;
                    }

                    const formData = new FormData();
                    formData.append('action_type', 'fetch_monthly_ledger');
                    formData.append('host_id', '<?= $host_id ?>');
                    formData.append('year', year);
                    formData.append('month', month);
                    formData.append('is_player_side', '1');

                    fetch('/api/player-pay-ledger', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => {
                            if (!response.ok) throw new Error('HTTP ' + response.status);
                            return response.text();
                        })
                        .then(html => {
                            if (container) {
                                if (html.includes('<html') || html.includes('<!DOCTYPE') || html.includes('id="Play-Payment"')) {
                                    container.innerHTML = `<div class="text-center py-12 text-rose-500 font-medium">Session expired or invalid response. Please refresh the page.</div>`;
                                } else {
                                    container.innerHTML = html;
                                }
                            }
                        })
                        .catch(err => {
                            if (container) {
                                container.innerHTML = `<div class="text-center py-12 text-rose-500 font-medium">Failed to load ledger: ${err.message}</div>`;
                            }
                        });
                }
            }

            window.resetPaymentTabToCurrentMonth = function () {
                const currentM = '<?= $selMonth ?>';
                const currentY = '<?= $selYear ?>';
                const monthSelect = document.getElementById('directLedgerMonth');
                const yearSelect = document.getElementById('directLedgerYear');
                if (monthSelect && yearSelect) {
                    if (monthSelect.value !== currentM || yearSelect.value !== currentY) {
                        monthSelect.value = currentM;
                        yearSelect.value = currentY;
                        changeDirectMonthlyLedger();
                    }
                }
            };
        </script>

    </div>

    <!-- Payment Modal -->
    <section class="customModal_wrap PayAmountModal">
        <div class="customModal_body">
            <h6 class="customModal_head">Enter Your Payment Details</h6>
            <button class="customModal_close btn PayAmountModal_close" type="button" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="customModal_content">
                <form id="payAmountForm" onsubmit="return false;">
                    <!-- Total Amount & Due Summary -->
                    <div class="d-flex align-items-center justify-content-between mb-3 px-3 py-2 bg-light rounded border text-muted small">
                        <span id="tot_amnt">Total Amount: <strong>$0.00</strong></span>
                        <span id="due">Due: <strong>$0.00</strong></span>
                    </div>
                    <input type="hidden" class="game_dt" id="game_id" value="" />
                    <input type="hidden" id="user_id" value="<?= htmlspecialchars((string)($player_id ?? '')) ?>" />
                    <input type="hidden" id="payhost" value="<?= htmlspecialchars((string)($host_id ?? '')) ?>" />
                    <input type="hidden" id="payyear" value="<?= htmlspecialchars((string)($selYear ?? '')) ?>" />
                    <input type="hidden" id="paymonth" value="<?= htmlspecialchars((string)($selMonth ?? '')) ?>" />
                    <input type="hidden" id="due_amt" value="" />

                    <div class="row g-3">
                        <!-- Amount -->
                        <div class="col-md-4 col-12 mb-2">
                            <label for="Amount" class="form-label fw-bold">Amount<span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="Amount" placeholder="0.00" required>
                        </div>

                        <!-- Payment Type -->
                        <div class="col-md-4 col-12 mb-2">
                            <label for="paymentType" class="form-label fw-bold">Payment Type<span class="text-danger">*</span></label>
                            <select class="form-select form-control" id="paymentType" required>
                                <option value="Interac">Interac</option>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- DATE & TIME -->
                        <div class="col-md-4 col-12 mb-2">
                            <label for="payment_datetime" class="form-label fw-bold text-uppercase" style="font-size: 0.82rem; letter-spacing: 0.5px; color: #3b5998;">DATE & TIME<span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control rounded-3" id="payment_datetime" value="<?= htmlspecialchars(($currentDate ?? date('Y-m-d')) . 'T' . ($currentTime ?? date('H:i'))) ?>" required>
                            <input type="hidden" id="date" value="<?= htmlspecialchars($currentDate ?? date('Y-m-d')) ?>" />
                            <input type="hidden" id="time" value="<?= htmlspecialchars($currentTime ?? date('H:i')) ?>" />
                        </div>

                        <!-- Remark / Payment Details -->
                        <div class="col-md-12 col-12 mb-3">
                            <label for="details" class="form-label">Remark / Payment Details (Optional)</label>
                            <textarea class="form-control" id="details" rows="2" placeholder="Enter payment details or remarks (e.g. Interac Reference No.)..."></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-12 text-center mt-2">
                            <button type="button" class="btn btn-primary save_payment px-4">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Payment & Approval History Modal -->
    <section class="customModal_wrap hostgameview_modal hostgameview_modal_payment">
        <div class="customModal_body payment-history-modal-body">
            <h6 class="customModal_head payment-history-modal-head">
                <i class="fa-solid fa-clock-rotate-left text-teal-600"></i> Payment & Approval History
            </h6>
            <button class="customModal_close btn p-0 border-0 shadow-none bg-transparent payment-history-modal-close" type="button" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="customModal_content payment-history-modal-content">
                <div class="playerList-container" id="playerList_payment"></div>
            </div>
        </div>
    </section>

    <script>
        // Consolidated datetime synchronization helper
        function syncPaymentDateTime() {
            const dtEl = document.getElementById('payment_datetime');
            if (dtEl && dtEl.value && dtEl.value.includes('T')) {
                const parts = dtEl.value.split('T');
                const dEl = document.getElementById('date');
                const tEl = document.getElementById('time');
                if (dEl) dEl.value = parts[0];
                if (tEl) tEl.value = parts[1];
            }
        }
        document.addEventListener('change', function(e) {
            if (e.target && e.target.id === 'payment_datetime') syncPaymentDateTime();
        });
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('save_payment')) syncPaymentDateTime();
        });
    </script>
</div>