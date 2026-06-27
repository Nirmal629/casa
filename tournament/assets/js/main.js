
// Navbar & Active and Submenu Start
jQuery(document).ready(function () {
    //main Menu
    jQuery("#open_Sidebar").click(function () {
        jQuery(".menubar_box").toggleClass("open");
        jQuery(".responsivemenubar_btn").toggleClass("on");
    });
    //End
    
    jQuery(".navber_wrap li a").click(function () {
        jQuery(".menubar_box").toggleClass("open");
        jQuery(".responsivemenubar_btn").toggleClass("on");
    });

    //Sub Menu
    jQuery(".navber_wrap li .icon").click(function () {
        jQuery(this)
            .toggleClass("active")
            .next(".navber_wrap li ul")
            .slideToggle()
            .parent()
            .siblings()
            .find(".navber_wrap li ul")
            .slideUp()
            .prev()
            .removeClass("active");
    });
    //End
})
// Navbar & Active and Submenu End



//Header Sticky
window.onscroll = function () { myheaderFunction() };
var header = document.getElementById("main_Header");
var sticky = header.offsetTop;
function myheaderFunction() {
    if (window.pageYOffset > sticky) {
        header.classList.add("sticky");
    } else {
        header.classList.remove("sticky");
    }
}


//Scroll to top 
if ($('.return-to-top').length) {
    var scrollTrigger = 300,
        backToTop = function () {
            var scrollTop = $(window).scrollTop();
            if (scrollTop > scrollTrigger) {
                $('.return-to-top').addClass('show');
            } else {
                $('.return-to-top').removeClass('show');
            }
        };
    backToTop();
    $(window).on('scroll', function () {
        backToTop();
    });
    $('.return-to-top').on('click', function (e) {
        e.preventDefault();
        $('html,body').animate({
            scrollTop: 0
        }, 700);
    });
}


/////homebanner_slider
$('.homebanner_slider').slick({
    speed: 300,
    infinite: true,
    slidesToShow: 1,
    slidesToScroll: 1,
    dots: true,
    autoplay: true,
    autoplaySpeed: 3000,
    arrows: false,
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'
});


//show more result
function showResults(event) {
    event.preventDefault(); // prevent page refresh
    document.getElementById("extra-results").style.display = "block";
    event.target.style.display = "none"; // hide the button
}
















