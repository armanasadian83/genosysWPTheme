<?php
/** Editable homepage slider; the default photographs and markup are unchanged. */
defined( 'ABSPATH' ) || exit;
$slides = array_values( array_filter( genosys_site_setting( 'slides' ), static function ( $slide ) { return is_array( $slide ) && ! empty( $slide['image'] ); } ) );
if ( ! $slides ) { return; }
$slide_total = genosys_persian_number( count( $slides ) );
?>
    <section class="hero-banner" aria-label="تصاویر محصولات ژنوسیس" aria-roledescription="اسلایدر" data-interval="<?php echo esc_attr( max( 2000, min( 20000, (int) genosys_site_setting( 'slider_interval', 6000 ) ) ) ); ?>">
        <div id="banner-slides" class="hero-slides">
            <?php foreach ( $slides as $index => $slide ) : ?>
                <?php $slide_class = isset( $slide['class'] ) && in_array( $slide['class'], array( 'slide-skincare', 'slide-eco', 'slide-hair', 'slide-ampoules' ), true ) ? $slide['class'] : ''; ?>
                <figure class="hero-slide <?php echo esc_attr( $slide_class ); ?><?php echo 0 === $index ? ' is-active' : ''; ?>" role="group" aria-roledescription="اسلاید" aria-label="<?php echo esc_attr( genosys_persian_number( $index + 1 ) . ' از ' . $slide_total ); ?>" aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>">
                    <img src="<?php echo esc_url( $slide['image'] ); ?>" alt="<?php echo esc_attr( $slide['alt'] ?? '' ); ?>" width="1672" height="941" loading="eager" decoding="async" fetchpriority="<?php echo 0 === $index ? 'high' : 'low'; ?>" draggable="false">
                    <?php if ( ! empty( $slide['link'] ) ) : ?><a class="genosys-slide-link" href="<?php echo esc_url( $slide['link'] ); ?>" aria-label="<?php echo esc_attr( ! empty( $slide['title'] ) ? $slide['title'] : ( ! empty( $slide['alt'] ) ? $slide['alt'] : 'مشاهده محصولات' ) ); ?>"></a><?php endif; ?>
                    <?php if ( ! empty( $slide['title'] ) ) : ?><figcaption class="genosys-slide-caption"><?php echo esc_html( $slide['title'] ); ?></figcaption><?php endif; ?>
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
                    <button class="banner-dot" type="button" data-slide="<?php echo esc_attr( $index ); ?>" aria-label="<?php echo esc_attr( 'نمایش اسلاید ' . genosys_persian_number( $index + 1 ) ); ?>" aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>" aria-controls="banner-slides"><span aria-hidden="true"></span></button>
                <?php endforeach; ?>
                <button class="banner-playback" type="button" aria-label="توقف پخش خودکار" aria-controls="banner-slides">
                    <svg class="banner-pause-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false"><path d="M8 5v14M16 5v14"/></svg>
                    <svg class="banner-play-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false"><path d="m8 5 11 7-11 7Z"/></svg>
                </button>
            </div>
        </div>
        <p class="screen-reader-text banner-status" role="status" aria-live="polite" aria-atomic="true"></p>
    </section>