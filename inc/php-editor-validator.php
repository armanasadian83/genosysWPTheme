<?php
/** Conservative, non-executing checks for a proposed PHP source edit. */
defined( 'ABSPATH' ) || exit;

function genosys_php_editor_tokens( $source, $parse = true ) {
	$raw = token_get_all( $source, $parse ? TOKEN_PARSE : 0 );
	$out = array(); $line = 1;
	foreach ( $raw as $token ) {
		$id = is_array( $token ) ? $token[0] : 0;
		$text = is_array( $token ) ? $token[1] : $token;
		$start = is_array( $token ) ? $token[2] : $line;
		if ( ! in_array( $id, array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) { $out[] = array( 'id'=>$id, 'text'=>$text, 'line'=>$start ); }
		$line = $start + substr_count( $text, "\n" );
	}
	return $out;
}

function genosys_php_editor_join( $tokens ) {
	return implode( '', array_column( $tokens, 'text' ) );
}

function genosys_php_editor_end( $tokens, $start, $open = '(', $close = ')' ) {
	$depth = 0;
	for ( $i = $start, $n = count( $tokens ); $i < $n; $i++ ) {
		if ( $open === $tokens[$i]['text'] ) { $depth++; }
		if ( $close === $tokens[$i]['text'] && 0 === --$depth ) { return $i; }
	}
	return $start;
}

function genosys_php_editor_segments( $tokens ) {
	$segments = array(); $part = array(); $depth = 0;
	foreach ( $tokens as $token ) {
		if ( ',' === $token['text'] && 0 === $depth ) { $segments[] = $part; $part = array(); continue; }
		if ( in_array( $token['text'], array( '(', '[', '{' ), true ) ) { $depth++; }
		if ( in_array( $token['text'], array( ')', ']', '}' ), true ) ) { $depth--; }
		$part[] = $token;
	}
	if ( $part ) { $segments[] = $part; }
	return $segments;
}

function genosys_php_editor_literal( $tokens ) {
	$text = genosys_php_editor_join( $tokens );
	if ( ! $tokens ) { return array( 'known'=>false ); }
	if ( in_array( strtolower( $text ), array( 'true','false','null' ), true ) ) { return array( 'known'=>true, 'type'=>'null' === strtolower( $text ) ? 'null' : 'bool', 'value'=>'true' === strtolower( $text ) ); }
	if ( 1 === count( $tokens ) && T_CONSTANT_ENCAPSED_STRING === $tokens[0]['id'] ) { return array( 'known'=>true, 'type'=>'string', 'value'=>genosys_php_editor_decode_string( $text ) ); }
	if ( preg_match( '/^[+-]?(?:\d+|0x[a-f0-9]+)$/iD', $text ) ) { return array( 'known'=>true, 'type'=>'int', 'value'=>(int) $text ); }
	if ( is_numeric( $text ) ) { return array( 'known'=>true, 'type'=>'float', 'value'=>(float) $text ); }
	if ( '[' === $tokens[0]['text'] || T_ARRAY === $tokens[0]['id'] ) { return array( 'known'=>true, 'type'=>'array', 'value'=>array() ); }
	return array( 'known'=>false );
}

function genosys_php_editor_decode_string( $text ) {
	$inner = substr( $text,1,-1 );
	if ( "'" !== substr( $text,0,1 ) ) { return stripcslashes( $inner ); }
	$result = '';
	for ( $i = 0,$n = strlen( $inner ); $i < $n; $i++ ) {
		if ( '\\' === $inner[$i] && isset( $inner[$i + 1] ) && in_array( $inner[$i + 1],array( '\\',"'" ),true ) ) { $result .= $inner[++$i]; }
		else { $result .= $inner[$i]; }
	}
	return $result;
}

/** Recursive literal grammar, never PHP expression evaluation. */
function genosys_php_editor_constant_value( $tokens, &$offset, &$type ) {
	$token = $tokens[$offset] ?? null; if ( ! $token ) { return false; }
	if ( in_array( $token['id'],array( T_CONSTANT_ENCAPSED_STRING,T_LNUMBER,T_DNUMBER ),true ) ) { $offset++; $type = T_CONSTANT_ENCAPSED_STRING === $token['id'] ? 'string' : ( T_DNUMBER === $token['id'] ? 'float' : 'int' ); return true; }
	if ( in_array( $token['text'],array( '+','-' ),true ) && isset( $tokens[$offset + 1] ) && in_array( $tokens[$offset + 1]['id'],array( T_LNUMBER,T_DNUMBER ),true ) ) { $type = T_DNUMBER === $tokens[$offset + 1]['id'] ? 'float' : 'int'; $offset += 2; return true; }
	if ( T_STRING === $token['id'] && in_array( strtolower( $token['text'] ),array( 'true','false','null' ),true ) ) { $type = 'null' === strtolower( $token['text'] ) ? 'null' : 'bool'; $offset++; return true; }
	$close = null;
	if ( '[' === $token['text'] ) { $close = ']'; $offset++; }
	elseif ( T_ARRAY === $token['id'] && '(' === ( $tokens[$offset + 1]['text'] ?? '' ) ) { $close = ')'; $offset += 2; }
	if ( ! $close ) { return false; }
	if ( $close === ( $tokens[$offset]['text'] ?? '' ) ) { $offset++; $type = 'array'; return true; }
	while ( isset( $tokens[$offset] ) ) {
		$item_type = null;
		if ( ! genosys_php_editor_constant_value( $tokens,$offset,$item_type ) ) { return false; }
		if ( '=>' === ( $tokens[$offset]['text'] ?? '' ) ) {
			if ( ! in_array( $item_type,array( 'string','int','bool','null' ),true ) ) { return false; }
			$offset++; $value_type = null;
			if ( ! genosys_php_editor_constant_value( $tokens,$offset,$value_type ) ) { return false; }
		}
		$next = $tokens[$offset]['text'] ?? '';
		if ( $next === $close ) { $offset++; $type = 'array'; return true; }
		if ( ',' !== $next ) { return false; }
		$offset++;
		if ( $close === ( $tokens[$offset]['text'] ?? '' ) ) { $offset++; $type = 'array'; return true; }
	}
	return false;
}

function genosys_php_editor_constant_argument( $tokens ) {
	$offset = 0; $type = null;
	return genosys_php_editor_constant_value( $tokens,$offset,$type ) && count( $tokens ) === $offset;
}

/** Model calls/declarations without evaluating any candidate expression. */
function genosys_php_editor_model( $tokens ) {
	$model = array( 'definitions'=>array(), 'calls'=>array(), 'includes'=>array(), 'classes'=>array(), 'namespaces'=>array(), 'forbidden'=>array(), 'literals'=>array() );
	$declarations = array(); $class_ranges = array(); $names = array( T_STRING );
	foreach ( array( 'T_NAME_FULLY_QUALIFIED','T_NAME_QUALIFIED','T_NAME_RELATIVE' ) as $constant ) { if ( defined( $constant ) ) { $names[] = constant( $constant ); } }
	foreach ( $tokens as $i => $token ) {
		if ( T_CONSTANT_ENCAPSED_STRING === $token['id'] ) { $model['literals'][] = array( 'value'=>strtolower( genosys_php_editor_decode_string( $token['text'] ) ), 'line'=>$token['line'] ); }
		if ( T_NAMESPACE === $token['id'] ) { $model['namespaces'][] = $token['text'] . ( $tokens[$i + 1]['text'] ?? '' ); }
		if ( in_array( $token['id'], array( T_CLASS,T_INTERFACE,T_TRAIT ), true ) && '::' !== ( $tokens[$i - 1]['text'] ?? '' ) ) {
			$start = $i;
			while ( isset( $tokens[$start] ) && '{' !== $tokens[$start]['text'] ) { $start++; }
			if ( isset( $tokens[$start] ) ) { $end = genosys_php_editor_end( $tokens, $start, '{', '}' ); $class_ranges[] = array( $i, $end ); $model['classes'][] = hash( 'sha256', genosys_php_editor_join( array_slice( $tokens, $i, $end - $i + 1 ) ) ); }
		}
		if ( in_array( $token['id'], array( T_EVAL,T_HALT_COMPILER,T_EXIT,T_THROW,T_GOTO ), true ) ) { $model['forbidden'][] = $token; }
		if ( in_array( $token['id'], array( T_REQUIRE,T_REQUIRE_ONCE,T_INCLUDE,T_INCLUDE_ONCE ), true ) ) {
			$end = $i + 1; $depth = 0;
			while ( isset( $tokens[$end] ) ) {
				$text = $tokens[$end]['text'];
				if ( 0 === $depth && in_array( $text, array( ';',',' ), true ) ) { break; }
				if ( in_array( $text, array( '(', '[', '{' ), true ) ) { $depth++; }
				if ( in_array( $text, array( ')', ']', '}' ), true ) ) { if ( 0 === $depth ) { break; } $depth--; }
				$end++;
			}
			$model['includes'][] = array( 'text'=>genosys_php_editor_join( array_slice( $tokens, $i, $end - $i ) ), 'line'=>$token['line'] );
		}
		if ( T_FUNCTION !== $token['id'] ) { continue; }
		$name_index = $i + 1;
		if ( '&' === ( $tokens[$name_index]['text'] ?? '' ) ) { $name_index++; }
		if ( ! isset( $tokens[$name_index] ) || T_STRING !== $tokens[$name_index]['id'] || '(' !== ( $tokens[$name_index + 1]['text'] ?? '' ) ) { continue; }
		$declarations[$name_index] = true;
		$inside_class = false;
		foreach ( $class_ranges as $range ) { if ( $i > $range[0] && $i < $range[1] ) { $inside_class = true; break; } }
		if ( $inside_class ) { continue; }
		$name = strtolower( $tokens[$name_index]['text'] );
		$end = genosys_php_editor_end( $tokens, $name_index + 1 );
		$parts = genosys_php_editor_segments( array_slice( $tokens, $name_index + 2, $end - $name_index - 2 ) );
		$parameters = array(); $required = 0; $variadic = false;
		foreach ( $parts as $index => $part ) {
			$type = ''; $optional = false; $parameter_name = ''; $by_ref = false;
			foreach ( $part as $item ) {
				if ( '=' === $item['text'] ) { $optional = true; break; }
				if ( '...' === $item['text'] ) { $variadic = true; continue; }
				if ( '&' === $item['text'] ) { $by_ref = true; continue; }
				if ( T_VARIABLE === $item['id'] ) { $parameter_name = substr( $item['text'], 1 ); continue; }
				if ( ! $parameter_name ) { $type .= $item['text']; }
			}
			if ( ! $optional && ! $variadic ) { $required = $index + 1; }
			$parameters[] = array( 'name'=>$parameter_name, 'type'=>$type, 'reference'=>$by_ref, 'optional'=>$optional );
		}
		$return_type = '';
		if ( ':' === ( $tokens[$end + 1]['text'] ?? '' ) ) { for ( $r = $end + 2; isset( $tokens[$r] ) && ! in_array( $tokens[$r]['text'], array( '{',';' ), true ); $r++ ) { $return_type .= $tokens[$r]['text']; } }
		$definition = array( 'name'=>$name, 'line'=>$token['line'], 'required'=>$required, 'maximum'=>count( $parts ), 'variadic'=>$variadic, 'parameters'=>$parameters, 'return_type'=>$return_type );
		$model['definitions'][$name][] = $definition;
	}
	foreach ( $tokens as $i => $token ) {
		if ( '(' !== ( $tokens[$i + 1]['text'] ?? '' ) || isset( $declarations[$i] ) ) { continue; }
		$is_name = in_array( $token['id'], $names, true );
		if ( ! $is_name && T_VARIABLE !== $token['id'] ) { continue; }
		$previous = $tokens[$i - 1]['text'] ?? '';
		$kind = 'function'; $name = strtolower( ltrim( $token['text'], '\\' ) ); $class = '';
		if ( in_array( $previous, array( '->','?->' ), true ) || T_VARIABLE === $token['id'] ) { $kind = 'dynamic'; }
		elseif ( '::' === $previous ) { $kind = 'static'; $class = ltrim( $tokens[$i - 2]['text'] ?? '', '\\' ); }
		elseif ( T_NEW === ( $tokens[$i - 1]['id'] ?? 0 ) ) { $kind = 'constructor'; $class = ltrim( $token['text'], '\\' ); }
		elseif ( in_array( $previous, array( 'function','fn' ), true ) ) { continue; }
		$end = genosys_php_editor_end( $tokens, $i + 1 );
		$arguments = genosys_php_editor_segments( array_slice( $tokens, $i + 2, $end - $i - 2 ) );
		$start = $i;
		if ( 'dynamic' === $kind && in_array( $previous, array( '->','?->' ), true ) ) {
			$start = max( 0,$i - 2 ); $depth = 0;
			for ( $r = $i - 2; $r >= 0; $r-- ) {
				$value = $tokens[$r]['text'];
				if ( in_array( $value,array( ')',']' ),true ) ) { $depth++; }
				if ( in_array( $value,array( '(', '[' ),true ) ) { if ( 0 === $depth ) { break; } $depth--; }
				if ( 0 === $depth && in_array( $value,array( ';','{','}','=',',','return','echo' ),true ) ) { break; }
				$start = $r;
			}
		}
		$text = $kind . '|' . strtolower( $class ) . '|' . genosys_php_editor_join( array_slice( $tokens, $start, $end - $start + 1 ) );
		$model['calls'][] = array( 'kind'=>$kind, 'name'=>$name, 'class'=>$class, 'arguments'=>$arguments, 'line'=>$token['line'],'start'=>$start,'end'=>$end, 'fingerprint'=>hash( 'sha256', $text ) );
	}
	return $model;
}

function genosys_php_editor_reflection_signature( $reflection ) {
	$params = array();
	foreach ( $reflection->getParameters() as $parameter ) { $params[] = array( 'name'=>$parameter->getName(), 'type'=>$parameter->hasType() ? (string) $parameter->getType() : '', 'reference'=>$parameter->isPassedByReference(), 'optional'=>$parameter->isOptional() ); }
	return array( 'required'=>$reflection->getNumberOfRequiredParameters(), 'maximum'=>$reflection->getNumberOfParameters(), 'variadic'=>$reflection->isVariadic(), 'parameters'=>$params );
}

/** PHP weak scalar coercions are allowed; arrays/objects and strict calls are checked. */
function genosys_php_editor_type_accepts( $type, $literal, $strict ) {
	if ( ! $type || 'mixed' === strtolower( $type ) ) { return true; }
	$type = strtolower( str_replace( array( '?','\\' ), array( 'null|','' ), $type ) );
	foreach ( explode( '|', $type ) as $allowed ) {
		if ( $allowed === $literal['type'] || ( 'float' === $allowed && 'int' === $literal['type'] ) || ( 'iterable' === $allowed && 'array' === $literal['type'] ) ) { return true; }
		if ( 'false' === $allowed && 'bool' === $literal['type'] && false === $literal['value'] ) { return true; }
		if ( 'true' === $allowed && 'bool' === $literal['type'] && true === $literal['value'] ) { return true; }
		if ( ! $strict && in_array( $allowed, array( 'string','int','float','bool' ), true ) && in_array( $literal['type'], array( 'string','int','float','bool' ), true ) ) {
			if ( ! in_array( $allowed, array( 'int','float' ), true ) || 'string' !== $literal['type'] || is_numeric( $literal['value'] ) ) { return true; }
		}
	}
	return false;
}

function genosys_php_editor_call_errors( $call, $definitions, $strict ) {
	$error = static function( $code, $message ) use ( $call ) { return array( 'line'=>$call['line'], 'code'=>$code, 'message'=>$message ); };
	if ( 'dynamic' === $call['kind'] ) { return array( $error( 'unsupported_dynamic', 'فراخوانی پویا یا متدِ جدید بدون تعیین نوع شیء قابل تأیید نیست؛ این تغییر ذخیره نمی‌شود.' ) ); }
	$signature = null;
	if ( 'function' === $call['kind'] && isset( $definitions[ $call['name'] ] ) ) { $signature = $definitions[ $call['name'] ][0]; }
	else {
		try {
			if ( 'function' === $call['kind'] ) {
				if ( ! function_exists( $call['name'] ) ) { return array( $error( 'undefined_function', 'تابع «' . $call['name'] . '» در وردپرس فعال یا کد پیشنهادی تعریف نشده است.' ) ); }
				$reflection = new ReflectionFunction( $call['name'] );
			} else {
				if ( ! class_exists( $call['class'], false ) ) { return array( $error( 'unsupported_class', 'کلاس «' . $call['class'] . '» در زمان بررسی بارگذاری نشده است؛ فراخوانی جدید قابل تأیید نیست.' ) ); }
				$reflection = 'constructor' === $call['kind'] ? ( new ReflectionClass( $call['class'] ) )->getConstructor() : new ReflectionMethod( $call['class'], $call['name'] );
				if ( null === $reflection ) { $signature = array( 'required'=>0, 'maximum'=>0, 'variadic'=>false, 'parameters'=>array() ); }
				elseif ( ! $reflection->isPublic() || ( 'static' === $call['kind'] && ! $reflection->isStatic() ) ) { return array( $error( 'method_access', 'این متد از این نوع فراخوانی عمومی و قابل دسترس نیست.' ) ); }
			}
			if ( null === $signature ) { $signature = genosys_php_editor_reflection_signature( $reflection ); }
		} catch ( ReflectionException $exception ) { return array( $error( 'undefined_method', 'متد یا سازندهٔ فراخوانی‌شده وجود ندارد.' ) ); }
	}
	$count = count( $call['arguments'] );
	foreach ( $call['arguments'] as $argument ) {
		foreach ( $argument as $i => $token ) {
			if ( '...' === $token['text'] || ( ':' === $token['text'] && 1 === $i ) ) { return array( $error( 'unsupported_arguments', 'پارامتر نام‌دار یا بازشدهٔ این فراخوانی جدید به‌صورت ایستا قابل تأیید نیست.' ) ); }
		}
	}
	if ( $count < $signature['required'] || ( ! $signature['variadic'] && $count > $signature['maximum'] ) ) { return array( $error( 'argument_count', 'تعداد پارامترهای «' . $call['name'] . '» با امضای تابع سازگار نیست (حداقل ' . $signature['required'] . '، حداکثر ' . ( $signature['variadic'] ? 'نامحدود' : $signature['maximum'] ) . ').' ) ); }
	$errors = array();
	foreach ( $call['arguments'] as $index => $argument ) {
		$parameter = $signature['parameters'][ min( $index, max( 0, count( $signature['parameters'] ) - 1 ) ) ] ?? null;
		if ( ! $parameter ) { continue; }
		$literal = genosys_php_editor_literal( $argument );
		if ( $parameter['reference'] && $literal['known'] ) { $errors[] = $error( 'argument_reference', 'پارامتر ' . ( $index + 1 ) . ' باید یک متغیر قابل ارجاع باشد.' ); }
		if ( $parameter['type'] && ! $literal['known'] ) { $errors[] = $error( 'unsupported_type', 'نوع مقدار پویای پارامتر ' . ( $index + 1 ) . ' در فراخوانی جدید قابل اثبات نیست؛ تغییر ذخیره نمی‌شود.' ); }
		elseif ( $literal['known'] && ! genosys_php_editor_type_accepts( $parameter['type'], $literal, $strict ) ) { $errors[] = $error( 'argument_type', 'نوع پارامتر ' . ( $index + 1 ) . ' در «' . $call['name'] . '» با نوع «' . $parameter['type'] . '» سازگار نیست.' ); }
	}
	if ( ! $errors ) {
		// Reflection proves a signature, not a function's value-domain semantics.
		// New/changed calls outside this deliberately small presentation surface
		// therefore remain unsupported, even if their PHP types look plausible.
		$one_string = array( 'strlen','strtolower','strtoupper','htmlspecialchars','esc_html','esc_attr','esc_textarea','esc_url','esc_url_raw','sanitize_text_field','sanitize_textarea_field','sanitize_key','sanitize_title' );
		$translations = array( '__','_e','esc_html__','esc_attr__','esc_html_e','esc_attr_e' );
		$contexts = array( '_x','_ex','esc_html_x','esc_attr_x' );
		$allowed = 'function' === $call['kind'] && ( in_array( $call['name'],$one_string,true ) || in_array( $call['name'],$translations,true ) || in_array( $call['name'],$contexts,true ) || 'count' === $call['name'] );
		if ( $allowed ) {
			$minimum = in_array( $call['name'],$contexts,true ) ? 2 : 1;
			$maximum = in_array( $call['name'],$contexts,true ) ? 3 : ( in_array( $call['name'],$translations,true ) ? 2 : 1 );
			$allowed = $count >= $minimum && $count <= $maximum;
			foreach ( $call['arguments'] as $argument ) {
				$literal = genosys_php_editor_literal( $argument );
				$wanted = 'count' === $call['name'] ? 'array' : 'string';
				if ( ! genosys_php_editor_constant_argument( $argument ) || empty( $literal['known'] ) || $wanted !== ( $literal['type'] ?? '' ) ) { $allowed = false; }
			}
		}
		if ( ! $allowed ) { $errors[] = $error( 'unsupported_semantics','معنای اجرایی یا دامنهٔ مقادیر این فراخوانی جدید/تغییرکرده با اطمینان قابل تأیید نیست؛ فقط توابع نمایشی محدود با پارامترهای ثابت پذیرفته می‌شوند.' ); }
	}
	return $errors;
}

function genosys_php_editor_validate_source( $file, $content, $previous, $files ) {
	$errors = array(); $warnings = array(); $notices = array();
	$checks = array(
		'syntax'=>array( 'id'=>'syntax','status'=>'pending','message'=>'تجزیهٔ PHP روی همین سرور' ),
		'calls'=>array( 'id'=>'calls','status'=>'pending','message'=>'فراخوانی‌ها و پارامترهای جدید یا تغییرکرده' ),
		'dependencies'=>array( 'id'=>'dependencies','status'=>'pending','message'=>'تعریف‌ها و وابستگی‌های موجود قالب' ),
		'safety'=>array( 'id'=>'safety','status'=>'pending','message'=>'محدودهٔ فایل و الگوهای غیرقابل تأیید' ),
		'runtime'=>array( 'id'=>'runtime','status'=>'pending','message'=>'کد ذخیره‌نشده اجرا نمی‌شود؛ ذخیره نیازمند آزمون واقعی مدیر، صفحهٔ اصلی و تنظیمات است.' ),
	);
	$finish = static function() use ( &$errors, &$warnings, &$notices, &$checks, $content ) { return array( 'valid'=>! $errors && ! $warnings, 'errors'=>array_slice( $errors, 0, 40 ), 'warnings'=>$warnings,'notices'=>$notices, 'checks'=>array_values( $checks ), 'scope'=>'تجزیهٔ سرور و بررسی محافظه‌کارانهٔ تغییرها؛ مسیرهای شرطی و داده‌های ورودی به‌طور کامل قابل اثبات نیستند.', 'runtimeVerified'=>false, 'hash'=>hash( 'sha256', $content ) ); };
	if ( ! function_exists( 'token_get_all' ) || ! defined( 'TOKEN_PARSE' ) ) { $errors[] = array( 'line'=>1,'code'=>'parser_unavailable','message'=>'تجزیه‌گر PHP روی سرور در دسترس نیست؛ ذخیره مسدود است.' ); $checks['syntax']['status']='unsupported'; return $finish(); }
	try { $tokens = genosys_php_editor_tokens( $content ); }
	catch ( ParseError $error ) { $errors[] = array( 'line'=>$error->getLine(),'code'=>'parse','message'=>$error->getMessage() ); $checks['syntax']['status']='error'; return $finish(); }
	catch ( Throwable $error ) { $errors[] = array( 'line'=>1,'code'=>'parser','message'=>'تجزیهٔ امن فایل ممکن نشد؛ ذخیره مسدود است.' ); $checks['syntax']['status']='unsupported'; return $finish(); }
	$checks['syntax'] = array( 'id'=>'syntax','status'=>'ok','message'=>'ساختار PHP با تجزیه‌گر همین سرور معتبر است.' );
	try { $old_tokens = genosys_php_editor_tokens( $previous ); $old = genosys_php_editor_model( $old_tokens ); $new = genosys_php_editor_model( $tokens ); }
	catch ( Throwable $error ) { $errors[] = array( 'line'=>1,'code'=>'model','message'=>'زمینهٔ قبلی فایل قابل تحلیل نیست؛ ذخیرهٔ خودکار امن نیست.' ); $checks['dependencies']['status']='unsupported'; return $finish(); }
	if ( $old['namespaces'] !== $new['namespaces'] || $old['classes'] !== $new['classes'] ) { $errors[] = array( 'line'=>1,'code'=>'unsupported_declaration','message'=>'تغییر ساختار کلاس یا فضای نام به آزمون جداگانه نیاز دارد و در این ویرایشگر ذخیره نمی‌شود.' ); }
	if ( $old['includes'] !== $new['includes'] ) {
		// Line shifts alone must not turn an unchanged include into a dependency edit.
		if ( array_column( $old['includes'], 'text' ) !== array_column( $new['includes'], 'text' ) ) { $errors[] = array( 'line'=>1,'code'=>'include_dependency','message'=>'افزودن، حذف یا تغییر include/require موجود بدون آزمون وابستگی کامل مجاز نیست.' ); }
	}
	if ( count( $new['forbidden'] ) > count( $old['forbidden'] ) ) { $errors[] = array( 'line'=>$new['forbidden'][0]['line'],'code'=>'unsafe_execution','message'=>'eval و توقف کامپایل جدید در ویرایشگر قابل تأیید نیست.' ); }
	$counts = array(); foreach ( $old['calls'] as $call ) { $counts[$call['fingerprint']] = ( $counts[$call['fingerprint']] ?? 0 ) + 1; }
	$changed_names = array();
	foreach ( $new['definitions'] as $name => $defs ) {
		if ( count( $defs ) > 1 && count( $defs ) > count( $old['definitions'][$name] ?? array() ) ) { $errors[] = array( 'line'=>$defs[0]['line'],'code'=>'duplicate_function','message'=>'تابع «' . $name . '» بیش از یک بار تعریف شده است.' ); }
		if ( isset( $old['definitions'][$name] ) ) {
			$before = $old['definitions'][$name][0]; $after = $defs[0]; unset( $before['line'], $after['line'] );
			if ( $before !== $after ) { $changed_names[$name] = true; $errors[] = array( 'line'=>$defs[0]['line'],'code'=>'unsupported_signature','message'=>'تغییر امضا یا نوع بازگشت تابع بدون تحلیل همهٔ ورودی‌ها قابل تأیید نیست؛ ذخیره مسدود است.' ); }
		} elseif ( function_exists( $name ) ) { $errors[] = array( 'line'=>$defs[0]['line'],'code'=>'duplicate_function','message'=>'نام تابع جدید «' . $name . '» از قبل در محیط فعال استفاده شده است.' ); }
	}
	$strict = (bool) preg_match( '/declare\s*\(\s*strict_types\s*=\s*1\s*\)/', $content );
	$strict_changed = $strict !== (bool) preg_match( '/declare\s*\(\s*strict_types\s*=\s*1\s*\)/', $previous );
	$call_error_start = count( $errors );
	foreach ( $new['calls'] as $call ) {
		$existing = ! empty( $counts[$call['fingerprint']] );
		if ( $existing ) { $counts[$call['fingerprint']]--; }
		if ( ! $existing || $strict_changed || isset( $changed_names[$call['name']] ) ) { $errors = array_merge( $errors, genosys_php_editor_call_errors( $call, $new['definitions'], $strict ) ); }
	}
	// Compare executable-token skeletons. Only HTML/comments and verified call
	// names/literal arguments may differ; unknown expressions never get a pass.
	$skeleton = static function( $source_tokens,$model ) {
		$replace = array(); $omit = array(); $constant_calls = 0;
		foreach ( $model['calls'] as $call ) {
			if ( 'function' !== $call['kind'] ) { continue; }
			$constant = true; foreach ( $call['arguments'] as $argument ) { if ( ! genosys_php_editor_constant_argument( $argument ) ) { $constant = false; break; } }
			if ( $constant ) {
				$constant_calls++;
				for ( $i = $call['start']; $i <= $call['end']; $i++ ) { $omit[$i] = true; }
				$before = $source_tokens[$call['start'] - 1] ?? array( 'id'=>0,'text'=>'' );
				$after = $source_tokens[$call['end'] + 1] ?? array( 'id'=>0,'text'=>'' );
				$statement = ( in_array( $before['id'],array( T_ECHO,T_PRINT,T_OPEN_TAG,T_OPEN_TAG_WITH_ECHO ),true ) || in_array( $before['text'],array( ';','{' ),true ) ) && ( ';' === $after['text'] || T_CLOSE_TAG === $after['id'] );
				$replace[$call['start']] = ( $statement ? '@statement:' : '@call:' ) . $call['name']; unset( $omit[$call['start']] );
				if ( ';' === ( $source_tokens[$call['end'] + 1]['text'] ?? '' ) ) { $omit[$call['end'] + 1] = true; }
				if ( T_ECHO === ( $source_tokens[$call['start'] - 1]['id'] ?? 0 ) || T_PRINT === ( $source_tokens[$call['start'] - 1]['id'] ?? 0 ) ) { $omit[$call['start'] - 1] = true; }
			} else {
				$cursor = $call['start'] + 2;
				foreach ( $call['arguments'] as $argument ) {
					if ( genosys_php_editor_constant_argument( $argument ) ) {
						$replace[$cursor] = '@constant-argument';
						for ( $i = $cursor + 1; $i < $cursor + count( $argument ); $i++ ) { $omit[$i] = true; }
					}
					$cursor += count( $argument ) + 1;
				}
			}
		}
		$result = array();
		foreach ( $source_tokens as $i => $token ) { if ( T_INLINE_HTML === $token['id'] || isset( $omit[$i] ) ) { continue; } $result[] = $replace[$i] ?? $token['id'] . ':' . $token['text']; }
		return array( 'tokens'=>$result,'constantCalls'=>$constant_calls );
	};
	$old_skeleton = $skeleton( $old_tokens,$old ); $new_skeleton = $skeleton( $tokens,$new );
	$old_index = 0;
	foreach ( $new_skeleton['tokens'] as $value ) {
		if ( isset( $old_skeleton['tokens'][$old_index] ) && $value === $old_skeleton['tokens'][$old_index] ) { $old_index++; }
		elseif ( 0 !== strpos( $value,'@statement:' ) ) { $old_index = -1; break; }
	}
	if ( $old_index !== count( $old_skeleton['tokens'] ) || $new_skeleton['constantCalls'] < $old_skeleton['constantCalls'] ) { $errors[] = array( 'line'=>1,'code'=>'unsupported_expression','message'=>'این تغییر شامل منطق، متغیر، عملگر، ساختار یا عبارت جدیدی است که ایمنی آن به‌صورت ایستا قابل اثبات نیست. فقط تغییر HTML، توضیح و فراخوانی/پارامتر ثابتِ قابل تأیید از این مسیر پذیرفته می‌شود.' ); }
	$checks['calls']['status'] = count( $errors ) > $call_error_start ? 'error' : 'ok';
	$removed = array_diff( array_keys( $old['definitions'] ), array_keys( $new['definitions'] ) );
	$dependency_start = count( $errors );
	foreach ( $files as $metadata ) {
		$path = $metadata['path'];
		try { $model = $path === $file ? $new : genosys_php_editor_model( genosys_php_editor_tokens( (string) @file_get_contents( genosys_php_editor_resolve( $path ) ), false ) ); }
		catch ( Throwable $error ) { $errors[] = array( 'line'=>1,'code'=>'dependency_unverified','message'=>'خواندن وابستگی‌های «' . $path . '» ممکن نشد.' ); continue; }
		if ( $path !== $file ) { foreach ( $new['definitions'] as $name => $definition ) { if ( ! isset( $old['definitions'][$name] ) && isset( $model['definitions'][$name] ) ) { $errors[] = array( 'line'=>$definition[0]['line'],'code'=>'duplicate_function','message'=>'نام تابع جدید در «' . $path . '» نیز تعریف شده است.' ); } } }
		foreach ( $removed as $name ) {
			$referenced = false;
			foreach ( $model['calls'] as $call ) { if ( 'function' === $call['kind'] && $name === $call['name'] ) { $referenced = true; break; } }
			foreach ( $model['literals'] as $literal ) { if ( $name === $literal['value'] ) { $referenced = true; break; } }
			if ( $referenced ) { $errors[] = array( 'line'=>1,'code'=>'removed_dependency','message'=>'تابع حذف‌شدهٔ «' . $name . '» هنوز در «' . $path . '» استفاده می‌شود.' ); }
		}
		if ( $path !== $file && $changed_names ) { foreach ( $model['calls'] as $call ) { if ( isset( $changed_names[$call['name']] ) ) { $found = genosys_php_editor_call_errors( $call, $new['definitions'], false ); foreach ( $found as &$item ) { $item['message'] .= ' وابستگی: ' . $path; } unset( $item ); $errors = array_merge( $errors, $found ); } } }
	}
	$checks['dependencies']['status'] = count( $errors ) > $dependency_start || $removed && $errors ? 'error' : 'ok';
	$checks['safety']['status'] = $errors ? 'error' : 'ok';
	$checks['safety']['message'] = $errors ? 'تغییر دارای خطا یا ساختار غیرقابل تأیید است؛ ذخیره مسدود می‌ماند.' : 'در تغییرهای تحلیل‌شده، وابستگی یا فراخوانی غیرقابل تأیید شناسایی نشد.';
	$notices[] = 'این بررسی اثبات همهٔ مسیرهای اجرایی نیست. آزمون اجرای وردپرس روی مسیرهای بررسی‌شده خطاهای مهلک را گزارش می‌کند؛ همهٔ هشدارها و مسیرهای شرطی پوشش داده نمی‌شوند.';
	return $finish();
}
