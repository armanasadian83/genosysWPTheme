<?php
/** Customer-club administration. Membership data and permissions live in club-members.php. */
defined( 'ABSPATH' ) || exit;

function genosys_club_admin_menu() {
	if ( ! function_exists( 'genosys_club_capability' ) ) { return; }
	add_menu_page( 'باشگاه مشتریان', 'باشگاه مشتریان', genosys_club_capability(), 'genosys-club-members', 'genosys_club_admin_page', 'dashicons-groups', 57 );
}
add_action( 'admin_menu', 'genosys_club_admin_menu', 25 );

function genosys_club_admin_assets( $hook ) {
	if ( 'toplevel_page_genosys-club-members' !== $hook ) { return; }
	wp_enqueue_style( 'genosys-club-admin', get_theme_file_uri( '/assets/css/club-admin.css' ), array(), '1.5.85' );
	wp_enqueue_script( 'genosys-club-admin', get_theme_file_uri( '/assets/js/club-admin.js' ), array(), '1.5.85', true );
	wp_localize_script( 'genosys-club-admin', 'genosysClubAdmin', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'genosys_club_admin' ), 'smsCanConfigure' => current_user_can( 'manage_options' ) && ! defined( 'GENOSYS_CLUB_SMS_SENDER' ), 'smsSender' => function_exists( 'genosys_club_sms_sender' ) ? genosys_club_sms_sender() : '' ) );
}
add_action( 'admin_enqueue_scripts', 'genosys_club_admin_assets' );

function genosys_club_admin_body_class( $classes ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	return $screen && 'toplevel_page_genosys-club-members' === $screen->id ? $classes . ' genosys-club-adminpage' : $classes;
}
add_filter( 'admin_body_class', 'genosys_club_admin_body_class' );

function genosys_club_admin_icons() {
	$icons = array(
		'people' => '<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 4a3 3 0 0 1 0 6m3 11v-3a6 6 0 0 0-3-5"/>',
		'sparkle' => '<path d="m12 3 2.6 6.4L21 12l-6.4 2.6L12 21l-2.6-6.4L3 12l6.4-2.6L12 3Z"/>',
		'star' => '<path d="m12 3 2.8 5.8 6.4.9-4.6 4.5 1.1 6.4-5.7-3-5.7 3 1.1-6.4L2.8 9.7l6.4-.9L12 3Z"/>',
		'chart' => '<path d="M4 20V10m5 10V4m5 16v-8m5 8V7"/>',
		'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
		'download' => '<path d="M12 3v12m-5-5 5 5 5-5M5 19v2h14v-2"/>',
		'chevron' => '<path d="m6 9 6 6 6-6"/>',
		'left' => '<path d="m15 5-7 7 7 7"/>',
		'right' => '<path d="m9 5 7 7-7 7"/>',
		'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
		'check' => '<path d="m5 12 4 4L19 6"/>',
	);
	echo '<svg class="gca__sprite" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><defs>';
	foreach ( $icons as $name => $paths ) { echo '<symbol id="gca-i-' . esc_attr( $name ) . '" viewBox="0 0 24 24">' . $paths . '</symbol>'; }
	echo '</defs></svg>';
}

function genosys_club_admin_page() {
	if ( ! function_exists( 'genosys_club_can_manage' ) || ! genosys_club_can_manage() ) { wp_die( 'اجازه دسترسی به این صفحه را ندارید.', '', array( 'response' => 403 ) ); }
	$context = genosys_club_admin_context();
	?>
	<main class="gca" data-club-admin dir="rtl">
		<?php genosys_club_admin_icons(); ?>
		<header class="gca__header"><div><h1>اعضای باشگاه مشتریان</h1><p>اعضایی که پرسشنامه‌ی باشگاه را تکمیل کرده‌اند؛ برای دیدن جزئیات هر عضو روی او کلیک کنید.</p></div><div class="gca__header-actions"><button class="gca__button gca__button--outline" type="button" data-club-sms-bulk>ارسال پیامک گروهی</button><a class="gca__button" data-club-export href="<?php echo esc_url( $context['exportUrl'] ?? '' ); ?>"><svg aria-hidden="true"><use href="#gca-i-download"></use></svg>خروجی CSV</a></div></header>
		<section class="gca__stats" aria-label="آمار اعضای باشگاه" data-club-stats></section>
		<section class="gca__panel" aria-label="فهرست اعضا">
			<div class="gca__tabs" role="group" aria-label="وضعیت عضویت"><button type="button" class="gca__tab is-active" data-club-status="active" aria-pressed="true">اعضای فعال <span data-club-count="active"></span></button><button type="button" class="gca__tab" data-club-status="cancelled" aria-pressed="false">لغو شده <span data-club-count="cancelled"></span></button></div>
			<div class="gca__filters"><label class="gca__search"><svg aria-hidden="true"><use href="#gca-i-search"></use></svg><span class="gca__sr-only">جستجوی عضو</span><input type="search" data-club-filter="search" placeholder="جستجو در نام، موبایل، ایمیل" autocomplete="off"></label><div class="gca__select"><label class="gca__sr-only" for="gca-filter-audience">نوع عضو</label><select id="gca-filter-audience" data-club-filter="audience"><option value="">همه‌ی اعضا</option><option value="consumer">مصرف کننده</option><option value="clinic">کلینیک / بیوتی تراپیست</option></select></div><div class="gca__select"><label class="gca__sr-only" for="gca-filter-skin">نوع پوست</label><select id="gca-filter-skin" data-club-filter="skin"><option value="">همه‌ی انواع پوست</option></select></div><div class="gca__select"><label class="gca__sr-only" for="gca-filter-city">شهر</label><select id="gca-filter-city" data-club-filter="city"><option value="">همه‌ی شهرها</option></select></div><div class="gca__select"><label class="gca__sr-only" for="gca-filter-rating">رضایت</label><select id="gca-filter-rating" data-club-filter="rating"><option value="">همه‌ی رضایت‌ها</option><option value="5">۵ ستاره</option><option value="4">۴ ستاره</option><option value="3">۳ ستاره</option><option value="2">۲ ستاره</option><option value="1">۱ ستاره</option></select></div><div class="gca__select"><label class="gca__sr-only" for="gca-filter-source">منبع آشنایی</label><select id="gca-filter-source" data-club-filter="source"><option value="">همه‌ی منابع آشنایی</option></select></div><div class="gca__select"><label class="gca__sr-only" for="gca-filter-sort">ترتیب نمایش</label><select id="gca-filter-sort" data-club-filter="sort"><option value="newest">جدیدترین عضو</option><option value="oldest">قدیمی‌ترین عضو</option></select></div><p class="gca__result" data-club-results role="status"></p></div>
			<div class="gca__list-message" data-club-list-message role="alert" hidden></div>
			<div class="gca__table-scroll" data-club-table-scroll><table class="gca__table"><thead><tr><th scope="col">عضو</th><th scope="col">نوع عضو</th><th scope="col">شهر</th><th scope="col">نوع پوست و دغدغه / خدمات</th><th scope="col">رضایت</th><th scope="col">سفارش‌ها</th><th scope="col">تاریخ عضویت</th><th scope="col"><span class="gca__sr-only">عملیات</span></th></tr></thead><tbody data-club-members></tbody></table></div>
			<nav class="gca__pagination" data-club-pagination aria-label="صفحه‌های اعضا"></nav>
		</section>
		<p class="gca__toast" role="status" data-club-notice hidden></p>
		<noscript><p class="gca__no-script">برای جستجو و مشاهده‌ی جزئیات اعضا، جاوااسکریپت مرورگر را فعال کنید.</p></noscript>
		<dialog class="gca__drawer" data-club-drawer aria-labelledby="gca-detail-title"><header class="gca__drawer-header"><h2 id="gca-detail-title">جزئیات عضو</h2><div class="gca__drawer-navigation"><button class="gca__round" type="button" data-club-next aria-label="عضو بعدی"><svg aria-hidden="true"><use href="#gca-i-right"></use></svg></button><button class="gca__round" type="button" data-club-prev aria-label="عضو قبلی"><svg aria-hidden="true"><use href="#gca-i-left"></use></svg></button><button class="gca__round" type="button" data-club-close aria-label="بستن جزئیات"><svg aria-hidden="true"><use href="#gca-i-close"></use></svg></button></div></header><div class="gca__drawer-body" data-club-detail></div></dialog>
		<dialog class="gca__sms-dialog" data-club-sms-dialog aria-labelledby="gca-sms-title"><header><h2 id="gca-sms-title">ارسال پیامک</h2><button class="gca__round" type="button" data-club-sms-close aria-label="بستن"><svg aria-hidden="true"><use href="#gca-i-close"></use></svg></button></header><p data-club-sms-recipients role="status">در حال بررسی گیرندگان…</p><p class="gca__sms-hint" data-club-sms-provider hidden></p><label for="gca-sms-text">متن پیامک</label><textarea id="gca-sms-text" rows="5" maxlength="1000" data-club-sms-text placeholder="متن پیامک را بنویسید…"></textarea><div class="gca__sms-meta"><span data-club-sms-length>۰ / ۱۰۰۰</span><span>فقط اعضای موافق دریافت پیامک</span></div><p data-club-sms-review hidden></p><p class="gca__sms-error" data-club-sms-error role="alert" hidden></p><div class="gca__sms-actions"><button class="gca__button" type="button" data-club-sms-review-button disabled>بررسی ارسال</button><button class="gca__button" type="button" data-club-sms-confirm hidden>تأیید و ارسال پیامک</button><button class="gca__button gca__button--outline" type="button" data-club-sms-cancel>انصراف</button></div><?php if ( current_user_can( 'manage_options' ) && ! defined( 'GENOSYS_CLUB_SMS_SENDER' ) ) : ?><details class="gca__sms-settings"><summary>تنظیم خط فرستنده</summary><label class="gca__sr-only" for="gca-sms-sender">شماره خط فرستنده</label><div class="gca__sender-fields"><input id="gca-sms-sender" type="text" inputmode="numeric" maxlength="24" data-club-sms-sender placeholder="شماره خط فرستنده" value="<?php echo esc_attr( function_exists( 'genosys_club_sms_sender' ) ? genosys_club_sms_sender() : '' ); ?>"><button class="gca__button gca__button--outline" type="button" data-club-sms-sender-save>ذخیره خط فرستنده</button></div><p>کلید API از تنظیمات سرویس پیامک قالب خوانده می‌شود.</p></details><?php endif; ?></dialog>
		<script type="application/json" data-club-initial><?php echo wp_json_encode( $context, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>
	</main>
	<?php
}
