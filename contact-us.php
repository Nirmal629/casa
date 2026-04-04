<!-- SweetAlert CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<section class="contactUs_sec bothSide_gap" id="contactUsId">
    <div class="cust_container">
        <div class="row mainwrap">
            <div class="col-md-6 col-12" data-aos="zoom-in" data-aos-duration="1500">
                <div class="left_wrap" style="background-image: url(assets/images/contact-bg.png);">
                    <div class="contact_details">
                        <div>
                            <h2 class="heading white">Contact <span>Us</span></h2>
                            <p class="desc white">Thank you for your interest in Casainfotech Pvt. Ltd. We're excited to hear from you and discuss...</p>
                        </div>
                        <ul class="box_wrap">
                            <li><a href="#"><i class="fa-solid fa-phone-volume"></i><span>+1 (437) 981-0512</span></a></li>
                            <li><a href="#"><i class="fa-solid fa-envelope"></i><span>casaclubtoronto@gmail.com</span></a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-12" data-aos="zoom-in" data-aos-duration="1500">
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById("contactForm").addEventListener("submit", function(e) {
    e.preventDefault(); // Stop default form submission

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
            document.getElementById("contactForm").reset();
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
</script>


