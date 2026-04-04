<main id="main" class="main">

    <div class="pagetitle">
      <h1>List Inventory</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?=base_url()?>club-owner/dashboard">Dashboard</a></li>
          <li class="breadcrumb-item">Manage Inventory</li>
          <li class="breadcrumb-item active">List Inventory</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body">
              <!-- Table with stripped rows -->
              <div class="table-responsive">
                <table class="table table-bordered" id="inventoryList">
                  <thead>
                    <tr>
                      <th style="text-align:left;">Id</th>
                      <th>Name</th>
                      <th>SKU</th>
                      <th>Category</th>
                      <th>Brand</th>
                      <th>Size</th>
                      <th>Color</th>
                      <th>Quantity</th>
                      <th>Price</th>
                      <th>Added Date</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      if(isset($inventory_list) && !empty($inventory_list)){
                        foreach ($inventory_list as $key => $value) {
                    ?>
                    <tr id="table_row_<?=$value['id']?>">
                      <td><?=$value['id']?></td>                      
                      <td><?=$value['name']?></td>
                      <td><?=$value['sku']?></td>
                      <td><?=$value['category_name']?></td>
                      <td><?=$value['brand_name']?></td>
                      <td><?=$value['size_name']?></td>
                      <td>
                        <input type="color" class="form-control form-control-color" value="<?=$value['color']?>" style="margin: 0px auto;" disabled>
                      </td>
                      <td><?=$value['quantity']?></td>
                      <td><?=CURRENCY?> <?=number_format($value['price'], 2, '.', '')?></td>
                      <td><?=$value['addeddate']?></td>
                      <td style="text-align: center;">
                        <select class="form-select" aria-label="Select Status" id="status" name="status" style="width:150px; margin: 0px auto;">
                          <option <?=$value['status'] == '1' ? 'selected' : '' ?> value="1">Active</option>
                          <option <?=$value['status'] == '3' ? 'selected' : '' ?> value="3">Discontinued</option>
                          <option <?=$value['status'] == '4' ? 'selected' : '' ?> value="4">Out of Stock</option>
                        </select>
                      </td>
                      
                      <td>
                        <a href="<?=base_url()?>club-owner/edit-inventory/<?=$value['id']?>" style="font-size: 20px;color: green;"><i class="bi bi-pencil"></i></a>
                        <a href="javascript:void(0)" onclick="deleteItem('<?=$value['id']?>', 'inventory')" class="text-danger" style="font-size: 20px;"><i class="bi bi-trash"></i></a>
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
  </main><!-- End #main -->