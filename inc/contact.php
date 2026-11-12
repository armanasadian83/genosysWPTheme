<?php
/** Contact presentation and a private, local message inbox. */
defined( 'ABSPATH' ) || exit;

function genosys_contact_defaults() {
	return array(
		'contact_page_title'=>'تماس با ما',
		'contact_hero_before'=>'با ما در', 'contact_hero_highlight'=>'تماس', 'contact_hero_after'=>'باشید',
		'contact_description_line1'=>'سؤال، پیشنهاد یا نیاز به مشاوره‌ی تخصصی پوست و مو دارید؟ تیم ژنوسیس',
		'contact_description_line2'=>'همین‌جاست؛ از هر راهی که راحت‌ترید با ما در ارتباط باشید.',
		'contact_status_open'=>'هم‌اکنون پاسخگو هستیم', 'contact_status_closed'=>'اکنون خارج از ساعات پاسخگویی هستیم',
		'contact_status_open_detail'=>'تا ساعت {time} امروز', 'contact_status_closed_detail'=>'در ساعات پاسخگویی همراه شما هستیم',
		'contact_phone_label'=>'تلفن پشتیبانی', 'contact_phone_value'=>'', 'contact_phone_button'=>'تماس', 'contact_phone_url'=>'',
		'contact_whatsapp_label'=>'واتساپ', 'contact_whatsapp_value'=>'', 'contact_whatsapp_button'=>'شروع گفتگو', 'contact_whatsapp_url'=>'',
		'contact_telegram_label'=>'تلگرام', 'contact_telegram_value'=>'', 'contact_telegram_button'=>'ارسال پیام', 'contact_telegram_url'=>'',
		'contact_email_label'=>'ایمیل', 'contact_email_value'=>'', 'contact_email_button'=>'ارسال ایمیل', 'contact_email_url'=>'',
		'contact_copy_label'=>'کپی', 'contact_copied_label'=>'کپی شد', 'contact_empty_label'=>'به‌زودی اعلام می‌شود',
		'contact_hours_title'=>'ساعات پاسخگویی', 'contact_hours_closed'=>'تعطیل', 'contact_today_label'=>'امروز',
		'contact_hours'=>array(
			array('label'=>'شنبه','open'=>'09:00','close'=>'18:00','closed'=>0),
			array('label'=>'یکشنبه','open'=>'09:00','close'=>'18:00','closed'=>0),
			array('label'=>'دوشنبه','open'=>'09:00','close'=>'18:00','closed'=>0),
			array('label'=>'سه‌شنبه','open'=>'09:00','close'=>'18:00','closed'=>0),
			array('label'=>'چهارشنبه','open'=>'09:00','close'=>'18:00','closed'=>0),
			array('label'=>'پنجشنبه','open'=>'09:00','close'=>'14:00','closed'=>0),
			array('label'=>'جمعه','open'=>'09:00','close'=>'18:00','closed'=>1),
		),
		'contact_address_label'=>'نشانی دفتر مرکزی', 'contact_address'=>'', 'contact_postal_label'=>'کد پستی', 'contact_postal'=>'',
		'contact_social_instagram'=>'', 'contact_social_telegram'=>'', 'contact_social_whatsapp'=>'', 'contact_social_linkedin'=>'',
		'contact_form_title'=>'پیامتان را برای ما بنویسید', 'contact_form_description'=>'معمولاً ظرف چند ساعت کاری پاسخ می‌دهیم.',
		'contact_topic_label'=>'موضوع پیام', 'contact_topic_support'=>'پشتیبانی سفارش', 'contact_topic_consultation'=>'مشاوره‌ی پوست و مو',
		'contact_topic_partnership'=>'همکاری و نمایندگی', 'contact_topic_feedback'=>'پیشنهاد و انتقاد', 'contact_topic_other'=>'سایر',
		'contact_name_label'=>'نام و نام خانوادگی', 'contact_mobile_label'=>'شماره موبایل', 'contact_email_input_label'=>'ایمیل (اختیاری)',
		'contact_message_label'=>'پیام شما', 'contact_attachment_label'=>'پیوست فایل (اختیاری)',
		'contact_attachment_hint'=>'تصویر یا PDF تا ۵ مگابایت', 'contact_consent_before'=>'با ثبت پیام،',
		'contact_privacy_label'=>'قوانین حریم خصوصی', 'contact_consent_after'=>'را می‌پذیرم.', 'contact_privacy_url'=>'',
		'contact_submit_label'=>'ارسال پیام', 'contact_form_success'=>'پیام شما با موفقیت ثبت شد. همکاران ما در ساعات پاسخگویی با شما تماس می‌گیرند.',
		'contact_notification_enabled'=>0, 'contact_notification_email'=>'',
		'contact_faq_before'=>'پاسخ', 'contact_faq_highlight'=>'سؤال‌های', 'contact_faq_after'=>'رایج',
		'contact_faq_description'=>'شاید پاسخ شما همین‌جا باشد.',
		'contact_faq'=>array(
			array('question'=>'سفارش من چه زمانی ارسال می‌شود؟','answer'=>'برای اطلاع از وضعیت و زمان ارسال سفارش خود، بخش «سفارشات من» در حساب کاربری را بررسی کنید یا با پشتیبانی در ارتباط باشید.'),
			array('question'=>'چطور فیش واریزی را ارسال کنم؟','answer'=>'وارد حساب کاربری شوید و در بخش «سفارشات من»، جزئیات سفارش را باز کنید. اگر سفارش امکان بارگذاری فیش داشته باشد، فایل فیش را از همان بخش ارسال کنید.'),
			array('question'=>'محصولات اصل هستند؟','answer'=>'برای دریافت اطلاعات اصالت، مشخصات و روش استفاده‌ی هر محصول با تیم پشتیبانی تماس بگیرید.'),
			array('question'=>'برای مشاوره‌ی تخصصی پوست و مو چه کنم؟','answer'=>'در فرم همین صفحه، موضوع «مشاوره‌ی پوست و مو» را انتخاب کنید و پرسش خود را برای ما بنویسید.'),
			array('question'=>'چگونه به باشگاه مشتریان بپیوندم؟','answer'=>'از بخش «باشگاه مشتریان» سایت، نوع عضویت خود را انتخاب و پرسشنامه را تکمیل کنید.'),
		),
	);
}

function genosys_contact_setting( $key ) {
	$key = 0 === strpos( $key, 'contact_' ) ? $key : 'contact_' . $key;
	$defaults = genosys_contact_defaults();
	return genosys_site_setting( $key, $defaults[ $key ] ?? '' );
}

function genosys_contact_ascii( $value ) {
	return strtr( (string) $value, array('۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9') );
}

function genosys_contact_digits( $value ) {
	return strtr( (string) $value, array('0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹') );
}

function genosys_contact_length( $value ) {
	return function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : preg_match_all( '/./us', $value, $unused );
}

function genosys_contact_time_valid( $value ) {
	return is_string( $value ) && (bool) preg_match( '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $value );
}

function genosys_contact_link( $value, $schemes = array( 'http', 'https' ) ) {
	if ( ! is_string( $value ) || '' === trim( $value ) ) { return ''; }
	$value = trim( $value );
	$scheme = strtolower( (string) wp_parse_url( $value, PHP_URL_SCHEME ) );
	if ( ! in_array( $scheme, $schemes, true ) ) { return ''; }
	if ( in_array( $scheme, array('http','https'), true ) && ( ! preg_match('~^https?://~i',$value) || ! wp_parse_url($value,PHP_URL_HOST) ) ) { return ''; }
	if ( 'tel' === $scheme && ! preg_match('/^tel:\+?[0-9() .-]{6,30}$/Di',$value) ) { return ''; }
	if ( 'mailto' === $scheme && ! is_email( substr($value,7) ) ) { return ''; }
	return esc_url_raw( $value, $schemes );
}

/** This model is shared by the contact template and public form validation. */
function genosys_contact_page_data( $at = null ) {
	$data = array();
	foreach ( array('hero_before','hero_highlight','hero_after','description_line1','description_line2','copy_label','copied_label','empty_label','hours_title','today_label','address_label','address','postal_label','postal') as $key ) { $data[ $key ] = (string) genosys_contact_setting( $key ); }
	$data['channels'] = array();
	foreach ( array('phone','whatsapp','telegram','email') as $channel ) {
		$value = (string) genosys_contact_setting( $channel . '_value' );
		$url = genosys_contact_link( genosys_contact_setting( $channel . '_url' ), array('http','https','tel','mailto') );
		if ( ! $url && '' !== $value ) {
			$number = preg_replace( '/[^0-9+]/', '', genosys_contact_ascii( $value ) );
			if ( 'phone' === $channel && preg_match( '/^\+?[0-9]{6,16}$/D', $number ) ) { $url = 'tel:' . $number; }
			if ( 'email' === $channel && is_email( $value ) ) { $url = 'mailto:' . sanitize_email( $value ); }
			if ( 'telegram' === $channel && preg_match( '/^@?([A-Za-z][A-Za-z0-9_]{3,31})$/D', $value, $matches ) ) { $url = 'https://t.me/' . $matches[1]; }
			if ( 'whatsapp' === $channel ) {
				$number = ltrim( $number, '+' );
				if ( 0 === strpos( $number, '00' ) ) { $number = substr( $number, 2 ); }
				if ( preg_match( '/^09[0-9]{9}$/D', $number ) ) { $number = '98' . substr( $number, 1 ); }
				if ( preg_match( '/^[1-9][0-9]{7,14}$/D', $number ) ) { $url = 'https://wa.me/' . $number; }
			}
		}
		$data['channels'][ $channel ] = array('label'=>(string)genosys_contact_setting($channel.'_label'),'value'=>$value,'button'=>(string)genosys_contact_setting($channel.'_button'),'url'=>$url,'copy'=>$value,'placeholder'=>$data['empty_label']);
	}
	$defaults = genosys_contact_defaults();
	$hours = genosys_contact_setting( 'hours' );
	$now = $at instanceof DateTimeInterface ? (new DateTimeImmutable( $at->format( 'Y-m-d H:i:s.u' ), $at->getTimezone() ))->setTimezone( wp_timezone() ) : current_datetime();
	$today = ( (int) $now->format( 'w' ) + 1 ) % 7;
	$time = $now->format( 'H:i' );
	$data['hours'] = array();
	$is_open = false;
	$closing = '';
	foreach ( $defaults['contact_hours'] as $index => $base ) {
		$row = isset( $hours[ $index ] ) && is_array( $hours[ $index ] ) ? $hours[ $index ] : $base;
		$open = genosys_contact_time_valid( $row['open'] ?? null ) ? $row['open'] : $base['open'];
		$close = genosys_contact_time_valid( $row['close'] ?? null ) ? $row['close'] : $base['close'];
		$closed = ! empty( $row['closed'] ) || $open >= $close;
		$format = static function( $clock ) { $hour = (string) (int) substr( $clock, 0, 2 ); return genosys_contact_digits( ':00' === substr( $clock, -3 ) ? $hour : $hour . ':' . substr( $clock, 3, 2 ) ); };
		$data['hours'][] = array('day'=>$index,'label'=>is_string($row['label']??null)?$row['label']:$base['label'],'open'=>$open,'close'=>$close,'closed'=>$closed,'today'=>$index===$today,'today_label'=>$data['today_label'],'text'=>$closed?(string)genosys_contact_setting('hours_closed'):$format($open).' تا '.$format($close));
		if ( $index === $today ) { $is_open = ! $closed && $time >= $open && $time < $close; $closing = $format( $close ); }
	}
	$data['status'] = array('open'=>$is_open,'label'=>(string)genosys_contact_setting($is_open?'status_open':'status_closed'),'detail'=>str_replace('{time}',$closing,(string)genosys_contact_setting($is_open?'status_open_detail':'status_closed_detail')));
	$data['socials'] = array();
	foreach ( array('instagram','telegram','whatsapp','linkedin') as $network ) { $data['socials'][$network] = genosys_contact_link( genosys_contact_setting( 'social_' . $network ) ); }
	$form = array();
	foreach ( array('title'=>'form_title','description'=>'form_description','topic_label'=>'topic_label','name_label'=>'name_label','mobile_label'=>'mobile_label','email_label'=>'email_input_label','message_label'=>'message_label','attachment_label'=>'attachment_label','attachment_hint'=>'attachment_hint','consent_before'=>'consent_before','privacy_label'=>'privacy_label','consent_after'=>'consent_after','submit_label'=>'submit_label','success'=>'form_success') as $key=>$setting ) { $form[$key] = (string) genosys_contact_setting( $setting ); }
	$form['topics'] = array();
	foreach ( array('support','consultation','partnership','feedback','other') as $topic ) { $form['topics'][$topic] = (string) genosys_contact_setting( 'topic_' . $topic ); }
	$form['privacy_url'] = genosys_contact_link( genosys_contact_setting('privacy_url') ) ?: get_privacy_policy_url();
	$data['form'] = $form;
	$data['faq'] = array('before'=>(string)genosys_contact_setting('faq_before'),'highlight'=>(string)genosys_contact_setting('faq_highlight'),'after'=>(string)genosys_contact_setting('faq_after'),'description'=>(string)genosys_contact_setting('faq_description'),'items'=>array());
	$faqs = genosys_contact_setting( 'faq' );
	foreach ( array_slice( is_array($faqs)?$faqs:array(), 0, 30 ) as $row ) {
		if ( is_array($row) && is_string($row['question']??null) && is_string($row['answer']??null) && '' !== trim($row['question']) && '' !== trim($row['answer']) ) { $data['faq']['items'][] = array('question'=>$row['question'],'answer'=>$row['answer']); }
	}
	return $data;
}

function genosys_contact_register_messages() {
	// These are already primitive administrator capabilities. Mapping them again
	// would register manage_options as a post meta-capability for the whole site.
	register_post_type( 'genosys_contact', array('label'=>'پیام‌های تماس','public'=>false,'publicly_queryable'=>false,'exclude_from_search'=>true,'show_ui'=>false,'show_in_menu'=>false,'show_in_rest'=>false,'rewrite'=>false,'query_var'=>false,'can_export'=>false,'supports'=>array('title'),'capability_type'=>'genosys_contact','map_meta_cap'=>false,'capabilities'=>array('read_post'=>'manage_options','edit_post'=>'manage_options','delete_post'=>'manage_options','read'=>'manage_options','edit_posts'=>'manage_options','edit_others_posts'=>'manage_options','edit_private_posts'=>'manage_options','edit_published_posts'=>'manage_options','publish_posts'=>'manage_options','read_private_posts'=>'manage_options','delete_posts'=>'manage_options','delete_private_posts'=>'manage_options','delete_published_posts'=>'manage_options','delete_others_posts'=>'manage_options','create_posts'=>'manage_options')) );
}
add_action( 'init', 'genosys_contact_register_messages' );

function genosys_contact_post_text( $key, $alias = '' ) {
	$value = $_POST[ $key ] ?? ( $alias ? ( $_POST[ $alias ] ?? '' ) : '' );
	return is_string( $value ) ? trim( wp_unslash( $value ) ) : null;
}

function genosys_contact_error( $message, $status = 400, $fields = array() ) {
	$data = array('message'=>$message);
	if ( $fields ) { $data['fields'] = $fields; }
	wp_send_json_error( $data, $status );
}

/** The browser never receives a URL for an attachment: it remains private post metadata. */
function genosys_contact_read_attachment() {
	if ( ! isset( $_FILES['attachment'] ) ) { return null; }
	$file = $_FILES['attachment'];
	if ( ! is_array($file) || ! isset($file['error']) || is_array($file['error']) || ! is_numeric($file['error']) ) { return new WP_Error('attachment','فایل پیوست معتبر نیست.'); }
	if ( UPLOAD_ERR_NO_FILE === (int)$file['error'] ) { return null; }
	if ( UPLOAD_ERR_OK !== (int)$file['error'] ) { return new WP_Error('attachment','فایل به‌طور کامل دریافت نشد یا حجم آن بیش از حد مجاز است.'); }
	if ( ! is_string($file['tmp_name']??null) || ! is_string($file['name']??null) || ! is_uploaded_file($file['tmp_name']) ) { return new WP_Error('attachment','فایل پیوست معتبر نیست.'); }
	$size = filesize( $file['tmp_name'] );
	if ( false === $size || $size < 1 || $size > 5*1024*1024 ) { return new WP_Error('attachment','حجم فایل باید حداکثر ۵ مگابایت باشد.'); }
	$name = sanitize_file_name( $file['name'] );
	$extension = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
	$allowed = array('jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','pdf'=>'application/pdf');
	if ( ! isset($allowed[$extension]) || strlen($name)>255 ) { return new WP_Error('attachment','تنها فایل JPG، PNG، WebP یا PDF قابل ارسال است.'); }
	$bytes = file_get_contents( $file['tmp_name'], false, null, 0, 5*1024*1024+1 );
	if ( ! is_string($bytes) || strlen($bytes)!==$size ) { return new WP_Error('attachment','خواندن فایل پیوست ممکن نشد.'); }
	$mime = function_exists('finfo_open') ? (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes) : '';
	$checked = wp_check_filetype_and_ext( $file['tmp_name'], $name, array('jpg|jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','pdf'=>'application/pdf') );
	if ( empty($checked['ext']) || empty($checked['type']) || $checked['type'] !== $allowed[$extension] ) { return new WP_Error('attachment','محتوای فایل با پسوند آن مطابقت ندارد.'); }
	if ( 'pdf' === $extension ) { $valid = 'application/pdf'===$mime && 0===strpos($bytes,'%PDF-'); }
	else { $detected = wp_get_image_mime($file['tmp_name']); $valid = $allowed[$extension]===$detected && (!$mime || $mime===$detected); }
	if ( ! $valid ) { return new WP_Error('attachment','محتوای فایل با نوع مجاز آن مطابقت ندارد.'); }
	return array('filename'=>$name,'mime'=>$allowed[$extension],'bytes'=>$size,'base64'=>base64_encode($bytes),'sha256'=>hash('sha256',$bytes));
}

function genosys_contact_submit() {
	if ( 'POST' !== ($_SERVER['REQUEST_METHOD']??'') ) { genosys_contact_error('روش ارسال معتبر نیست.',405); }
	$nonce = genosys_contact_post_text('nonce');
	if ( ! is_string($nonce) || ! wp_verify_nonce($nonce,'genosys_contact_submit') ) { genosys_contact_error('اعتبار فرم تمام شده است. صفحه را تازه کنید و دوباره بفرستید.',403); }
	$trap = genosys_contact_post_text('website');
	if ( null === $trap ) { genosys_contact_error('اطلاعات فرم معتبر نیست.'); }
	if ( '' !== $trap ) { wp_send_json_success(array('message'=>(string)genosys_contact_setting('form_success'))); }
	$ip = isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
	$rate_key = 'genosys_contact_rate_' . substr(hash_hmac('sha256',$ip,wp_salt('nonce')),0,32);
	$attempts = (int)get_transient($rate_key);
	if ( $attempts >= 6 ) { genosys_contact_error('تعداد درخواست‌ها زیاد است. کمی بعد دوباره تلاش کنید.',429); }
	set_transient($rate_key,$attempts+1,15*MINUTE_IN_SECONDS);
	$name = genosys_contact_post_text('fullname','name');
	$mobile = genosys_contact_post_text('mobile','phone');
	$email = genosys_contact_post_text('email');
	$topic = genosys_contact_post_text('topic');
	$message = genosys_contact_post_text('message');
	$consent = genosys_contact_post_text('consent','privacy');
	$errors = array();
	if ( ! is_string($name) || genosys_contact_length($name)<2 || genosys_contact_length($name)>100 || sanitize_text_field($name)!==$name ) { $errors['fullname']='نام و نام خانوادگی را به‌درستی وارد کنید (۲ تا ۱۰۰ نویسه).'; }
	if ( is_string($mobile) ) { $mobile = preg_replace('/[\s()\-]/u','',genosys_contact_ascii($mobile)); if (0===strpos($mobile,'0098')){$mobile='0'.substr($mobile,4);} elseif(0===strpos($mobile,'+98')){$mobile='0'.substr($mobile,3);} }
	if ( ! is_string($mobile) || ! preg_match('/^09[0-9]{9}$/D',$mobile) ) { $errors['mobile']='شماره موبایل معتبر وارد کنید.'; }
	if ( ! is_string($email) || (''!==$email && (strlen($email)>254 || !is_email($email))) ) { $errors['email']='ایمیل معتبر وارد کنید یا این کادر را خالی بگذارید.'; }
	if ( ! is_string($topic) || !in_array($topic,array('support','consultation','partnership','feedback','other'),true) ) { $errors['topic']='موضوع پیام را انتخاب کنید.'; }
	if ( ! is_string($message) || genosys_contact_length($message)<1 || genosys_contact_length($message)>500 || wp_check_invalid_utf8($message)!==$message ) { $errors['message']='پیام باید بین ۱ تا ۵۰۰ نویسه باشد.'; }
	if ( '1' !== $consent ) { $errors['consent']='پذیرش قوانین حریم خصوصی لازم است.'; }
	if ( $errors ) { genosys_contact_error('لطفاً اطلاعات مشخص‌شده را اصلاح کنید.',400,$errors); }
	$mobile_key='genosys_contact_mobile_'.substr(hash_hmac('sha256',$mobile,wp_salt('nonce')),0,32);
	if ( (int)get_transient($mobile_key)>=3 ) { genosys_contact_error('پیام‌های قبلی این شماره دریافت شده است. کمی بعد دوباره تلاش کنید.',429); }
	$attachment=genosys_contact_read_attachment();
	if ( is_wp_error($attachment) ) { genosys_contact_error($attachment->get_error_message(),400,array('attachment'=>$attachment->get_error_message())); }
	$data=genosys_contact_page_data();
	$id=wp_insert_post(wp_slash(array('post_type'=>'genosys_contact','post_status'=>'private','post_title'=>$name.' — '.$data['form']['topics'][$topic],'post_content'=>'','post_author'=>get_current_user_id())),true);
	if ( is_wp_error($id) || !$id ) { genosys_contact_error('ثبت پیام ممکن نشد. لطفاً کمی بعد دوباره تلاش کنید.',500); }
	$record=array('fullname'=>sanitize_text_field($name),'mobile'=>$mobile,'email'=>sanitize_email($email),'topic'=>$topic,'topic_label'=>$data['form']['topics'][$topic],'message'=>sanitize_textarea_field($message),'consent'=>true,'submitted_at'=>current_time('mysql'),'user_id'=>get_current_user_id());
	$stored=add_post_meta($id,'_genosys_contact_data',wp_slash($record),true);
	$file_stored=null===$attachment?true:add_post_meta($id,'_genosys_contact_attachment',wp_slash($attachment),true);
	if ( !$stored || !$file_stored ) { wp_delete_post($id,true); genosys_contact_error('ثبت پیام کامل نشد. لطفاً دوباره تلاش کنید.',500); }
	set_transient($mobile_key,(int)get_transient($mobile_key)+1,HOUR_IN_SECONDS);
	if ( '1'===(string)genosys_contact_setting('notification_enabled') ) {
		$recipient=(string)genosys_contact_setting('notification_email');
		if ( is_email($recipient) ) {
			try { wp_mail($recipient,'پیام تازه در صفحه تماس ژنوسیس','یک پیام تازه در بخش تماس ثبت شد. برای مشاهده، وارد مدیریت سایت شوید: '.admin_url('admin.php?page=genosys-contact-messages&message='.$id)); }
			catch ( Throwable $error ) { /* The private message is already saved; a mail transport cannot turn it into a duplicate submission. */ }
		}
	}
	wp_send_json_success(array('message'=>(string)genosys_contact_setting('form_success')));
}
add_action('wp_ajax_genosys_contact_submit','genosys_contact_submit');
add_action('wp_ajax_nopriv_genosys_contact_submit','genosys_contact_submit');

function genosys_contact_admin_menu() {
	add_submenu_page('genosys-theme-settings','پیام‌های تماس','پیام‌های تماس','manage_options','genosys-contact-messages','genosys_contact_render_inbox');
}
add_action('admin_menu','genosys_contact_admin_menu',30);

function genosys_contact_render_inbox() {
	if(!current_user_can('manage_options')){wp_die('اجازه مشاهده پیام‌ها را ندارید.','',array('response'=>403));}
	echo '<div class="wrap" dir="rtl"><h1>پیام‌های تماس</h1><p>پیام‌ها و فایل‌های این بخش خصوصی هستند و تنها مدیران سایت به آن‌ها دسترسی دارند.</p>';
	$id=isset($_GET['message'])&&is_scalar($_GET['message'])?absint($_GET['message']):0;
	if($id){
		$post=get_post($id);
		if(!$post||'genosys_contact'!==$post->post_type){echo '<div class="notice notice-error"><p>پیام پیدا نشد.</p></div></div>';return;}
		$record=get_post_meta($id,'_genosys_contact_data',true);$record=is_array($record)?$record:array();
		echo '<p><a class="button" href="'.esc_url(admin_url('admin.php?page=genosys-contact-messages')).'">بازگشت به پیام‌ها</a></p><table class="widefat striped"><tbody>';
		foreach(array('fullname'=>'نام و نام خانوادگی','mobile'=>'موبایل','email'=>'ایمیل','topic_label'=>'موضوع','submitted_at'=>'تاریخ ثبت') as $key=>$label){echo '<tr><th scope="row">'.esc_html($label).'</th><td>'.esc_html(is_scalar($record[$key]??null)?$record[$key]:'—').'</td></tr>';}
		echo '<tr><th scope="row">پیام</th><td style="white-space:pre-wrap">'.esc_html(is_string($record['message']??null)?$record['message']:'').'</td></tr></tbody></table>';
		$file=get_post_meta($id,'_genosys_contact_attachment',true);
		if(is_array($file)&&isset($file['filename'])){$url=wp_nonce_url(admin_url('admin-post.php?action=genosys_contact_download&id='.$id),'genosys_contact_download_'.$id);echo '<p><a class="button button-primary" href="'.esc_url($url).'">دریافت پیوست خصوصی: '.esc_html($file['filename']).'</a></p>';}
		echo '</div>';return;
	}
	$page=isset($_GET['paged'])&&is_scalar($_GET['paged'])?max(1,absint($_GET['paged'])):1;
	$query=new WP_Query(array('post_type'=>'genosys_contact','post_status'=>'private','posts_per_page'=>20,'paged'=>$page,'orderby'=>'date','order'=>'DESC'));
	echo '<table class="widefat striped"><thead><tr><th>نام</th><th>موبایل</th><th>موضوع</th><th>تاریخ</th><th>جزئیات</th></tr></thead><tbody>';
	foreach($query->posts as $post){$record=get_post_meta($post->ID,'_genosys_contact_data',true);$record=is_array($record)?$record:array();echo '<tr>';foreach(array('fullname','mobile','topic_label','submitted_at') as $key){echo '<td>'.esc_html(is_scalar($record[$key]??null)?$record[$key]:'—').'</td>';}echo '<td><a class="button" href="'.esc_url(add_query_arg('message',$post->ID,admin_url('admin.php?page=genosys-contact-messages'))).'">مشاهده</a></td></tr>';}
	if(!$query->posts){echo '<tr><td colspan="5">هنوز پیامی ثبت نشده است.</td></tr>';}
	echo '</tbody></table>';
	if($query->max_num_pages>1){echo '<div class="tablenav"><div class="tablenav-pages">'.wp_kses_post(paginate_links(array('base'=>add_query_arg('paged','%#%',admin_url('admin.php?page=genosys-contact-messages')),'format'=>'','current'=>$page,'total'=>$query->max_num_pages))).'</div></div>';}
	echo '</div>';
}

function genosys_contact_download() {
	if(!current_user_can('manage_options')){wp_die('اجازه دریافت فایل را ندارید.','',array('response'=>403));}
	$id=isset($_GET['id'])&&is_scalar($_GET['id'])?absint($_GET['id']):0;
	$nonce=$_GET['_wpnonce']??'';
	if(!is_string($nonce)||!wp_verify_nonce($nonce,'genosys_contact_download_'.$id)){wp_die('اعتبار پیوند دریافت فایل تمام شده است.','',array('response'=>403));}
	$post=get_post($id);$file=$post&&'genosys_contact'===$post->post_type?get_post_meta($id,'_genosys_contact_attachment',true):null;
	if(!is_array($file)||!is_string($file['base64']??null)||!is_string($file['sha256']??null)){wp_die('فایل پیدا نشد.','',array('response'=>404));}
	$bytes=base64_decode($file['base64'],true);
	$allowed=array('image/jpeg','image/png','image/webp','application/pdf');
	if(!is_string($bytes)||strlen($bytes)>5*1024*1024||!hash_equals($file['sha256'],hash('sha256',$bytes))||!in_array($file['mime']??'', $allowed,true)){wp_die('فایل معتبر نیست.','',array('response'=>404));}
	nocache_headers();header('X-Content-Type-Options: nosniff');header('Content-Security-Policy: sandbox');header('Content-Type: application/octet-stream');header('Content-Length: '.strlen($bytes));
	$filename=sanitize_file_name(is_string($file['filename']??null)?$file['filename']:'attachment');
	header('Content-Disposition: attachment; filename="attachment.'.sanitize_key(pathinfo($filename,PATHINFO_EXTENSION)).'"; filename*=UTF-8\'\''.rawurlencode($filename));
	echo $bytes;exit;
}
add_action('admin_post_genosys_contact_download','genosys_contact_download');
add_action('admin_post_nopriv_genosys_contact_download','genosys_contact_download');
