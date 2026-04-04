<main id="main" class="main">

    <div class="pagetitle">
      <div class="row">
        <div class="col-md-6 col-xs-12 col-sm-12">
          <h1>New Court</h1>
          <nav>
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?=base_url()?>club-owner/dashboard">Dashboard</a></li>
              <li class="breadcrumb-item">Manage Court</li>
              <li class="breadcrumb-item active">Add New Court</li>
            </ol>
          </nav>
        </div>
        <div class="col-md-6 col-xs-12 col-sm-12 text-end">
          <a href="<?=base_url()?>club-owner/dashboard"><button type="button" class="btn btn-success">Dashboard</button></a>
          <a href="<?=base_url()?>club-owner/list-court"><button type="button" class="btn btn-primary">List Court</button></a>
        </div>
        <div class="clearfix"></div>
      </div>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title d-inline-block">Add New Court</h5>
              <?php if(isset($validation)):?>
                  <div class="alert alert-danger" style="margin-bottom: 30px;"><?= $validation->listErrors() ?></div>
              <?php endif;?>
              <!-- General Form Elements -->
              <form class="row g-3 needs-validation" action="<?=base_url()?>club-owner/addcourt" method="post" novalidate>
                
                <div class="row mb-3">
                  <label for="name" class="col-sm-2 col-form-label">Court Name<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" placeholder="Enter court name" id="name" name="name" value="<?= set_value('name') ?>" required>
                    <div class="invalid-feedback">Please enter the court name!</div>
                  </div>
                </div>
                <!-- <div class="row mb-3">
                  <label for="cost" class="col-sm-2 col-form-label">Cost/Hr</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" placeholder="Enter cost/hr" id="phone" name="cost" value="<?= set_value('cost') ?>" required>
                    <div class="invalid-feedback">Please enter the cost!</div>
                  </div>
                </div> -->
                
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">Save Court</label>
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