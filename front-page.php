<?php
/** Full-screen homepage banner with the four supplied product photographs. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$slides = array(
    array( 'file' => 'banner-01-skincare-1.2.2.webp', 'alt' => 'مجموعه کرم‌ها و محصولات مراقبت از پوست ژنوسیس روی پایه‌های سفید', 'class' => 'slide-skincare' ),
    array( 'file' => 'banner-02-eco-gel-1.2.2.webp', 'alt' => 'ژل مراقبت از پوست ژنوسیس در کنار گوی‌های شفاف و قطره‌های ژل', 'class' => 'slide-eco' ),
    array( 'file' => 'banner-03-hair-1.2.2.webp', 'alt' => 'محصولات مراقبت از مو و دستگاه ژنوسیس در برابر دیوار گرم و روشن', 'class' => 'slide-hair' ),
    array( 'file' => 'banner-04-roller-1.2.2.webp', 'alt' => 'درمارولر ژنوسیس و ویال‌های مراقبت از پوست روی سطح سنگی روشن', 'class' => 'slide-ampoules' ),
);
$slide_numbers = array( '۱', '۲', '۳', '۴' );
get_header();
?>
<main id="main" class="site-main banner-main" tabindex="-1">
    <h1 class="screen-reader-text">ژنوسیس</h1>
    <section class="hero-banner" aria-label="تصاویر محصولات ژنوسیس" aria-roledescription="اسلایدر" data-interval="6000">
        <div id="banner-slides" class="hero-slides">
            <?php foreach ( $slides as $index => $slide ) : ?>
                <figure class="hero-slide <?php echo esc_attr( $slide['class'] ); ?><?php echo 0 === $index ? ' is-active' : ''; ?>" role="group" aria-roledescription="اسلاید" aria-label="<?php echo esc_attr( $slide_numbers[ $index ] . ' از ۴' ); ?>" aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>">
                    <img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/' . $slide['file'] ) ); ?>" alt="<?php echo esc_attr( $slide['alt'] ); ?>" width="1672" height="941" loading="eager" decoding="async" fetchpriority="<?php echo 0 === $index ? 'high' : 'low'; ?>" draggable="false">
                </figure>
            <?php endforeach; ?>
        </div>
        <div class="banner-controls">
            <button class="banner-arrow banner-previous" type="button" aria-label="اسلاید قبلی" aria-controls="banner-slides">
                <svg viewBox="0 0 32 48" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="m8 6 18 18L8 42"/></svg>
            </button>
            <button class="banner-arrow banner-next" type="button" aria-label="اسلاید بعدی" aria-controls="banner-slides">
                <svg viewBox="0 0 32 48" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M24 6 6 24l18 18"/></svg>
            </button>
            <div class="banner-pagination" role="group" aria-label="انتخاب اسلاید">
                <?php foreach ( $slides as $index => $slide ) : ?>
                    <button class="banner-dot" type="button" data-slide="<?php echo esc_attr( $index ); ?>" aria-label="<?php echo esc_attr( 'نمایش اسلاید ' . $slide_numbers[ $index ] ); ?>" aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>" aria-controls="banner-slides"><span aria-hidden="true"></span></button>
                <?php endforeach; ?>
                <button class="banner-playback" type="button" aria-label="توقف پخش خودکار" aria-controls="banner-slides">
                    <svg class="banner-pause-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false"><path d="M8 5v14M16 5v14"/></svg>
                    <svg class="banner-play-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false"><path d="m8 5 11 7-11 7Z"/></svg>
                </button>
            </div>
        </div>
        <p class="screen-reader-text banner-status" role="status" aria-live="polite" aria-atomic="true"></p>
    </section>
    <section class="home-feature-section" aria-label="باشگاه مشتریان و محصولات ژنوسیس">
        <div class="home-feature-grid">
            <article class="home-feature-card home-feature-club" aria-labelledby="home-club-title">
                <img class="home-feature-image" src="<?php echo esc_url( get_theme_file_uri( '/assets/images/customer-club-1.3.1.webp' ) ); ?>" alt="" width="1672" height="941" loading="lazy" decoding="async">
                <div class="home-feature-content">
                    <h2 id="home-club-title" class="home-feature-title"><a class="home-feature-title-link" href="<?php echo esc_url( home_url( '/fans-club/' ) ); ?>">باشگاه مشتریان</a></h2>
                    <a class="home-feature-button" href="<?php echo esc_url( home_url( '/fans-club/' ) ); ?>">ورود به باشگاه</a>
                </div>
            </article>
            <article class="home-feature-card home-feature-products" aria-labelledby="home-products-title">
                <img class="home-feature-image" src="<?php echo esc_url( get_theme_file_uri( '/assets/images/products-card-1.3.2.webp' ) ); ?>" alt="" width="1974" height="797" loading="lazy" decoding="async">
                <div class="home-feature-content">
                    <h2 id="home-products-title" class="home-feature-title">محصولات ژنوسیس</h2>
                    <a class="home-feature-button" href="<?php echo esc_url( genosys_home_card_url( 'products' ) ); ?>">مشاهده محصولات</a>
                </div>
            </article>
        </div>
    </section>
    <?php get_template_part( 'template-parts/home/latest-products' ); ?>
    <?php get_template_part( 'template-parts/home/categories' ); ?>
</main>
<?php get_footer(); ?>
