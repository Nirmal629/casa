<!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>POS</span></strong>. All Rights Reserved
    </div>
  </footer><!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="<?=base_url()?>public/assets/js/jquery.min.js"></script>
  <script src="<?=base_url()?>public/assets/js/sweetalert.min.js"></script>
  <script src="<?=base_url()?>public/assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="<?=base_url()?>public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="<?=base_url()?>public/assets/vendor/chart.js/chart.umd.js"></script>
  <script src="<?=base_url()?>public/assets/vendor/echarts/echarts.min.js"></script>
  <script src="<?=base_url()?>public/assets/vendor/quill/quill.js"></script>
  <script src="<?=base_url()?>public/assets/vendor/datatable/datatable.js"></script>
  <script src="<?=base_url()?>public/assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="<?=base_url()?>public/assets/vendor/php-email-form/validate.js"></script>
  <script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap-switch-button@1.1.0/dist/bootstrap-switch-button.min.js"></script>
  <script src="<?=base_url()?>public/assets/js/moment.js"></script>
  <!-- <script src="<?=base_url()?>public/assets/js/bootstrap-datetimepicker.min.js"></script> -->
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <!-- Template Main JS File -->
  <script src="<?=base_url()?>public/assets/js/main.js"></script>
  <script>
    var page = '<?=$page?>';
    if(page == 'dashboard'){
      function bookingDataTable() {
        var datatable = new DataTable('#bookingReport', {
            info: false,
            ordering: false,
            searching: false,
            paging: false
        });
      }

      $('document').ready(function () {
        flatpickr("#date", {
            enableTime: false,
            dateFormat: "d-m-Y", // Date and time format
            time_24hr: false, // 24-hour format
            minuteIncrement: 1 // Minute step increment
        });

        flatpickr("#from_time", {
            enableTime: true,         // Enable time selection
            noCalendar: true,         // Disable calendar (no date picker)
            dateFormat: "h:i K",        // Only time format (24-hour)
            time_24hr: false,          // Use 24-hour format
            minuteIncrement: 30        // Increment by minute
        });
        flatpickr("#to_time", {
            enableTime: true,         // Enable time selection
            noCalendar: true,         // Disable calendar (no date picker)
            dateFormat: "h:i K",        // Only time format (24-hour)
            time_24hr: false,          // Use 24-hour format
            minuteIncrement: 30        // Increment by minute
        });

        bookingDataTable();
      })
    }else if(page == 'listbooking'){
      function bookingDataTable() {
        var datatable = new DataTable('#bookingHistory', {
            info: false,
            ordering: false,
            searching: true,
            paging: true
        });
      }

      
      $('document').ready(function () {
        bookingDataTable();
      });
      
    }else if(page == 'listprebooking'){
      function bookingDataTable() {
        var datatable = new DataTable('#bookingHistory', {
            info: false,
            ordering: false,
            searching: true,
            paging: true
        });
      }

      
      $('document').ready(function () {
        bookingDataTable();
      });
      
    }else if(page == 'listcourt'){
      function courtDataTable() {
        var datatable = new DataTable('#courtList', {
            info: false,
            ordering: false,
            searching: true,
            paging: true
        });
      }

      
      $('document').ready(function () {
        courtDataTable();
      });

    }else if(page == 'listinventory'){
      function inventoryDataTable() {
        var datatable = new DataTable('#inventoryList', {
            info: false,
            ordering: false,
            searching: true,
            paging: true
        });
      }

      
      $('document').ready(function () {
        inventoryDataTable();
      });

    }else if(page == 'listcategory'){
      function categoryDataTable() {
        var datatable = new DataTable('#categoryList', {
            info: false,
            ordering: false,
            searching: true,
            paging: true
        });
      }

      
      $('document').ready(function () {
        categoryDataTable();
      });

    }else if(page == 'listbrand'){
      function brandDataTable() {
        var datatable = new DataTable('#brandList', {
            info: false,
            ordering: false,
            searching: true,
            paging: true
        });
      }

      
      $('document').ready(function () {
        brandDataTable();
      });

    }else if(page == 'listsize'){
      function sizeDataTable() {
        var datatable = new DataTable('#sizeList', {
            info: false,
            ordering: false,
            searching: true,
            paging: true
        });
      }

      
      $('document').ready(function () {
        sizeDataTable();
      });

    }else if(page == 'liststringing'){
      function stringingDataTable() {
        var datatable = new DataTable('#listStringing', {
            info: false,
            ordering: false,
            searching: true,
            paging: true
        });
      }

      
      $('document').ready(function () {
        stringingDataTable();
      });

    }else if(page == 'listmembership'){
      function bookingDataTable() {
        var datatable = new DataTable('#bookingHistory', {
            info: false,
            ordering: false,
            searching: true,
            paging: true
        });
      }

      
      $('document').ready(function () {
        bookingDataTable();
      });

      function openModal(membership_id) {
        $.ajax({
          url: "<?=base_url()?>club-owner/membershipdetails",
          cache: false,
          type: "POST",
          data: {id : membership_id},
          success: function(res){
            // console.log(res);
            res = JSON.parse(res);
            if(res){
              $('#membership_id').html(res.unique_id);
              $('#membership_name').html(res.name);
              $('#membership_phone').html(res.phone);
              $('#membership_email').html(res.email);
              $('#membership_start').html(res.start_date);
              $('#membership_end').html(res.end_date);
              $('#membership_amount').html('<?=CURRENCY?>'+' '+res.amount);
              if(res.discount > 0){
                $('#membership_discount').html(res.discount+'%');
              }
              else{
                $('#membership_discount').html('0');
              }

              $('#membership_status').html(res.status);
            }
            $('#membershipModal').modal('show');
          }
        });
      }
    }else if(page == 'addnewbooking' || page == 'editbooking'){
      $(document).ready(function() {
        flatpickr("#date", {
            enableTime: false,
            dateFormat: "d/m/Y", // Date and time format
            time_24hr: false, // 24-hour format
            minuteIncrement: 1 // Minute step increment
        });

        flatpickr("#time", {
            enableTime: true,         // Enable time selection
            noCalendar: true,         // Disable calendar (no date picker)
            dateFormat: "h:i K",        // Only time format (24-hour)
            time_24hr: false,          // Use 24-hour format
            minuteIncrement: 1        // Increment by minute
        });

        $('#court_id').on('change', function() {
          var courtId = $(this).val(); // Get the selected court ID

          if (courtId !== '') {
            // Fetch the cost of the selected court (from the option data attribute)
            var courtCost = $('#court_id option:selected').data('cost');
            // If the court cost is available, populate the cost field
            if (courtCost) {
              $('#cost').val(courtCost); // Populate the cost field
            } else {
              $('#cost').val(''); // Clear the cost if no cost data is available
            }
          }
          else {
            $('#cost').val(''); // Clear the cost if no cost data is available
          }
        });
      });
    }else if(page == 'prebooking'){
      flatpickr("#booking_set_1 .datePicker", {
            enableTime: false,
            dateFormat: "d/m/Y", // Date and time format
            time_24hr: false, // 24-hour format
            minuteIncrement: 1 // Minute step increment
        });

        flatpickr("#booking_set_1 .timePicker", {
            enableTime: true,         // Enable time selection
            noCalendar: true,         // Disable calendar (no date picker)
            dateFormat: "h:i K",        // Only time format (24-hour)
            time_24hr: false,          // Use 24-hour format
            minuteIncrement: 1        // Increment by minute
        });
      
    }else if(page == 'addnewmembership' || page == 'editmembership'){
      $(document).ready(function() {
        const startPicker = flatpickr("#start_date", {
            enableTime: false,
            dateFormat: "d/m/Y",
            minDate: "today", // Prevent selecting past dates
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length > 0) {
                    endPicker.set('minDate', selectedDates[0]);
                }
            }
        });

        const endPicker = flatpickr("#end_date", {
            enableTime: false,
            dateFormat: "d/m/Y",
            minDate: "today",
        });
      });
    }else if(page == 'addnewstringing' || page == 'editstringing'){
      $(document).ready(function() {
        flatpickr("#service_date", {
            enableTime: false,
            dateFormat: "d/m/Y", // Date and time format
            time_24hr: false, // 24-hour format
            minuteIncrement: 1 // Minute step increment
        });
        flatpickr("#delivery_date", {
            enableTime: false,
            dateFormat: "d/m/Y", // Date and time format
            time_24hr: false, // 24-hour format
            minuteIncrement: 1 // Minute step increment
        });
      });
    }else if(page == 'paymenthistory'){
      function paymentDataTable() {
        var datatable = new DataTable('#paymentHistory', {
            info: false,
            ordering: false,
            searching: true,
            paging: true
        });
      }

      
      $('document').ready(function () {
        paymentDataTable();
      });
    }

    function deleteItem(id, delete_for) {
      Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!"
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: "<?=base_url()?>club-owner/deleteitem",
            cache: false,
            type: "POST",
            data: {id : id, delete_for: delete_for},
            success: function(res){
              $('#table_row_'+id).remove();
              Swal.fire({
                title: "Deleted!",
                text: "Data deleted successfully!",
                icon: "success"
              });

            }
          });
        }
      });
      
    }

    function changeStatus(status, booking_key) {
        $.ajax({
          url: "<?=base_url()?>club-owner/changeBookingStatus",
          cache: false,
          type: "POST",
          data: {status : status, booking_key: booking_key},
          success: function(res){
            // console.log(res);
            res = JSON.parse(res);
            if(res.success == '1'){
              Swal.fire({
                title: "Good job!",
                text: "Booking status has been changed!",
                icon: "success",
                confirmButtonText: "OK",
              }).then((result) => {
                if (result.isConfirmed) {
                    window.location.reload();
                }
            });
            }
          }
        });
      }

      function validateAmountInput(input) {
          input.addEventListener('input', function () {
              let value = input.value;
              if (!/^\d*\.?\d{0,2}$/.test(value)) {
                  input.value = value.slice(0, -1);
              }
          });
      }
      
      document.addEventListener('DOMContentLoaded', function () {
          const amountInputs = document.querySelectorAll('.amount-input');
          amountInputs.forEach(validateAmountInput);
      });

      function validatePhoneInput(input) {
          input.addEventListener('input', function () {
              input.value = input.value.replace(/[^\d+\-\s()]/g, '');
          });
      }

      document.addEventListener('DOMContentLoaded', function () {
          const phoneInputs = document.querySelectorAll('.phone-input');
          phoneInputs.forEach(validatePhoneInput);
      });
    
  </script>
</body>

</html>