<main id="main" class="main">

    <div class="pagetitle">
      <div style="display: flex;flex-direction: row;justify-content: space-between;">
        <div>
          <h1>List Brand</h1>
          <nav>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="<?=base_url()?>club-owner/dashboard">Dashboard</a></li>
              <li class="breadcrumb-item">Master</li>
              <li class="breadcrumb-item active">List Brand</li>
            </ol>
          </nav>
        </div>
        <div>
          <button onclick="addBrand()" type="button" class="btn btn-primary"><i class="bi bi-plus me-1"></i> Add</button>
        </div>
      </div>
      
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-12">
          <?php
            if(session()->getFlashdata('success')){
              ?>
              <div class="alert alert-success bg-success text-light border-0 alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('success') ?>                
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
              <?php
            }
          ?>
          <?php
            if(session()->getFlashdata('error')){
              ?>
              <div class="alert alert-danger bg-danger text-light border-0 alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('error') ?>                
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
              <?php
            }
          ?>
        </div>
        
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body">
              <!-- Table with stripped rows -->
              <div class="table-responsive">
                <table class="table table-bordered" id="brandList">
                  <thead>
                    <tr>
                      <th style="text-align: center;">Brand Id</th>
                      <th>Brand Name</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      if(isset($brand_list) && !empty($brand_list)){
                        foreach ($brand_list as $key => $value) {
                    ?>
                    <tr id="table_row_<?=$value['id']?>">
                      <td><?=$value['id']?></td>
                      <td><?=$value['name']?></td>
                      <td>
                        <a href="javascript:void(0)" onclick="updateBrand('<?=$value['id']?>')" style="font-size: 20px;color: green;"><i class="bi bi-pencil"></i></a>
                        <a href="javascript:void(0)" onclick="deleteItem('<?=$value['id']?>', 'pos_brand')" class="text-danger" style="font-size: 20px;"><i class="bi bi-trash"></i></a>
                      </td>
                    </tr>
                    <?php } } ?>
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
    <div class="modal fade" id="addModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h1 class="modal-title fs-5" id="addModalLabel">Add new Brand</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?= form_open('club-owner/add-brand', ['class' => 'row g-3 needs-validation', 'novalidate' => 'novalidate']) ?>                
                <div class="row mb-3">
                  <label for="name" class="col-sm-12 col-form-label">Brand Name<small class="text-danger">*</small></label>
                  <div class="col-12">
                    <input type="text" class="form-control" placeholder="Enter Brand name" id="name" name="name" required>
                    <div class="invalid-feedback">Please enter the Brand name!</div>
                  </div>
                </div>             
                <div class="row mb-3">
                  <div class="col-sm-10">
                    <button type="submit" class="btn btn-primary">Submit</button>
                  </div>
                </div>

              <?= form_close() ?>
          </div>
        </div>
      </div>
    </div>


    <!-- Update Modal -->
    <div class="modal fade" id="updateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h1 class="modal-title fs-5" id="updateModalLabel">Update brand</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?= form_open('club-owner/update-brand', ['class' => 'row g-3 needs-validation', 'novalidate' => 'novalidate']) ?>                
                <div class="row mb-3">
                  <label for="editname" class="col-sm-12 col-form-label">Brand Name<small class="text-danger">*</small></label>
                  <div class="col-12">
                    <input type="text" class="form-control" placeholder="Enter Brand name" id="editname" name="name" required>
                    <div class="invalid-feedback">Please enter the Brand name!</div>
                  </div>
                </div>             
                <div class="row mb-3">
                  <div class="col-sm-10">
                    <input type="hidden" name="editid" id="editid" value="0">
                    <button type="submit" class="btn btn-primary">Update</button>
                  </div>
                </div>

              <?= form_close() ?>
          </div>
        </div>
      </div>
    </div>





  </main><!-- End #main -->


  <script>
    function addBrand() {
      const myModal = new bootstrap.Modal(document.getElementById('addModal')).show();
    }
    function updateBrand(id) {
      $.ajax({
        url: "<?=base_url()?>club-owner/get-brand/"+id,
        cache: false,
        type: "GET",
        dataType : 'json',
        success: function(res){
          console.log(res);
          $("#editid").val(id);
          $("#editname").val(res.name);
          const myModal = new bootstrap.Modal(document.getElementById('updateModal')).show();
        }
      });
      
    }
  </script>