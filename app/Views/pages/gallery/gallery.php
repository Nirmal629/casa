<?php
$conn = \App\Config\Database::getMysqli();

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalize_media_path($path)
{
    $path = trim((string) $path);

    if ($path === '') {
        return '';
    }

    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0 || strpos($path, '//') === 0) {
        return $path;
    }

    $clean = ltrim($path, '/');
    if (file_exists($clean)) {
        return $clean;
    }
    if (file_exists('assets/images/' . $clean)) {
        return 'assets/images/' . $clean;
    }
    if (file_exists('assets/images/gallery/' . $clean)) {
        return 'assets/images/gallery/' . $clean;
    }
    if (file_exists('admin/' . $clean)) {
        return 'admin/' . $clean;
    }

    return $clean;
}

function get_video_thumb($url, $thumb = '') {
    $thumb = trim((string)$thumb);
    if (!empty($thumb)) {
        return normalize_media_path($thumb);
    }
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches)) {
        return 'https://img.youtube.com/vi/' . $matches[1] . '/hqdefault.jpg';
    }
    return 'assets/images/badminton-banner1.jpg';
}

$galleryImages = [];
$galleryVideos = [];

$query = "
    SELECT *
    FROM ca_landing_page_media
    WHERE LOWER(media_type) IN ('image', 'video')
      AND (CAST(is_active AS CHAR) = '1' OR LOWER(CAST(is_active AS CHAR)) = 'active')
    ORDER BY id DESC
";

try {
    $result = $conn->query($query);
} catch (mysqli_sql_exception $e) {
    $result = false;
} catch (Exception $e) {
    $result = false;
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['title'] = $row['title'] ?? 'Casa Gallery';
        $row['thumbnail_url'] = get_video_thumb($row['media_url'] ?? '', $row['thumbnail_url'] ?? '');
        $row['media_url'] = normalize_media_path($row['media_url'] ?? '');

        if (strtolower((string) $row['media_type']) === 'video') {
            $galleryVideos[] = $row;
        } else {
            $galleryImages[] = $row;
        }
    }
}

// Fallback to ca_gallery if no landing page media found
if (empty($galleryImages) && empty($galleryVideos)) {
    try {
        $gal_res = $conn->query("SELECT * FROM ca_gallery ORDER BY MAIN DESC, ID ASC");
        if ($gal_res) {
            while ($g_row = $gal_res->fetch_assoc()) {
                $g_path = normalize_media_path($g_row['IMAGE'] ?? '');
                if (!empty($g_path)) {
                    $galleryImages[] = [
                        'id' => $g_row['ID'],
                        'media_type' => 'image',
                        'title' => 'Casa Gallery',
                        'media_url' => $g_path,
                        'thumbnail_url' => $g_path,
                        'description' => '',
                        'sort_order' => $g_row['MAIN'] ? 0 : 10,
                        'is_active' => 1
                    ];
                }
            }
        }
    } catch (Exception $e) {
    }
}

$hasImages = !empty($galleryImages);
$hasVideos = !empty($galleryVideos);
$defaultTab = $hasImages ? 'images' : 'videos';
?>

<!--home-gallery--->
<section class="gallerypage_sec bothSide_gap" id="gallerysecId">
    <div class="cust_container">

        <div class="text-center d-flex flex-column align-items-center">
            <h6 class="sub_heading">Gallery</h6>
            <h2 class="heading">Casa Gallery</h2>
        </div>

        <?php if ($hasImages || $hasVideos) { ?>
            <ul class="tabs">
                <?php if ($hasImages) { ?>
                    <li class="tab <?php echo $defaultTab === 'images' ? 'active' : ''; ?>" data-tab="images">Images</li>
                <?php } ?>
                <?php if ($hasVideos) { ?>
                    <li class="tab <?php echo $defaultTab === 'videos' ? 'active' : ''; ?>" data-tab="videos">Videos</li>
                <?php } ?>
            </ul>

            <?php if ($hasImages) { ?>
                <div id="images" class="tab-content <?php echo $defaultTab === 'images' ? 'active' : ''; ?>">
                    <div id="gallery" class="casaphotos_slider gallery">
                        <?php foreach ($galleryImages as $image) { ?>
                            <div class="img-box">
                                <a href="<?php echo h($image['media_url']); ?>" class="glightbox-image" data-gallery="gallery-images" data-glightbox="type: image">
                                    <img src="<?php echo h($image['media_url']); ?>" alt="<?php echo h($image['title'] ?: 'Gallery image'); ?>" onerror="this.onerror=null; this.src='assets/images/badminton-banner1.jpg';">
                                </a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <?php if ($hasVideos) { ?>
                <div id="videos" class="tab-content <?php echo $defaultTab === 'videos' ? 'active' : ''; ?>">
                    <div class="casavideo_slider gallery">
                        <?php foreach ($galleryVideos as $video) { ?>
                            <div class="casavideo-box">
                                <a href="<?php echo h($video['media_url']); ?>" class="glightbox-video" data-gallery="gallery-videos" data-glightbox="type: video">
                                    <img src="<?php echo h($video['thumbnail_url']); ?>" alt="<?php echo h($video['title'] ?: 'Gallery video'); ?>" onerror="this.onerror=null; this.src='assets/images/badminton-banner1.jpg';">
                                    <div class="transparent-box">
                                        <div class="caption">▶</div>
                                    </div>
                                </a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="text-center" style="padding: 30px 0;">
                <p class="desc mb-0">No active gallery media found.</p>
            </div>
        <?php } ?>

    </div>
</section>

<!-----tab js------>
<script>
    document.querySelectorAll(".tab").forEach(function(tab) {
        tab.addEventListener("click", function() {
            var tabName = this.dataset.tab;
            if (!tabName) return;

            document.querySelectorAll(".tab").forEach(function(item) {
                item.classList.remove("active");
            });

            document.querySelectorAll(".tab-content").forEach(function(content) {
                content.classList.remove("active");
            });

            this.classList.add("active");

            var target = document.getElementById(tabName);
            if (target) {
                target.classList.add("active");
                if (window.jQuery && jQuery.fn.slick) {
                    var $slider = jQuery(target).find('.casaphotos_slider, .casavideo_slider');
                    if ($slider.length) {
                        $slider.slick('setPosition');
                    }
                    requestAnimationFrame(function() {
                        jQuery('.casaphotos_slider, .casavideo_slider').slick('setPosition');
                    });
                }
            }
        });
    });
</script>
