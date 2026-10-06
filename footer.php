<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<footer class="genosys-footer" role="contentinfo">
    <div class="genosys-footer__hero">
        <div class="genosys-footer__art" aria-hidden="true"></div>
        <div class="genosys-footer__hero-inner">
            <div class="genosys-footer__invitation">
                <h2 class="genosys-footer__headline">
                    <span>بیایید با هم، <em>پوستی</em></span>
                    <span><em>درخشان</em> بسازیم</span>
                </h2>
                <button class="genosys-footer__outline-button" type="button" disabled>دریافت کاتالوگ محصولات</button>
            </div>
        </div>
        <svg class="genosys-footer__wave" viewBox="0 0 1703 202" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <path fill="#efedeb" d="M0 94C222 8 473 127 738 113c281-14 468-91 700-81 112 5 207 20 265 44v126H0Z"/>
            <path fill="#f7f5f3" d="M0 196C270 127 440 130 680 148c220 15 420-43 640-60 160-14 280-8 383 2v112H0Z"/>
        </svg>
    </div>

    <div class="genosys-footer__lower">
        <div class="genosys-footer__columns">
            <div class="genosys-footer__brand">
                <div class="genosys-footer__brand-content">
                    <a class="genosys-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="ژنوسیس — صفحه اصلی">
                        <img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/genosys-logo-1.1.1.svg' ) ); ?>" alt="GENOSYS" width="265" height="58">
                    </a>
                    <p>ژنوسیس، مراقبت علمی پوست و مو با تکیه بر فناوری بازسازی <br>ژنی.</p>
                    <a class="genosys-footer__filled-button" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">تماس با ما</a>
                </div>
            </div>

            <nav class="genosys-footer__links" aria-label="پیوندهای پاورقی">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a>
                <a href="<?php echo esc_url( genosys_home_card_url( 'products' ) ); ?>">محصولات</a>
                <a href="<?php echo esc_url( home_url( '/brand/' ) ); ?>">برند</a>
                <a href="<?php echo esc_url( home_url( '/fans-club/' ) ); ?>">باشگاه مشتریان</a>
                <a href="<?php echo esc_url( home_url( '/company/' ) ); ?>">درباره‌ی شرکت</a>
            </nav>

            <div class="genosys-footer__social" aria-label="شبکه‌های اجتماعی">
                <span class="genosys-footer__social-icon" role="img" aria-label="اینستاگرام">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="12" cy="12" r="4"/><circle cx="17.4" cy="6.7" r=".85" fill="currentColor" stroke="none"/></svg>
                </span>
                <span class="genosys-footer__social-icon" role="img" aria-label="تلگرام">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 3-3.8 17-5.3-4.2-2.8 2.7.5-4.6L21 3Z"/><path d="m9.6 13.9 8.7-7.7L6.8 12l-4.3-1.5L21 3"/></svg>
                </span>
                <span class="genosys-footer__social-icon" role="img" aria-label="واتساپ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.2 11.8a8.2 8.2 0 0 1-12.1 7.3L3 20.5l1.5-4.8a8.2 8.2 0 1 1 15.7-3.9Z"/><path d="M8.7 8.2c-.5-.2-1.2.6-1.2 1.4 0 2.2 3 5.5 5.6 6.6 1.4.6 2.9.5 3.5-.4l.5-1.1-2.6-1.2-.9 1c-1.5-.5-2.9-1.7-3.6-3.1l.8-1-1.1-2.2Z"/></svg>
                </span>
                <span class="genosys-footer__social-icon genosys-footer__social-icon--linkedin" role="img" aria-label="لینکدین"><span aria-hidden="true">in</span></span>
            </div>
        </div>

        <div class="genosys-footer__bottom">
            <span>© ژنوسیس — تمامی حقوق محفوظ است</span>
            <span lang="en" dir="ltr">GENOSYS - Gene Re-birth System</span>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
