<?php
/** WordPress selects this template for missing pages and invalid URLs. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<main id="main" class="site-main error-page" tabindex="-1" lang="fa">
    <section class="error-content" aria-labelledby="error-title">
        <p class="error-code" aria-hidden="true">۴۰۴</p>
        <h1 id="error-title" class="error-title">این صفحه پیدا نشد</h1>
        <p class="error-description">ممکن است آدرس را اشتباه وارد کرده باشید یا این صفحه جابه‌جا شده باشد. از صفحه اصلی دوباره شروع کنید.</p>
        <a class="home-button" href="<?php echo esc_url( home_url( '/' ) ); ?>">
            <span>بازگشت به صفحه اصلی</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M19 12H5m6-6-6 6 6 6"/></svg>
        </a>
    </section>
</main>
<?php get_footer(); ?>
