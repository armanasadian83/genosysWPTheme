<?php
/** Current customer's private account dashboard. Native Woo endpoints retain their own views. */
defined( 'ABSPATH' ) || exit;

$account = genosys_account_dashboard_context();
$digits = 'genosys_account_dashboard_digits';
$requested_tab = isset( $_GET['ga_tab'] ) && is_string( $_GET['ga_tab'] ) ? sanitize_key( wp_unslash( $_GET['ga_tab'] ) ) : 'profile';
$is_address_endpoint = is_wc_endpoint_url( 'edit-address' );
$address_type = $is_address_endpoint ? (string) get_query_var( 'edit-address' ) : '';
$active_tab = $is_address_endpoint ? 'addresses' : ( in_array( $requested_tab, array( 'profile', 'addresses', 'notifications' ), true ) ? $requested_tab : 'profile' );
$initial = function_exists( 'mb_substr' ) ? mb_substr( $account['displayName'], 0, 1, 'UTF-8' ) : preg_replace( '/^(.).*$/us', '$1', $account['displayName'] );
$months = array( 1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
$current_year = (int) substr( genosys_account_dashboard_shamsi( time() ), 0, 4 );
$icon = static function ( $name ) {
	$paths = array(
		'user' => '<circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
		'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
		'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
		'gift' => '<path d="M3 8h18v4H3zM5 12v9h14v-9M12 8v13"/><path d="M12 8C5 8 5 1 8 2c3 0 4 6 4 6Zm0 0c7 0 7-7 4-6-3 0-4 6-4 6Z"/>',
		'bag' => '<path d="M5 8h14l-1 13H6L5 8ZM9 8V5a3 3 0 0 1 6 0v3"/>',
		'logout' => '<path d="M10 4H4v16h6m-1-8h13m-5-5 5 5-5 5"/>',
		'star' => '<path d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z"/>',
		'sparkle' => '<path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5L12 3Z"/>',
		'medal' => '<circle cx="12" cy="8" r="5"/><path d="m9 12-1 9 4-3 4 3-1-9"/>',
		'check' => '<path d="m5 12 4 4L19 6"/>',
	);
	echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $paths[ $name ] ?? '' ) . '</svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG constants.
};
$badge = static function () use ( $account, $icon ) {
	?><span class="gaccount__badge" data-membership="<?php echo esc_attr( $account['membershipType'] ); ?>"><?php $icon( 'member' === $account['membershipType'] ? 'star' : ( 'returning' === $account['membershipType'] ? 'medal' : 'sparkle' ) ); ?><span><?php echo esc_html( $account['membershipLabel'] ); ?></span></span><?php
};
$avatar = static function ( $modifier ) use ( $account, $initial ) {
	?><div class="gaccount__avatar gaccount__avatar--<?php echo esc_attr( $modifier ); ?>" data-account-avatar><?php if ( $account['avatarUrl'] ) : ?><img src="<?php echo esc_url( $account['avatarUrl'] ); ?>" alt="" width="130" height="130"><?php else : ?><span data-account-avatar-initial><?php echo esc_html( $initial ); ?></span><?php endif; ?></div><?php
};
$navigation = array(
	array( 'key' => 'profile', 'label' => genosys_site_setting( 'account_profile_label', 'اطلاعات حساب' ), 'icon' => 'user', 'url' => $account['accountUrl'], 'count' => 0 ),
	array( 'key' => 'addresses', 'label' => genosys_site_setting( 'account_addresses_label', 'آدرس‌ها' ), 'icon' => 'pin', 'url' => add_query_arg( 'ga_tab', 'addresses', $account['accountUrl'] ), 'count' => $account['addressCount'] ),
	array( 'key' => 'notifications', 'label' => genosys_site_setting( 'account_notifications_label', 'اعلان‌ها' ), 'icon' => 'bell', 'url' => add_query_arg( 'ga_tab', 'notifications', $account['accountUrl'] ), 'count' => 0 ),
	array( 'key' => 'club', 'label' => genosys_site_setting( 'account_club_label', 'باشگاه مشتریان' ), 'icon' => 'gift', 'url' => $account['clubUrl'], 'count' => 0 ),
	array( 'key' => 'orders', 'label' => genosys_site_setting( 'account_orders_label', 'سفارشات من' ), 'icon' => 'bag', 'url' => $account['ordersUrl'], 'count' => $account['ordersActiveCount'] ),
);
$navigation_labels = array_column( $navigation, 'label', 'key' );
get_header();
?>
<main id="main" class="gaccount" data-account-dashboard dir="rtl" tabindex="-1">
	<div class="gaccount__container">
		<nav class="gaccount__breadcrumb" aria-label="مسیر صفحه"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a><span aria-hidden="true">/</span><span aria-current="page"><?php echo esc_html( genosys_site_setting( 'account_title', 'حساب کاربری' ) ); ?></span></nav>
		<h1 class="gaccount__title"><?php echo esc_html( genosys_site_setting( 'account_title', 'حساب کاربری' ) ); ?></h1>
		<div class="gaccount__layout">
			<aside class="gaccount__sidebar" aria-label="حساب مشتری">
				<section class="gaccount__identity">
					<?php $avatar( 'sidebar' ); ?>
					<h2 data-account-display-name><?php echo esc_html( $account['displayName'] ); ?></h2>
					<p class="gaccount__phone-small" data-account-phone><?php echo esc_html( $account['phone'] ? $digits( $account['phone'] ) : 'شماره‌ای ثبت نشده است' ); ?></p>
					<?php $badge(); ?>
					<p class="gaccount__membership-date"><?php echo esc_html( $account['clubActive'] && $account['clubSinceText'] ? 'عضو باشگاه از ' . $account['clubSinceText'] : 'عضویت: ' . $account['memberDurationText'] ); ?></p>
				</section>
				<nav class="gaccount__nav" aria-label="بخش‌های حساب کاربری">
					<?php foreach ( $navigation as $item ) : ?>
						<a class="gaccount__nav-item<?php echo $active_tab === $item['key'] ? ' is-active' : ''; ?>" href="<?php echo esc_url( $item['url'] ); ?>"<?php if ( $active_tab === $item['key'] ) : ?> aria-current="page"<?php endif; ?>><?php $icon( $item['icon'] ); ?><span><?php echo esc_html( $item['label'] ); ?></span><?php if ( $item['count'] ) : ?><span class="gaccount__nav-count"><?php echo esc_html( $digits( $item['count'] ) ); ?></span><?php endif; ?></a>
					<?php endforeach; ?>
					<a class="gaccount__nav-item gaccount__nav-item--logout" href="<?php echo esc_url( $account['logoutUrl'] ); ?>"><?php $icon( 'logout' ); ?><span>خروج از حساب</span></a>
				</nav>
			</aside>
			<section class="gaccount__content" aria-label="<?php echo esc_attr( $navigation_labels[ $active_tab ] ); ?>">
				<?php if ( 'profile' === $active_tab ) : ?>
					<article class="gaccount__hero">
						<?php $avatar( 'hero' ); ?>
						<div class="gaccount__hero-copy">
							<div class="gaccount__hero-heading"><h2 data-account-display-name><?php echo esc_html( $account['displayName'] ); ?></h2><?php $badge(); ?></div>
							<p class="gaccount__hero-subtitle"><?php echo esc_html( 'امروز' === $account['memberDurationText'] ? 'از امروز عضو ژنوسیس هستید' : 'عضو ژنوسیس از ' . $account['memberDurationText'] ); ?></p>
							<div class="gaccount__avatar-actions"><button type="button" class="gaccount__button" data-avatar-pick>تغییر عکس</button><button type="button" class="gaccount__button" data-avatar-remove<?php disabled( ! $account['avatarUrl'] ); ?>>حذف عکس</button><input type="file" accept="image/jpeg,image/png,image/webp" data-avatar-file hidden aria-label="انتخاب عکس حساب"></div>
							<div class="gaccount__stats">
								<div class="gaccount__stat"><strong data-account-orders-count><?php echo esc_html( $digits( $account['ordersCount'] ) ); ?></strong><span>سفارش ثبت‌شده</span></div>
								<div class="gaccount__stat"><strong data-account-membership-days><?php echo esc_html( $digits( $account['daysMember'] ) ); ?></strong><span>روز عضویت</span></div>
								<?php if ( $account['clubActive'] ) : ?><div class="gaccount__stat"><strong>فعال</strong><span><?php echo esc_html( $navigation_labels['club'] ); ?></span></div><?php endif; ?>
							</div>
						</div>
					</article>
					<section class="gaccount__card gaccount__personal">
						<div class="gaccount__card-header"><h2>اطلاعات شخصی</h2><button type="button" class="gaccount__button" data-profile-edit>ویرایش اطلاعات</button></div>
						<form data-profile-form data-mode="view" novalidate>
							<div class="gaccount__fields">
								<div class="gaccount__field"><label for="gaccount-first">نام <span class="gaccount__required">*</span></label><input id="gaccount-first" name="first_name" autocomplete="given-name" value="<?php echo esc_attr( $account['firstName'] ); ?>" maxlength="100" required readonly></div>
								<div class="gaccount__field"><label for="gaccount-last">نام خانوادگی <span class="gaccount__required">*</span></label><input id="gaccount-last" name="last_name" autocomplete="family-name" value="<?php echo esc_attr( $account['lastName'] ); ?>" maxlength="100" required readonly></div>
								<div class="gaccount__field gaccount__field--wide"><label for="gaccount-email">ایمیل <small>(اختیاری)</small></label><input id="gaccount-email" name="email" type="email" autocomplete="email" value="<?php echo esc_attr( $account['email'] ); ?>" maxlength="100" dir="ltr" readonly></div>
								<div class="gaccount__field gaccount__field--wide"><label id="gaccount-birth-label">تاریخ تولد <small>(اختیاری)</small></label><div class="gaccount__birth-fields" role="group" aria-labelledby="gaccount-birth-label">
									<select name="birth_day" aria-label="روز تولد" disabled><option value="">روز</option><?php for ( $day = 1; $day <= 31; $day++ ) : ?><option value="<?php echo esc_attr( $day ); ?>"<?php selected( $account['birth']['day'], (string) $day ); ?>><?php echo esc_html( $digits( $day ) ); ?></option><?php endfor; ?></select>
									<select name="birth_month" aria-label="ماه تولد" disabled><option value="">ماه</option><?php foreach ( $months as $month => $label ) : ?><option value="<?php echo esc_attr( $month ); ?>"<?php selected( $account['birth']['month'], (string) $month ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
									<select name="birth_year" aria-label="سال تولد" disabled><option value="">سال</option><?php for ( $year = $current_year; $year >= 1200; $year-- ) : ?><option value="<?php echo esc_attr( $year ); ?>"<?php selected( $account['birth']['year'], (string) $year ); ?>><?php echo esc_html( $digits( $year ) ); ?></option><?php endfor; ?></select>
								</div></div>
							</div>
							<p class="gaccount__error" role="alert" data-profile-error hidden></p>
							<div class="gaccount__profile-actions" data-profile-actions hidden><button type="submit" class="gaccount__button gaccount__button--primary">ذخیره تغییرات</button><button type="button" class="gaccount__button" data-profile-cancel>انصراف</button></div>
						</form>
					</section>
					<section class="gaccount__card gaccount__phone-card">
						<div class="gaccount__card-header"><h2>شماره موبایل</h2><button type="button" class="gaccount__button" data-phone-open>تغییر شماره</button></div>
						<p class="gaccount__phone-description" data-account-phone-description><?php echo esc_html( $account['phoneVerified'] ? 'شماره‌ی شما برای ورود با کد تأیید و دریافت پیامک سفارش‌ها استفاده می‌شود.' : ( $account['phone'] ? 'این شماره برای تماس و اطلاع‌رسانی سفارش‌ها ثبت شده است و هنوز برای ورود تأیید نشده است.' : 'هنوز شماره موبایلی برای این حساب ثبت نشده است.' ) ); ?></p>
						<div class="gaccount__phone-summary"><strong class="gaccount__phone-number" data-account-phone><?php echo esc_html( $account['phone'] ? $digits( $account['phone'] ) : 'شماره‌ای ثبت نشده است' ); ?></strong><span class="gaccount__verified" data-account-phone-verified<?php echo $account['phoneVerified'] ? '' : ' hidden'; ?>><?php $icon( 'check' ); ?>تأیید شده</span></div>
					</section>
				<?php elseif ( 'addresses' === $active_tab ) : ?>
					<section class="gaccount__card"><div class="gaccount__card-header"><h2><?php echo esc_html( $navigation_labels['addresses'] ); ?></h2><?php if ( in_array( $address_type, array( 'billing', 'shipping' ), true ) ) : ?><a class="gaccount__button" href="<?php echo esc_url( add_query_arg( 'ga_tab', 'addresses', $account['accountUrl'] ) ); ?>">بازگشت به آدرس‌ها</a><?php endif; ?></div><?php wc_print_notices(); ?><?php if ( in_array( $address_type, array( 'billing', 'shipping' ), true ) ) : ?><div class="woocommerce gaccount__native-address"><?php WC_Shortcode_My_Account::edit_address( $address_type ); ?></div><?php else : ?><div class="gaccount__address-list"><?php foreach ( $account['addresses'] as $address ) : ?><article class="gaccount__address"><h3><?php echo esc_html( $address['label'] ); ?></h3><p><?php echo $address['exists'] ? nl2br( esc_html( $address['formatted'] ) ) : 'آدرسی ثبت نشده است.'; ?></p><a class="gaccount__button" href="<?php echo esc_url( $address['editUrl'] ); ?>"><?php echo $address['exists'] ? 'ویرایش آدرس' : 'افزودن آدرس'; ?></a></article><?php endforeach; ?></div><?php endif; ?></section>
				<?php else : ?>
					<?php
					$notification_preferences = $account['notificationPreferences'] ?? array();
					$notification_rows = array(
						'order_status_sms' => array( 'title' => 'پیامک وضعیت سفارش', 'description' => 'با هر تغییر وضعیت سفارش، پیامک دریافت کنید.' ),
						'payment_sms' => array( 'title' => 'پیامک تأیید پرداخت', 'description' => 'پس از بررسی فیش واریزی شما.' ),
						'promotions' => array( 'title' => 'پیشنهادهای ویژه و تخفیف‌ها', 'description' => 'اطلاع‌رسانی از محصولات جدید و کدهای تخفیف.' ),
						'newsletter' => array( 'title' => 'ایمیل خبرنامه', 'description' => 'گزیده‌ی مطالب و محصولات، ماهی یک‌بار.' ),
					);
					?>
					<section class="gaccount__card gaccount__preferences" data-notification-preferences aria-labelledby="gaccount-notifications-title">
						<div class="gaccount__card-header"><h2 id="gaccount-notifications-title"><?php echo esc_html( $navigation_labels['notifications'] ); ?></h2></div>
						<p class="gaccount__preferences-help">هر تغییر به‌صورت خودکار ذخیره می‌شود.</p>
						<div class="gaccount__preferences-list">
							<?php foreach ( $notification_rows as $preference => $row ) : ?>
								<div class="gaccount__preference" data-notification-row>
									<div class="gaccount__preference-copy">
										<label id="gaccount-notification-<?php echo esc_attr( $preference ); ?>-label" for="gaccount-notification-<?php echo esc_attr( $preference ); ?>"><?php echo esc_html( $row['title'] ); ?></label>
										<p id="gaccount-notification-<?php echo esc_attr( $preference ); ?>-description"><?php echo esc_html( $row['description'] ); ?></p>
										<p class="gaccount__preference-feedback" role="alert" data-notification-feedback hidden></p>
									</div>
									<span class="gaccount__preference-switch">
										<input id="gaccount-notification-<?php echo esc_attr( $preference ); ?>" type="checkbox" role="switch" name="<?php echo esc_attr( $preference ); ?>" value="1" data-notification-preference="<?php echo esc_attr( $preference ); ?>" aria-labelledby="gaccount-notification-<?php echo esc_attr( $preference ); ?>-label" aria-describedby="gaccount-notification-<?php echo esc_attr( $preference ); ?>-description"<?php checked( ! empty( $notification_preferences[ $preference ] ) ); ?> disabled>
										<span class="gaccount__preference-track" aria-hidden="true"></span>
									</span>
								</div>
							<?php endforeach; ?>
						</div>
						<p class="gaccount__preference-status screen-reader-text" role="status" aria-live="polite" data-notification-status></p>
						<noscript><p class="gaccount__error">برای ذخیره تنظیمات اعلان‌ها، جاوااسکریپت مرورگر را فعال کنید.</p></noscript>
					</section>
				<?php endif; ?>
			</section>
		</div>
	</div>
	<div class="gaccount__notice" role="status" aria-live="polite" data-account-notice hidden></div>
	<?php if ( 'profile' === $active_tab ) : ?>
		<dialog class="gaccount__dialog" data-phone-dialog aria-labelledby="gaccount-phone-title" aria-describedby="gaccount-phone-help">
			<div class="gaccount__dialog-card">
				<h2 id="gaccount-phone-title">تغییر شماره موبایل</h2>
				<p id="gaccount-phone-help" class="gaccount__dialog-description" data-phone-notice role="status">کد تأیید به شماره‌ی جدید ارسال می‌شود.</p>
				<div class="gaccount__phone-step" data-phone-step="number"><label for="gaccount-new-phone">شماره‌ی جدید</label><input id="gaccount-new-phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="09123456789" maxlength="17" data-phone-number><div class="gaccount__dialog-actions"><button type="button" class="gaccount__button gaccount__button--primary" data-phone-request>دریافت کد</button><button type="button" class="gaccount__button" data-phone-close>انصراف</button></div></div>
				<div class="gaccount__phone-step" data-phone-step="code" hidden><p class="gaccount__otp-caption">کد ۶ رقمی را برای <strong data-phone-preview-number></strong> وارد کنید.</p><div class="gaccount__otp" dir="ltr" role="group" aria-label="کد تأیید شش رقمی"><?php for ( $position = 1; $position <= 6; $position++ ) : ?><input type="text" inputmode="numeric" maxlength="<?php echo 1 === $position ? '6' : '1'; ?>" autocomplete="<?php echo 1 === $position ? 'one-time-code' : 'off'; ?>" aria-label="<?php echo esc_attr( 'رقم ' . $digits( $position ) ); ?>" data-phone-digit><?php endfor; ?></div><div class="gaccount__phone-meta"><button type="button" data-phone-back>ویرایش شماره</button><button type="button" data-phone-resend disabled>ارسال مجدد <span data-phone-timer></span></button></div><div class="gaccount__dialog-actions"><button type="button" class="gaccount__button gaccount__button--primary" data-phone-confirm>تأیید شماره</button></div></div>
			</div>
		</dialog>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
