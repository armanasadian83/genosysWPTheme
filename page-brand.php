<?php
/** The Genosys brand: complementary professional skin care. */
defined( 'ABSPATH' ) || exit;
$brand_video = genosys_brand_uploaded_video();
get_header();
?>
<main id="main" class="gs-brand" tabindex="-1">
	<h1 id="gs-brand-hero-title" class="screen-reader-text">برند ژنوسیس</h1>
	<section class="gs-brand-hero" aria-labelledby="gs-brand-hero-title">
		<img class="gs-brand-hero__poster" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/brand-film-1.5.73.jpg' ); ?>" alt="" width="1280" height="720" fetchpriority="high" decoding="async">
		<?php if ( $brand_video ) : ?>
			<div class="gs-brand-hero__video" aria-hidden="true"><video class="gs-brand-hero__hosted" data-brand-hosted-video autoplay muted loop playsinline preload="auto" tabindex="-1" aria-hidden="true" inert controlslist="nodownload nofullscreen noremoteplayback" disablepictureinpicture disableremoteplayback poster="<?php echo esc_url( get_template_directory_uri() . '/assets/images/brand-film-1.5.73.jpg' ); ?>"><source src="<?php echo esc_url( $brand_video['url'] ); ?>" type="<?php echo esc_attr( $brand_video['type'] ); ?>"></video></div>
		<?php else : ?>
			<div class="gs-brand-hero__video" data-brand-background-video="1044239323" aria-hidden="true"></div>
		<?php endif; ?>
		<div class="gs-brand-hero__shade" aria-hidden="true"></div>
	</section>
	<section class="gs-brand-banner" aria-labelledby="gs-brand-title">
		<img class="gs-brand-banner__image" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/brand-banner-1.5.72.webp' ); ?>" alt="مراقبت از پوست با ابزار میکرونیدلینگ ژنوسیس" width="1920" height="1080" loading="lazy" decoding="async">
		<div class="gs-brand-banner__content">
			<div class="gs-brand-treatments">
				<section class="gs-brand-treatment" aria-labelledby="gs-brand-internal-title" dir="rtl">
					<h2 id="gs-brand-internal-title">درمان از درون<br><span>(میکرونیدلینگ)</span></h2>
					<p>تحریک طبیعی تولید کلاژن<br>سامانهٔ انتقال از راه پوست</p>
				</section>
				<section class="gs-brand-treatment" aria-labelledby="gs-brand-external-title" dir="rtl">
					<h2 id="gs-brand-external-title">مراقبت از بیرون<br><span>(محصولات آرایشی و مراقبتی)</span></h2>
					<p>فرمولاسیون سازگار با پوست<br>ترکیبات مؤثر با عملکرد بالا</p>
				</section>
			</div>
			<a class="gs-brand-next" href="#gs-brand-introduction" aria-label="دربارهٔ برند ژنوسیس بیشتر بخوانید"><svg viewBox="0 0 72 40" fill="none" aria-hidden="true" focusable="false"><path d="m4 4 32 31L68 4" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
			<h2 id="gs-brand-title">مقابله با پیری پوست</h2>
			<p class="gs-brand-banner__description">میکرونیدلینگ روشی مؤثر است که برای رفع دغدغه‌های پوستی، مانند پیری پوست یا جای زخم، ابداع شده است. این روش با ایجاد زخم‌های بسیار ریز در پوست و فعال‌کردن فرایند ترمیم طبیعی آن، به بازسازی پوست و تولید طبیعی کلاژن کمک می‌کند. همچنین با افزایش چشمگیر نفوذپذیری پوست برای جذب مواد مغذی، به مقابله با پیری پوست می‌انجامد.</p>
		</div>
	</section>
	<section id="gs-brand-introduction" class="gs-brand-introduction" aria-labelledby="gs-brand-introduction-title" tabindex="-1">
		<p class="gs-brand-eyebrow">دربارهٔ ژنوسیس</p>
		<h2 id="gs-brand-introduction-title">سامانهٔ باززایی ژنی</h2>
		<p class="gs-brand-introduction__subtitle">نخستین برند تخصصی میکرونیدلینگ در جهان</p>
		<p class="gs-brand-introduction__description">ژنوسیس نخستین برند تخصصی میکرونیدلینگ در جهان است؛ برندی که از ترکیب میکرونیدلینگ با محصولات آرایشی‌درمانی، با فرمولاسیونی ویژهٔ این روش، پدید آمده است تا اثربخشی مراقبت از پوست را به بهترین سطح برساند.</p>
	</section>
	<section class="gs-brand-features gs-brand-shell" aria-label="رویکرد مکمل ژنوسیس به مراقبت از پوست">
		<article class="gs-brand-treatment-row" aria-labelledby="gs-brand-internal-feature-title">
			<div class="gs-brand-treatment-copy"><h2 id="gs-brand-internal-feature-title">درمان از درون <span>(میکرونیدلینگ)</span></h2><ul><li>تحریک فرایند طبیعی ترمیم پوست و کمک به تولید کلاژن.</li><li>ایجاد مسیرهای ریز برای افزایش نفوذپذیری پوست و رساندن مواد مغذی.</li></ul></div>
			<div class="gs-brand-media">
				<div class="gs-brand-video"><button class="gs-brand-video__button" type="button" data-brand-video="1029545187" data-brand-video-title="درمان از درون با میکرونیدلینگ" aria-label="پخش ویدئوی درمان از درون با میکرونیدلینگ" disabled><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/brand-internal-1.5.73.jpg' ); ?>" alt="" width="1280" height="720" loading="lazy" decoding="async"><span class="gs-brand-video__play" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" focusable="false"><path d="m9 5 10 7-10 7Z" fill="currentColor"/></svg></span><span class="gs-brand-video__label">پخش ویدئو</span></button></div>
				<a class="gs-brand-video__fallback" href="https://vimeo.com/1029545187" target="_blank" rel="noopener noreferrer">تماشای ویدئو</a>
			</div>
		</article>
		<article class="gs-brand-treatment-row gs-brand-treatment-row--reverse" aria-labelledby="gs-brand-external-feature-title">
			<div class="gs-brand-treatment-copy"><h2 id="gs-brand-external-feature-title">مراقبت از بیرون <span>(محصولات آرایشی و مراقبتی)</span></h2><ul><li>فرمولاسیون بدون افزودنی‌های مضر، متناسب با نفوذ عمیق‌تر ترکیبات پس از میکرونیدلینگ.</li><li>استفاده از ترکیبات مؤثر برای تکمیل نتیجهٔ مراقبت از پوست.</li></ul></div>
			<div class="gs-brand-media">
				<div class="gs-brand-video"><button class="gs-brand-video__button" type="button" data-brand-video="1030958117" data-brand-video-title="مراقبت از بیرون با محصولات ژنوسیس" aria-label="پخش ویدئوی مراقبت از بیرون با محصولات ژنوسیس" disabled><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/brand-external-1.5.73.jpg' ); ?>" alt="" width="1280" height="720" loading="lazy" decoding="async"><span class="gs-brand-video__play" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" focusable="false"><path d="m9 5 10 7-10 7Z" fill="currentColor"/></svg></span><span class="gs-brand-video__label">پخش ویدئو</span></button></div>
				<a class="gs-brand-video__fallback" href="https://vimeo.com/1030958117" target="_blank" rel="noopener noreferrer">تماشای ویدئو</a>
			</div>
		</article>
	</section>
	<section class="gs-brand-comparisons gs-brand-shell" aria-labelledby="gs-brand-comparisons-title">
		<header class="gs-brand-comparisons__header"><h2 id="gs-brand-comparisons-title">مقایسه‌های ارائه‌شده توسط ژنوسیس</h2><p>بازنمایی نمودارهای معرفی برند</p></header>
		<div class="gs-brand-comparison-panels">
			<article class="gs-brand-comparison" aria-labelledby="gs-brand-collagen-title">
				<h3 id="gs-brand-collagen-title">تحریک طبیعی تولید کلاژن</h3>
				<figure class="gs-brand-histology"><div class="gs-brand-histology__crop"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/brand-histology-1.5.73.png' ); ?>" alt="مقایسهٔ تصاویر بافت‌شناسی گروه کنترل، آی‌پی‌ال و ژنوسیس، از چپ به راست" width="388" height="121" loading="lazy" decoding="async"></div><div class="gs-brand-histology__labels" aria-hidden="true"><span>گروه کنترل</span><span>آی‌پی‌ال</span><span>ژنوسیس</span></div><figcaption>بررسی بافت‌شناسی با رنگ‌آمیزی ام‌تی، بزرگ‌نمایی ۱۰۰ برابر</figcaption></figure>
				<figure class="gs-brand-bar-figure"><div class="gs-brand-bars gs-brand-bars--collagen" role="img" aria-label="بازنمایی نمودار مقایسهٔ کلاژن در گروه کنترل، آی‌پی‌ال و ژنوسیس"><div class="gs-brand-bar" aria-hidden="true"><span class="gs-brand-bar__column"></span><span class="gs-brand-bar__label">گروه کنترل</span></div><div class="gs-brand-bar" aria-hidden="true"><span class="gs-brand-bar__column"></span><span class="gs-brand-bar__label">آی‌پی‌ال</span></div><div class="gs-brand-bar" aria-hidden="true"><span class="gs-brand-bar__column"></span><span class="gs-brand-bar__label">ژنوسیس</span></div></div><figcaption>تحلیل کمّی کلاژن</figcaption></figure>
			</article>
			<article class="gs-brand-comparison" aria-labelledby="gs-brand-delivery-title">
				<h3 id="gs-brand-delivery-title">انتقال مواد مغذی از راه پوست</h3>
				<figure class="gs-brand-delivery"><div class="gs-brand-delivery__legend"><span><i class="gs-brand-key gs-brand-key--red" aria-hidden="true"></i>ژنوسیس</span><span><i class="gs-brand-key gs-brand-key--blue" aria-hidden="true"></i><bdi dir="ltr">NON</bdi></span></div>
					<svg class="gs-brand-delivery__chart" viewBox="0 0 352 204" role="img" aria-labelledby="gs-brand-delivery-chart-title gs-brand-delivery-chart-desc" focusable="false"><title id="gs-brand-delivery-chart-title">نمودار عمق نفوذ در گذر زمان</title><desc id="gs-brand-delivery-chart-desc">بازنمایی مسیرهای نمودار ژنوسیس و NON در معرفی برند؛ محور افقی زمان از صفر تا ۶۰ دقیقه و محور عمودی عمق نفوذ از صفر تا ۳۵۰۰ میکرومتر است.</desc>
						<g class="gs-brand-chart-grid" fill="none"><path d="M60 15H311M60 36H311M60 56H311M60 77H311M60 98H311M60 119H311M60 140H311"/><path class="gs-brand-chart-axis" d="M60 6V154H311"/></g>
						<g class="gs-brand-chart-ticks" text-anchor="end"><text x="53" y="19">۳۵۰۰</text><text x="53" y="40">۳۰۰۰</text><text x="53" y="60">۲۵۰۰</text><text x="53" y="81">۲۰۰۰</text><text x="53" y="102">۱۵۰۰</text><text x="53" y="123">۱۰۰۰</text><text x="53" y="144">۵۰۰</text><text x="53" y="158">۰</text></g>
						<g class="gs-brand-chart-ticks" text-anchor="middle"><text x="60" y="171">۰</text><text x="102" y="171">۱۰</text><text x="144" y="171">۲۰</text><text x="185" y="171">۳۰</text><text x="227" y="171">۴۰</text><text x="269" y="171">۵۰</text><text x="311" y="171">۶۰</text></g>
						<g class="gs-brand-chart-labels" text-anchor="middle"><text x="187" y="196" direction="rtl">زمان (دقیقه)</text><text x="14" y="83" transform="rotate(-90 14 83)" direction="rtl">عمق نفوذ (میکرومتر)</text></g>
						<g class="gs-brand-chart-genosys" transform="translate(80 36)" fill="none" stroke="currentColor"><path d="M0.705078 99.9198L20.121 90.079L58.872 68.9823L96.9278 78.4264L135.841 21.4305L174.341 11.1818L212.346 1.00146" stroke-width="1.3"/><g fill="currentColor" stroke="none"><rect x="-1.8" y="97.4" width="5" height="5"/><rect x="17.6" y="87.6" width="5" height="5"/><rect x="56.4" y="66.5" width="5" height="5"/><rect x="94.4" y="75.9" width="5" height="5"/><rect x="133.3" y="18.9" width="5" height="5"/><rect x="171.8" y="8.7" width="5" height="5"/><rect x="209.8" y="-1.5" width="5" height="5"/></g></g>
						<g transform="translate(80 129)" fill="none" stroke="#00aeef"><path d="M0.705078 16.1088L20.121 18.9905L58.872 17.407L96.9278 12.4253L135.841 8.03416L174.341 2.87838L212.346 0.336182" stroke-width="1.3"/><g fill="#00aeef" stroke="none"><circle cx=".705078" cy="16.1088" r="2.6"/><circle cx="20.121" cy="18.9905" r="2.6"/><circle cx="58.872" cy="17.407" r="2.6"/><circle cx="96.9278" cy="12.4253" r="2.6"/><circle cx="135.841" cy="8.03416" r="2.6"/><circle cx="174.341" cy="2.87838" r="2.6"/><circle cx="212.346" cy=".336182" r="2.6"/></g></g>
					</svg><figcaption>عمق نفوذ در گذر زمان</figcaption>
				</figure>
				<figure class="gs-brand-bar-figure gs-brand-bar-figure--absorption"><div class="gs-brand-bars gs-brand-bars--absorption" role="img" aria-label="بازنمایی نمودار مقایسهٔ جذب با مرطوب‌کننده، لیپوزوم، یونتوفورزیس و ژنوسیس"><div class="gs-brand-bar" aria-hidden="true"><span class="gs-brand-bar__column"></span><span class="gs-brand-bar__label">مرطوب‌کننده</span></div><div class="gs-brand-bar" aria-hidden="true"><span class="gs-brand-bar__column"></span><span class="gs-brand-bar__label">لیپوزوم</span></div><div class="gs-brand-bar" aria-hidden="true"><span class="gs-brand-bar__column"></span><span class="gs-brand-bar__label">یونتوفورزیس</span></div><div class="gs-brand-bar" aria-hidden="true"><span class="gs-brand-bar__column"></span><span class="gs-brand-bar__label">ژنوسیس</span></div></div><figcaption>جذب</figcaption></figure>
			</article>
		</div>
	</section>
	<section class="gs-brand-closing" aria-labelledby="gs-brand-closing-title"><h2 id="gs-brand-closing-title">مقابله با پیری پوست</h2><p>بازسازی پوست، تولید طبیعی کلاژن و جذب بهتر مواد مغذی؛ سه بخش مکمل در رویکرد ژنوسیس به مراقبت از پوست.</p><a class="gs-brand-source" href="http://genosys.info/en/page/11" target="_blank" rel="noopener noreferrer">منبع: معرفی برند ژنوسیس</a></section>
</main>
<?php get_footer(); ?>
