<?php
$isEmbedded = defined('HOME_PAGE_INCLUDED');
if (!$isEmbedded) {
    include __DIR__ . "/../../layouts/header/header.php";
}
?>
<!-- SweetAlert CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
@keyframes contactZoomIn {
    0% {
        opacity: 0;
        transform: scale(0.96);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}
.contactUs_sec {
    animation: contactZoomIn 0.6s ease-out forwards;
    opacity: 1 !important;
    visibility: visible !important;
}
.contactUs_sec .left_wrap, 
.contactUs_sec .right_wrap {
    opacity: 1 !important;
    visibility: visible !important;
}
</style>

<section class="contactUs_sec bothSide_gap" id="contactUsId">
    <div class="cust_container">
        <div class="row mainwrap">
            <div class="col-md-6 col-12">
                <div class="left_wrap" style="background-image: url('assets/images/contact-bg.png');">
                    <div class="contact_details">
                        <div>
                            <h2 class="heading white">Contact <span>Us</span></h2>
                            <p class="desc white">Thank you for your interest in casa-games.com &minus; We're excited to hear from you and discuss...</p>
                        </div>
                        <ul class="box_wrap">
                            <li><a href="mailto:info.casagames@gmail.com"><i class="fa-solid fa-envelope"></i><span>info.casagames@gmail.com</span></a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-12">
                <div class="right_wrap">
                    <h2 class="heading white">Send us a <span>Message</span></h2>
                    <form id="contactForm">
                        <div class="form-group">
                            <label for="yourName" class="text-white">Your Name :</label>
                            <input type="text" class="form-control" id="yourName" name="name" placeholder="Enter your name" required>
                        </div>
                        <div class="form-group">
                            <label for="emailAddress" class="text-white">Email address :</label>
                            <input type="email" class="form-control" id="emailAddress" name="email" placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <label for="phoneNumber" class="text-white">Phone Number :</label>
                            <input type="text" class="form-control" id="phoneNumber" name="phone" placeholder="Enter phone number" required>
                        </div>
                        <div class="form-group">
                            <label for="Entermessage" class="text-white">Message :</label>
                            <textarea class="form-control" id="Entermessage" name="message" rows="3" placeholder="Message....." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Submit Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const contactForm = document.getElementById("contactForm");
    if (contactForm) {
        contactForm.addEventListener("submit", function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch("contact-handler.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Message Sent!',
                        text: data.message,
                    });
                    contactForm.reset();
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Saved but Email Failed!',
                        text: data.message,
                    });
                }
            })
            .catch(error => {
                console.error("Error:", error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Something went wrong. Please try again later.',
                });
            });
        });
    }
});
</script>

<?php
if (!$isEmbedded) {
    include __DIR__ . "/../../layouts/footer/footer.php";
}
?>
