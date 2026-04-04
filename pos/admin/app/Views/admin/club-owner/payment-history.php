<main id="main" class="main">

    <div class="pagetitle">
      <h1>Payment History</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?=base_url()?>club-owner/dashboard">Dashboard</a></li>
          <li class="breadcrumb-item active">Payment History</li>
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
                <table class="table table-bordered" id="paymentHistory">
                  <thead>
                    <tr>
                      <th>Booking ID</th>
                      <th>Payment Date</th>
                      <th>Amount</th>
                      <th>Type</th>
                      <th>Payment Method</th>
                      <th>Payment Status</th>
                      <!-- <th>Action</th> -->
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                        foreach ($payment_history as $key => $value) {
                    ?>
                    <tr id="table_row_<?=$value['id']?>">
                      <td><?=$value['booking_key']?></td>
                      <td><?=date('jS F, Y', strtotime($value['payment_date']))?></td>
                      <td><?=CURRENCY?> <?=number_format($value['amount'], 2, '.', '')?></td>
                      <td><?=$value['type']?></td>
                      <td><?=$value['payment_type']?></td>
                      <td><strong><?=$value['payment_status']?></strong></td>
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