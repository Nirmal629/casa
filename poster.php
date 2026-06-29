<!--<section class="Casapostbanner_sec bothSide_gap" style="background: #0f172a;">-->
<!--    <div class="cust_container">-->
<!--        <h6 class="sub_heading" style="color: #22d3ee !important;">Event</h6>-->
<!--        <h2 class="heading text-white">Posters and Cards</h2>-->

<!--        <div class="advertisement_slider gallery">-->
<!--            <div class="item_banner img-box">-->
<!--                <a href="assets/images/casa_page-0001.jpg" class="glightbox" data-glightbox="type: image">-->
<!--                <img src="assets/images/casa_page-0001.jpg" class="img-fluid" alt="banner" style="width:100%"/>-->
<!--                </a>-->
<!--            </div>-->
<!--            <div class="item_banner img-box">-->
<!--                <a href="assets/images/Casa Club 1_page-0001.jpg" class="glightbox" data-glightbox="type: image">-->
<!--                <img src="assets/images/Casa Club 1_page-0001.jpg" class="img-fluid" alt="banner" style="width:100%"/>-->
<!--                </a>-->
<!--            </div>-->
<!--             <div class="item_banner img-box">-->
<!--                <a href="assets/images/Casa Visiting Card_page-0001.jpg" class="glightbox" data-glightbox="type: image">-->
<!--                <img src="assets/images/Casa Visiting Card_page-0001.jpg" class="img-fluid" alt="banner" style="width:100%"/>-->
<!--                </a>-->
<!--            </div>-->
<!--            <div class="item_banner img-box">-->
<!--                <a href="assets/images/Casa Visiting Card_page-0002.jpg" class="glightbox" data-glightbox="type: image">-->
<!--                <img src="assets/images/Casa Visiting Card_page-0002.jpg" class="img-fluid" alt="banner" style="width:100%"/>-->
<!--                </a>-->
<!--            </div> -->
<!--        </div>-->

<!--    </div>-->
<!--</section>-->

<style>
    /* CASA POSTER SECTION */

    .Casapostbanner_sec {
        background: #0f172a;
    }

    .sub_heading {
        color: #22d3ee !important;
    }

</style>

<section class="Casapostbanner_sec bothSide_gap">
    <div class="cust_container">

     <div class="text-center d-flex flex-column align-items-center">
        <!-- <h6 class="sub_heading">Event</h6> -->
        <h2 class="heading text-white">Posters and Cards</h2>
     </div>

        <div class="advertisement_slider gallery">

            <?php
            $query = "SELECT media_url FROM ca_landing_page_media 
          WHERE media_type='poster' AND is_active=1";

            $result = mysqli_query($conn, $query);

            while ($row = mysqli_fetch_assoc($result)) {

                $image = $row['media_url'];
            ?>

                <div class="item_banner">
                    <a href="<?php echo $image; ?>" class="glightbox" data-glightbox="type:image">
                        <img src="<?php echo $image; ?>" alt="Casa Poster">
                    </a>
                </div>

            <?php } ?>

        </div>

    </div>
</section>


