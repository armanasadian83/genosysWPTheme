<?php
/** Contact channels, weekly availability and the private support form. */
defined( 'ABSPATH' ) || exit;
$contact = genosys_contact_page_data();
$contact_icon = static function ( $name ) {
	$paths = array(
		'phone' => '<path d="m8 3 3 5-3 2a14 14 0 0 0 6 6l2-3 5 3-1 5C10 23 1 14 3 4Z"/>',
		'whatsapp' => '<path d="m3 21 1.5-5A9 9 0 1 1 8 20Z"/><path d="m9 7 2 3-1.5 1a8 8 0 0 0 3.5 3.5l1-1.5 3 2c-1 3-4 2-6 1-3-2-6-6-3-9Z"/>',
		'telegram' => '<path d="m3 11 18-8-4 18-6-5-3 3 1-6 10-8-12 7Z"/>',
		'email' => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/>',
		'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/>',
		'pin' => '<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 0 1 14 0Z"/><circle cx="12" cy="10" r="2.5"/>',
		'postal' => '<rect x="3" y="5" width="18" height="14" rx="1"/><path d="M3 9h18"/>',
		'attachment' => '<path d="m8 13 7-7a3 3 0 0 1 4 4l-9 9a5 5 0 0 1-7-7L13 2m-7 13 9-9"/>',
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
		'linkedin' => '<path d="M5 9v11M5 5v.01M10 20V9m0 5c0-7 9-7 9 0v6"/>',
		'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
	);
	echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? $paths['email'] ) . '</svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed local SVG paths only.
};
get_header();
?>
<main id="main" class="gs-contact" tabindex="-1" data-contact-page>
	<section class="gs-contact-hero" aria-labelledby="gs-contact-title">
		<svg class="gs-contact-wave" viewBox="0 0 280 120" fill="none" aria-hidden="true" focusable="false"><path d="M17 86C65-5 96 24 132 77s74 25 126-57" stroke="#1d1d22" stroke-width="29" stroke-linecap="round"/><path d="M17 74C65-17 96 12 132 65s74 25 126-57" stroke="#fff" stroke-width="29" stroke-linecap="round"/></svg>
		<span class="gs-contact-disc" aria-hidden="true"></span>
		<div class="gs-contact-shell">
			<div class="gs-contact-status<?php echo $contact['status']['open'] ? ' is-open' : ''; ?>"><span class="gs-contact-status__dot" aria-hidden="true"></span><strong><?php echo esc_html( $contact['status']['label'] ); ?></strong><?php if ( '' !== $contact['status']['detail'] ) : ?><span class="gs-contact-status__detail"><?php echo esc_html( $contact['status']['detail'] ); ?></span><?php endif; ?></div>
			<h1 id="gs-contact-title"><?php echo esc_html( $contact['hero_before'] ); ?> <span class="gs-contact-highlight"><?php echo esc_html( $contact['hero_highlight'] ); ?><svg viewBox="0 0 270 26" fill="none" aria-hidden="true" focusable="false"><path d="M5 19C40 0 79 28 119 15S200 1 265 15" stroke="currentColor" stroke-width="7" stroke-linecap="round"/></svg></span> <?php echo esc_html( $contact['hero_after'] ); ?></h1>
			<p class="gs-contact-intro"><?php echo esc_html( $contact['description_line1'] ); ?><br><?php echo esc_html( $contact['description_line2'] ); ?></p>
			<div class="gs-contact-channels">
				<?php foreach ( $contact['channels'] as $channel_key => $channel ) : ?>
				<article class="gs-contact-channel<?php echo 'phone' === $channel_key ? ' gs-contact-channel--featured' : ''; ?>">
					<span class="gs-contact-channel__icon"><?php $contact_icon( $channel_key ); ?></span>
					<h2><?php echo esc_html( $channel['label'] ); ?></h2><p class="gs-contact-channel__value<?php echo '' === $channel['value'] ? ' is-empty' : ''; ?>" dir="<?php echo '' === $channel['value'] ? 'rtl' : 'ltr'; ?>"><?php echo esc_html( '' !== $channel['value'] ? $channel['value'] : $contact['empty_label'] ); ?></p>
					<div class="gs-contact-channel__actions"><?php if ( '' !== $channel['url'] ) : ?><a class="gs-contact-button" href="<?php echo esc_url( $channel['url'], array( 'http', 'https', 'tel', 'mailto' ) ); ?>"<?php if ( in_array( $channel_key, array( 'whatsapp', 'telegram' ), true ) ) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>><?php echo esc_html( $channel['button'] ); ?></a><?php else : ?><button class="gs-contact-button" type="button" disabled><?php echo esc_html( $channel['button'] ); ?></button><?php endif; ?><button class="gs-contact-button gs-contact-button--outline" type="button" data-contact-copy="<?php echo esc_attr( $channel['copy'] ); ?>" data-copied-label="<?php echo esc_attr( $contact['copied_label'] ); ?>" aria-label="<?php echo esc_attr( $contact['copy_label'] . ' ' . $channel['label'] ); ?>"<?php disabled( '', $channel['copy'] ); ?>><?php echo esc_html( $contact['copy_label'] ); ?></button></div>
				</article>
				<?php endforeach; ?>
			</div>
			<p class="gs-contact-copy-status gs-contact-sr-only" role="status" aria-live="polite" data-contact-copy-status></p>
		</div>
	</section>
	<section class="gs-contact-main gs-contact-shell" aria-labelledby="gs-contact-form-title">
		<div class="gs-contact-form-card">
			<header><h2 id="gs-contact-form-title"><?php echo esc_html( $contact['form']['title'] ); ?></h2><p><?php echo esc_html( $contact['form']['description'] ); ?></p></header>
			<form class="gs-contact-form" data-contact-form method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" enctype="multipart/form-data" novalidate>
				<input type="hidden" name="action" value="genosys_contact_submit"><input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'genosys_contact_submit' ) ); ?>">
				<label class="gs-contact-honeypot" aria-hidden="true">وب‌سایت<input name="website" type="text" tabindex="-1" autocomplete="off"></label>
				<fieldset class="gs-contact-topics"><legend><?php echo esc_html( $contact['form']['topic_label'] ); ?></legend><div class="gs-contact-topic-list"><?php $topic_index = 0; foreach ( $contact['form']['topics'] as $topic_id => $topic_label ) : ?><label class="gs-contact-topic"><input type="radio" name="topic" value="<?php echo esc_attr( $topic_id ); ?>"<?php checked( 0, $topic_index++ ); ?> required><span><svg viewBox="0 0 18 18" fill="none" aria-hidden="true"><path d="m4 9 3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><?php echo esc_html( $topic_label ); ?></span></label><?php endforeach; ?></div><p class="gs-contact-field-error" data-contact-error="topic" hidden></p></fieldset>
				<div class="gs-contact-field-row">
					<div class="gs-contact-field"><label for="gs-contact-name" class="gs-contact-sr-only"><?php echo esc_html( $contact['form']['name_label'] ); ?></label><input id="gs-contact-name" type="text" name="fullname" autocomplete="name" maxlength="100" placeholder="<?php echo esc_attr( $contact['form']['name_label'] ); ?>" aria-describedby="gs-contact-name-error" required><p id="gs-contact-name-error" class="gs-contact-field-error" data-contact-error="fullname" hidden></p></div>
					<div class="gs-contact-field"><label for="gs-contact-phone" class="gs-contact-sr-only"><?php echo esc_html( $contact['form']['mobile_label'] ); ?></label><input id="gs-contact-phone" type="tel" name="mobile" autocomplete="tel" inputmode="tel" maxlength="25" placeholder="<?php echo esc_attr( $contact['form']['mobile_label'] ); ?>" aria-describedby="gs-contact-phone-error" required><p id="gs-contact-phone-error" class="gs-contact-field-error" data-contact-error="mobile" hidden></p></div>
				</div>
				<div class="gs-contact-field"><label for="gs-contact-email" class="gs-contact-sr-only"><?php echo esc_html( $contact['form']['email_label'] ); ?></label><input id="gs-contact-email" name="email" type="email" autocomplete="email" maxlength="254" placeholder="<?php echo esc_attr( $contact['form']['email_label'] ); ?>" aria-describedby="gs-contact-email-error"><p id="gs-contact-email-error" class="gs-contact-field-error" data-contact-error="email" hidden></p></div>
				<div class="gs-contact-field"><label for="gs-contact-message" class="gs-contact-sr-only"><?php echo esc_html( $contact['form']['message_label'] ); ?></label><div class="gs-contact-message-wrap"><textarea id="gs-contact-message" name="message" rows="6" maxlength="500" placeholder="<?php echo esc_attr( $contact['form']['message_label'] ); ?>" aria-describedby="gs-contact-message-error gs-contact-counter" required></textarea><span class="gs-contact-counter" id="gs-contact-counter"><span data-contact-count>۰</span> / ۵۰۰</span></div><p id="gs-contact-message-error" class="gs-contact-field-error" data-contact-error="message" hidden></p></div>
				<div class="gs-contact-attachment-wrap"><label class="gs-contact-attachment" for="gs-contact-file"><?php $contact_icon( 'attachment' ); ?><span><strong><?php echo esc_html( $contact['form']['attachment_label'] ); ?></strong> <span data-contact-file-label><?php echo esc_html( $contact['form']['attachment_hint'] ); ?></span></span><input id="gs-contact-file" type="file" name="attachment" accept="image/jpeg,image/png,image/webp,application/pdf" aria-describedby="gs-contact-file-error"></label><button class="gs-contact-file-remove" type="button" data-contact-file-remove aria-label="حذف فایل پیوست" hidden><?php $contact_icon( 'close' ); ?></button><p id="gs-contact-file-error" class="gs-contact-field-error" data-contact-error="attachment" hidden></p></div>
				<div class="gs-contact-form-actions"><div class="gs-contact-consent"><label><input type="checkbox" name="consent" value="1" required aria-describedby="gs-contact-consent-error"><span><?php echo esc_html( $contact['form']['consent_before'] ); ?> <?php if ( '' !== $contact['form']['privacy_url'] ) : ?><a href="<?php echo esc_url( $contact['form']['privacy_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $contact['form']['privacy_label'] ); ?></a><?php else : ?><span class="gs-contact-privacy-label"><?php echo esc_html( $contact['form']['privacy_label'] ); ?></span><?php endif; ?> <?php echo esc_html( $contact['form']['consent_after'] ); ?></span></label><p id="gs-contact-consent-error" class="gs-contact-field-error" data-contact-error="consent" hidden></p></div><button class="gs-contact-button gs-contact-submit" type="submit"><span data-contact-submit-label><?php echo esc_html( $contact['form']['submit_label'] ); ?></span><?php $contact_icon( 'telegram' ); ?></button></div>
				<p class="gs-contact-form-status" data-contact-form-status role="status" aria-live="polite" tabindex="-1" hidden></p>
			</form>
		</div>
		<aside class="gs-contact-sidebar" aria-label="اطلاعات تماس و ساعات پاسخگویی">
			<section class="gs-contact-hours" aria-labelledby="gs-contact-hours-title"><h2 id="gs-contact-hours-title"><?php $contact_icon( 'clock' ); ?><span><?php echo esc_html( $contact['hours_title'] ); ?></span></h2><ul><?php foreach ( $contact['hours'] as $day ) : ?><li<?php echo $day['today'] ? ' class="is-today" aria-current="date"' : ''; ?>><span><?php echo esc_html( $day['label'] ); ?><?php if ( $day['today'] ) : ?> <span>(<?php echo esc_html( $day['today_label'] ); ?>)</span><?php endif; ?></span><span><?php echo esc_html( $day['text'] ); ?></span></li><?php endforeach; ?></ul></section>
			<section class="gs-contact-address" aria-label="نشانی و شبکه‌های اجتماعی"><div class="gs-contact-address-row"><span class="gs-contact-address-icon"><?php $contact_icon( 'pin' ); ?></span><div><h2><?php echo esc_html( $contact['address_label'] ); ?></h2><p><?php echo esc_html( '' !== $contact['address'] ? $contact['address'] : $contact['empty_label'] ); ?></p></div></div><div class="gs-contact-address-row"><span class="gs-contact-address-icon"><?php $contact_icon( 'postal' ); ?></span><div><h2><?php echo esc_html( $contact['postal_label'] ); ?></h2><p class="gs-contact-postal" dir="<?php echo '' === $contact['postal'] ? 'rtl' : 'ltr'; ?>"><?php echo esc_html( '' !== $contact['postal'] ? $contact['postal'] : $contact['empty_label'] ); ?></p></div></div><div class="gs-contact-socials"><?php foreach ( $contact['socials'] as $network => $social_url ) : if ( '' === $social_url ) { continue; } $social_names = array( 'instagram' => 'اینستاگرام', 'telegram' => 'تلگرام', 'whatsapp' => 'واتساپ', 'linkedin' => 'لینکدین' ); ?><a href="<?php echo esc_url( $social_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $social_names[ $network ] ?? $network ); ?>"><?php $contact_icon( $network ); ?></a><?php endforeach; ?></div></section>
		</aside>
	</section>
	<?php if ( ! empty( $contact['faq']['items'] ) ) : ?>
	<section class="gs-contact-faq gs-contact-shell" aria-labelledby="gs-contact-faq-title"><header><h2 id="gs-contact-faq-title"><?php echo esc_html( $contact['faq']['before'] ); ?> <span><?php echo esc_html( $contact['faq']['highlight'] ); ?></span> <?php echo esc_html( $contact['faq']['after'] ); ?></h2><p><?php echo esc_html( $contact['faq']['description'] ); ?></p></header><div class="gs-contact-faq-list"><?php foreach ( $contact['faq']['items'] as $faq ) : ?><details class="gs-contact-faq-item" data-contact-faq><summary><span><?php echo esc_html( $faq['question'] ); ?></span><span class="gs-contact-faq-toggle" aria-hidden="true">+</span></summary><div class="gs-contact-faq-answer"><p><?php echo nl2br( esc_html( $faq['answer'] ) ); ?></p></div></details><?php endforeach; ?></div></section>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
