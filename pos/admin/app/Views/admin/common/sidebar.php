<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">
  <ul class="sidebar-nav" id="sidebar-nav">
    
    <!-- Dashboard -->
    <li class="nav-item" style="display:none;">
      <a class="nav-link <?= (current_url() == base_url('club-owner/dashboard')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/dashboard">
        <i class="bi bi-grid"></i>
        <span>Dashboard</span>
      </a>
    </li><!-- End Dashboard Nav -->

    <!-- Manage Bookings -->
    <li class="nav-item" style="display: none;">
      <a class="nav-link collapsed" data-bs-target="#booking-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-calendar-week"></i><span>Manage Bookings</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="booking-nav" class="nav-content collapse <?= (current_url() == base_url('club-owner/add-new-booking') || current_url() == base_url('club-owner/list-booking')) ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
        <li>
          <a class="<?= (current_url() == base_url('club-owner/add-new-booking')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/add-new-booking">
            <i class="bi bi-circle"></i><span>Add New Booking</span>
          </a>
        </li>
        <li>
          <a class="<?= (current_url() == base_url('club-owner/list-booking')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/list-booking">
            <i class="bi bi-circle"></i><span>List Booking</span>
          </a>
        </li>
      </ul>
    </li>

    <!-- Manage Pre Bookings -->
    <li class="nav-item" style="display: none;">
      <a class="nav-link collapsed" data-bs-target="#pre-booking-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-calendar-week"></i><span>Manage Pre Bookings</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="pre-booking-nav" class="nav-content collapse <?= (current_url() == base_url('club-owner/pre-booking') || current_url() == base_url('club-owner/list-pre-booking')) ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
        <li>
          <a class="<?= (current_url() == base_url('club-owner/pre-booking')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/pre-booking">
            <i class="bi bi-circle"></i><span>Add Pre Booking</span>
          </a>
        </li>
        <li>
          <a class="<?= (current_url() == base_url('club-owner/list-pre-booking')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/list-pre-booking">
            <i class="bi bi-circle"></i><span>List Pre Booking</span>
          </a>
        </li>
      </ul>
    </li>

    <!-- Manage Membership -->
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#membership-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-person-vcard"></i><span>Manage Membership</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="membership-nav" class="nav-content collapse <?= (current_url() == base_url('club-owner/add-new-membership') || current_url() == base_url('club-owner/list-membership')) ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
        <li>
          <a class="<?= (current_url() == base_url('club-owner/add-new-membership')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/add-new-membership">
            <i class="bi bi-circle"></i><span>Add New Membership</span>
          </a>
        </li>
        <li>
          <a class="<?= (current_url() == base_url('club-owner/list-membership')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/list-membership">
            <i class="bi bi-circle"></i><span>List Membership</span>
          </a>
        </li>
      </ul>
    </li>

    <!-- Manage Inventory -->
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#inventory-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-menu-button-wide"></i><span>Manage Inventory</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="inventory-nav" class="nav-content collapse <?= (current_url() == base_url('club-owner/add-new-inventory') || current_url() == base_url('club-owner/list-inventory')) ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
        <li>
          <a class="<?= (current_url() == base_url('club-owner/add-new-inventory')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/add-new-inventory">
            <i class="bi bi-circle"></i><span>Add New Inventory</span>
          </a>
        </li>
        <li>
          <a class="<?= (current_url() == base_url('club-owner/list-inventory')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/list-inventory">
            <i class="bi bi-circle"></i><span>List Inventory</span>
          </a>
        </li>
      </ul>
    </li>

    <!-- Master -->
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#master-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-menu-button-wide"></i><span>Master</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="master-nav" class="nav-content collapse <?= (current_url() == base_url('club-owner/list-brand') || current_url() == base_url('club-owner/list-category')) ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
        
        <li>
          <a class="<?= (current_url() == base_url('club-owner/list-category')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/list-category">
            <i class="bi bi-circle"></i><span>List Category</span>
          </a>
        </li>
        <li>
          <a class="<?= (current_url() == base_url('club-owner/list-brand')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/list-brand">
            <i class="bi bi-circle"></i><span>List Brand</span>
          </a>
        </li>
        <li>
          <a class="<?= (current_url() == base_url('club-owner/list-size')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/list-size">
            <i class="bi bi-circle"></i><span>List Size</span>
          </a>
        </li>
      </ul>
    </li>

    <!-- Manage Court -->
    <li class="nav-item" style="display: none;">
      <a class="nav-link collapsed" data-bs-target="#court-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-menu-button-wide"></i><span>Manage Court</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="court-nav" class="nav-content collapse <?= (current_url() == base_url('club-owner/add-new-court') || current_url() == base_url('club-owner/list-court')) ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
        <li>
          <a class="<?= (current_url() == base_url('club-owner/add-new-court')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/add-new-court">
            <i class="bi bi-circle"></i><span>Add New Court</span>
          </a>
        </li>
        <li>
          <a class="<?= (current_url() == base_url('club-owner/list-court')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/list-court">
            <i class="bi bi-circle"></i><span>List Court</span>
          </a>
        </li>
      </ul>
    </li>

    <!-- Manage Stringing -->
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#string-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-menu-button-wide"></i><span>Manage Stringing</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="string-nav" class="nav-content collapse <?= (current_url() == base_url('club-owner/add-new-stringing') || current_url() == base_url('club-owner/list-stringing')) ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
        <li>
          <a class="<?= (current_url() == base_url('club-owner/add-new-strigning')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/add-new-stringing">
            <i class="bi bi-circle"></i><span>Add New Stringing</span>
          </a>
        </li>
        <li>
          <a class="<?= (current_url() == base_url('club-owner/list-stringing')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/list-stringing">
            <i class="bi bi-circle"></i><span>List Stringing</span>
          </a>
        </li>
      </ul>
    </li>

    <!-- Payment History -->
    <li class="nav-item" style="display: none;">
      <a class="nav-link <?= (current_url() == base_url('club-owner/payment-history')) ? 'active' : '' ?>" href="<?=base_url()?>club-owner/payment-history">
        <i class="bi bi-credit-card-2-front"></i>
        <span>Payment History</span>
      </a>
    </li>
  </ul>
</aside><!-- End Sidebar-->
