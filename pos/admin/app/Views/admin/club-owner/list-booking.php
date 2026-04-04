<main id="main" class="main">

    <div class="pagetitle">
      <h1>List Booking</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?=base_url()?>club-owner/dashboard">Dashboard</a></li>
          <li class="breadcrumb-item">Manage Bookings</li>
          <li class="breadcrumb-item active">List Booking</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body">
              <!-- <h5 class="card-title">Booking History</h5> -->
              <!-- Table with stripped rows -->
              <div class="table-responsive">
                <table class="table table-bordered" id="bookingHistory">
                  <thead>
                    <tr>
                      <th>Booking ID</th>
                      <th>Court No</th>
                      <th>Name</th>
                      <th>Phone</th>
                      <th>Membership ID</th>
                      <th>Booking Date</th>
                      <th>Booking Time</th>
                      <th>Payment Type</th>
                      <th>Booking Type</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                        $bg = '#472eeb';
                        $color = '#fff';
                        foreach ($bookings as $key => $booking) {
                            /*if($booking['booking_status'] == 'Canceled'){
                                continue;
                            }*/
                            
                            if($booking['booking_status'] == 'Active'){
                                $bg = "#472eeb";
                                $color = "#fff";
                            }else if($booking['booking_status'] == 'Defaulter'){
                                $bg = "yellow";
                                $color = "#000";
                            }else if($booking['booking_status'] == 'Cancelled'){
                                $bg = "red";
                                $color = "#fff";
                            }else if($booking['booking_status'] == 'Completed'){
                                $bg = "green";
                                $color = "#fff";
                            }
                    ?>
                    <tr id="table_row_<?=$booking['id']?>">
                      <td>#<?= $booking['booking_key'] ?></td>
                      <td><?= $booking['court_id'] ?></td>
                      <td><?= $booking['name'] ?></td>
                      <td><?= $booking['phone'] ?></td>
                      <td><?= $booking['membership_id'] ?></td>
                      <td><?= date('jS F, Y', strtotime($booking['date'])) ?></td>
                      <td><?= date('h:i A', strtotime($booking['start_time'])) ?></td>
                      <td><?= $booking['payment_type'] ?></td>
                      <td><?= $booking['booking_type_text'] ?></td>
                      <td>
                          <select class="status_dropdown" style="background: <?=$bg?>;color: <?=$color?>" onchange="changeStatus(this.value, '<?= $booking['booking_key'] ?>')">
                              <option <?=isset($booking['booking_status']) && $booking['booking_status'] == 'Active' ? 'selected':''?> value="Active">Active</option>
                              <option <?=isset($booking['booking_status']) && $booking['booking_status'] == 'Defaulter' ? 'selected':''?> value="Defaulter">Defaulter</option>
                              <option <?=isset($booking['booking_status']) && $booking['booking_status'] == 'Cancelled' ? 'selected':''?> value="Cancelled">Cancelled</option>
                              <option <?=isset($booking['booking_status']) && $booking['booking_status'] == 'Completed' ? 'selected':''?> value="Completed">Completed</option>
                          </select>
                      </td>
                      <td>
                        <?php
                            if($booking['booking_type'] == 'new_booking'){

                        ?>
                        <a href="<?=base_url()?>club-owner/edit-booking/<?=$booking['id']?>" style="font-size: 20px;color: green;"><i class="bi bi-pencil"></i></a>
                        <?php } ?>
                        <a href="javascript:void(0)" style="font-size: 20px;" onclick="openModal('<?= $booking['id'] ?>')"><i class="bi bi-eye-fill"></i></a>
                        <a href="#" onclick="deleteItem(<?=$booking['id']?>, 'booking')" class="text-danger" style="font-size: 20px;"><i class="bi bi-trash"></i></a>
                      </td>
                    </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
              <!-- End Table with stripped rows -->

            </div>
          </div>

        </div>
      </div>
    </section>
    <!-- Modal -->
    <div class="modal fade" id="bookingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingModalLabel">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="row mb-2">
                        <div class="col bookingDetailsCol">
                            <label>Booking ID</label>
                        </div>
                        <div class="col bookingDetailsVal">
                            <label id="modalBookingID"></label>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col bookingDetailsCol">
                            <label>Membership ID</label>
                        </div>
                        <div class="col bookingDetailsVal">
                            <label id="modalMembershipID"></label>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col bookingDetailsCol">
                            <label>Name</label>
                        </div>
                        <div class="col bookingDetailsVal">
                            <label id="modalName"></label>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col bookingDetailsCol">
                            <label>Phone</label>
                        </div>
                        <div class="col bookingDetailsVal">
                            <label id="modalPhone"></label>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col bookingDetailsCol">
                            <label>Email</label>
                        </div>
                        <div class="col bookingDetailsVal">
                            <label id="modalEmail"></label>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col bookingDetailsCol">
                            <label>Date</label>
                        </div>
                        <div class="col bookingDetailsVal">
                            <label id="modalDate"></label>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col bookingDetailsCol">
                            <label>Time</label>
                        </div>
                        <div class="col bookingDetailsVal">
                            <label id="modalTime"></label>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col bookingDetailsCol">
                            <label>Duration</label>
                        </div>
                        <div class="col bookingDetailsVal">
                            <label id="modalDuration"></label>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col bookingDetailsCol">
                            <label>Card Number</label>
                        </div>
                        <div class="col bookingDetailsVal">
                            <label id="modalCardNumber"></label>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col bookingDetailsCol">
                            <label>Payment Type</label>
                        </div>
                        <div class="col bookingDetailsVal">
                            <label id="modalPaymentType"></label>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col bookingDetailsCol">
                            <label>Payment Status</label>
                        </div>
                        <div class="col bookingDetailsVal">
                            <label id="modalPaymentStatus"></label>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col bookingDetailsCol">
                            <label>No Change</label>
                        </div>
                        <div class="col bookingDetailsVal">
                            <label id="modalNoChange"></label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
  </main><!-- End #main -->
  <script>
    var bookings = <?= json_encode($bookings); ?>; // Convert PHP array to JavaScript array

    // Now you can use the 'bookings' variable in JavaScript
    console.log(bookings);  // This will show the data in the browser console

    function openModal(bookingID) {
        var booking = bookings.find(item => item.id === bookingID);  // Find the booking by ID
        
        // Set modal data dynamically
        document.getElementById("modalBookingID").innerText = "#" + booking.booking_key;
        document.getElementById("modalMembershipID").innerText = booking.membership_id;
        document.getElementById("modalName").innerText = booking.name;
        document.getElementById("modalPhone").innerText = booking.phone;
        document.getElementById("modalEmail").innerText = booking.email;
        document.getElementById("modalDate").innerText = booking.date;
        document.getElementById("modalTime").innerText = booking.start_time;
        document.getElementById("modalDuration").innerText = booking.duration+' hr';
        document.getElementById("modalCardNumber").innerText = booking.card_number;
        document.getElementById("modalPaymentType").innerText = booking.payment_type;
        document.getElementById("modalPaymentStatus").innerText = booking.payment_status;
        document.getElementById("modalNoChange").innerText = booking.no_change;

        // Open the modal
        new bootstrap.Modal(document.getElementById('bookingModal')).show();
    }
  </script>