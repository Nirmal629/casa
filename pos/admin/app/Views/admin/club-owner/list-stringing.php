<main id="main" class="main">

    <div class="pagetitle">
      <h1>List Stringing</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?=base_url()?>club-owner/dashboard">Dashboard</a></li>
          <li class="breadcrumb-item">Manage Stringing</li>
          <li class="breadcrumb-item active">List Stringing</li>
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
                <table class="table table-bordered" id="listStringing">
                  <thead>
                    <tr>
                      <th>Service No</th>
                      <th>Which String</th>
                      <th>Colour</th>
                      <th>Tension</th>
                      <th>Service Date</th>
                      <th>Delivery Date</th>
                      <th>Total Cost</th>
                      <th>Payment Type</th>
                      <th>Payment Status</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                        foreach ($string_list as $key => $value) {
                          // echo "<pre>";
                          // print_r($value);
                          // echo "</pre>";
                    ?>
                    <tr id="table_row_<?=$value['id']?>">
                      <td><?=$value['service_no']?></td>
                      <td><?=$value['inventory_name']?></td>
                      <td>
                        <input type="color" class="form-control form-control-color" value="<?=$value['color']?>" style="margin: 0px auto;" disabled>
                      </td>
                      <td><?=$value['tension']?></td>
                      <td><?=date('jS F, Y', strtotime($value['service_date']))?></td>
                      <td><?=date('jS F, Y', strtotime($value['delivery_date']))?></td>
                      <td><?=CURRENCY?> <?=number_format($value['total_cost'], 2, '.', '')?></td>
                      <td><?=$value['payment_type']?></td>
                      <td><?=$value['payment_status']?></td>
                      <td><?= ucfirst($value['status'])?></td>
                      <td>
                        <a href="<?=base_url()?>club-owner/edit-stringing/<?=$value['id']?>" style="font-size: 20px;color: green;"><i class="bi bi-pencil"></i></a>
                        <a href="javascript:void(0)" onclick="deleteItem('<?=$value['id']?>', 'stringing')" class="text-danger" style="font-size: 20px;"><i class="bi bi-trash"></i></a>
                      </td>
                    </tr>
                    <?php } ?>
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