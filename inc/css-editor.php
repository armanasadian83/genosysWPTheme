<?php
/** Real CSS source editing. Drafts live only in the editor; writes are explicit. */
defined( 'ABSPATH' ) || exit;

// This is the shipped bundle version, independent of the editable style.css header.
const GENOSYS_CSS_EDITOR_BASELINE_VERSION = '1.5.86';

function genosys_css_editor_allowed() {
	return current_user_can( 'manage_options' ) && current_user_can( 'edit_themes' )
		&& ! ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT )
		&& ! ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS )
		&& wp_is_file_mod_allowed( 'genosys_css_editor' );
}

/** Use this running theme's physical directory, never a caller-supplied theme. */
function genosys_css_editor_root() {
	$root = realpath( dirname( __DIR__ ) );
	$active = realpath( get_template_directory() );
	return $root && $active && wp_normalize_path( $root ) === wp_normalize_path( $active ) ? $root : false;
}

/** A symlinked theme directory is supported; symlinks within it are not editable. */
function genosys_css_editor_resolve( $relative ) {
	$root = genosys_css_editor_root();
	if ( ! $root || ! is_string( $relative ) || ! preg_match( '#^(?:style\.css|assets/css/[A-Za-z0-9_./-]+\.css)$#D', $relative )
		|| false !== strpos( $relative, '..' ) || false !== strpos( $relative, '//' ) || 0 !== validate_file( $relative ) ) {
		return false;
	}
	$path = $root;
	foreach ( explode( '/', $relative ) as $piece ) {
		$path .= DIRECTORY_SEPARATOR . $piece;
		if ( is_link( $path ) ) { return false; }
	}
	$real = realpath( $path );
	$prefix = trailingslashit( wp_normalize_path( $root ) );
	if ( ! $real || 0 !== strpos( wp_normalize_path( $real ), $prefix ) || ! is_file( $real ) || 'css' !== strtolower( pathinfo( $real, PATHINFO_EXTENSION ) ) ) { return false; }
	return $real;
}

function genosys_css_editor_files() {
	$root = genosys_css_editor_root();
	if ( ! $root ) { return array(); }
	$labels = array(
		'style.css'=>'استایل اصلی و صفحه اصلی', 'account-menu.css'=>'منوی حساب و نوار موبایل',
		'products.css'=>'کارت محصولات', 'featured-products.css'=>'محصولات منتخب', 'home-categories.css'=>'دسته‌بندی‌های صفحه اصلی',
		'footer.css'=>'فوتر', 'shop.css'=>'فروشگاه', 'single-product.css'=>'صفحه محصول',
		'add-to-cart-animation.css'=>'انیمیشن افزودن به سبد', 'cart.css'=>'سبد خرید', 'checkout.css'=>'تسویه حساب',
		'account-dashboard.css'=>'حساب کاربری', 'customer-orders.css'=>'سفارش‌های مشتری', 'order-access.css'=>'دسترسی سفارش',
        'fans-club.css'=>'باشگاه مشتریان', 'contact.css'=>'تماس با ما', 'brand.css'=>'صفحه برند', 'company.css'=>'صفحه شرکت', 'login.css'=>'ورود به حساب', 'receipt-uploader.css'=>'بارگذاری فیش',
		'package-builder.css'=>'مدیریت افزودن پکیج', 'package-list.css'=>'مدیریت لیست پکیج‌ها',
		'receipt-admin.css'=>'مدیریت فیش‌ها', 'club-admin.css'=>'مدیریت باشگاه مشتریان',
		'settings-dashboard.css'=>'تنظیمات قالب', 'css-editor.css'=>'ویرایشگر CSS', 'php-editor.css'=>'ویرایشگر PHP',
	);
	$admin = array( 'package-builder.css','package-list.css','receipt-admin.css','club-admin.css','settings-dashboard.css','css-editor.css','php-editor.css' );
	$paths = array( 'style.css' );
	$directory = $root . '/assets/css';
	if ( is_dir( $directory ) && ! is_link( $directory ) ) {
		try {
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ) );
			foreach ( $iterator as $entry ) {
				if ( $entry->isFile() && ! $entry->isLink() && 'css' === strtolower( $entry->getExtension() ) ) {
					$paths[] = substr( wp_normalize_path( $entry->getPathname() ), strlen( trailingslashit( wp_normalize_path( $root ) ) ) );
				}
			}
		} catch ( UnexpectedValueException $exception ) { /* An unreadable folder simply has no editable files. */ }
	}
	$paths = array_values( array_unique( $paths ) );
	sort( $paths, SORT_NATURAL );
	$paths = array_merge( array( 'style.css' ), array_diff( $paths, array( 'style.css' ) ) );
	$files = array();
	foreach ( $paths as $path ) {
		$real = genosys_css_editor_resolve( $path );
		if ( ! $real || ! is_readable( $real ) ) { continue; }
		$name = basename( $path );
		$files[] = array(
			'path'=>$path, 'label'=>$labels[ $name ] ?? $name,
			'scope'=>in_array( $name, $admin, true ) ? 'admin' : 'frontend',
			'url'=>trailingslashit( get_template_directory_uri() ) . $path,
			'readonly'=>! genosys_css_editor_allowed() || ! is_writable( $real ) || ! is_writable( dirname( $real ) ),
		);
	}
	return $files;
}

function genosys_css_editor_preview_url( $url ) {
	return add_query_arg( array( 'genosys_css_preview'=>'1', '_genosys_css_nonce'=>wp_create_nonce( 'genosys_css_preview' ) ), $url );
}

function genosys_css_editor_bootstrap() {
	static $code_editor = null;
	if ( null === $code_editor ) {
		$code_editor = genosys_css_editor_allowed() ? wp_enqueue_code_editor( array( 'type'=>'text/css', 'codemirror'=>array( 'lineNumbers'=>true, 'lineWrapping'=>false, 'indentUnit'=>2, 'tabSize'=>2, 'direction'=>'ltr' ) ) ) : false;
	}
	$pages = array( array( 'label'=>'صفحه اصلی', 'url'=>genosys_css_editor_preview_url( home_url( '/' ) ) ) );
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$shop = wc_get_page_permalink( 'shop' );
		if ( $shop && home_url( '/' ) !== $shop ) { $pages[] = array( 'label'=>'فروشگاه', 'url'=>genosys_css_editor_preview_url( $shop ) ); }
		$products = wc_get_products( array( 'status'=>'publish', 'limit'=>1, 'return'=>'ids' ) );
		if ( $products ) { $pages[] = array( 'label'=>'صفحه محصول', 'url'=>genosys_css_editor_preview_url( get_permalink( reset( $products ) ) ) ); }
	}
	$pages[] = array( 'label'=>'باشگاه مشتریان', 'url'=>genosys_css_editor_preview_url( home_url( '/fans-club/' ) ) );
	$pages[] = array( 'label'=>'حساب کاربری', 'url'=>genosys_css_editor_preview_url( home_url( '/my-account/' ) ) );
	$pages[] = array( 'label'=>'شرکت', 'url'=>genosys_css_editor_preview_url( home_url( '/company/' ) ) );
	return array(
		'ajaxUrl'=>admin_url( 'admin-ajax.php' ), 'nonce'=>wp_create_nonce( 'genosys_css_editor' ),
		'files'=>genosys_css_editor_files(), 'previewUrl'=>$pages[0]['url'], 'previewPages'=>$pages,
		'codeEditor'=>$code_editor, 'allowed'=>genosys_css_editor_allowed(), 'maxBytes'=>1048576,
		'baselineVersion'=>GENOSYS_CSS_EDITOR_BASELINE_VERSION,
		'reason'=>genosys_css_editor_allowed() ? '' : 'ویرایش فایل‌های قالب برای حساب شما یا در تنظیمات امنیتی وردپرس غیرفعال است.',
	);
}

function genosys_css_editor_error( $code, $message, $status = 400, $extra = array() ) {
	wp_send_json_error( array_merge( array( 'code'=>$code, 'message'=>$message ), $extra ), $status );
}

/** Every source endpoint uses the same permission, method and nonce checks. */
function genosys_css_editor_request_file() {
	if ( ! genosys_css_editor_allowed() ) { genosys_css_editor_error( 'forbidden', 'شما اجازهٔ ویرایش فایل‌های قالب را ندارید.', 403 ); }
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { genosys_css_editor_error( 'method', 'روش درخواست معتبر نیست.', 405 ); }
	$nonce = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] ) ? wp_unslash( $_POST['nonce'] ) : '';
	if ( ! wp_verify_nonce( $nonce, 'genosys_css_editor' ) ) { genosys_css_editor_error( 'nonce', 'نشست شما منقضی شده است؛ صفحه را دوباره باز کنید.', 403 ); }
	$file = isset( $_POST['file'] ) && is_string( $_POST['file'] ) ? wp_unslash( $_POST['file'] ) : '';
	$allowed = wp_list_pluck( genosys_css_editor_files(), 'path' );
	$real = in_array( $file, $allowed, true ) ? genosys_css_editor_resolve( $file ) : false;
	if ( ! $real ) { genosys_css_editor_error( 'file', 'این فایل CSS در قالب فعال قابل ویرایش نیست.', 400 ); }
	return array( $file, $real );
}

/** Private, non-autoloaded revisions: at most five snapshots per real CSS file. */
function genosys_css_editor_history_key( $file ) {
	return 'genosys_css_history_' . md5( wp_normalize_path( (string) genosys_css_editor_root() ) . '|' . $file );
}

function genosys_css_editor_revisions( $file ) {
	$rows = get_option( genosys_css_editor_history_key( $file ), array() );
	return is_array( $rows ) ? array_slice( $rows, 0, 5 ) : array();
}

function genosys_css_editor_history_summary( $file, $current_hash = '' ) {
	$history = array();
	foreach ( genosys_css_editor_revisions( $file ) as $row ) {
		if ( ! is_array( $row ) || ! isset( $row['id'], $row['date'], $row['hash'], $row['content'] ) ) { continue; }
		$history[] = array( 'id'=>$row['id'], 'date'=>$row['date'], 'user'=>$row['user'] ?? '', 'hash'=>$row['hash'], 'bytes'=>strlen( $row['content'] ), 'current'=>$current_hash === $row['hash'] );
	}
	return $history;
}

function genosys_css_editor_remember( $file, $content ) {
	$rows = genosys_css_editor_revisions( $file );
	$hash = hash( 'sha256', $content );
	foreach ( $rows as $row ) { if ( isset( $row['hash'] ) && $hash === $row['hash'] ) { return true; } }
	$user = wp_get_current_user();
	array_unshift( $rows, array( 'id'=>wp_generate_uuid4(), 'date'=>gmdate( 'c' ), 'user'=>sanitize_text_field( $user->display_name ), 'hash'=>$hash, 'content'=>$content ) );
	$key = genosys_css_editor_history_key( $file );
	$rows = array_slice( $rows, 0, 5 );
	update_option( $key, $rows, false );
	return get_option( $key ) === $rows;
}

function genosys_css_editor_payload( $file, $real, $content = null ) {
	if ( null === $content ) { $content = @file_get_contents( $real ); }
	if ( false === $content ) { genosys_css_editor_error( 'unreadable', 'خواندن این فایل ممکن نیست.', 500 ); }
	$hash = hash( 'sha256', $content );
	clearstatcache( true, $real );
	return array( 'file'=>$file, 'content'=>$content, 'hash'=>$hash, 'bytes'=>strlen( $content ), 'modified'=>(int) @filemtime( $real ), 'readonly'=>! is_writable( $real ) || ! is_writable( dirname( $real ) ), 'history'=>genosys_css_editor_history_summary( $file, $hash ) );
}

function genosys_css_editor_read() {
	list( $file, $real ) = genosys_css_editor_request_file();
	wp_send_json_success( genosys_css_editor_payload( $file, $real ) );
}
add_action( 'wp_ajax_genosys_css_editor_read', 'genosys_css_editor_read' );

/**
 * Read the immutable CSS snapshot shipped in this installed theme bundle.
 * This file is deliberately outside the CSS manifest and has no write endpoint.
 */
function genosys_css_editor_baseline() {
	$root = genosys_css_editor_root();
	$path = $root ? $root . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'css-baseline.json' : '';
	$real = $path ? realpath( $path ) : false;
	if ( ! $root || is_link( $root . DIRECTORY_SEPARATOR . 'inc' ) || is_link( $path ) || ! $real
		|| wp_normalize_path( $real ) !== trailingslashit( wp_normalize_path( $root ) ) . 'inc/css-baseline.json'
		|| ! is_file( $real ) || ! is_readable( $real ) ) {
		return new WP_Error( 'baseline_missing', 'نسخهٔ اصلی CSS در بستهٔ نصب‌شده در دسترس نیست؛ فایل‌های کامل همین نسخهٔ قالب را دوباره نصب کنید.' );
	}
	$bytes = @filesize( $real );
	if ( false === $bytes || $bytes < 2 || $bytes > 8388608 ) {
		return new WP_Error( 'baseline_invalid', 'فایل نسخهٔ اصلی CSS معتبر نیست. بستهٔ کامل همین نسخهٔ قالب را دوباره نصب کنید.' );
	}
	$json = @file_get_contents( $real );
	$baseline = false === $json ? null : json_decode( $json, true, 16 );
	if ( ! is_array( $baseline ) || JSON_ERROR_NONE !== json_last_error() || ! isset( $baseline['version'], $baseline['files'] )
		|| ! is_string( $baseline['version'] ) || GENOSYS_CSS_EDITOR_BASELINE_VERSION !== $baseline['version'] || ! is_array( $baseline['files'] ) ) {
		return new WP_Error( 'baseline_version', 'نسخهٔ اصلی CSS با بستهٔ نصب‌شده هماهنگ نیست. فایل‌های کامل همین نسخهٔ قالب را دوباره نصب کنید.' );
	}
	return $baseline;
}

/** Reset loads a selected file into a draft; it never publishes or alters history. */
function genosys_css_editor_original() {
	list( $file, $real ) = genosys_css_editor_request_file();
	$baseline = genosys_css_editor_baseline();
	if ( is_wp_error( $baseline ) ) { genosys_css_editor_error( $baseline->get_error_code(), $baseline->get_error_message(), 500 ); }
	$entry = $baseline['files'][ $file ] ?? null;
	if ( ! is_array( $entry ) || ! isset( $entry['content'], $entry['sha256'] ) || ! is_string( $entry['content'] ) || ! is_string( $entry['sha256'] )
		|| strlen( $entry['content'] ) > 1048576 || ! preg_match( '/^[a-f0-9]{64}$/D', $entry['sha256'] )
		|| ! hash_equals( $entry['sha256'], hash( 'sha256', $entry['content'] ) ) ) {
		genosys_css_editor_error( 'baseline_content', 'نسخهٔ اصلی این فایل در بستهٔ نصب‌شده معتبر نیست. فایل‌های کامل همین نسخهٔ قالب را دوباره نصب کنید.', 500 );
	}
	wp_send_json_success( array(
		'file'=>$file, 'content'=>$entry['content'], 'originalHash'=>$entry['sha256'],
		'installedVersion'=>$baseline['version'], 'current'=>genosys_css_editor_payload( $file, $real ),
	) );
}
add_action( 'wp_ajax_genosys_css_editor_original', 'genosys_css_editor_original' );

function genosys_css_editor_history() {
	list( $file, $real ) = genosys_css_editor_request_file();
	$hash = @hash_file( 'sha256', $real );
	wp_send_json_success( array( 'file'=>$file, 'history'=>genosys_css_editor_history_summary( $file, $hash ?: '' ) ) );
}
add_action( 'wp_ajax_genosys_css_editor_history', 'genosys_css_editor_history' );

function genosys_css_editor_revision() {
	list( $file ) = genosys_css_editor_request_file();
	$id = isset( $_POST['revision'] ) && is_string( $_POST['revision'] ) ? wp_unslash( $_POST['revision'] ) : '';
	foreach ( genosys_css_editor_revisions( $file ) as $row ) {
		if ( isset( $row['id'] ) && hash_equals( (string) $row['id'], $id ) ) {
			wp_send_json_success( array( 'file'=>$file, 'id'=>$id, 'content'=>$row['content'], 'hash'=>$row['hash'], 'date'=>$row['date'] ) );
		}
	}
	genosys_css_editor_error( 'revision', 'این نسخه در تاریخچهٔ این فایل وجود ندارد.', 404 );
}
add_action( 'wp_ajax_genosys_css_editor_revision', 'genosys_css_editor_revision' );

/** Serialize our writers independently of the target inode, which rename replaces. */
function genosys_css_editor_lock() {
	$directory = realpath( get_temp_dir() );
	if ( ! $directory ) { return false; }
	$path = $directory . DIRECTORY_SEPARATOR . 'genosys-css-' . hash( 'sha256', ABSPATH . '|' . genosys_css_editor_root() ) . '.lock';
	if ( is_link( $path ) ) { return false; }
	$handle = @fopen( $path, 'c+b' );
	if ( ! $handle ) { return false; }
	if ( is_link( $path ) || ! @flock( $handle, LOCK_EX | LOCK_NB ) ) { fclose( $handle ); return false; }
	return $handle;
}

function genosys_css_editor_save() {
	list( $file, $real ) = genosys_css_editor_request_file();
	if ( ! isset( $_POST['content'], $_POST['hash'] ) || ! is_string( $_POST['content'] ) || ! is_string( $_POST['hash'] ) ) { genosys_css_editor_error( 'content', 'کد و نسخهٔ فایل را دوباره بارگذاری کنید.' ); }
	$content = wp_unslash( $_POST['content'] );
	$expected = wp_unslash( $_POST['hash'] );
	if ( strlen( $content ) > 1048576 || false !== strpos( $content, "\0" ) || ! preg_match( '//u', $content ) || false !== stripos( $content, '<?' ) || ! preg_match( '/^[a-f0-9]{64}$/D', $expected ) ) {
		genosys_css_editor_error( 'content', 'فقط کد CSS معتبر با حجم حداکثر یک مگابایت قابل ذخیره است.' );
	}
	if ( 'style.css' === $file && ! preg_match( '/^[\t ]*(?:\/\*+|\*|\/\/)?[\t ]*Theme Name:[\t ]*\S+/mi', substr( $content, 0, 8192 ) ) ) {
		genosys_css_editor_error( 'theme_header', 'سربرگ Theme Name را در ابتدای style.css حفظ کنید تا وردپرس قالب را تشخیص دهد.' );
	}
	if ( ! is_writable( $real ) || ! is_writable( dirname( $real ) ) ) { genosys_css_editor_error( 'readonly', 'فایل یا پوشهٔ آن در سرور قابل نوشتن نیست. دسترسی نوشتن باید از میزبان اصلاح شود.', 403 ); }
	$lock = genosys_css_editor_lock();
	if ( ! $lock ) { genosys_css_editor_error( 'busy', 'فایل در حال ذخیره است؛ چند لحظه بعد دوباره تلاش کنید.', 409 ); }
	$error = null;
	$temp = false;
	$result = null;
	try {
		// Re-resolve after acquiring the lock; reject changed paths or external edits.
		if ( $real !== genosys_css_editor_resolve( $file ) ) { throw new RuntimeException( 'path' ); }
		$previous = @file_get_contents( $real );
		if ( false === $previous ) { throw new RuntimeException( 'read' ); }
		if ( ! hash_equals( hash( 'sha256', $previous ), $expected ) ) {
			$error = array( 'conflict', 'فایل پس از باز شدن ویرایشگر تغییر کرده است. نسخهٔ فعلی سرور را دوباره بارگذاری کنید؛ پیش‌نویس شما ذخیره نشده است.', 409 );
		} elseif ( $previous === $content ) {
			$result = genosys_css_editor_payload( $file, $real, $content );
		} else {
			if ( ! genosys_css_editor_remember( $file, $previous ) ) { throw new RuntimeException( 'history' ); }
			// Write a complete sibling first, so an interrupted write never truncates CSS.
			$temp = @tempnam( dirname( $real ), '.genosys-css-' );
			if ( ! $temp || wp_normalize_path( dirname( $temp ) ) !== wp_normalize_path( dirname( $real ) ) ) { throw new RuntimeException( 'temp' ); }
			$handle = @fopen( $temp, 'wb' );
			if ( ! $handle ) { throw new RuntimeException( 'temp' ); }
			$offset = 0;
			while ( $offset < strlen( $content ) ) {
				$written = @fwrite( $handle, substr( $content, $offset ) );
				if ( false === $written || 0 === $written ) { fclose( $handle ); throw new RuntimeException( 'write' ); }
				$offset += $written;
			}
			$flushed = @fflush( $handle );
			if ( function_exists( 'fsync' ) ) { $flushed = @fsync( $handle ) && $flushed; }
			fclose( $handle );
			if ( ! $flushed || hash( 'sha256', $content ) !== @hash_file( 'sha256', $temp ) ) { throw new RuntimeException( 'write' ); }
			@chmod( $temp, fileperms( $real ) & 0777 );
			// External tools do not share our lock; check their writes again before commit.
			if ( $real !== genosys_css_editor_resolve( $file ) || ! hash_equals( $expected, (string) @hash_file( 'sha256', $real ) ) ) {
				$error = array( 'conflict', 'نسخهٔ سرور هنگام ذخیره تغییر کرد؛ دوباره فایل را بارگذاری کنید.', 409 );
			} elseif ( ! @rename( $temp, $real ) ) { throw new RuntimeException( 'rename' ); }
			else {
				$temp = false;
				clearstatcache( true, $real );
				$remembered = genosys_css_editor_remember( $file, $content );
				$result = genosys_css_editor_payload( $file, $real, $content );
				if ( ! $remembered ) { $result['historyWarning'] = 'فایل ذخیره شد؛ افزودن این نسخه به تاریخچه ممکن نشد.'; }
			}
		}
	} catch ( RuntimeException $exception ) {
		$error = array( 'write', 'ذخیرهٔ امن فایل ممکن نشد؛ فایل فعلی سایت حفظ شده است. دسترسی فایل و فضای میزبان را بررسی کنید.', 500 );
	} finally {
		if ( $temp && is_file( $temp ) ) { @unlink( $temp ); }
		@flock( $lock, LOCK_UN );
		fclose( $lock );
	}
	if ( $error ) { genosys_css_editor_error( $error[0], $error[1], $error[2] ); }
	wp_send_json_success( $result );
}
add_action( 'wp_ajax_genosys_css_editor_save', 'genosys_css_editor_save' );

/** Real file hashes invalidate old fixed enqueue versions after editor saves. */
function genosys_css_editor_asset_version( $src ) {
	$root = genosys_css_editor_root();
	if ( ! $root || ! is_string( $src ) ) { return $src; }
	$base = wp_parse_url( trailingslashit( get_template_directory_uri() ) );
	$url = wp_parse_url( html_entity_decode( $src, ENT_QUOTES, 'UTF-8' ) );
	if ( ! $base || ! $url || ( isset( $url['host'] ) && strcasecmp( $url['host'], $base['host'] ?? '' ) ) || ( isset( $url['port'] ) && (int) $url['port'] !== (int) ( $base['port'] ?? 0 ) ) ) { return $src; }
	$prefix = $base['path'] ?? '';
	$path = rawurldecode( $url['path'] ?? '' );
	if ( 0 !== strpos( $path, $prefix ) ) { return $src; }
	$relative = substr( $path, strlen( $prefix ) );
	$real = genosys_css_editor_resolve( $relative );
	if ( ! $real ) { return $src; }
	static $hashes = array();
	if ( ! isset( $hashes[ $real ] ) ) { $hashes[ $real ] = @hash_file( 'sha256', $real ); }
	return $hashes[ $real ] ? add_query_arg( 'genosys_css', substr( $hashes[ $real ], 0, 16 ), $src ) : $src;
}
add_filter( 'style_loader_src', 'genosys_css_editor_asset_version', 99 );

function genosys_css_editor_is_preview() {
	return ! empty( $GLOBALS['genosys_css_editor_preview_context'] ) || ( isset( $_GET['genosys_css_preview'] ) && is_string( $_GET['genosys_css_preview'] ) && '1' === $_GET['genosys_css_preview'] );
}

/** CSS image/import loads are native browser requests, outside fetch/XHR guards. */
function genosys_css_editor_preview_referrer() {
	$referrer = isset( $_SERVER['HTTP_REFERER'] ) && is_string( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '';
	$ref = wp_parse_url( $referrer );
	$home = wp_parse_url( home_url( '/' ) );
	if ( ! $ref || ! $home || empty( $ref['host'] ) || empty( $ref['scheme'] )
		|| strtolower( $ref['scheme'] ) !== strtolower( $home['scheme'] ?? '' )
		|| 0 !== strcasecmp( $ref['host'], $home['host'] ?? '' )
		|| (int) ( $ref['port'] ?? ( 'https' === strtolower( $ref['scheme'] ) ? 443 : 80 ) ) !== (int) ( $home['port'] ?? ( 'https' === strtolower( $home['scheme'] ?? '' ) ? 443 : 80 ) ) ) { return false; }
	$query = array();
	wp_parse_str( $ref['query'] ?? '', $query );
	return isset( $query['genosys_css_preview'], $query['_genosys_css_nonce'] )
		&& is_string( $query['genosys_css_preview'] ) && '1' === $query['genosys_css_preview']
		&& is_string( $query['_genosys_css_nonce'] ) && wp_verify_nonce( $query['_genosys_css_nonce'], 'genosys_css_preview' );
}

/** Preview is a private view of the actual frontend, never an action endpoint. */
function genosys_css_editor_preview_gate() {
	$explicit = isset( $_GET['genosys_css_preview'] );
	$referred = ! $explicit && genosys_css_editor_allowed() && genosys_css_editor_preview_referrer();
	if ( ! $explicit && ! $referred ) { return; }
	$nonce = isset( $_GET['_genosys_css_nonce'] ) && is_string( $_GET['_genosys_css_nonce'] ) ? wp_unslash( $_GET['_genosys_css_nonce'] ) : '';
	if ( ! genosys_css_editor_allowed() || ( $explicit && ( ! genosys_css_editor_is_preview() || ! wp_verify_nonce( $nonce, 'genosys_css_preview' ) ) ) ) {
		wp_die( 'پیش‌نمایش فقط در نشست معتبر مدیر قابل مشاهده است.', 'پیش‌نمایش CSS', array( 'response'=>403 ) );
	}
	$GLOBALS['genosys_css_editor_preview_context'] = true;
	$method = $_SERVER['REQUEST_METHOD'] ?? '';
	$blocked = array( 'action','wc-ajax','wc-api','add-to-cart','remove_item','undo_item','remove_coupon','apply_coupon','update_cart','proceed','order_again','cancel_order','download_file','customer-logout','delete-payment-method','set-default-payment-method','rest_route' );
	$unsafe = ! in_array( $method, array( 'GET','HEAD' ), true ) || is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
	foreach ( $blocked as $key ) { if ( isset( $_REQUEST[ $key ] ) ) { $unsafe = true; } }
	$path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
	if ( preg_match( '#/(?:wp-admin|wp-json|wc-api|customer-logout|delete-payment-method|set-default-payment-method)(?:/|$)|/wp-login\.php(?:/|$)#i', rawurldecode( (string) $path ) ) ) { $unsafe = true; }
	if ( $unsafe ) { wp_die( 'عملیات و ارسال فرم در پیش‌نمایش غیرفعال است.', 'پیش‌نمایش CSS', array( 'response'=>403 ) ); }
	if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
	nocache_headers();
	if ( ! headers_sent() ) {
		header( 'Referrer-Policy: same-origin' );
		header( 'X-Robots-Tag: noindex, nofollow', true );
		// An additional policy intersects with existing CSP; never replace its rules.
		header( "Content-Security-Policy: form-action 'none'; base-uri 'self'", false );
	}
	show_admin_bar( false );
}
add_action( 'init', 'genosys_css_editor_preview_gate', -100000 );

/** Run before theme scripts so the real page remains visually accurate but read-only. */
function genosys_css_editor_preview_guard() {
	if ( ! genosys_css_editor_is_preview() ) { return; }
	$config = array( 'nonce'=>wp_create_nonce( 'genosys_css_preview' ), 'origin'=>home_url( '/' ) );
	?>
	<script id="genosys-css-preview-guard">
	(function(config){'use strict';
	 var origin=new URL(config.origin,location.href).origin;
	 function safe(value,method){try{var u=new URL(value,location.href);if(u.origin!==origin||!/^(GET|HEAD)$/i.test(method||'GET')||/\/(wp-admin|wp-json|wc-api|customer-logout|delete-payment-method|set-default-payment-method)(\/|$)|\/wp-login\.php/i.test(decodeURIComponent(u.pathname)))return false;var bad=['action','wc-ajax','wc-api','add-to-cart','remove_item','undo_item','remove_coupon','apply_coupon','update_cart','proceed','order_again','cancel_order','download_file','customer-logout','delete-payment-method','set-default-payment-method','rest_route'];return !bad.some(function(k){return u.searchParams.has(k);});}catch(e){return false;}}
	 function decorate(value){var u=new URL(value,location.href);u.searchParams.set('genosys_css_preview','1');u.searchParams.set('_genosys_css_nonce',config.nonce);return u.href;}
	 var fetchOriginal=window.fetch;if(fetchOriginal)window.fetch=function(input,init){var method=(init&&init.method)||(input&&input.method)||'GET',url=typeof input==='string'||input instanceof URL?String(input):input.url;if(!safe(url,method))return Promise.reject(new TypeError('Read-only CSS preview'));return fetchOriginal.apply(this,arguments);};
	 var openOriginal=XMLHttpRequest.prototype.open;XMLHttpRequest.prototype.open=function(method,url){if(!safe(url,method))throw new DOMException('Read-only CSS preview','SecurityError');return openOriginal.apply(this,arguments);};
	 if(navigator.sendBeacon)try{navigator.sendBeacon=function(){return false;};}catch(e){}
	 window.open=function(){return null;};
	 document.addEventListener('submit',function(e){e.preventDefault();e.stopImmediatePropagation();},true);
	 document.addEventListener('click',function(e){var a=e.target&&e.target.closest?e.target.closest('a'):null;if(!a)return;var href=a.getAttribute('href');if(!href||href.charAt(0)==='#')return;e.preventDefault();e.stopImmediatePropagation();if(safe(a.href,'GET'))location.assign(decorate(a.href));},true);
	 window.genosysCSSReadOnlyPreview=true;
	})(<?php echo wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>);
	</script>
	<?php
}
add_action( 'wp_head', 'genosys_css_editor_preview_guard', -100000 );
