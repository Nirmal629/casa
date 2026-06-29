<main id="main" class="main">

    <div class="pagetitle">
      <div class="row">
        <div class="col-md-6 col-xs-12 col-sm-12">
          <h1>New Membership</h1>
          <nav>
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?=base_url()?>club-owner/dashboard">Dashboard</a></li>
              <li class="breadcrumb-item">Manage Membership</li>
              <li class="breadcrumb-item active">Add New Membership</li>
            </ol>
          </nav>
        </div>
        <div class="col-md-6 col-xs-12 col-sm-12 text-end">
          <a href="<?=base_url()?>club-owner/dashboard"><button type="button" class="btn btn-success">Dashboard</button></a>
          <a href="<?=base_url()?>club-owner/list-membership"><button type="button" class="btn btn-primary">List Membership</button></a>
        </div>
        <div class="clearfix"></div>
      </div>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title d-inline-block">Add New Membership</h5>
              <?php if(isset($validation)):?>
                  <div class="alert alert-danger" style="margin-bottom: 30px;"><?= $validation->listErrors() ?></div>
              <?php endif;?>
              <!-- General Form Elements -->
              <form class="row g-3 needs-validation" action="<?=base_url()?>club-owner/addmembership" method="post" novalidate>
                <!-- <div class="row mb-3">
                  <label for="membership_id" class="col-sm-2 col-form-label">Membership ID</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" placeholder="Enter membership id" id="membership_id" name="membership_id">
                  </div>
                </div> -->
                <div class="row mb-3">
                  <label for="player_name" class="col-sm-2 col-form-label">Player Name<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" placeholder="Enter name" id="player_name" name="player_name" value="<?= set_value('player_name') ?>" required>
                    <div class="invalid-feedback">Please enter the player name!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="phone" class="col-sm-2 col-form-label">Phone<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control phone-input" placeholder="Enter phone number" id="phone" name="phone" value="<?= set_value('phone') ?>" required>
                    <div class="invalid-feedback">Please enter the phone number!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="email" class="col-sm-2 col-form-label">Email<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <input type="email" class="form-control" placeholder="Enter email address" id="email" name="email" value="<?= set_value('email') ?>" required>
                    <div class="invalid-feedback">Please enter the email address!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="start_date" class="col-sm-2 col-form-label">Start Date<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <input type="date" class="form-control" placeholder="Enter start date" id="start_date" name="start_date" value="<?= set_value('start_date') ?>" required>
                    <div class="invalid-feedback">Please enter start date!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="end_date" class="col-sm-2 col-form-label">End date<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <input type="date" class="form-control" placeholder="Enter end date" id="end_date" name="end_date" value="<?= set_value('end_date') ?>" required>
                    <div class="invalid-feedback">Please enter end date!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="amount" class="col-sm-2 col-form-label">Amount<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control amount-input" placeholder="Enter amount" id="amount" name="amount" value="<?= set_value('amount') ?>" required>
                    <div class="invalid-feedback">Please enter an amount!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="discount" class="col-sm-2 col-form-label">Discount<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control amount-input" placeholder="Enter discount" id="discount" name="discount" value="<?= set_value('discount') ?>" required>
                    <div class="invalid-feedback">Please enter discount!</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="card_number" class="col-sm-2 col-form-label">Card Number</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" placeholder="Enter card number" id="card_number" name="card_number" value="<?= set_value('card_number') ?>">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="status" class="col-sm-2 col-form-label">Status</label>
                  <div class="col-sm-10">
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" id="status_active" value="Active" checked>
                        <label class="form-check-label" for="status_active">Active</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" id="status_inactive" value="Inactive" >
                        <label class="form-check-label" for="status_inactive">In Active</label>
                      </div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">Save Membership</label>
                  <div class="col-sm-10">
                    <button type="submit" class="btn btn-primary">Submit</button>
                  </div>
                </div>

              </form><!-- End General Form Elements -->

            </div>
          </div>

        </div>

      </div>
    </section>

  </main><!-- End #main -->