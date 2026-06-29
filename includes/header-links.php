    <meta charset="UTF-8">

    <meta name="description" content="">

    <meta name="keywords" content="">

    <meta name="author" content="">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>Casa | Play</title>



    <!----favicon------>

    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon_io/apple-touch-icon.png">

    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon_io/favicon-32x32.png">

    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon_io/favicon-16x16.png">

    <link rel="manifest" href="assets/favicon_io/site.webmanifest">



    <!---font-awesome/6----->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />



    <!----glightbox-css---->

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />



    <!------AOS------->

    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">



    <!-----Google-fonts-------->

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Figtree:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">



    <!-- Slick slider link-->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.css" integrity="sha512-yHknP1/AwR+yx26cB1y0cjvQUMvEa2PFzt1c9LlS4pRQ5NOTZFWbhBig+X9G9eYW/8m0/4OXNx8pxJ6z57x0dw==" crossorigin="anonymous" referrerpolicy="no-referrer" />



    <!----bootstrap link---->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">



    <!-----css file link----->

    <link rel="stylesheet" href="assets/css/tournament.css?v=1.9.45" type="text/css">

    <link rel="stylesheet" href="assets/css/style.css?v=1.2.27" type="text/css">

    <?php
    $docRoot = $_SERVER['DOCUMENT_ROOT'];
    $projectRoot = realpath(__DIR__ . '/..'); 
    $base_dir = str_replace(str_replace('\\', '/', $docRoot), '', str_replace('\\', '/', $projectRoot)) . '/';
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $base_dir;
    ?>
    <script>
        const BASE_URL = '<?php echo $base_url; ?>';
    </script>