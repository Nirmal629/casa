<main id="main" class="main">

    <div class="pagetitle">
      <div class="row">
        <div class="col-md-6 col-xs-12 col-sm-12">
          <h1>Edit Booking</h1>
          <nav>
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?=base_url()?>club-owner/dashboard">Dashboard</a></li>
              <li class="breadcrumb-item">Manage Bookings</li>
              <li class="breadcrumb-item active">Edit Booking</li>
            </ol>
          </nav>
        </div>
        <div class="col-md-6 col-xs-12 col-sm-12 text-end">
          <a href="<?=base_url()?>club-owner/dashboard"><button type="button" class="btn btn-success">Dashboard</button></a>
          <a href="<?=base_url()?>club-owner/list-booking"><button type="button" class="btn btn-primary">List Booking</button></a>
        </div>
        <div class="clearfix"></div>
      </div>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row" >
        <div class="col-lg-8" >

          <div class="card" style="padding: 10px 0px">
            <?php if(isset($validation)):?>
              <div class="alert alert-danger" style="margin-bottom: 30px;"><?= $validation->listErrors() ?></div>
            <?php endif;?>
            <?php if(isset($error_message)): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px;">
                    <?= $error_message ?>
                </div>
            <?php endif; ?>
            <form class="row g-3 needs-validation" action="<?=base_url()?>club-owner/updatebooking" method="post" novalidate>
              <input type="hidden" name="booking_type" value="new_booking">
              <input type="hidden" name="booking_id" value="<?=$booking_id?>">
            <div class="card-body">
              
              <!-- General Form Elements -->
              <form>
                <div class="row mb-3">
                  <label for="membership_id" class="col-sm-3 col-form-label">Membership ID<small class="text-danger">*</small></label>
                  <div class="col-sm-9">
                    <input type="text" class="form-control" placeholder="Enter membership id" id="membership_id" name="membership_id" required oninput="getMembershipInfo(this.value)" value="<?=isset($booking_details['membership_id']) && $booking_details['membership_id']!= '' ? $booking_details['membership_id'] :''?>">
                    <div class="invalid-feedback">Please enter the membership id!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="name" class="col-sm-3 col-form-label">Name<small class="text-danger">*</small></label>
                  <div class="col-sm-9">
                    <input type="text" class="form-control" placeholder="Enter name" id="name" name="name" required value="<?=isset($booking_details['name']) && $booking_details['name']!= '' ? $booking_details['name'] :''?>">
                    <div class="invalid-feedback">Please enter the name!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="phone" class="col-sm-3 col-form-label">Phone<small class="text-danger">*</small></label>
                  <div class="col-sm-9">
                    <input type="text" class="form-control phone-input" placeholder="Enter phone number" id="phone" name="phone" required value="<?=isset($booking_details['phone']) && $booking_details['phone']!= '' ? $booking_details['phone'] :''?>">
                    <div class="invalid-feedback">Please enter the phone number!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="email" class="col-sm-3 col-form-label">Email<small class="text-danger">*</small></label>
                  <div class="col-sm-9">
                    <input type="email" class="form-control" placeholder="Enter email address" id="email" name="email" required value="<?=isset($booking_details['email']) && $booking_details['email']!= '' ? $booking_details['email'] :''?>">
                    <div class="invalid-feedback">Please enter the email address!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="date" class="col-sm-3 col-form-label">Date<small class="text-danger">*</small></label>
                  <div class="col-sm-9">
                    <!-- <div class="input-group date">
                        <input type="text" class="form-control" placeholder="Select date" id="date" name="date" required onchange="getAvailableCourt()" />
                        <span class="input-group-text">
                            <i class="bi bi-calendar3"></i>
                        </span>
                    </div> -->
                    <input type="text" class="form-control" placeholder="Select booking date" id="date" name="date" required onchange="getAvailableCourt()" value="<?=isset($booking_details['date']) && $booking_details['date']!= '' ? $booking_details['date'] :''?>" />
                    <div class="invalid-feedback">Please enter the booking date!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="time" class="col-sm-3 col-form-label">Time<small class="text-danger">*</small></label>
                  <div class="col-sm-9">
                    <input type="text" class="form-control" placeholder="Select booking time" id="time" name="time" required onchange="getAvailableCourt()" value="<?=isset($booking_details['start_time']) && $booking_details['start_time']!= '' ? $booking_details['start_time'] :''?>">
                    <div class="invalid-feedback">Please enter the booking time!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="court_no" class="col-sm-3 col-form-label">Court No<small class="text-danger">*</small></label>
                  <div class="col-sm-9">
                    <input type="text" class="form-control" placeholder="Enter court no" id="court_no" name="court_no" required disabled value="<?=isset($booking_details['court_no']) && $booking_details['court_no']!= '' ? $booking_details['court_no'] :''?>">
                    <input type="hidden" name="court_id" id="court_id" value="<?=isset($booking_details['court_id']) && $booking_details['court_id']!= '' ? $booking_details['court_id'] :''?>">
                    <!-- <select class="form-control" required id="court_id" name="court_id">
                      <option value="" selected>Select Court</option>
                      <?php
                        if(!empty($court_list)){
                          foreach($court_list as $key => $value){
                      ?>
                      <option value="<?=$value['id']?>" data-cost="<?=$value['cost']?>"><?=$value['name']?></option>
                      <?php
                          }
                        }
                      ?>
                    </select> -->
                    <div class="invalid-feedback">Please enter a court no!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="duration" class="col-sm-3 col-form-label">Duration<small class="text-danger">*</small></label>
                  <div class="col-sm-9">
                    <select class="form-control" id="duration" name="duration" required onchange="calculateCourtCost(this.value)">
                      <option value="" disabled selected>Select duration</option>
                      <?php
                        for ($i = 1; $i <= 10; $i++) {
                            $selected = (isset($booking_details['duration']) && $booking_details['duration'] == $i) ? 'selected' : '';
                            echo "<option value=\"$i\" $selected>$i hour" . ($i > 1 ? 's' : '') . "</option>";
                        }
                      ?>
                    </select>
                    <div class="invalid-feedback">Please enter the booking duration!</div>
                  </div>

                </div>
                <div class="row mb-3">
                  <label for="cost" class="col-sm-3 col-form-label">Cost<small class="text-danger">*</small></label>
                  <div class="col-sm-9">
                    <input type="text" class="form-control amount-input" placeholder="Enter cost" id="cost_input" name="cost_input" required disabled value="<?=isset($booking_details['cost']) && $booking_details['cost']!= '' ? $booking_details['cost'] :''?>">
                    <input type="hidden" name="cost" id="cost" value="<?=isset($booking_details['cost']) && $booking_details['cost']!= '' ? $booking_details['cost'] :''?>">
                    <div class="invalid-feedback">Please enter the booking cost!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="card_number" class="col-sm-3 col-form-label">Card Number</label>
                  <div class="col-sm-9">
                    <input type="text" class="form-control" placeholder="Enter card number" id="card_number" name="card_number" value="<?=isset($booking_details['card_number']) && $booking_details['card_number']!= '' ? $booking_details['card_number'] :''?>">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="payment_type" class="col-sm-3 col-form-label">Payment Type</label>
                  <div class="col-sm-9">
                    <select class="form-control" id="payment_type" name="payment_type">
                      <option value="" disabled selected>Select payment type</option>
                      <?php
                        foreach ($paymentTypes as $option) {
                            $selected = (isset($booking_details['payment_type']) && $booking_details['payment_type'] == $option) ? 'selected' : '';
                            echo "<option value=\"$option\" $selected>$option</option>";
                        }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="no_change" class="col-sm-3 col-form-label">No Change</label>
                  <div class="col-sm-9">
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="no_change" id="no_change_yes" value="Yes" <?=isset($booking_details['no_change']) && $booking_details['no_change']== 'Yes' ? 'checked' :''?>>
                        <label class="form-check-label" for="no_change_yes">Yes</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="no_change" id="no_change_no" value="No" <?=isset($booking_details['no_change']) && $booking_details['no_change']== 'No' ? 'checked' :''?>>
                        <label class="form-check-label" for="no_change_no">No</label>
                      </div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="payment_status" class="col-sm-3 col-form-label">Payment Status</label>
                  <div class="col-sm-9">
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="payment_status" id="payment_status_paid" value="Paid" <?=isset($booking_details['payment_status']) && $booking_details['payment_status']== 'Paid' ? 'checked' :''?>>
                        <label class="form-check-label" for="payment_status_paid">Paid</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="payment_status" id="payment_status_unpaid" value="Unpaid" <?=isset($booking_details['payment_status']) && $booking_details['payment_status']== 'Unpaid' ? 'checked' :''?>>
                        <label class="form-check-label" for="payment_status_unpaid">Unpaid</label>
                      </div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label class="col-sm-3 col-form-label">Update Booking</label>
                  <div class="col-sm-9">
                    <button type="submit" class="btn btn-primary">Update</button>
                  </div>
                </div>

              </form><!-- End General Form Elements -->

            </div>
          </div>

        </div>
        <div class="col-lg-4 d-none" id="available_court">
          <div class="card" style="padding: 10px 0px;">
            <h3 class="text-center">Available Court</h3>
            <ul class="available_court" id="available_court_list">
              <li></li>
            </ul>
          </div>
        </div>
      </div>
    </section>

  </main><!-- End #main -->
  <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        getAvailableCourt();
    });

    function getAvailableCourt() {
      var date = $('#date').val();
      var time = $('#time').val();
      var booking_id = '<?=$booking_id?>';
      var court_id = $('#court_id').val();
      $.ajax({
        url: "<?=base_url()?>club-owner/getAvailableCourt",
        cache: false,
        type: "POST",
        data: {booking_id : booking_id, court_id : court_id, date : date, time : time},
        success: function(res){
          // console.log(res);
          
          if(res != ""){
            res = JSON.parse(res);
            $('#available_court').removeClass('d-none');
            $('#available_court').addClass('d-block');
            $('#available_court_list').html(res);
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

    document.getElementById('duration').addEventListener('input', function(event) {
      // Allow only numbers (and optionally some specific characters, such as decimal points or negative signs)
      this.value = this.value.replace(/[^0-9]/g, ''); // Remove anything that's not a number
    });

    function selectCourt(court_id, court_name) {
      $('#court_id').val(court_id);
      $('#court_no').val(court_name);
      if($('.courtlist').hasClass('active_court')){
        $('.courtlist').removeClass('active_court');
      }
      $('.court_'+court_id).addClass('active_court');
    }

    function getCourtCost(duration, date, time) {
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
    }

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
              $('#card_number').val(''); // Fill email
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
  </script>