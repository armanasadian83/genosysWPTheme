<?php
/** Section-based theme editor. Existing commerce options retain their original names. */
defined( 'ABSPATH' ) || exit;

function genosys_settings_tabs() {
	return array( 'general' => array( 'عمومی', 'settings' ), 'navbar' => array( 'نوار', 'menu' ), 'slides' => array( 'اسلایدر', 'image' ), 'home' => array( 'صفحه اصلی', 'home' ), 'featured' => array( 'محصولات منتخب', 'tag' ), 'invitation' => array( 'بنر دعوت', 'speaker' ), 'footer' => array( 'فوتر', 'footer' ), 'product' => array( 'صفحه محصول', 'tag' ), 'shop' => array( 'فروشگاه', 'cart' ), 'packages' => array( 'پکیج‌ها', 'gift' ), 'account' => array( 'حساب کاربری', 'account' ), 'headings' => array( 'تیتر ها', 'type' ), 'payment' => array( 'پرداخت و ارسال', 'card' ), 'orders' => array( 'سفارشات', 'bag' ), 'sms' => array( 'سرویس پیامک', 'message' ), 'club' => array( 'باشگاه مشتریان', 'gift' ), 'contact' => array( 'تماس با ما', 'message' ), 'brand' => array( 'برند', 'video' ), 'css-editor' => array( 'ویرایشگر CSS', 'code' ), 'php-editor' => array( 'ویرایشگر php', 'code' ) );
}

function genosys_settings_icon( $name ) {
	$paths = array(
		'settings' => '<path d="m9 3-1 3-3 1-2 3 2 2-1 3 3 2 3-1 2 3 3-1 1-3 3-1 1-3-2-2 1-3-3-2-3 1-2-3Z"/><circle cx="12" cy="12" r="3"/>',
		'menu' => '<path d="M3 5h18M3 11h18M3 17h12"/>',
		'image' => '<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8" cy="8" r="1.5"/><path d="m3 17 5-5 4 4 4-7 5 8"/>',
		'video' => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="m10 8 6 4-6 4Z"/>',
		'home' => '<path d="m3 10 9-8 9 8M5 8v13h5v-7h4v7h5V8"/>',
		'tag' => '<path d="M3 3h9l9 9-9 9-9-9Z"/><circle cx="8" cy="8" r="1"/>',
		'speaker' => '<path d="M4 9h5l10-5v16l-10-5H4ZM9 15l2 6M22 8v8"/>',
		'footer' => '<path d="M3 4h18M3 8h12"/><rect x="3" y="13" width="18" height="7" rx="2"/>',
		'cart' => '<path d="M2 4h3l2 11h12l2-8H6M8 18h11"/><circle cx="9" cy="21" r="1"/><circle cx="18" cy="21" r="1"/>',
		'type' => '<path d="M4 5h16M12 5v16M8 21h8M4 5v3M20 5v3"/>',
		'card' => '<rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20M6 15h4"/>',
		'bag' => '<path d="M5 8h14l-1 13H6ZM9 8V6a3 3 0 0 1 6 0v2"/>',
		'message' => '<path d="M21 11a9 9 0 0 1-9 9H4l-2 2 1-7a9 9 0 1 1 18-4Z"/><path d="M7 9h10M7 13h6"/>',
		'gift' => '<path d="M3 10h18v11H3ZM2 6h20v4H2ZM12 6v15M12 6C4 6 5 1 8 2c2 0 4 4 4 4Zm0 0c8 0 7-5 4-4-2 0-4 4-4 4Z"/>',
		'trash' => '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>',
		'up' => '<path d="m6 15 6-6 6 6"/>', 'down' => '<path d="m6 9 6 6 6-6"/>',
		'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
		'grip' => '<circle cx="9" cy="5" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="19" r="1"/>',
		'search' => '<circle cx="10.5" cy="10.5" r="7"/><path d="m16 16 5 5"/>',
		'account' => '<circle cx="12" cy="7" r="4"/><path d="M4 21c0-5 3-7 8-7s8 2 8 7"/>',
		'code' => '<path d="m8 7-5 5 5 5m8-10 5 5-5 5m-3-14-2 18"/>',
	);
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $paths[ $name ] ?? $paths['settings'] ) . '</svg>';
}

function genosys_settings_assets( $hook ) {
	if ( 'toplevel_page_genosys-theme-settings' !== $hook ) { return; }
	wp_enqueue_media();
	wp_enqueue_style( 'genosys-settings-dashboard', get_theme_file_uri( '/assets/css/settings-dashboard.css' ), array(), '1.5.64' );
	wp_enqueue_script( 'genosys-settings-dashboard', get_theme_file_uri( '/assets/js/settings-dashboard.js' ), array( 'media-views' ), '1.5.76', true );
	$editor = genosys_css_editor_bootstrap();
	wp_enqueue_style( 'genosys-css-editor', get_theme_file_uri( '/assets/css/css-editor.css' ), array( 'genosys-settings-dashboard' ), '1.5.67' );
	$dependencies = array( 'genosys-settings-dashboard' );
	if ( ! empty( $editor['codeEditor'] ) ) { $dependencies[] = 'code-editor'; }
	wp_enqueue_script( 'genosys-css-editor', get_theme_file_uri( '/assets/js/css-editor.js' ), $dependencies, '1.5.67', true );
	wp_add_inline_script( 'genosys-css-editor', 'window.genosysCSSEditor = ' . wp_json_encode( $editor, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
	$php_editor = genosys_php_editor_bootstrap();
	wp_enqueue_style( 'genosys-php-editor', get_theme_file_uri( '/assets/css/php-editor.css' ), array( 'genosys-css-editor' ), '1.5.68' );
	$php_dependencies = array( 'genosys-settings-dashboard' );
	if ( ! empty( $php_editor['codeEditor'] ) ) { $php_dependencies[] = 'code-editor'; }
	wp_enqueue_script( 'genosys-php-editor', get_theme_file_uri( '/assets/js/php-editor.js' ), $php_dependencies, '1.5.68', true );
	wp_add_inline_script( 'genosys-php-editor', 'window.genosysPHPEditor = ' . wp_json_encode( $php_editor, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
}
add_action( 'admin_enqueue_scripts', 'genosys_settings_assets' );

/** Only the declared field types may enter the public presentation settings. */
function genosys_settings_field_types() {
	$types = array();
	foreach ( array( 'site_name','site_slogan','header_search_placeholder','header_login_text','club_card_title','club_card_button','products_card_title','products_card_button','latest_title','categories_title','categories_highlight','categories_button','categories_brand_text','featured_eyebrow','featured_title','featured_highlight','featured_suffix','featured_link_text','invitation_button','footer_contact_text','footer_copyright','footer_tagline','shop_title','shop_highlight','orders_title','club_eyebrow','club_title_before','club_title_highlight','club_title_after','club_description_line1','club_description_line2','club_form_title','club_form_description','product_related_title','product_description_title','product_information_title','product_reviews_title' ) as $key ) { $types[ $key ] = 'text'; }
	foreach ( array( 'categories_description','featured_description' ) as $key ) { $types[ $key ] = 'textarea'; }
	foreach ( array( 'invitation_line1','invitation_line2','footer_description' ) as $key ) { $types[ $key ] = 'html'; }
	foreach ( array( 'header_logo_home','header_logo_internal','club_card_image','products_card_image','invitation_image','footer_logo','club_card_url','products_card_url','categories_brand_url','invitation_url','footer_contact_url','social_instagram','social_telegram','social_whatsapp','social_linkedin' ) as $key ) { $types[ $key ] = 'url'; }
	foreach ( array( 'primary_color','deep_color','header_icon_color_home','header_icon_color_internal','header_icon_hover' ) as $key ) { $types[ $key ] = 'color'; }
	foreach ( array( 'header_show_search','header_show_account','header_show_cart' ) as $key ) { $types[ $key ] = 'visibility'; }
	foreach ( array( 'header_link_color_home','header_link_color_internal','header_link_hover_home','header_link_hover_internal','header_background_internal','mobile_menu_color','mobile_menu_hover' ) as $key ) { $types[ $key ] = 'color'; }
	foreach ( array( 'header_background_home','mobile_menu_background' ) as $key ) { $types[ $key ] = 'optional_color'; }
	foreach ( array( 'latest_all_text','account_title','account_profile_label','account_addresses_label','account_notifications_label','account_club_label','account_orders_label','club_consumer_label','club_clinic_label','clinic_eyebrow','clinic_title_before','clinic_title_highlight','clinic_title_after','clinic_description_line1','clinic_description_line2','clinic_form_title','clinic_form_description' ) as $key ) { $types[ $key ] = 'text'; }
	$types['club_default_audience'] = 'audience';
	$types['brand_video_id'] = 'video_attachment';
	$types['product_cart_animation'] = 'bool';
	$types['product_related_count'] = 'number';
	$types['stats_visible'] = 'bool';
	foreach ( array( 'header_height','slider_interval','latest_count','featured_count','shop_per_page' ) as $key ) { $types[ $key ] = 'number'; }
	return $types;
}

function genosys_settings_invalid( $label ) {
	add_settings_error( 'genosys_settings_dashboard', 'invalid_' . sanitize_key( $label ), 'مقدار «' . $label . '» معتبر نیست؛ مقدار قبلی حفظ شد.', 'error' );
}

function genosys_settings_clean_value( $value, $type, $key, $previous ) {
	if ( ! is_scalar( $value ) || is_bool( $value ) ) { genosys_settings_invalid( $key ); return $previous; }
	$value = trim( (string) $value );
	if ( 'video_attachment' === $type ) {
		if ( '' === $value || '0' === $value ) { return 0; }
		$id = (int) $value;
		if ( $id > 0 && (string) $id === $value && preg_match( '/^[1-9][0-9]*$/D', $value ) && genosys_brand_video_attachment( $id ) ) { return $id; }
		genosys_settings_invalid( 'ویدیوی صفحه برند' );
		return $previous;
	}
	if ( 'optional_color' === $type && '' === $value ) { return ''; }
	if ( 'color' === $type || 'optional_color' === $type ) {
		$color = sanitize_hex_color( $value );
		if ( ! $color ) { genosys_settings_invalid( $key ); return $previous; }
		return $color;
	}
	if ( 'visibility' === $type ) { return in_array( $value, array( 'auto','yes','no' ), true ) ? $value : $previous; }
	if ( 'audience' === $type ) { return in_array( $value, array( 'consumer','clinic' ), true ) ? $value : $previous; }
	if ( 'bool' === $type ) {
		if ( ! in_array( $value, array( '0', '1' ), true ) ) { genosys_settings_invalid( $key ); return $previous; }
		return '1' === $value ? 1 : 0;
	}
	if ( 'url' === $type ) {
		if ( '' === $value ) { return ''; }
		$clean = esc_url_raw( $value, array( 'http','https' ) );
		if ( ! $clean || ! in_array( strtolower( (string) wp_parse_url( $clean, PHP_URL_SCHEME ) ), array( 'http','https' ), true ) ) { genosys_settings_invalid( $key ); return $previous; }
		return $clean;
	}
	if ( 'number' === $type ) {
		$ranges = array( 'header_height'=>array(76,180), 'slider_interval'=>array(2000,20000), 'latest_count'=>array(1,24), 'featured_count'=>array(1,24), 'shop_per_page'=>array(3,48), 'product_related_count'=>array(1,12), 'count'=>array(0,1000000) );
		$range = $ranges[ $key ] ?? array( 0, 1000000 );
		$value = genosys_checkout_ascii_digits( $value );
		if ( ! preg_match( '/^\d{1,8}$/D', $value ) || (int) $value < $range[0] || (int) $value > $range[1] ) { genosys_settings_invalid( $key ); return $previous; }
		return (int) $value;
	}
	if ( 'html' === $type ) { return wp_kses( $value, array( 'em'=>array(), 'br'=>array() ) ); }
	$value = 'textarea' === $type ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
	return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 2000 ) : substr( $value, 0, 4000 );
}

/** Merge submitted keys. A missing field never erases an existing option. */
function genosys_settings_sanitize_site( $input ) {
	$previous = get_option( 'genosys_site_settings', array() );
	$previous = is_array( $previous ) ? $previous : array();
	$defaults = genosys_site_defaults();
	if ( ! is_array( $input ) ) { return $previous; }
	$result = $previous;
	// A rendered, empty slider list is an intentional removal; partial saves omit this marker.
	if ( isset( $input['slides_present'] ) && '1' === $input['slides_present'] && ! array_key_exists( 'slides', $input ) ) { $result['slides'] = array(); }
	if ( isset( $input['featured_tabs_present'] ) && '1' === $input['featured_tabs_present'] && ! array_key_exists( 'featured_tabs', $input ) ) { $result['featured_tabs'] = array(); }
	foreach ( genosys_settings_field_types() as $key => $type ) {
		if ( array_key_exists( $key, $input ) ) { $result[ $key ] = genosys_settings_clean_value( $input[ $key ], $type, $key, $previous[ $key ] ?? ( $defaults[ $key ] ?? '' ) ); }
	}
	if ( isset( $input['home_sections_order'] ) && is_array( $input['home_sections_order'] ) ) {
		$ids = array( 'slider','cards','latest','categories','featured' );
		$order = array_values( array_unique( array_filter( $input['home_sections_order'], static function( $id ) use ( $ids ) { return is_string( $id ) && in_array( $id, $ids, true ); } ) ) );
		$enabled = isset( $input['home_sections_enabled'] ) && is_array( $input['home_sections_enabled'] ) ? $input['home_sections_enabled'] : array();
		if ( count( $order ) === count( $ids ) ) { $result['home_sections'] = array_map( static function( $id ) use ( $enabled ) { return array( 'id'=>$id, 'visible'=>in_array( $id, $enabled, true ) ? 1 : 0 ); }, $order ); }
	}
	$repeaters = array(
		'slides'=>array( 'image'=>'url','alt'=>'text','title'=>'text','link'=>'url','class'=>'slide_class' ),
		'category_cards'=>array( 'title'=>'text','image'=>'url','term_id'=>'term' ),
		'featured_tabs'=>array( 'label'=>'text','term_id'=>'term' ),
		'stats'=>array( 'count'=>'number','label'=>'text','icon'=>'icon' ),
	);
	foreach ( $repeaters as $key => $fields ) {
		if ( ! isset( $input[ $key ] ) || ! is_array( $input[ $key ] ) ) { continue; }
		if ( ! in_array( $key, array( 'slides', 'featured_tabs' ), true ) && count( $input[ $key ] ) !== count( $defaults[ $key ] ) ) { genosys_settings_invalid( $key ); continue; }
		$old = $previous[ $key ] ?? $defaults[ $key ];
		$old = is_array( $old ) ? $old : $defaults[ $key ];
		$rows = array();
		foreach ( array_slice( array_values( $input[ $key ] ), 0, 12 ) as $index => $row ) {
			if ( ! is_array( $row ) ) { continue; }
			$base = $old[ $index ] ?? ( $defaults[ $key ][ $index ] ?? array() );
			if ( 'featured_tabs' === $key && array_key_exists( '_source_index', $row ) ) {
				// Preserve automatic category aliases with their row when another row is removed.
				$source = is_scalar( $row['_source_index'] ) ? (string) $row['_source_index'] : '';
				$base = preg_match( '/^\d+$/D', $source ) && isset( $old[ (int) $source ] ) ? $old[ (int) $source ] : array();
			}
			foreach ( $fields as $field => $type ) {
				if ( ! array_key_exists( $field, $row ) ) { continue; }
				if ( 'term' === $type ) {
					$id = is_scalar( $row[ $field ] ) ? absint( $row[ $field ] ) : 0;
					$term = $id ? get_term( $id, 'product_cat' ) : null;
					if ( ! $id || ( $term && ! is_wp_error( $term ) ) ) { $base[ $field ] = $id; } else { genosys_settings_invalid( 'دسته‌بندی' ); }
				} elseif ( 'slide_class' === $type ) {
					if ( in_array( $row[ $field ], array( '', 'slide-skincare', 'slide-eco', 'slide-hair', 'slide-ampoules' ), true ) ) { $base[ $field ] = $row[ $field ]; }
				} elseif ( 'icon' === $type ) {
					if ( in_array( $row[ $field ], array( 'calendar','people','flask','globe' ), true ) ) { $base[ $field ] = $row[ $field ]; }
				} else { $base[ $field ] = genosys_settings_clean_value( $row[ $field ], $type, $field, $base[ $field ] ?? '' ); }
			}
			if ( 'slides' === $key ) {
				if ( empty( $base['image'] ) ) { continue; }
				$base['class'] = sanitize_html_class( $base['class'] ?? 'slide-skincare' );
			}
			if ( 'featured_tabs' === $key ) {
				$base['label'] = $base['label'] ?? '';
				$base['term_id'] = absint( $base['term_id'] ?? 0 );
				if ( '' === $base['label'] && $base['term_id'] ) {
					$selected = get_term( $base['term_id'], 'product_cat' );
					if ( $selected && ! is_wp_error( $selected ) ) { $base['label'] = $selected->name; }
				}
				if ( '' === $base['label'] ) { continue; }
				if ( ! $base['term_id'] && empty( $base['names'] ) ) { $base['names'] = array( $base['label'] ); }
			}
			$rows[] = $base;
		}
		$result[ $key ] = $rows;
	}
	if ( function_exists( 'genosys_contact_settings_sanitize' ) ) { $result = genosys_contact_settings_sanitize( $input, $result, $previous, $defaults ); }
	return $result;
}

function genosys_settings_save_dashboard() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'اجازه تغییر تنظیمات را ندارید.', '', array( 'response'=>403 ) ); }
	check_admin_referer( 'genosys_settings_dashboard' );
	$input = wp_unslash( $_POST );
	if ( isset( $input['site'] ) ) { update_option( 'genosys_site_settings', genosys_settings_sanitize_site( $input['site'] ), false ); }
	$legacy = array( 'genosys_shipping_cost'=>'genosys_sanitize_shipping_cost', 'genosys_checkout_card_number'=>'genosys_sanitize_checkout_card_number', 'genosys_checkout_card_holder'=>'genosys_sanitize_checkout_name', 'genosys_checkout_bank_name'=>'genosys_sanitize_checkout_name', 'genosys_checkout_whatsapp'=>'genosys_sanitize_checkout_whatsapp', 'genosys_order_sms_template_id'=>'genosys_sanitize_order_sms_template_id', 'genosys_order_status_sms_template_id'=>'genosys_sanitize_order_status_sms_template_id', 'genosys_receipt_approved_sms_template_id'=>'genosys_sanitize_receipt_approved_sms_template_id', 'genosys_receipt_rejected_sms_template_id'=>'genosys_sanitize_receipt_rejected_sms_template_id' );
	foreach ( $legacy as $key => $sanitize ) {
		if ( ! array_key_exists( $key, $input ) ) { continue; }
		if ( ! is_string( $input[ $key ] ) ) { genosys_settings_invalid( $key ); continue; }
		update_option( $key, $sanitize( $input[ $key ] ), false );
	}
	foreach ( array( 'custom_logo','site_icon' ) as $key ) {
		if ( ! isset( $input['native'][ $key ] ) || ! is_scalar( $input['native'][ $key ] ) ) { continue; }
		$id = absint( $input['native'][ $key ] );
		if ( $id && ! wp_attachment_is_image( $id ) ) { genosys_settings_invalid( $key ); continue; }
		if ( 'custom_logo' === $key ) { set_theme_mod( 'custom_logo', $id ); } else { update_option( 'site_icon', $id ); }
	}
	if ( isset( $input['sms_template'] ) ) {
		$value = is_string( $input['sms_template'] ) ? trim( genosys_checkout_ascii_digits( $input['sms_template'] ) ) : '';
		if ( preg_match( '/^[1-9][0-9]{0,9}$/D', $value ) && (float) $value <= 2147483647 ) { update_option( 'genosys_smsir_template_id', (int) $value, false ); } else { genosys_settings_invalid( 'شناسه قالب ورود' ); }
	}
	if ( isset( $input['sms_parameter'] ) ) {
		$value = is_string( $input['sms_parameter'] ) ? trim( $input['sms_parameter'] ) : '';
		if ( preg_match( '/^[A-Za-z][A-Za-z0-9_]{0,99}$/D', $value ) ) { update_option( 'genosys_smsir_parameter_name', $value, false ); } else { genosys_settings_invalid( 'نام متغیر کد' ); }
	}
	if ( isset( $input['sms_api_key'] ) && ! defined( 'GENOSYS_SMSIR_API_KEY' ) ) {
		$value = is_string( $input['sms_api_key'] ) ? trim( $input['sms_api_key'] ) : '';
		if ( '' !== $value ) {
			if ( strlen( $value ) >= 16 && strlen( $value ) <= 200 && preg_match( '/^[\x21-\x7E]+$/D', $value ) ) { update_option( 'genosys_smsir_api_key', $value, false ); } else { genosys_settings_invalid( 'کلید API' ); }
		}
	}
	if ( array_key_exists( 'club_sms_sender', $input ) && ! defined( 'GENOSYS_CLUB_SMS_SENDER' ) ) {
		$value = is_string( $input['club_sms_sender'] ) ? trim( genosys_checkout_ascii_digits( $input['club_sms_sender'] ) ) : null;
		if ( null !== $value && ( '' === $value || preg_match( '/^[1-9][0-9]{2,14}$/D', $value ) ) ) { update_option( 'genosys_club_sms_sender', $value, false ); }
		else { genosys_settings_invalid( 'خط ارسال پیامک باشگاه' ); }
	}
	$errors = get_settings_errors();
	set_transient( 'genosys_settings_notice_' . get_current_user_id(), $errors, 60 );
	$tab = isset( $input['active_tab'] ) && is_string( $input['active_tab'] ) ? sanitize_key( $input['active_tab'] ) : 'general';
	if ( ! isset( genosys_settings_tabs()[ $tab ] ) ) { $tab = 'general'; }
	wp_safe_redirect( add_query_arg( 'settings_saved', '1', admin_url( 'admin.php?page=genosys-theme-settings' ) ) . '#' . $tab );
	exit;
}
add_action( 'admin_post_genosys_settings_dashboard', 'genosys_settings_save_dashboard' );

function genosys_settings_card_open( $title, $description = '' ) {
	echo '<section class="gs-card"><div class="gs-card__heading"><h2>' . esc_html( $title ) . '</h2>';
	if ( $description ) { echo '<p>' . esc_html( $description ) . '</p>'; }
	echo '</div>';
}

function genosys_settings_field( $key, $label, $type = 'text', $args = array() ) {
	$name = $args['name'] ?? 'site[' . $key . ']';
	$value = array_key_exists( 'value', $args ) ? $args['value'] : genosys_site_setting( $key );
	$id = 'gs-' . sanitize_key( $key );
	$attr = ! empty( $args['preview'] ) ? ' data-preview-target="' . esc_attr( $args['preview'] ) . '"' : '';
	if ( ! empty( $args['preview_kind'] ) ) { $attr .= ' data-preview-kind="' . esc_attr( $args['preview_kind'] ) . '"'; }
	echo '<div class="gs-field"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
	if ( 'textarea' === $type ) { echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="3">' . esc_textarea( (string) $value ) . '</textarea>'; }
	elseif ( 'select' === $type ) {
		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . $attr . '>';
		foreach ( $args['options'] as $option => $text ) { echo '<option value="' . esc_attr( $option ) . '"' . selected( (string) $value, (string) $option, false ) . '>' . esc_html( $text ) . '</option>'; }
		echo '</select>';
	} elseif ( 'color' === $type || 'optional_color' === $type ) {
		$swatch = preg_match( '/^#[0-9a-fA-F]{3}$/D', (string) $value ) ? '#' . str_repeat( $value[1], 2 ) . str_repeat( $value[2], 2 ) . str_repeat( $value[3], 2 ) : ( $value ?: '#ffffff' );
		echo '<div class="gs-color"><input type="color" value="' . esc_attr( $swatch ) . '" aria-label="' . esc_attr( 'انتخاب ' . $label ) . '"><input id="' . esc_attr( $id ) . '" data-color-text name="' . esc_attr( $name ) . '" type="text" dir="ltr" pattern="#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})" value="' . esc_attr( $value ) . '"' . $attr . '></div>';
	} else {
		echo ( 'password' === $type ? '<div class="gs-password">' : '' ) . '<input id="' . esc_attr( $id ) . '" type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '"' . $attr;
		foreach ( array( 'min','max','step','placeholder','autocomplete' ) as $prop ) { if ( isset( $args[ $prop ] ) ) { echo ' ' . $prop . '="' . esc_attr( $args[ $prop ] ) . '"'; } }
		if ( in_array( $type, array( 'url','number','password' ), true ) || ! empty( $args['ltr'] ) ) { echo ' dir="ltr"'; }
		if ( ! empty( $args['readonly'] ) ) { echo ' readonly'; }
		echo '>';
		if ( 'password' === $type ) { echo '<button type="button" data-password-toggle aria-label="نمایش کلید API" aria-pressed="false">' . genosys_settings_icon( 'eye' ) . '</button></div>'; }
	}
	if ( ! empty( $args['hint'] ) ) { echo '<p class="gs-hint">' . esc_html( $args['hint'] ) . '</p>'; }
	echo '</div>';
}

function genosys_settings_media( $name, $value, $label, $hint = '', $native_id = null, $compact = false ) {
	$id = 'gs-media-' . sanitize_key( $name );
	echo '<div class="gs-field"><label>' . esc_html( $label ) . '</label><div class="gs-media" data-media><input type="hidden" data-media-value name="' . esc_attr( null === $native_id ? $name : '' ) . '" value="' . esc_attr( $value ) . '">';
	if ( null !== $native_id ) { echo '<input type="hidden" data-media-id name="' . esc_attr( $name ) . '" value="' . esc_attr( $native_id ) . '">'; }
	echo '<span class="gs-media__preview" data-media-preview>';
	if ( $value ) { echo '<img src="' . esc_url( $value ) . '" alt="' . esc_attr( $label ) . '">'; } else { echo genosys_settings_icon( 'image' ); }
	echo '</span><span class="gs-media__copy"><strong data-media-label>' . esc_html( $value ? wp_basename( wp_parse_url( $value, PHP_URL_PATH ) ) : 'تصویری انتخاب نشده' ) . '</strong><small>' . esc_html( $hint ) . '</small></span><span class="gs-media__actions"><button type="button" class="gs-outline" data-media-choose>' . ( $compact ? 'رسانه' : 'انتخاب تصویر' ) . '</button><button type="button" class="gs-media__remove" data-media-remove' . ( $value ? '' : ' hidden' ) . '>حذف تصویر</button></span></div></div>';
}

/** Store a media-library attachment ID rather than an arbitrary video URL. */
function genosys_settings_brand_video() {
	$video = genosys_brand_uploaded_video();
	$id = ! empty( $video['id'] ) ? (int) $video['id'] : 0;
	$url = ! empty( $video['url'] ) ? $video['url'] : '';
	$name = $url ? wp_basename( wp_parse_url( $url, PHP_URL_PATH ) ) : 'ویدیویی انتخاب نشده';
	echo '<div class="gs-field"><label for="gs-brand-video-id">ویدیوی اصلی صفحه برند</label><div class="gs-media" data-media data-media-kind="video"><input type="hidden" data-media-value value="' . esc_attr( $url ) . '"><input type="hidden" data-media-id id="gs-brand-video-id" name="site[brand_video_id]" value="' . esc_attr( $id ) . '">';
	echo '<span class="gs-media__preview" data-media-preview>' . genosys_settings_icon( 'video' ) . '</span><span class="gs-media__copy"><strong data-media-label>' . esc_html( $name ) . '</strong><small>ویدیوی بارگذاری‌شده در کتابخانه رسانه سایت ذخیره می‌شود و پس از ذخیره تنظیمات، جایگزین ویدیوی اصلی صفحه برند خواهد شد.</small></span><span class="gs-media__actions"><button type="button" class="gs-outline" data-media-choose>بارگذاری / انتخاب ویدیو</button><button type="button" class="gs-media__remove" data-media-remove' . ( $url ? '' : ' hidden' ) . '>حذف انتخاب ویدیو</button></span></div><p class="gs-hint">پیشنهاد: فایل MP4 با کدک H.264 برای پخش در موبایل و دسکتاپ. فرمت‌های MP4، WebM و OGV پشتیبانی می‌شوند. ویدیو به‌صورت خودکار، بی‌صدا و تکرارشونده پخش می‌شود.</p><p class="gs-hint" data-media-error role="alert" hidden></p></div>';
}

function genosys_settings_terms() {
	$options = array( 0=>'انتخاب خودکار فعلی' );
	if ( taxonomy_exists( 'product_cat' ) ) {
		$terms = get_terms( array( 'taxonomy'=>'product_cat', 'hide_empty'=>false ) );
		if ( ! is_wp_error( $terms ) ) { foreach ( $terms as $term ) { $options[ $term->term_id ] = $term->name; } }
	}
	return $options;
}

function genosys_settings_slide_row( $index, $slide ) {
	echo '<div class="gs-repeater__row gs-slide-row">';
	echo '<input type="hidden" name="site[slides][' . esc_attr( $index ) . '][class]" value="' . esc_attr( $slide['class'] ?? '' ) . '">';
	echo '<input type="hidden" name="site[slides][' . esc_attr( $index ) . '][alt]" value="' . esc_attr( $slide['alt'] ?? '' ) . '">';
	genosys_settings_media( 'site[slides][' . $index . '][image]', $slide['image'] ?? '', 'تصویر اسلاید', '', null, true );
	genosys_settings_field( 'slide-title-' . $index, 'تیتر (اختیاری)', 'text', array( 'name'=>'site[slides][' . $index . '][title]', 'value'=>$slide['title'] ?? '', 'placeholder'=>'تیتر (اختیاری)' ) );
	genosys_settings_field( 'slide-link-' . $index, 'لینک (اختیاری)', 'url', array( 'name'=>'site[slides][' . $index . '][link]', 'value'=>$slide['link'] ?? '', 'placeholder'=>'لینک (اختیاری)' ) );
	echo '<button type="button" class="gs-icon-button gs-danger" data-remove-row aria-label="حذف اسلاید">' . genosys_settings_icon( 'trash' ) . '</button></div>';
}

function genosys_settings_featured_row( $index, $row, $terms ) {
	echo '<div class="gs-repeater__row">';
	echo '<input type="hidden" name="site[featured_tabs][' . esc_attr( $index ) . '][_source_index]" value="' . esc_attr( is_numeric( $index ) ? $index : '' ) . '">';
	genosys_settings_field( 'featured-label-' . $index, 'عنوان تب', 'text', array( 'name'=>'site[featured_tabs][' . $index . '][label]', 'value'=>$row['label'] ?? '', 'placeholder'=>'خالی یعنی نام دسته‌بندی' ) );
	genosys_settings_field( 'featured-term-' . $index, 'دسته‌بندی ووکامرس', 'select', array( 'name'=>'site[featured_tabs][' . $index . '][term_id]', 'value'=>$row['term_id'] ?? 0, 'options'=>$terms ) );
	echo '<button type="button" class="gs-icon-button gs-danger" data-remove-row aria-label="حذف دسته‌بندی از محصولات منتخب">' . genosys_settings_icon( 'trash' ) . '</button></div>';
}

function genosys_settings_link( $url, $text ) { echo '<a class="gs-outline" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>'; }

function genosys_render_settings_dashboard() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$tabs = genosys_settings_tabs();
	$terms = genosys_settings_terms();
	?>
	<div class="wrap gs-settings" dir="rtl">
		<header class="gs-settings__header"><h1>تنظیمات قالب</h1><p>تنظیمات فروشگاه را از این بخش مدیریت کنید.</p></header>
		<?php
		if ( isset( $_GET['settings_saved'] ) ) {
			$errors = get_transient( 'genosys_settings_notice_' . get_current_user_id() );
			delete_transient( 'genosys_settings_notice_' . get_current_user_id() );
			if ( $errors ) { foreach ( $errors as $error ) { echo '<div class="notice notice-error"><p>' . esc_html( $error['message'] ) . '</p></div>'; } }
			else { echo '<div class="notice notice-success is-dismissible"><p>تنظیمات با موفقیت ذخیره شد.</p></div>'; }
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="gs-settings-form">
			<input type="hidden" name="action" value="genosys_settings_dashboard"><input type="hidden" name="active_tab" value="general" data-active-tab>
			<?php wp_nonce_field( 'genosys_settings_dashboard' ); ?>
			<div class="gs-tabs" role="tablist" aria-label="بخش‌های تنظیمات قالب">
				<?php foreach ( $tabs as $id => $tab ) : ?><button class="gs-tab" id="gs-tab-<?php echo esc_attr( $id ); ?>" type="button" role="tab" data-tab="<?php echo esc_attr( $id ); ?>" aria-controls="gs-panel-<?php echo esc_attr( $id ); ?>" aria-selected="<?php echo 'general' === $id ? 'true' : 'false'; ?>"><?php echo genosys_settings_icon( $tab[1] ); ?><span><?php echo esc_html( $tab[0] ); ?></span></button><?php endforeach; ?>
			</div>
			<?php foreach ( $tabs as $id => $tab ) : ?>
			<div class="gs-panel" id="gs-panel-<?php echo esc_attr( $id ); ?>" role="tabpanel" aria-labelledby="gs-tab-<?php echo esc_attr( $id ); ?>" tabindex="0">
			<?php
			switch ( $id ) {
				case 'brand':
					genosys_settings_card_open( 'ویدیوی صفحه برند', 'ویدیوی اصلی بالای صفحه /brand/ را از سرور خودتان پخش کنید. پس از انتخاب فایل، دکمه ذخیره تغییرات را بزنید.' );
					genosys_settings_brand_video();
					echo '<div class="gs-card__actions">'; genosys_settings_link( home_url( '/brand/' ), 'مشاهده صفحه برند' ); echo '</div></section>';
					break;
				case 'contact':
					genosys_render_contact_settings();
					break;
				case 'css-editor':
					$editor = genosys_css_editor_bootstrap();
					genosys_render_css_editor( $editor['files'], $editor['previewUrl'] );
					break;
				case 'php-editor':
					$php_editor = genosys_php_editor_bootstrap();
					genosys_render_php_editor( $php_editor['files'], $php_editor['previewUrl'] );
					break;
				case 'general':
					genosys_settings_card_open( 'هویت فروشگاه', 'نام، لوگو و رنگ‌های اصلی سایت.' );
					echo '<div class="gs-grid">';
					genosys_settings_field( 'site_name', 'نام فروشگاه' ); genosys_settings_field( 'site_slogan', 'شعار' );
					$logo_id = (int) get_theme_mod( 'custom_logo', 0 ); $icon_id = (int) get_option( 'site_icon', 0 );
					genosys_settings_media( 'native[custom_logo]', $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '', 'لوگو', 'پیشنهاد: PNG شفاف؛ خالی یعنی لوگوی فعلی قالب', $logo_id );
					genosys_settings_media( 'native[site_icon]', $icon_id ? wp_get_attachment_image_url( $icon_id, 'full' ) : '', 'فاوآیکن', 'تصویر مربع، حداقل ۵۱۲ پیکسل', $icon_id );
					genosys_settings_field( 'primary_color', 'رنگ اصلی', 'color' ); genosys_settings_field( 'deep_color', 'رنگ تیره', 'color' );
					echo '</div></section>';
					break;
				case 'navbar':
					genosys_settings_card_open( 'پیش‌نمایش زنده', 'تغییرات را همین‌جا ببینید. روی لینک‌ها بروید تا رنگ هاور را ببینید.' );
					$custom_logo = (int) get_theme_mod( 'custom_logo', 0 );
					$preview_home = $custom_logo ? wp_get_attachment_image_url( $custom_logo, 'full' ) : get_theme_file_uri( '/assets/images/genosys-logo-light-1.2.4.svg' );
					$preview_inner = $custom_logo ? wp_get_attachment_image_url( $custom_logo, 'full' ) : get_theme_file_uri( '/assets/images/genosys-logo-1.1.1.svg' );
					echo '<div class="gs-preview-modes"><button type="button" data-preview-mode="home" aria-pressed="true">صفحه‌ی اصلی (روی اسلایدر)</button><button type="button" data-preview-mode="inner" aria-pressed="false">صفحات داخلی</button></div><div class="gs-nav-preview" data-nav-preview data-home-logo="' . esc_url( $preview_home ) . '" data-inner-logo="' . esc_url( $preview_inner ) . '"><img data-preview="logo" src="' . esc_url( genosys_site_setting( 'header_logo_home', '' ) ?: $preview_home ) . '" alt="ژنوسیس"><nav aria-label="پیش‌نمایش نوار"><span>برند</span><span class="gs-preview-active">محصولات</span><span>شرکت</span><span>تماس با ما</span></nav><div class="gs-preview-actions">';
					foreach ( array( 'search','cart','account' ) as $action ) { echo '<span data-preview="' . esc_attr( $action ) . '" class="gs-preview-icon">' . genosys_settings_icon( $action ) . '</span>'; }
					echo '<span class="gs-preview-login" data-preview="login-text">' . esc_html( genosys_site_setting( 'header_login_text', 'ورود / ثبت‌نام' ) ) . '</span></div></div></section>';
					genosys_settings_card_open( 'لوگو و پس‌زمینه', 'در صفحه‌ی اصلی نوار روی اسلایدر و شفاف است.' ); echo '<div class="gs-grid">';
					genosys_settings_media( 'site[header_logo_home]', genosys_site_setting( 'header_logo_home','' ), 'لوگوی نوار (صفحه اصلی)', 'نسخه روشن برای پس‌زمینه تیره؛ خالی یعنی لوگوی فعلی' );
					genosys_settings_media( 'site[header_logo_internal]', genosys_site_setting( 'header_logo_internal','' ), 'لوگوی نوار (صفحات داخلی)', 'خالی یعنی لوگوی فعلی قالب' );
					genosys_settings_field( 'header_height','ارتفاع نوار','number',array('min'=>76,'max'=>180,'hint'=>'پیکسل؛ ارتفاع موبایل با چیدمان فعلی سازگار می‌ماند.' ) ); echo '</div></section>';
					genosys_settings_card_open( 'رنگ لینک‌ها و پس‌زمینه نوار', 'رنگ لینک‌ها مستقل از آیکن‌هاست. رنگ هاور برای لینک فعال و فوکوس صفحه‌کلید هم اعمال می‌شود.' ); echo '<div class="gs-grid">';
					foreach ( array( 'header_link_color_home'=>'رنگ لینک‌ها روی اسلایدر', 'header_link_color_internal'=>'رنگ لینک‌ها در صفحات داخلی', 'header_link_hover_home'=>'رنگ هاور لینک‌ها روی اسلایدر', 'header_link_hover_internal'=>'رنگ هاور لینک‌ها در صفحات داخلی', 'header_background_internal'=>'پس‌زمینه نوار در صفحات داخلی' ) as $key=>$label ) { genosys_settings_field( $key, $label, 'color' ); }
					genosys_settings_field( 'header_background_home', 'پس‌زمینه نوار روی اسلایدر', 'optional_color', array( 'hint'=>'برای حفظ پس‌زمینه شفاف، کد رنگ را خالی بگذارید.' ) ); echo '</div></section>';
					genosys_settings_card_open( 'منوی موبایل', 'رنگ متن و هاور برای فهرست لینک‌های منوی اصلی و دکمه بستن منو استفاده می‌شود.' ); echo '<div class="gs-grid">';
					genosys_settings_field( 'mobile_menu_background','پس‌زمینه منوی موبایل','optional_color',array('hint'=>'خالی یعنی پس‌زمینه نیمه‌شفاف فعلی.') );
					genosys_settings_field( 'mobile_menu_color','رنگ لینک‌های منوی اصلی در موبایل','color' ); genosys_settings_field( 'mobile_menu_hover','رنگ هاور منوی اصلی در موبایل','color' ); echo '</div></section>';
					genosys_settings_card_open( 'آیکن‌ها و دکمه ورود', 'آیکن‌های سمت چپ نوار.' ); echo '<div class="gs-grid">';
					foreach ( array( 'search'=>'جستجو','cart'=>'آیکن سبد خرید','account'=>'آیکن حساب کاربری' ) as $key=>$label ) { genosys_settings_field( 'header_show_' . $key, 'نمایش ' . $label, 'select', array( 'options'=>array( 'auto'=>'رفتار فعلی سایت', 'yes'=>'نمایش', 'no'=>'پنهان' ), 'preview'=>$key,'preview_kind'=>'visible' ) ); }
					genosys_settings_field( 'header_icon_color_home','رنگ آیکن‌ها (صفحه اصلی)','color',array('preview'=>'actions','preview_kind'=>'color') ); genosys_settings_field( 'header_icon_color_internal','رنگ آیکن‌ها (صفحات داخلی)','color' ); genosys_settings_field( 'header_icon_hover','رنگ آیکن‌ها هنگام هاور','color' );
					genosys_settings_field( 'header_search_placeholder','متن داخل کادر جستجو' ); genosys_settings_field( 'header_login_text','متن دکمه ورود','text',array('preview'=>'login-text') ); echo '</div><div class="gs-card__actions">'; genosys_settings_link( admin_url( 'nav-menus.php' ), 'ویرایش لینک‌های منوی اصلی' ); echo '</div></section>';
					break;
				case 'slides':
					genosys_settings_card_open( 'اسلایدها', 'اسلایدهای تمام‌عرض بالای صفحه‌ی اصلی؛ نوار روی آن شفاف نشان داده می‌شود.' );
					echo '<input type="hidden" name="site[slides_present]" value="1"><div class="gs-repeater" data-repeater="slides" data-max-rows="12"><div class="gs-repeater__rows">';
					foreach ( genosys_site_setting( 'slides' ) as $index=>$slide ) { genosys_settings_slide_row( $index, $slide ); }
					echo '</div><template data-row-template>'; genosys_settings_slide_row( '__INDEX__', array() ); echo '</template><button type="button" class="gs-outline" data-add-row>+ افزودن اسلاید</button></div><div class="gs-grid gs-spacing">';
					genosys_settings_field( 'slider_interval','زمان نمایش هر اسلاید','number',array('min'=>2000,'max'=>20000,'step'=>500,'hint'=>'میلی‌ثانیه؛ ۶۰۰۰ یعنی ۶ ثانیه.' ) ); echo '</div></section>';
					break;
				case 'home':
					genosys_settings_card_open( 'ترتیب و نمایش بخش‌ها', 'بخش‌ها را با فلش جابه‌جا کنید یا با کلید خاموش‌شان کنید.' );
					$names = array( 'slider'=>'اسلایدر اصلی','cards'=>'دو کارت تبلیغاتی','latest'=>'جدیدترین محصولات','categories'=>'دسته‌بندی‌ها و آمار و افتخارات','featured'=>'محصولات منتخب' );
					echo '<div class="gs-sections">';
					foreach ( genosys_home_sections() as $section ) { $key=$section['id']; echo '<div class="gs-section-row"><span class="gs-section-row__label">' . genosys_settings_icon( 'grip' ) . esc_html( $names[ $key ] ?? $key ) . '</span><input type="hidden" name="site[home_sections_order][]" value="' . esc_attr( $key ) . '"><div class="gs-section-row__actions"><label class="gs-switch"><input type="checkbox" name="site[home_sections_enabled][]" value="' . esc_attr( $key ) . '"' . checked( ! empty( $section['visible'] ), true, false ) . '><span aria-hidden="true"></span><span class="screen-reader-text">نمایش ' . esc_html( $names[ $key ] ?? $key ) . '</span></label><button type="button" class="gs-icon-button" data-move="up" aria-label="بالاتر">' . genosys_settings_icon('up') . '</button><button type="button" class="gs-icon-button" data-move="down" aria-label="پایین‌تر">' . genosys_settings_icon('down') . '</button></div></div>'; }
					echo '</div></section>';
					genosys_settings_card_open( 'دو کارت تبلیغاتی', 'تصاویر، عنوان‌ها و مقصد دکمه‌ها.' ); echo '<div class="gs-grid">';
					foreach ( array( 'club'=>'باشگاه مشتریان','products'=>'محصولات ژنوسیس' ) as $key=>$label ) { genosys_settings_media( 'site['.$key.'_card_image]', genosys_site_setting( $key.'_card_image','' ), 'تصویر '.$label, 'خالی یعنی تصویر فعلی' ); genosys_settings_field( $key.'_card_title','عنوان '.$label ); genosys_settings_field( $key.'_card_button','متن دکمه '.$label ); genosys_settings_field( $key.'_card_url','پیوند '.$label,'url',array('hint'=>'خالی یعنی مقصد فعلی سایت.') ); } echo '</div></section>';
					genosys_settings_card_open( 'دسته‌بندی‌های صفحه اصلی', 'دسته‌بندی‌ها از ووکامرس خوانده می‌شوند.' );
					foreach ( genosys_site_setting( 'category_cards' ) as $index=>$row ) { echo '<div class="gs-editor-row"><div class="gs-grid">'; genosys_settings_field( 'category-title-'.$index,'عنوان کارت','text',array('name'=>'site[category_cards]['.$index.'][title]','value'=>$row['title']) ); genosys_settings_field( 'category-term-'.$index,'دسته‌بندی','select',array('name'=>'site[category_cards]['.$index.'][term_id]','value'=>$row['term_id'],'options'=>$terms) ); genosys_settings_media( 'site[category_cards]['.$index.'][image]', $row['image'], 'تصویر کارت' ); echo '</div></div>'; } echo '</section>';
					genosys_settings_card_open( 'آمار و افتخارات' );
					genosys_settings_field( 'stats_visible','نمایش آمار','select',array('options'=>array(1=>'نمایش',0=>'پنهان')) );
					foreach ( genosys_site_setting( 'stats' ) as $index=>$row ) { echo '<div class="gs-editor-row"><div class="gs-grid">'; genosys_settings_field( 'stat-count-'.$index,'عدد','number',array('name'=>'site[stats]['.$index.'][count]','value'=>$row['count'],'min'=>0,'max'=>1000000) ); genosys_settings_field( 'stat-label-'.$index,'عنوان','text',array('name'=>'site[stats]['.$index.'][label]','value'=>$row['label']) ); genosys_settings_field( 'stat-icon-'.$index,'آیکن','select',array('name'=>'site[stats]['.$index.'][icon]','value'=>$row['icon'],'options'=>array('calendar'=>'تقویم','people'=>'مشتریان','flask'=>'محصول','globe'=>'جهان')) ); echo '</div></div>'; } echo '</section>';
					break;
				case 'featured':
					genosys_settings_card_open( 'محصولات منتخب', 'محصولات از دسته‌بندی‌های انتخاب‌شده در ووکامرس نمایش داده می‌شوند.' ); echo '<div class="gs-grid">';
					genosys_settings_field( 'featured_count','تعداد محصولات هر دسته','number',array('min'=>1,'max'=>24) ); genosys_settings_field( 'featured_link_text','متن پیوند دسته‌بندی‌ها' ); echo '</div>';
					echo '<input type="hidden" name="site[featured_tabs_present]" value="1"><div class="gs-repeater gs-spacing" data-repeater="featured_tabs" data-max-rows="12"><div class="gs-repeater__rows">';
					foreach ( genosys_site_setting( 'featured_tabs' ) as $index=>$row ) { genosys_settings_featured_row( $index, $row, $terms ); }
					echo '</div><p class="gs-empty"' . ( genosys_site_setting( 'featured_tabs' ) ? ' hidden' : '' ) . '>دسته‌بندی‌ای انتخاب نشده است؛ این بخش در صفحه اصلی نمایش داده نمی‌شود.</p><template data-row-template>';
					genosys_settings_featured_row( '__INDEX__', array(), $terms );
					echo '</template><button type="button" class="gs-outline" data-add-row>+ افزودن دسته‌بندی</button><p class="gs-hint">تا ۱۲ دسته‌بندی؛ حذف یک تب فقط آن را از این بخش برمی‌دارد و دسته‌بندی ووکامرس را حذف نمی‌کند.</p></div></section>';
					break;
				case 'invitation':
					genosys_settings_card_open( 'بنر دعوت', 'بنر بالای فوتر؛ برای تاکید در تیتر فقط از <em> استفاده کنید.' ); echo '<div class="gs-grid">';
					genosys_settings_field( 'invitation_line1','خط اول تیتر' ); genosys_settings_field( 'invitation_line2','خط دوم تیتر' ); genosys_settings_field( 'invitation_button','متن دکمه' ); genosys_settings_field( 'invitation_url','پیوند کاتالوگ','url',array('hint'=>'خالی یعنی دکمه فعلی بدون لینک.') ); genosys_settings_media( 'site[invitation_image]',genosys_site_setting('invitation_image',''),'تصویر بنر','خالی یعنی تصویر فعلی' ); echo '</div></section>';
					break;
				case 'footer':
					genosys_settings_card_open( 'فوتر', 'لوگو، معرفی فروشگاه و راه‌های ارتباطی.' ); echo '<div class="gs-grid">';
					genosys_settings_media( 'site[footer_logo]',genosys_site_setting('footer_logo',''),'لوگوی فوتر','خالی یعنی لوگوی فعلی' ); genosys_settings_field('footer_description','متن معرفی','textarea',array('hint'=>'برای شکست خط از <br> استفاده کنید.')); genosys_settings_field('footer_contact_text','متن دکمه تماس'); genosys_settings_field('footer_contact_url','پیوند تماس','url',array('hint'=>'خالی یعنی صفحه تماس فعلی.')); genosys_settings_field('footer_copyright','متن کپی‌رایت'); genosys_settings_field('footer_tagline','متن انگلیسی پایین فوتر'); echo '</div></section>';
					genosys_settings_card_open( 'شبکه‌های اجتماعی', 'با وارد کردن پیوند، آیکن مربوط به صفحه شما متصل می‌شود.' ); echo '<div class="gs-grid">'; foreach(array('instagram'=>'اینستاگرام','telegram'=>'تلگرام','whatsapp'=>'واتساپ','linkedin'=>'لینکدین') as $network=>$label){genosys_settings_field('social_'.$network,$label,'url');} echo '</div></section>';
					break;
				case 'product':
					genosys_settings_card_open( 'صفحه محصول', 'اطلاعات، قیمت و موجودی هر محصول در ووکامرس مدیریت می‌شود. عنوان خالی از ترجمه فعلی ووکامرس استفاده می‌کند.' ); echo '<div class="gs-grid">'; foreach(array('product_related_title'=>'عنوان محصولات مرتبط','product_description_title'=>'عنوان توضیحات','product_information_title'=>'عنوان اطلاعات بیشتر','product_reviews_title'=>'عنوان نظرات') as $key=>$label){genosys_settings_field($key,$label);}
					genosys_settings_field( 'product_related_count','تعداد محصولات مرتبط','number',array('min'=>1,'max'=>12) );
					genosys_settings_field( 'product_cart_animation','انیمیشن افتادن محصول در سبد','select',array('options'=>array(1=>'فعال',0=>'غیرفعال'),'hint'=>'افزودن به سبد و پیام تایید در هر دو حالت فعال می‌مانند. تنظیم کاهش حرکت دستگاه نیز رعایت می‌شود.') );
					echo '</div><div class="gs-card__actions">';genosys_settings_link(admin_url('edit.php?post_type=product'),'مدیریت محصولات');echo '</div></section>';
					break;
				case 'packages':
					genosys_settings_card_open( 'پکیج‌های ویژه', 'نام، تصویر، قیمت و محصولات داخل هر پکیج در بخش پکیج‌ها مدیریت می‌شود. موجودی از محصولات داخل آن محاسبه می‌شود. برای نمایش در صفحه اصلی از تب «محصولات منتخب» استفاده کنید.' );
					echo '<div class="gs-card__actions">'; genosys_settings_link( admin_url('admin.php?page=genosys-add-package'), 'افزودن پکیج' ); genosys_settings_link( admin_url('admin.php?page=genosys-package-list'), 'لیست پکیج‌ها' ); echo '</div></section>';
					break;
				case 'account':
					genosys_settings_card_open( 'حساب کاربری', 'عنوان صفحه و متن منوی حساب؛ اطلاعات مشتری، آدرس‌ها و سفارشات همچنان از حساب واقعی ووکامرس خوانده می‌شوند.' ); echo '<div class="gs-grid">';
					foreach ( array( 'account_title'=>'عنوان صفحه حساب', 'account_profile_label'=>'متن اطلاعات حساب', 'account_addresses_label'=>'متن آدرس‌ها', 'account_notifications_label'=>'متن اعلان‌ها', 'account_club_label'=>'متن باشگاه مشتریان', 'account_orders_label'=>'متن سفارشات من' ) as $key=>$label ) { genosys_settings_field( $key, $label ); } echo '</div></section>';
					break;
				case 'shop':
					genosys_settings_card_open( 'فروشگاه', 'فیلترها و دسته‌بندی‌ها همچنان از اطلاعات ووکامرس استفاده می‌کنند.' );echo '<div class="gs-grid">';genosys_settings_field('shop_title','عنوان فروشگاه');genosys_settings_field('shop_highlight','بخش رنگی عنوان');genosys_settings_field('shop_per_page','تعداد محصولات هر صفحه','number',array('min'=>3,'max'=>48)); echo '</div><div class="gs-card__actions">';genosys_settings_link(admin_url('edit-tags.php?taxonomy=product_cat&post_type=product'),'مدیریت دسته‌بندی‌ها');echo '</div></section>';
					break;
				case 'headings':
					genosys_settings_card_open( 'تیترهای صفحه اصلی', 'متن‌ها با همان قالب فعلی نمایش داده می‌شوند.' ); echo '<div class="gs-grid">';
					foreach(array('latest_title'=>'جدیدترین محصولات','latest_all_text'=>'متن مشاهده همه محصولات','categories_title'=>'ابتدای تیتر دسته‌بندی','categories_highlight'=>'بخش رنگی تیتر دسته‌بندی','categories_description'=>'توضیح دسته‌بندی‌ها','categories_button'=>'متن دکمه دسته‌بندی‌ها','categories_brand_text'=>'متن پیوند برند','featured_eyebrow'=>'متن بالای محصولات منتخب','featured_title'=>'ابتدای تیتر محصولات منتخب','featured_highlight'=>'بخش رنگی تیتر منتخب','featured_suffix'=>'ادامه تیتر منتخب','featured_description'=>'توضیح محصولات منتخب') as $key=>$label){genosys_settings_field($key,$label,strpos($key,'description')!==false?'textarea':'text');} genosys_settings_field('latest_count','تعداد جدیدترین محصولات','number',array('min'=>1,'max'=>24));genosys_settings_field('categories_brand_url','پیوند برند','url',array('hint'=>'خالی یعنی مقصد فعلی')); echo '</div></section>';
					break;
				case 'payment':
					genosys_settings_card_open( 'اطلاعات کارت به کارت', 'همین اطلاعات در تسویه‌حساب و سفارش‌های بدون فیش نمایش داده می‌شود.' ); echo '<div class="gs-grid">';
					foreach(array('genosys_checkout_card_number'=>'شماره کارت','genosys_checkout_card_holder'=>'نام و نام خانوادگی صاحب کارت','genosys_checkout_bank_name'=>'نام بانک','genosys_checkout_whatsapp'=>'شماره واتساپ') as $key=>$label){genosys_settings_field($key,$label,'text',array('name'=>$key,'value'=>get_option($key,''),'ltr'=>in_array($key,array('genosys_checkout_card_number','genosys_checkout_whatsapp'),true)));} echo '</div></section>';
					genosys_settings_card_open( 'هزینه ارسال', 'هزینه با واحد پول تنظیم‌شده در ووکامرس محاسبه می‌شود.' );echo '<div class="gs-grid">'; genosys_settings_field('genosys_shipping_cost','هزینه ارسال','text',array('name'=>'genosys_shipping_cost','value'=>get_option('genosys_shipping_cost',''),'ltr'=>true,'hint'=>'صفر یعنی ارسال رایگان؛ کادر خالی هزینه‌های فعلی ووکامرس را حفظ می‌کند.'));echo '</div></section>';
					break;
				case 'orders':
					genosys_settings_card_open( 'سفارشات', 'عنوان صفحه پیگیری سفارش‌ها و دسترسی به مدیریت سفارش و فیش.' );echo '<div class="gs-grid">';genosys_settings_field('orders_title','عنوان صفحه سفارشات');echo '</div><div class="gs-card__actions">'; genosys_settings_link(admin_url(class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()?'admin.php?page=wc-orders':'edit.php?post_type=shop_order'),'مدیریت سفارشات');genosys_settings_link(admin_url('admin.php?page=genosys-payment-receipts'),'فیش‌های واریزی');echo '</div></section>';
					break;
				case 'sms':
					genosys_settings_card_open( 'اتصال به سرویس', 'اطلاعات پنل پیامکی شما برای ارسال کد ورود و اطلاع‌رسانی سفارش‌ها.' ); echo '<div class="gs-grid">';genosys_settings_field('sms_provider','ارائه‌دهنده','text',array('name'=>'','value'=>'sms.ir','readonly'=>true));
					$key_constant=defined('GENOSYS_SMSIR_API_KEY');$key_set=function_exists('genosys_sms_api_key') && ''!==genosys_sms_api_key();
					genosys_settings_field('sms_api_key','کلید API','password',array('name'=>'sms_api_key','value'=>'','autocomplete'=>'new-password','readonly'=>$key_constant,'placeholder'=>$key_set?'••••••••••••••••':'کلید API را وارد کنید','hint'=>$key_constant?'کلید از wp-config.php خوانده می‌شود.':'خالی بگذارید تا کلید ذخیره‌شده حفظ شود.'));echo '</div></section>';
					genosys_settings_card_open( 'کد تایید ورود', 'تنظیمات قالب پیامک ورود؛ تعداد ارقام و زمان اعتبار مطابق سیستم فعلی است.' );echo '<div class="gs-grid">';genosys_settings_field('sms_template','شناسه قالب ورود','number',array('name'=>'sms_template','value'=>genosys_sms_template_id(),'min'=>1,'max'=>2147483647));genosys_settings_field('sms_parameter','نام متغیر کد در قالب','text',array('name'=>'sms_parameter','value'=>genosys_sms_parameter_name(),'ltr'=>true));genosys_settings_field('otp_digits','تعداد ارقام کد','text',array('name'=>'','value'=>'6','readonly'=>true));genosys_settings_field('otp_duration','مدت اعتبار کد (ثانیه)','text',array('name'=>'','value'=>'120','readonly'=>true));echo '</div><div class="gs-card__actions">';genosys_settings_link(admin_url('options-general.php?page=genosys-sms-login'),'تنظیم شماره ورود مدیر');echo '</div></section>';
					genosys_settings_card_open( 'اطلاع‌رسانی سفارش و فیش', 'کادر خالی، فقط پیامک همان رویداد را غیرفعال می‌کند. کلید API بین همه قالب‌ها مشترک است.' );echo '<div class="gs-grid">';
					foreach(array('genosys_order_sms_template_id'=>array('شناسه قالب پیامک ثبت سفارش','متغیرها: NAME، ORDERID، DATE (شمسی)'), 'genosys_order_status_sms_template_id'=>array('شناسه قالب پیامک تغییر وضعیت سفارش','متغیرها: NAME، STATUS'), 'genosys_receipt_approved_sms_template_id'=>array('شناسه قالب پیامک تایید فیش','متغیر: NAME'), 'genosys_receipt_rejected_sms_template_id'=>array('شناسه قالب پیامک رد فیش','متغیر: NAME')) as $key=>$data){genosys_settings_field($key,$data[0],'text',array('name'=>$key,'value'=>get_option($key,''),'ltr'=>true,'hint'=>$data[1]));}echo '</div></section>';
					genosys_settings_card_open( 'پیامک گروهی باشگاه', 'ذخیره تنظیمات هیچ پیامکی ارسال نمی‌کند. متن و گیرنده‌ها در صفحه اعضای باشگاه انتخاب و پیش از ارسال بررسی می‌شوند.' ); echo '<div class="gs-grid">';
					genosys_settings_field( 'club_sms_sender','شماره خط ارسال باشگاه','text',array('name'=>'club_sms_sender','value'=>function_exists('genosys_club_sms_sender')?genosys_club_sms_sender():'','ltr'=>true,'readonly'=>defined('GENOSYS_CLUB_SMS_SENDER'),'hint'=>defined('GENOSYS_CLUB_SMS_SENDER')?'خط ارسال از wp-config.php خوانده می‌شود.':'شماره خط فعال در SMS.ir؛ خالی یعنی پیامک گروهی غیرفعال.') ); echo '</div><div class="gs-card__actions">'; genosys_settings_link( admin_url('admin.php?page=genosys-club-members'), 'مدیریت اعضا و پیامک گروهی' ); echo '</div></section>';
					break;
				case 'club':
					genosys_settings_card_open( 'انتخاب نوع عضویت', 'هر دو پرسشنامه در دسترس می‌مانند؛ فقط گزینه‌ای را انتخاب کنید که هنگام باز شدن صفحه نمایش داده شود.' ); echo '<div class="gs-grid">';
					genosys_settings_field( 'club_default_audience','نوع عضویت پیش‌فرض','select',array('options'=>array('clinic'=>'کلینیک‌ها و بیوتی تراپیست‌ها','consumer'=>'مصرف‌کننده')) );
					genosys_settings_field( 'club_consumer_label','متن گزینه مصرف‌کننده' ); genosys_settings_field( 'club_clinic_label','متن گزینه کلینیک‌ها' ); echo '</div><div class="gs-card__actions">'; genosys_settings_link( admin_url('admin.php?page=genosys-club-members'), 'مدیریت اعضای باشگاه' ); echo '</div></section>';
					genosys_settings_card_open( 'معرفی برای مصرف‌کنندگان', 'این متن‌ها پس از انتخاب گزینه مصرف‌کننده در صفحه باشگاه نمایش داده می‌شوند.' );echo '<div class="gs-grid">';foreach(array('club_eyebrow'=>'متن بالای عنوان','club_title_before'=>'ابتدای عنوان','club_title_highlight'=>'بخش رنگی عنوان','club_title_after'=>'ادامه عنوان','club_description_line1'=>'خط اول معرفی','club_description_line2'=>'خط دوم معرفی','club_form_title'=>'عنوان پرسشنامه','club_form_description'=>'توضیح پرسشنامه') as $key=>$label){genosys_settings_field($key,$label);}echo '</div></section>';
					genosys_settings_card_open( 'معرفی برای کلینیک‌ها و بیوتی تراپیست‌ها', 'متن معرفی و عنوان پرسشنامه همکاری حرفه‌ای؛ سوال‌ها و ثبت اطلاعات تغییری نمی‌کنند.' );echo '<div class="gs-grid">';foreach(array('clinic_eyebrow'=>'متن بالای عنوان','clinic_title_before'=>'ابتدای عنوان','clinic_title_highlight'=>'بخش رنگی عنوان','clinic_title_after'=>'ادامه عنوان','clinic_description_line1'=>'خط اول معرفی','clinic_description_line2'=>'خط دوم معرفی','clinic_form_title'=>'عنوان پرسشنامه','clinic_form_description'=>'توضیح پرسشنامه') as $key=>$label){genosys_settings_field($key,$label);}echo '</div></section>';
					break;
			}
			?>
			</div>
			<?php endforeach; ?>
			<div class="gs-savebar"><span data-dirty-label role="status" aria-live="polite">همه تغییرات ذخیره شده‌اند</span><button type="submit" class="gs-save">ذخیره تغییرات</button></div>
		</form>
	</div>
	<?php
}
