<main id="main" class="main">

    <div class="pagetitle">
      <h1>List Pre Booking</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?=base_url()?>club-owner/dashboard">Dashboard</a></li>
          <li class="breadcrumb-item">Manage Bookings</li>
          <li class="breadcrumb-item active">List Pre Booking</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body">
              <!-- <h5 class="card-title">Booking History</h5> -->
              <!-- Table with stripped rows -->
              <div class="table-responsive">
                <table class="table table-bordered" id="bookingHistory">
                  <thead>
                    <tr>
                      <th>Booking ID</th>
                      <th>Membership ID</th>
                      <th>Name</th>
                      <th>Phone</th>
                      <th>Booking Date</th>
                      <th>Booking Time</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                        foreach ($bookings as $key => $booking) {
                    ?>
                    <tr id="table_row_<?=$booking['booking_key']?>">
                      <td>#<?= $booking['booking_key'] ?></td>
                      <td><?= $booking['membership_id'] ?></td>
                      <td><?= $booking['name'] ?></td>
                      <td><?= $booking['phone'] ?></td>
                      <td><?= date('jS F, Y', strtotime($booking['addeddate'])) ?></td>
                      <td><?= date('h:i A', strtotime($booking['addeddate'])) ?></td>
                      <td>
                        <a href="<?=base_url()?>club-owner/edit-pre-booking/<?=$booking['booking_key']?>" style="font-size: 20px;"><button class="btn btn-success btn-sm"><i class="bi bi-pencil"></i>&nbsp;Modify</button></a>
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
