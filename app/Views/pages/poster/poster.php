<?php
$posters ??= [];

if (!empty($posters)):
?>
<style>
    /* CASA POSTER SECTION */
    .Casapostbanner_sec {
        background: #0f172a;
    }

    .sub_heading {
        color: #22d3ee !important;
    }
</style>

<section class="Casapostbanner_sec bothSide_gap">
    <div class="cust_container">
        <div class="text-center d-flex flex-column align-items-center">
            <h2 class="heading text-white">Posters and Cards</h2>
        </div>

        <div class="advertisement_slider gallery">
            <?php foreach ($posters as $item): ?>
                <?php
                $src = is_array($item) ? ($item['media_url'] ?? $item['image'] ?? '') : (string)$item;
                $src = trim((string)$src);
                if (empty($src)) continue;
                if (!preg_match('#^(?:https?:)?//#i', $src)) {
                    $basePath = function_exists('appBasePath') ? appBasePath() : '/';
                    if ($basePath === '/' || !str_starts_with($src, $basePath)) {
                        $src = function_exists('appUrl') ? appUrl(ltrim($src, '/')) : '/' . ltrim($src, '/');
                    }
                }
                $encodedSrc = implode('/', array_map('rawurlencode', explode('/', $src)));
                $url = htmlspecialchars($encodedSrc, ENT_QUOTES, 'UTF-8');
                ?>
                <div class="item_banner">
                    <a href="<?php echo $url; ?>" class="glightbox" data-glightbox="type:image">
                        <img src="<?php echo $url; ?>" alt="Casa Poster">
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
