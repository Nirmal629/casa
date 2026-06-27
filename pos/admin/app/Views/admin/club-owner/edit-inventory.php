<main id="main" class="main">

    <div class="pagetitle">
      <div class="row">
        <div class="col-md-6 col-xs-12 col-sm-12">
          <h1>Edit Inventory</h1>
          <nav>
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?=base_url()?>club-owner/dashboard">Dashboard</a></li>
              <li class="breadcrumb-item">Manage Inventory</li>
              <li class="breadcrumb-item active">Edit Inventory</li>
            </ol>
          </nav>
        </div>
        <div class="col-md-6 col-xs-12 col-sm-12 text-end">
          <a href="<?=base_url()?>club-owner/dashboard"><button type="button" class="btn btn-success">Dashboard</button></a>
          <a href="<?=base_url()?>club-owner/list-inventory"><button type="button" class="btn btn-primary">List Inventory</button></a>
        </div>
        <div class="clearfix"></div>
      </div>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title d-inline-block">Edit Inventory</h5>
              <?php if(isset($validation)):?>
                  <div class="alert alert-danger" style="margin-bottom: 30px;"><?= $validation->listErrors() ?></div>
              <?php endif;?>
              <!-- General Form Elements -->
              <form class="row g-3 needs-validation" action="<?=base_url()?>club-owner/editinventory" method="post" novalidate>
                <input type="hidden" name="inventory_id" id="inventory_id" value="<?=$inventory_id?>">
                <div class="row mb-3">
                  <label for="name" class="col-sm-2 col-form-label">Inventory Name<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" placeholder="Enter inventory name" id="name" name="name" value="<?=isset($inventory_details) && $inventory_details['name'] !='' ? $inventory_details['name'] : '' ?>" required>
                    <div class="invalid-feedback">Please enter the inventory name!</div>
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="sku" class="col-sm-2 col-form-label">Inventory SKU<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" placeholder="Enter inventory sku" id="sku" name="sku" value="<?=isset($inventory_details) && $inventory_details['sku'] !='' ? $inventory_details['sku'] : '' ?>" required>
                    <div class="invalid-feedback">Please enter the inventory sku!</div>
                  </div>
                </div>

                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">Inventory Category<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <select class="form-select" aria-label="Select category" id="category" name="category" required>
                      <option value="">Select Category</option>
                      <?php
                      if(!empty($categorylist)){
                        foreach ($categorylist as $key => $value) {
                          ?>
                          <option value="<?= $value['id'] ?>" <?=isset($inventory_details) && $inventory_details['category'] ==$value['id'] ? 'selected' : '' ?>><?= $value['name'] ?></option>
                          <?php
                        }
                      }
                      ?>
                    </select>
                    <div class="invalid-feedback">Please select category!</div>
                  </div>
                </div>

                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">Inventory Brand<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <select class="form-select" aria-label="Select Brand" id="Brand" name="brand" required>
                      <option value="">Select Brand</option>
                      <?php
                      if(!empty($brandlist)){
                        foreach ($brandlist as $key => $value) {
                          ?>
                          <option value="<?= $value['id'] ?>"  <?=isset($inventory_details) && $inventory_details['brand'] ==$value['id'] ? 'selected' : '' ?> ><?= $value['name'] ?></option>
                          <?php
                        }
                      }
                      ?>                    
                    </select>
                    <div class="invalid-feedback">Please select brand!</div>
                  </div>
                </div>

                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">Inventory Size<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <select class="form-select" aria-label="Select size" id="size" name="size" required>
                      <option value="">Select Size</option>
                      <?php
                      if(!empty($sizelist)){
                        foreach ($sizelist as $key => $value) {
                          ?>
                          <option value="<?= $value['id'] ?>" <?=isset($inventory_details) && $inventory_details['size'] ==$value['id'] ? 'selected' : '' ?> ><?= $value['name'] ?></option>
                          <?php
                        }
                      }
                      ?>
                    </select>
                    <div class="invalid-feedback">Please select size!</div>
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="color" class="col-sm-2 col-form-label">Inventory Color<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <input type="color" class="form-color" id="color" name="color" value="<?=isset($inventory_details) && $inventory_details['color'] !='' ? $inventory_details['color'] : '#000000' ?>" required>
                    <div class="invalid-feedback">Please enter the inventory color!</div>
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="cost" class="col-sm-2 col-form-label">Inventory Quantity<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <input type="number" class="form-control" placeholder="Enter inventory quantity" id="quantity" name="quantity" value="<?=isset($inventory_details) && $inventory_details['quantity'] !='' ? $inventory_details['quantity'] : '' ?>" required>
                    <div class="invalid-feedback">Please enter the inventory quantity!</div>
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="cost" class="col-sm-2 col-form-label">Inventory Price<small class="text-danger">*</small></label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control amount-input" placeholder="Enter inventory price" id="price" name="price" value="<?=isset($inventory_details) && $inventory_details['price'] !='' ? $inventory_details['price'] : '' ?>" required>
                    <div class="invalid-feedback">Please enter the inventory price!</div>
                  </div>
                </div>
                
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">Update Inventory</label>
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