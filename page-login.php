<?php
/** Phone and verification-code screens for SMS sign-in. */
defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<main id="main" class="genosys-login" dir="rtl">
	<div class="genosys-login__background" aria-hidden="true">
		<svg viewBox="0 0 1920 880" preserveAspectRatio="none" focusable="false">
			<path fill="#fff" d="M0 806C387 923 926 723 1335 658c231-37 428-76 585-128v350H0Z"/>
			<path fill="#cc6d82" d="M0 743C398 843 915 706 1294 657c264-35 469-84 626-154v67c-292 76-472 120-633 146C859 785 420 923 0 813Z"/>
		</svg>
	</div>
	<section class="genosys-login__card" aria-labelledby="genosys-login-title" data-login-card>
		<header class="genosys-login__header">
			<img class="genosys-login__mark" src="<?php echo esc_url( get_theme_file_uri( '/assets/images/genosys-mark.svg' ) ); ?>" width="76" height="76" alt="">
			<div>
				<h1 id="genosys-login-title" data-login-title>ورود به حساب کاربری</h1>
				<p data-login-subtitle>به دنیای ژنوسیس خوش آمدید</p>
			</div>
		</header>

		<form class="genosys-login__form" id="genosys-phone-form" novalidate>
			<p class="genosys-login__lead">شماره تماس خود را وارد کنید تا کد تأیید برای شما ارسال شود.</p>
			<div class="genosys-login__field">
				<label for="genosys-login-phone">شماره تماس</label>
				<div class="genosys-login__phone-wrap">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M11 18.5h2"/></svg>
					<input id="genosys-login-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel-national" placeholder="09123456789" maxlength="16" aria-describedby="genosys-phone-error" aria-invalid="false">
				</div>
				<p class="genosys-login__error" id="genosys-phone-error" role="alert" hidden>شماره تماس معتبر نیست (مثال: 09123456789).</p>
			</div>
			<button class="genosys-login__primary" type="submit">دریافت کد</button>
		</form>

		<form class="genosys-login__form genosys-login__form--code" id="genosys-code-form" novalidate hidden>
			<p class="genosys-login__lead">کد ۶ رقمی ارسال‌شده به <strong id="genosys-code-phone" dir="ltr"></strong> را وارد کنید.</p>
			<div class="genosys-login__code-fields" dir="ltr" role="group" aria-label="کد تأیید ۶ رقمی" aria-describedby="genosys-code-error">
				<input type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" maxlength="1" aria-label="رقم اول کد تأیید">
				<input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="رقم دوم کد تأیید">
				<input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="رقم سوم کد تأیید">
				<input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="رقم چهارم کد تأیید">
				<input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="رقم پنجم کد تأیید">
				<input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="رقم ششم کد تأیید">
			</div>
			<p class="genosys-login__error genosys-login__code-error" id="genosys-code-error" role="alert" hidden>کد ۶ رقمی را کامل وارد کنید.</p>
			<div class="genosys-login__code-actions">
				<button type="button" class="genosys-login__text-button" id="genosys-edit-phone">ویرایش شماره</button>
				<button type="button" class="genosys-login__resend" id="genosys-resend-code" disabled>ارسال مجدد کد <span id="genosys-code-timer" dir="ltr">۰۲:۰۰</span></button>
			</div>
			<button class="genosys-login__primary" type="submit">تأیید شماره</button>
		</form>

		<form class="genosys-login__form genosys-login__form--profile" id="genosys-profile-form" novalidate hidden>
			<p class="genosys-login__lead">برای ساخت حساب کاربری، نام و نام خانوادگی خود را وارد کنید.</p>
			<div class="genosys-login__field">
				<label for="genosys-profile-first-name">نام</label>
				<div class="genosys-login__input-wrap">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="7" r="3.5"/><path d="M4.5 21v-2a7.5 7.5 0 0 1 15 0v2"/></svg>
					<input id="genosys-profile-first-name" name="first_name" type="text" autocomplete="given-name" maxlength="60" aria-describedby="genosys-profile-first-name-error" aria-invalid="false" required>
				</div>
				<p class="genosys-login__error" id="genosys-profile-first-name-error" role="alert" hidden>نام را وارد کنید.</p>
			</div>
			<div class="genosys-login__field">
				<label for="genosys-profile-last-name">نام خانوادگی</label>
				<div class="genosys-login__input-wrap">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="7" r="3.5"/><path d="M4.5 21v-2a7.5 7.5 0 0 1 15 0v2"/></svg>
					<input id="genosys-profile-last-name" name="last_name" type="text" autocomplete="family-name" maxlength="60" aria-describedby="genosys-profile-last-name-error" aria-invalid="false" required>
				</div>
				<p class="genosys-login__error" id="genosys-profile-last-name-error" role="alert" hidden>نام خانوادگی را وارد کنید.</p>
			</div>
			<div class="genosys-login__field">
				<label for="genosys-profile-email">ایمیل <span class="genosys-login__optional">(اختیاری)</span></label>
				<div class="genosys-login__input-wrap">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m3 6 9 7 9-7"/></svg>
					<input id="genosys-profile-email" name="email" type="email" inputmode="email" autocomplete="email" placeholder="name@example.com" maxlength="100" aria-describedby="genosys-profile-email-error" aria-invalid="false">
				</div>
				<p class="genosys-login__error" id="genosys-profile-email-error" role="alert" hidden>ایمیل معتبر وارد کنید.</p>
			</div>
			<p class="genosys-login__error genosys-login__profile-error" id="genosys-profile-error" role="alert" hidden></p>
			<button class="genosys-login__primary" type="submit">ثبت و ادامه</button>
		</form>

		<div class="genosys-login__brand" aria-label="GENOSYS · Gene Re-birth System">
			<img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/genosys-logo-1.1.1.svg' ) ); ?>" width="90" height="20" alt="GENOSYS">
			<span aria-hidden="true">· Gene Re-birth System</span>
		</div>
	</section>
</main>
<?php wp_footer(); ?>
</body>
</html>
