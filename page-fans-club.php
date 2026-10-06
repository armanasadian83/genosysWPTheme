<?php
/** The customer club questionnaire is visual-only; no registration is submitted. */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main" class="fans-club-page" tabindex="-1">
	<section class="fans-club-hero" aria-labelledby="fans-club-title">
		<div class="fans-club-hero__content">
			<span class="fans-club-hero__eyebrow">باشگاه مشتریان</span>
			<h1 id="fans-club-title">عضو خانواده‌ی <span>ژنوسیس</span> شوید</h1>
			<p>پرسشنامه‌ی کوتاه زیر را تکمیل کنید تا مراقبت‌ها و پیشنهادهای ویژه‌ی باشگاه را<br class="fans-club-desktop-break"> متناسب با پوست شما آماده کنیم.</p>
		</div>
	</section>
	<section class="fans-club-band" aria-label="عضویت در باشگاه مشتریان ژنوسیس">
		<div class="fans-club-layout">
			<div class="fans-club-form-card" id="fans-club-form-card">
				<header class="fans-club-form-card__header">
					<h2>پرسشنامه‌ی عضویت</h2>
					<p>حدود ۲ دقیقه زمان می‌برد. موارد دارای ستاره الزامی است.</p>
				</header>
				<ol class="fans-club-progress" aria-label="مراحل پرسشنامه">
					<li class="is-current" data-progress="0" aria-current="step"><span class="fans-club-progress__bar"></span><span>۱. اطلاعات شما</span></li>
					<li data-progress="1"><span class="fans-club-progress__bar"></span><span>۲. پوست و علایق</span></li>
					<li data-progress="2"><span class="fans-club-progress__bar"></span><span>۳. نظر شما</span></li>
				</ol>
				<form class="fans-club-form" id="fans-club-form" novalidate>
					<div class="fans-club-step" data-step="0">
						<div class="fans-club-field">
							<label for="fans-name">نام و نام خانوادگی <span class="fans-club-required">*</span></label>
							<input id="fans-name" name="full_name" type="text" autocomplete="name" aria-required="true" aria-describedby="fans-name-error">
							<p class="fans-club-error" id="fans-name-error" hidden>نام خود را وارد کنید.</p>
						</div>
						<div class="fans-club-field-grid">
							<div class="fans-club-field">
								<label for="fans-phone">شماره موبایل <span class="fans-club-required">*</span></label>
								<input id="fans-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="09123456789" aria-required="true" aria-describedby="fans-phone-error">
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
						<div class="fans-club-actions"><button class="fans-club-button fans-club-button--primary" type="button" data-register>ثبت عضویت</button><button class="fans-club-button fans-club-button--secondary" type="button" data-prev>قبلی</button></div>
					</div>
				</form>
			</div>
			<aside class="fans-club-benefits" aria-label="مزایای باشگاه مشتریان">
				<div class="fans-club-benefit"><span class="fans-club-benefit__icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10h18v11H3zM2 7h20v4H2zM12 7v14M12 7H8.4C6 7 5 6 5 4.6 5 3.3 6 2.5 7.2 2.5 9.1 2.5 11 5.3 12 7Zm0 0h3.6C18 7 19 6 19 4.6c0-1.3-1-2.1-2.2-2.1C14.9 2.5 13 5.3 12 7Z" /></svg></span><span><strong>هدیه و تخفیف اختصاصی</strong><small>پیشنهادهای ویژه‌ی اعضای باشگاه</small></span></div>
				<div class="fans-club-benefit"><span class="fans-club-benefit__icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7" r="3.2"/><path d="M5 21v-2.1c0-4.1 2.5-6.2 7-6.2s7 2.1 7 6.2V21"/></svg></span><span><strong>مشاوره‌ی تخصصی پوست</strong><small>پیشنهاد محصول بر اساس نیاز شما</small></span></div>
				<div class="fans-club-benefit"><span class="fans-club-benefit__icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2.6 2.8 5.8 6.4.9-4.6 4.5 1.1 6.4-5.7-3-5.7 3 1.1-6.4-4.6-4.5 6.4-.9Z"/></svg></span><span><strong>دسترسی زودتر به محصولات جدید</strong><small>اولین نفری باشید که امتحان می‌کنید</small></span></div>
			</aside>
		</div>
	</section>
</main>
<?php get_footer(); ?>
