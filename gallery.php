<?php 
include "includes/header.php"; 
include "dbConnection.php";

// Fetch main image (MAIN = 1)
$main_image = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM ca_gallery WHERE MAIN = 1 LIMIT 1"));

// Fetch first 4 small images (MAIN = 0)
$small_images = mysqli_query($conn, "SELECT * FROM ca_gallery WHERE MAIN = 0 LIMIT 4");

// Fetch remaining small images for "more" section
$more_images = mysqli_query($conn, "SELECT * FROM ca_gallery WHERE MAIN = 0 LIMIT 18446744073709551615 OFFSET 4");

// Fetch videos
// $videos = mysqli_query($conn, "SELECT * FROM ca_gallery WHERE TYPE='video'");
?>


<section class="gallerypage_sec bothSide_gap">
  <div class="cust_container">
    <h2 class="heading">Casa Gallery</h2>

    <!-- Tabs -->
    <ul class="tabs">
      <li class="tab active" data-tab="images">Images</li>
      <li class="tab" data-tab="videos">Videos</li>
    </ul>

    <!-- Images Section -->
    <div id="images" class="tab-content active">
      <div id="gallery" class="photos-grid-container gallery">
        <!-- Main Image -->
        <div class="main-photo img-box">
          <?php if ($main_image) { ?>
            <a href="/admin/<?php echo $main_image['IMAGE']; ?>" class="glightbox" data-glightbox="type: image">
              <img src="/admin/<?php echo $main_image['IMAGE']; ?>" alt="Main image" />
            </a>
          <?php } else { ?>
            <p>No main image available</p>
          <?php } ?>
        </div>

        <!-- First 4 small images -->
        <div>
          <div class="sub">
            <?php 
            $count = 0;
            while ($img = mysqli_fetch_assoc($small_images)) { 
              $count++;
              if ($count == 4 && mysqli_num_rows($more_images) > 0) { ?>
                <div id="multi-link" class="img-box">
                  <a href="/admin/<?php echo $img['IMAGE']; ?>" class="glightbox" data-glightbox="type: image">
                    <img src="/admin/<?php echo $img['IMAGE']; ?>" alt="Small image" />
                    <div class="transparent-box">
                      <div class="caption">+<?php echo mysqli_num_rows($more_images); ?></div>
                    </div>
                  </a>
                </div>
              <?php } else { ?>
                <div class="img-box">
                  <a href="/admin/<?php echo $img['IMAGE']; ?>" class="glightbox" data-glightbox="type: image">
                    <img src="/admin/<?php echo $img['IMAGE']; ?>" alt="Small image" />
                  </a>
                </div>
              <?php }
            } ?>
          </div>
        </div>

        <!-- More Images -->
        <?php if (mysqli_num_rows($more_images) > 0) { ?>
          <div id="more-img" class="extra-images-container hide-element">
            <?php while ($img = mysqli_fetch_assoc($more_images)) { ?>
              <a href="/admin/<?php echo $img['IMAGE']; ?>" class="glightbox" data-glightbox="type: image">
                <img src="/admin/<?php echo $img['IMAGE']; ?>" alt="Extra image" />
              </a>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </div>

    <!-- Videos Section -->
    <div id="videos" class="tab-content">
      <div class="video-grid gallery">
        <!--<?//php while ($vid = mysqli_fetch_assoc($videos)) { ?>-->
        <!--  <div class="img-box">-->
        <!--    <a href="<?//php echo $vid['VIDEO_URL']; ?>" class="glightbox" data-glightbox="type: video">-->
        <!--      <img src="/admin/<?//php echo $vid['THUMBNAIL']; ?>" alt="Video" />-->
        <!--      <div class="transparent-box">-->
        <!--        <div class="caption">▶</div>-->
        <!--      </div>-->
        <!--    </a>-->
        <!--  </div>-->
        <!--<?//php } ?>-->
        
        <div class="img-box">
            <a href="#" class="glightbox" data-glightbox="type: video">
              <img src="" alt="Video" />
              <div class="transparent-box">
                <div class="caption">▶</div>
              </div>
            </a>
          </div>
      </div>
    </div>

  </div>
</section>


<?php include "includes/footer.php"; ?>

<script>
  // Tabs
  document.querySelectorAll(".tab").forEach(tab => {
    tab.addEventListener("click", () => {
      document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
      document.querySelectorAll(".tab-content").forEach(c => c.classList.remove("active"));
      tab.classList.add("active");
      document.getElementById(tab.dataset.tab).classList.add("active");
    });
  });

</script>

