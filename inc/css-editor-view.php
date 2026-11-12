<?php
/** The CSS editor's presentation. Source reads and writes live in css-editor.php. */
defined( 'ABSPATH' ) || exit;

/** Small, consistent outline icons for the editor controls. */
function genosys_css_editor_icon( $name ) {
	$paths = array(
		'file'       => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6m-10 4-2 2 2 2m4-4 2 2-2 2"/>',
		'desktop'    => '<rect x="3" y="3" width="18" height="13" rx="2"/><path d="M8 21h8m-4-5v5"/>',
		'mobile'     => '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>',
		'undo'       => '<path d="m9 4-5 5 5 5M4 9h9a6 6 0 0 1 0 12"/>',
		'redo'       => '<path d="m15 4 5 5-5 5m5-5h-9a6 6 0 0 0 0 12"/>',
		'copy'       => '<rect x="8" y="8" width="12" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h3"/>',
		'download'   => '<path d="M12 3v12m-5-5 5 5 5-5M4 18v3h16v-3"/>',
		'moon'       => '<path d="M20.8 13.4A8.8 8.8 0 0 1 10.6 3.2a9 9 0 1 0 10.2 10.2Z"/>',
		'wrap'       => '<path d="M3 5h18M3 10h14a4 4 0 0 1 0 8h-4m3-3-3 3 3 3M3 15h5M3 20h5"/>',
		'fullscreen' => '<path d="M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5"/>',
		'reload'     => '<path d="M20 7v5h-5M4 17v-5h5"/><path d="M6 7a7 7 0 0 1 12-1l2 3M4 15l2 3a7 7 0 0 0 12-1"/>',
		'external'   => '<path d="M14 3h7v7m0-7L10 14M11 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-6"/>',
		'arrow'      => '<path d="m9 5 7 7-7 7"/>',
	);
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? $paths['file'] ) . '</svg>';
}

/**
 * Render the editor without a form or named inputs; saving uses its own endpoint.
 *
 * @param array  $files       Actual theme CSS file manifest.
 * @param string $preview_url Protected, read-only front-end preview URL.
 */
function genosys_render_css_editor( $files = array(), $preview_url = '' ) {
	if ( empty( $files ) && function_exists( 'genosys_css_editor_files' ) ) {
		$files = genosys_css_editor_files();
	}
	$preview_url = $preview_url ?: home_url( '/' );
	$first_file  = ! empty( $files[0]['path'] ) ? $files[0]['path'] : '';
	?>
	<div class="gsce" data-css-editor dir="rtl">
		<header class="gsce__heading">
			<div><h2>ویرایشگر CSS</h2><p>کدهای ظاهری قالب را ببینید، ویرایش کنید و از همین‌جا ذخیره کنید.</p></div>
			<span class="gsce__save-state" data-css-save-status role="status" aria-live="polite">در حال بارگذاری فایل‌ها…</span>
		</header>
		<div class="gsce__message" data-css-message role="status" aria-live="polite" hidden></div>
		<div class="gsce__workspace">
			<aside class="gsce__files" aria-label="فایل‌های استایل قالب">
				<button type="button" class="gsce__panel-resize gsce__panel-resize--files" data-css-files-resize aria-label="تغییر عرض فهرست فایل‌ها با کشیدن لبهٔ چپ" title="لبهٔ چپ را برای بزرگ یا کوچک کردن فهرست فایل‌ها بکشید"><span aria-hidden="true"></span></button>
				<h3>فایل‌های استایل</h3>
				<div class="gsce__file-list" role="tablist" aria-label="فایل‌های CSS" aria-orientation="vertical">
					<?php foreach ( $files as $index => $file ) :
						$path  = isset( $file['path'] ) ? (string) $file['path'] : '';
						$label = ! empty( $file['label'] ) ? (string) $file['label'] : basename( $path );
						$admin = isset( $file['scope'] ) && 'admin' === $file['scope'];
						?>
						<button type="button" class="gsce__file" data-css-file="<?php echo esc_attr( $path ); ?>" role="tab" aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>" aria-controls="genosys-css-editor-panel" tabindex="<?php echo 0 === $index ? '0' : '-1'; ?>">
							<span class="gsce__file-icon"><?php echo genosys_css_editor_icon( 'file' ); ?></span>
							<span class="gsce__file-info"><strong><?php echo esc_html( $label ); ?></strong><code dir="ltr"><?php echo esc_html( $path ); ?></code><?php if ( $admin ) : ?><small>پنل مدیریت</small><?php endif; ?></span>
							<span class="gsce__file-dirty" data-file-dirty hidden aria-label="ذخیره‌نشده"></span>
						</button>
					<?php endforeach; ?>
				</div>
				<div class="gsce__help"><p>ذخیره: <kbd>Ctrl</kbd> + <kbd>S</kbd></p><p>تورفتگی: <kbd>Tab</kbd></p><p>تغییرات را در پیش‌نمایش زنده می‌بینید؛ فایل اصلی فقط با ذخیره‌سازی تغییر می‌کند.</p></div>
			</aside>

			<section class="gsce__editor" id="genosys-css-editor-panel" aria-label="ویرایش کد CSS">
				<div class="gsce__toolbar">
					<code class="gsce__current-file" data-css-current-file dir="ltr"><?php echo esc_html( $first_file ); ?></code>
					<div class="gsce__tools" role="group" aria-label="ابزارهای ویرایش کد">
						<button type="button" class="gsce__icon-button" data-css-action="undo" title="واگرد" aria-label="واگرد"><?php echo genosys_css_editor_icon( 'undo' ); ?></button>
						<button type="button" class="gsce__icon-button" data-css-action="redo" title="از نو" aria-label="از نو"><?php echo genosys_css_editor_icon( 'redo' ); ?></button>
						<button type="button" class="gsce__icon-button" data-css-action="copy" title="کپی کد" aria-label="کپی کد"><?php echo genosys_css_editor_icon( 'copy' ); ?></button>
						<button type="button" class="gsce__icon-button" data-css-action="download" title="دانلود فایل CSS" aria-label="دانلود فایل CSS"><?php echo genosys_css_editor_icon( 'download' ); ?></button>
					</div>
					<label class="screen-reader-text" for="genosys-css-history">تاریخچه نسخه‌ها</label><select id="genosys-css-history" class="gsce__select" data-css-history data-css-draft-input aria-label="تاریخچه نسخه‌ها"><option value="">تاریخچه‌ی نسخه‌ها</option></select>
					<div class="gsce__tools" role="group" aria-label="نمایش ویرایشگر">
						<button type="button" class="gsce__icon-button gsce__font-button" data-css-action="font-decrease" title="کوچک کردن متن" aria-label="کوچک کردن متن">−A</button>
						<button type="button" class="gsce__icon-button gsce__font-button" data-css-action="font-increase" title="بزرگ کردن متن" aria-label="بزرگ کردن متن">+A</button>
						<button type="button" class="gsce__icon-button" data-css-action="wrap" title="شکستن خط‌های بلند" aria-label="شکستن خط‌های بلند" aria-pressed="false"><?php echo genosys_css_editor_icon( 'wrap' ); ?></button>
						<button type="button" class="gsce__icon-button" data-css-action="theme" title="تغییر روشنایی ویرایشگر" aria-label="تغییر روشنایی ویرایشگر" aria-pressed="false"><?php echo genosys_css_editor_icon( 'moon' ); ?></button>
						<button type="button" class="gsce__icon-button" data-css-action="fullscreen" title="تمام‌صفحه کردن ویرایشگر" aria-label="تمام‌صفحه کردن ویرایشگر" aria-pressed="false"><?php echo genosys_css_editor_icon( 'fullscreen' ); ?></button>
					</div>
					<button type="button" class="gsce__button gsce__button--reset" data-css-action="reset" title="بازیابی کد اصلی فایل انتخاب‌شده از نسخهٔ نصب‌شدهٔ قالب؛ برای اعمال دائمی ذخیره‌سازی را بزنید">بازنشانی</button>
					<button type="button" class="gsce__button gsce__button--save" data-css-action="save" title="ذخیره فایل انتخاب‌شده" disabled>ذخیره‌سازی</button>
				</div>
				<div class="gsce__code-area"><label class="screen-reader-text" for="genosys-css-code">کد CSS فایل انتخاب‌شده</label><textarea id="genosys-css-code" class="gsce__code" data-css-code data-css-draft-input dir="ltr" spellcheck="false" autocapitalize="off" autocomplete="off" wrap="off" aria-describedby="genosys-css-editor-note"></textarea></div>
				<footer class="gsce__statusbar"><span data-css-cursor>سطر ۱، ستون ۱</span><span data-css-validation role="status" aria-live="polite">در انتظار بارگذاری کد</span><span class="gsce__status-note" id="genosys-css-editor-note">پیش‌نمایش زنده</span></footer>
			</section>

			<section class="gsce__preview" aria-label="پیش‌نمایش زنده وب‌سایت">
				<button type="button" class="gsce__panel-resize" data-css-panel-resize aria-label="تغییر عرض کادر پیش‌نمایش با کشیدن لبهٔ راست" title="لبهٔ راست را برای بزرگ یا کوچک کردن کادر بکشید"><span aria-hidden="true"></span></button>
				<header class="gsce__preview-heading"><h3>پیش‌نمایش زنده</h3><div class="gsce__tools" role="group" aria-label="اندازه پیش‌نمایش"><button type="button" class="gsce__icon-button" data-css-action="preview-desktop" aria-label="نمایش دسکتاپ" title="نمایش دسکتاپ" aria-pressed="true"><?php echo genosys_css_editor_icon( 'desktop' ); ?></button><button type="button" class="gsce__icon-button" data-css-action="preview-mobile" aria-label="نمایش موبایل" title="نمایش موبایل" aria-pressed="false"><?php echo genosys_css_editor_icon( 'mobile' ); ?></button><button type="button" class="gsce__icon-button" data-css-action="preview-fullscreen" aria-label="نمایش تمام‌عرض پیش‌نمایش" title="نمایش تمام‌عرض" aria-pressed="false"><?php echo genosys_css_editor_icon( 'fullscreen' ); ?></button></div></header>
				<div class="gsce__preview-controls">
					<label class="screen-reader-text" for="genosys-css-preview-page">صفحه پیش‌نمایش</label><select id="genosys-css-preview-page" class="gsce__select" data-css-preview-page data-css-draft-input><option value="<?php echo esc_url( home_url( '/' ) ); ?>">صفحه اصلی</option></select>
					<div class="gsce__preview-address"><label class="screen-reader-text" for="genosys-css-preview-path">مسیر صفحه در همین وب‌سایت</label><input id="genosys-css-preview-path" data-css-preview-path data-css-draft-input type="text" value="/" placeholder="/shop/" dir="ltr" aria-label="مسیر صفحه در همین وب‌سایت"><button type="button" class="gsce__icon-button" data-css-action="preview-open" title="نمایش این مسیر" aria-label="نمایش این مسیر"><?php echo genosys_css_editor_icon( 'arrow' ); ?></button><button type="button" class="gsce__icon-button" data-css-action="preview-reload" title="بارگذاری دوباره پیش‌نمایش" aria-label="بارگذاری دوباره پیش‌نمایش"><?php echo genosys_css_editor_icon( 'reload' ); ?></button></div>
					<div class="gsce__preview-dimensions"><label for="genosys-css-preview-width">عرض<input id="genosys-css-preview-width" type="number" min="240" max="2560" step="1" value="1440" data-css-preview-width data-css-draft-input dir="ltr" inputmode="numeric"><span>px</span></label><span class="gsce__dimensions-cross" aria-hidden="true">×</span><label for="genosys-css-preview-height">ارتفاع<input id="genosys-css-preview-height" type="number" min="240" max="1600" step="1" value="900" data-css-preview-height data-css-draft-input dir="ltr" inputmode="numeric"><span>px</span></label></div>
				</div>
				<p class="gsce__preview-note" data-css-preview-note role="status" aria-live="polite" hidden></p>
				<div class="gsce__preview-stage" data-css-preview-stage>
					<div class="gsce__preview-viewport" data-css-preview-viewport><div class="gsce__preview-scale" data-css-preview-scale><iframe class="gsce__preview-frame" data-css-preview-frame data-preview-src="<?php echo esc_url( $preview_url ); ?>" title="پیش‌نمایش زنده وب‌سایت" sandbox="allow-same-origin allow-scripts" referrerpolicy="same-origin"></iframe></div><button type="button" class="gsce__resize-handle" data-css-preview-resize aria-label="تغییر اندازه پیش‌نمایش با کشیدن" title="برای تغییر اندازه بکشید"><span aria-hidden="true"></span></button></div>
				</div>
				<p class="gsce__preview-hint"><span class="gsce__panel-drag-hint">لبهٔ راست کادر را برای تغییر عرض آن بکشید. </span>گوشهٔ پایین پیش‌نمایش را بکشید یا اندازهٔ دلخواه را وارد کنید.</p>
			</section>
		</div>
	</div>
	<?php
}
