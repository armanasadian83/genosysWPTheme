<?php
/** Real PHP source editing with conservative analysis and WordPress fatal rollback. */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/php-editor-validator.php';
const GENOSYS_PHP_EDITOR_BASELINE_VERSION = '1.5.86';
const GENOSYS_PHP_EDITOR_BASELINE_PREFIX = "<?php\ndefined( 'ABSPATH' ) || exit;\n__halt_compiler();\n";

function genosys_php_editor_allowed() {
	return current_user_can( 'manage_options' ) && current_user_can( 'edit_themes' )
		&& ! ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT )
		&& ! ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) && wp_is_file_mod_allowed( 'genosys_php_editor' );
}
function genosys_php_editor_root() {
	$root = realpath( dirname( __DIR__ ) ); $active = realpath( get_template_directory() );
	return $root && $active && wp_normalize_path( $root ) === wp_normalize_path( $active ) ? $root : false;
}
function genosys_php_editor_resolve( $relative ) {
	$root = genosys_php_editor_root();
	if ( ! $root || ! is_string( $relative ) || ! preg_match( '#^[A-Za-z0-9_./-]+\.php$#D', $relative ) || false !== strpos( $relative, '..' ) || false !== strpos( $relative, '//' ) || 0 !== validate_file( $relative ) ) { return false; }
	$path = $root;
	foreach ( explode( '/', $relative ) as $piece ) { $path .= DIRECTORY_SEPARATOR . $piece; if ( is_link( $path ) ) { return false; } }
	$real = realpath( $path );
	return $real && is_file( $real ) && 0 === strpos( wp_normalize_path( $real ), trailingslashit( wp_normalize_path( $root ) ) ) && 'php' === strtolower( pathinfo( $real, PATHINFO_EXTENSION ) ) ? $real : false;
}
function genosys_php_editor_readonly_reason( $file, $real ) {
	if ( preg_match( '#^inc/(?:php|css)-editor(?:-[a-z0-9-]+)?\.php$#D', $file ) ) { return 'این فایل بخشی از موتور و محافظ ذخیرهٔ ویرایشگر است و برای حفظ امکان بازیابی فقط خواندنی است.'; }
	if ( ! genosys_php_editor_allowed() ) { return 'ویرایش فایل‌های قالب برای این حساب یا در تنظیمات امنیتی وردپرس مجاز نیست.'; }
	if ( @filesize( $real ) > 1048576 ) { return 'حجم این فایل از سقف یک مگابایت ویرایش امن بیشتر است.'; }
	if ( ! is_writable( $real ) || ! is_writable( dirname( $real ) ) ) { return 'فایل یا پوشهٔ آن در میزبان قابل نوشتن نیست.'; }
	return '';
}
function genosys_php_editor_files() {
	$root = genosys_php_editor_root(); $files = array();
	if ( ! $root ) { return $files; }
	try {
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iterator as $entry ) {
			if ( ! $entry->isFile() || $entry->isLink() || 'php' !== strtolower( $entry->getExtension() ) ) { continue; }
			$relative = substr( wp_normalize_path( $entry->getPathname() ), strlen( trailingslashit( wp_normalize_path( $root ) ) ) );
			// This guarded data archive is neither executable theme source nor editable.
			if ( 'inc/php-baseline.php' === $relative ) { continue; }
			$real = genosys_php_editor_resolve( $relative ); if ( ! $real || ! is_readable( $real ) ) { continue; }
			$reason = genosys_php_editor_readonly_reason( $relative, $real );
			$files[] = array( 'path'=>$relative,'label'=>$relative,'scope'=>0 === strpos( $relative, 'inc/' ) ? 'backend' : 'template','readonly'=>'' !== $reason,'readonlyReason'=>$reason );
		}
	} catch ( UnexpectedValueException $error ) { return array(); }
	usort( $files, static function( $a, $b ) { if ( 'functions.php' === $a['path'] ) { return -1; } if ( 'functions.php' === $b['path'] ) { return 1; } return strnatcasecmp( $a['path'], $b['path'] ); } );
	return $files;
}
function genosys_php_editor_bootstrap() {
	static $editor = null;
	if ( null === $editor ) { $editor = genosys_php_editor_allowed() ? wp_enqueue_code_editor( array( 'type'=>'application/x-httpd-php','codemirror'=>array( 'lineNumbers'=>true,'lineWrapping'=>false,'indentUnit'=>4,'tabSize'=>4,'direction'=>'ltr','lint'=>false ) ) ) : false; }
	$preview = function_exists( 'genosys_css_editor_preview_url' ) ? genosys_css_editor_preview_url( home_url( '/' ) ) : '';
	$pages = array( array( 'label'=>'صفحه اصلی — نسخهٔ ذخیره‌شده','url'=>$preview ) );
	foreach ( array( '/shop/'=>'فروشگاه','/fans-club/'=>'باشگاه مشتریان','/my-account/'=>'حساب کاربری' ) as $path => $label ) { if ( function_exists( 'genosys_css_editor_preview_url' ) ) { $pages[] = array( 'label'=>$label . ' — نسخهٔ ذخیره‌شده','url'=>genosys_css_editor_preview_url( home_url( $path ) ) ); } }
	return array( 'ajaxUrl'=>admin_url( 'admin-ajax.php' ),'nonce'=>wp_create_nonce( 'genosys_php_editor' ),'files'=>genosys_php_editor_files(),'codeEditor'=>$editor,'allowed'=>genosys_php_editor_allowed(),'reason'=>genosys_php_editor_allowed() ? '' : 'ویرایش فایل‌های PHP در تنظیمات امنیتی وردپرس یا برای حساب شما غیرفعال است.','maxBytes'=>1048576,'baselineVersion'=>GENOSYS_PHP_EDITOR_BASELINE_VERSION,'previewUrl'=>$preview,'previewPages'=>$pages,'previewMode'=>'saved' );
}
function genosys_php_editor_error( $code, $message, $status = 400, $extra = array() ) { wp_send_json_error( array_merge( array( 'code'=>$code,'message'=>$message ), $extra ), $status ); }
function genosys_php_editor_request_file( $writable = false ) {
	if ( ! genosys_php_editor_allowed() ) { genosys_php_editor_error( 'forbidden','اجازهٔ ویرایش فایل‌های PHP را ندارید.',403 ); }
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { genosys_php_editor_error( 'method','روش درخواست معتبر نیست.',405 ); }
	$nonce = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] ) ? wp_unslash( $_POST['nonce'] ) : '';
	if ( ! wp_verify_nonce( $nonce, 'genosys_php_editor' ) ) { genosys_php_editor_error( 'nonce','نشست منقضی شده است؛ صفحه را دوباره باز کنید.',403 ); }
	$file = isset( $_POST['file'] ) && is_string( $_POST['file'] ) ? wp_unslash( $_POST['file'] ) : '';
	$files = genosys_php_editor_files(); $real = in_array( $file, wp_list_pluck( $files, 'path' ), true ) ? genosys_php_editor_resolve( $file ) : false;
	if ( ! $real ) { genosys_php_editor_error( 'file','فایل PHP در قالب فعال در دسترس نیست.' ); }
	if ( $writable && ( $reason = genosys_php_editor_readonly_reason( $file, $real ) ) ) { genosys_php_editor_error( 'readonly',$reason,403 ); }
	return array( $file,$real,$files );
}
function genosys_php_editor_history_key( $file ) { return 'genosys_php_history_' . md5( wp_normalize_path( (string) genosys_php_editor_root() ) . '|' . $file ); }
function genosys_php_editor_revisions( $file ) { $rows = get_option( genosys_php_editor_history_key( $file ), array() ); return is_array( $rows ) ? array_slice( $rows, 0, 5 ) : array(); }
function genosys_php_editor_history_summary( $file, $current = '' ) {
	$result = array();
	foreach ( genosys_php_editor_revisions( $file ) as $row ) { if ( isset( $row['id'],$row['content'],$row['hash'],$row['date'] ) ) { $result[] = array( 'id'=>$row['id'],'date'=>$row['date'],'hash'=>$row['hash'],'user'=>$row['user'] ?? '','bytes'=>strlen( $row['content'] ),'current'=>$current === $row['hash'] ); } }
	return $result;
}
function genosys_php_editor_remember( $file, $content ) {
	$rows = genosys_php_editor_revisions( $file ); $hash = hash( 'sha256', $content );
	foreach ( $rows as $row ) { if ( ( $row['hash'] ?? '' ) === $hash ) { return true; } }
	$user = wp_get_current_user(); array_unshift( $rows, array( 'id'=>wp_generate_uuid4(),'date'=>gmdate( 'c' ),'hash'=>$hash,'user'=>sanitize_text_field( $user->display_name ),'content'=>$content ) );
	$rows = array_slice( $rows,0,5 ); $key = genosys_php_editor_history_key( $file ); update_option( $key,$rows,false ); return get_option( $key ) === $rows;
}
function genosys_php_editor_payload( $file, $real, $content = null ) {
	if ( null === $content ) { $content = @file_get_contents( $real ); }
	if ( false === $content ) { genosys_php_editor_error( 'read','خواندن فایل ممکن نیست.',500 ); }
	$hash = hash( 'sha256',$content ); $reason = genosys_php_editor_readonly_reason( $file,$real ); clearstatcache( true,$real );
	return array( 'file'=>$file,'content'=>$content,'hash'=>$hash,'bytes'=>strlen( $content ),'modified'=>(int) @filemtime( $real ),'readonly'=>'' !== $reason,'readonlyReason'=>$reason,'history'=>genosys_php_editor_history_summary( $file,$hash ) );
}
function genosys_php_editor_read() { list( $file,$real ) = genosys_php_editor_request_file(); wp_send_json_success( genosys_php_editor_payload( $file,$real ) ); }
add_action( 'wp_ajax_genosys_php_editor_read','genosys_php_editor_read' );
function genosys_php_editor_history() { list( $file,$real ) = genosys_php_editor_request_file(); wp_send_json_success( array( 'file'=>$file,'history'=>genosys_php_editor_history_summary( $file,(string) @hash_file( 'sha256',$real ) ) ) ); }
add_action( 'wp_ajax_genosys_php_editor_history','genosys_php_editor_history' );
function genosys_php_editor_revision() {
	list( $file ) = genosys_php_editor_request_file(); $id = isset( $_POST['revision'] ) && is_string( $_POST['revision'] ) ? wp_unslash( $_POST['revision'] ) : '';
	foreach ( genosys_php_editor_revisions( $file ) as $row ) { if ( isset( $row['id'] ) && hash_equals( (string) $row['id'],$id ) ) { wp_send_json_success( array( 'file'=>$file,'id'=>$id,'content'=>$row['content'],'hash'=>$row['hash'],'date'=>$row['date'] ) ); } }
	genosys_php_editor_error( 'revision','نسخهٔ درخواستی در تاریخچهٔ این فایل نیست.',404 );
}
add_action( 'wp_ajax_genosys_php_editor_revision','genosys_php_editor_revision' );
function genosys_php_editor_original() {
	list( $file,$real ) = genosys_php_editor_request_file(); $root = genosys_php_editor_root(); $path = $root . '/inc/php-baseline.php'; $canonical = realpath( $path );
	if ( is_link( $root . '/inc' ) || is_link( $path ) || ! $canonical || ! is_file( $canonical ) || wp_normalize_path( $canonical ) !== wp_normalize_path( $path ) || ! is_readable( $canonical ) || @filesize( $canonical ) > 16777216 ) { genosys_php_editor_error( 'baseline','نسخهٔ اصلی PHP بستهٔ نصب‌شده در دسترس نیست.',500 ); }
	$archive = @file_get_contents( $canonical );
	if ( ! is_string( $archive ) || 0 !== strpos( $archive,GENOSYS_PHP_EDITOR_BASELINE_PREFIX ) ) { genosys_php_editor_error( 'baseline_container','قالب محافظت‌شدهٔ نسخهٔ اصلی PHP معتبر نیست.',500 ); }
	// Read the archive only. It must never be included, required or evaluated.
	$baseline = json_decode( substr( $archive,strlen( GENOSYS_PHP_EDITOR_BASELINE_PREFIX ) ),true,16 );
	if ( ! is_array( $baseline ) || JSON_ERROR_NONE !== json_last_error() || ( $baseline['version'] ?? '' ) !== GENOSYS_PHP_EDITOR_BASELINE_VERSION ) { genosys_php_editor_error( 'baseline_version','نسخهٔ اصلی PHP با بستهٔ نصب‌شده هماهنگ نیست.',500 ); }
	$entry = $baseline['files'][$file] ?? null;
	if ( ! is_array( $entry ) || ! isset( $entry['content'],$entry['sha256'] ) || ! is_string( $entry['content'] ) || ! is_string( $entry['sha256'] ) || strlen( $entry['content'] ) > 1048576 || ! preg_match( '/^[a-f0-9]{64}$/D',$entry['sha256'] ) || ! hash_equals( $entry['sha256'],hash( 'sha256',$entry['content'] ) ) ) { genosys_php_editor_error( 'baseline_content','نسخهٔ اصلی این فایل PHP معتبر نیست.',500 ); }
	wp_send_json_success( array( 'file'=>$file,'content'=>$entry['content'],'originalHash'=>$entry['sha256'],'installedVersion'=>$baseline['version'],'current'=>genosys_php_editor_payload( $file,$real ) ) );
}
add_action( 'wp_ajax_genosys_php_editor_original','genosys_php_editor_original' );
function genosys_php_editor_content() {
	if ( ! isset( $_POST['content'],$_POST['hash'] ) || ! is_string( $_POST['content'] ) || ! is_string( $_POST['hash'] ) ) { genosys_php_editor_error( 'content','کد و نسخهٔ فعلی فایل را بارگذاری کنید.' ); }
	$content = wp_unslash( $_POST['content'] ); $hash = wp_unslash( $_POST['hash'] );
	if ( strlen( $content ) > 1048576 || false !== strpos( $content,"\0" ) || ! preg_match( '//u',$content ) || ! preg_match( '/^[a-f0-9]{64}$/D',$hash ) ) { genosys_php_editor_error( 'content','کد UTF-8 با حجم حداکثر یک مگابایت و نسخهٔ معتبر فایل لازم است.' ); }
	return array( $content,$hash );
}
function genosys_php_editor_validate() {
	list( $file,$real,$files ) = genosys_php_editor_request_file(); list( $content,$expected ) = genosys_php_editor_content(); $previous = @file_get_contents( $real );
	if ( false === $previous || ! hash_equals( hash( 'sha256',$previous ),$expected ) ) { genosys_php_editor_error( 'conflict','نسخهٔ فایل در سرور تغییر کرده است؛ دوباره آن را بارگذاری کنید.',409 ); }
	$validation = genosys_php_editor_validate_source( $file,$content,$previous,$files );
	$reason = genosys_php_editor_readonly_reason( $file,$real );
	wp_send_json_success( array( 'validation'=>$validation,'readonly'=>'' !== $reason,'readonlyReason'=>$reason ) );
}
add_action( 'wp_ajax_genosys_php_editor_validate','genosys_php_editor_validate' );

function genosys_php_editor_lock() {
	$directory = realpath( get_temp_dir() ); if ( ! $directory ) { return false; }
	$path = $directory . DIRECTORY_SEPARATOR . 'genosys-php-' . hash( 'sha256',ABSPATH . '|' . genosys_php_editor_root() ) . '.lock';
	if ( is_link( $path ) || ( file_exists( $path ) && ! is_file( $path ) ) ) { return false; }
	$handle = @fopen( $path,'c+b' ); if ( ! $handle ) { return false; }
	if ( is_link( $path ) || ! @flock( $handle,LOCK_EX | LOCK_NB ) ) { fclose( $handle ); return false; }
	return $handle;
}

/** Parse the real WordPress shutdown scrape marker; HTTP success alone is insufficient. */
function genosys_php_editor_probe( $file, $settings_only = false ) {
	$key = md5( wp_generate_uuid4() . wp_rand() ); $nonce = (string) wp_rand(); $transient = 'scrape_key_' . $key;
	set_transient( $transient,$nonce,60 );
	if ( get_transient( $transient ) !== $nonce ) { return new WP_Error( 'runtime_unavailable','ساخت نشست آزمون مستقل سرور ممکن نیست؛ ذخیره مسدود است.' ); }
	$params = array( 'wp_scrape_key'=>$key,'wp_scrape_nonce'=>$nonce );
	$urls = $settings_only ? array( admin_url( 'admin.php?page=genosys-theme-settings' ) ) : array( add_query_arg( array( 'theme'=>get_template(),'file'=>$file ),admin_url( 'theme-editor.php' ) ),home_url( '/' ) );
	$cookies = wp_unslash( $_COOKIE ); $headers = array( 'Cache-Control'=>'no-cache' );
	if ( isset( $_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'] ) ) { $headers['Authorization']='Basic ' . base64_encode( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) . ':' . wp_unslash( $_SERVER['PHP_AUTH_PW'] ) ); }
	if ( function_exists( 'session_status' ) && PHP_SESSION_ACTIVE === session_status() ) { session_write_close(); }
	try {
		foreach ( $urls as $index => $url ) {
			$response = wp_remote_get( add_query_arg( $params,$url ),array( 'cookies'=>$cookies,'headers'=>$headers,'timeout'=>12,'sslverify'=>apply_filters( 'https_local_ssl_verify',false ) ) );
			$body = wp_remote_retrieve_body( $response ); $start = "###### wp_scraping_result_start:$key ######"; $end = "###### wp_scraping_result_end:$key ######";
			$position = strpos( $body,$start );
			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) || false === $position ) { return new WP_Error( 'runtime_unavailable','ارتباط آزمون واقعی با سایت برقرار نشد؛ هیچ ذخیرهٔ تأییدنشده‌ای مجاز نیست.' ); }
			$output = substr( $body,$position + strlen( $start ) ); $finish = strpos( $output,$end ); $result = false === $finish ? null : json_decode( trim( substr( $output,0,$finish ) ),true );
			if ( true !== $result ) { return new WP_Error( 'runtime_error','آزمون واقعی سرور خطای PHP گزارش کرد.',is_array( $result ) ? $result : array() ); }
			if ( ( $settings_only && false === strpos( $body,'gs-settings' ) ) || ( ! $settings_only && 0 === $index && false === strpos( $body,'id="template"' ) ) ) { return new WP_Error( 'runtime_auth','صفحهٔ واقعی مدیر در آزمون قابل تأیید نیست؛ ذخیره مسدود است.' ); }
		}
	} finally { delete_transient( $transient ); }
	return true;
}

/** Atomic full-file replacement used only for a verified backup or rollback. */
function genosys_php_editor_restore( $real, $content ) {
	$temp = @tempnam( dirname( $real ),'.genosys-php-' );
	if ( ! $temp || wp_normalize_path( dirname( $temp ) ) !== wp_normalize_path( dirname( $real ) ) ) { if ( $temp ) { @unlink( $temp ); } return false; }
	$written = @file_put_contents( $temp,$content,LOCK_EX );
	$valid = strlen( $content ) === $written && hash( 'sha256',$content ) === @hash_file( 'sha256',$temp );
	if ( $valid && is_file( $real ) ) { @chmod( $temp,fileperms( $real ) & 0777 ); }
	$done = $valid && @rename( $temp,$real ); if ( ! $done ) { @unlink( $temp ); }
	if ( $done ) { clearstatcache( true,$real ); if ( function_exists( 'wp_opcache_invalidate' ) ) { wp_opcache_invalidate( $real,true ); } }
	return $done && hash( 'sha256',$content ) === @hash_file( 'sha256',$real );
}

/** Never overwrite unrelated bytes installed by an external writer during probes. */
function genosys_php_editor_restore_owned( $file,$real,$previous,$candidate ) {
	if ( $real !== genosys_php_editor_resolve( $file ) ) { return false; }
	$current = @file_get_contents( $real );
	if ( false === $current ) { return false; }
	if ( hash_equals( hash( 'sha256',$previous ),hash( 'sha256',$current ) ) ) { return true; }
	if ( $current !== $candidate && substr( $candidate,0,strlen( $current ) ) !== $current ) { return false; }
	return genosys_php_editor_restore( $real,$previous );
}

function genosys_php_editor_save() {
	list( $file,$real,$files ) = genosys_php_editor_request_file( true ); list( $content,$expected ) = genosys_php_editor_content();
	$lock = genosys_php_editor_lock(); if ( ! $lock ) { genosys_php_editor_error( 'busy','ذخیرهٔ PHP دیگری در حال بررسی است؛ بعداً دوباره تلاش کنید.',409 ); }
	$error = null; $payload = null; $previous = null; $confirmed = false; $may_have_written = false;
	try {
		if ( $real !== genosys_php_editor_resolve( $file ) ) { throw new RuntimeException( 'path' ); }
		$previous = @file_get_contents( $real );
		if ( false === $previous || ! hash_equals( hash( 'sha256',$previous ),$expected ) ) { $error = array( 'conflict','نسخهٔ فایل در سرور تغییر کرده است؛ دوباره آن را بارگذاری کنید.',409,array() ); }
		else {
			$validation = genosys_php_editor_validate_source( $file,$content,$previous,$files );
			if ( ! $validation['valid'] ) { $error = array( 'validation','کد دارای خطا یا تغییر غیرقابل تأیید است؛ فایل ذخیره نشد.',422,array( 'validation'=>$validation ) ); }
			elseif ( $previous === $content ) { $payload = genosys_php_editor_payload( $file,$real,$content ); $payload['validation']=$validation; $confirmed=true; }
			else {
				if ( ! function_exists( 'wp_edit_theme_plugin_file' ) ) { require_once ABSPATH . 'wp-admin/includes/file.php'; }
				if ( ! function_exists( 'wp_edit_theme_plugin_file' ) ) { throw new RuntimeException( 'native_unavailable' ); }
				$preflight = genosys_php_editor_probe( $file );
				if ( is_wp_error( $preflight ) ) {
					$validation['valid']=false;
					$validation['errors'][]=array( 'line'=>1,'code'=>'runtime_unavailable','message'=>$preflight->get_error_message() );
					foreach ( $validation['checks'] as &$check ) { if ( 'runtime' === $check['id'] ) { $check['status']='unsupported'; $check['message']=$preflight->get_error_message(); } } unset( $check );
					$error = array( $preflight->get_error_code(),$preflight->get_error_message(),503,array( 'validation'=>$validation ) );
				}
				elseif ( ! genosys_php_editor_remember( $file,$previous ) ) { throw new RuntimeException( 'backup' ); }
				elseif ( $real !== genosys_php_editor_resolve( $file ) || ! hash_equals( $expected,(string) @hash_file( 'sha256',$real ) ) ) { $error = array( 'conflict','فایل هنگام بررسی تغییر کرده است؛ ذخیره انجام نشد.',409,array() ); }
				else {
					// Keep a verified old snapshot and recover if this request dies mid-guard.
					$may_have_written = true;
					register_shutdown_function( static function() use ( &$confirmed,&$may_have_written,$real,$previous,$content,$file ) {
						if ( ! $confirmed && $may_have_written ) { genosys_php_editor_restore_owned( $file,$real,$previous,$content ); }
					} );
					$result = wp_edit_theme_plugin_file( array( 'file'=>$file,'theme'=>get_template(),'newcontent'=>$content,'nonce'=>wp_create_nonce( 'edit-theme_' . get_template() . '_' . $file ) ) );
					if ( true === $result ) { $result = genosys_php_editor_probe( $file,true ); }
					if ( true !== $result || hash( 'sha256',$content ) !== @hash_file( 'sha256',$real ) ) {
						$restored = genosys_php_editor_restore_owned( $file,$real,$previous,$content ); $may_have_written = ! $restored;
						$data = is_wp_error( $result ) ? $result->get_error_data() : array();
						$validation['valid']=false; $validation['errors'][]=array( 'line'=>is_array( $data ) ? (int) ( $data['line'] ?? 1 ) : 1,'code'=>'runtime','message'=>is_wp_error( $result ) ? $result->get_error_message() : 'نتیجهٔ ذخیرهٔ سرور قابل تأیید نیست.' );
						foreach ( $validation['checks'] as &$check ) { if ( 'runtime' === $check['id'] ) { $check['status']='error'; $check['message']='آزمون اجرای واقعی ناموفق بود؛ تغییر تأیید نشد.'; } } unset( $check );
						$error = array( $restored ? 'runtime_rollback' : 'recovery_required',$restored ? 'تغییر به‌دلیل خطای اجرای PHP یا آزمون ناموفق رد شد؛ کد قبلی بازیابی شد.' : 'بازیابی خودکار کامل نشد؛ از تاریخچه و دسترسی میزبان برای بازیابی فایل استفاده کنید.',500,array( 'validation'=>$validation,'restored'=>$restored ) );
					} else {
						$confirmed=true; $may_have_written=false;
						$validation['runtimeVerified']=true; $validation['scope']='تجزیه و بررسی محافظه‌کارانهٔ تغییرها، به‌علاوهٔ اجرای واقعی صفحهٔ اصلی، ویرایشگر مدیر وردپرس و تنظیمات قالب؛ همهٔ مسیرهای شرطی پوشش داده نشده‌اند.';
						foreach ( $validation['checks'] as &$check ) { if ( 'runtime' === $check['id'] ) { $check['status']='ok'; $check['message']='صفحهٔ اصلی، ویرایشگر مدیر و تنظیمات قالب در آزمون واقعی بدون خطای مهلک اجرا شدند.'; } } unset( $check );
						$remembered = genosys_php_editor_remember( $file,$content ); $payload = genosys_php_editor_payload( $file,$real,$content ); $payload['validation']=$validation;
						if ( ! $remembered ) { $payload['historyWarning']='فایل تأیید و ذخیره شد؛ ثبت نسخهٔ جدید در تاریخچه ممکن نشد.'; }
					}
				}
			}
		}
	} catch ( Throwable $exception ) {
		$restored = ! $may_have_written || null === $previous || genosys_php_editor_restore_owned( $file,$real,$previous,$content );
		$may_have_written = ! $restored;
		$error = array( $restored ? 'server_guard' : 'recovery_required',$restored ? 'ذخیرهٔ محافظت‌شده کامل نشد؛ کد قبلی حفظ یا بازیابی شد.' : 'بازیابی خودکار کامل نشد؛ فایل را از تاریخچه یا میزبان بازیابی کنید.',500,array( 'restored'=>$restored ) );
	} finally { @flock( $lock,LOCK_UN ); fclose( $lock ); }
	if ( $error ) { genosys_php_editor_error( $error[0],$error[1],$error[2],$error[3] ); }
	wp_send_json_success( $payload );
}
add_action( 'wp_ajax_genosys_php_editor_save','genosys_php_editor_save' );
