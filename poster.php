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

<section class="Casapostbanner_sec bothSide_gap">
<div class="cust_container">

<h6 class="sub_heading" style="color:#22d3ee;">Event</h6>
<h2 class="heading text-white">Posters and Cards</h2>

<div class="advertisement_slider gallery">

<?php
$query = "SELECT media_url FROM ca_landing_page_media 
          WHERE media_type='poster' AND is_active=1";

$result = mysqli_query($conn,$query);

while($row = mysqli_fetch_assoc($result)){

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

<!-- /*<style>*/ -->
<!-- /*.Casapostbanner_sec{*/
/*    background:#0f172a;*/
/*    padding:60px 0;*/
/*}*/

/* Horizontal scrolling */
/*.advertisement_slider{*/
/*    display:flex;*/
/*    gap:20px;*/
/*    overflow-x:auto;*/
/*    padding-top:25px;*/
/*    scroll-behavior:smooth;*/
/*}*/

/* Hide scrollbar */
/*.advertisement_slider::-webkit-scrollbar{*/
/*    display:none;*/
/*}*/

/* Poster card */
/*.item_banner{*/
/*    min-width:220px;*/
/*    flex-shrink:0;*/
/*    transition:transform .35s ease;*/
/*}*/

/* Poster image */
/*.item_banner img{*/
/*    width:100%;*/
/*    height:330px;*/
/*    object-fit:cover;*/
/*    border-radius:10px;*/
/*    box-shadow:0 10px 25px rgba(0,0,0,.5);*/
/*}*/

/* Hover zoom */
/*.item_banner:hover{*/
/*    transform:scale(1.08);*/
/*    z-index:10;*/
/*}*/

/*.item_banner:hover img{*/
/*    box-shadow:0 20px 40px rgba(0,0,0,.8);*/
/*}*/ -->
<!-- /*</style>*/ -->

<style>
/* CASA POSTER SECTION */

.Casapostbanner_sec{
    background:#0f172a;
    padding:60px 0;
}

/* Headings */
.heading{
    font-size:32px;
    font-weight:700;
    margin-bottom:10px;
}

.sub_heading{
    font-size:14px;
    letter-spacing:2px;
    text-transform:uppercase;
    color:#22d3ee;
}

/* Slider container */
.advertisement_slider{
    display:flex;
    gap:20px;
    overflow-x:auto;
    padding-top:30px;
    scroll-behavior:smooth;
}

/* Hide scrollbar */
.advertisement_slider::-webkit-scrollbar{
    display:none;
}

/* Poster card */
.item_banner{
    width:240px;          /* fixed width */
    height:360px;         /* fixed height (2:3 ratio) */
    min-width:240px;
    flex-shrink:0;
    border-radius:12px;
    overflow:hidden;
    background:#111827;
    transition:transform .3s ease, box-shadow .3s ease;
}

/* Poster image */
.item_banner img{
    width:100%;
    height:100%;
    object-fit:cover;     /* ensures uniform crop */
    display:block;
}

/* Hover effect */
.item_banner:hover{
    transform:scale(1.08);
    z-index:5;
    box-shadow:0 20px 40px rgba(0,0,0,0.7);
}

/* Mobile responsive */
@media (max-width:768px){

.advertisement_slider{
    gap:15px;
}

.item_banner{
    width:180px;
    height:270px;
    min-width:180px;
}

}
</style>