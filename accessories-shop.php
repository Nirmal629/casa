<!-----Header------>
<?php include "includes/header.php"; ?>
<style>
    .wrapper {
        width: 100%;
        height: 80vh;
        opacity: 0.7;
        background: linear-gradient(-55deg, transparent 25%, #16181E 25%, #16181E 75%, transparent 75%, transparent 100%);
        transition: all 0.5s cubic-bezier(0.67, 0, 0.3, 1) 1s;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
    }

    .content {
        padding: 1rem;
        color: rgba(255, 255, 255, 1);
    }

    .content h1 {
        font-size: 3rem;
        color: rgba(255, 255, 255, 1);
    }

    .content p {
        font-size: 1.1rem;
        color: rgba(255, 255, 255, 1);
    }

    .content a {
        color: rgba(255, 255, 255, 1);
        display: inline-block;
        padding: 2.1% 4%;
        overflow: hidden;
        border-radius: 0;
        text-decoration: none;

    }

    .btn1 {
        border: 1px solid rgba(255, 255, 255, 1);
        transition: .2s ease-in-out;
    }

    .btn1:hover {
        border: 1px solid rgba(255, 255, 255, 1);
        background-color: rgba(255, 255, 255, 1);
        color: black;
        transition: .2s ease-in-out;
    }

    .btn2 {
        margin-left: 3%;
        background: linear-gradient(to right, #252AFF 0%, #25FFED 100%);
        border-image: linear-gradient(to bottom right, #252AFF 0%, #25FFED 100%);
        border-image-slice: 1;
        border-width: 1px;
        border-style: solid;
        transition: .2s ease-in-out;
    }

    .btn2:hover {
        background: none;
        border-image: linear-gradient(to bottom right, #252AFF 0%, #25FFED 100%);
        border-image-slice: 1;
        border-width: 1px;
        border-style: solid;
        transition: .2s ease-in-out;
    }

    @media screen and (max-width: 355px) {
        .content {
            padding: 1rem 1.4rem;
        }

        .content h1 {
            font-size: 2.5rem;
        }

        .wrapper {
            background: linear-gradient(-210deg, transparent 0%, #16181E 0%, #16181E 70%, transparent 50%, transparent 100%);
            transition: all 0.5s cubic-bezier(0.67, 0, 0.3, 1) 1s;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            text-align: left;
        }
    }

    @media screen and (min-width: 356px) and (max-width: 650px) {
        .content {
            padding: 1rem 1.4rem;
        }

        .wrapper {
            background: linear-gradient(-210deg, transparent 0%, #16181E 0%, #16181E 70%, transparent 50%, transparent 100%);
            transition: all 0.5s cubic-bezier(0.67, 0, 0.3, 1) 1s;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            text-align: left;
        }
    }
</style>


<!--------Coming-soon------->
<section class="comingsoon_sec">
    <div class="cust_container">
        <div class="wrapper">
            <div class="content">
                <h1>We're Coming Soon</h1>
                <p>Perfect and awesome to present your future product or service.<br>Hooking audience attention is all in the opener.</p>
                <a href="index.php" class="btn2">Back to Home</a>
            </div>
        </div>
    </div>
</section>





<!------footer------>
<?php include "includes/footer.php"; ?>