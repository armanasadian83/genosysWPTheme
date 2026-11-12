<?php
/** Animated homepage statistics beneath the category cards. */
defined( 'ABSPATH' ) || exit;

if ( ! genosys_site_setting( 'stats_visible', 1 ) ) { return; }
$genosys_stats = genosys_site_setting( 'stats' );
foreach ( $genosys_stats as &$stat ) {
    $stat['count'] = max( 0, min( 1000000, (int) ( $stat['count'] ?? 0 ) ) );
    $stat['display'] = genosys_persian_number( $stat['count'] );
}
unset( $stat );
?>
<div class="genosys-category-stats" role="list" aria-label="آمار ژنوسیس">
	<?php foreach ( $genosys_stats as $stat ) : ?>
		<div class="genosys-category-stats__item" role="listitem" aria-label="<?php echo esc_attr( $stat['display'] . '+ ' . $stat['label'] ); ?>">
			<svg class="genosys-category-stats__ring" viewBox="0 0 340 340" aria-hidden="true" focusable="false">
				<circle class="genosys-category-stats__track" cx="170" cy="170" r="148" />
				<circle class="genosys-category-stats__progress" cx="170" cy="170" r="148" pathLength="100" />
			</svg>
			<div class="genosys-category-stats__content" aria-hidden="true">
				<span class="genosys-category-stats__icon">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<?php if ( 'calendar' === $stat['icon'] ) : ?>
							<rect x="3.5" y="5.5" width="17" height="15" rx="2" /><path d="M7.5 3v5M16.5 3v5M3.5 10h17" />
						<?php elseif ( 'people' === $stat['icon'] ) : ?>
							<circle cx="9" cy="8" r="3" /><circle cx="17" cy="9" r="2.3" /><path d="M3.5 20v-1.2c0-3 2.2-5 5.5-5s5.5 2 5.5 5V20M15 14.8c3.4-.1 5.5 1.8 5.5 4.7V20" />
						<?php elseif ( 'flask' === $stat['icon'] ) : ?>
							<path d="M9 3h6M10 3v7l-5.3 8.3A1.8 1.8 0 0 0 6.2 21h11.6a1.8 1.8 0 0 0 1.5-2.7L14 10V3M8 16h8" />
						<?php else : ?>
							<circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3c-2.5 2.4-3.8 5.4-3.8 9s1.3 6.6 3.8 9M12 3c2.5 2.4 3.8 5.4 3.8 9s-1.3 6.6-3.8 9" />
						<?php endif; ?>
					</svg>
				</span>
				<span class="genosys-category-stats__value" dir="ltr"><span class="genosys-category-stats__plus">+</span><span class="genosys-category-stats__number" data-count="<?php echo esc_attr( $stat['count'] ); ?>"><?php echo esc_html( $stat['display'] ); ?></span></span>
				<span class="genosys-category-stats__label"><?php echo esc_html( $stat['label'] ); ?></span>
			</div>
		</div>
	<?php endforeach; ?>
</div>
