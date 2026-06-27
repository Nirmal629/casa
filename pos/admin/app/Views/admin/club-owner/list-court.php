<main id="main" class="main">

    <div class="pagetitle">
      <h1>List Court</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?=base_url()?>club-owner/dashboard">Dashboard</a></li>
          <li class="breadcrumb-item">Manage Court</li>
          <li class="breadcrumb-item active">List Court</li>
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
                <table class="table table-bordered" id="courtList">
                  <thead>
                    <tr>
                      <th>Court Id</th>
                      <th>Court Name</th>
                      <!-- <th>Cost / hr</th> -->
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      if(isset($court_list) && !empty($court_list)){
                        foreach ($court_list as $key => $value) {
                    ?>
                    <tr id="table_row_<?=$value['id']?>">
                      <td><?=$value['id']?></td>
                      <td><?=$value['name']?></td>
                      <!-- <td><?=CURRENCY?> <?=number_format($value['cost'], 2, '.', '')?></td> -->
                      <td>
                        <a href="javascript:void(0)" onclick="deleteItem('<?=$value['id']?>', 'court')" class="text-danger" style="font-size: 20px;"><i class="bi bi-trash"></i></a>
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