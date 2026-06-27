<!----payment page----->

<div class="playarPayment_game">
    <div class="custom_card">
        <h6 class="card_heading">Player Payment List</h6>

        <div class="table-responsive">
            <table class="table table-success table-striped table-bordered">
                <thead>
                    <tr>
                        <th scope="col">Sl. No.</th>
                        <th scope="col">Profile</th>
                        <th scope="col">Player Name</th>
                        <th scope="col">Total Game</th>
                        <th scope="col">Total Amount</th>
                        <th scope="col">Total Payment</th>
                        <th scope="col">Total Due</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th scope="row">1</th>
                        <td>
                            <div class="profile_pic"><img src="assets/images/profile.jpg" class="img-fluid" alt="profile pic" /></div>
                        </td>
                        <td>Anurag GM</td>
                        <td>10</td>
                        <td>$ 100</td>
                        <td>$ 90</td>
                        <td>$ 10</td>
                        <td><button type="button" class="playPaymentModal_open btn btn-primary btn-sm">View More</button></td>
                    </tr>
                    <tr>
                        <th scope="row">2</th>
                        <td>
                            <div class="profile_pic"><img src="assets/images/profile.jpg" class="img-fluid" alt="profile pic" /></div>
                        </td>
                        <td>Nirmal Roythakur</td>
                        <td>5</td>
                        <td>$ 50</td>
                        <td>$ 50</td>
                        <td>$ 0</td>
                        <td><button type="button" class="playPaymentModal_open btn btn-primary btn-sm">View More</button></td>
                    </tr>
                    <tr>
                        <th scope="row">3</th>
                        <td>
                            <div class="profile_pic"><img src="assets/images/profile.jpg" class="img-fluid" alt="profile pic" /></div>
                        </td>
                        <td>Subham Roy</td>
                        <td>10</td>
                        <td>$ 100</td>
                        <td>$ 100</td>
                        <td>$ 0</td>
                        <td><button type="button" class="playPaymentModal_open btn btn-primary btn-sm">View More</button></td>
                    </tr>
                    <tr>
                        <th scope="row">4</th>
                        <td>
                            <div class="profile_pic"><img src="assets/images/profile.jpg" class="img-fluid" alt="profile pic" /></div>
                        </td>
                        <td>Uttam Ghosh</td>
                        <td>22</td>
                        <td>$ 220</td>
                        <td>$ 150</td>
                        <td>$ 70</td>
                        <td><button type="button" class="playPaymentModal_open btn btn-primary btn-sm">View More</button></td>
                    </tr>
                    <tr class="table-dark">
                        <th class="text-start" colspan="3">Total:</th>
                        <td>47</td>
                        <td>$ 470</td>
                        <td>$ 390</td>
                        <td>$ 80</td>
                        <td><span class="red">Pending</span></td>
                    </tr>
                </tbody>
            </table>
        </div>


        <!------Player-Modal----->
        <section class="customModal_wrap playPaymentModal">
            <div class="customModal_body">
                <h6 class="customModal_head">Anurag GM</h6>
                <button class="customModal_close btn playPaymentModal_close">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="customModal_content">

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr class="table-info">
                                    <th scope="col">Sl.</th>
                                    <th scope="col">Date & Time</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Payment</th>
                                    <th scope="col">Due</th>
                                    <th scope="col">Verify</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th scope="row">1</th>
                                    <td>Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</td>
                                    <td>$10</td>
                                    <td>$5</td>
                                    <td>$5</td>
                                    <td><span class="red">Pending</span></td>
                                </tr>
                                <tr>
                                    <th scope="row">2</th>
                                    <td>Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</td>
                                    <td>$10</td>
                                    <td>$10</td>
                                    <td>$0</td>
                                    <td><span class="green">Done</span></td>
                                </tr>
                                <tr>
                                    <th scope="row">3</th>
                                    <td>Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</td>
                                    <td>$10</td>
                                    <td>$10</td>
                                    <td>$0</td>
                                    <td><span class="green">Done</span></td>
                                </tr>
                                <tr>
                                    <th scope="row">4</th>
                                    <td>Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</td>
                                    <td>$10</td>
                                    <td>$10</td>
                                    <td>$0</td>
                                    <td><span class="green">Done</span></td>
                                </tr>
                                <tr>
                                    <th scope="row">5</th>
                                    <td>Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</td>
                                    <td>$10</td>
                                    <td>$10</td>
                                    <td>$0</td>
                                    <td><span class="green">Done</span></td>
                                </tr>

                                <tr class="table-dark">
                                    <th class="text-start" colspan="2">Total:</th>
                                    <td>$50</td>
                                    <td>$45</td>
                                    <td>$5</td>
                                    <td><span class="red">Pending</span></td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>


    </div>
</div>