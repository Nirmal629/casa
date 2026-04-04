<!-----Header------>
<?php include "includes/header.php"; ?>

<style>
    body {
        background-color: #020617;
        font-family: 'Inter', sans-serif;
        color: white;
    }

    .card {
        color: #504f4f !important;
    }

    .court-box {
        background: #0f172a;
        border-radius: 24px;
        border: 1px solid rgba(13, 202, 240, 0.2);
        transition: all 0.3s ease;
    }

    /* 4-Quadrant Court Grid */
    .court-grid {
        background: #1e40af;
        border: 4px solid #fff;
        height: 320px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1fr 1fr;
        gap: 2px;
        position: relative;
    }

    .court-quadrant {
        cursor: pointer;
        transition: 0.2s;
        position: relative;
    }

    .court-quadrant:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .player-circle {
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-weight: bold;
        font-size: 0.9rem;
    }

    .birdie-dot {
        width: 12px;
        height: 12px;
        background: #ffc107;
        border-radius: 50%;
        box-shadow: 0 0 10px #ffc107;
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(0.8);
            opacity: 0.6;
        }

        50% {
            transform: scale(1.2);
            opacity: 1;
        }

        100% {
            transform: scale(0.8);
            opacity: 0.6;
        }
    }

    .net-line {
        position: absolute;
        left: 50%;
        top: 0;
        bottom: 0;
        border-left: 3px solid white;
        z-index: 10;
        transform: translateX(-50%);
    }

    .hidden {
        display: none !important;
    }

    .animate-pulse {
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(0.9);
            opacity: 0.7;
        }

        50% {
            transform: scale(1.2);
            opacity: 1;
            text-shadow: 0 0 10px #ffc107;
        }

        100% {
            transform: scale(0.9);
            opacity: 0.7;
        }
    }
</style>

<!-----casa-trainers------->
<section class="casatrainers_sec bothSide_gap">
    <div class="cust_container">
        <h4 class="sub_heading">Casa for trainers</h4>
        <h2 class="heading">Casa Badminton Club For Host/Trainer</h2>
        <!-- <p class="desc">At The Batminton Club, we don’t just play badminton — we elevate it.</p> -->

        <div class="integrated_sec">
            <div class="card-grid">
                <div class="card fullCard">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="icon"><i class="fa-solid fa-user-tie"></i></div>
                        <span class="tag">Trainers</span>
                    </div>
                    <h3 class="mb-2">Trainer - Azhar</h3>
                    <p>We’re excited to offer professional badminton training for men, women, and kids, with flexible session options to fit your schedule.</p>
                    <div class="row">
                        <div class="col-md-6 col-12 mb-2">
                            <p>⏰ Training Schedule:</p>
                            <ul>
                                <li>Weekday - Monday to Friday: Available on call </li>
                                <li>Weekend - Saturday & Sunday : Full-day availability</li>
                            </ul>
                        </div>
                        <div class="col-md-6 col-12 mb-2">
                            <p>💲 Session Cost Details:</p>
                            <ul>
                                <li>Private: $60 player, per 1hr session</li>
                                <li>Semi private: $30 player, per 2hr session (2-3 player)</li>
                                <li>Interac ID will be shared by Casa Club</li>
                                <li>We handle the court reservations and trainer coordination</li>
                            </ul>
                        </div>
                        <div class="col-md-6 col-12 mb-2">
                            <p>📅 Booking:</p>
                            <ul>
                                <li>To book a session, reach out to us! Scheduling will be based on trainer, court, and player availability.</li>
                            </ul>
                            <p>Kids’ Training Sessions</p>
                            <ul>
                                <li>Specially designed programs for children that focus on skill-building, discipline, teamwork, and confidence while keeping learning fun.</li>
                            </ul>
                            <p>Adults’ Training Sessions</p>
                            <ul>
                                <li>Structured coaching for beginners and advanced players, helping adults improve techniques, fitness, and strategy at their own pace.</li>
                            </ul>
                        </div>
                        <div class="col-md-6 col-12 mb-2">
                            <p>Warm regards,</p>
                            <ul>
                                <li>🏸 Casa Badminton Club</li>
                                <li>📞 +1 437 981 0512 </li>
                                <li><a href="https://casainfotech.com/staging">🌐 https://casainfotech.com/staging</a></li>
                                <li>💪 Stay fit and active!</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

<!----trainer_sec------->
<section id="trainer_sec" class="py-5"
    style="background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)), url('./assets/images/trainer-bg.jpeg'); 
                background-size: cover; 
                background-position: center; 
                background-attachment: fixed;
                min-height: 70vh;">

    <div class="container px-4">
        <div class="text-center mb-5 d-flex flex-column align-items-center">
            <h6 class="text-info mb-2 text-uppercase fw-bold" style="letter-spacing: 2px;">Casa for Trainers</h6>
            <h2 class="display-5 text-white fw-bold mb-4">Professional Coaching</h2>

            <div style="max-width: 800px;">
                <p class="lead text-white fw-bold mb-3">
                    Elevate your game with Trainer Azhar. Professional training for men, women, and kids.
                </p>
            </div>

            <div class="d-flex align-items-center justify-content-center w-100 pt-5 mb-4">
                <span class="px-4 py-2 rounded-pill fw-bold text-uppercase"
                    style="background-color: rgba(255, 255, 255, 0.1); 
                             border: 1px solid rgba(255,255,255,0.4); 
                             color: #fff; 
                             font-size: 0.8rem;
                             backdrop-filter: blur(10px);">
                    Training Programs
                </span>
            </div>
        </div>

        <div class="row g-4 justify-content-center">

            <div class="col-lg-4 col-md-6">
                <div class="h-100 p-4 text-center glass-card"
                    role="button" data-bs-toggle="modal" data-bs-target="#pricingModal"
                    style="cursor: pointer;">
                    <div class="icon-circle mb-3 bg-info text-dark">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <h5 class="fw-bold text-white mb-2">Pricing & Timing</h5>
                    <p class="text-white small mb-0 opacity-75">Private and semi-private sessions available weekdays and weekends. Click for rates.</p>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="h-100 p-4 text-center glass-card">
                    <div class="icon-circle mb-3 bg-white text-dark">
                        <i class="fa-solid fa-child-reaching"></i>
                    </div>
                    <h5 class="fw-bold text-white mb-2">Kids’ Sessions</h5>
                    <p class="text-white small mb-0 opacity-75">Focusing on skill-building, discipline, and confidence through fun, active learning.</p>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="h-100 p-4 text-center glass-card">
                    <div class="icon-circle mb-3 bg-white text-dark">
                        <i class="fa-solid fa-user-ninja"></i>
                    </div>
                    <h5 class="fw-bold text-white mb-2">Adults’ Sessions</h5>
                    <p class="text-white small mb-0 opacity-75">Structured coaching for all levels to improve technique, fitness, and match strategy.</p>
                </div>
            </div>

        </div>

        <div class="mt-5 text-center">
            <p class="text-white mb-3 opacity-75">Ready to start your first session?</p>
            <button type="button"
                class="btn btn-info rounded-pill px-5 fw-bold py-2 shadow-lg"
                data-bs-toggle="modal"
                data-bs-target="#contactTrainerModal">
                Book Trainer Azhar
            </button>
        </div>

    </div>
</section>

<!-----Prising Modal----->
<div class="modal fade" id="pricingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background: #0f172a; border-radius: 20px; overflow: hidden; border: 1px solid rgba(13, 202, 240, 0.3) !important;">

            <div class="modal-header border-0 p-4" style="background: linear-gradient(135deg, #1e293b, #0f172a);">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fa-solid fa-calendar-days text-info me-2"></i> Schedule & Rates
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4 text-white">
                <h6 class="text-info fw-bold small text-uppercase mb-3">⏰ Training Schedule</h6>
                <div class="bg-dark p-3 rounded-3 mb-4">
                    <div class="d-flex justify-content-between small mb-2">
                        <span>Weekdays (Mon-Fri)</span>
                        <span class="text-info">Available on Call</span>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span>Weekends (Sat-Sun)</span>
                        <span class="text-info">Full Day</span>
                    </div>
                </div>

                <h6 class="text-info fw-bold small text-uppercase mb-3">💲 Session Cost</h6>
                <div class="row g-2 mb-4">
                    <div class="col-6">
                        <div class="p-3 text-center rounded-3" style="background: rgba(255,255,255,0.05);">
                            <h4 class="fw-bold mb-0">$80</h4>
                            <p class="small opacity-50 mb-0">Private (1hr)</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 text-center rounded-3" style="background: rgba(255,255,255,0.05);">
                            <h4 class="fw-bold mb-0">$40</h4>
                            <p class="small opacity-50 mb-0">Semi-Private (1hr)</p>
                        </div>
                    </div>
                </div>

                <div class="small opacity-75 p-3 rounded-3" style="background: rgba(13, 202, 240, 0.05);">
                    <p class="mb-2"><i class="fa-solid fa-check text-info me-2"></i> Interac ID will be shared by Casa</p>
                    <p class="mb-0"><i class="fa-solid fa-check text-info me-2"></i> We handle court reservations & coordination</p>
                </div>
            </div>

            <div class="modal-footer border-0 p-3 bg-dark">
                <button type="button" class="btn btn-outline-info w-100 fw-bold py-2 rounded-pill" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-----Contact Trainer Modal----->
<div class="modal fade" id="contactTrainerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background: #0f172a; border-radius: 20px; overflow: hidden; border: 1px solid rgba(13, 202, 240, 0.3) !important;">

            <div class="modal-header border-0 p-4" style="background: linear-gradient(135deg, #0ea5e9, #2563eb);">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fa-solid fa-paper-plane me-2"></i> Get in Touch
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4 text-white text-center">
                <div class="mb-4">
                    <div class="icon-circle bg-white text-dark mb-3 mx-auto" style="width: 70px; height: 70px; font-size: 1.8rem;">
                        <i class="fa-solid fa-comment-dots"></i>
                    </div>
                    <h5 class="fw-bold">Contact for Booking</h5>
                    <p class="small opacity-75">To schedule your session with Azhar, please reach out via our official channels.</p>
                </div>

                <div class="d-grid gap-3">
                    <a href="mailto:info.casagames@gmail.com" class="btn btn-outline-light d-flex align-items-center justify-content-center py-3 rounded-4">
                        <i class="fa-solid fa-envelope text-info me-3"></i>
                        <span>info.casagames@gmail.com</span>
                    </a>

                    <a href="https://casa-games.com" target="_blank" class="btn btn-outline-light d-flex align-items-center justify-content-center py-3 rounded-4">
                        <i class="fa-solid fa-globe text-info me-3"></i>
                        <span>Visit Official Portal</span>
                    </a>
                </div>

                <div class="mt-4 p-3 rounded-3" style="background: rgba(255,255,255,0.05);">
                    <p class="small mb-0 italic">Scheduling is based on trainer, court, and player availability.</p>
                </div>
            </div>

            <div class="modal-footer border-0 p-3 bg-dark">
                <p class="w-100 text-center small text-info mb-0 fw-bold">💪 Stay fit and active!</p>
            </div>
        </div>
    </div>
</div>


<!--------#####################--------->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casa Badminton Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="py-5">
    <div class="container">
        <div id="app-container">
        </div>
    </div>
    <audio id="pointSound" src="https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3"></audio>
    <script>
        const CourtApp = {
            state: {
                view: 'setup', // setup, active, summary
                matchSettings: {
                    type: 'doubles',
                    deuce: 'deuce'
                },
                teams: {
                    t1: {
                        name: '',
                        p1: '',
                        p2: '',
                        setsWon: 0
                    },
                    t2: {
                        name: '',
                        p1: '',
                        p2: '',
                        setsWon: 0
                    }
                },
                currentSet: 1,
                scores: {
                    t1: 0,
                    t2: 0
                },
                setHistory: [],
                undoStack: [],
                birdiePos: 1 // 1=T1-L, 2=T2-R, 3=T1-R, 4=T2-L
            },

            init() {
                this.render();
            },

            // --- TEMPLATES ---

            tplSetup() {
                return `
        <div class="court-box mx-auto p-4 shadow-lg" style="max-width: 450px;">
            <h5 class="text-info fw-bold mb-4"><i class="fa-solid fa-sliders me-2"></i> Initial Setup</h5>
            <div class="mb-3 p-3 bg-dark rounded-3">
                <input type="text" id="t1n" class="form-control bg-transparent text-white border-0 border-bottom mb-2" placeholder="Team 1 Name">
                <div class="row g-2">
                    <div class="col-6"><input type="text" id="t1p1" class="form-control bg-black text-white-50 border-0" placeholder="P1 Name"></div>
                    <div class="col-6"><input type="text" id="t1p2" class="form-control bg-black text-white-50 border-0" placeholder="P2 Name"></div>
                </div>
            </div>
            <div class="mb-3 p-3 bg-dark rounded-3">
                <input type="text" id="t2n" class="form-control bg-transparent text-white border-0 border-bottom mb-2" placeholder="Team 2 Name">
                <div class="row g-2">
                    <div class="col-6"><input type="text" id="t2p1" class="form-control bg-black text-white-50 border-0" placeholder="P1 Name"></div>
                    <div class="col-6"><input type="text" id="t2p2" class="form-control bg-black text-white-50 border-0" placeholder="P2 Name"></div>
                </div>
            </div>
            <div class="row g-2 mb-4">
                <div class="col-6">
                    <select id="m_type" class="form-select bg-dark text-white border-secondary">
                        <option value="doubles">Doubles</option>
                        <option value="singles">Singles</option>
                    </select>
                </div>
                <div class="col-6">
                    <select id="d_type" class="form-select bg-dark text-white border-secondary">
                        <option value="deuce">Deuce On</option>
                        <option value="no-deuce">No Deuce</option>
                    </select>
                </div>
            </div>
            <button onclick="CourtApp.startMatch()" class="btn btn-info w-100 fw-bold py-2 rounded-pill">START MATCH</button>
        </div>`;
            },

            tplActive() {
                return `
        <div class="court-box mx-auto p-3 shadow-lg" style="max-width: 500px; border: 2px solid #0ea5e9;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button onclick="CourtApp.undo()" class="btn btn-sm btn-outline-warning">UNDO</button>
                <div class="text-center">
                    <div class="fw-bold small">SET ${this.state.currentSet}</div>
                    <div class="text-info h5 mb-0">${this.state.scores.t1} - ${this.state.scores.t2}</div>
                </div>
                <button onclick="CourtApp.finishSet()" class="btn btn-sm btn-outline-danger">FINISH SET</button>
            </div>

            <div class="court-grid rounded-3 mb-4">
                <div class="net-line"></div>
                ${[1, 2, 3, 4].map(i => `
                    <div class="court-quadrant d-flex flex-column align-items-center justify-content-center" onclick="CourtApp.setBirdie(${i})">
                        <div class="player-circle ${this.state.birdiePos === i ? 'bg-white text-dark' : 'border border-white text-white opacity-50'}">
                            ${this.getPlayerInitials(i)}
                        </div>
                        ${this.state.birdiePos === i ? '<div class="birdie-dot mt-2"></div>' : ''}
                    </div>
                `).join('')}
            </div>

            <div class="row g-2">
                <div class="col-6">
                    <button onclick="CourtApp.addPoint('t1')" class="btn btn-info w-100 py-3 rounded-4 shadow">
                        <span class="small d-block opacity-75">${this.state.teams.t1.name}</span>
                        <i class="fa-solid fa-plus"></i> 1
                    </button>
                </div>
                <div class="col-6">
                    <button onclick="CourtApp.addPoint('t2')" class="btn btn-outline-info w-100 py-3 rounded-4">
                        <span class="small d-block opacity-75">${this.state.teams.t2.name}</span>
                        <i class="fa-solid fa-plus"></i> 1
                    </button>
                </div>
            </div>
        </div>`;
            },

            tplSummary() {
                const winner = this.state.teams.t1.setsWon > this.state.teams.t2.setsWon ? this.state.teams.t1 : this.state.teams.t2;
                return `
        <div class="court-box mx-auto p-4 shadow-lg text-center" style="max-width: 450px;">
            <h6 class="text-warning text-uppercase small mb-4">Match Summary</h6>
            <div class="bg-black p-4 rounded-4 border border-warning mb-4">
                <i class="fa-solid fa-trophy text-warning fa-3x mb-3"></i>
                <h3 class="text-success fw-bold">${winner.name}</h3>
                <p class="mb-1">${winner.p1} ${winner.p2 ? '& ' + winner.p2 : ''}</p>
                <div class="h2 text-info mt-3">${this.state.teams.t1.setsWon} - ${this.state.teams.t2.setsWon}</div>
            </div>
            <button onclick="location.reload()" class="btn btn-info w-100 rounded-pill py-2 fw-bold">NEW MATCH</button>
        </div>`;
            },

            // --- LOGIC ---

            startMatch() {
                // 1. Get references to the input elements
                const t1n = document.getElementById('t1n');
                const t2n = document.getElementById('t2n');
                const mType = document.getElementById('m_type');
                const dType = document.getElementById('d_type');

                // 2. Check if the elements actually exist in the DOM
                if (!t1n || !t2n) {
                    console.error("Input fields not found! Check your IDs.");
                    return;
                }

                // 3. Assign values to state
                this.state.teams.t1.name = t1n.value || 'Team 1';
                this.state.teams.t1.p1 = document.getElementById('t1p1')?.value || 'P1';
                this.state.teams.t1.p2 = document.getElementById('t1p2')?.value || '';

                this.state.teams.t2.name = t2n.value || 'Team 2';
                this.state.teams.t2.p1 = document.getElementById('t2p1')?.value || 'P1';
                this.state.teams.t2.p2 = document.getElementById('t2p2')?.value || '';

                this.state.matchSettings.type = mType.value;
                this.state.matchSettings.deuce = dType.value;

                // 4. Change view and re-render
                this.state.view = 'active';
                this.render();
            }

            addPoint(team) {
                this.state.undoStack.push(JSON.parse(JSON.stringify(this.state.scores)));
                this.state.scores[team]++;
                document.getElementById('pointSound').play();
                this.render();
            },

            undo() {
                if (this.state.undoStack.length > 0) {
                    this.state.scores = this.state.undoStack.pop();
                    this.render();
                }
            },

            setBirdie(pos) {
                this.state.birdiePos = pos;
                this.render();
            },

            finishSet() {
                this.state.setHistory.push({
                    ...this.state.scores
                });
                if (this.state.scores.t1 > this.state.scores.t2) this.state.teams.t1.setsWon++;
                else this.state.teams.t2.setsWon++;

                if (this.state.teams.t1.setsWon >= 2 || this.state.teams.t2.setsWon >= 2) {
                    this.state.view = 'summary';
                } else {
                    this.state.currentSet++;
                    this.state.scores = {
                        t1: 0,
                        t2: 0
                    };
                    this.state.undoStack = [];
                }
                this.render();
            },

            getPlayerInitials(pos) {
                if (pos === 1) return (this.state.teams.t1.p1[0] || 'T1').toUpperCase();
                if (pos === 2) return (this.state.teams.t2.p1[0] || 'T2').toUpperCase();
                if (pos === 3) return (this.state.teams.t1.p2[0] || 'T1').toUpperCase();
                if (pos === 4) return (this.state.teams.t2.p2[0] || 'T2').toUpperCase();
            },

            render() {
                const root = document.getElementById('app-container');
                if (this.state.view === 'setup') root.innerHTML = this.tplSetup();
                else if (this.state.view === 'active') root.innerHTML = this.tplActive();
                else if (this.state.view === 'summary') root.innerHTML = this.tplSummary();
            }
        };

        // Start the app
        CourtApp.init();
    </script>
</body>

</html>

<!------footer------>
<?php include "includes/footer.php"; ?>