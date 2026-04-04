<?php
ob_start();
session_start();
// print_r($_SESSION);
// exit;
if (isset($_SESSION['user_id'])) {
    if (!empty($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        header("Location: index.php"); // Default fallback
    }
    exit();
}
?>

<!-----Header------>
<?php include "includes/header.php"; 
?>

<!-----new banner-------->
<section class="homebanner_sec bottomSide_gap">
    <div class="banner_image herobanner_slider">
        <div class="item">
            <div class="item_img">
                <img src="assets/images/herobanner2.jpg" class="img-fluid" alt="banner-image" />
            </div>
            <div class="cust_container">
                <div class="row bothSide_gap">
                    <!-- Left Blank Side -->
                    <div class="col-lg-6 col-12">
                        <div class="banner_content">
                            <div class="wrapper" data-aos="fade-left" data-aos-duration="2000">
                                <h6 class="Homebanner sub_heading">CasaInfoTech:</h6>
                                <h1 class="Homebanner heading">Uniting Passion <span>for Sports</span></h1>
                                <p class="bannerdesc desc mb-1">We are your all-in-one platform, connecting sports enthusiasts with playmates, helping you find venues, enhance your skills, manage activities effortlessly</p>
                                <p class="bannerdesc desc">From friendly matches to competitive tournaments, Casa is the perfect platform to enjoy a strong, supportive community that shares your passion.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side Form -->
                    <div class="col-lg-6 col-12 d-flex align-items-center justify-content-center right-side">
                        <?php include "includes/Auth/login.php"; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="item">
            <div class="item_img">
                <img src="assets/images/herobanner3.jpg" class="img-fluid" alt="banner-image" />
            </div>
            <div class="cust_container">
                <div class="row bothSide_gap">
                    <!-- Left Blank Side -->
                    <div class="col-lg-6 col-12">
                        <div class="banner_content">
                            <div class="wrapper" data-aos="fade-left" data-aos-duration="2000">
                                <h6 class="Homebanner sub_heading">Uniting Passion for Sports:</h6>
                                <h1 class="Homebanner heading">Casa Badminton Training <span>Toronto</span></h1>
                                <p class="bannerdesc desc">Elevate your game with professional badminton training for men, women, and kids &minus; flexible sessions to match your schedule!</p>
                              </div>
                        </div>
                    </div>

                    <!-- Right Side Form -->
                    <div class="col-lg-6 col-12 d-flex align-items-center justify-content-center right-side">
                        <?php include "includes/Auth/login.php"; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="item">
            <div class="item_img">
                <img src="assets/images/woman-outdoors2.jpg" class="img-fluid" alt="banner-image" />
            </div>
            <div class="cust_container">
                <div class="row bothSide_gap">
                    <!-- Left Blank Side -->
                    <div class="col-lg-6 col-12">
                        <div class="banner_content">
                            <div class="wrapper" data-aos="fade-left" data-aos-duration="2000">
                                <h6 class="Homebanner sub_heading">Uniting Passion for Sports:</h6>
                                <h1 class="Homebanner heading">Casa Badminton <span>Store</span></h1>
                                <p class="bannerdesc desc">Discover premium shuttlecocks and accessories from the sports most trusted brands &minus; high-quality products at the most competitive prices.</p>
                                <a href="https://casainfotech.com/tournament/" target="_blank" class="learnmore_btn btn">Read More</a> 
                               </div>
                        </div>
                    </div>

                    <!-- Right Side Form -->
                    <div class="col-lg-6 col-12 d-flex align-items-center justify-content-center right-side">
                        <?php include "includes/Auth/login.php"; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-------category_sec-------->
<section class="category_sec bothSide_gap">
    <div class="cust_container">
        <div class="categorycard_wrap">
            <a href="about-us.php" class="category_card">
                <div class="icon">
                    <i class="fa-regular fa-address-card"></i>
                </div>
                <h4 class="name">Casa About Us</h4>
                <div class="d-flex align-items-center justify-content-center">
                    <p class="readmore_btn btn">Learn More</p>
                </div>
            </a>
            <a href="organiser.php" class="category_card">
                <div class="icon">
                    <i class="fa-solid fa-briefcase"></i>
                </div>
                <h4 class="name">Casa for Organiser</h4>
                <div class="d-flex align-items-center justify-content-center">
                    <p class="readmore_btn btn">Learn More</p>
                </div>
            </a>
            <a href="players.php" class="category_card">
                <div class="icon">
                    <i class="fa-solid fa-users"></i>
                </div>
                <h4 class="name">Casa for Players</h4>
                <div class="d-flex align-items-center justify-content-center">
                    <p class="readmore_btn btn">Learn More</p>
                </div>
            </a>
            <a href="casa-trainers.php" class="category_card">
                <div class="icon">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <h4 class="name">Casa for trainers</h4>
                <div class="d-flex align-items-center justify-content-center">
                    <p class="readmore_btn btn">Learn More</p>
                </div>
            </a>
            <a href="casa-clubs.php" class="category_card">
                <div class="icon">
                    <i class="fa-solid fa-hotel"></i>
                </div>
                <h4 class="name">Casa for clubs</h4>
                <div class="d-flex align-items-center justify-content-center">
                    <p class="readmore_btn btn">Learn More</p>
                </div>
            </a>
        </div>
    </div>
</section>

<!----tournament----->
<section class="tournament_sec bothSide_gap">
    <div class="cust_container">
        <h6 class="sub_heading">Tournament</h6>
        <h2 class="heading">Tournament this month</h2>
        <div class="tournamentcard_slider">
            <a href="tournament-details.php" class="tournament_card">
                <div class="image">
                    <img src="https://media.istockphoto.com/id/172486068/photo/badminton-player-with-a-racket-in-his-hand-hit-shuttlecock.jpg?s=612x612&w=0&k=20&c=kgXUcu_gFz6wwh11PmYPehy8bsYjsKV_vccBFSBalqw=" class="img" alt="tournament image" />
                </div>
                <div class="content">
                    <h4 class="name">Men's Singles</h4>
                    <p class="desc">Jan 28, 2026 at 9am Casa Badminton Club</p>
                    <p class="desc">$50 Per Player</p>
                </div>
            </a>
            <a href="tournament-details.php" class="tournament_card">
                <div class="image">
                    <img src="https://media.istockphoto.com/id/172486068/photo/badminton-player-with-a-racket-in-his-hand-hit-shuttlecock.jpg?s=612x612&w=0&k=20&c=kgXUcu_gFz6wwh11PmYPehy8bsYjsKV_vccBFSBalqw=" class="img" alt="tournament image" />
                </div>
                <div class="content">
                    <h4 class="name">Men's Singles</h4>
                    <p class="desc">Jan 28, 2026 at 9am Casa Badminton Club</p>
                    <p class="desc">$50 Per Player</p>
                </div>
            </a>
            <a href="tournament-details.php" class="tournament_card">
                <div class="image">
                    <img src="https://media.istockphoto.com/id/172486068/photo/badminton-player-with-a-racket-in-his-hand-hit-shuttlecock.jpg?s=612x612&w=0&k=20&c=kgXUcu_gFz6wwh11PmYPehy8bsYjsKV_vccBFSBalqw=" class="img" alt="tournament image" />
                </div>
                <div class="content">
                    <h4 class="name">Men's Singles</h4>
                    <p class="desc">Jan 28, 2026 at 9am Casa Badminton Club</p>
                    <p class="desc">$50 Per Player</p>
                </div>
            </a>
            <a href="tournament-details.php" class="tournament_card">
                <div class="image">
                    <img src="https://media.istockphoto.com/id/172486068/photo/badminton-player-with-a-racket-in-his-hand-hit-shuttlecock.jpg?s=612x612&w=0&k=20&c=kgXUcu_gFz6wwh11PmYPehy8bsYjsKV_vccBFSBalqw=" class="img" alt="tournament image" />
                </div>
                <div class="content">
                    <h4 class="name">Men's Singles</h4>
                    <p class="desc">Jan 28, 2026 at 9am Casa Badminton Club</p>
                    <p class="desc">$50 Per Player</p>
                </div>
            </a>
            <a href="tournament-details.php" class="tournament_card">
                <div class="image">
                    <img src="https://media.istockphoto.com/id/172486068/photo/badminton-player-with-a-racket-in-his-hand-hit-shuttlecock.jpg?s=612x612&w=0&k=20&c=kgXUcu_gFz6wwh11PmYPehy8bsYjsKV_vccBFSBalqw=" class="img" alt="tournament image" />
                </div>
                <div class="content">
                    <h4 class="name">Men's Singles</h4>
                    <p class="desc">Jan 28, 2026 at 9am Casa Badminton Club</p>
                    <p class="desc">$50 Per Player</p>
                </div>
            </a>
        </div>
    </div>
</section>


<!-----The playground------->
<section class="playground_sec bothSide_gap">
    <div class="cust_container">
        <h4 class="sub_heading">playground</h4>
        <h2 class="heading">The playground</h2>
        <ul class="playground_list">
            <li class="playground_box">
                <div class="icon">
                    <i class="fa-solid fa-users"></i>
                </div>
                <h4 class="count" data-target="86">0</h4>
                <p class="name">Total Players</p>
            </li>
            <li class="playground_box">
                <div class="icon">
                    <i class="fa-solid fa-building"></i>
                </div>
                <h4 class="count" data-target="14">0</h4>
                <p class="name">Total Clubs</p>
            </li>
            <li class="playground_box">
                <div class="icon">
                    <i class="fa-solid fa-play"></i>
                </div>
                <h4 class="count" data-target="22">0</h4>
                <p class="name">Total Sessions</p>
            </li>
            <li class="playground_box">
                <div class="icon">
                    <i class="fa-solid fa-fire"></i>
                </div>
                <h4 class="count" data-target="76">0</h4>
                <p class="name">Total Matches</p>
            </li>
        </ul>
    </div>
</section>


<!-----About-Us------->
<!--<section class="aboutus_sec bothSide_gap">-->
<!--    <div class="cust_container">-->
<!--        <div class="row">-->
<!--            <div class="col-lg-7 col-md-12 col-12 m-auto">-->
<!--                <h4 class="sub_heading">About Us</h4>-->
<!--                <h2 class="heading">Welcome to the Casa Club</h2>-->
<!--                <p class="desc">At The Batminton Club, we don’t just play badminton — we elevate it.</p>-->
<!--                <p class="desc">From our world-class courts to our elite training programs, we provide everything-->
<!--                    serious players need to reach their highest potential. Our mission is to blend performance,-->
<!--                    professionalism, and passion into an unmatched badminton experience.</p>-->
<!--                <h6 class="miniheading">Our Vision</h6>-->
<!--                <p class="desc">To be the premier destination for badminton in City, setting the standard for excellence in facilities, coaching, and member experience.</p>-->

<!--                <h6 class="miniheading">What We Offer</h6>-->

<!--                <ul>-->
<!--                    <li>Cutting-edge facilities maintained to international standards</li>-->
<!--                    <li>Expert coaches with proven track records and personalized coaching</li>-->
<!--                    <li>Structured lessons, fitness training, strategy workshops, mental coaching</li>-->
<!--                    <li>Competitive tournaments and matches across all levels</li>-->
<!--                    <li>Member amenities that go beyond the court: relaxing lounges, gear shop, community events</li>-->
<!--                </ul>-->


<!--            </div>-->
<!--            <div class="col-lg-5 col-md-12 col-12">-->
<!--                <div class="aboutimg_wrap">-->
<!--                    <img src="assets/images/trainer-pic.jpg" class="img-fluid" loading="lazy" alt="image...">-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->
<!--</section>-->

<!-----WhyChooseUs------->
<!--<section class="homeAbout_sec bothSide_gap" id="WhyChooseUs">-->
<!--    <div class="cust_container">-->
<!--        <h6 class="sub_heading">Why choose us </h6>-->
<!--        <div class="row mb-5">-->
<!--            <div class="col-xl-8 col-lg-7 col-md-12 col-12">-->
<!--                <h2 class="heading">As a Host/Trainer:</h2>-->
                <!-- <p class="desc">Schedule games and mark events as active or cancelled </p> -->
<!--                <ul class="list_wrap">-->
<!--                    <li>Schedule games and mark events as active or cancelled</li>-->
<!--                    <li>Define event details: venue, date, and time</li>-->
<!--                    <li>Define event category(Badminton, Tennis, Cricket)</li>-->
<!--                    <li>Define event sub-category (Men, Women, Mixed, Singles, Kids)</li>-->
<!--                    <li>Define event skill level (Beginner, Amateur, Intermediate, Advanced, Professional)</li>-->
<!--                    <li>Define event type (Public, Invite Only)</li>-->
<!--                    <li>Define event cost (court fees, birdie costs, player fees)</li>-->
<!--                    <li>Define freeze time (default: six hours before event; cancellations not allowed after freeze time)</li>-->
<!--                    <li>Track daily/monthly payments and mark them as verified</li>-->
<!--                    <li>Send payment reminders to players regularly (daily/monthly)</li>-->
<!--                    <li>Generate monthly settlement statements for accounting</li>-->
<!--                </ul>-->
                <!-- <a href="#" class="getQuote_btn btn">Learn More</a> -->
<!--            </div>-->

<!--            <div class="col-xl-4 col-lg-5 col-md-12 col-12">-->
<!--                <div class="image_wrap" data-aos="fade-left" data-aos-duration="2000">-->
<!--                    <img src="assets/images/trainer-pic.jpg" class="img-fluid" alt="image" />-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->
<!--        <div class="row columnReverse">-->
<!--            <div class="col-xl-4 col-lg-5 col-md-12 col-12">-->
<!--                <div class="image_wrap" data-aos="fade-right" data-aos-duration="2000">-->
<!--                    <img src="assets/images/young-sporty.jpg" class="img-fluid" alt="image" />-->
<!--                </div>-->
<!--            </div>-->
<!--            <div class="col-xl-8 col-lg-7 col-md-12 col-12">-->
<!--                <h2 class="heading">As a Player: </h2>-->
                <!-- <p class="desc">Request login by filling out the registration form on the website </p> -->
<!--                <ul class="list_wrap">-->
<!--                    <li>Request login by filling out the registration form on the website</li>-->
<!--                    <li>Admin will send login credentials via email or WhatsApp</li>-->
<!--                    <li>Default skill level is set to Beginner</li>-->
<!--                    <li>Note: Skill levels can be challenged based on peer and host reviews</li>-->
<!--                    <li>Log into your account</li>-->
<!--                    <li>Choose dates and times that fit your schedule, level, and join events</li>-->
<!--                    <li>Cancel games before freeze time if you're unable to attend</li>-->
<!--                    <li>Pay event costs directly to the host (daily/monthly, as per your agreement) and mark payment details</li>-->
<!--                    <li>Download monthly statements (paid/dues)</li>-->
<!--                </ul>-->
                <!-- <a href="#" class="getQuote_btn btn">Learn More</a> -->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->
<!--</section>-->


<!-----Casa-post-banner------->
<div id="eventCard_post">
  <?php include "./poster.php"; ?>
</div>

<!------bookVenues_sec------->
<!-- <section class="bookVenues_sec bothSide_gap">
    <div class="cust_container">
        <div class="section_top">
            <h6 class="sub_heading">Lorem ipsum</h6>
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h2 class="heading">Book Venues</h2>
                <a href="#" class="seeAll_btn btn">See all Venues</a>
            </div>
        </div>

        <div class="bookVenues_slider">
            <div class="bookVenues_card">
                <a href="#">
                    <div class="image_wrap">
                        <img src="assets/images/card-img.webp" class="img-fluid" alt="image" />
                        <span class="ratting">4.7 (6)</span>
                    </div>
                    <h6 class="head">Game Theory - Joseph's...</h6>
                    <p class="desc">Gate 3, No.2, Vittal M... (~0.13 Kms)</p>
                </a>
            </div>
            <div class="bookVenues_card">
                <a href="#">
                    <div class="image_wrap">
                        <img src="assets/images/card-img.webp" class="img-fluid" alt="image" />
                        <span class="ratting">4.7 (6)</span>
                    </div>
                    <h6 class="head">Game Theory - Joseph's...</h6>
                    <p class="desc">Gate 3, No.2, Vittal M... (~0.13 Kms)</p>
                </a>
            </div>
            <div class="bookVenues_card">
                <a href="#">
                    <div class="image_wrap">
                        <img src="assets/images/card-img.webp" class="img-fluid" alt="image" />
                        <span class="ratting">4.7 (6)</span>
                    </div>
                    <h6 class="head">Game Theory - Joseph's...</h6>
                    <p class="desc">Gate 3, No.2, Vittal M... (~0.13 Kms)</p>
                </a>
            </div>
            <div class="bookVenues_card">
                <a href="#">
                    <div class="image_wrap">
                        <img src="assets/images/card-img.webp" class="img-fluid" alt="image" />
                        <span class="ratting">4.7 (6)</span>
                    </div>
                    <h6 class="head">Game Theory - Joseph's...</h6>
                    <p class="desc">Gate 3, No.2, Vittal M... (~0.13 Kms)</p>
                </a>
            </div>
            <div class="bookVenues_card">
                <a href="#">
                    <div class="image_wrap">
                        <img src="assets/images/card-img.webp" class="img-fluid" alt="image" />
                        <span class="ratting">4.7 (6)</span>
                    </div>
                    <h6 class="head">Game Theory - Joseph's...</h6>
                    <p class="desc">Gate 3, No.2, Vittal M... (~0.13 Kms)</p>
                </a>
            </div>
        </div>
    </div>
</section> -->

<!-----casa for Tournament------->
<section id="casaTournament_sec" class="Storebanner_sec bothSide_gap mt-4" style="background-image: url(./assets/images/Tournament-bg.jpeg)">
    <div class="cust_container">
        <div class="wraper py-4">
            <div class="d-flex align-items-center justify-content-center w-100">
                <h6 class="sub_heading white">Casa for Tournament</h6>
            </div>
            <h2 class="heading white">Badminton Tournament Management Software</h2>
            <p class="desc white">Simplify your tournament operations with our all-in-one badminton event management platform, designed for both organizers and players.</p>
            
            <h6 class="miniheading text-center mt-2 mb-1">Key Features:</h6>
             <p class="desc white mb-2">
                    <span class="pe-1">1).Online Game-Day Dashboard: Get a real-time overview of all ongoing matches and schedules.</span>
                    <span class="pe-1">2).Player Registration: Players can easily join games or tournaments online.</span>
                    <span class="pe-1">3).Team Grouping: Automatically create and manage player or team groups.</span>
                    <span class="pe-1">4).Double Elimination Format: Organize fair and competitive matches using the double-elimination system.</span>
                    <span class="pe-1">5).Live Score Tracking: Display real-time scores for each court with complete match history.</span>
                    <span class="pe-1">6).Leaderboard: Showcase player rankings and tournament progress dynamically.</span>
            </p>
           
            <p class="desc white">Run your tournaments smoothly and deliver an engaging experience for players and spectators alike.</p>
            <div class="d-flex align-items-center justify-content-center w-100 pt-4">
                <a href="#" class="btn btn-info rounded text-white">Read More</a>
            </div>
        </div>
    </div>
</section>

<!-----Popular Sports------->
<section class="popularSports_sec bothSide_gap">
    <div class="cust_container">
        <div class="section_top">
            <h6 class="sub_heading">Sports</h6>
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h2 class="heading">Select The Sports</h2>
                <!-- <a href="#" class="seeAll_btn btn">See all</a> -->
            </div>
        </div>

        <!----Sports-Tab-start----->
        <ul class="popularSports_wrap">
            <li class="clickme">
                <a href="javascript:void();" data-tag="BadmintonTab" class="activelink">
                    <div class="popularSports_card">
                        <img src="assets/images/game/badminton_new.avif" class="img-fluid" alt="game" />
                        <span class="game_name">Badminton</span>
                    </div>
                </a>
            </li>
            <li class="clickme">
                <a href="javascript:void();" data-tag="FootballTab" class="">
                    <div class="popularSports_card">
                        <img src="assets/images/game/football_new.avif" class="img-fluid" alt="game" />
                        <span class="game_name">Football</span>
                        <span class="coming_soon">[ Coming Soon ]</span>
                    </div>
                </a>
            </li>
            <li class="clickme">
                <a href="javascript:void();" data-tag="CricketTab" class="">
                    <div class="popularSports_card">
                        <img src="assets/images/game/cricket_new.avif" class="img-fluid" alt="game" />
                        <span class="game_name">Cricket</span>
                        <span class="coming_soon">[ Coming Soon ]</span>
                    </div>
                </a>
            </li>
            <li class="clickme">
                <a href="javascript:void();" data-tag="SwimmingTab" class="">
                    <div class="popularSports_card">
                        <img src="assets/images/game/swimming_new.avif" class="img-fluid" alt="game" />
                        <span class="game_name">Swimming</span>
                        <span class="coming_soon">[ Coming Soon ]</span>
                    </div>
                </a>
            </li>
            <li class="clickme">
                <a href="javascript:void();" data-tag="TennisTab" class="">
                    <div class="popularSports_card">
                        <img src="assets/images/game/tennis_new.avif" class="img-fluid" alt="game" />
                        <span class="game_name">Tennis</span>
                        <span class="coming_soon">[ Coming Soon ]</span>
                    </div>
                </a>
            </li>
            <li class="clickme">
                <a href="javascript:void();" data-tag="TableTennisTab" class="">
                    <div class="popularSports_card">
                        <img src="assets/images/game/table_tennis_new.avif" class="img-fluid" alt="game" />
                        <span class="game_name">Table Tennis</span>
                        <span class="coming_soon">[ Coming Soon ]</span>
                    </div>
                </a>
            </li>
        </ul>

        <div style="clear: both;"></div>
        <div>
            <div class="list" id="BadmintonTab">
                <?php include "discover-games.php"; ?>
            </div>
            <div class="list hide" id="FootballTab">
                <div class="comingsoon_image">
                    <img src="assets/images/giphy.gif" class="img-fluid" alt="Coming Soon" />
                </div>
            </div>
            <div class="list hide" id="CricketTab">
                <div class="comingsoon_image">
                    <img src="assets/images/giphy.gif" class="img-fluid" alt="Coming Soon" />
                </div>
            </div>
            <div class="list hide" id="SwimmingTab">
                <div class="comingsoon_image">
                    <img src="assets/images/giphy.gif" class="img-fluid" alt="Coming Soon" />
                </div>
            </div>
            <div class="list hide" id="TennisTab">
                <div class="comingsoon_image">
                    <img src="assets/images/giphy.gif" class="img-fluid" alt="Coming Soon" />
                </div>
            </div>
            <div class="list hide" id="TableTennisTab">
                <div class="comingsoon_image">
                    <img src="assets/images/giphy.gif" class="img-fluid" alt="Coming Soon" />
                </div>
            </div>
        </div>
        <!----Sports-Tab-End----->

    </div>
</section>

<!-----casa for Stadium------->
<section id="casaStadium_sec" class="Storebanner_sec bothSide_gap mt-3" style="background-image: url(./assets/images/Casa_Stadium.jpg)">
    <div class="cust_container">
        <div class="wraper py-4">
            <div class="d-flex align-items-center justify-content-center w-100">
                <h6 class="sub_heading white">Casa for Stadium</h6>
            </div>
            <h2 class="heading white">Court Booking Management Software</h2>
            <p class="desc white">Experience seamless and efficient court management with our all-in-one software solution, designed to simplify operations and enhance member satisfaction.</p>
            <h6 class="miniheading text-center mt-2 mb-1">Key Features:</h6>
            <p class="desc white mb-2">
                <span class="pe-1">1).<b>Membership Management:</b> Easily handle player registrations, renewals, and profiles</span>
                <span class="pe-1">2).<b>Daily & Advance Bookings:</b> Manage both same-day and future court reservations with ease.</span>
                <span class="pe-1">3).<b>Inventory Management:</b> Track and control sports equipment and facility resources.</span>
                <span class="pe-1">4).<b>Stringing Services:</b> Organize and monitor stringing requests efficiently.</span>
                <span class="pe-1">5).<b>Online Player Booking:</b> Allow players to book courts anytime, anywhere.</span>
                <span class="pe-1">6).<b>Billing & Payment History:</b> Simplify transactions and maintain detailed payment records.</span>
            </p>
            <p class="desc white">Streamline your club operations and elevate the player experience all through one powerful platform.</p>
            <div class="d-flex align-items-center justify-content-center w-100 pt-4">
                <a href="#" class="btn btn-info rounded text-white">Read More</a>
            </div>
        </div>
    </div>
</section>

<!-----testimonials--------->
<section class="testimonials_sec bothSide_gap">
    <div class="cust_container">
        <div class="section_top">
            <h6 class="sub_heading">Testimonials</h6>
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h2 class="heading">What our players say</h2>
                <a href="#" class="seeAll_btn btn">See all</a>
            </div>
        </div>

        <div class="testimonials_all testimonials_slider">
            <div class="testimonials_card">
                <h4 class="name">Nirmal Thakur</h4>
                <p class="desc">Lorem ipsum, dolor sit amet consectetur adipisicing elit. Eos omnis sequi temporibus obcaecati dolorem saepe odio neque quasi velit magni officiis beatae libero unde, a fugiat consequuntur, numquam ut iste?</p>
                <div class="rating_wrap">
                    <div class="ratings">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class=""></span>
                    </div>
                    <p class="google">Google</p>
                </div>
            </div>
            <div class="testimonials_card">
                <h4 class="name">Nirmal Thakur</h4>
                <p class="desc">Lorem ipsum dolor sit amet consectetur adipisicing elit. Saepe recusandae est magni, earum tempore, animi molestias ullam veniam praesentium culpa ipsum eius asperiores. Esse eos quaerat temporibus, obcaecati debitis reiciendis.</p>
                <div class="rating_wrap">
                    <div class="ratings">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class=""></span>
                    </div>
                    <p class="google">Google</p>
                </div>
            </div>
            <div class="testimonials_card">
                <h4 class="name">Nirmal Thakur</h4>
                <p class="desc">Lorem ipsum dolor sit amet consectetur adipisicing elit. Amet ratione quidem veritatis ullam repellat alias molestias, similique voluptates, sit ipsum ea repudiandae blanditiis incidunt aliquam error molestiae corporis et nemo?</p>
                <div class="rating_wrap">
                    <div class="ratings">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class=""></span>
                    </div>
                    <p class="google">Google</p>
                </div>
            </div>
            <div class="testimonials_card">
                <h4 class="name">Nirmal Thakur</h4>
                <p class="desc">Lorem ipsum dolor sit, amet consectetur adipisicing elit. Soluta ullam error dolor non cupiditate totam officiis nam, molestiae aperiam expedita natus voluptatibus labore nulla tenetur temporibus illum quis odit. Sit.</p>
                <div class="rating_wrap">
                    <div class="ratings">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class=""></span>
                    </div>
                    <p class="google">Google</p>
                </div>
            </div>
            <div class="testimonials_card">
                <h4 class="name">Nirmal Thakur</h4>
                <p class="desc">Lorem ipsum dolor sit amet consectetur adipisicing elit. Placeat nisi recusandae eveniet, perspiciatis culpa exercitationem expedita, soluta deserunt qui non officia ipsum. Esse pariatur impedit tempora dolorum tenetur quam non.</p>
                <div class="rating_wrap">
                    <div class="ratings">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class=""></span>
                    </div>
                    <p class="google">Google</p>
                </div>
            </div>
            <div class="testimonials_card">
                <h4 class="name">Nirmal Thakur</h4>
                <p class="desc">Lorem ipsum dolor sit, amet consectetur adipisicing elit. Quod eligendi laborum maiores, in nulla tenetur tempore ducimus accusamus dicta id dignissimos neque quisquam aliquid cum ut voluptatibus incidunt nam deserunt?</p>
                <div class="rating_wrap">
                    <div class="ratings">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class=""></span>
                    </div>
                    <p class="google">Google</p>
                </div>
            </div>
            <div class="testimonials_card">
                <h4 class="name">Nirmal Thakur</h4>
                <p class="desc">Lorem ipsum, dolor sit amet consectetur adipisicing elit. Ad laudantium, consectetur cum accusantium maiores delectus sequi magni reiciendis suscipit aperiam molestiae fugit recusandae similique labore eligendi ut culpa iure officia.</p>
                <div class="rating_wrap">
                    <div class="ratings">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class=""></span>
                    </div>
                    <p class="google">Google</p>
                </div>
            </div>
            <div class="testimonials_card">
                <h4 class="name">Nirmal Thakur</h4>
                <p class="desc">Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptatibus necessitatibus nostrum quod, possimus aliquid dolores voluptas incidunt nesciunt provident, quis aperiam nulla perferendis sint unde quidem obcaecati? Facere, magnam iste.</p>
                <div class="rating_wrap">
                    <div class="ratings">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class=""></span>
                    </div>
                    <p class="google">Google</p>
                </div>
            </div>
        </div>
    </div>
</section>


<!----Store-------->
<section class="Storebanner_sec bothSide_gap mt-3" style="background-image: url(./assets/images/bags-rack-store.jpeg)">
    <div class="cust_container">
        <div class="wraper">
            <div class="d-flex align-items-center justify-content-center w-100">
                <h6 class="sub_heading white">Store</h6>
            </div>
            <h2 class="heading white">Visit Our Store</h2>
              <p class="desc white">We offer a thoughtfully selected range of shuttlecocks and accessories from the most trusted and renowned brands in the sport.</p>
            <p class="desc white">Whether you're just starting out or a seasoned professional, our collection caters to badminton players of all levels.</p>
            <p class="desc white">Our mission is to provide authentic, high-quality products at the most competitive prices</p>
            <p class="desc white">Visit our store to explore our full range and learn more about our offerings.</p>
            <div class="d-flex align-items-center justify-content-center w-100 pt-4">
                <a href="product-listing.php" class="btn btn-info rounded text-white">Visit Store</a>
            </div>
        </div>
    </div>
</section>

<!----contact-us------->
<?php include "./contact-us.php"; ?>

<!------footer------>
<?php include "includes/footer.php";
ob_end_flush();
?>