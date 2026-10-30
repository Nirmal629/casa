<meta charset="UTF-8">
<meta name="description" content="Casa Games & Tournaments Platform">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars(\App\Helpers\Session::csrfToken()) ?>">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="theme-color" content="#081b3c">
<meta property="og:title" content="Casa | Play">
<meta property="og:description" content="Casa Games & Tournaments Platform">
<meta property="og:type" content="website">
<title>Casa | Play</title>

<?php
// Asset prefix for static files. Using an absolute root path `/` for the router.
$assetPrefix = '/';

// Smart Cache-Busting for Local CSS Files
$tournCssFile = __DIR__ . '/../../../../assets/css/tournament.css';
$styleCssFile = __DIR__ . '/../../../../assets/css/style.css';
$dropCssFile  = __DIR__ . '/../../../../assets/css/casa-dropdown.css';

$tournCssVer = file_exists($tournCssFile) ? filemtime($tournCssFile) : '1.0';
$styleCssVer = file_exists($styleCssFile) ? filemtime($styleCssFile) : '1.0';
$dropCssVer  = file_exists($dropCssFile) ? filemtime($dropCssFile) : '1.0';

// Absolute URL Calculation
$docRootNormalized = strtolower(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''));
$projectRootRaw = realpath(__DIR__ . '/../../../..');
$projectRootNormalized = strtolower(str_replace('\\', '/', $projectRootRaw ?: ''));

$base_dir = '';
if (!empty($docRootNormalized) && !empty($projectRootNormalized) && strpos($projectRootNormalized, $docRootNormalized) === 0) {
    $base_dir = substr(str_replace('\\', '/', $projectRootRaw), strlen($docRootNormalized));
}
$base_dir = rtrim($base_dir, '/') . '/';
if ($base_dir !== '/') {
    $base_dir = '/' . ltrim($base_dir, '/');
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = $protocol . "://" . $host . $base_dir;
?>

<!-- Favicon Links -->
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $assetPrefix; ?>assets/favicon_io/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo $assetPrefix; ?>assets/favicon_io/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo $assetPrefix; ?>assets/favicon_io/favicon-16x16.png">
<link rel="manifest" href="<?php echo $assetPrefix; ?>assets/favicon_io/site.webmanifest">

<!-- Google Fonts Preconnect & Stylesheet -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Figtree:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">

<!-- Vendor CSS Stylesheets -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
<link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.css" integrity="sha512-yHknP1/AwR+yx26cB1y0cjvQUMvEa2PFzt1c9LlS4pRQ5NOTZFWbhBig+X9G9eYW/8m0/4OXNx8pxJ6z57x0dw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

<!-- Toastr CSS & Custom Royal Blue Theme -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
<link rel="stylesheet" href="<?php echo $assetPrefix; ?>assets/css/toast.css">

<!-- Local Application CSS Stylesheets -->
<link rel="stylesheet" href="<?php echo $assetPrefix; ?>assets/css/tournament.css?v=<?php echo $tournCssVer; ?>" type="text/css">
<link rel="stylesheet" href="<?php echo $assetPrefix; ?>assets/css/style.css?v=<?php echo $styleCssVer; ?>" type="text/css">
<link rel="stylesheet" href="<?php echo $assetPrefix; ?>assets/css/casa-dropdown.css?v=<?php echo $dropCssVer; ?>" type="text/css">

<script>
    var BASE_URL = '<?php echo $assetPrefix; ?>';
    var ABSOLUTE_BASE_URL = '<?php echo $base_url; ?>';
</script>
