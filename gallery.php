<?php
include 'dbConnection.php';

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

    if (strpos($path, '/admin/') === 0 || strpos($path, '/frontend/') === 0) {
        return $path;
    }

    if ($path[0] === '/') {
        return $path;
    }

    if (strpos($path, 'admin/') === 0 || strpos($path, 'assets/') === 0) {
        return '/' . $path;
    }

    return '/admin/' . ltrim($path, '/');
}

$galleryImages = [];
$galleryVideos = [];

$query = "
    SELECT id, media_type, title, media_url, thumbnail_url, description, sort_order, is_active
    FROM ca_landing_page_media
    WHERE LOWER(media_type) IN ('image', 'video')
      AND (CAST(is_active AS CHAR) = '1' OR LOWER(CAST(is_active AS CHAR)) = 'active')
    ORDER BY sort_order ASC, id DESC
";

$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['media_url'] = normalize_media_path($row['media_url'] ?? '');
        $row['thumbnail_url'] = normalize_media_path($row['thumbnail_url'] ?? '');

        if (strtolower((string) $row['media_type']) === 'video') {
            $galleryVideos[] = $row;
        } else {
            $galleryImages[] = $row;
        }
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
                                    <img src="<?php echo h($image['media_url']); ?>" alt="<?php echo h($image['title'] ?: 'Gallery image'); ?>">
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
                                    <?php if ($video['thumbnail_url'] !== '') { ?>
                                        <img src="<?php echo h($video['thumbnail_url']); ?>" alt="<?php echo h($video['title'] ?: 'Gallery video'); ?>">
                                    <?php } else { ?>
                                        <div style="height: 220px; display:flex; align-items:center; justify-content:center; background:#111; color:#fff; border-radius:12px;">
                                            <?php echo h($video['title'] ?: 'Play Video'); ?>
                                        </div>
                                    <?php } ?>
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
            document.querySelectorAll(".tab").forEach(function(item) {
                item.classList.remove("active");
            });

            document.querySelectorAll(".tab-content").forEach(function(content) {
                content.classList.remove("active");
            });

            tab.classList.add("active");

            var target = document.getElementById(tab.dataset.tab);
            if (target) {
                target.classList.add("active");
            }

            setTimeout(function() {
                if (window.jQuery && jQuery.fn.slick) {
                    jQuery('.casaphotos_slider').slick('setPosition');
                    jQuery('.casavideo_slider').slick('setPosition');
                }
            }, 200);
        });
    });
</script>
