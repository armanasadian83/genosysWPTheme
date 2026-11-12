<?php
/**
 * Order-linked receipt picker. Uploads are saved separately from payment processing.
 *
 * @package Genosys
 */

defined('ABSPATH') || exit;
$receipt_order = isset($args['receipt_order']) ? $args['receipt_order'] : null;
$receipt_config = function_exists('genosys_receipt_customer_context') && $receipt_order
    ? genosys_receipt_customer_context($receipt_order) : null;
?>
<div class="gco-receipt-upload" data-gco-receipt-upload>
    <script type="application/json" data-gco-receipt-config><?php echo wp_json_encode($receipt_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
    <p class="gco-receipt-upload__label" id="gco-receipt-label">
        <?php esc_html_e('بارگذاری فیش واریزی', 'genosys'); ?>
        <span class="gco-receipt-upload__required" aria-hidden="true">*</span>
    </p>
    <input type="file" id="gco-receipt-file" data-gco-receipt-input accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" tabindex="-1" hidden>
    <div class="gco-receipt-box" data-gco-receipt-box data-state="idle" role="button" tabindex="0" aria-labelledby="gco-receipt-label" aria-describedby="gco-receipt-hint gco-receipt-error">
        <div class="gco-receipt-idle" data-gco-receipt-idle>
            <span class="gco-receipt-idle__icon" data-gco-receipt-icon aria-hidden="true">
                <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 27V7m0 0L11.5 15.5M20 7l8.5 8.5M7 24v9a2 2 0 0 0 2 2h22a2 2 0 0 0 2-2v-9" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <p class="gco-receipt-idle__title"><?php esc_html_e('تصویر فیش واریزی را اینجا رها کنید', 'genosys'); ?></p>
            <p class="gco-receipt-idle__hint"><?php esc_html_e('یا برای انتخاب فایل کلیک کنید', 'genosys'); ?></p>
            <span class="gco-receipt-idle__formats" id="gco-receipt-hint"><bdi>JPG · PNG · WEBP · PDF</bdi><span aria-hidden="true">—</span><?php esc_html_e('حداکثر ۵ مگابایت', 'genosys'); ?></span>
        </div>
        <div class="gco-receipt-busy" data-gco-receipt-busy aria-hidden="true" hidden>
            <div class="gco-receipt-envelope" data-gco-envelope>
                <div class="gco-receipt-envelope__back" data-gco-envelope-back></div>
                <div class="gco-receipt-envelope__paper-track">
                    <div class="gco-receipt-envelope__paper" data-gco-envelope-paper>
                        <div class="gco-receipt-envelope__preview" data-gco-envelope-preview></div>
                    </div>
                </div>
                <svg class="gco-receipt-envelope__front" data-gco-envelope-front viewBox="0 0 288 200" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <defs><linearGradient id="gco-receipt-pocket-gradient" x1="100" y1="48" x2="177" y2="220" gradientUnits="userSpaceOnUse"><stop stop-color="#d84460"/><stop offset="1" stop-color="#a0082c"/></linearGradient></defs>
                    <path d="M0 50L135 122Q144 128 153 122L288 50V183Q288 200 271 200H17Q0 200 0 183V50Z" fill="url(#gco-receipt-pocket-gradient)"/>
                    <path d="M0 50L135 122Q144 128 153 122L288 50" stroke="#ef8c9d" stroke-width="2"/>
                </svg>
                <svg class="gco-receipt-envelope__flap" data-gco-envelope-flap viewBox="0 0 288 124" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <defs><linearGradient id="gco-receipt-flap-gradient" x1="144" y1="0" x2="144" y2="124" gradientUnits="userSpaceOnUse"><stop stop-color="#c50932"/><stop offset="1" stop-color="#df4561"/></linearGradient></defs>
                    <path d="M14 0H274Q288 0 277 12L152 119Q144 125 136 119L11 12Q0 0 14 0Z" fill="url(#gco-receipt-flap-gradient)"/>
                </svg>
                <div class="gco-receipt-envelope__seal" data-gco-envelope-seal>
                    <span class="gco-receipt-envelope__ripple" data-gco-envelope-ripple></span>
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="m6.5 12 3.5 3.5 7.5-7.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
            </div>
        </div>
        <div class="gco-receipt-result" data-gco-receipt-result aria-hidden="true" hidden>
            <div class="gco-receipt-result__thumb" data-gco-receipt-thumb aria-hidden="true">
                <svg class="gco-receipt-fallback" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 3h10l6 6v20l-4-2-5 2-5-2-4 2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M19 3v7h6M11 15h10M11 20h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="gco-receipt-result__info">
                <span class="gco-receipt-result__success">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="12" cy="12" r="12" fill="currentColor"/><path d="m6.8 12 3.5 3.5 6.9-7" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <?php esc_html_e('فیش با موفقیت بارگذاری شد', 'genosys'); ?>
                </span>
                <p class="gco-receipt-result__name" data-gco-receipt-name dir="auto"></p>
                <p class="gco-receipt-result__meta" data-gco-receipt-meta></p>
            </div>
            <div class="gco-receipt-result__actions">
                <button type="button" class="gco-receipt-result__button" data-gco-receipt-change>
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M15.5 7a6 6 0 1 0 .2 5M15.5 3.5V7h-3.6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <?php esc_html_e('تغییر فایل', 'genosys'); ?>
                </button>
                <button type="button" class="gco-receipt-result__button" data-gco-receipt-remove>
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 5.5h12M7.5 5.5V3.8h5v1.7M6 5.5l.7 11h6.6l.7-11M8.3 8.5v5M11.7 8.5v5" stroke="currentColor" stroke-width="1.45" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <?php esc_html_e('حذف', 'genosys'); ?>
                </button>
            </div>
        </div>
    </div>
    <p class="gco-receipt-upload__error" id="gco-receipt-error" data-gco-receipt-error role="alert" hidden></p>
    <span class="gco-receipt-upload__status" data-gco-receipt-status role="status" aria-live="polite"></span>
</div>
