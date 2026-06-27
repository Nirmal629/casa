

// Navbar & Active and Submenu Start

jQuery(document).ready(function () {

    // Main Menu

    jQuery("#open_Sidebar").click(function () {

        jQuery(".menubar_box").toggleClass("open");

        jQuery(".responsivemenubar_btn").toggleClass("on");

    });



    // Sub Menu

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



    // Close Sidebar When Any Menu Link Is Clicked

    jQuery(".menubar_box .navber_wrap li a").click(function () {

        jQuery(".menubar_box").removeClass("open");

        jQuery(".responsivemenubar_btn").removeClass("on");

    });

});



// Navbar & Active and Submenu End





//sub menu

$(document).ready(function () {

    $(".navber_wrap li .icon").click(function () {

        $(this)

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

});





document.addEventListener("DOMContentLoaded", function () {



    //Header Sticky===============

    var header = document.getElementById("main_Header");

    var sticky = header ? header.offsetTop : 0;



    //Scroll To Top==========

    var scrollTrigger = 150;

    var backToTop = document.querySelector('.return-to-top');



    function onScroll() {

        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;



        // Sticky Header

        if (header) {

            if (scrollTop > sticky) {

                header.classList.add("sticky");

            } else {

                header.classList.remove("sticky");

            }

        }



        // Back To Top Button

        if (backToTop) {

            if (scrollTop > scrollTrigger) {

                backToTop.classList.add("show");

            } else {

                backToTop.classList.remove("show");

            }

        }

    }



    // Scroll event

    window.addEventListener("scroll", onScroll);



    // Initial check on page load

    onScroll();



    // Back to top click

    if (backToTop) {

        backToTop.addEventListener("click", function (e) {

            e.preventDefault();

            window.scrollTo({

                top: 0,

                behavior: "smooth"

            });

        });

    }



});







////new add js Start===============================================>

//Hero banner slider

jQuery('.herobanner_slider').slick({

    slidesToShow: 1,

    slidesToScroll: 1,

    fade: true,

    infinite: true,

    autoplay: true,

    autoplaySpeed: 3000,

    arrows: false,

    dots: true,

    pauseOnHover: true

});



// Extra control if pauseOnHover doesn't work

jQuery('.herobanner_slider').on('mouseenter', function () {

    jQuery(this).slick('slickPause');

});



jQuery('.herobanner_slider').on('mouseleave', function () {

    jQuery(this).slick('slickPlay');

});



//Clients slider

jQuery('.testimonials_slider').slick({

    infinite: true,

    speed: 300,

    slidesToShow: 3,

    slidesToScroll: 1,

    autoplay: true,

    autoplaySpeed: 2500,

    dots: false,

    arrows: true,

    responsive: [

        {

            breakpoint: 1400,

            settings: {

                slidesToShow: 3,

                slidesToScroll: 1,

                infinite: true,

            }

        },

        {

            breakpoint: 1024,

            settings: {

                slidesToShow: 2,

                slidesToScroll: 1,

                infinite: true,

            }

        },

        {

            breakpoint: 768,

            settings: {

                slidesToShow: 2,

                slidesToScroll: 1,

                autoplay: true

            }

        },

        {

            breakpoint: 600,

            settings: {

                slidesToShow: 1,

                slidesToScroll: 1,

                autoplay: true

            }

        },

        {

            breakpoint: 480,

            settings: {

                slidesToShow: 1,

                slidesToScroll: 1,

                autoplay: true

            }

        }

    ],

    prevArrow: '<button class="slide-arrow prev-arrow"></button>',

    nextArrow: '<button class="slide-arrow next-arrow"></button>'

});



////new add js End===============================================>


/////casaphotos_slider

$('.casaphotos_slider').slick({
    speed: 300,
    infinite: true,
    slidesToShow: 6,
    slidesToScroll: 1,
    dots: false,
    autoplay: true,
    autoplaySpeed: 3000,
    arrows: true,
    responsive: [
        {
            breakpoint: 1400,
            settings: {
                slidesToShow: 5,
                slidesToScroll: 1,
            }
        },

        {
            breakpoint: 1024,
            settings: {
                slidesToShow: 4,
                slidesToScroll: 1,
            }
        },

        {
            breakpoint: 768,
            settings: {
                slidesToShow: 3,
                slidesToScroll: 1,
                autoplay: true,
            }
        },

        {
            breakpoint: 576,
            settings: {
                slidesToShow: 2,
                slidesToScroll: 1,
                autoplay: true,
            }
        },
    ],
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'
});


/////casavideo_slider

$('.casavideo_slider').slick({
    speed: 300,
    infinite: true,
    slidesToShow: 6,
    slidesToScroll: 1,
    dots: false,
    autoplay: true,
    autoplaySpeed: 3000,
    arrows: true,
    responsive: [
        {
            breakpoint: 1400,
            settings: {
                slidesToShow: 5,
                slidesToScroll: 1,
            }
        },

        {
            breakpoint: 1024,
            settings: {
                slidesToShow: 4,
                slidesToScroll: 1,
            }
        },

        {
            breakpoint: 768,
            settings: {
                slidesToShow: 3,
                slidesToScroll: 1,
                autoplay: true,
            }
        },

        {
            breakpoint: 576,
            settings: {
                slidesToShow: 2,
                slidesToScroll: 1,
                autoplay: true,
            }
        },
    ],
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'
});


/////tournamentcard_slider

$('.tournamentcard_slider').slick({

    speed: 300,

    infinite: true,

    slidesToShow: 4,

    slidesToScroll: 1,

    dots: false,

    autoplay: true,

    autoplaySpeed: 3000,

    arrows: true,

    responsive: [

        {

            breakpoint: 1024,

            settings: {

                slidesToShow: 3,

                slidesToScroll: 1,

            }

        },

        {

            breakpoint: 841,

            settings: {

                slidesToShow: 2,

                slidesToScroll: 1,

                autoplay: false,

            }

        },

        {

            breakpoint: 576,

            settings: {

                slidesToShow: 1,

                slidesToScroll: 1,

                autoplay: false,

            }

        },

        {

            breakpoint: 300,

            settings: {

                slidesToShow: 1,

                slidesToScroll: 1,

                autoplay: false,

            }

        }

    ],

    prevArrow: '<button class="slide-arrow prev-arrow"></button>',

    nextArrow: '<button class="slide-arrow next-arrow"></button>'

});

// tournament slider
$('.tourSlick').slick({
    infinite: false,
    slidesToShow: 1,
    slidesToScroll: 1,
    dots: false,
    arrows: true,
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'

});

// badge slider
$('.badgeSlick').slick({
    infinite: false,
    slidesToShow: 1,
    slidesToScroll: 1,
    dots: false,
    arrows: true,
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'

});

// badge slider
$('.reviewSlick').slick({
    infinite: false,
    slidesToShow: 1,
    slidesToScroll: 1,
    dots: false,
    arrows: true,
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'

});



/////bookVenues_slider

$('.bookVenues_slider').slick({

    speed: 300,

    infinite: true,

    slidesToShow: 4,

    slidesToScroll: 1,

    dots: false,

    autoplay: true,

    autoplaySpeed: 2000,

    arrows: true,

    responsive: [

        {

            breakpoint: 1024,

            settings: {

                slidesToShow: 3,

                slidesToScroll: 1,

            }

        },

        {

            breakpoint: 600,

            settings: {

                slidesToShow: 2,

                slidesToScroll: 1,

            }

        },

        {

            breakpoint: 480,

            settings: {

                slidesToShow: 1,

                slidesToScroll: 1,

            }

        }

    ],

    prevArrow: '<button class="slide-arrow prev-arrow"></button>',

    nextArrow: '<button class="slide-arrow next-arrow"></button>'

});





/////discoverGames_slider

$('.discoverGames_slider').slick({

    speed: 300,

    infinite: true,

    slidesToShow: 4,

    slidesToScroll: 1,

    dots: false,

    autoplay: true,

    autoplaySpeed: 2000,

    arrows: true,

    responsive: [

        {

            breakpoint: 1024,

            settings: {

                slidesToShow: 3,

                slidesToScroll: 1,

            }

        },

        {

            breakpoint: 600,

            settings: {

                slidesToShow: 2,

                slidesToScroll: 1,

            }

        },

        {

            breakpoint: 480,

            settings: {

                slidesToShow: 1,

                slidesToScroll: 1,

            }

        }

    ],

    prevArrow: '<button class="slide-arrow prev-arrow"></button>',

    nextArrow: '<button class="slide-arrow next-arrow"></button>'

});



/////advertisement_slider

$('.advertisement_slider').slick({

    speed: 300,

    infinite: true,

    slidesToShow: 4,

    slidesToScroll: 1,

    dots: false,

    autoplay: true,

    autoplaySpeed: 2000,

    arrows: true,

    responsive: [

        {

            breakpoint: 1400,

            settings: {

                slidesToShow: 3,

                slidesToScroll: 1,

            }

        },

        {

            breakpoint: 992,

            settings: {

                slidesToShow: 2,

                slidesToScroll: 1,

            }

        },

        {

            breakpoint: 576,

            settings: {

                slidesToShow: 1,

                slidesToScroll: 1,

            }

        },

        {

            breakpoint: 480,

            settings: {

                slidesToShow: 1,

                slidesToScroll: 1,

            }

        }

    ],

    prevArrow: '<button class="slide-arrow prev-arrow"></button>',

    nextArrow: '<button class="slide-arrow next-arrow"></button>'

});





/////innerbanner_slider

$('.innerbanner_slider').slick({

    speed: 300,

    infinite: true,

    slidesToShow: 1,

    slidesToScroll: 1,

    dots: false,

    autoplay: true,

    autoplaySpeed: 2500,

    arrows: false,

    prevArrow: '<button class="slide-arrow prev-arrow"></button>',

    nextArrow: '<button class="slide-arrow next-arrow"></button>'

});


///store slider
$('.sttoreproduct_slider').slick({
    speed: 300,
    infinite: true,
    slidesToShow: 4,
    slidesToScroll: 1,
    dots: false,
    autoplay: true,
    autoplaySpeed: 2000,
    arrows: true,
    responsive: [

        {
            breakpoint: 1400,
            settings: {
                slidesToShow: 3,
                slidesToScroll: 1,
            }
        },

        {
            breakpoint: 992,
            settings: {
                slidesToShow: 2,
                slidesToScroll: 1,
            }
        },

        {
            breakpoint: 576,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
            }
        },

        {
            breakpoint: 480,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
            }
        }
    ],
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'
});


///popularSports js

$(document).ready(function () {

    $('.clickme a').click(function () {

        $('.clickme a').removeClass('activelink');

        $(this).addClass('activelink');

        var tagid = $(this).data('tag');

        $('.list').removeClass('active').addClass('hide');

        $('#' + tagid).addClass('active').removeClass('hide');

    });

});





//playground_sec count function

document.addEventListener("DOMContentLoaded", function () {

    const counters = document.querySelectorAll(".count");

    let started = false; // Prevent re-triggering



    function startCounting() {

        counters.forEach(counter => {

            const target = +counter.getAttribute("data-target");

            const speed = 50; // lower = faster

            let count = 0;



            const updateCount = () => {

                count += Math.ceil(target / 50);

                if (count < target) {

                    counter.textContent = count;

                    setTimeout(updateCount, speed);

                } else {

                    counter.textContent = target;

                }

            };

            updateCount();

        });

    }



    function onScroll() {

        const section = document.querySelector(".playground_sec");

        const rect = section.getBoundingClientRect();

        if (!started && rect.top < window.innerHeight && rect.bottom >= 0) {

            started = true;

            startCounting();

            window.removeEventListener("scroll", onScroll);

        }

    }



    window.addEventListener("scroll", onScroll);

});









///event width js

$(document).ready(function () {

    if ($('.event-card').length == 1) {

        $('.event-card').css('max-width', '100%');

    }

    if ($('.event-card').length == 2) {

        $('.event-card').css('max-width', '49%');

    }

});





////Custom Resister Modal

$(document).ready(function () {

    $('#resisterEvent').click(function () {

        $('#resisterEvent_add').addClass('open');

    });

    $('#resisterEvent_close').click(function () {

        $('#resisterEvent_add').removeClass('open');

    });

});





////Player pay Amount Modal

$(document).ready(function () {

    // $('.PayAmountModal_open').click(function () {

    $(document).on('click', '.PayAmountModal_open', function () {

        let gameId = $(this).attr('data-id')

        let userId = $(this).attr('data-user-id')

        $('.PayAmountModal').addClass('open');

        $('.game_dt').val(gameId);

        if (gameId !== "") {

            $.ajax({

                url: "https://casainfotech.com/staging/api/fetch_payment.php",

                type: "POST",

                data: { game_id: gameId, user_id: userId },

                success: function (response) {

                    var data = JSON.parse(response);

                    if (data.success) {

                        $("#tot_amnt strong").text(data.currency + ' ' + data.total_amount);

                        $("#due strong").text(data.currency + ' ' + data.due);

                        $("#due_amt").val(data.due);

                        $("#Amount").val(data.due)

                    } else {

                        $("#tot_amnt strong").text("$0");

                        $("#due strong").text("$0");

                        $("#due_amt").val(0);

                        $("#Amount").val(0)



                    }

                },

                error: function () {

                    alert("Error fetching payment details.");

                }

            });

        } else {

            $("#tot_amnt strong").text("$0");

            $("#due strong").text("$0");

        }



    });

    $('.PayAmountModal_close').click(function () {

        $('.PayAmountModal').removeClass('open');

    });

});





////Custom Player payment Modal

$(document).ready(function () {

    // $('.playPaymentModal_open').click(function () {

    $(document).on('click', '.playPaymentModal_open', function () {

        // $('.playPaymentModal').addClass('open');



        var player_id = $(this).attr('data-id');

        let year = $("#hpyear").val()

        let month = $("#hpmonth").val()

        console.log(player_id);

        $.ajax({

            url: "https://casainfotech.com/staging/api/view_player_pay.php",

            type: "POST",

            data: {

                user_id: player_id, year: year, month: month,

            },

            success: function (response) {

                // console.log(response)

                $('.playPaymentModal').addClass('open');

                $(".patmentTb").html(response);

            },

            error: function () {

                alert("Error saving data.");

            }

        });

    });

    $('.playPaymentModal_close').click(function () {

        $('.playPaymentModal').removeClass('open');

        var year = $("#hpyear").val()

        var month = $("#hpmonth").val()

        var host = $("#hhost").val()

        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/filter_host_payment.php',

            data: {

                year: year,

                month: month,

                player: host,

                type: 'filter'

            },

            success: function (response) {

                $(".host_payment").html(response)

            }

        })

    });

});





////Custom match view Modal

$(document).ready(function () {

    // $('.matchviewmodal_open').click(function () {

    //     $('.matchviewmodal').addClass('open');

    // });

    $('.matchviewmodal_close').click(function () {

        $('.matchviewmodal').removeClass('open');

    });

});





////number add for add to cart

$(document).ready(function () {

    $('.minus').click(function () {

        var $input = $(this).parent().find('input');

        var count = parseInt($input.val()) - 1;

        count = count < 1 ? 1 : count;

        $input.val(count);

        $input.change();

        return false;

    });

    $('.plus').click(function () {

        var $input = $(this).parent().find('input');

        $input.val(parseInt($input.val()) + 1);

        $input.change();

        return false;

    });

});



///thankyou modal

$(document).ready(function () {

    $('.thankyouModal_click').click(function () {

        $('.thankyouModal').addClass('open');

    });

    $('.thankyouModal_close').click(function () {

        $('.thankyouModal').removeClass('open');

    });

});



///product details page

const imgs = document.querySelectorAll('.img-select a');

const imgBtns = [...imgs];

let imgId = 1;

imgBtns.forEach((imgItem) => {

    imgItem.addEventListener('click', (event) => {

        event.preventDefault();

        imgId = imgItem.dataset.id;

        slideImage();

    });

});

function slideImage() {

    const displayWidth = document.querySelector('.img-showcase img:first-child').clientWidth;

    document.querySelector('.img-showcase').style.transform = `translateX(${- (imgId - 1) * displayWidth}px)`;

}

window.addEventListener('resize', slideImage);



////Host Game Edit Modal

$(document).ready(function () {

    $(document).on('click', '.discoverGames_card .edit_btn', function () {
        let id = $(this).attr('data-id');
        // console.log(id);
        let data = $('#data_' + id).val()
        let parseData = JSON.parse(data)
        console.log(parseData)
        $('#EVENT_IDD').val(id);
        $('#host-namee').val(parseData.HOST_NAME);
        $('#eventCountryy').val(parseData.EVENT_COUNTRY);
        $('#eventProvincee').val(parseData.EVENT_PROVINCE);
        $('#eventCityy').val(parseData.EVENT_CITY);
        $('#eventCurrencyy').val(parseData.EVENT_CURRENCY);
        $('#eventVenuee').val(parseData.EVENT_VENUE);
        $('#eventCategoryy').val(parseData.EVENT_CATEGORY);
        $('#genderCategoryy').val(parseData.GENDER_CATEGORY);
        $('#genderSkillLevell').val(parseData.GENDER_SKILL_LEVEL);
        $('#eventTypee').val(parseData.EVENT_TYPE);
        $('#eventDatee').val(parseData.EVENT_DATE);
        setTimeout(() => {
            let eventTimeFormatted = parseData.EVENT_TIME.slice(0, 5);
            let fromTime = parseData.EVENT_TIME.slice(0, 5); // HH:mm
            let toTime = parseData.TO_TIME.slice(0, 5);    // HH:mm
            $('#eventTimee').val(fromTime).change();
            $('#toTimee').val(toTime).change();
            // $('#eventTimee').val(eventTimeFormatted).change();
            // $('#toTimee').val(parseData.TO_TIME.slice(0, 5)).change();
            $('#freezeDatee').val(parseData.CANCEL_DATE);
            $('#freezeTimee').val(parseData.CANCEL_TIME.slice(0, 5)).change();

            const [fromH, fromM] = fromTime.split(':').map(Number);
            const [toH, toM] = toTime.split(':').map(Number);

            let diffMinutes =
                (toH * 60 + toM) - (fromH * 60 + fromM);

            if (diffMinutes < 0) diffMinutes += 1440;

            // ⭐ decimal hours
            let decimalHours = (diffMinutes / 60).toFixed(2);
            console.log("decimalHours", decimalHours)
            $("#hours").val(decimalHours)

        }, 1000);
        $('#eventCostt').val(parseData.EVENT_COST);
        $('#eventDiscountt').val(parseData.EVENT_DISCOUNT);
        $('#eventDescriptionn').val(parseData.EVENT_DESCRIPTION);
        $('#eventMessagee').val(parseData.EVENT_MESSAGE);
        $('#statusevent').val(parseData.STATUS);
        $('#evnt_id').val(parseData.ID);
        $('#facilitycost').val(parseData.FACILITY_COST);
        $('#accessoriesCost').val(parseData.ACCESSORIES_COST);
        $('#snackscost').val(parseData.SNACKS_COST);
        $('#eventtotalCostt').val(parseData.TOTAL_EVENT_COST);
        $('#eventtotalplayerCostt').val(parseData.TOTAL_PLAYER_COST);
        $('#profitloss').val(parseData.PROFIT_LOSS);
        $('#birdieUsed').val(parseData.BIRDIE_USED);
        $('#clubClost').val(parseData.CLUB_COST);
        $('#nobirdieUsed').val(parseData.BIRDIE_PRICE);
        $('#courtconfirmed').val(parseData.COURTS_CONFIRMED);
        if (parseData.AUTOMATION === 'Y') {
            $('#autoConfirm').prop('checked', true);
        } else {
            $('#autoConfirm').prop('checked', false);
        }
        $('.hostgameupdate_modal').addClass('open');
        $.ajax({
            url: 'api/get_player_cost.php', // ðŸ”„ Adjust this URL to your PHP script
            method: 'POST',
            data: { event_id: id },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#eventtotalplayerCostt').val(response.total_player_cost);
                    $('#facilitycostperhour').val(response.court_cost);
                    $('#playersConfirmed').val(response.confirmed_players);
                    $('#playersJoined').val(response.joined_players);
                } else {
                    console.warn('Failed to load player cost:', response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    });

    $('.hostgameupdate_modal .customModal_close').click(function () {

        $('.hostgameupdate_modal').removeClass('open');

    });



    $('.hostgameupdate_modal #save_btn').click(function () {
        let formData = {
            id: $('#evnt_id').val(),
            host_name: $('#host-namee').val(),
            event_country: $('#eventCountryy').val(),
            event_province: $('#eventProvincee').val(),
            event_city: $('#eventCityy').val(),
            event_currency: $('#eventCurrencyy').val(),
            event_venue: $('#eventVenuee').val(),
            event_category: $('#eventCategoryy').val(),
            gender_category: $('#genderCategoryy').val(),
            gender_skill_level: $('#genderSkillLevell').val(),
            event_type: $('#eventTypee').val(),
            event_date: $('#eventDatee').val(),
            event_time: $('#eventTimee').val(),
            to_time: $('#toTimee').val(),
            freeze_date: $('#freezeDatee').val(),
            freeze_time: $('#freezeTimee').val(),
            event_cost: $('#eventCostt').val(),
            event_discount: $('#eventDiscountt').val(),
            event_description: $('#eventDescriptionn').val(),
            event_message: $('#eventMessagee').val(),
            status: $('#statusevent').val(),
            facilitycost: $('#facilitycost').val(),
            accessoriesCost: $('#accessoriesCost').val(),
            snackscost: $('#snackscost').val(),
            eventtotalCostt: $('#eventtotalCostt').val(),
            eventtotalplayerCostt: $('#eventtotalplayerCostt').val(),
            profitloss: $('#profitloss').val(),
            birdieUsed: $('#birdieUsed').val(),
            clubClost: $('#clubClost').val(),
            updatePlayerPrice: $('#updatePlayerPrice').is(':checked') ? 'Y' : 'N'
        };

        // console.log("formData",formData)
        // return

        // Simple validation
        let errors = [];
        if (!formData.host_name) errors.push("Host name is required.");
        if (!formData.event_country) errors.push("Event country is required.");
        if (!formData.event_province) errors.push("Event province is required.");
        if (!formData.event_city) errors.push("Event city is required.");
        if (!formData.event_currency) errors.push("Event currency is required.");
        if (!formData.event_venue) errors.push("Event venue is required.");
        if (!formData.event_category) errors.push("Event category is required.");
        if (!formData.gender_category) errors.push("Gender category is required.");
        if (!formData.gender_skill_level) errors.push("Gender skill level is required.");
        if (!formData.event_type) errors.push("Event type is required.");
        if (!formData.event_date) errors.push("Event date is required.");
        if (!formData.event_time) errors.push("Event time is required.");
        if (!formData.to_time) errors.push("To time is required.");
        if (!formData.freeze_date) errors.push("Freeze date is required.");
        if (!formData.freeze_time) errors.push("Freeze time is required.");
        if (!formData.event_cost || isNaN(formData.event_cost)) errors.push("Event cost must be a valid number.");
        // if (!formData.event_discount errors.push("Event Court is required.");
        if (!formData.event_description) errors.push("Event description is required.");
        if (!formData.event_message) errors.push("Event message is required.");
        if (!formData.status) errors.push("Status is required.");

        if (errors.length > 0) {
            alert(errors.join('\n'));
            return; // Do not proceed with the AJAX request
        }

        $.ajax({
            url: '../../api/update_event.php',
            type: 'POST',
            data: formData,
            success: function (response) {
                response = JSON.parse(response);
                if (response.success) {
                    alert('Event updated successfully!');
                    $('.hostgameupdate_modal').removeClass('open');
                    // location.reload();
                    // window.location.href='host-dashboard.php';
                    var year = $("#year").val()
                    var month = $("#month").val()
                    $.ajax({
                        type: "POST",
                        url: '../../api/filter_schedule.php',
                        data: {
                            year: year,
                            month: month,
                            type: 'filter'
                        },
                        success: function (response) {
                            $(".hostWrapper").html(response)
                            initPopovers();

                        }
                    })
                } else {
                    alert('Failed to update event. ' + response.error);
                }
            },
            error: function () {
                alert('An error occurred while updating the event.');
            },
        });
    });



    $('.hostgameupdate_modal #save_btn_subs').click(function () {

        let formData = {

            id: $('#event_sub_id').val(),

            host_name: $('#host-nameee').val(),

            event_country: $('#eventCountryyy').val(),

            event_province: $('#eventProvinceee').val(),

            event_city: $('#eventCityyy').val(),

            event_currency: $('#eventCurrencyyy').val(),

            event_venue: $('#eventVenueee').val(),

            event_category: $('#eventCategoryyy').val(),

            gender_category: $('#genderCategoryyy').val(),

            gender_skill_level: $('#genderSkillLevelll').val(),

            event_type: $('#eventTypeee').val(),

            event_date: $('#eventDateee').val(),

            event_time: $('#eventTimeee').val(),

            to_time: $('#toTimeee').val(),

            freeze_date: $('#freezeDateee').val(),

            freeze_time: $('#freezeTimeee').val(),

            event_cost: $('#eventCosttt').val(),

            event_discount: $('#eventDiscounttt').val(),

            event_description: $('#eventDescriptionnn').val(),

            event_message: $('#eventMessageee').val(),

            status: $('#statuseventt').val(),

            facilitycost: $('#facilitycostt').val(),

            accessoriesCost: $('#accessoriesCostt').val(),

            snackscost: $('#snackscostt').val(),

            eventtotalCostt: $('#eventtotalCosttt').val(),

            eventtotalplayerCostt: $('#eventtotalplayerCosttt').val(),

            profitloss: $('#profitlosss').val(),

        };



        // console.log("formData",formData)

        // return



        // Simple validation

        //     let errors = [];

        // if (!formData.host_name) errors.push("Host name is required.");

        // if (!formData.event_country) errors.push("Event country is required.");

        // if (!formData.event_province) errors.push("Event province is required.");

        // if (!formData.event_city) errors.push("Event city is required.");

        // if (!formData.event_currency) errors.push("Event currency is required.");

        // if (!formData.event_venue) errors.push("Event venue is required.");

        // if (!formData.event_category) errors.push("Event category is required.");

        // if (!formData.gender_category) errors.push("Gender category is required.");

        // if (!formData.gender_skill_level) errors.push("Gender skill level is required.");

        // if (!formData.event_type) errors.push("Event type is required.");

        // if (!formData.event_date) errors.push("Event date is required.");

        // if (!formData.event_time) errors.push("Event time is required.");

        // if (!formData.to_time) errors.push("To time is required.");

        // if (!formData.freeze_date) errors.push("Freeze date is required.");

        // if (!formData.freeze_time) errors.push("Freeze time is required.");

        // if (!formData.event_cost || isNaN(formData.event_cost)) errors.push("Event cost must be a valid number.");

        // // if (!formData.event_discount errors.push("Event Court is required.");

        // if (!formData.event_description) errors.push("Event description is required.");

        // if (!formData.event_message) errors.push("Event message is required.");

        // if (!formData.status) errors.push("Status is required.");



        //     if (errors.length > 0) {

        //         alert(errors.join('\n'));

        //         return; // Do not proceed with the AJAX request

        //     }



        $.ajax({

            url: 'https://casainfotech.com/staging/api/create_event_sub.php',

            type: 'POST',

            data: formData,

            success: function (response) {

                response = JSON.parse(response);

                if (response.success) {

                    alert('Event Created successfully!');

                    $('.hostgameupdate_modal').removeClass('open');

                    // location.reload();

                    window.location.href = 'host-dashboard.php';

                } else {

                    alert('Failed to update event. ' + response.error);

                }

            },

            error: function () {

                alert('An error occurred while updating the event.');

            },

        });

    });



    $(document).on('click', '.discoverGames_card_sub #create_btn', function () {

        let id = $(this).attr('data-id');

        let data = $('#data_' + id).val();

        let parseData = JSON.parse(data);



        // Reset ID to empty for "create" flow

        let formData = {

            id: id,

            host_name: parseData.HOST_NAME,

            event_country: parseData.EVENT_COUNTRY,

            event_province: parseData.EVENT_PROVINCE,

            event_city: parseData.EVENT_CITY,

            event_currency: parseData.EVENT_CURRENCY,

            event_venue: parseData.EVENT_VENUE,

            event_category: parseData.EVENT_CATEGORY,

            gender_category: parseData.GENDER_CATEGORY,

            gender_skill_level: parseData.GENDER_SKILL_LEVEL,

            event_type: parseData.EVENT_TYPE,

            event_time: parseData.EVENT_TIME.slice(0, 5),

            to_time: parseData.TO_TIME.slice(0, 5),

            freeze_time: parseData.CANCEL_TIME.slice(0, 5),

            event_cost: parseData.EVENT_COST,

            event_discount: parseData.EVENT_DISCOUNT,

            event_description: parseData.EVENT_DESCRIPTION,

            event_message: parseData.EVENT_MESSAGE,

            status: parseData.STATUS,

            facilitycost: parseData.FACILITY_COST,

            accessoriesCost: parseData.ACCESSORIES_COST,

            snackscost: parseData.SNACKS_COST,

            eventtotalCostt: parseData.TOTAL_EVENT_COST,

            eventtotalplayerCostt: parseData.TOTAL_PLAYER_COST,

            profitloss: parseData.PROFIT_LOSS

        };



        function getNextDayOfWeek(dayIndex) {

            let date = new Date();

            let currentDay = date.getDay(); // Sunday=0 ... Saturday=6

            let diff = (dayIndex + 7 - currentDay) % 7;

            if (diff === 0) diff = 7; // always next, not today

            date.setDate(date.getDate() + diff);

            return date;

        }



        // Map parseData.DAY to JS weekday index

        let dayName = parseData.DAY ? parseData.DAY.toUpperCase().trim() : "";

        let dayIndexMap = {

            "SUNDAY": 0,

            "MONDAY": 1,

            "TUESDAY": 2,

            "WEDNESDAY": 3,

            "THURSDAY": 4,

            "FRIDAY": 5,

            "SATURDAY": 6

        };



        if (dayIndexMap.hasOwnProperty(dayName)) {

            let eventDateObj = getNextDayOfWeek(dayIndexMap[dayName]);

            let eventDate = eventDateObj.toISOString().split("T")[0];



            // By default freeze date = event date

            let freezeDateObj = new Date(eventDateObj);



            // If it's Saturday, subtract one day

            if (dayIndexMap[dayName] === 6) {

                freezeDateObj.setDate(freezeDateObj.getDate() - 1);

            }



            let freezeDate = freezeDateObj.toISOString().split("T")[0];



            formData.event_date = eventDate;

            formData.freeze_date = freezeDate;

        }

        // console.log("formData",formData)

        // return false



        // Send AJAX request to create event

        $.ajax({

            url: 'https://casainfotech.com/staging/api/create_event_sub.php',

            type: 'POST',

            data: formData,

            success: function (response) {

                response = JSON.parse(response);

                if (response.success) {

                    alert('Event Created successfully!');

                    // return false

                    window.location.href = 'host-dashboard.php';

                } else {

                    alert('Failed to create event: ' + response.error);

                }

            },

            error: function () {

                alert('An error occurred while creating the event.');

            }

        });

    });







    $('.hostgameupdate_modal #copy_btn').click(function () {

        let formData = {

            id: $('#evnt_id').val(),

            host_name: $('#host-namee').val(),

            event_country: $('#eventCountryy').val(),

            event_province: $('#eventProvincee').val(),

            event_city: $('#eventCityy').val(),

            event_currency: $('#eventCurrencyy').val(),

            event_venue: $('#eventVenuee').val(),

            event_category: $('#eventCategoryy').val(),

            gender_category: $('#genderCategoryy').val(),

            gender_skill_level: $('#genderSkillLevell').val(),

            event_type: $('#eventTypee').val(),

            event_date: $('#eventDatee').val(),

            event_time: $('#eventTimee').val(),

            to_time: $('#toTimee').val(),

            freeze_date: $('#freezeDatee').val(),

            freeze_time: $('#freezeTimee').val(),

            event_cost: $('#eventCostt').val(),

            event_discount: $('#eventDiscountt').val(),

            event_description: $('#eventDescriptionn').val(),

            event_message: $('#eventMessagee').val(),

            status: $('#statusevent').val(),

        };



        // console.log("formData",formData)

        // return



        // Simple validation

        let errors = [];

        if (!formData.host_name) errors.push("Host name is required.");

        if (!formData.event_country) errors.push("Event country is required.");

        if (!formData.event_province) errors.push("Event province is required.");

        if (!formData.event_city) errors.push("Event city is required.");

        if (!formData.event_currency) errors.push("Event currency is required.");

        if (!formData.event_venue) errors.push("Event venue is required.");

        if (!formData.event_category) errors.push("Event category is required.");

        if (!formData.gender_category) errors.push("Gender category is required.");

        if (!formData.gender_skill_level) errors.push("Gender skill level is required.");

        if (!formData.event_type) errors.push("Event type is required.");

        if (!formData.event_date) errors.push("Event date is required.");

        if (!formData.event_time) errors.push("Event time is required.");

        if (!formData.to_time) errors.push("To time is required.");

        if (!formData.freeze_date) errors.push("Freeze date is required.");

        if (!formData.freeze_time) errors.push("Freeze time is required.");

        if (!formData.event_cost || isNaN(formData.event_cost)) errors.push("Event cost must be a valid number.");

        if (formData.event_discount && isNaN(formData.event_discount)) errors.push("Event discount must be a valid number.");

        if (!formData.event_description) errors.push("Event description is required.");

        if (!formData.event_message) errors.push("Event message is required.");

        if (!formData.status) errors.push("Status is required.");



        if (errors.length > 0) {

            alert(errors.join('\n'));

            return; // Do not proceed with the AJAX request

        }



        $.ajax({

            url: 'https://casainfotech.com/staging/api/copy_event.php',

            type: 'POST',

            data: formData,

            success: function (response) {

                response = JSON.parse(response);

                console.log(response)

                if (response.success) {

                    alert('Event Created successfully!');

                    $('.hostgameupdate_modal').removeClass('open');

                    // location.reload();

                    // window.location.href='host-dashboard.php';

                    var year = $("#year").val()

                    var month = $("#month").val()

                    $.ajax({

                        type: "POST",

                        url: 'https://casainfotech.com/staging/api/filter_schedule.php',

                        data: {

                            year: year,

                            month: month,

                            type: 'filter'

                        },

                        success: function (response) {

                            $(".hostWrapper").html(response)

                            initPopovers();



                        }

                    })

                } else {

                    alert('Failed to update event. ' + response.error);

                }

            },

            error: function () {

                alert('An error occurred while updating the event.');

            },

        });

    });

    $(document).on('click', '.discoverGames_card .delete_btn', function () {

        // $('.discoverGames_card .delete_btn').click(function () {

        if (confirm("Are you sure you want to delete this event?")) {

            let eventId = $(this).attr('data-id');



            $.ajax({

                url: 'https://casainfotech.com/staging/api/delete_event.php',

                type: 'POST',

                data: { id: eventId },

                success: function (response) {

                    response = JSON.parse(response);

                    if (response.success) {

                        alert('Event deleted successfully.');

                        location.reload(); // Refresh the page to reflect changes

                    } else {

                        alert('Failed to delete event. ' + response.error);

                    }

                },

                error: function () {

                    alert('An error occurred while deleting the event.');

                },

            });

        }

    })



    $(document).on('click', '.plyerGame_wrapper .player_cards .actionJC', function () {

        console.log("player_cards")



        if (confirm("Are you sure you want to join this event?")) {

            let id = $(this).attr('data-id');

            // console.log(id);

            let data = $('#data_' + id).val()

            let parseData = JSON.parse(data)

            let userId = $("#user_" + id).val()



            // console.log(parseData,userId)



            $.ajax({

                url: 'https://casainfotech.com/staging/api/join_event.php',

                type: 'POST',

                data: { ...parseData, USER_ID: userId },

                success: function (response) {

                    location.reload();

                    // response = JSON.parse(response);

                    // console.log(response)

                    // if (response.status=='success') {

                    //     $(".plyerGame_wrapper").html(response.outputHTML)

                    //     bindClickEventt()

                    // } else {

                    //     alert('Failed to delete event. ' + response.error);

                    // }

                },

                error: function () {

                    alert('An error occurred while joining the event.');

                },

            });

        }

    });



    /*$(document).on('click', '.discoverGames_card_sub .join_btn', function () {
    
        console.log("player_cards")
    
        
    
        if (confirm("Are you sure you want to join this event?")) {
    
            let id = $(this).attr('data-id');
    
            // console.log(id);
    
            let data = $('#data_'+id).val()
    
            let parseData = JSON.parse(data)
    
            // console.log(parseData)
    
            let userId = $("#user_"+id).val()
    
            
    
            console.log(parseData,userId)
    
    
    
            $.ajax({
    
                url: 'https://casainfotech.com/staging/api/join_event_default.php',
    
                type: 'POST',
    
                data: { ...parseData,USER_ID:userId },
    
                success: function (response) {
    
                    // response = JSON.parse(response);
    
                    // console.log(response)
    
                    // if (response.status=='success') {
    
                        // $(".hostWrapper").html(response)
    
                        $(".outputHtml").html(response)
    
                    //     bindClickEventt()
    
                    // } else {
    
                    //     alert('Failed to delete event. ' + response.error);
    
                    // }
    
                },
    
                error: function () {
    
                    alert('An error occurred while joining the event.');
    
                },
    
            });
    
        }
    
    });*/



    $(document).on('click', '.discoverGames_card_sub .join_btn', function () {

        let $btn = $(this);

        let eventId = $btn.attr('data-id');

        let isJoined = $btn.attr('data-joined') === '1';



        let confirmMsg = isJoined

            ? "Are you sure you want to cancel your participation?"

            : "Are you sure you want to join this event?";



        if (!confirm(confirmMsg)) return;



        let data = $('#data_' + eventId).val();

        let parseData = JSON.parse(data);

        let userId = $("#user_" + eventId).val();



        let url = isJoined

            ? 'https://casainfotech.com/staging/api/cancel_event_default.php'

            : 'https://casainfotech.com/staging/api/join_event_default.php';



        $.ajax({

            url: url,

            type: 'POST',

            data: { ...parseData, USER_ID: userId },

            success: function (response) {

                $(".outputHtml").html(response);



                // Toggle button style + text

                if (isJoined) {

                    $btn.removeClass('btn-danger').addClass('btn-primary');

                    $btn.text('Join');

                    $btn.attr('data-joined', '0');

                } else {

                    $btn.removeClass('btn-primary').addClass('btn-danger');

                    $btn.text('Cancel');

                    $btn.attr('data-joined', '1');

                }

            },

            error: function () {

                alert('An error occurred while processing your request.');

            },

        });

    });







    $(document).on('click', '.plyerGame_wrapper .player_cards .actionC', function () {

        // console.log("player_cards")



        if (confirm("Are you sure you want to cancel this event?")) {

            let id = $(this).attr('data-id');

            // console.log(id);

            let data = $('#data_' + id).val()

            let parseData = JSON.parse(data)

            // console.log(parseData)

            let userId = $("#user_" + id).val()



            // console.log(parseData,userId)



            $.ajax({

                url: 'https://casainfotech.com/staging/api/cancel_event.php',

                type: 'POST',

                data: { ...parseData, USER_ID: userId },

                success: function (response) {

                    response = JSON.parse(response);

                    alert(response.message)

                    location.reload();

                    // response = JSON.parse(response);

                    // console.log(response)

                    // if (response.status=='success') {

                    //     $(".plyerGame_wrapper").html(response.outputHTML)

                    //     bindClickEventt()

                    // } else {

                    //     alert('Failed to delete event. ' + response.error);

                    // }

                },

                error: function () {

                    alert('An error occurred while joining the event.');

                },

            });

        }

    });





    $(document).on('click', '.plyerGame_wrapper .player_cards .actionAC', function () {

        console.log("player_cards")



        if (confirm("Are you sure you want to join this event?")) {

            let id = $(this).attr('data-id');

            // console.log(id);

            let data = $('#data_' + id).val()

            let parseData = JSON.parse(data)

            // console.log(parseData)

            let userId = $("#user_" + id).val()



            // console.log(parseData,userId)



            $.ajax({

                url: 'https://casainfotech.com/staging/api/update_inviteJoin.php',

                type: 'POST',

                data: { ...parseData, USER_ID: userId },

                success: function (response) {

                    response = JSON.parse(response);

                    if (response.status == 'success') {

                        $(".plyerGame_wrapper").html(response.outputHTML)

                        bindClickEvent();

                    } else {

                        alert('Failed to delete event. ' + response.error);

                    }

                },

                error: function () {

                    alert('An error occurred while deleting the event.');

                },

            });

        }

    });



    function bindClickEvent() {

        $(document).on('click', '.plyerGame_wrapper .player_cards .actionAC', function () {

            console.log("player_cards");

            // You can call the same AJAX function again here if needed

        });

    }



    function bindClickEventt() {

        $(document).on('click', '.plyerGame_wrapper .player_cards .actionJC', function () {

            console.log("player_cards");

            // You can call the same AJAX function again here if needed

        });

    }









    $("#eventFormm").submit(function (e) {

        e.preventDefault(); // Prevent default form submission



        var formData = $(this).serialize(); // Serialize form data



        $.ajax({

            type: "POST",

            url: "https://casainfotech.com/staging/api/new-game-host.php", // Ensure this file handles AJAX requests

            data: formData,

            dataType: "json",

            success: function (response) {

                if (response.success) {

                    alert("New Event Created Successfully.");

                    // $("#eventForm")[0].reset(); // Reset form after success

                    location.reload();

                } else {

                    alert("Error: " + response.message);

                }

            },

            error: function () {

                alert("Something went wrong! Please try again.");

            }

        });

    });



    // Populate time dropdowns with 30-min intervals

    function populateTimeDropdown(selectId) {

        let select = document.getElementById(selectId);

        for (let h = 0; h < 24; h++) {

            for (let m = 0; m < 60; m += 30) {

                let hour = h < 10 ? "0" + h : h;

                let minute = m < 10 ? "0" + m : m;

                let timeValue = `${hour}:${minute}`;

                let option = new Option(timeValue, timeValue);

                select.appendChild(option);

            }

        }

    }



    populateTimeDropdown("eventTime");

    populateTimeDropdown("toTime");

    populateTimeDropdown("freezeTime");

    populateTimeDropdown("eventTimee");

    populateTimeDropdown("toTimee");

    populateTimeDropdown("freezeTimee");



});



////Host Game View and invite Modal

$(document).ready(function () {

    $(document).on('click', '.discoverGames_card .view_btn', function () {

        // $('.discoverGames_card .view_btn').click(function () {

        let id = $(this).attr('data-id');

        // console.log(id);

        let data = $('#data_' + id).val()

        let parseData = JSON.parse(data)

        // console.log(parseData)

        $("#sgenderCategoryy").val(parseData?.GENDER_CATEGORY);

        $("#sgenderSkillLevell").val(parseData?.GENDER_SKILL_LEVEL);

        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/view_joined_invited.php',

            data: parseData,

            success: function (response) {

                $("#playerList").html(response)

                $('.hostgameview_modal').addClass('open');

            }

        })

    });





    $(document).on('click', '.discoverGames_card .joined_btn', function () {

        // $('.discoverGames_card .view_btn').click(function () {

        let id = $(this).attr('data-id');

        // console.log(id);

        let data = $('#data_' + id).val()

        let parseData = JSON.parse(data)

        // console.log(parseData)

        $("#sgenderCategoryy").val(parseData?.GENDER_CATEGORY);

        $("#sgenderSkillLevell").val(parseData?.GENDER_SKILL_LEVEL);

        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/view_joined_all.php',

            data: parseData,

            success: function (response) {

                $(".hostgameviewjoined_modal #playerList_joined").html(response)

                $('.hostgameviewjoined_modal').addClass('open');

            }

        })

    });



    $(document).on('click', '.discoverGames_card_sub .joined_btn', function () {

        // $('.discoverGames_card .view_btn').click(function () {

        let id = $(this).attr('data-id');

        // console.log(id);

        let data = $('#data_' + id).val()

        let parseData = JSON.parse(data)

        // console.log(parseData)

        $("#sgenderCategoryy").val(parseData?.GENDER_CATEGORY);

        $("#sgenderSkillLevell").val(parseData?.GENDER_SKILL_LEVEL);

        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/view_joined_all_default.php',

            data: parseData,

            success: function (response) {

                $(".hostgameviewjoined_modal #playerList_joined").html(response)

                $('.hostgameviewjoined_modal').addClass('open');

            }

        })

    });



    $(document).on('click', '.hostgameviewjoined_modal .customModal_close', function () {

        // $('.discoverGames_card .view_btn').click(function () {



        $('.hostgameviewjoined_modal').removeClass('open');



    });



    $(document).on('click', '.plyerGame_wrapper .view_btnn', function () {

        let id = $(this).attr('data-id');

        // console.log(id);

        let data = $('#data_' + id).val()

        let parseData = JSON.parse(data)

        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/view_player.php',

            data: parseData,

            success: function (response) {

                $("#playerList").html(response)

                $('.hostgameview_modal').addClass('open');

            }

        })

    });



    $(document).on('click', '.action-btn-pay', function () {

        let user_id = $(this).attr('data-id');

        let game_id = $(this).attr('data-game-id');

        let amount = $(this).attr('data-pay-amount');



        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/same_paymentByhost.php',

            data: {

                user_id: user_id,

                game_id: game_id,

                amount: amount

            },

            success: function (response) {

                alert('Payment successfull');

                $(".playergameview_modal #playerList").html(response)



            }

        })

    });

    $('.hostgameview_modal .customModal_close').click(function () {

        console.log('abc')

        $('.hostgameview_modal').removeClass('open');

        // setTimeout(function () {

        //         window.location.href = baseUrl + '/host-dashboard.php';

        //     }, 200);



    });













    $('.playergameview_modal .customModal_close').click(function () {

        $('.playergameview_modal').removeClass('open');

    });



    $(document).on('click', '.invite-checkbox', function (e) {

        // console.log('a')

        e.stopPropagation();  // Prevent parent from being triggered

        // e.preventDefault();   // Prevent checkbox from toggling

        const userId = $(this).attr('data-user-id');

        const gameId = $(this).attr('data-game-id');

        const hostId = $(this).attr('data-host-id');

        const currency = $(this).attr('data-currency-id');

        const price = $(this).attr('data-price-id');

        const dtype = $(this).attr('data-type');



        // console.log('userId',userId)



        if ($(this).is(':checked')) {

            // Send AJAX request to save data

            $.ajax({

                url: 'https://casainfotech.com/staging/api/save_invitation.php', // PHP script to handle the request

                type: 'POST',

                data: {

                    type: 'checked',

                    user_id: userId,

                    game_id: gameId,

                    host_id: hostId,

                    currency: currency,

                    price: price,

                    dtype: dtype

                },

                success: function (response) {

                    // alert('Invitation sent successfully!');

                },

                error: function () {

                    alert('Error sending invitation. Please try again.');

                }

            });

        }

        else {

            $.ajax({

                url: 'https://casainfotech.com/staging/api/save_invitation.php', // PHP script to handle the request

                type: 'POST',

                data: {

                    user_id: userId,

                    game_id: gameId,

                    host_id: hostId,

                    currency: currency,

                    price: price,

                    type: 'unchecked',

                },

                success: function (response) {

                    // alert('Invitation sent successfully!');

                },

                error: function () {

                    alert('Error sending invitation. Please try again.');

                }

            });

        }

    });



    $(document).on('click', '.invite-checkboxx', function () {

        // console.log('a')

        //  const checkbox = $(this).find('.invite-checkbox');



        // if (checkbox.is(':disabled')) return;



        const userId = $(this).attr('data-user-id');

        const gameId = $(this).attr('data-game-id');

        const hostId = $(this).attr('data-host-id');

        const currency = $(this).attr('data-currency-id');

        const price = $(this).attr('data-price-id');

        const dtype = $(this).attr('data-type');



        // console.log('userId',userId)



        if ($(this).is(':checked')) {

            // Send AJAX request to save data

            $.ajax({

                url: 'https://casainfotech.com/staging/api/save_confirm.php', // PHP script to handle the request

                type: 'POST',

                data: {

                    type: 'checked',

                    user_id: userId,

                    game_id: gameId,

                    host_id: hostId,

                    currency: currency,

                    price: price,

                    dtype: dtype

                },

                success: function (response) {

                    // alert('Invitation sent successfully!');

                },

                error: function () {

                    alert('Error sending invitation. Please try again.');

                }

            });

        }

        else {

            $.ajax({

                url: 'https://casainfotech.com/staging/api/save_confirm.php', // PHP script to handle the request

                type: 'POST',

                data: {

                    user_id: userId,

                    game_id: gameId,

                    host_id: hostId,

                    currency: currency,

                    price: price,

                    type: 'unchecked',

                },

                success: function (response) {

                    // alert('Invitation sent successfully!');

                },

                error: function () {

                    alert('Error sending invitation. Please try again.');

                }

            });

        }

    });



    $(document).on("click", ".save-price", function () {

        var userId = $(this).data("user-id");

        var gameId = $(this).data("game-id");

        var price = $("#price_" + userId).val();



        $.ajax({

            url: "https://casainfotech.com/staging/api/update_price.php", // Backend PHP script

            type: "POST",

            data: {

                user_id: userId,

                game_id: gameId,

                price: price

            },

            success: function (response) {

                if (response.trim() === "success") {

                    alert("Price updated successfully!");

                } else {

                    alert("Failed to update price!");

                }

            },

            error: function () {

                alert("Error in AJAX request.");

            }

        });

    });





    // $('#search').on('keyup', function() {



    //     let searchText = $(this).val().trim();

    //     let firstCheckbox = $("#playerList .invite-checkbox").first();



    //         let gameId = firstCheckbox.data("game-id");

    //         let hostId = firstCheckbox.data("host-id");



    //         $("#playdt").attr('data-game-id',gameId)

    //         $("#playdt").attr('data-host-id',hostId)



    //         let gameIdN =  $("#playdt").attr('data-game-id');

    //         let hostIdN = $("#playdt").attr('data-host-id');





    //     if (searchText.length >= 2) {

    //             $.ajax({

    //                 type: "POST",

    //                 url: "https://casainfotech.com/staging/api/search_result.php", // Replace with your actual API endpoint

    //                 data: { query: searchText,ID:gameIdN,HOST_ID:hostIdN },

    //                 success: function(response) {

    //                     $("#playerList").html(response);

    //                 }

    //             });

    //     } else if(searchText.length == 0){

    //          $.ajax({

    //                 type: "POST",

    //                 url: "https://casainfotech.com/staging/api/search_result.php", // Replace with your actual API endpoint

    //                 data: { query: "",ID:gameIdN,HOST_ID:hostIdN },

    //                 success: function(response) {

    //                     $("#playerList").html(response);

    //                 }

    //             });

    //     }

    // });

    function fetchPlayers() {

        let searchText = $('#search').val().trim();

        let gender = $('#sgenderCategoryy').val();

        let skillLevel = $('#sgenderSkillLevell').val();

        let firstCheckbox = $("#playerList .invite-checkbox").first();



        let gameId = firstCheckbox.data("game-id");

        let hostId = firstCheckbox.data("host-id");



        $("#playdt").attr('data-game-id', gameId);

        $("#playdt").attr('data-host-id', hostId);



        let gameIdN = $("#playdt").attr('data-game-id');

        let hostIdN = $("#playdt").attr('data-host-id');



        $.ajax({

            type: "POST",

            url: "https://casainfotech.com/staging/api/search_result.php",

            data: {

                query: searchText,

                ID: gameIdN,

                HOST_ID: hostIdN,

                gender: gender,

                skill_level: skillLevel

            },

            success: function (response) {

                $("#playerList").html(response);

            }

        });

    }



    // Triggers

    $('#search').on('keyup', function () {

        if ($(this).val().length >= 2 || $(this).val().length === 0) {

            fetchPlayers();

        }

    });



    $('#sgenderCategoryy, #sgenderSkillLevell').on('change', function () {

        fetchPlayers();

    });







    $(".save_payment").click(function () {

        var userId = $("#user_id").val(); // Assuming session user_id is set

        var gameId = $("select.form-control").val();

        var amount = $("#Amount").val();

        var paymentDate = $("#date").val();

        var paymentTime = $("#time").val();

        var paymentType = $("#paymentType").val();

        var details = $("#details").val();

        var message = $("#Message").val();

        var dueamt = $("#due_amt").val();

        var host = $("#payhost").val()

        var year = $("#payyear").val()

        var month = $("#paymonth").val()



        if (gameId == "" || amount == "" || paymentDate == "" || paymentTime == "" || paymentType == "") {

            alert("Please fill all required fields.");

            return;

        }



        if (Number(amount) > Number(dueamt)) {

            alert("Amount Exceeds Due Amount");

            return;

        }



        $.ajax({

            url: "https://casainfotech.com/staging/api/save_payment.php",

            type: "POST",

            data: {

                user_id: userId,

                game_id: gameId,

                amount: amount,

                payment_date: paymentDate,

                payment_time: paymentTime,

                payment_type: paymentType,

                details: details,

                message: message,

                year: year,

                month: month,

                host: host,

            },

            success: function (response) {

                console.log(response)

                // alert("Payment Detials Saved Successfully"); // Show success message



                $("#Amount").val('');

                $("#date").val('');

                $("#time").val('');

                $("#paymentType").val('');

                $("#details").val('');

                $("#Message").val('');

                $("select.form-control").val('');

                $("#due_amt").val('');

                $("#tot_amnt strong").text("$0");

                $("#due strong").text("$0");



                // Close the modal (if using Bootstrap modal)

                $('.PayAmountModal').removeClass('open');

                $(".custom_card .patmentTb").html(response);

            },

            error: function () {

                alert("Error saving data.");

            }

        });

    });



    $(".game_dt").change(function () {

        var gameId = $(this).val();

        var userId = $("#user_id").val(); // Get user ID from session

        // alert(userId)

        if (gameId !== "") {

            $.ajax({

                url: "https://casainfotech.com/staging/api/fetch_payment.php",

                type: "POST",

                data: { game_id: gameId, user_id: userId },

                success: function (response) {

                    var data = JSON.parse(response);

                    if (data.success) {

                        $("#tot_amnt strong").text(data.currency + ' ' + data.total_amount);

                        $("#due strong").text(data.currency + ' ' + data.due);

                        $("#due_amt").val(data.due);

                    } else {

                        $("#tot_amnt strong").text("$0");

                        $("#due strong").text("$0");

                        $("#due_amt").val(0);



                    }

                },

                error: function () {

                    alert("Error fetching payment details.");

                }

            });

        } else {

            $("#tot_amnt strong").text("$0");

            $("#due strong").text("$0");

        }

    });



    $(document).on('click', '.custom_card .patmentTb .paymentTab .view_btn', function () {

        let gameJoinId = $(this).attr('data-id');

        let userId = $(this).attr('data-user-id');

        // console.log(id);



        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/view_player_paymentHis.php',

            data: {

                user_id: userId,

                game_id: gameJoinId

            },

            success: function (response) {

                $(".hostgameview_modal #playerList").html(response)

                $('.hostgameview_modal').addClass('open');

            }

        })

    });



    $(document).on('click', '.matchviewmodal_open', function () {

        let id = $(this).attr('data-id');

        let user_id = $(this).attr('data-user-id');



        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/view_player_paylist.php',

            data: {

                game_id: id,

                user_id: user_id

            },

            success: function (response) {

                $(".hostgamevieww_modal #playerList").html(response)

                $('.hostgamevieww_modal').addClass('open');

            }

        })

    });





    $(document).on('click', '.hostgamevieww_modal .customModal_close', function () {



        var year = $("#com_year").val()

        var month = $("#com_month").val()

        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/com_filter_schedule.php',

            data: {

                year: year,

                month: month,

                type: 'filter'

            },

            success: function (response) {

                $("#comp_game").html(response)

                $('.hostgamevieww_modal').removeClass('open');

            }

        })

    });



    $(document).on('click', '.playerviewmodal_open', function () {

        let id = $(this).attr('data-id');

        let user_id = $(this).attr('data-user-id');



        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/view_game_player.php',

            data: {

                game_id: id,

                user_id: user_id

            },

            success: function (response) {

                $(".playergameview_modal #playerList").html(response)

                $('.playergameview_modal').addClass('open');

            }

        })

    });



    $(document).on('click', '.rollback_btn', function () {

        console.log('rollback');





        const eventId = $(this).attr('data-id');

        const userId = $(this).attr('data-user-id');



        if (!eventId || !userId) {

            alert('Missing event or user ID');

            return;

        }

        if (confirm('Do you want to proceed?')) {



            $.ajax({

                type: 'POST',

                url: 'https://casainfotech.com/staging/api/rollback_event.php',

                data: {

                    event_id: eventId,

                    host_id: userId,

                    action: 'rollback'

                },

                success: function (response) {

                    window.location.href = 'https://casainfotech.com/staging/host-dashboard.php';

                },

                error: function (xhr, status, error) {

                    console.error('AJAX error:', error);

                    alert('An error occurred while processing your request.');

                }

            });

        }

    });



    // $(document).on('click', '.hostgameview_modal #hostplaypay .playerList .action-btn', function () {

    $(document).on('click', '.action-btn', function () {

        // alert('a')

        let payid = $(this).attr('data-id');

        let status = $(this).attr('data-status');

        let game_id = $(this).attr('data-game-id');

        // console.log(id);

        console.log(status)

        // return false



        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/update_paymentHis.php',

            data: {

                payid: payid,

                status: status,

                game_id: game_id

            },

            success: function (response) {

                if (status == 'R') {

                    alert('Payment Rejected');

                }

                else {

                    alert('Payment Approved');

                }

                $(".hostgameview_modal #playerList").html(response)

                $('.hostgameview_modal').addClass('open');

            }

        })

    });



    $(document).on('click', '.approveBtnnn', function () {

        const game_id = $(this).data('id');

        const user_id = $(this).data('user');



        let year = $("#hpyear").val()

        let month = $("#hpmonth").val()



        $.post('api/payment_action.php', {

            action: 'approve',

            game_id,

            user_id,

            year: year, month: month

        }, function (res) {

            alert(res.message);

            $(".patmentTb").html(res.html);

        }, 'json');

    });



    $(document).on('click', '.rejectBtnnn', function () {

        const game_id = $(this).data('id');

        const user_id = $(this).data('user');



        let year = $("#hpyear").val()

        let month = $("#hpmonth").val()



        $.post('api/payment_action.php', {

            action: 'reject',

            game_id,

            user_id,

            year: year, month: month

        }, function (res) {

            alert(res.message);

            $(".patmentTb").html(res.html);

        }, 'json');

    });



    $(document).on('click', '.payBtnnn', function () {

        const game_id = $(this).data('id');

        const user_id = $(this).data('user');

        const amount = parseFloat($(this).data('due')); // ✅ Use due amount

        const payment_type = 'Interac'; // or 'Interac', or let user select from dropdown if needed



        let year = $("#hpyear").val()

        let month = $("#hpmonth").val()



        if (amount > 0) {

            $.post('api/payment_action.php', {

                action: 'pay',

                game_id,

                user_id,

                amount,

                payment_type,

                year: year, month: month

            }, function (res) {

                alert(res.message);

                $(".patmentTb").html(res.html);

            }, 'json');

        } else {

            alert("No due to pay.");

        }

    });



    $(document).on('click', '.rollbackBtnnn', function () {

        const gameId = $(this).data('id');

        const userId = $(this).data('user');

        const amount = $(this).data('amount');

        let year = $("#hpyear").val()

        let month = $("#hpmonth").val()



        if (confirm(`Are you sure you want to rollback payment of ${amount}?`)) {

            $.post('api/rollback_payment.php', {

                game_id: gameId,

                user_id: userId,

                year: year, month: month

            }, function (response) {

                $(".patmentTb").html(response);

                // Optionally refresh the table

            });

        }

    });









    $("#filter").click(function () {

        var year = $("#year").val()

        var month = $("#month").val()

        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/filter_schedule.php',

            data: {

                year: year,

                month: month,

                type: 'filter'

            },

            success: function (response) {

                $(".hostWrapper").html(response)

                initPopovers();



            }

        })

    })



    $("#hpfilter").click(function () {

        var year = $("#hpyear").val()

        var month = $("#hpmonth").val()

        var host = $("#hhost").val()

        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/filter_host_payment.php',

            data: {

                year: year,

                month: month,

                player: host,

                type: 'filter'

            },

            success: function (response) {

                $(".host_payment").html(response)

            }

        })

    })



    $("#reset").click(function () {

        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/filter_schedule.php',

            data: {

                type: 'reset'

            },

            success: function (response) {

                const now = new Date();

                const currentYear = now.getFullYear();

                const currentMonth = now.getMonth() + 1; // getMonth() returns 0–11



                // Set current year and month in dropdowns

                $("#year").val(currentYear);

                $("#month").val(currentMonth);

                // $("#year").val('');

                // $("#month").val('');

                $(".hostWrapper").html(response)

                initPopovers();



            }

        })

    })



    $("#com_filter").click(function () {

        var year = $("#com_year").val()

        var month = $("#com_month").val()

        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/com_filter_schedule.php',

            data: {

                year: year,

                month: month,

                type: 'filter'

            },

            success: function (response) {

                $("#comp_game").html(response)

            }

        })

    })



    $("#com_reset").click(function () {

        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/com_filter_schedule.php',

            data: {

                type: 'reset'

            },

            success: function (response) {

                const now = new Date();

                const currentYear = now.getFullYear();

                const currentMonth = now.getMonth() + 1; // getMonth() returns 0–11



                // Set current year and month in dropdowns

                $("#com_year").val(currentYear);

                $("#com_month").val(currentMonth);

                $("#comp_game").html(response)

            }

        })

    })





    $("#play_filter").click(function () {

        var year = $("#year").val()

        var month = $("#month").val()

        var host = $("#host").val()

        var event_category = $("#event_category").val()

        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/play_filter_schedule.php',

            data: {

                year: year,

                month: month,

                host: host,

                event_category: event_category,

                type: 'filter'

            },

            success: function (response) {

                $(".plyerGame_wrapper").html(response)

                initPopovers();



            }

        })

    })



    $("#play_reset").click(function () {

        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/play_filter_schedule.php',

            data: {

                type: 'reset'

            },

            success: function (response) {

                $(".plyerGame_wrapper").html(response)

                // $("#year").val('');

                // $("#month").val('');

                // $("#host").val(21);

                const now = new Date();

                const currentYear = now.getFullYear();

                const currentMonth = now.getMonth() + 1; // getMonth() returns 0–11



                // Set current year and month in dropdowns

                $("#year").val(currentYear);

                $("#month").val(currentMonth);

                $("#host").val("");

                $("#event_category").val("Badminton Game");

                initPopovers();



            }

        })

    })



    $("#play_com_filter").click(function () {

        var year = $("#comyear").val()

        var month = $("#commonth").val()

        var host = $("#host").val()



        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/player_filter_complete.php',

            data: {

                year: year,

                month: month,

                host: host,

                type: 'filter'

            },

            success: function (response) {

                $(".playCom").html(response)

            }

        })

    })



    $("#play_com_reset").click(function () {

        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/player_filter_complete.php',

            data: {

                type: 'reset'

            },

            success: function (response) {

                $(".playCom").html(response)

                // $("#comyear").val('');

                // $("#commonth").val('');

                // $("#host").val(21);

                const now = new Date();

                const currentYear = now.getFullYear();

                const currentMonth = now.getMonth() + 1; // getMonth() returns 0–11



                // Set current year and month in dropdowns

                $("#comyear").val(currentYear);

                $("#commonth").val(currentMonth);

                $("#host").val("");

            }

        })

    })



    $("#pay_com_filter").click(function () {

        var host = $("#payhost").val()

        var year = $("#payyear").val()

        var month = $("#paymonth").val()

        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/pay_filter_complete.php',

            data: {

                year: year,

                month: month,

                host: host,

                type: 'filter'

            },

            success: function (response) {

                $(".patmentTb").html(response)

            }

        })

    })



    $("#pay_com_reset").click(function () {

        $.ajax({

            type: "POST",

            url: 'https://casainfotech.com/staging/api/pay_filter_complete.php',

            data: {

                type: 'reset'

            },

            success: function (response) {

                $(".patmentTb").html(response)

                const now = new Date();

                const currentYear = now.getFullYear();

                const currentMonth = now.getMonth() + 1; // getMonth() returns 0–11



                // Set current year and month in dropdowns

                $("#payyear").val(currentYear);

                $("#paymonth").val(currentMonth);

                $("#host").val("");

            }

        })

    })



});





///user profile account

let menuToggle = document.querySelector('.menu-toggle');

let navigation = document.querySelector('.navigation');

menuToggle.onclick = function () {

    navigation.classList.toggle('active');

};





///calender- Date-Picker start

const daysContainer = document.getElementById("daysContainer");

const prevBtn = document.getElementById("prevBtn");

const nextBtn = document.getElementById("nextBtn");

const monthYear = document.getElementById("monthYear");

const dateInput = document.getElementById("dateInput");

const calendar = document.getElementById("calendar");



let currentDate = new Date();

let selectedDate = null;



function handleDayClick(day) {

    selectedDate = new Date(

        currentDate.getFullYear(),

        currentDate.getMonth(),

        day

    );

    dateInput.value = selectedDate.toLocaleDateString("en-US");

    calendar.style.display = "none";

    renderCalendar();

}



function createDayElement(day) {

    const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);

    const dayElement = document.createElement("div");

    dayElement.classList.add("day");



    if (date.toDateString() === new Date().toDateString()) {

        dayElement.classList.add("current");

    }

    if (selectedDate && date.toDateString() === selectedDate.toDateString()) {

        dayElement.classList.add("selected");

    }



    dayElement.textContent = day;

    dayElement.addEventListener("click", () => {

        handleDayClick(day);

    });

    daysContainer.appendChild(dayElement);

}



function renderCalendar() {

    daysContainer.innerHTML = "";

    const firstDay = new Date(

        currentDate.getFullYear(),

        currentDate.getMonth(),

        1

    );

    const lastDay = new Date(

        currentDate.getFullYear(),

        currentDate.getMonth() + 1,

        0

    );



    monthYear.textContent = `${currentDate.toLocaleString("default", {

        month: "long"

    })} ${currentDate.getFullYear()}`;



    for (let day = 1; day <= lastDay.getDate(); day++) {

        createDayElement(day);

    }

}



// var facilityInput = document.getElementById('facilitycost');

// var accessoriesInput = document.getElementById('accessoriesCost');

// var snacksInput = document.getElementById('snackscost');

// var totalInput = document.getElementById('eventtotalCostt');

// var playerCostInput = document.getElementById('eventtotalplayerCostt');

// var profitLossInput = document.getElementById('profitloss');

// var eventCostt = document.getElementById('eventCostt')



// // Function to calculate total and profit/loss

// function calculateTotal() {

//     var facility = parseFloat(facilityInput.value) || 0;

//     var accessories = parseFloat(accessoriesInput.value) || 0;

//     var snacks = parseFloat(snacksInput.value) || 0;



//     var total = facility + accessories + snacks;

//     totalInput.value = total.toFixed(2); // 2 decimal places



//     var playerCost = parseFloat(playerCostInput.value) || 0;

//     var profitLoss = playerCost - total;

//     profitLossInput.value = profitLoss.toFixed(2);



//     if (profitLoss < 0) {

//     profitLossInput.style.color = 'red';

//     } else {

//         profitLossInput.style.color = 'green';

//     }

// }



// // Attach event listeners

// facilityInput.addEventListener('input', calculateTotal);

// accessoriesInput.addEventListener('input', calculateTotal);

// snacksInput.addEventListener('input', calculateTotal);

// playerCostInput.addEventListener('input', calculateTotal); // also update if player cost changes/





var facilityInput = document.getElementById('facilitycost');
var accessoriesInput = document.getElementById('accessoriesCost');
var snacksInput = document.getElementById('snackscost');
var clubCostInput = document.getElementById('clubClost');

var totalInput = document.getElementById('eventtotalCostt');
var playersConfirmedInput = document.getElementById('playersConfirmed');
var eventCostt = document.getElementById('eventCostt');
var playerCostInput = document.getElementById('eventtotalplayerCostt');
var profitLossInput = document.getElementById('profitloss');

var fromTimeInput = document.getElementById('eventTimee');
var toTimeInput = document.getElementById('toTimee');
var hoursInput = document.getElementById('hours');
var facilityPerHourInput = document.getElementById('facilitycostperhour');

var birdieUsedInput = document.getElementById('birdieUsed');
var birdiePriceInput = document.getElementById('nobirdieUsed');
var courtsConfirmed = document.getElementById('courtconfirmed');

// console.log("courtsConfirmed",courtsConfirmed)

/* ==========================
   TOTAL + COST / PLAYER + PROFIT / LOSS
========================== */
function calculateTotal() {
    var facility = parseFloat(facilityInput.value) || 0;
    var accessories = parseFloat(accessoriesInput.value) || 0;
    var snacks = parseFloat(snacksInput.value) || 0;
    var clubCost = parseFloat(clubCostInput.value) || 0;

    // 🔥 RAW TOTAL
    var rawTotal = facility + accessories + snacks + clubCost;

    // 🔥 FINAL EVENT TOTAL (CEIL ONLY HERE)
    var total = Math.ceil(rawTotal);
    totalInput.value = total;

    // 🔥 COST PER PLAYER (CEIL FINAL)
    var playersConfirmed = parseInt(playersConfirmedInput.value) || 0;
    if (playersConfirmed > 0) {
        eventCostt.value = Math.ceil(total / playersConfirmed);
    }
    else {
        eventCostt.value = Math.ceil(total / 4);
    }

    // 🔥 PROFIT / LOSS (CEIL FINAL)
    var playerTotal = parseFloat(playerCostInput.value) || 0;
    var profitLoss = Math.ceil(playerTotal - total);
    profitLossInput.value = profitLoss;
    profitLossInput.style.color = profitLoss < 0 ? 'red' : 'green';
}

/* ==========================
   TIME → HOURS → FACILITY COST
========================== */
function calculateHoursAndFacilityCost() {
    if (!fromTimeInput.value || !toTimeInput.value) return;

    let [fh, fm] = fromTimeInput.value.split(':').map(Number);
    let [th, tm] = toTimeInput.value.split(':').map(Number);

    let diffMinutes = (th * 60 + tm) - (fh * 60 + fm);
    if (diffMinutes < 0) diffMinutes += 1440;

    // 🔥 KEEP DECIMAL HOURS
    let hoursDecimal = diffMinutes / 60;
    hoursInput.value = hoursDecimal.toFixed(2);

    let costPerHour = parseFloat(facilityPerHourInput.value) || 0;
    const courts = parseInt(courtsConfirmed.value, 10) || 1;

    // 🔥 FACILITY COST (NO CEIL HERE)
    facilityInput.value = (hoursDecimal * costPerHour * courts).toFixed(2);

    calculateTotal();
}

/* ==========================
   BIRDIE → ACCESSORIES COST
========================== */
function calculateAccessoriesCost() {
    let birdieUsed = parseFloat(birdieUsedInput.value) || 0;
    let birdiePrice = parseFloat(birdiePriceInput.value) || 0;

    // 🔥 NO CEIL HERE
    accessoriesInput.value = (birdieUsed * birdiePrice).toFixed(2);
    calculateTotal();
}

/* ==========================
   EVENT LISTENERS
========================== */
['change', 'input'].forEach(evt => {
    fromTimeInput.addEventListener(evt, calculateHoursAndFacilityCost);
    toTimeInput.addEventListener(evt, calculateHoursAndFacilityCost);
});
facilityPerHourInput.addEventListener('input', calculateHoursAndFacilityCost);

birdieUsedInput.addEventListener('input', calculateAccessoriesCost);
birdiePriceInput.addEventListener('input', calculateAccessoriesCost);

facilityInput.addEventListener('input', calculateTotal);
accessoriesInput.addEventListener('input', calculateTotal);
snacksInput.addEventListener('input', calculateTotal);
clubCostInput.addEventListener('input', calculateTotal);
playerCostInput.addEventListener('input', calculateTotal);
playersConfirmedInput.addEventListener('input', calculateTotal);

/* ==========================
   INITIAL CALC
========================== */
calculateTotal();



//for automation

// let originalTotalEventCost = null;

// let originalEventCost = null;

// let originalProfitLoss = null;





// snacksInput.addEventListener('input', function () {



//     // calculateTotal(); // UI update first



//     var snacks = parseFloat(snacksInput.value) || 0;



//      if (!snacks || snacks <= 0) {



//         if (originalTotalEventCost !== null) {

//             totalInput.value = originalTotalEventCost;

//             eventCostt.value = originalEventCost;

//             profitLossInput.value = originalProfitLoss;

//         }



//         return;

//     }



//     // 📌 Save original values ONCE

//     if (originalTotalEventCost === null) {

//         originalTotalEventCost = parseFloat(totalInput.value) || 0;

//         originalEventCost = parseFloat(eventCostt.value) || 0;

//         originalProfitLoss = parseFloat(profitLossInput.value) || 0;

//     }



//     var eventIdInput = document.getElementById('EVENT_IDD');



//     var eventId = eventIdInput.value;



//     // console.log("eventId",eventId)

//     // return



//     if (!eventId) {

//         console.error('EVENT_ID missing');

//         return;

//     }



//     fetch('api/get-num-player-joined.php', {

//         method: 'POST',

//         headers: { 'Content-Type': 'application/json' },

//         body: JSON.stringify({

//             event_id: eventId,

//         })

//     })

//     .then(res => res.json())

//     .then(data => {

//         if (!data.success) {

//             alert(data.message);

//             return;

//         }



//         // totalInput.value = data.total_event_cost.toFixed(2);

//         // playerCostInput.value = data.event_cost.toFixed(2);

//         // profitLossInput.value = data.profit_loss.toFixed(2);

//         // profitLossInput.style.color = data.profit_loss < 0 ? 'red' : 'green';

//         var totalEventCost = parseFloat(originalTotalEventCost)+parseFloat(snacks);

//         totalInput.value = totalEventCost;

//         eventCostt.value = (totalEventCost/data.no_player_joined).toFixed(2);

//         profitLossInput.value = playerCostInput.value - totalEventCost



//     });

// });





prevBtn.addEventListener("click", () => {

    currentDate.setMonth(currentDate.getMonth() + 1);

    renderCalendar();

});



nextBtn.addEventListener("click", () => {

    currentDate.setMonth(currentDate.getMonth() + 1);

    renderCalendar();

});



dateInput.addEventListener("click", () => {

    calendar.style.display = "block";

    positionCalendar();

});



document.addEventListener("click", (event) => {

    if (!dateInput.contains(event.target) && !calendar.contains(event.target)) {

        calendar.style.display = "none";

    }

});



function positionCalendar() {

    const inputRect = dateInput.getBoundingClientRect();

    calendar.style.top = inputRect.bottom + "px";

    calendar.style.left = inputRect.left + "px";

}



window.addEventListener("resize", positionCalendar);



renderCalendar();



///calender- Date-Picker End

// Get input elements



document.addEventListener('DOMContentLoaded', function () {

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));

    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {

        return new bootstrap.Tooltip(tooltipTriggerEl);

    });

});



function initPopovers() {

    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');



    popoverTriggerList.forEach(triggerEl => {

        // If popover already exists, destroy it first

        if (bootstrap.Popover.getInstance(triggerEl)) {

            bootstrap.Popover.getInstance(triggerEl).dispose();

        }



        const popover = new bootstrap.Popover(triggerEl);



        // Close other popovers when one is clicked

        triggerEl.addEventListener('click', function () {

            popoverTriggerList.forEach(otherEl => {

                if (otherEl !== triggerEl) {

                    bootstrap.Popover.getInstance(otherEl)?.hide();

                }

            });

        });

    });

}



// document.addEventListener('DOMContentLoaded', function () {



//     const toTimeEl = document.getElementById('toTimee');



//     if (!toTimeEl) return;



//     toTimeEl.addEventListener('change', function () {

//         recalculateCost();

//     });



// });





// function recalculateCost() {



//     const eventTime = document.getElementById('eventTimee')?.value;

//     const toTime    = document.getElementById('toTimee')?.value;

//     const eventId   = document.getElementById('EVENT_IDD')?.value;



//     if (!eventId || !eventTime || !toTime) return;



//     fetch('api/recalculate_event_cost.php', {

//         method: 'POST',

//         headers: { 'Content-Type': 'application/json' },

//         body: JSON.stringify({

//             event_id: eventId,

//             event_time: eventTime,

//             to_time: toTime

//         })

//     })

//     .then(res => res.json())

//     .then(data => {



//         console.log('Recalc response:', data);



//         if (!data.success) return;



//         setText('eventtotalCostt', data.total_event_cost);

//         setText('eventCostt', data.event_cost);

//         setText('eventtotalplayerCostt', data.total_player_cost);

//         setText('profitloss', data.profit_loss);

//     })

//     .catch(err => console.error(err));

// }



function setText(id, value) {

    const el = document.getElementById(id);

    if (!el) return;



    if (el.tagName === 'INPUT' || el.tagName === 'SELECT') {

        el.value = value;

    } else {

        el.innerText = value;

    }

}









