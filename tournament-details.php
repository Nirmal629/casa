<!-----Header------>
<?php include "includes/header.php"; ?>

<section class="tournament_Details bothSide_gap">
    <div class="cust_container">
        <div class="row">
            <div class="col-lg-6 col-md-12 col-12">
                <div class="image">
                    <img src="https://media.istockphoto.com/id/172486068/photo/badminton-player-with-a-racket-in-his-hand-hit-shuttlecock.jpg?s=612x612&w=0&k=20&c=kgXUcu_gFz6wwh11PmYPehy8bsYjsKV_vccBFSBalqw=" class="img" alt="tournament image" />
                </div>
            </div>
            <div class="col-lg-6 col-md-12 col-12">
                <div class="content">
                    <h4 class="name">Men's Doubles</h4>
                    <p class="desc">Lorem ipsum, dolor sit amet consectetur adipisicing elit. Et modi, aspernatur
                        expedita cupiditate at numquam autem! Voluptatum animi ipsam ut facere maiores est, sint
                        facilis perferendis, nihil porro quae eligendi!</p>
                    <div class="datelocation">
                        <i class="fa-regular fa-calendar"></i>
                        <span>Saturday, 27 December, 2025 at Casa Club</span>
                    </div>
                    <div class="datelocation">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>Casa Badminton club, 123 Main Street, Apt 4, Vancouver, BC V6B 1A1, CANADA</span>
                    </div>
                    <p class="amount">$40 Per Player</p>
                    <p class="desc">Men's Doubles</p>

                    <button type="button" data-bs-toggle="modal" data-bs-target="#tournamentRegis" class="btn btn-outline-secondary login-btn w-100 mb-3">Confirm My Spot</button>

                    <h4 class="amount" style="text-decoration: underline;">Overview</h4>
                    <p class="desc">Lorem ipsum dolor, sit amet consectetur adipisicing elit. Natus suscipit, quos similique officiis provident repudiandae placeat nostrum cum, doloribus maxime enim dolores quas sit culpa ipsa quia vero rerum ea.</p>
                    <p class="desc">Lorem ipsum dolor, sit amet consectetur adipisicing elit. Natus suscipit, quos similique officiis provident repudiandae placeat nostrum cum.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-----tournament-registration-modal------>
<div class="modal fade tournamentdet_modal" id="tournamentRegis" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="tournamentRegisLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tournamentRegisLabel" style="color: #000;">Confirm my spot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="teamForm">

                    <!-- Team Name -->
                    <div class="form-group">
                        <label>Team Name</label>
                        <input type="text" placeholder="Enter team name">
                    </div>

                    <!-- Player 1 -->
                    <div class="player-section">
                        <h4 class="miniheading">Player 1 Details</h4>

                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" placeholder="Enter player name">
                        </div>

                        <div class="form-group">
                            <label>Contact</label>
                            <input type="tel" placeholder="Enter contact number">
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" placeholder="Enter email address">
                        </div>

                        <div class="form-group">
                            <label>DOB</label>
                            <input type="date">
                        </div>
                        <div class="form-group">
                            <div class="checkbox-wrap">
                                <input type="checkbox" id="p1-existing">
                                <label for="p1-existing" class="mb-0">Existing Member</label>
                            </div>
                        </div>
                    </div>

                    <!-- Player 2 -->
                    <div class="player-section">
                        <h4 class="miniheading">Player 2 Details</h4>

                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" placeholder="Enter player name">
                        </div>

                        <div class="form-group">
                            <label>Contact</label>
                            <input type="tel" placeholder="Enter contact number">
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" placeholder="Enter email address">
                        </div>

                        <div class="form-group">
                            <label>DOB</label>
                            <input type="date">
                        </div>
                        <div class="form-group">
                            <div class="checkbox-wrap">
                                <input type="checkbox" id="p2-existing">
                                <label for="p2-existing" class="mb-0">Existing Member</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer gap-1">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" data-switch="#tournaRegis_thankyou">Submit</button>
            </div>
        </div>
    </div>
</div>

<!-----Thank You popup------->
<div class="modal fade" id="tournaRegis_thankyou" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="tournaRegis_thankyouLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0">
                <!-- <h5 class="modal-title" id="tournaRegis_thankyouLabel">Confirm my spot</h5> -->
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="text-success" width="75" height="75"
                        fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                        <path
                            d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z" />
                    </svg>
                </div>
                <div class="text-center">
                    <h2 style="font-size: 140%; color: #000; font-weight: 700;">Thank You !</h2>
                    <p>“Your registration is complete. Spot confirmed! Best of luck and see you at the event.”</p>
                    <a href="https://casainfotech.com/" class="btn btn-outline-success">Back Home</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!------footer------>
<?php include "includes/footer.php"; ?>

<script>
    document.querySelectorAll('[data-switch]').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetModal = document.querySelector(this.dataset.switch);

            // Close currently open modal
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                bootstrap.Modal.getInstance(openModal).hide();
            }

            // Open target modal after close animation
            setTimeout(() => {
                new bootstrap.Modal(targetModal).show();
            }, 300);
        });
    });
</script>