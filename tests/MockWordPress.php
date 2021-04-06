<?php

	function add_action( $type, $function ) {
		// Don't bother implementing this.
	}

	function apply_filters( $tag, $value ) {
		global $wp_filter, $wp_current_filter;
	 
		$args = func_get_args();
	 
		// Do 'all' actions first.
		if ( isset( $wp_filter['all'] ) ) {
			$wp_current_filter[] = $tag;
			_wp_call_all_hook( $args );
		}
	 
		if ( ! isset( $wp_filter[ $tag ] ) ) {
			if ( isset( $wp_filter['all'] ) ) {
				array_pop( $wp_current_filter );
			}
			return $value;
		}
	 
		if ( ! isset( $wp_filter['all'] ) ) {
			$wp_current_filter[] = $tag;
		}
	 
		// Don't pass the tag name to WP_Hook.
		array_shift( $args );
	 
		$filtered = $wp_filter[ $tag ]->apply_filters( $value, $args );
	 
		array_pop( $wp_current_filter );
	 
		return $filtered;
	}

	function sanitize_title( $title, $fallback_title = '', $context = 'save' ) {
		// Make up our own that emulates WordPress’s default.
		return strtolower(trim(preg_replace('/[^A-Za-z0-9-_]+/', '-', remove_accents($title)), '-'));
	}

	function remove_accents( $string ) {
		if ( ! preg_match( '/[\x80-\xff]/', $string ) ) {
			return $string;
		}
	 
		if ( seems_utf8( $string ) ) {
			$chars = array(
				// Decompositions for Latin-1 Supplement.
				'ª' => 'a',
				'º' => 'o',
				'À' => 'A',
				'Á' => 'A',
				'Â' => 'A',
				'Ã' => 'A',
				'Ä' => 'A',
				'Å' => 'A',
				'Æ' => 'AE',
				'Ç' => 'C',
				'È' => 'E',
				'É' => 'E',
				'Ê' => 'E',
				'Ë' => 'E',
				'Ì' => 'I',
				'Í' => 'I',
				'Î' => 'I',
				'Ï' => 'I',
				'Ð' => 'D',
				'Ñ' => 'N',
				'Ò' => 'O',
				'Ó' => 'O',
				'Ô' => 'O',
				'Õ' => 'O',
				'Ö' => 'O',
				'Ù' => 'U',
				'Ú' => 'U',
				'Û' => 'U',
				'Ü' => 'U',
				'Ý' => 'Y',
				'Þ' => 'TH',
				'ß' => 's',
				'à' => 'a',
				'á' => 'a',
				'â' => 'a',
				'ã' => 'a',
				'ä' => 'a',
				'å' => 'a',
				'æ' => 'ae',
				'ç' => 'c',
				'è' => 'e',
				'é' => 'e',
				'ê' => 'e',
				'ë' => 'e',
				'ì' => 'i',
				'í' => 'i',
				'î' => 'i',
				'ï' => 'i',
				'ð' => 'd',
				'ñ' => 'n',
				'ò' => 'o',
				'ó' => 'o',
				'ô' => 'o',
				'õ' => 'o',
				'ö' => 'o',
				'ø' => 'o',
				'ù' => 'u',
				'ú' => 'u',
				'û' => 'u',
				'ü' => 'u',
				'ý' => 'y',
				'þ' => 'th',
				'ÿ' => 'y',
				'Ø' => 'O',
				// Decompositions for Latin Extended-A.
				'Ā' => 'A',
				'ā' => 'a',
				'Ă' => 'A',
				'ă' => 'a',
				'Ą' => 'A',
				'ą' => 'a',
				'Ć' => 'C',
				'ć' => 'c',
				'Ĉ' => 'C',
				'ĉ' => 'c',
				'Ċ' => 'C',
				'ċ' => 'c',
				'Č' => 'C',
				'č' => 'c',
				'Ď' => 'D',
				'ď' => 'd',
				'Đ' => 'D',
				'đ' => 'd',
				'Ē' => 'E',
				'ē' => 'e',
				'Ĕ' => 'E',
				'ĕ' => 'e',
				'Ė' => 'E',
				'ė' => 'e',
				'Ę' => 'E',
				'ę' => 'e',
				'Ě' => 'E',
				'ě' => 'e',
				'Ĝ' => 'G',
				'ĝ' => 'g',
				'Ğ' => 'G',
				'ğ' => 'g',
				'Ġ' => 'G',
				'ġ' => 'g',
				'Ģ' => 'G',
				'ģ' => 'g',
				'Ĥ' => 'H',
				'ĥ' => 'h',
				'Ħ' => 'H',
				'ħ' => 'h',
				'Ĩ' => 'I',
				'ĩ' => 'i',
				'Ī' => 'I',
				'ī' => 'i',
				'Ĭ' => 'I',
				'ĭ' => 'i',
				'Į' => 'I',
				'į' => 'i',
				'İ' => 'I',
				'ı' => 'i',
				'Ĳ' => 'IJ',
				'ĳ' => 'ij',
				'Ĵ' => 'J',
				'ĵ' => 'j',
				'Ķ' => 'K',
				'ķ' => 'k',
				'ĸ' => 'k',
				'Ĺ' => 'L',
				'ĺ' => 'l',
				'Ļ' => 'L',
				'ļ' => 'l',
				'Ľ' => 'L',
				'ľ' => 'l',
				'Ŀ' => 'L',
				'ŀ' => 'l',
				'Ł' => 'L',
				'ł' => 'l',
				'Ń' => 'N',
				'ń' => 'n',
				'Ņ' => 'N',
				'ņ' => 'n',
				'Ň' => 'N',
				'ň' => 'n',
				'ŉ' => 'n',
				'Ŋ' => 'N',
				'ŋ' => 'n',
				'Ō' => 'O',
				'ō' => 'o',
				'Ŏ' => 'O',
				'ŏ' => 'o',
				'Ő' => 'O',
				'ő' => 'o',
				'Œ' => 'OE',
				'œ' => 'oe',
				'Ŕ' => 'R',
				'ŕ' => 'r',
				'Ŗ' => 'R',
				'ŗ' => 'r',
				'Ř' => 'R',
				'ř' => 'r',
				'Ś' => 'S',
				'ś' => 's',
				'Ŝ' => 'S',
				'ŝ' => 's',
				'Ş' => 'S',
				'ş' => 's',
				'Š' => 'S',
				'š' => 's',
				'Ţ' => 'T',
				'ţ' => 't',
				'Ť' => 'T',
				'ť' => 't',
				'Ŧ' => 'T',
				'ŧ' => 't',
				'Ũ' => 'U',
				'ũ' => 'u',
				'Ū' => 'U',
				'ū' => 'u',
				'Ŭ' => 'U',
				'ŭ' => 'u',
				'Ů' => 'U',
				'ů' => 'u',
				'Ű' => 'U',
				'ű' => 'u',
				'Ų' => 'U',
				'ų' => 'u',
				'Ŵ' => 'W',
				'ŵ' => 'w',
				'Ŷ' => 'Y',
				'ŷ' => 'y',
				'Ÿ' => 'Y',
				'Ź' => 'Z',
				'ź' => 'z',
				'Ż' => 'Z',
				'ż' => 'z',
				'Ž' => 'Z',
				'ž' => 'z',
				'ſ' => 's',
				// Decompositions for Latin Extended-B.
				'Ș' => 'S',
				'ș' => 's',
				'Ț' => 'T',
				'ț' => 't',
				// Euro sign.
				'€' => 'E',
				// GBP (Pound) sign.
				'£' => '',
				// Vowels with diacritic (Vietnamese).
				// Unmarked.
				'Ơ' => 'O',
				'ơ' => 'o',
				'Ư' => 'U',
				'ư' => 'u',
				// Grave accent.
				'Ầ' => 'A',
				'ầ' => 'a',
				'Ằ' => 'A',
				'ằ' => 'a',
				'Ề' => 'E',
				'ề' => 'e',
				'Ồ' => 'O',
				'ồ' => 'o',
				'Ờ' => 'O',
				'ờ' => 'o',
				'Ừ' => 'U',
				'ừ' => 'u',
				'Ỳ' => 'Y',
				'ỳ' => 'y',
				// Hook.
				'Ả' => 'A',
				'ả' => 'a',
				'Ẩ' => 'A',
				'ẩ' => 'a',
				'Ẳ' => 'A',
				'ẳ' => 'a',
				'Ẻ' => 'E',
				'ẻ' => 'e',
				'Ể' => 'E',
				'ể' => 'e',
				'Ỉ' => 'I',
				'ỉ' => 'i',
				'Ỏ' => 'O',
				'ỏ' => 'o',
				'Ổ' => 'O',
				'ổ' => 'o',
				'Ở' => 'O',
				'ở' => 'o',
				'Ủ' => 'U',
				'ủ' => 'u',
				'Ử' => 'U',
				'ử' => 'u',
				'Ỷ' => 'Y',
				'ỷ' => 'y',
				// Tilde.
				'Ẫ' => 'A',
				'ẫ' => 'a',
				'Ẵ' => 'A',
				'ẵ' => 'a',
				'Ẽ' => 'E',
				'ẽ' => 'e',
				'Ễ' => 'E',
				'ễ' => 'e',
				'Ỗ' => 'O',
				'ỗ' => 'o',
				'Ỡ' => 'O',
				'ỡ' => 'o',
				'Ữ' => 'U',
				'ữ' => 'u',
				'Ỹ' => 'Y',
				'ỹ' => 'y',
				// Acute accent.
				'Ấ' => 'A',
				'ấ' => 'a',
				'Ắ' => 'A',
				'ắ' => 'a',
				'Ế' => 'E',
				'ế' => 'e',
				'Ố' => 'O',
				'ố' => 'o',
				'Ớ' => 'O',
				'ớ' => 'o',
				'Ứ' => 'U',
				'ứ' => 'u',
				// Dot below.
				'Ạ' => 'A',
				'ạ' => 'a',
				'Ậ' => 'A',
				'ậ' => 'a',
				'Ặ' => 'A',
				'ặ' => 'a',
				'Ẹ' => 'E',
				'ẹ' => 'e',
				'Ệ' => 'E',
				'ệ' => 'e',
				'Ị' => 'I',
				'ị' => 'i',
				'Ọ' => 'O',
				'ọ' => 'o',
				'Ộ' => 'O',
				'ộ' => 'o',
				'Ợ' => 'O',
				'ợ' => 'o',
				'Ụ' => 'U',
				'ụ' => 'u',
				'Ự' => 'U',
				'ự' => 'u',
				'Ỵ' => 'Y',
				'ỵ' => 'y',
				// Vowels with diacritic (Chinese, Hanyu Pinyin).
				'ɑ' => 'a',
				// Macron.
				'Ǖ' => 'U',
				'ǖ' => 'u',
				// Acute accent.
				'Ǘ' => 'U',
				'ǘ' => 'u',
				// Caron.
				'Ǎ' => 'A',
				'ǎ' => 'a',
				'Ǐ' => 'I',
				'ǐ' => 'i',
				'Ǒ' => 'O',
				'ǒ' => 'o',
				'Ǔ' => 'U',
				'ǔ' => 'u',
				'Ǚ' => 'U',
				'ǚ' => 'u',
				// Grave accent.
				'Ǜ' => 'U',
				'ǜ' => 'u',
			);
	 
			// Used for locale-specific rules.
			$locale = get_locale();
	 
			if ( in_array( $locale, array( 'de_DE', 'de_DE_formal', 'de_CH', 'de_CH_informal', 'de_AT' ), true ) ) {
				$chars['Ä'] = 'Ae';
				$chars['ä'] = 'ae';
				$chars['Ö'] = 'Oe';
				$chars['ö'] = 'oe';
				$chars['Ü'] = 'Ue';
				$chars['ü'] = 'ue';
				$chars['ß'] = 'ss';
			} elseif ( 'da_DK' === $locale ) {
				$chars['Æ'] = 'Ae';
				$chars['æ'] = 'ae';
				$chars['Ø'] = 'Oe';
				$chars['ø'] = 'oe';
				$chars['Å'] = 'Aa';
				$chars['å'] = 'aa';
			} elseif ( 'ca' === $locale ) {
				$chars['l·l'] = 'll';
			} elseif ( 'sr_RS' === $locale || 'bs_BA' === $locale ) {
				$chars['Đ'] = 'DJ';
				$chars['đ'] = 'dj';
			}
	 
			$string = strtr( $string, $chars );
		} else {
			$chars = array();
			// Assume ISO-8859-1 if not UTF-8.
			$chars['in'] = "\x80\x83\x8a\x8e\x9a\x9e"
				. "\x9f\xa2\xa5\xb5\xc0\xc1\xc2"
				. "\xc3\xc4\xc5\xc7\xc8\xc9\xca"
				. "\xcb\xcc\xcd\xce\xcf\xd1\xd2"
				. "\xd3\xd4\xd5\xd6\xd8\xd9\xda"
				. "\xdb\xdc\xdd\xe0\xe1\xe2\xe3"
				. "\xe4\xe5\xe7\xe8\xe9\xea\xeb"
				. "\xec\xed\xee\xef\xf1\xf2\xf3"
				. "\xf4\xf5\xf6\xf8\xf9\xfa\xfb"
				. "\xfc\xfd\xff";
	 
			$chars['out'] = 'EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy';
	 
			$string              = strtr( $string, $chars['in'], $chars['out'] );
			$double_chars        = array();
			$double_chars['in']  = array( "\x8c", "\x9c", "\xc6", "\xd0", "\xde", "\xdf", "\xe6", "\xf0", "\xfe" );
			$double_chars['out'] = array( 'OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th' );
			$string              = str_replace( $double_chars['in'], $double_chars['out'], $string );
		}
	 
		return $string;
	}

	function sanitize_text_field( $str ) {
		$filtered = _sanitize_text_fields( $str, false );
	 
		/**
		 * Filters a sanitized text field string.
		 *
		 * @since 2.9.0
		 *
		 * @param string $filtered The sanitized string.
		 * @param string $str      The string prior to being sanitized.
		 */
		return apply_filters( 'sanitize_text_field', $filtered, $str );
	}

	function _sanitize_text_fields( $str, $keep_newlines = false ) {
		if ( is_object( $str ) || is_array( $str ) ) {
			return '';
		}
	 
		$str = (string) $str;
	 
		$filtered = wp_check_invalid_utf8( $str );
	 
		if ( strpos( $filtered, '<' ) !== false ) {
			$filtered = wp_pre_kses_less_than( $filtered );
			// This will strip extra whitespace for us.
			$filtered = wp_strip_all_tags( $filtered, false );
	 
			// Use HTML entities in a special case to make sure no later
			// newline stripping stage could lead to a functional tag.
			$filtered = str_replace( "<\n", "&lt;\n", $filtered );
		}
	 
		if ( ! $keep_newlines ) {
			$filtered = preg_replace( '/[\r\n\t ]+/', ' ', $filtered );
		}
		$filtered = trim( $filtered );
	 
		$found = false;
		while ( preg_match( '/%[a-f0-9]{2}/i', $filtered, $match ) ) {
			$filtered = str_replace( $match[0], '', $filtered );
			$found    = true;
		}
	 
		if ( $found ) {
			// Strip out the whitespace that may now exist after removing the octets.
			$filtered = trim( preg_replace( '/ +/', ' ', $filtered ) );
		}
	 
		return $filtered;
	}

	function wp_check_invalid_utf8( $string, $strip = false ) {
		$string = (string) $string;
	 
		if ( 0 === strlen( $string ) ) {
			return '';
		}
	 
		// Store the site charset as a static to avoid multiple calls to get_option().
		static $is_utf8 = null;
		if ( ! isset( $is_utf8 ) ) {
			$is_utf8 = in_array( get_option( 'blog_charset' ), array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' ), true );
		}
		if ( ! $is_utf8 ) {
			return $string;
		}
	 
		// Check for support for utf8 in the installed PCRE library once and store the result in a static.
		static $utf8_pcre = null;
		if ( ! isset( $utf8_pcre ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$utf8_pcre = @preg_match( '/^./u', 'a' );
		}
		// We can't demand utf8 in the PCRE installation, so just return the string in those cases.
		if ( ! $utf8_pcre ) {
			return $string;
		}
	 
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- preg_match fails when it encounters invalid UTF8 in $string.
		if ( 1 === @preg_match( '/^./us', $string ) ) {
			return $string;
		}
	 
		// Attempt to strip the bad chars if requested (not recommended).
		if ( $strip && function_exists( 'iconv' ) ) {
			return iconv( 'utf-8', 'utf-8', $string );
		}
	 
		return '';
	}

	function get_option( $option, $default = false ) {
		return $default;
	}

	function seems_utf8( $str ) {
		mbstring_binary_safe_encoding();
		$length = strlen( $str );
		reset_mbstring_encoding();
		for ( $i = 0; $i < $length; $i++ ) {
			$c = ord( $str[ $i ] );
			if ( $c < 0x80 ) {
				$n = 0; // 0bbbbbbb
			} elseif ( ( $c & 0xE0 ) == 0xC0 ) {
				$n = 1; // 110bbbbb
			} elseif ( ( $c & 0xF0 ) == 0xE0 ) {
				$n = 2; // 1110bbbb
			} elseif ( ( $c & 0xF8 ) == 0xF0 ) {
				$n = 3; // 11110bbb
			} elseif ( ( $c & 0xFC ) == 0xF8 ) {
				$n = 4; // 111110bb
			} elseif ( ( $c & 0xFE ) == 0xFC ) {
				$n = 5; // 1111110b
			} else {
				return false; // Does not match any model.
			}
			for ( $j = 0; $j < $n; $j++ ) { // n bytes matching 10bbbbbb follow ?
				if ( ( ++$i == $length ) || ( ( ord( $str[ $i ] ) & 0xC0 ) != 0x80 ) ) {
					return false;
				}
			}
		}
		return true;
	}

	function mbstring_binary_safe_encoding( $reset = false ) {
		static $encodings  = array();
		static $overloaded = null;
	 
		if ( is_null( $overloaded ) ) {
			$overloaded = function_exists( 'mb_internal_encoding' ) && ( ini_get( 'mbstring.func_overload' ) & 2 ); // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.mbstring_func_overloadDeprecated
		}
	 
		if ( false === $overloaded ) {
			return;
		}
	 
		if ( ! $reset ) {
			$encoding = mb_internal_encoding();
			array_push( $encodings, $encoding );
			mb_internal_encoding( 'ISO-8859-1' );
		}
	 
		if ( $reset && $encodings ) {
			$encoding = array_pop( $encodings );
			mb_internal_encoding( $encoding );
		}
	}

	function reset_mbstring_encoding() {
		mbstring_binary_safe_encoding( true );
	}

	function get_locale() {
		return "en_US";
	}