<main id="main" class="main">

    <div class="pagetitle">
      <div class="row">
        <div class="col-md-6 col-xs-12 col-sm-12">
          <h1>New Stringing</h1>
          <nav>
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?=base_url()?>club-owner/dashboard">Dashboard</a></li>
              <li class="breadcrumb-item">Manage Membership</li>
              <li class="breadcrumb-item active">Add Stringing</li>
            </ol>
          </nav>
        </div>
        <div class="col-md-6 col-xs-12 col-sm-12 text-end">
          <a href="<?=base_url()?>club-owner/dashboard"><button type="button" class="btn btn-success">Dashboard</button></a>
          <a href="<?=base_url()?>club-owner/list-stringing"><button type="button" class="btn btn-primary">List Stringing</button></a>
        </div>
        <div class="clearfix"></div>
      </div>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title d-inline-block"></h5>
              <?php if(isset($validation)):?>
                  <div class="alert alert-danger" style="margin-bottom: 30px;"><?= $validation->listErrors() ?></div>
              <?php endif;?>
              <!-- General Form Elements -->
              <form class="row g-3 needs-validation" action="<?=base_url()?>club-owner/addstringing" method="post" novalidate>
                
                <div class="row mb-3">
                    <label for="which_string" class="col-sm-2 col-form-label">String Unique ID<small class="text-danger">*</small></label>
                    <div class="col-sm-10">
                        <select class="form-select" id="which_string" name="which_string" onchange="getInventoryDetails()">
                              <option value="" disabled selected>Select string</option>
                              <?php
                                foreach ($inventory_list as $key=>$value) {
                                    echo "<option value=\"".$value['id']."\" >".$value['name']."</option>";
                                }
                              ?>
                        </select>
                    </div>
                </div>


                
                <div class="row mb-3">
                  <label for="color" class="col-sm-2 col-form-label">Colour<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <input type="color" class="form-color" id="color" name="color" value="<?= set_value('color') ?>" required>
                    <div class="invalid-feedback">Please enter the color!</div>
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="tension" class="col-sm-2 col-form-label">Tension<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <select class="form-select" id="tension" name="tension" required>
                        <option value="" selected>Select Tension</option>
                        <?php
                        for ($i=23; $i < 36; $i++) { 
                            ?>
                            <option value="<?= $i ?>LBS"><?= $i ?> LBS</option>
                            <?php
                        }
                        ?>                          
                    </select>
                    <div class="invalid-feedback">Please enter the tension!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="service_date" class="col-sm-2 col-form-label">Service Date<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <input type="date" class="form-control" placeholder="Enter service date" id="service_date" name="service_date" value="<?= set_value('service_date') ?>" required>
                    <div class="invalid-feedback">Please enter service date!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="delivery_date" class="col-sm-2 col-form-label">Delivery Date<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <input type="date" class="form-control" placeholder="Enter delivery date" id="delivery_date" name="delivery_date" value="<?= set_value('delivery_date') ?>" required>
                    <div class="invalid-feedback">Please enter delivery date!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="string_cost" class="col-sm-2 col-form-label">String Cost<small class="text-danger">*</small> </label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control amount-input" placeholder="Enter String Cost" id="string_cost" name="string_cost" value="<?= set_value('string_cost') ?>" required>
                    <div class="invalid-feedback">Please enter the String Cost!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="string_discount" class="col-sm-2 col-form-label">String Discount<small class="text-danger">*</small> </label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control amount-input" placeholder="Enter String Discount" id="string_discount" name="string_discount" value="<?= set_value('string_discount') ?>" required oninput="calculateTotalCost()">
                    <div class="invalid-feedback">Please enter the String Discount!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="labour_charge" class="col-sm-2 col-form-label">Labour Charge<small class="text-danger">*</small> </label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control amount-input" placeholder="Enter labour charge" id="labour_charge" name="labour_charge" value="<?= set_value('labour_charge') ?>" required oninput="calculateTotalCost()">
                    <div class="invalid-feedback">Please enter the labour charge!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="total_cost" class="col-sm-2 col-form-label">Total Cost<small class="text-danger">*</small> </label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control amount-input" placeholder="Total Cost" id="total_cost" name="total_cost" value="<?= set_value('total_cost') ?>" required readonly>
                    <div class="invalid-feedback">Please enter the Total Cost!</div>
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="membership_id" class="col-sm-2 col-form-label">Membership ID</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" placeholder="Enter Membership ID" id="membership_id" name="membership_id" value="<?= set_value('membership_id') ?>" oninput="getMembershipInfo(this.value)">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="name" class="col-sm-2 col-form-label">Name</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" placeholder="Enter Name" id="name" name="name" value="<?= set_value('name') ?>">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="phone" class="col-sm-2 col-form-label">Phone</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" placeholder="Enter Phone" id="phone" name="phone" value="<?= set_value('phone') ?>" oninput="getMembershipInfoByPhone(this.value)">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="email" class="col-sm-2 col-form-label">Email</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" placeholder="Enter Email" id="email" name="email" value="<?= set_value('email') ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="card_number" class="col-sm-2 col-form-label">Card Number</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" placeholder="Enter card number" id="card_number" name="card_number" value="<?= set_value('card_number') ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="exp_date" class="col-sm-2 col-form-label">Exp date (MM/YY)</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" placeholder="Enter date" id="exp_date" name="exp_date" value="<?= set_value('exp_date') ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="cvv" class="col-sm-2 col-form-label">CVV</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" placeholder="Enter CVV" id="cvv" name="cvv" value="<?= set_value('cvv') ?>">
                  </div>
                </div>


                <div class="row mb-3">
                  <label for="payment_type" class="col-sm-2 col-form-label">Payment Type</label>
                  <div class="col-sm-10">
                    <select class="form-select" id="payment_type" name="payment_type">
                      <option value="" disabled selected>Select payment type</option>
                      <?php
                        foreach ($paymentTypes as $option) {
                            echo "<option value=\"$option\" >$option</option>";
                        }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="payment_status" class="col-sm-2 col-form-label">Payment Status</label>
                  <div class="col-sm-10">
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="payment_status" id="payment_status_paid" value="Paid" >
                        <label class="form-check-label" for="payment_status_paid">Paid</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="payment_status" id="payment_status_unpaid" value="Unpaid" checked>
                        <label class="form-check-label" for="payment_status_unpaid">Unpaid</label>
                      </div>
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="status" class="col-sm-2 col-form-label">Status</label>
                  <div class="col-sm-10">
                      <select class="form-select" aria-label="Select status" id="status" name="status" required>
                      <option selected="" value="active">Active</option>
                      <option value="complete">Complete</option>
                      <option value="delivered">Delivered</option>
                      
                    </select>
                    <div class="invalid-feedback">Please select status!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label" style="visibility: hidden;"></label>
                  <div class="col-sm-10">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <button type="reset" class="btn btn-danger">Cancel</button>
                  </div>
                </div>

              </form><!-- End General Form Elements -->

            </div>
          </div>

        </div>

      </div>
    </section>

  </main><!-- End #main -->

  <script>
    function getInventoryDetails() {
        var invID = $("#which_string").val();
        console.log("invID============", invID);
        $("#string_cost").val('0.00');
        calculateTotalCost();
        if(invID != ''){
            $.ajax({
                url: "<?=base_url()?>club-owner/getInventoryDetails/"+invID,
                cache: false,
                type: "GET",
                dataType : 'json',
                success: function(res){
                  if(res.price){
                    $("#string_cost").val(res.price);
                    calculateTotalCost();

                  }
                }
            });
        }
        
    }

    function calculateTotalCost() {
        var string_cost =  $("#string_cost").val();
        if(Number(string_cost) > 0){

        }else{
            string_cost = 0;
        }

        var string_discount =  $("#string_discount").val();
        if(Number(string_discount) > 0){

        }else{
            string_discount = 0;
        }

        var labour_charge =  $("#labour_charge").val();
        if(Number(labour_charge) > 0){

        }else{
            labour_charge = 0;
        }

        var finalCost = (Number(string_cost) + Number(labour_charge)) - Number(string_discount);

        $("#total_cost").val(finalCost );
    }

    function getMembershipInfo(membershipId) {
      if (membershipId !== '' && membershipId.length >=6 ) {
        $.ajax({
          url: '<?= base_url() ?>club-owner/get_user_details',
          method: 'GET',
          data: { membership_id: membershipId,  type : "membershipID"},
          success: function(data) {
            if (data) {
              $('#name').val(data.name);
              $('#phone').val(data.phone);
              $('#email').val(data.email);
              $('#card_number').val(data.card_number);
            }
            else{
              $('#name').val('');
              $('#phone').val(''); 
              $('#email').val('');
              $('#card_number').val('');
            }
          },
          error: function() {
            alert('Error fetching user details.');
          }
        });
      }
      else{
        $('#name').val('');
        // $('#phone').val('');
        $('#email').val('');
      }
    }
    function getMembershipInfoByPhone(membershipId) {
      if (membershipId !== '' && membershipId.length >=10 ) {
        $.ajax({
          url: '<?= base_url() ?>club-owner/get_user_details',
          method: 'GET',
          data: { membership_id: membershipId,  type : "membershipPhone"},
          success: function(data) {
            if (data) {
              $('#membership_id').val(data.unique_id);
              $('#name').val(data.name);
              $('#phone').val(data.phone);
              $('#email').val(data.email);
              $('#card_number').val(data.card_number);
            }
            else{
                $('#membership_id').val('');
              $('#name').val('');
              // $('#phone').val(''); 
              $('#email').val('');
              $('#card_number').val('');
            }
          },
          error: function() {
            alert('Error fetching user details.');
          }
        });
      }
      else{
        $('#membership_id').val('');
        $('#name').val('');
        // $('#phone').val('');
        $('#email').val('');
      }
    }
  </script>