<?php
/** Shamsi formatting for order SMS dates, independent of date-format filters. */
defined( 'ABSPATH' ) || exit;

/*
 * Gregorian-to-Jalali conversion adapted from jalaali-js v1.2.8 (jalCal/d2j),
 * based on Kazimierz M. Borkowski's Persian calendar algorithm.
 * https://github.com/jalaali/jalaali-js/tree/v1.2.8
 *
 * MIT License
 * Copyright (c) 2020 Behrang Norouzinia
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 * Format a real Unix timestamp as YYYY/MM/DD HH:MM in the site's timezone.
 *
 * @param int $timestamp Unix timestamp, without an added local offset.
 * @return string Shamsi date and time, with Persian digits when available.
 */
function genosys_sms_shamsi_date( $timestamp ): string {
	// Native formatting bypasses wp_date/date_i18n calendar-conversion filters.
	$local_date = ( new DateTimeImmutable( '@' . (int) $timestamp ) )->setTimezone( wp_timezone() );
	$gy         = (int) $local_date->format( 'Y' );
	$jy         = $gy - 621;
	$breaks     = array( -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178 );
	if ( $jy < $breaks[0] || $jy >= $breaks[ count( $breaks ) - 1 ] ) {
		throw new RangeException( 'SMS date is outside the supported Jalali calendar range.' );
	}

	$leap_j = -14;
	$jp     = $breaks[0];
	$jump   = 0;
	foreach ( array_slice( $breaks, 1 ) as $jm ) {
		$jump = $jm - $jp;
		if ( $jy < $jm ) {
			break;
		}
		$leap_j += intdiv( $jump, 33 ) * 8 + intdiv( $jump % 33, 4 );
		$jp      = $jm;
	}
	$n       = $jy - $jp;
	$leap_j += intdiv( $n, 33 ) * 8 + intdiv( $n % 33 + 3, 4 );
	if ( 4 === $jump % 33 && 4 === $jump - $n ) {
		++$leap_j;
	}
	$leap_g = intdiv( $gy, 4 ) - intdiv( ( intdiv( $gy, 100 ) + 1 ) * 3, 4 ) - 150;
	$march  = 20 + $leap_j - $leap_g;
	if ( $jump - $n < 6 ) {
		$n = $n - $jump + intdiv( $jump + 4, 33 ) * 33;
	}
	$leap = ( ( $n + 1 ) % 33 - 1 ) % 4;
	if ( -1 === $leap ) {
		$leap = 4;
	}

	// The native Gregorian day-of-year replaces a Julian-day round trip.
	$gregorian_leap = 0 === $gy % 4 && ( 0 !== $gy % 100 || 0 === $gy % 400 );
	$k              = (int) $local_date->format( 'z' ) - ( 59 + (int) $gregorian_leap + $march - 1 );
	if ( $k >= 0 && $k <= 185 ) {
		$jm = 1 + intdiv( $k, 31 );
		$jd = $k % 31 + 1;
	} else {
		if ( $k >= 0 ) {
			$k -= 186;
		} else {
			--$jy;
			$k += 179 + ( 1 === $leap ? 1 : 0 );
		}
		$jm = 7 + intdiv( $k, 30 );
		$jd = $k % 30 + 1;
	}
	$date_text = sprintf( '%04d/%02d/%02d %s', $jy, $jm, $jd, $local_date->format( 'H:i' ) );
	return function_exists( 'genosys_persian_number' ) ? genosys_persian_number( $date_text ) : $date_text;
}
