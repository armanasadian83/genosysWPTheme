<?php
/** Homepage category showcase, using the supplied images. */
defined( 'ABSPATH' ) || exit;

$category_cards = array(
	array( 'title' => 'مراقبت مو و پوست سر', 'image' => 'category-hair-1.5.7.webp', 'size' => 'short', 'terms' => array( 'مراقبت مو و پوست سر', 'مراقبت از مو و پوست سر', 'محصولات مو' ) ),
	array( 'title' => 'مراقبت پوست', 'image' => 'category-skin-1.5.7.webp', 'size' => 'tall', 'terms' => array( 'مراقبت پوست', 'مراقبت از پوست', 'محصولات مراقبت از پوست' ) ),
	array( 'title' => 'ضد آفتاب‌ها', 'image' => 'category-sun-1.5.7.webp', 'size' => 'short', 'terms' => array( 'ضد آفتاب‌ها', 'ضد آفتاب', 'ضدآفتاب' ) ),
	array( 'title' => 'میست و اسپری', 'image' => 'category-mist-1.5.7.webp', 'size' => 'tall', 'terms' => array( 'میست و اسپری', 'میست', 'اسپری' ) ),
	array( 'title' => 'محصولات آرایشی', 'image' => 'category-makeup-1.5.7.webp', 'size' => 'short', 'terms' => array( 'محصولات آرایشی', 'آرایش', 'محصولات آرایش' ) ),
	array( 'title' => 'مراقبت دور چشم', 'image' => 'category-eye-1.5.7.webp', 'size' => 'tall', 'terms' => array( 'مراقبت دور چشم', 'محصولات دور چشم', 'دور چشم' ) ),
);
$products_url = genosys_home_card_url( 'products' );
?>
<section class="genosys-categories" aria-labelledby="genosys-categories-title">
	<div class="genosys-categories__intro">
		<h2 id="genosys-categories-title">مراقبتی که <span>مخصوص شماست</span></h2>
		<p>با محصولات تخصصی ژنوسیس، پوست و موی خود را به متخصصان بسپارید.</p>
		<a class="genosys-categories__button" href="#genosys-category-row">مشاهده دسته‌بندی‌ها</a>
		<a class="genosys-categories__brand" href="<?php echo esc_url( home_url( '/brand/' ) ); ?>">یا با برند ما آشنا شوید</a>
	</div>
	<div class="genosys-categories__stage">
		<div id="genosys-category-row" class="genosys-categories__row latest-products__row" role="region" aria-label="دسته‌بندی محصولات ژنوسیس، فهرست افقی" tabindex="0">
			<?php foreach ( $category_cards as $card ) : ?>
				<?php
				$url = $products_url;
				foreach ( $card['terms'] as $term_name ) {
					$term = get_term_by( 'name', $term_name, 'product_cat' );
					if ( ! $term || is_wp_error( $term ) ) {
						continue;
					}
					$term_url = get_term_link( $term );
					if ( ! is_wp_error( $term_url ) ) {
						$url = $term_url;
						break;
					}
				}
				?>
				<a class="genosys-categories__card genosys-categories__card--<?php echo esc_attr( $card['size'] ); ?>" href="<?php echo esc_url( $url ); ?>">
					<img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/' . $card['image'] ) ); ?>" alt="" width="536" height="536" loading="lazy" decoding="async" draggable="false">
					<span class="genosys-categories__card-title"><?php echo esc_html( $card['title'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<?php get_template_part( 'template-parts/home/category-stats' ); ?>
	</div>
</section>
