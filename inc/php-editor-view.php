<?php
/** Presentation for the PHP source editor. Reads, checks and writes use its API. */
defined( 'ABSPATH' ) || exit;

/** Outline icons shared by the source editor controls. */
function genosys_php_editor_icon( $name ) {
	$extra = array(
		'code'   => '<path d="m8 6-6 6 6 6m8-12 6 6-6 6m-3-15-2 18"/>',
		'eye'    => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
		'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
		'info'   => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10v.01"/>',
		'check'  => '<path d="m5 12 4 4L19 6"/>',
		'chevron'=> '<path d="m6 9 6 6 6-6"/>',
	);
	if ( isset( $extra[ $name ] ) ) {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $extra[ $name ] . '</svg>';
	}
	return function_exists( 'genosys_css_editor_icon' ) ? genosys_css_editor_icon( $name ) : '';
}

/**
 * Render real theme PHP files, never a sample source or an executable draft.
 *
 * @param array  $files       Source file manifest from the protected editor API.
 * @param string $preview_url Signed read-only front-end preview URL.
 */
function genosys_render_php_editor( $files = array(), $preview_url = '' ) {
	$first_file = ! empty( $files[0]['path'] ) ? (string) $files[0]['path'] : '';
	$preview_url = $preview_url ?: home_url( '/' );
	$groups = array(
		'root'           => array( 'label' => 'فایل‌های اصلی قالب', 'files' => array() ),
		'inc'            => array( 'label' => 'inc/', 'files' => array() ),
		'template-parts' => array( 'label' => 'template-parts/', 'files' => array() ),
		'woocommerce'    => array( 'label' => 'woocommerce/', 'files' => array() ),
	);
	foreach ( $files as $file ) {
		$path = isset( $file['path'] ) ? (string) $file['path'] : '';
		$folder = strpos( $path, '/' ) !== false ? strtok( $path, '/' ) : 'root';
		$key = isset( $groups[ $folder ] ) ? $folder : 'root';
		$groups[ $key ]['files'][] = $file;
	}
	$checks = array(
		'syntax'       => 'ساختار و سینتکس',
		'calls'        => 'توابع و پارامترها',
		'dependencies' => 'وابستگی فایل‌ها',
		'safety'       => 'ساختارهای قابل بررسی',
		'runtime'      => 'بررسی سرور',
	);
	?>
	<div class="gsce gspe" data-php-editor dir="rtl">
		<header class="gsce__heading gspe__heading">
			<div><h2>ویرایشگر <span lang="en" dir="ltr">PHP</span></h2><p>فایل‌های PHP قالب را ببینید و ویرایش کنید؛ کد پیش از ذخیره بررسی می‌شود.</p></div>
			<span class="gsce__save-state" data-php-save-status role="status" aria-live="polite">در حال بارگذاری فایل‌ها…</span>
		</header>
		<div class="gsce__message" data-php-message role="status" aria-live="polite" hidden></div>
		<div class="gsce__message gspe__readonly-note" data-php-readonly-note role="note" hidden>این فایل محافظت‌شده یا فقط‌خواندنی است؛ کد آن قابل مشاهده است و ذخیره‌سازی آن در دسترس نیست.</div>
		<div class="gspe__workspace">
			<aside class="gsce__files gspe__files" aria-label="فایل‌های PHP قالب">
				<button type="button" class="gsce__panel-resize gsce__panel-resize--files gspe__files-resize" data-php-files-resize aria-label="تغییر عرض فهرست فایل‌های PHP با کشیدن لبهٔ چپ" title="لبهٔ چپ را برای تغییر عرض فهرست فایل‌ها بکشید"><span aria-hidden="true"></span></button>
				<h3>فایل‌های PHP قالب</h3>
				<label class="gspe__search" for="genosys-php-file-search"><span><?php echo genosys_php_editor_icon( 'search' ); ?></span><input id="genosys-php-file-search" data-php-file-search type="search" placeholder="جستجوی فایل…" aria-label="جستجوی فایل PHP" autocomplete="off"></label>
				<div class="gsce__file-list gspe__file-list" role="tablist" aria-label="فایل‌های PHP" aria-orientation="vertical">
					<?php foreach ( $groups as $group_key => $group ) : if ( empty( $group['files'] ) ) { continue; } ?>
						<div class="gspe__file-group" data-php-group="<?php echo esc_attr( $group_key ); ?>">
							<h4<?php echo 'root' !== $group_key ? ' dir="ltr"' : ''; ?>><?php echo esc_html( $group['label'] ); ?></h4>
							<?php foreach ( $group['files'] as $file ) :
								$path = isset( $file['path'] ) ? (string) $file['path'] : '';
								$label = ! empty( $file['label'] ) ? (string) $file['label'] : basename( $path );
								$is_current = $path === $first_file;
								?>
								<button type="button" class="gsce__file gspe__file" data-php-file="<?php echo esc_attr( $path ); ?>" role="tab" aria-selected="<?php echo $is_current ? 'true' : 'false'; ?>" aria-controls="genosys-php-editor-panel" tabindex="<?php echo $is_current ? '0' : '-1'; ?>" title="<?php echo esc_attr( $path ); ?>">
									<span class="gsce__file-icon"><?php echo genosys_php_editor_icon( 'file' ); ?></span>
									<span class="gsce__file-info"><strong><?php echo esc_html( $label ); ?></strong><code dir="ltr"><?php echo esc_html( $path ); ?></code><?php if ( 'functions.php' === $path ) : ?><small class="gspe__file-risk" title="این فایل توابع اصلی قالب را بارگذاری می‌کند">توابع اصلی قالب</small><?php endif; ?></span>
									<span class="gspe__file-diagnostic" data-php-file-diagnostic data-file-error hidden aria-label="نتیجهٔ بررسی فایل"></span>
									<span class="gsce__file-dirty" data-file-dirty data-php-file-dirty hidden aria-label="ذخیره‌نشده"></span>
								</button>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				</div>
				<p class="gspe__empty-files" data-php-file-empty hidden>فایلی با این نام پیدا نشد.</p>
				<div class="gsce__help gspe__help"><p>ذخیره: <kbd>Ctrl</kbd> + <kbd>S</kbd></p><p>تورفتگی: <kbd>Tab</kbd></p><p>پیش‌نویس PHP اجرا نمی‌شود؛ پیش‌نمایش، کد ذخیره‌شدهٔ سایت را نشان می‌دهد.</p></div>
			</aside>

			<section class="gsce__editor gspe__editor" id="genosys-php-editor-panel" aria-label="ویرایش کد PHP">
				<div class="gsce__toolbar gspe__toolbar">
					<code class="gsce__current-file" data-php-current-file dir="ltr"><?php echo esc_html( $first_file ); ?></code>
					<div class="gsce__tools" role="group" aria-label="ابزارهای ویرایش کد PHP">
						<button type="button" class="gsce__icon-button" data-php-action="undo" title="واگرد" aria-label="واگرد"><?php echo genosys_php_editor_icon( 'undo' ); ?></button>
						<button type="button" class="gsce__icon-button" data-php-action="redo" title="از نو" aria-label="از نو"><?php echo genosys_php_editor_icon( 'redo' ); ?></button>
						<button type="button" class="gsce__icon-button" data-php-action="copy" title="کپی کد" aria-label="کپی کد"><?php echo genosys_php_editor_icon( 'copy' ); ?></button>
						<button type="button" class="gsce__icon-button" data-php-action="download" title="دانلود فایل PHP" aria-label="دانلود فایل PHP"><?php echo genosys_php_editor_icon( 'download' ); ?></button>
					</div>
					<label class="screen-reader-text" for="genosys-php-history">تاریخچه نسخه‌ها</label><select id="genosys-php-history" class="gsce__select" data-php-history aria-label="تاریخچه نسخه‌ها"><option value="">تاریخچهٔ نسخه‌ها</option></select>
					<div class="gsce__tools" role="group" aria-label="نمایش ویرایشگر PHP">
						<button type="button" class="gsce__icon-button gsce__font-button" data-php-action="font-decrease" title="کوچک کردن متن" aria-label="کوچک کردن متن">−A</button>
						<button type="button" class="gsce__icon-button gsce__font-button" data-php-action="font-increase" title="بزرگ کردن متن" aria-label="بزرگ کردن متن">+A</button>
						<button type="button" class="gsce__icon-button" data-php-action="wrap" title="شکستن خط‌های بلند" aria-label="شکستن خط‌های بلند" aria-pressed="false"><?php echo genosys_php_editor_icon( 'wrap' ); ?></button>
						<button type="button" class="gsce__icon-button" data-php-action="theme" title="تغییر روشنایی ویرایشگر" aria-label="تغییر روشنایی ویرایشگر" aria-pressed="false"><?php echo genosys_php_editor_icon( 'moon' ); ?></button>
						<button type="button" class="gsce__icon-button" data-php-action="fullscreen" title="تمام‌صفحه کردن ویرایشگر" aria-label="تمام‌صفحه کردن ویرایشگر" aria-pressed="false"><?php echo genosys_php_editor_icon( 'fullscreen' ); ?></button>
					</div>
					<button type="button" class="gsce__button gsce__button--reset" data-php-action="reset" title="بازیابی کد اصلی فایل انتخاب‌شده از نسخهٔ نصب‌شده؛ برای اعمال دائمی ذخیره‌سازی را بزنید">بازنشانی</button>
					<button type="button" class="gsce__button gsce__button--save" data-php-action="save" title="بررسی و ذخیرهٔ فایل انتخاب‌شده" disabled>ذخیرهٔ تغییرات</button>
				</div>
				<nav class="gspe__tabs" role="tablist" aria-label="کد و پیش‌نمایش">
					<button type="button" class="gspe__tab" data-php-tab="code" role="tab" id="genosys-php-tab-code" aria-selected="true" aria-controls="genosys-php-code-panel"><?php echo genosys_php_editor_icon( 'code' ); ?><span>کد</span></button>
					<button type="button" class="gspe__tab" data-php-tab="preview" role="tab" id="genosys-php-tab-preview" aria-selected="false" aria-controls="genosys-php-preview-panel" tabindex="-1"><?php echo genosys_php_editor_icon( 'eye' ); ?><span>پیش‌نمایش</span></button>
				</nav>
				<div class="gspe__tab-panel" data-php-tab-panel="code" id="genosys-php-code-panel" role="tabpanel" aria-labelledby="genosys-php-tab-code">
					<div class="gsce__code-area gspe__code-area"><label class="screen-reader-text" for="genosys-php-code">کد PHP فایل انتخاب‌شده</label><textarea id="genosys-php-code" class="gsce__code" data-php-code dir="ltr" spellcheck="false" autocapitalize="off" autocomplete="off" wrap="off" aria-describedby="genosys-php-editor-note"></textarea></div>
					<footer class="gsce__statusbar"><span data-php-cursor>سطر ۱، ستون ۱</span><span data-php-validation role="status" aria-live="polite">در انتظار بارگذاری کد</span><span class="gsce__status-note" id="genosys-php-editor-note">پیش‌نویس ذخیره نشده است</span></footer>
				</div>
				<div class="gspe__tab-panel gspe__preview-panel" data-php-tab-panel="preview" id="genosys-php-preview-panel" role="tabpanel" aria-labelledby="genosys-php-tab-preview" hidden>
					<section class="gsce__preview gspe__preview" aria-label="پیش‌نمایش نسخهٔ ذخیره‌شدهٔ سایت">
						<header class="gsce__preview-heading"><h3>پیش‌نمایش زنده <small class="gspe__saved-site">کد ذخیره‌شدهٔ سایت</small></h3><div class="gsce__tools" role="group" aria-label="اندازه پیش‌نمایش"><button type="button" class="gsce__icon-button" data-php-action="preview-desktop" aria-label="نمایش دسکتاپ" title="نمایش دسکتاپ" aria-pressed="true"><?php echo genosys_php_editor_icon( 'desktop' ); ?></button><button type="button" class="gsce__icon-button" data-php-action="preview-mobile" aria-label="نمایش موبایل" title="نمایش موبایل" aria-pressed="false"><?php echo genosys_php_editor_icon( 'mobile' ); ?></button><button type="button" class="gsce__icon-button" data-php-action="preview-fullscreen" aria-label="نمایش تمام‌عرض پیش‌نمایش" title="نمایش تمام‌عرض" aria-pressed="false"><?php echo genosys_php_editor_icon( 'fullscreen' ); ?></button></div></header>
						<p class="gspe__preview-disclosure">پیش‌نمایش، نسخهٔ ذخیره‌شدهٔ سایت را نشان می‌دهد. برای دیدن تغییرات PHP، بررسی و ذخیره‌سازی لازم است؛ پیش‌نویس اجرا نمی‌شود.</p>
						<div class="gsce__preview-controls gspe__preview-controls">
							<label class="screen-reader-text" for="genosys-php-preview-page">صفحه پیش‌نمایش</label><select id="genosys-php-preview-page" class="gsce__select" data-php-preview-page><option value="<?php echo esc_url( home_url( '/' ) ); ?>">صفحه اصلی</option></select>
							<div class="gsce__preview-address"><label class="screen-reader-text" for="genosys-php-preview-path">مسیر صفحه در همین وب‌سایت</label><input id="genosys-php-preview-path" data-php-preview-path type="text" value="/" placeholder="/shop/" dir="ltr" aria-label="مسیر صفحه در همین وب‌سایت"><button type="button" class="gsce__icon-button" data-php-action="preview-open" title="نمایش این مسیر" aria-label="نمایش این مسیر"><?php echo genosys_php_editor_icon( 'arrow' ); ?></button><button type="button" class="gsce__icon-button" data-php-action="preview-reload" title="بارگذاری دوباره پیش‌نمایش" aria-label="بارگذاری دوباره پیش‌نمایش"><?php echo genosys_php_editor_icon( 'reload' ); ?></button></div>
							<div class="gsce__preview-dimensions"><label for="genosys-php-preview-width">عرض<input id="genosys-php-preview-width" type="number" min="240" max="2560" step="1" value="1440" data-php-preview-width dir="ltr" inputmode="numeric"><span>px</span></label><span class="gsce__dimensions-cross" aria-hidden="true">×</span><label for="genosys-php-preview-height">ارتفاع<input id="genosys-php-preview-height" type="number" min="240" max="1600" step="1" value="900" data-php-preview-height dir="ltr" inputmode="numeric"><span>px</span></label></div>
						</div>
						<p class="gsce__preview-note" data-php-preview-note role="status" aria-live="polite" hidden></p>
						<div class="gsce__preview-stage" data-php-preview-stage><div class="gsce__preview-viewport" data-php-preview-viewport><div class="gsce__preview-scale" data-php-preview-scale><iframe class="gsce__preview-frame" data-php-preview-frame data-preview-src="<?php echo esc_url( $preview_url ); ?>" title="پیش‌نمایش نسخهٔ ذخیره‌شدهٔ سایت" sandbox="allow-same-origin allow-scripts" referrerpolicy="same-origin"></iframe></div><button type="button" class="gsce__resize-handle" data-php-preview-resize aria-label="تغییر اندازه پیش‌نمایش با کشیدن" title="برای تغییر اندازه بکشید"><span aria-hidden="true"></span></button></div></div>
						<p class="gsce__preview-hint">گوشهٔ پایین پیش‌نمایش را بکشید یا اندازهٔ دلخواه را وارد کنید.</p>
					</section>
				</div>

				<section class="gspe__diagnostics" data-php-diagnostics aria-label="نتیجهٔ بررسی کد PHP">
					<header class="gspe__diagnostics-heading">
						<div class="gspe__validation-state" data-php-validation-status data-status="pending" role="status" aria-live="polite"><span class="gspe__status-icon" data-php-validation-icon><?php echo genosys_php_editor_icon( 'info' ); ?></span><strong data-php-validation-label>در انتظار بررسی کد</strong></div>
						<div class="gspe__counts"><span>خطا <b data-php-error-count>—</b></span><span>هشدار <b data-php-warning-count>—</b></span></div>
						<button type="button" class="gsce__button gspe__validate" data-php-action="validate">بررسی مجدد</button>
					</header>
					<div class="gspe__checks">
						<?php foreach ( $checks as $check_id => $check_label ) : ?>
							<div class="gspe__check" data-php-check="<?php echo esc_attr( $check_id ); ?>" data-status="pending"><span class="gspe__check-icon" data-check-icon>…</span><div><strong data-check-title><?php echo esc_html( $check_label ); ?></strong><small data-check-status>در انتظار بررسی</small></div></div>
						<?php endforeach; ?>
					</div>
					<div class="gspe__summary" data-php-summary data-status="pending" role="status" aria-live="polite">نتیجهٔ بررسی فایل انتخاب‌شده اینجا نمایش داده می‌شود.</div>
					<div class="gspe__issues" data-php-issues role="list" aria-label="خطاها و هشدارهای کد"></div>
					<p class="gspe__diagnostic-note"><?php echo genosys_php_editor_icon( 'info' ); ?><span>نتیجهٔ هر بخش فقط همان بررسی را پوشش می‌دهد. بررسی اجرای سرور هنگام ذخیره انجام می‌شود؛ تا آن زمان وضعیت آن تأییدشده نیست.</span></p>
				</section>
			</section>
		</div>
	</div>
	<?php
}
