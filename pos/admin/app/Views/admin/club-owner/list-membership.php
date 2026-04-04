<main id="main" class="main">

    <div class="pagetitle">
      <h1>List Membership</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?=base_url()?>club-owner/dashboard">Dashboard</a></li>
          <li class="breadcrumb-item">Manage Membership</li>
          <li class="breadcrumb-item active">List Membership</li>
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
                <table class="table table-bordered" id="bookingHistory">
                  <thead>
                    <tr>
                      <th>Membership ID</th>
                      <th>Player Name</th>
                      <th>Phone</th>
                      <th>Start Date</th>
                      <th>End Date</th>
                      <th>Amount</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                        foreach ($membership_list as $key => $value) {
                    ?>
                    <tr id="table_row_<?=$value['id']?>">
                      <td><?=$value['unique_id']?></td>
                      <td><?=$value['name']?></td>
                      <td><?=$value['phone']?></td>
                      <td><?=date('jS F, Y', strtotime($value['start_date']))?></td>
                      <td><?=date('jS F, Y', strtotime($value['end_date']))?></td>
                      <td><?=CURRENCY?> <?=number_format($value['amount'], 2, '.', '')?></td>
                      <td><input type="checkbox" data-toggle="switchbutton" <?=isset($value['status']) && $value['status'] == 'Active' ? 'checked':''?> data-onlabel="Active" data-offlabel="In Active" data-onstyle="success" data-offstyle="danger"></td>
                      <td>
                        <a href="<?=base_url()?>club-owner/edit-membership/<?=$value['id']?>" style="font-size: 20px;color: green;"><i class="bi bi-pencil"></i></a>
                        <a href="javascript:void(0)" style="font-size: 20px;" onclick="openModal('<?=$value['id']?>')"><i class="bi bi-eye-fill"></i></a>
                        <a href="javascript:void(0)" onclick="deleteItem('<?=$value['id']?>', 'membership')" class="text-danger" style="font-size: 20px;"><i class="bi bi-trash"></i></a>
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
    <!-- Modal -->
    <div class="modal fade" id="membershipModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="membershipModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="membershipModalLabel">Membership Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body px-4">
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Membership ID</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="membership_id">#HY12344533</label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Player Name</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="membership_name">Sarajit Mondal</label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Phone</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="membership_phone">123445334534</label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Email</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="membership_email">abc@gmail.com</label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Start Date</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="membership_start">26/01/2025</label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>End Date</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="membership_end">26/01/2026</label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Card Number</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="membership_card">4242 4242 4242 4242</label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Amount</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="membership_amount">Rs. 800.00</label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Discount</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="membership_discount">100%</label>
              </div>
            </div>
            <div class="row mb-2">
              <div class="col bookingDetailsCol">
                <label>Status</label>
              </div>
              <div class="col bookingDetailsVal">
                <label id="membership_status">Active</label>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </main><!-- End #main -->