<?php
/** Homepage presentation follows the saved section order; defaults preserve the existing layout. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<main id="main" class="site-main banner-main" tabindex="-1">
    <h1 class="screen-reader-text"><?php echo esc_html( genosys_site_setting( 'site_name', 'ژنوسیس' ) ); ?></h1>
    <?php
    $parts = array( 'slider' => 'hero-banner', 'cards' => 'feature-cards', 'latest' => 'latest-products', 'categories' => 'categories', 'featured' => 'featured-products' );
    foreach ( genosys_home_sections() as $section ) {
        if ( $section['visible'] && isset( $parts[ $section['id'] ] ) ) { get_template_part( 'template-parts/home/' . $parts[ $section['id'] ] ); }
    }
    ?>
</main>
<?php get_footer(); ?>