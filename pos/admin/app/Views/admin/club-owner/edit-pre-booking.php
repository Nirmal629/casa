<?php
  $currentMonth = date('n'); // 'n' returns the current month as a number (1-12)

  // Array of month names
  $months = [
      "January", "February", "March", "April", "May", "June", 
      "July", "August", "September", "October", "November", "December"
  ];
?>
<main id="main" class="main">

    <div class="pagetitle">
      <div class="row">
        <div class="col-md-6 col-xs-12 col-sm-12">
          <h1>Modify Pre Booking</h1>
          <nav>
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?=base_url()?>club-owner/dashboard">Dashboard</a></li>
              <li class="breadcrumb-item">Manage Bookings</li>
              <li class="breadcrumb-item active">Modify Pre Booking</li>
            </ol>
          </nav>
        </div>
        <div class="col-md-6 col-xs-12 col-sm-12 text-end">
          <a href="<?=base_url()?>club-owner/dashboard"><button type="button" class="btn btn-success">Dashboard</button></a>
          <a href="<?=base_url()?>club-owner/list-pre-booking"><button type="button" class="btn btn-primary">List Pre Booking</button></a>
        </div>
        <div class="clearfix"></div>
      </div>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row" >
        <div class="col-lg-12" >

          <div class="card" style="padding: 10px 0px">
            <?php if(isset($validation)):?>
              <div class="alert alert-danger" style="margin-bottom: 30px;"><?= $validation->listErrors() ?></div>
            <?php endif;?>
            <?php if(isset($error_message)): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px;">
                    <?= $error_message ?>
                </div>
            <?php endif; ?>
            <form class="row g-3 needs-validation" action="<?=base_url()?>club-owner/modifyprebooking" method="post" novalidate>
              <input type="hidden" name="booking_type" value="pre_booking">
              <input type="hidden" name="booking_key" value="<?=$booking_key?>">
            <div class="card-body">
                <div class="row mb-3">
                  <div class="col-md-6 colsm-12">
                    <label for="membership_id" class="col-sm-12 col-form-label">Membership ID<small class="text-danger">*</small></label>
                    <div class="col-sm-12">
                      <input type="text" class="form-control" placeholder="Enter membership id" id="membership_id" name="membership_id" readonly oninput="getMembershipInfo(this.value)" value="<?=$bookings[0]['membership_id']?>">
                      <div class="invalid-feedback">Please enter the membership id!</div>
                    </div>
                  </div>
                  <div class="col-md-6 colsm-12">
                    <label for="name" class="col-sm-12 col-form-label">Name<small class="text-danger">*</small></label>
                    <div class="col-sm-12">
                      <input type="text" class="form-control" placeholder="Enter name" id="name" name="name" readonly value="<?=$bookings[0]['name']?>">
                      <div class="invalid-feedback">Please enter the name!</div>
                    </div>
                  </div>
                  <div class="col-md-6 colsm-12">
                    <label for="phone" class="col-sm-12 col-form-label">Phone<small class="text-danger">*</small></label>
                    <div class="col-sm-12">
                      <input type="text" class="form-control phone-input" placeholder="Enter phone number" id="phone" name="phone" readonly value="<?=$bookings[0]['phone']?>">
                      <div class="invalid-feedback">Please enter the phone number!</div>
                    </div>
                  </div>
                  <div class="col-md-6 colsm-12">
                    <label for="email" class="col-sm-12 col-form-label">Email<small class="text-danger">*</small></label>
                    <div class="col-sm-12">
                      <input type="email" class="form-control" placeholder="Enter email address" id="email" name="email" readonly value="<?=$bookings[0]['email']?>">
                      <div class="invalid-feedback">Please enter the email address!</div>
                    </div>
                  </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12 col-sm-12">
                        <label>Pre Booking Set 
                            <button type="button" class="btn btn-success" id="add_booking_set">+ Add New</button>
                        </label>
                        
                        <div id="booking_sets_container" class="d-flex flex-wrap">
                            <!-- The first set will appear here initially -->
                            <?php
                              foreach($bookings as $key => $value){
                                $currentDate = date('Y-m-d');
                                $bookingDate = $value['date'];  // Assuming the 'date' format is Y-m-d

                                $opacity = ($value['booking_status'] == 'Canceled') ? '0.2' : '5';
                                // Check if the booking date has already passed
                                $disableCancel = (strtotime($bookingDate) < strtotime($currentDate) || $value['booking_status'] == 'Canceled') ? true : false;
                            ?>
                            <div class="booking-set d-flex align-items-center" id="booking_set_<?=$value['id']?>" data-booking-id="<?=$value['id']?>" style="opacity: <?=$opacity?>;">
                                <div class="booking_set_list">
                                    <label class="col-form-label" style="width: 100%;">Booking Day<small class="text-danger">*</small></label>
                                    <select class="form-control" required name="booking_day[]" readonly>
                                        <option value="Monday" <?=(isset($value) && $value['day'] == 'Monday' ? 'selected': '')?>>Monday</option>
                                        <option value="Tuesday" <?=(isset($value) && $value['day'] == 'Tuesday' ? 'selected': '')?>>Tuesday</option>
                                        <option value="Wednessday" <?=(isset($value) && $value['day'] == 'Wednessday' ? 'selected': '')?>>Wednessday</option>
                                        <option value="Thrushday" <?=(isset($value) && $value['day'] == 'Thrushday' ? 'selected': '')?>>Thrushday</option>
                                        <option value="Friday" <?=(isset($value) && $value['day'] == 'Friday' ? 'selected': '')?>>Friday</option>
                                        <option value="Saturday" <?=(isset($value) && $value['day'] == 'Saturday' ? 'selected': '')?>>Saturday</option>
                                        <option value="Sunday" <?=(isset($value) && $value['day'] == 'Sunday' ? 'selected': '')?>>Sunday</option>
                                    </select>
                                </div>
                                <div class="booking_set_list">
                                    <label class="col-form-label" style="width: 100%;">Start Date<small class="text-danger">*</small></label>
                                    <input type="text" class="form-control mx-1 datePicker" placeholder="Start date" value="<?=$value['date']?>" name="date[]" id="date_<?=$key?>" readonly>
                                </div>
                                <div class="booking_set_list">
                                    <label class="col-form-label" style="width: 100%;">Start Time<small class="text-danger">*</small></label>
                                    <input type="text" class="form-control mx-1 timePicker" placeholder="Start time" name="start_time[]" id="start_time_<?=$key?>" value="<?=$value['start_time']?>" readonly>
                                </div>
                                <div class="booking_set_list">
                                    <label class="col-form-label" style="width: 100%;">End Time<small class="text-danger">*</small></label>
                                    <input type="text" class="form-control mx-1 timePicker" placeholder="End time" name="end_time[]" id="end_time_<?=$key?>" value="<?=$value['end_time']?>" readonly>
                                </div>
                                <div class="booking_set_list">
                                    <label class="col-form-label" style="width: 100%;">Select Court<small class="text-danger">*</small></label>
                                    <select class="form-control" name="court_id[]" onchange="selectCourt(this, 1)" readonly>
                                        <option value="">Court</option>
                                        <?php if (!empty($court_list)) { foreach ($court_list as $value1) { ?>
                                            <option value="<?=$value1['id']?>" data-cost="<?=$value1['cost']?>" <?=(isset($value) && $value['court_id'] == $value1['id'] ? 'selected': '')?>><?=$value1['name']?></option>
                                        <?php }} ?>
                                    </select>
                                </div>
                                <div class="booking_set_list">
                                    <label class="col-form-label" style="width: 100%;">No Change</label>
                                    <div class="d-flex align-items-center">
                                      <div class="form-check form-check-inline mx-1">
                                          <input class="form-check-input" type="radio" name="no_change[<?=$key?>]" value="Yes" <?=(isset($value) && $value['no_change'] == 'Yes' ? 'checked': '')?> readonly>
                                          <label class="form-check-label" for="no_change_yes_<?=$key?>">Yes</label>
                                      </div>
                                      <div class="form-check form-check-inline mx-1">
                                          <input class="form-check-input" type="radio" name="no_change[<?=$key?>]" value="No" <?=(isset($value) && $value['no_change'] == 'No' ? 'checked': '')?> readonly>
                                          <label class="form-check-label" for="no_change_no_<?=$key?>">No</label>
                                      </div>
                                    </div>
                                </div>
                                <div class="booking_set_list">
                                    <label class="col-form-label" style="width: 100%;">Payment Status</label>
                                    <div class="d-flex align-items-center">
                                      <div class="form-check form-check-inline mx-1">
                                          <input class="form-check-input" type="radio" name="payment_status[<?=$key?>]" value="Paid" <?=(isset($value) && $value['payment_status'] == 'Paid' ? 'checked': '')?> readonly>
                                          <label class="form-check-label" for="payment_status_paid_<?=$key?>">Paid</label>
                                      </div>
                                      <div class="form-check form-check-inline mx-1">
                                          <input class="form-check-input" type="radio" name="payment_status[<?=$key?>]" value="Unpaid" <?=(isset($value) && $value['payment_status'] == 'Unpaid' ? 'checked': '')?> readonly>
                                          <label class="form-check-label" for="payment_status_unpaid_<?=$key?>">Unpaid</label>
                                      </div>
                                    </div>
                                </div>
                                <div class="booking_set_list">
                                    <label class="col-form-label" style="width: 100%;">Deposit<small class="text-danger">*</small></label>
                                    <input type="text" class="form-control mx-1" placeholder="Deposit" name="deposit[]" value="<?=$value['deposit']?>" readonly>
                                </div>
                                <?php if(!$disableCancel): ?>
                                <div class="text-center deleteSet">
                                    <span onclick="cancelBookingSet(this)" title="Cancel Booking"><i class="bi bi-x-circle"></i></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php } ?>
                        </div>
                      
                    </div>
                </div>

                <div class="row mb-6">
                  <div class="col-md-6 col-sm-12">
                    <label for="card_number" class="col-sm-12 col-form-label">Card Number</label>
                    <div class="col-sm-12">
                      <input type="text" class="form-control" placeholder="Enter card number" id="card_number" name="card_number" readonly value="<?=$bookings[0]['card_number']?>">
                    </div>
                  </div>
                  <div class="col-md-6 col-sm-12">
                    <label for="payment_type" class="col-sm-12 col-form-label">Payment Type</label>
                    <div class="col-sm-12">
                      <select class="form-control" id="payment_type" name="payment_type" readonly>
                      <option value="" disabled selected>Select payment type</option>
                      <?php
                        foreach ($paymentTypes as $option) {
                            $selected = (isset($bookings[0]['payment_type']) && $bookings[0]['payment_type'] == $option) ? 'selected' : '';
                            echo "<option value=\"$option\" $selected>$option</option>";
                        }
                      ?>
                    </select>
                      <div class="invalid-feedback">Please enter the payment type!</div>
                    </div>
                  </div>
                </div>
                <div class="row mb-3 mt-3">
                  <div class="col-md-12 col-sm-12 text-center">
                    <button type="submit" class="btn btn-primary" style="width: 200px">Modify Booking</button>
                  </div>
                </div>
                <div id="hidden_canceled_bookings"></div>
              </form><!-- End General Form Elements -->

            </div>
          </div>

        </div>
        <!-- <div class="col-lg-4 d-none" id="available_court">
          <div class="card" style="padding: 10px 0px;">
            <h3 class="text-center">Available Court</h3>
            <ul class="available_court" id="available_court_list">
              <li></li>
            </ul>
          </div>
        </div> -->
      </div>
    </section>

  </main><!-- End #main -->
  <script type="text/javascript">
    var selectedCourts = [];
    function getAvailableCourt(myThis, bookingRow) {
      var date = $('#date_'+bookingRow).val();
      var start_time = $('#start_time_'+bookingRow).val();
      var end_time = $('#end_time_'+bookingRow).val();
      var court_id = myThis.value;

      if (!date || !start_time || !end_time) {
        Swal.fire({
          title: "Missing information!",
          text: "Please fill in the date and time fields.",
          icon: "warning"
        });
        $(myThis).val('');
        return;
      }

      var isCourtSelected = selectedCourts.some(function (court) {
        return court.court_id === court_id &&
               court.date === date &&
               court.start_time === start_time &&
               court.end_time === end_time;
      });

      if (isCourtSelected) {
        Swal.fire({
          title: "Court Already Selected!",
          text: "This court is already selected for the same date and time in another booking set.",
          icon: "warning"
        });
        $(myThis).val(''); // Reset the court selection field
        return; // Don't continue further if the court is already selected
      }

      $.ajax({
        url: "<?=base_url()?>club-owner/checkCourtAvailibity",
        cache: false,
        type: "POST",
        data: {date : date, start_time : start_time, end_time : end_time, court_id : court_id},
        success: function(res){
          // console.log(res);
          res = JSON.parse(res);
          if(res.status == "exists"){
            Swal.fire({
              title: "Ohh No!",
              text: "Selected court is not available for this date and time",
              icon: "error"
            });
            $(myThis).val('');
          }
          else{
            selectedCourts.push({
              court_id: court_id,
              date: date,
              start_time: start_time,
              end_time: end_time
            });
          }
        }
      });
    }
    /*document.addEventListener('click', function (event) {
      const dateInput = document.getElementById('date');
      const timeInput = document.getElementById('time');
      
      // Check if the click was outside of the date or time inputs
      if (!dateInput.contains(event.target) && !timeInput.contains(event.target)) {
        getAvailableCourt();
      }
    });*/

    /*document.getElementById('duration').addEventListener('input', function(event) {
      // Allow only numbers (and optionally some specific characters, such as decimal points or negative signs)
      this.value = this.value.replace(/[^0-9]/g, ''); // Remove anything that's not a number
    });*/

    function selectCourt(myThis, bookingRow) {
      getAvailableCourt(myThis, bookingRow);
    }

    /*function getCourtCost(duration, date, time) {
      $.ajax({
        url: "<?=base_url()?>club-owner/getCourtCost",
        cache: false,
        type: "POST",
        data: {date : date, time : time, duration : duration},
        success: function(res){
          console.log(res);
          res = JSON.parse(res);
          if(res.cost > 0){
            $('#cost_input').val(res.cost);
            $('#cost').val(res.cost);
          }
          else{
            $('#cost_input').val('0.00');
            $('#cost').val('0.00');
          }
        }
      });
    }
    function calculateCourtCost(duration) {
      var time = $('#time').val();
      var date = $('#date').val();
      if(date == ''){
        Swal.fire({
          title: "Error!",
          text: "Please enter the booking date first!",
          icon: "error"
        });
        $('#duration').val('');
      }else if(time == ''){
        Swal.fire({
          title: "Error!",
          text: "Please enter the booking time first!",
          icon: "error"
        });
        $('#duration').val('');
      }
      else{
        if(duration > 0){
          getCourtCost(duration, date, time);
        }
      }
    }*/

    function getMembershipInfo(membershipId) {
      if (membershipId !== '' && membershipId.length >=6 ) {
        // Make an AJAX request to fetch user details based on membership ID
        $.ajax({
          url: '<?= base_url() ?>club-owner/get_user_details', // The server endpoint to fetch details
          method: 'GET',
          data: { membership_id: membershipId }, // Send membership ID as data
          success: function(data) {
            // If the response contains data, populate the form fields
            if (data) {
              $('#name').val(data.name); // Fill name
              $('#phone').val(data.phone); // Fill phone
              $('#email').val(data.email); // Fill email
              $('#card_number').val(data.card_number);
            }
            else{
              $('#name').val(''); // Fill name
              $('#phone').val(''); // Fill phone
              $('#email').val(''); // Fill email
              $('#card_number').val('');
            }
          },
          error: function() {
            alert('Error fetching user details.');
          }
        });
      }
      else{
        $('#name').val(''); // Fill name
        $('#phone').val(''); // Fill phone
        $('#email').val(''); // Fill email
      }
    }

    // let bookingSetCounter = document.querySelectorAll('.booking-set').length;
    let bookingSetCounter = 1;

    // Add a new booking set when the 'Add New' button is clicked
    document.getElementById('add_booking_set').addEventListener('click', function () {
        bookingSetCounter++;

        // Create the new booking set HTML structure
        const newBookingSet = `
            <div class="booking-set d-flex align-items-center mb-3" id="booking_set_${bookingSetCounter}">
                <div class="booking_set_list">
                    <label class="col-form-label" style="width: 100%;">Select Day<small class="text-danger">*</small></label>
                    <select class="form-control" required name="booking_day_new[]">
                        <option value="" selected>Booking Day</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
                <div class="booking_set_list">
                    <label class="col-form-label" style="width: 100%;">Start Date<small class="text-danger">*</small></label>
                    <input type="text" class="form-control mx-1 datePicker" placeholder="Start date" name="date_new[]" id="date_${bookingSetCounter}" required>
                </div>
                <div class="booking_set_list">
                    <label class="col-form-label" style="width: 100%;">Start Time<small class="text-danger">*</small></label>
                    <input type="text" class="form-control mx-1 timePicker" placeholder="Start time" name="start_time_new[]" id="start_time_${bookingSetCounter}" required>
                </div>
                <div class="booking_set_list">
                    <label class="col-form-label" style="width: 100%;">End Time<small class="text-danger">*</small></label>
                    <input type="text" class="form-control mx-1 timePicker" placeholder="End time" name="end_time_new[]" id="end_time_${bookingSetCounter}" required>
                </div>
                <div class="booking_set_list">
                    <label class="col-form-label" style="width: 100%;">Select Court<small class="text-danger">*</small></label>
                    <select class="form-control" required name="court_id_new[]" onchange="selectCourt(this, ${bookingSetCounter})">
                        <option value="" selected>Court</option>
                        <?php if (!empty($court_list)) { foreach ($court_list as $value) { ?>
                            <option value="<?=$value['id']?>" data-cost="<?=$value['cost']?>"><?=$value['name']?></option>
                        <?php }} ?>
                    </select>
                </div>
                <div class="booking_set_list">
                    <label class="col-form-label" style="width: 100%;">No Change</label>
                    <div class="d-flex align-items-center">
                      <div class="form-check form-check-inline mx-1">
                          <input class="form-check-input" type="radio" name="no_change_new[${bookingSetCounter}]" value="Yes">
                          <label class="form-check-label" for="no_change_yes_${bookingSetCounter}">Yes</label>
                      </div>
                      <div class="form-check form-check-inline mx-1">
                          <input class="form-check-input" type="radio" name="no_change_new[${bookingSetCounter}]" value="No" checked>
                          <label class="form-check-label" for="no_change_no_${bookingSetCounter}">No</label>
                      </div>
                    </div>
                </div>
                <div class="booking_set_list">
                    <label class="col-form-label" style="width: 100%;">Payment Status</label>
                    <div class="d-flex align-items-center">
                      <div class="form-check form-check-inline mx-1">
                          <input class="form-check-input" type="radio" name="payment_status_new[${bookingSetCounter}]" value="Paid">
                          <label class="form-check-label" for="payment_status_paid_${bookingSetCounter}">Paid</label>
                      </div>
                      <div class="form-check form-check-inline mx-1">
                          <input class="form-check-input" type="radio" name="payment_status_new[${bookingSetCounter}]" value="Unpaid" checked>
                          <label class="form-check-label" for="payment_status_unpaid_${bookingSetCounter}">Unpaid</label>
                      </div>
                    </div>
                </div>
                <div class="booking_set_list">
                    <label class="col-form-label" style="width: 100%;">Deposit<small class="text-danger">*</small></label>
                    <input type="text" class="form-control mx-1" placeholder="Deposit" name="deposit_new[]" required>
                </div>
                <div class="booking_set_list">
                    <label class="col-form-label" style="width: 100%;">Month</label>
                    <select class="form-control" required name="pre_booking_month_new[]">
                      <option value="" selected>Month</option>
                      <?php 
                        foreach ($months as $index => $month) {
                            $isDisabled = ($index + 1 < $currentMonth) ? 'disabled' : ''; 
                            echo "<option value=\"$month\" $isDisabled>$month</option>";
                        }
                      ?>
                    </select>
                </div>
                <div class="text-center deleteSet">
                    <span onclick="deleteBookingSet(this)"><i class="bi bi-trash"></i></span>
                </div>
            </div>
        `;

        // Append the new set to the container
        document.getElementById('booking_sets_container').insertAdjacentHTML('beforeend', newBookingSet);

        flatpickr(`#booking_set_${bookingSetCounter} .datePicker`, {
            enableTime: false,
            dateFormat: "d/m/Y", // Date and time format
            time_24hr: false, // 24-hour format
            minuteIncrement: 1 // Minute step increment
        });

        flatpickr(`#booking_set_${bookingSetCounter} .timePicker`, {
            enableTime: true,         // Enable time selection
            noCalendar: true,         // Disable calendar (no date picker)
            dateFormat: "h:i K",        // Only time format (24-hour)
            time_24hr: false,          // Use 24-hour format
            minuteIncrement: 1 
        });

        // If it's the first set, hide the delete icon
        if (bookingSetCounter === 1) {
            const firstSetDeleteButton = document.querySelector(`#booking_set_1 .deleteSet`);
            if (firstSetDeleteButton) {
                firstSetDeleteButton.style.display = 'none'; // Hide the delete button for the first set
            }
        }
    });

    // Function to delete a booking set
    function deleteBookingSet(button) {
        const bookingSet = button.closest('.booking-set');
        bookingSet.remove();
       
    }

    function cancelBookingSet(element) {
      var bookingSet = element.closest('.booking-set');  // Get the parent .booking-set div
      var bookingKey = bookingSet.getAttribute('data-booking-id');  // Get the booking key
      var bookingRow = document.getElementById('booking_set_' + bookingKey);
      
      // Lower the opacity of the booking set row
      bookingRow.style.opacity = 0.5;

      // Create a hidden input to mark this booking as canceled
      var hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = 'canceled_bookings[]';  // Array of canceled bookings
      hiddenInput.value = bookingKey;

      // Append the hidden input to a container (e.g., #hidden_canceled_bookings)
      document.getElementById('hidden_canceled_bookings').appendChild(hiddenInput);

      // Disable the cancel icon (add disabled class or remove onclick)
      element.setAttribute('disabled', 'disabled');
      element.style.pointerEvents = 'none';  // Disable any further clicks
      element.style.opacity = 0.5;  // Optional: visually disable the icon
  }


  </script>