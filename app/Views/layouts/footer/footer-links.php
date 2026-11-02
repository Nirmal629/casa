<!-- Core Vendor Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.js" integrity="sha512-HGOnQO9+SP1V92SrtZfjqxxtLmVzqZpjFFekvzZVWoiASSQgSr4cw9Kqd2+l8Llp4Gm0G8GIFJ4ddwZilcdb8A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<?php
if (!isset($assetPrefix)) {
    $inSubfolder = in_array(basename(dirname($_SERVER['SCRIPT_FILENAME'] ?? '')), ['host', 'player'], true);
    $assetPrefix = $inSubfolder ? '../' : '';
}

// Smart Cache-Busting: Uses file modification timestamp instead of time() to enable browser caching while ensuring instant updates on file changes
$scriptFile  = __DIR__ . '/../../../../app/script/index.js';
$trackerFile = __DIR__ . '/../../../../app/script/activity-tracker.js';
$dropJsFile  = __DIR__ . '/../../../../assets/js/casa-dropdown.js';
$pagJsFile   = __DIR__ . '/../../../../assets/js/casa-pagination.js';
$calJsFile   = __DIR__ . '/../../../../assets/js/dynamic-event-calendar.js';

$scriptVer  = file_exists($scriptFile) ? filemtime($scriptFile) : '1.0';
$trackerVer = file_exists($trackerFile) ? filemtime($trackerFile) : '1.0';
$dropJsVer  = file_exists($dropJsFile) ? filemtime($dropJsFile) : '1.0';
$pagJsVer   = file_exists($pagJsFile) ? filemtime($pagJsFile) : '1.0';
$calJsVer   = file_exists($calJsFile) ? filemtime($calJsFile) : '1.0';
?>

<!-- Application Custom Scripts -->
<script src="<?php echo $assetPrefix; ?>assets/js/casa-dropdown.js?v=<?php echo $dropJsVer; ?>"></script>
<script src="<?php echo $assetPrefix; ?>assets/js/casa-pagination.js?v=<?php echo $pagJsVer; ?>"></script>
<?php if (function_exists('renderDynamicCalendarScript')): ?>
<?= renderDynamicCalendarScript($assetPrefix) ?>
<?php else: ?>
<script src="<?php echo $assetPrefix; ?>assets/js/dynamic-event-calendar.js?v=<?php echo $calJsVer; ?>"></script>
<?php endif; ?>
<script src="<?php echo $assetPrefix; ?>app/script/index.js?v=<?php echo $scriptVer; ?>"></script>

<?php
$qaEnv = function_exists('env') ? strtolower((string) env('APP_ENV', 'local')) : 'local';
$isNotProd = !in_array($qaEnv, ['production', 'prod'], true);
$appDebug = function_exists('env') ? env('APP_DEBUG', null) : null;
$debugNotFalse = ($appDebug === null || filter_var($appDebug, FILTER_VALIDATE_BOOLEAN) !== false);

if ($isNotProd && $debugNotFalse):
    $qaJsFile = __DIR__ . '/../../../../assets/js/manual-test-logger.js';
    $qaJsVer = file_exists($qaJsFile) ? filemtime($qaJsFile) : '1.0';
?>
<!-- Development-Only Manual Testing Journey Logger & Control Panel -->
<script src="<?php echo $assetPrefix; ?>assets/js/manual-test-logger.js?v=<?php echo $qaJsVer; ?>"></script>
<?php include __DIR__ . '/manual-test-panel.php'; ?>
<?php endif; ?>

<script>
  // Global Toast Notification Helper - Supports both (type, message, title) and (message, type) signatures
  function showToast(arg1, arg2, title) {
    if (typeof toastr !== 'undefined') {
      if (typeof toastr.clear === 'function') {
        toastr.clear();
      }
      toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "2500",
        "extendedTimeOut": "1000",
        "showDuration": "200",
        "hideDuration": "200",
        "preventDuplicates": true,
        "maxOpened": 1
      };
      var knownTypes = ['success', 'error', 'warning', 'info'];
      var type = 'success';
      var message = '';

      if (knownTypes.indexOf(String(arg1).toLowerCase()) !== -1) {
        type = String(arg1).toLowerCase();
        message = String(arg2 || '');
      } else {
        message = String(arg1 || '');
        type = (arg2 && knownTypes.indexOf(String(arg2).toLowerCase()) !== -1) ? String(arg2).toLowerCase() : 'success';
      }

      if (typeof toastr[type] === 'function') {
        toastr[type](message, title);
      } else {
        toastr.info(message, title);
      }
    }
  }

  // Smart override: converts all native browser alert() calls site-wide into Toastr notifications
  window.alert = function (message) {
    if (typeof toastr !== 'undefined') {
      if (typeof toastr.clear === 'function') {
        toastr.clear();
      }
      var msgStr = String(message || '');
      var lower = msgStr.toLowerCase();
      if (lower.indexOf('error') !== -1 || lower.indexOf('fail') !== -1 || lower.indexOf('invalid') !== -1 || lower.indexOf('unable') !== -1) {
        toastr.error(msgStr);
      } else if (lower.indexOf('success') !== -1 || lower.indexOf('joined') !== -1 || lower.indexOf('saved') !== -1 || lower.indexOf('done') !== -1) {
        toastr.success(msgStr);
      } else if (lower.indexOf('warning') !== -1 || lower.indexOf('please') !== -1 || lower.indexOf('select') !== -1) {
        toastr.warning(msgStr);
      } else {
        toastr.info(msgStr);
      }
    } else {
      console.log(message);
    }
  };


  document.addEventListener("DOMContentLoaded", function () {
    // 1. Toastr Global Default Configuration
    if (typeof toastr !== 'undefined') {
      toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "2500",
        "extendedTimeOut": "1000",
        "showDuration": "200",
        "hideDuration": "200",
        "preventDuplicates": true,
        "maxOpened": 1
      };
    }

    // 2. Bootstrap 5 Tooltips & Popovers Auto-Initialization
    if (typeof bootstrap !== 'undefined') {
      var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltipTriggerList.forEach(function (el) { new bootstrap.Tooltip(el); });

      if (typeof initPopovers === 'function') {
        initPopovers();
      } else {
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.forEach(function (el) { new bootstrap.Popover(el); });
      }
    }

    // 3. GLightbox Initialization (Defensive check for elements)
    if (typeof GLightbox !== 'undefined' && document.querySelector('.glightbox-image, .glightbox-video')) {
      GLightbox({
        selector: ".glightbox-image",
        touchNavigation: true,
        loop: true,
        width: "90vw",
        height: "90vh"
      });

      GLightbox({
        selector: ".glightbox-video",
        touchNavigation: true,
        loop: true,
        width: "90vw",
        height: "90vh"
      });
    }

    // 4. AOS Initialization
    if (typeof AOS !== 'undefined') {
      AOS.init();
    }

    // 5. Global Automatic Image Lazy Loading (Exclude slider images to prevent layout jump on refresh)
    document.querySelectorAll('img:not([loading])').forEach(function (img) {
      if (!img.closest('.Logo_area') && !img.closest('.site-header-left') && !img.closest('.slick-slider') && !img.closest('.herobanner_slider') && !img.closest('.tournamentcard_slider') && !img.closest('.sttoreproduct_slider') && !img.classList.contains('no-lazy')) {
        img.setAttribute('loading', 'lazy');
      }
    });
  });

  window.addEventListener('load', function () {
    if (window.jQuery && jQuery.fn.slick) {
      jQuery('.slick-slider, .herobanner_slider, .tournamentcard_slider, .sttoreproduct_slider, .casaphotos_slider, .casavideo_slider, .discoverGames_slider').each(function () {
        if (jQuery(this).hasClass('slick-initialized')) {
          jQuery(this).slick('setPosition');
        }
      });
    }
    if (typeof AOS !== 'undefined') {
      AOS.refresh();
    }
    window.dispatchEvent(new Event('resize'));
  });

  // 5. Global Image Error Fallback (Prevents broken image icons site-wide)
  document.addEventListener('error', function (e) {
    if (e.target && e.target.tagName === 'IMG' && !e.target.getAttribute('data-fallback-handled')) {

      e.target.setAttribute('data-fallback-handled', 'true');
      var fallbackImg = '<?php echo $assetPrefix; ?>assets/images/default.jpg';
      if (e.target.src !== fallbackImg) {
        e.target.src = fallbackImg;
      }
    }
  }, true);
</script>

<?= function_exists('renderFlashToast') ? renderFlashToast() : '' ?>


