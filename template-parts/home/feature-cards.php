<?php
/** The two established homepage cards with editable images, labels and destinations. */
defined( 'ABSPATH' ) || exit;
$club_url = genosys_site_url( 'club_card_url', home_url( '/fans-club/' ) );
$products_url = genosys_site_url( 'products_card_url', home_url( '/shop/' ) );
?>
    <section class="home-feature-section" aria-label="باشگاه مشتریان و محصولات ژنوسیس">
        <div class="home-feature-grid">
            <article class="home-feature-card home-feature-club" aria-labelledby="home-club-title">
                <img class="home-feature-image" src="<?php echo esc_url( genosys_site_url( 'club_card_image', get_theme_file_uri( '/assets/images/customer-club-1.3.1.webp' ) ) ); ?>" alt="" width="1672" height="941" loading="lazy" decoding="async">
                <div class="home-feature-content">
                    <h2 id="home-club-title" class="home-feature-title"><a class="home-feature-title-link" href="<?php echo esc_url( $club_url ); ?>"><?php echo esc_html( genosys_site_setting( 'club_card_title' ) ); ?></a></h2>
                    <a class="home-feature-button" href="<?php echo esc_url( $club_url ); ?>"><?php echo esc_html( genosys_site_setting( 'club_card_button' ) ); ?></a>
                </div>
            </article>
            <article class="home-feature-card home-feature-products" aria-labelledby="home-products-title">
                <img class="home-feature-image" src="<?php echo esc_url( genosys_site_url( 'products_card_image', get_theme_file_uri( '/assets/images/products-card-1.3.2.webp' ) ) ); ?>" alt="" width="1974" height="797" loading="lazy" decoding="async">
                <div class="home-feature-content">
                    <h2 id="home-products-title" class="home-feature-title"><a class="home-feature-title-link" href="<?php echo esc_url( $products_url ); ?>"><?php echo esc_html( genosys_site_setting( 'products_card_title' ) ); ?></a></h2>
                    <a class="home-feature-button" href="<?php echo esc_url( $products_url ); ?>"><?php echo esc_html( genosys_site_setting( 'products_card_button' ) ); ?></a>
                </div>
            </article>
        </div>
    </section>
