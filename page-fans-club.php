<?php
/** Consumer and professional club enrollment questionnaires. */
defined( 'ABSPATH' ) || exit;
$fans_club_context = genosys_club_enrollment_context();
$fans_club_can_submit = ! empty( $fans_club_context['canSubmit'] );
$fans_club_types = $fans_club_context['audiences'];
$fans_club_phone = (string) $fans_club_context['phone'];
$fans_club_name = (string) $fans_club_context['fullName'];
$fans_club_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
$fans_club_back_url = wp_validate_redirect( wp_get_referer() ?: home_url( '/' ), home_url( '/' ) );
$fans_club_audience = 'consumer' === genosys_site_setting( 'club_default_audience', 'clinic' ) ? 'consumer' : 'clinic';
if ( empty( $fans_club_types[ $fans_club_audience ]['canSubmit'] ) ) {
	$available_type = 'consumer' === $fans_club_audience ? 'clinic' : 'consumer';
	if ( ! empty( $fans_club_types[ $available_type ]['canSubmit'] ) ) { $fans_club_audience = $available_type; }
}
$fans_club_selected_available = ! empty( $fans_club_types[ $fans_club_audience ]['canSubmit'] );
$fans_club_is_clinic = 'clinic' === $fans_club_audience;
$fans_club_copy = static function ( $consumer_key, $clinic_key ) use ( $fans_club_is_clinic ) {
	$consumer = (string) genosys_site_setting( $consumer_key );
	$clinic = (string) genosys_site_setting( $clinic_key );
	echo ' data-consumer="' . esc_attr( $consumer ) . '" data-clinic="' . esc_attr( $clinic ) . '">' . esc_html( $fans_club_is_clinic ? $clinic : $consumer );
};
$fans_club_membership_panel = static function ( $audience ) use ( $fans_club_types, $fans_club_account_url, $fans_club_context ) {
	$state = $fans_club_types[ $audience ];
	$other = 'consumer' === $audience ? 'clinic' : 'consumer';
	?>
	<section class="fans-club-membership" data-fans-membership="<?php echo esc_attr( $audience ); ?>" aria-labelledby="fans-club-membership-<?php echo esc_attr( $audience ); ?>"<?php if ( ! empty( $state['canSubmit'] ) ) : ?> hidden<?php endif; ?>>
		<span class="fans-club-membership__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9Z"/><path d="m9 11.9 2 2 4-4"/></svg></span>
		<h2 id="fans-club-membership-<?php echo esc_attr( $audience ); ?>" tabindex="-1"><?php echo 'consumer' === $audience ? 'عضویت مصرف کننده' : 'عضویت کلینیک / بیوتی تراپیست'; ?></h2>
		<p class="fans-club-membership__message" data-fans-membership-message role="status" aria-live="polite"><?php echo esc_html( (string) ( $state['message'] ?: ( $fans_club_context['message'] ?: 'برای بررسی وضعیت عضویت، صفحه را دوباره بارگذاری کنید.' ) ) ); ?></p>
		<p class="fans-club-membership__note">هر حساب و شماره موبایل در هر نوع عضویت، فقط یک بار امکان ثبت‌نام دارد.</p>
		<div class="fans-club-membership__actions"><a class="fans-club-button fans-club-button--primary" href="<?php echo esc_url( home_url( '/fans-club/' ) ); ?>" data-fans-reload hidden>شروع پرسشنامه</a><button class="fans-club-button fans-club-button--primary" type="button" data-fans-switch-to="<?php echo esc_attr( $other ); ?>"<?php if ( empty( $fans_club_types[ $other ]['canSubmit'] ) ) : ?> hidden<?php endif; ?>><?php echo 'clinic' === $other ? 'عضویت کلینیک / بیوتی تراپیست' : 'عضویت مصرف کننده'; ?></button><a class="fans-club-button fans-club-button--secondary" href="<?php echo esc_url( $fans_club_account_url ); ?>">حساب کاربری</a></div>
	</section>
	<?php
};
get_header();
?>
<main id="main" class="fans-club-page<?php echo $fans_club_selected_available ? '' : ' fans-club-page--membership'; ?>" tabindex="-1" data-fans-club data-audience="<?php echo esc_attr( $fans_club_audience ); ?>" data-enrollment-state="<?php echo $fans_club_can_submit ? 'available' : 'unavailable'; ?>">
	<nav class="fans-club-topbar" aria-label="بازگشت"><a class="fans-club-back" href="<?php echo esc_url( $fans_club_back_url ); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg><span>بازگشت</span></a></nav>
	<section class="fans-club-hero" aria-labelledby="<?php echo $fans_club_selected_available ? 'fans-club-title' : 'fans-club-member-title'; ?>">
		<div class="fans-club-hero__content">
			<div class="fans-club-audience" role="tablist" aria-label="نوع عضویت در باشگاه">
				<?php foreach ( array( 'consumer', 'clinic' ) as $type ) : $registered = ! empty( $fans_club_types[ $type ]['alreadySubmitted'] ); ?>
				<button type="button" role="tab" id="fans-audience-<?php echo esc_attr( $type ); ?>" aria-controls="fans-club-<?php echo esc_attr( $type ); ?>" aria-selected="<?php echo $type === $fans_club_audience ? 'true' : 'false'; ?>" tabindex="<?php echo $type === $fans_club_audience ? '0' : '-1'; ?>" data-fans-audience="<?php echo esc_attr( $type ); ?>" data-registered="<?php echo $registered ? 'true' : 'false'; ?>"><span><?php echo esc_html( genosys_site_setting( 'consumer' === $type ? 'club_consumer_label' : 'club_clinic_label', 'consumer' === $type ? 'مصرف کننده' : 'کلینیک ها و بیوتی تراپیست ها' ) ); ?></span><small data-fans-registration-badge<?php if ( ! $registered ) : ?> hidden<?php endif; ?>>ثبت شده</small></button>
				<?php endforeach; ?>
			</div>
			<div data-fans-hero-join<?php if ( ! $fans_club_selected_available ) : ?> hidden<?php endif; ?>>
			<span class="fans-club-hero__eyebrow" data-fans-copy<?php $fans_club_copy( 'club_eyebrow', 'clinic_eyebrow' ); ?></span>
			<h1 id="fans-club-title"><span class="fans-club-title-context" data-fans-copy<?php $fans_club_copy( 'club_title_before', 'clinic_title_before' ); ?></span> <span data-fans-copy<?php $fans_club_copy( 'club_title_highlight', 'clinic_title_highlight' ); ?></span> <span class="fans-club-title-context" data-fans-copy<?php $fans_club_copy( 'club_title_after', 'clinic_title_after' ); ?></span></h1>
			<p><span data-fans-copy<?php $fans_club_copy( 'club_description_line1', 'clinic_description_line1' ); ?></span><br class="fans-club-desktop-break"> <span data-fans-copy<?php $fans_club_copy( 'club_description_line2', 'clinic_description_line2' ); ?></span></p>
			</div>
			<div data-fans-hero-member<?php if ( $fans_club_selected_available ) : ?> hidden<?php endif; ?>>
			<span class="fans-club-hero__eyebrow">باشگاه مشتریان ژنوسیس</span>
			<h1 id="fans-club-member-title"><span class="fans-club-title-context">همراه</span> <span>ژنوسیس</span></h1>
			<p>اطلاعات عضویت شما در این بخش نمایش داده می‌شود.</p>
			</div>
		</div>
	</section>
	<section class="fans-club-band" aria-label="عضویت در باشگاه مشتریان ژنوسیس">
		<div class="fans-club-layout">
			<div class="fans-club-form-card" id="fans-club-form-card">
				<div class="fans-club-panel" id="fans-club-consumer" data-fans-panel="consumer" role="tabpanel" aria-labelledby="fans-audience-consumer"<?php if ( $fans_club_is_clinic ) : ?> hidden<?php endif; ?>>
				<?php $fans_club_membership_panel( 'consumer' ); ?>
				<?php if ( ! empty( $fans_club_types['consumer']['canSubmit'] ) ) : ?>
				<header class="fans-club-form-card__header">
					<h2><?php echo esc_html( genosys_site_setting( 'club_form_title' ) ); ?></h2>
					<p><?php echo esc_html( genosys_site_setting( 'club_form_description' ) ); ?></p>
				</header>
				<ol class="fans-club-progress" aria-label="مراحل پرسشنامه">
					<li class="is-current" data-progress="0" aria-current="step"><span class="fans-club-progress__bar"></span><span>۱. اطلاعات شما</span></li>
					<li data-progress="1"><span class="fans-club-progress__bar"></span><span>۲. پوست و علایق</span></li>
					<li data-progress="2"><span class="fans-club-progress__bar"></span><span>۳. نظر شما</span></li>
				</ol>
				<form class="fans-club-form" id="fans-club-form" data-fans-wizard="consumer" novalidate>
					<label class="fans-club-honeypot" aria-hidden="true">وب‌سایت<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
					<div class="fans-club-step" data-step="0">
						<div class="fans-club-field">
							<label for="fans-name">نام و نام خانوادگی <span class="fans-club-required">*</span></label>
							<input id="fans-name" name="full_name" type="text" value="<?php echo esc_attr( $fans_club_name ); ?>" autocomplete="name" aria-required="true" aria-describedby="fans-name-error">
							<p class="fans-club-error" id="fans-name-error" hidden>نام خود را وارد کنید.</p>
						</div>
						<div class="fans-club-field-grid">
							<div class="fans-club-field">
								<label for="fans-phone">شماره موبایل <span class="fans-club-required">*</span></label>
								<input id="fans-phone" name="phone" type="tel" value="<?php echo esc_attr( $fans_club_phone ); ?>" inputmode="tel" autocomplete="tel" readonly aria-readonly="true" data-fans-account-phone aria-required="true" aria-describedby="fans-phone-help fans-phone-error">
								<p class="fans-club-field-help" id="fans-phone-help">شماره تأییدشده‌ی حساب کاربری شما؛ در این فرم قابل تغییر نیست.</p>
								<p class="fans-club-error" id="fans-phone-error" hidden>شماره موبایل معتبر نیست (مثل 09123456789).</p>
							</div>
							<div class="fans-club-field">
								<label for="fans-email">ایمیل</label>
								<input id="fans-email" name="email" type="email" autocomplete="email">
							</div>
						</div>
						<div class="fans-club-field-grid">
							<div class="fans-club-field">
								<label for="fans-city">شهر</label>
								<input id="fans-city" name="city" type="text" autocomplete="address-level2">
							</div>
							<div class="fans-club-field">
								<label for="fans-age">بازه‌ی سنی</label>
								<span class="fans-club-select"><select id="fans-age" name="age_range"><option value="">انتخاب کنید</option><option value="under-18">زیر ۱۸ سال</option><option value="18-24">۱۸ تا ۲۴ سال</option><option value="25-34">۲۵ تا ۳۴ سال</option><option value="35-44">۳۵ تا ۴۴ سال</option><option value="45-54">۴۵ تا ۵۴ سال</option><option value="55-plus">۵۵ سال به بالا</option></select></span>
							</div>
						</div>
						<div class="fans-club-actions"><button class="fans-club-button fans-club-button--primary" type="button" data-next>مرحله‌ی بعد</button></div>
					</div>
					<div class="fans-club-step" data-step="1" hidden>
						<fieldset class="fans-club-choice-group" data-choice-group="skin" data-single="true">
							<legend>نوع پوست شما <span class="fans-club-required">*</span></legend>
							<div class="fans-club-choices">
								<button type="button" aria-pressed="false">نرمال</button><button type="button" aria-pressed="false">خشک</button><button type="button" aria-pressed="false">چرب</button><button type="button" aria-pressed="false">مختلط</button><button type="button" aria-pressed="false">حساس</button>
							</div>
							<p class="fans-club-error" id="fans-skin-error" hidden>نوع پوست خود را انتخاب کنید.</p>
						</fieldset>
						<fieldset class="fans-club-choice-group" data-choice-group="concerns">
							<legend>دغدغه‌های اصلی شما</legend>
							<div class="fans-club-choices">
								<button type="button" aria-pressed="false">لک و یکدستی</button><button type="button" aria-pressed="false">چین و چروک</button><button type="button" aria-pressed="false">آکنه و جوش</button><button type="button" aria-pressed="false">خشکی و کدری</button><button type="button" aria-pressed="false">ریزش مو</button><button type="button" aria-pressed="false">آفتاب‌سوختگی</button><button type="button" aria-pressed="false">حساسیت و قرمزی</button>
							</div>
						</fieldset>
						<fieldset class="fans-club-choice-group" data-choice-group="categories">
							<legend>از کدام دسته محصولات استفاده می‌کنید؟</legend>
							<div class="fans-club-choices">
								<button type="button" aria-pressed="false">مراقبت پوست</button><button type="button" aria-pressed="false">مراقبت مو و پوست سر</button><button type="button" aria-pressed="false">ضد آفتاب</button><button type="button" aria-pressed="false">دور چشم</button><button type="button" aria-pressed="false">آرایشی</button>
							</div>
						</fieldset>
						<div class="fans-club-actions"><button class="fans-club-button fans-club-button--primary" type="button" data-next>مرحله‌ی بعد</button><button class="fans-club-button fans-club-button--secondary" type="button" data-prev>قبلی</button></div>
					</div>
					<div class="fans-club-step" data-step="2" hidden>
						<fieldset class="fans-club-rating-field">
							<legend>میزان رضایت شما از ژنوسیس <span class="fans-club-required">*</span></legend>
							<div class="fans-club-stars" role="radiogroup" aria-label="میزان رضایت از یک تا پنج ستاره">
								<button type="button" role="radio" aria-checked="false" aria-label="۱ ستاره" data-rating="1">★</button><button type="button" role="radio" aria-checked="false" aria-label="۲ ستاره" data-rating="2">★</button><button type="button" role="radio" aria-checked="false" aria-label="۳ ستاره" data-rating="3">★</button><button type="button" role="radio" aria-checked="false" aria-label="۴ ستاره" data-rating="4">★</button><button type="button" role="radio" aria-checked="false" aria-label="۵ ستاره" data-rating="5">★</button>
							</div>
							<p class="fans-club-error" id="fans-rating-error" hidden>میزان رضایت خود را انتخاب کنید.</p>
						</fieldset>
						<div class="fans-club-range-field">
							<label for="fans-recommend">چقدر ما را به دوستانتان پیشنهاد می‌کنید؟ (۰ تا ۱۰)</label>
							<div class="fans-club-range-row"><output id="fans-recommend-value" for="fans-recommend">۸</output><input id="fans-recommend" type="range" min="0" max="10" value="8" style="--range-fill: 80%"></div>
						</div>
						<div class="fans-club-field">
							<label for="fans-source">از کجا با ژنوسیس آشنا شدید؟</label>
							<span class="fans-club-select"><select id="fans-source" name="source"><option value="">انتخاب کنید</option><option value="instagram">اینستاگرام</option><option value="friends">دوستان و آشنایان</option><option value="search">جستجوی اینترنتی</option><option value="store">فروشگاه یا کلینیک</option><option value="other">سایر</option></select></span>
						</div>
						<div class="fans-club-field">
							<label for="fans-message">نظر یا پیشنهاد شما</label>
							<textarea id="fans-message" name="message" rows="5"></textarea>
						</div>
						<label class="fans-club-consent"><input id="fans-consent" type="checkbox"><span>با دریافت پیامک و اطلاعیه‌های باشگاه مشتریان موافقم. <span class="fans-club-required">*</span></span></label>
						<p class="fans-club-error" id="fans-consent-error" hidden>برای ادامه، موافقت خود را مشخص کنید.</p>
						<div class="fans-club-actions"><button class="fans-club-button fans-club-button--primary" type="button" data-register>ثبت عضویت</button><button class="fans-club-button fans-club-button--secondary" type="button" data-prev>قبلی</button></div>
					</div>
				</form>
				<p class="fans-club-feedback" role="status" data-fans-feedback hidden></p>
				<?php endif; ?>
				</div>
				<div class="fans-club-panel" id="fans-club-clinic" data-fans-panel="clinic" role="tabpanel" aria-labelledby="fans-audience-clinic"<?php if ( ! $fans_club_is_clinic ) : ?> hidden<?php endif; ?>>
					<?php $fans_club_membership_panel( 'clinic' ); ?>
					<?php if ( ! empty( $fans_club_types['clinic']['canSubmit'] ) ) : ?>
					<header class="fans-club-form-card__header"><h2><?php echo esc_html( genosys_site_setting( 'clinic_form_title' ) ); ?></h2><p><?php echo esc_html( genosys_site_setting( 'clinic_form_description' ) ); ?></p></header>
					<ol class="fans-club-progress" aria-label="مراحل پرسشنامه‌ی همکاران"><li class="is-current" data-progress="0" aria-current="step"><span class="fans-club-progress__bar"></span><span>۱. اطلاعات مجموعه</span></li><li data-progress="1"><span class="fans-club-progress__bar"></span><span>۲. خدمات و تجربه</span></li><li data-progress="2"><span class="fans-club-progress__bar"></span><span>۳. مشتریان و مصرف</span></li></ol>
					<form class="fans-club-form" id="fans-clinic-form" data-fans-wizard="clinic" novalidate>
						<label class="fans-club-honeypot" aria-hidden="true">وب‌سایت<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
						<div class="fans-club-step" data-step="0">
							<div class="fans-club-field"><label for="clinic-name">نام کلینیک یا نام حرفه‌ای شما <span class="fans-club-required">*</span></label><input id="clinic-name" name="clinic_name" type="text" autocomplete="organization" maxlength="150" required aria-describedby="clinic-name-error"><p class="fans-club-error" id="clinic-name-error" hidden>نام کلینیک یا نام حرفه‌ای خود را وارد کنید.</p></div>
							<div class="fans-club-field"><label for="clinic-contact">نام و نام خانوادگی مسئول <span class="fans-club-required">*</span></label><input id="clinic-contact" name="contact_name" type="text" value="<?php echo esc_attr( $fans_club_name ); ?>" autocomplete="name" maxlength="150" required aria-describedby="clinic-contact-error"><p class="fans-club-error" id="clinic-contact-error" hidden>نام مسئول مجموعه یا بیوتی تراپیست را وارد کنید.</p></div>
							<div class="fans-club-field-grid">
								<div class="fans-club-field"><label for="clinic-phone">شماره موبایل مسئول <span class="fans-club-required">*</span></label><input id="clinic-phone" name="contact_phone" type="tel" value="<?php echo esc_attr( $fans_club_phone ); ?>" inputmode="tel" autocomplete="mobile tel" maxlength="20" required readonly aria-readonly="true" data-fans-account-phone aria-describedby="clinic-phone-help clinic-phone-error"><p class="fans-club-field-help" id="clinic-phone-help">شماره تأییدشده‌ی حساب کاربری شما؛ در این فرم قابل تغییر نیست.</p><p class="fans-club-error" id="clinic-phone-error" hidden>شماره موبایل معتبر وارد کنید (مثل 09123456789).</p></div>
								<div class="fans-club-field"><label for="clinic-landline">شماره ثابت کلینیک <small>(با پیش‌شماره)</small></label><input id="clinic-landline" name="clinic_landline" type="tel" inputmode="tel" autocomplete="work tel" placeholder="02112345678" maxlength="20" aria-describedby="clinic-landline-error"><p class="fans-club-error" id="clinic-landline-error" hidden>شماره ثابت را همراه پیش‌شماره وارد کنید (مثل 02112345678).</p></div>
							</div>
							<div class="fans-club-field"><label for="clinic-city">شهر فعالیت</label><input id="clinic-city" name="clinic_city" type="text" autocomplete="address-level2" maxlength="100"></div>
							<div class="fans-club-actions"><button class="fans-club-button fans-club-button--primary" type="button" data-next>مرحله‌ی بعد</button></div>
						</div>
						<div class="fans-club-step" data-step="1" hidden>
							<div class="fans-club-field-grid">
								<div class="fans-club-field"><label for="clinic-activity">نوع فعالیت</label><span class="fans-club-select"><select id="clinic-activity" name="activity"><option value="">انتخاب کنید</option><option value="clinic">کلینیک زیبایی</option><option value="therapist">بیوتی تراپیست مستقل</option><option value="facial-center">مرکز فیشیال و مراقبت پوست</option><option value="other">سایر</option></select></span></div>
								<div class="fans-club-field"><label for="clinic-experience">سابقه کاری <span class="fans-club-required">*</span></label><span class="fans-club-select"><select id="clinic-experience" name="experience" required aria-describedby="clinic-experience-error"><option value="">انتخاب کنید</option><option value="starting">در حال راه‌اندازی</option><option value="under-one">کمتر از ۱ سال</option><option value="one-three">۱ تا ۳ سال</option><option value="three-five">۳ تا ۵ سال</option><option value="five-ten">۵ تا ۱۰ سال</option><option value="over-ten">بیشتر از ۱۰ سال</option></select></span><p class="fans-club-error" id="clinic-experience-error" hidden>سابقه کاری خود را انتخاب کنید.</p></div>
							</div>
							<fieldset class="fans-club-choice-group" data-choice-group="clinic-services" aria-describedby="clinic-services-error"><legend>نوع خدمات شما <span class="fans-club-required">*</span></legend><div class="fans-club-choices"><button type="button" aria-pressed="false">فیشیال و پاکسازی</button><button type="button" aria-pressed="false">جوانسازی پوست</button><button type="button" aria-pressed="false">خدمات لک و آکنه</button><button type="button" aria-pressed="false">مراقبت مو و پوست سر</button><button type="button" aria-pressed="false">مراقبت پس از خدمات زیبایی</button><button type="button" aria-pressed="false">سایر</button></div><p class="fans-club-error" id="clinic-services-error" hidden>حداقل یک نوع خدمت را انتخاب کنید.</p></fieldset>
							<div class="fans-club-actions"><button class="fans-club-button fans-club-button--primary" type="button" data-next>مرحله‌ی بعد</button><button class="fans-club-button fans-club-button--secondary" type="button" data-prev>قبلی</button></div>
						</div>
						<div class="fans-club-step" data-step="2" hidden>
							<fieldset class="fans-club-choice-group" data-choice-group="clinic-clients" aria-describedby="clinic-clients-error"><legend>مشتریان شما بیشتر چه نیازهایی دارند؟ <span class="fans-club-required">*</span></legend><div class="fans-club-choices"><button type="button" aria-pressed="false">آبرسانی و مراقبت روتین</button><button type="button" aria-pressed="false">لک و تیرگی</button><button type="button" aria-pressed="false">آکنه و چربی پوست</button><button type="button" aria-pressed="false">چین و چروک</button><button type="button" aria-pressed="false">پوست حساس</button><button type="button" aria-pressed="false">مراقبت مو و پوست سر</button><button type="button" aria-pressed="false">سایر</button></div><p class="fans-club-error" id="clinic-clients-error" hidden>حداقل یک نیاز مشتریان خود را انتخاب کنید.</p></fieldset>
							<div class="fans-club-field-grid">
								<div class="fans-club-field"><label for="clinic-consumption">مصرف ماهانه محصولات <span class="fans-club-required">*</span></label><span class="fans-club-select"><select id="clinic-consumption" name="monthly_consumption" required aria-describedby="clinic-consumption-error"><option value="">انتخاب کنید</option><option value="under-five">کمتر از ۵ عدد در ماه</option><option value="five-ten">۵ تا ۱۰ عدد در ماه</option><option value="eleven-twenty">۱۱ تا ۲۰ عدد در ماه</option><option value="over-twenty">بیشتر از ۲۰ عدد در ماه</option><option value="unknown">هنوز برآوردی ندارم</option></select></span><p class="fans-club-error" id="clinic-consumption-error" hidden>میزان مصرف ماهانه محصولات را انتخاب کنید.</p></div>
								<div class="fans-club-field"><label for="clinic-visits">تعداد مشتریان در ماه</label><span class="fans-club-select"><select id="clinic-visits" name="monthly_clients"><option value="">انتخاب کنید</option><option value="under-twenty">تا ۲۰ نفر</option><option value="twenty-fifty">۲۱ تا ۵۰ نفر</option><option value="fifty-hundred">۵۱ تا ۱۰۰ نفر</option><option value="hundred-twohundred">۱۰۱ تا ۲۰۰ نفر</option><option value="over-twohundred">بیشتر از ۲۰۰ نفر</option></select></span></div>
							</div>
							<div class="fans-club-field"><label for="clinic-message">نیازها یا توضیحات تکمیلی مجموعه</label><textarea id="clinic-message" name="clinic_message" rows="4" maxlength="3000"></textarea></div>
							<label class="fans-club-consent"><input id="clinic-consent" type="checkbox" required aria-describedby="clinic-consent-error"><span>با دریافت اطلاعات همکاری و پیشنهادهای حرفه‌ای ژنوسیس موافقم. <span class="fans-club-required">*</span></span></label><p class="fans-club-error" id="clinic-consent-error" hidden>برای ادامه، موافقت خود را با دریافت اطلاعات همکاری مشخص کنید.</p>
							<div class="fans-club-actions"><button class="fans-club-button fans-club-button--primary" type="button" data-register>تکمیل پرسشنامه</button><button class="fans-club-button fans-club-button--secondary" type="button" data-prev>قبلی</button></div>
						</div>
						</form>
					<p class="fans-club-feedback" role="status" data-fans-feedback hidden></p>
				<?php endif; ?>
				</div>
			</div>
			<aside class="fans-club-benefits" aria-label="مزایای باشگاه مشتریان">
				<div class="fans-club-benefit"><span class="fans-club-benefit__icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10h18v11H3zM2 7h20v4H2zM12 7v14M12 7H8.4C6 7 5 6 5 4.6 5 3.3 6 2.5 7.2 2.5 9.1 2.5 11 5.3 12 7Zm0 0h3.6C18 7 19 6 19 4.6c0-1.3-1-2.1-2.2-2.1C14.9 2.5 13 5.3 12 7Z" /></svg></span><span><strong data-fans-copy data-consumer="هدیه و تخفیف اختصاصی" data-clinic="پیشنهادهای ویژه‌ی همکاران">پیشنهادهای ویژه‌ی همکاران</strong><small data-fans-copy data-consumer="پیشنهادهای ویژه‌ی اعضای باشگاه" data-clinic="متناسب با حجم مصرف و نیاز مجموعه‌ی شما">متناسب با حجم مصرف و نیاز مجموعه‌ی شما</small></span></div>
				<div class="fans-club-benefit"><span class="fans-club-benefit__icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7" r="3.2"/><path d="M5 21v-2.1c0-4.1 2.5-6.2 7-6.2s7 2.1 7 6.2V21"/></svg></span><span><strong data-fans-copy data-consumer="مشاوره‌ی تخصصی پوست" data-clinic="راهنمای انتخاب محصولات حرفه‌ای">راهنمای انتخاب محصولات حرفه‌ای</strong><small data-fans-copy data-consumer="پیشنهاد محصول بر اساس نیاز شما" data-clinic="هماهنگ با نوع خدمات و نیاز مشتریان شما">هماهنگ با نوع خدمات و نیاز مشتریان شما</small></span></div>
				<div class="fans-club-benefit"><span class="fans-club-benefit__icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2.6 2.8 5.8 6.4.9-4.6 4.5 1.1 6.4-5.7-3-5.7 3 1.1-6.4-4.6-4.5 6.4-.9Z"/></svg></span><span><strong data-fans-copy data-consumer="دسترسی زودتر به محصولات جدید" data-clinic="آشنایی با محصولات و پروتکل‌ها">آشنایی با محصولات و پروتکل‌ها</strong><small data-fans-copy data-consumer="اولین نفری باشید که امتحان می‌کنید" data-clinic="برای تکمیل خدمات تخصصی کلینیک و بیوتی تراپیست‌ها">برای تکمیل خدمات تخصصی کلینیک و بیوتی تراپیست‌ها</small></span></div>
			</aside>
		</div>
	</section>
	<script type="application/json" data-fans-context><?php echo wp_json_encode( $fans_club_context, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>
</main>
<?php wp_footer(); ?>
</body>
</html>
