<?php
/** Contact fields are part of the existing theme-settings save transaction. */
defined( 'ABSPATH' ) || exit;

function genosys_contact_settings_types() {
	$types = array();
	foreach ( genosys_contact_defaults() as $key=>$value ) { if ( ! is_array($value) ) { $types[$key]='text'; } }
	foreach ( array('contact_description_line1','contact_description_line2','contact_address','contact_form_description','contact_form_success','contact_faq_description') as $key ) { $types[$key]='textarea'; }
	foreach ( array('phone','whatsapp','telegram','email') as $channel ) { $types['contact_'.$channel.'_url']='contact_link'; }
	foreach ( array('instagram','telegram','whatsapp','linkedin') as $network ) { $types['contact_social_'.$network]='url'; }
	$types['contact_privacy_url']='url';
	$types['contact_notification_enabled']='bool';
	$types['contact_notification_email']='contact_email';
	return $types;
}

function genosys_contact_settings_sanitize( $input, $result, $previous, $defaults ) {
	foreach ( genosys_contact_settings_types() as $key=>$type ) {
		if ( ! array_key_exists($key,$input) ) { continue; }
		$old=$previous[$key]??($defaults[$key]??'');
		if ( ! is_scalar($input[$key]) || is_bool($input[$key]) ) { genosys_settings_invalid($key); continue; }
		$value=trim((string)$input[$key]);
		if ( 'contact_link'===$type ) {
			$clean=genosys_contact_link($value,array('http','https','tel','mailto'));
			if(''!==$value&&(''===$clean||strlen($clean)>2000)){genosys_settings_invalid($key);continue;}
			$result[$key]=$clean;
		} elseif ( 'contact_email'===$type ) {
			if(''!==$value&&(!is_email($value)||strlen($value)>254)){genosys_settings_invalid($key);continue;}
			$result[$key]=sanitize_email($value);
		} else { $result[$key]=genosys_settings_clean_value($value,$type,$key,$old); }
	}
	if ( array_key_exists('contact_hours',$input) ) {
		if ( ! is_array($input['contact_hours']) || 7!==count($input['contact_hours']) ) { genosys_settings_invalid('ساعات پاسخگویی'); }
		else {
			$old=$previous['contact_hours']??$defaults['contact_hours'];$rows=array();$valid=true;
			foreach($defaults['contact_hours'] as $index=>$base){
				$row=$input['contact_hours'][$index]??null;
				if(!is_array($row)||!is_string($row['label']??null)||!is_string($row['open']??null)||!is_string($row['close']??null)||!is_scalar($row['closed']??null)){ $valid=false; break; }
				$open=genosys_contact_ascii($row['open']);$close=genosys_contact_ascii($row['close']);$closed=(string)$row['closed'];
				if(!genosys_contact_time_valid($open)||!genosys_contact_time_valid($close)||!in_array($closed,array('0','1'),true)||('0'===$closed&&$open>=$close)){ $valid=false;break; }
				$label=sanitize_text_field($row['label']);
				if(''===$label||genosys_contact_length($label)>40){$valid=false;break;}
				$rows[]=array('label'=>$label,'open'=>$open,'close'=>$close,'closed'=>(int)$closed);
			}
			if($valid){$result['contact_hours']=$rows;}else{genosys_settings_invalid('ساعات پاسخگویی؛ پایان هر روز کاری باید بعد از شروع باشد');}
		}
	}
	$faq_present=isset($input['contact_faq_present'])&&is_string($input['contact_faq_present'])&&'1'===$input['contact_faq_present'];
	if ( array_key_exists('contact_faq',$input) ) {
		if(!is_array($input['contact_faq'])||count($input['contact_faq'])>30){genosys_settings_invalid('سؤال‌های رایج');}
		else{
			$rows=array();$valid=true;
			foreach(array_values($input['contact_faq']) as $row){
				if(!is_array($row)||!is_string($row['question']??null)||!is_string($row['answer']??null)){$valid=false;break;}
				$question=sanitize_text_field($row['question']);$answer=sanitize_textarea_field($row['answer']);
				if(''===$question&&''===$answer){continue;}
				if(''===$question||''===$answer||genosys_contact_length($question)>300||genosys_contact_length($answer)>3000){$valid=false;break;}
				$rows[]=array('question'=>$question,'answer'=>$answer);
			}
			if($valid){$result['contact_faq']=$rows;}else{genosys_settings_invalid('پرسش و پاسخ کامل؛ پرسش حداکثر ۳۰۰ و پاسخ حداکثر ۳۰۰۰ نویسه');}
		}
	} elseif ( $faq_present ) { $result['contact_faq']=array(); }
	return $result;
}

function genosys_contact_settings_faq_row( $index, $row ) {
	echo '<div class="gs-repeater__row"><div style="flex:1;min-width:0">';
	genosys_settings_field('contact-faq-question-'.$index,'پرسش','text',array('name'=>'site[contact_faq]['.$index.'][question]','value'=>$row['question']??'','hint'=>'حداکثر ۳۰۰ نویسه'));
	genosys_settings_field('contact-faq-answer-'.$index,'پاسخ','textarea',array('name'=>'site[contact_faq]['.$index.'][answer]','value'=>$row['answer']??'','hint'=>'متن ساده، حداکثر ۳۰۰۰ نویسه؛ شکست خط در سایت حفظ می‌شود.'));
	echo '</div><button type="button" class="gs-icon-button gs-danger" data-remove-row aria-label="حذف پرسش و پاسخ">'.genosys_settings_icon('trash').'</button></div>';
}

function genosys_render_contact_settings() {
	genosys_settings_card_open('صفحه تماس با ما','مقادیر این بخش در /contact/ نمایش داده می‌شوند. شماره‌ها، نشانی و شبکه‌های اجتماعی را با اطلاعات واقعی مجموعه تکمیل کنید.');
	echo '<div class="gs-card__actions">';genosys_settings_link(home_url('/contact/'),'مشاهده صفحه تماس');genosys_settings_link(admin_url('admin.php?page=genosys-contact-messages'),'پیام‌های دریافت‌شده');echo '</div><div class="gs-grid">';
	foreach(array('contact_page_title'=>'عنوان صفحه در مرورگر','contact_hero_before'=>'ابتدای عنوان','contact_hero_highlight'=>'بخش رنگی عنوان','contact_hero_after'=>'ادامه عنوان','contact_description_line1'=>'خط اول معرفی','contact_description_line2'=>'خط دوم معرفی','contact_copy_label'=>'متن دکمه کپی','contact_copied_label'=>'متن پس از کپی','contact_empty_label'=>'متن اطلاعات تکمیل‌نشده') as $key=>$label){genosys_settings_field($key,$label);}
	echo '</div></section>';
	genosys_settings_card_open('راه‌های ارتباطی','پیوند اختیاری است؛ با خالی گذاشتن آن، تماس، ایمیل، تلگرام و واتساپ از مقدار واردشده ساخته می‌شوند. اطلاعات خالی، دکمه فعال نخواهد داشت.');
	foreach(array('phone'=>'تلفن پشتیبانی','whatsapp'=>'واتساپ','telegram'=>'تلگرام','email'=>'ایمیل') as $channel=>$label){echo '<div class="gs-editor-row"><h3>'.esc_html($label).'</h3><div class="gs-grid">';genosys_settings_field('contact_'.$channel.'_label','عنوان کارت');genosys_settings_field('contact_'.$channel.'_value','شماره / شناسه / نشانی','text',array('ltr'=>true));genosys_settings_field('contact_'.$channel.'_button','متن دکمه');genosys_settings_field('contact_'.$channel.'_url','پیوند اختیاری','text',array('ltr'=>true,'hint'=>'پیوند کامل https://، tel: یا mailto:'));echo '</div></div>';}
	echo '</section>';
	genosys_settings_card_open('ساعات پاسخگویی','نشان وضعیت بر اساس ساعت و منطقه زمانی تنظیم‌شده در وردپرس محاسبه می‌شود. پایان کار هر روز باید پس از شروع همان روز باشد.');echo '<div class="gs-grid">';
	foreach(array('contact_hours_title'=>'عنوان کادر ساعات','contact_today_label'=>'متن امروز','contact_hours_closed'=>'متن روز تعطیل','contact_status_open'=>'وضعیت در ساعات پاسخگویی','contact_status_closed'=>'وضعیت خارج از ساعات پاسخگویی','contact_status_open_detail'=>'توضیح وضعیت باز','contact_status_closed_detail'=>'توضیح وضعیت بسته') as $key=>$label){genosys_settings_field($key,$label,'text',array('hint'=>'contact_status_open_detail'===$key?'برای ساعت پایان کار از {time} استفاده کنید.':''));}
	echo '</div>';
	$hours=genosys_contact_page_data()['hours'];
	foreach($hours as $index=>$row){echo '<div class="gs-editor-row"><div class="gs-grid">';genosys_settings_field('contact-hour-label-'.$index,'روز هفته','text',array('name'=>'site[contact_hours]['.$index.'][label]','value'=>$row['label']));genosys_settings_field('contact-hour-closed-'.$index,'وضعیت','select',array('name'=>'site[contact_hours]['.$index.'][closed]','value'=>$row['closed']?1:0,'options'=>array(0=>'روز کاری',1=>'تعطیل')));genosys_settings_field('contact-hour-open-'.$index,'شروع پاسخگویی','time',array('name'=>'site[contact_hours]['.$index.'][open]','value'=>$row['open'],'ltr'=>true));genosys_settings_field('contact-hour-close-'.$index,'پایان پاسخگویی','time',array('name'=>'site[contact_hours]['.$index.'][close]','value'=>$row['close'],'ltr'=>true));echo '</div></div>';}
	echo '</section>';
	genosys_settings_card_open('دفتر و شبکه‌های اجتماعی','پیوند خالی، آیکن آن شبکه را پنهان می‌کند.');echo '<div class="gs-grid">';
	foreach(array('contact_address_label'=>'عنوان نشانی دفتر','contact_address'=>'نشانی دفتر','contact_postal_label'=>'عنوان کد پستی','contact_postal'=>'کد پستی') as $key=>$label){genosys_settings_field($key,$label,'contact_address'===$key?'textarea':'text');}
	foreach(array('instagram'=>'اینستاگرام','telegram'=>'تلگرام','whatsapp'=>'واتساپ','linkedin'=>'لینکدین') as $network=>$label){genosys_settings_field('contact_social_'.$network,'پیوند '.$label,'url');}echo '</div></section>';
	genosys_settings_card_open('فرم تماس','پیام‌های واقعی و پیوست‌ها در صندوق خصوصی مدیر ذخیره می‌شوند. تصویر JPG، PNG، WebP یا PDF تا ۵ مگابایت پذیرفته می‌شود.');echo '<div class="gs-grid">';
	foreach(array('contact_form_title'=>'عنوان فرم','contact_form_description'=>'توضیح فرم','contact_topic_label'=>'عنوان موضوع','contact_topic_support'=>'موضوع پشتیبانی','contact_topic_consultation'=>'موضوع مشاوره','contact_topic_partnership'=>'موضوع همکاری','contact_topic_feedback'=>'موضوع پیشنهاد و انتقاد','contact_topic_other'=>'موضوع سایر','contact_name_label'=>'راهنمای نام','contact_mobile_label'=>'راهنمای موبایل','contact_email_input_label'=>'راهنمای ایمیل','contact_message_label'=>'راهنمای پیام','contact_attachment_label'=>'عنوان پیوست','contact_attachment_hint'=>'توضیح پیوست','contact_consent_before'=>'متن قبل از پیوند حریم خصوصی','contact_privacy_label'=>'متن پیوند حریم خصوصی','contact_consent_after'=>'متن بعد از پیوند حریم خصوصی','contact_submit_label'=>'متن دکمه ارسال','contact_form_success'=>'پیام ثبت موفق') as $key=>$label){genosys_settings_field($key,$label,in_array($key,array('contact_form_description','contact_form_success'),true)?'textarea':'text');}
	genosys_settings_field('contact_privacy_url','پیوند حریم خصوصی','url',array('hint'=>'خالی یعنی صفحه حریم خصوصی انتخاب‌شده در تنظیمات وردپرس.'));
	genosys_settings_field('contact_notification_enabled','اعلان ایمیلی مدیر','select',array('options'=>array(0=>'غیرفعال',1=>'فعال'),'hint'=>'ارسال ایمیل تنها پس از ثبت پیام انجام می‌شود؛ خود پیام همیشه در صندوق خصوصی نگهداری می‌شود.'));
	genosys_settings_field('contact_notification_email','ایمیل دریافت اعلان','email',array('ltr'=>true,'hint'=>'هیچ اطلاعات خصوصی یا فایل پیوستی در اعلان ارسال نمی‌شود.'));echo '</div></section>';
	genosys_settings_card_open('سؤال‌های رایج','تا ۳۰ پرسش و پاسخ اضافه کنید. حذف همه ردیف‌ها، فهرست سؤال‌های صفحه را خالی می‌کند.');echo '<div class="gs-grid">';
	foreach(array('contact_faq_before'=>'ابتدای عنوان','contact_faq_highlight'=>'بخش رنگی عنوان','contact_faq_after'=>'ادامه عنوان','contact_faq_description'=>'توضیح بخش') as $key=>$label){genosys_settings_field($key,$label);}echo '</div>';
	echo '<input type="hidden" name="site[contact_faq_present]" value="1"><div class="gs-repeater gs-spacing" data-repeater="contact_faq" data-max-rows="30"><div class="gs-repeater__rows">';
	foreach(genosys_contact_setting('faq') as $index=>$row){genosys_contact_settings_faq_row($index,$row);}
	echo '</div><p class="gs-empty"'.(genosys_contact_setting('faq')?' hidden':'').'>هنوز پرسشی اضافه نشده است.</p><template data-row-template>';genosys_contact_settings_faq_row('__INDEX__',array());echo '</template><button type="button" class="gs-outline" data-add-row>+ افزودن پرسش و پاسخ</button></div></section>';
}
