<main id="main" class="main">

    <div class="pagetitle">
      <div class="row">
        <div class="col-md-6 col-xs-12 col-sm-12">
          <h1>Dashboard</h1>
          <nav>
            <ol class="breadcrumb m-0">
              <!-- <li class="breadcrumb-item"><a href="dashboard.html">Dashboard</a></li> -->
              <li class="breadcrumb-item active">Booking Reports</li>
            </ol>
          </nav>
        </div>
        <div class="col-md-6 col-xs-12 col-sm-12 text-end">
          <a href="<?=base_url()?>club-owner/add-new-booking"><button type="button" class="btn btn-success">New Booking</button></a>
          <a href="<?=base_url()?>club-owner/pre-booking"><button type="button" class="btn btn-warning">Pre Booking</button></a>
          <a href="<?=base_url()?>club-owner/add-new-membership"><button type="button" class="btn btn-primary">Membership</button></a>
        </div>
        <div class="clearfix"></div>
      </div>
    </div><!-- End Page Title -->

    <section class="section dashboard">
      <div class="row">
           <div class="card">
            <div class="card-body table-responsive">
              <!-- <h5 class="card-title">Booking Reports</h5> -->
              <!-- Table with stripped rows -->
              
              <div class="col-md-12" style="padding-top: 10px;">
                <div class="row">
                  <div class="col-md-4 col-lg-4 col-xs-12 col-sm-12 booking-indicator">
                    <label class="indicator-label">
                      <span class="booked"></span>
                      Booked
                    </label>
                    <label class="indicator-label">
                      <span class="preBooking"></span>
                      Pre Booking
                    </label>
                    <label class="indicator-label">
                      <span class="noBooking indecator-border"></span>
                      No Booking
                    </label>
                  </div>
                  <div class="col-md-8 col-lg-8 col-xs-12 col-sm-12">
                    <div class="row">
                      <div class="col-md-3 pe-0 filterRow">
                        <div class="col-md-2">
                          <label for="date" class="col-sm-2 col-form-label w-100">Date</label>
                        </div>
                        <div class="col-md-10">
                          <input type="text" id="date" placeholder="Select Date" class="form-control" value="<?=isset($date) && $date !='' ? $date : ''?>">
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="row">
                          <div class="col-md-12 p-0 filterRow">
                            <div class="col-md-4 text-center">
                              <label for="from_time" class="col-sm-2 col-form-label w-100">From Time</label>
                            </div>
                            <div class="col-md-8">
                              <input type="text" id="from_time" placeholder="From Time" class="form-control w-100" value="<?=isset($from_time) && $from_time !='' ? $from_time : ''?>">
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="row">
                          <div class="col-md-12 p-0 filterRow">
                            <div class="col-md-3 text-center">
                              <label for="to_time" class="col-sm-2 col-form-label w-100">To Time</label>
                            </div>
                            <div class="col-md-9">
                              <input type="text" id="to_time" placeholder="To Time" class="form-control w-100" value="<?=isset($to_time) && $to_time !='' ? $to_time : ''?>">
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <button type="button" class="btn btn-primary" style="width: 51%;" onclick="searchBooking()">Search</button>
                        <button type="button" class="btn btn-danger" style="width: 46%;" onclick="window.location.href='<?=base_url()?>club-owner/dashboard'">Reset</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <table class="table table-bordered" id="bookingReport">
                <thead>
                  <tr>
                    <th>Time</th>
                    <?php
                      if(!empty($court_list)){
                        foreach($court_list as $key => $value){
                    ?>
                    <th><?=$value['name']?></th>
                    <?php
                        }
                      }
                    ?>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    if(!empty($booking_slots)){
                      foreach($booking_slots as $key => $value){

                  ?>
                  <tr>
                    <td class="align-middle"><?=$value['time']?></td>
                    <?php
                      if(!empty($value['booking_data'])){
                        foreach($value['booking_data'] as $key => $booking){
                    ?>
                      <?php
                        if(!empty($booking) && $booking['booking_type'] == 'new_booking' && $booking['booking_status'] != 'Canceled'){
                      ?>
                      <td class="booked" >
                        <div class="d-flex flex-column text-black">
                          <span><?=$booking['name']?></span>
                          <span>#<?=$booking['booking_key']?></span>
                          <span class="d-flex justify-content-center  gap-1">
                            <span class="bookingBtn bg-primary" title="Edit/View Booking" onclick="openModal('<?= $booking['booking_key'] ?>')">E</span>
                            <?php
                              if($booking['booking_status'] == 'Active'){
                            ?>
                            <span class="bookingBtn <?=isset($booking['payment_status']) && $booking['payment_status'] == 'Paid'?'bg-primary':'bg-danger'?>" title="Payment <?=isset($booking['payment_status']) && $booking['payment_status'] == 'Paid'?'Done':'Due'?>" onclick="paymentModal('<?= $booking['booking_key'] ?>')">P</span>
                            <?php
                              } else {
                            ?>
                            <span class="bookingBtn <?=isset($booking['payment_status']) && $booking['payment_status'] == 'Paid'?'bg-primary':'bg-danger'?>" title="Payment <?=isset($booking['payment_status']) && $booking['payment_status'] == 'Paid'?'Done':'Due'?>">P</span>
                            <?php } ?>
                            <span class="bookingBtn <?=isset($booking['no_change']) && $booking['no_change'] == 'Yes'?'bg-primary':'bg-danger'?>" title="<?=isset($booking['no_change']) && $booking['no_change'] == 'Yes'?'Yes':'No'?> Change">N</span>
                          </span>
                        </div>
                      </td>
                      <?php } else if(!empty($booking) && $booking['booking_type'] == 'pre_booking' && $booking['booking_status'] != 'Canceled'){?>
                        <td class="preBooking">
                          <div class="d-flex flex-column text-white">
                          <span><?=$booking['name']?></span>
                          <span>#<?=$booking['booking_key']?></span>
                          <span class="d-flex justify-content-center  gap-1">
                            <span class="bookingBtn bg-primary" title="Edit/View Booking" onclick="openModal('<?= $booking['booking_key'] ?>')">E</span>
                            <?php
                              if($booking['booking_status'] == 'Active'){
                            ?>
                            <span class="bookingBtn <?=isset($booking['payment_status']) && $booking['payment_status'] == 'Paid'?'bg-primary':'bg-danger'?>" title="Payment <?=isset($booking['payment_status']) && $booking['payment_status'] == 'Paid'?'Done':'Due'?>" onclick="paymentModal('<?= $booking['booking_key'] ?>')">P</span>
                            <?php
                              } else {
                            ?>
                            <span class="bookingBtn <?=isset($booking['payment_status']) && $booking['payment_status'] == 'Paid'?'bg-primary':'bg-danger'?>" title="Payment <?=isset($booking['payment_status']) && $booking['payment_status'] == 'Paid'?'Done':'Due'?>" >P</span>
                            <?php } ?>
                            <span class="bookingBtn <?=isset($booking['no_change']) && $booking['no_change'] == 'Yes'?'bg-primary':'bg-danger'?>" title="<?=isset($booking['no_change']) && $booking['no_change'] == 'Yes'?'Yes':'No'?> Change">N</span>
                          </span>
                        </div>
                        </td>
                      <?php } else { ?>
                        <td class="noBooking"></td>
                      <?php } } ?>
                    <?php }  else { ?>
                      <td class="noBooking"></td>
                    <?php } ?>
                  </tr>
                  <?php }
                }
                ?>
                </tbody>
              </table>
              <!-- End Table with stripped rows -->

            </div>
          </div>

      </div>
    </section>
    <!-- Modal -->
    <div class="modal fade" id="bookingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="bookingModalLabel">Booking Details - <span id="booking_id"></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <input type="hidden" id="booking_key">
          </div>
          <div class="modal-body px-4">
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Court No</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="court_no"></label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Membership ID</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="membership_id"></label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Name</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="name"></label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Phone</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="phone"></label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Email</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="email"></label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Date</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="dateVal"></label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Time</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="time"></label>
              </div>
            </div>
            <div class="row mb-2" id="duration_html">
              <div class="col bookingDetailsCol">
                <label>Duration</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="duration"></label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Cost</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="cost"></label>
              </div>
            </div>
            <div class="row mb-2" id="deposit_html">
              <div class="col bookingDetailsCol">
                <label>Deposit</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="deposit"></label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Card Number</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="card_number"></label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Payment Type</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="payment_type_val"></label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Payment Status</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="payment_status"></label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>No Change</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="no_change"></label>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <div id="booking_status_div">
              <label>Booking Status</label>
              <select class="status_dropdown" id="booking_status" onchange="changeBookingStatus()">
                <option value="Active">Active</option>
                <option value="Defaulter">Defaulter</option>
                <option value="Cancelled">Cancelled</option>
                <option value="Completed">Completed</option>
              </select>
            </div>
            <div class="ms-auto">
              <button type="button" class="btn btn-success" id="edit_booking" onclick="editBooking()">Edit</button>
              <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
          <input type="hidden" id="edit_booking_id">
        </div>
      </div>
    </div>
    <div class="modal fade" id="paymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
      <form class="row g-3 needs-validation" action="<?=base_url()?>club-owner/addentry" method="post" novalidate>
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="paymentModalLabel">Create Entry - <span id="payment_booking_id"></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <input type="hidden" name="booking_key_val" id="booking_key_val">
          <div class="modal-body px-4">
              <div class="row mb-2">
                <div class="col-md-12 text-end" style="padding:0px"><button type="button" class="btn btn-primary" id="addEntryBtn">+ Add Entry</button></div>
              </div>
              <div class="row mb-2" id="entrySetContainer">
                <div class="col-md-12" style="padding: 0px;">
                  <label for="payment_type" class="col-sm-12 col-form-label">Payment Type</label>
                  <div class="col-sm-12">
                    <!-- <input type="text" class="form-control" placeholder="Enter payment type" id="payment_type" name="payment_type" required> -->
                    <select class="form-control" id="payment_type" required name="payment_type">
                      <option value="" disabled selected>Select payment type</option>
                      <?php
                        foreach ($paymentTypes as $option) {
                            echo "<option value=\"$option\" >$option</option>";
                        }
                      ?>
                    </select>
                    <div class="invalid-feedback">Please enter the payment type!</div>
                  </div>
                </div>
               <div class="d-flex entry_set" style="padding: 0px;gap:5px" id="entry_set_1">
                   <div style="width: 50%">
                      <label for="name" class="col-sm-12 col-form-label">Type</label>
                      <div class="col-sm-12">
                        <input type="text" class="form-control" placeholder="Entry Type" id="entry_type_name" name="entry_type_name" value="Court" readonly>
                      </div>
                  </div>
                  <div style="width: 50%">
                    <label for="name" class="col-sm-12 col-form-label">Cost</label>
                    <div class="col-sm-12">
                      <input type="text" class="form-control" placeholder="Cost" id="entry_type_cost" name="entry_type_cost" readonly>
                    </div>
                  </div>
               </div>
              </div>
            
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary" name="entryType" value="save" id="saveEntry">Save</button>
            <button type="submit" class="btn btn-success" name="entryType" value="pay" id="payEntry">Pay</button>
          </div>
          <input type="hidden" id="edit_booking_id">
        </div>
      </div>
      </form>
    </div>
  </main><!-- End #main -->
  <script type="text/javascript">
    function openModal(booking_key) {
        $.ajax({
        url: "<?=base_url()?>club-owner/getBookingHistory",
        cache: false,
        type: "POST",
        data: {booking_key : booking_key},
        success: function(res){
          // console.log(res);
          if(res != ""){
            res = JSON.parse(res);
            if(res.booking_type == 'pre_booking'){
              $('#duration_html').css("display", 'none');
              $('#edit_booking').css("display", 'none');
            }
            if(res.booking_status == 'Cancelled' || res.booking_status == 'Completed'){
              $('#edit_booking').css("display", 'none');
            }
            if(res.booking_type == 'new_booking'){
              $('#deposit_html').css("display", 'none');
            }
            $('#booking_id').html('#'+res.booking_key);
            $('#court_no').html(res.court_no);
            $('#membership_id').html(res.membership_id);
            $('#name').html(res.name);
            $('#phone').html(res.phone);
            $('#email').html(res.email);
            $('#dateVal').html(res.date);
            $('#time').html(res.start_time);
            $('#duration').html(res.duration+' hr');
            $('#cost').html(res.cost);
            $('#deposit').html(res.deposit);
            $('#card_number').html(res.card_number);
            $('#payment_type_val').html(res.payment_type);
            $('#payment_status').html(res.payment_status);
            $('#no_change').html(res.no_change);
            $('#edit_booking_id').val(res.id);
            $('#booking_status').val(res.booking_status);
            $('#booking_key').val(res.booking_key);
            $('#bookingModal').modal('show');


          }
        }
      });
      }
      function editBooking() {
        var booking_id = $('#edit_booking_id').val();
        window.location.href="<?=base_url()?>club-owner/edit-booking/"+booking_id;
      }
      function searchBooking() {
        var date = $('#date').val();
        var from_time = $('#from_time').val();
        var to_time = $('#to_time').val();
        if(date == ''){
          Swal.fire({
            title: "Error!",
            text: "Please select the date!",
            icon: "error"
          });
        }else if(from_time == ''){
          Swal.fire({
            title: "Error!",
            text: "Please select the from time!",
            icon: "error"
          });
        }else if(to_time == ''){
          Swal.fire({
            title: "Error!",
            text: "Please select the to time!",
            icon: "error"
          });
        }
        else{
          from_time = from_time.replace(" ", "-");
          to_time = to_time.replace(" ", "-");
          window.location.href="<?=base_url()?>club-owner/dashboard/"+date+'/'+from_time+'/'+to_time;
        }
      }

      function paymentModal(booking_key) {
        $.ajax({
        url: "<?=base_url()?>club-owner/getBookingHistory",
        cache: false,
        type: "POST",
        data: {booking_key : booking_key},
        success: function(res){
          // console.log(res);
          if(res != ""){
            res = JSON.parse(res);
            var entries = res.entries;
            $('#payment_booking_id').html('#'+res.booking_key);
            $('#booking_key_val').val(res.booking_key);
            $('#payment_type').val(res.payment_type);
            if(res.booking_type == 'new_booking'){
              $('#entry_type_cost').val(res.cost_value);
            }
            else{
              $('#entry_type_cost').val(res.deposit_value);
            }

            if(res.payment_status == 'Paid'){
              $('#saveEntry').css("display", "none");
              $('#payEntry').css("display", "none");
              $('#addEntryBtn').css("display", "none");
            }
            else{
              $('#saveEntry').css("display", "block");
              $('#payEntry').css("display", "block");
              $('#addEntryBtn').css("display", "block");
            }

            if (entries && entries.length > 0) {
                // Clear existing entry sets first if needed
                // document.getElementById('entrySetContainer').innerHTML = '';
                $('.entry_set').remove();
                entrySetContainer = 0;
                entries.forEach((entry, index) => {
                    // Simulate clicking the "Add Entry" button
                    document.getElementById('addEntryBtn').click();

                    // Wait for element to exist (ensure correct ID)
                    const typeSelect = document.getElementById(`entry_type_name_${entrySetContainer}`);
                    const quantitySelect = document.getElementById(`entry_type_quantity_${entrySetContainer}`);
                    const costInput = document.getElementById(`entry_type_cost_${entrySetContainer}`);

                    if (typeSelect && quantitySelect && costInput) {
                        // Set type
                        const options = typeSelect.options;
                        for (let i = 0; i < options.length; i++) {
                            if (options[i].value === entry.entry_name) {
                                options[i].selected = true;
                                break;
                            }
                        }

                        // Set quantity
                        quantitySelect.value = entry.entry_qnty;
                        costInput.value = entry.entry_cost;
                    }
                });
            }

            $('#paymentModal').modal('show');

          }
        }
      });
      }

      let entrySetContainer = 0; // To keep track of the number of sets

    // Add a new booking set when the 'Add New' button is clicked
    document.getElementById('addEntryBtn').addEventListener('click', function () {
        entrySetContainer++;

        // Create the new booking set HTML structure
        const newEntrySet = `
            <div class="d-flex align-items-center entry_set" style="padding: 0px;gap:5px" id="entry_set_${entrySetContainer}">
                 <div style="width: 33%">
                    <label for="name" class="col-sm-12 col-form-label">Type</label>
                    <div class="col-sm-12">
                      <select class="form-control" name="entry[${entrySetContainer}][name]" id="entry_type_name_${entrySetContainer}" onchange="selectEntryType(${entrySetContainer})" required>
                      <option value="" selected>Choose one</option>
                      <?php if (!empty($inventory_list)) { foreach ($inventory_list as $value) { ?>
                            <option value="<?=$value['name']?>" data-cost="<?=$value['price']?>"><?=$value['name']?></option>
                        <?php }} ?>
                      </select>
                    </div>
                </div>
               <div style="width: 33%">
                  <label for="name" class="col-sm-12 col-form-label">Qnty</label>
                  <div class="col-sm-12">
                      <select class="form-control" id="entry_type_quantity_${entrySetContainer}" name="entry[${entrySetContainer}][quantity]" onchange="updateTotalCost(${entrySetContainer})">
                          ${[...Array(20)].map((_, i) => {
                              const val = i + 1;
                              return `<option value="${val}" ${val === 1 ? 'selected' : ''}>${val}</option>`;
                          }).join('')}
                      </select>
                  </div>
              </div>
                <div style="width: 33%">
                  <label for="name" class="col-sm-12 col-form-label">Cost</label>
                  <div class="col-sm-12">
                    <input type="text" class="form-control" placeholder="Cost" id="entry_type_cost_${entrySetContainer}" name="entry[${entrySetContainer}][cost]" readonly>
                  </div>
                </div>
                <div class="text-center deleteSet" style="margin-top: 40px;">
                    <span onclick="deleteEnrtySet(this)"><i class="bi bi-trash"></i></span>
                </div>
             </div>
        `;

        // Append the new set to the container
        document.getElementById('entrySetContainer').insertAdjacentHTML('beforeend', newEntrySet);
    });

    function deleteEnrtySet(button) {
        const entrySet = button.closest('.entry_set');
        entrySet.remove();
    }

    function selectEntryType(index) {
        updateTotalCost(index);
    }

    // Handles when quantity is selected
    function updateTotalCost(index) {
        const typeSelect = document.getElementById(`entry_type_name_${index}`);
        const quantitySelect = document.getElementById(`entry_type_quantity_${index}`);
        const costInput = document.getElementById(`entry_type_cost_${index}`);

        const selectedOption = typeSelect.options[typeSelect.selectedIndex];
        const unitCost = parseFloat(selectedOption.getAttribute('data-cost')) || 0;
        const quantity = parseInt(quantitySelect.value) || 0;

        const totalCost = unitCost * quantity;
        costInput.value = totalCost.toFixed(2); // format to 2 decimal places
    }

    function changeBookingStatus(){
      var booking_key = $('#booking_key').val();
      var status = $('#booking_status').val();
      changeStatus(status, booking_key);
    }
  </script>