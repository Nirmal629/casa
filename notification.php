<!-----Header------>
<?php include "includes/inner-header.php"; ?>

<!-----notification-sec-------->
<section class="notification_sec bothSide_gap">
    <div class="cust_container">

        <h2 class="heading">My Notification</h2>

        <div class="row notification-container">
            <!-- <h2 class="text-center">My Notifications</h2> -->
            <p class="dismiss text-end"><a id="dismiss-all" href="#">Dimiss All</a></p>
            <div class="card notification-card notification-invitation">
                <div class="card-body">
                    <table>
                        <tr>
                            <td style="width:70%">
                                <div class="card-title">Anurag invited you to join '<b>Casa</b>' Game</div>
                            </td>
                            <td style="width:30%">
                                <a href="#" class="btn btn-primary">View</a>
                                <a href="#" class="btn btn-danger dismiss-notification">Dismiss</a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card notification-card notification-warning">
                <div class="card-body">
                    <table>
                        <tr>
                            <td style="width:70%">
                                <div class="card-title">your payment is '<b>pending</b>' please clear your due payment</div>
                            </td>
                            <td style="width:30%">
                                <a href="#" class="btn btn-primary">View</a>
                                <a href="#" class="btn btn-danger dismiss-notification">Dismiss</a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card notification-card notification-danger">
                <div class="card-body">
                    <table>
                        <tr>
                            <td style="width:70%">
                                <div class="card-title">Today game '<b>cancelled</b>' ....any information call anurag</div>
                            </td>
                            <td style="width:30%">
                                <a href="#" class="btn btn-primary">View</a>
                                <a href="#" class="btn btn-danger dismiss-notification">Dismiss</a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card notification-card notification-reminder">
                <div class="card-body">
                    <table>
                        <tr>
                            <td style="width:70%">
                                <div class="card-title">You have <b>2</b> upcoming payment(s) this week</div>
                            </td>
                            <td style="width:30%">
                                <a href="#" class="btn btn-primary">View</a>
                                <a href="#" class="btn btn-danger dismiss-notification">Dismiss</a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>


        </div>
    </div>
</section>


<!------footer------>
<?php include "includes/footer.php"; ?>

<script>
    const dismissAll = document.getElementById('dismiss-all');
    const dismissBtns = Array.from(document.querySelectorAll('.dismiss-notification'));

    const notificationCards = document.querySelectorAll('.notification-card');

    dismissBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault;
            var parent = e.target.parentElement.parentElement.parentElement.parentElement.parentElement.parentElement;
            parent.classList.add('display-none');
        })
    });

    dismissAll.addEventListener('click', function(e) {
        e.preventDefault;
        notificationCards.forEach(card => {
            card.classList.add('display-none');
        });
        const row = document.querySelector('.notification-container');
        const message = document.createElement('h4');
        message.classList.add('text-center');
        message.innerHTML = 'All caught up!';
        row.appendChild(message);
    })
</script>