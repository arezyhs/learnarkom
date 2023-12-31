<?php

/**
 * The class handles the url replacements.
 *
 * @package    \Optml\Inc
 * @author     Optimole <friends@optimole.com>
 */
final class Optml_Url_Replacer extends Optml_App_Replacer {

	use Optml_Dam_Offload_Utils;
	use Optml_Validator;
	use Optml_Normalizer;

	/**
	 * Cached object instance.
	 *
	 * @var Optml_Url_Replacer
	 */
	protected static $instance = null;

	/**
	 * Class instance method.
	 *
	 * @codeCoverageIgnore
	 * @static
	 * @return Optml_Url_Replacer
	 * @since  1.0.0
	 * @access public
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			add_action( 'optml_replacer_setup', [ self::$instance, 'init' ] );
		}

		return self::$instance;
	}

	/**
	 * A filter which turns a local url into an optimized CDN image url or an array of image urls.
	 *
	 * @param string|array $url The url which should be replaced.
	 *
	 * @return string|array Replaced url.
	 */
	public function replace_option_url( $url ) {
		if ( empty( $url ) ) {
			return $url;
		}

		// $url might be an array or an json encoded array with urls.
		if ( is_array( $url ) || filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
			$array   = $url;
			$encoded = false;

			// it might a json encoded array
			if ( is_string( $url ) ) {
				$array   = json_decode( $url, true );
				$encoded = true;
			}

			// in case there is an array, apply it recursively.
			if ( is_array( $array ) ) {
				foreach ( $array as $index => $value ) {
					$array[ $index ] = $this->replace_option_url( $value );
				}

				if ( $encoded ) {
					return json_encode( $array );
				}

				return $array;
			}

			if ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
				return $url;
			}
		}

		return apply_filters( 'optml_content_url', $url );
	}

	/**
	 * The initialize method.
	 */
	public function init() {

		add_filter( 'optml_replace_image', [ $this, 'build_url' ], 10, 2 );
		parent::init();

		Optml_Quality::$default_quality = $this->to_accepted_quality( $this->settings->get_quality() );
		Optml_Image::$watermark         = new Optml_Watermark( $this->settings->get_site_settings()['watermark'] );
		Optml_Resize::$default_enlarge  = apply_filters( 'optml_always_enlarge', false );
		add_filter( 'optml_content_url', [ $this, 'build_url' ], 1, 2 );

	}

	/**
	 * Returns a signed image url authorized to be used in our CDN.
	 *
	 * @param string $url The url which should be signed.
	 * @param array  $args Dimension params; Supports `width` and `height`.
	 *
	 * @return string
	 */
	public function build_url(
		$url, $args = [
			'width'  => 'auto',
			'height' => 'auto',
		]
	) {
		if ( apply_filters( 'optml_dont_replace_url', false, $url ) ) {
			return $url;
		}

		$original_url = $url;

		$is_slashed = strpos( $url, '\/' ) !== false;

		// We do a little hack here, for json unicode chars we first replace them with html special chars,
		// we then strip slashes to normalize the URL and last we convert html special chars back to get a clean URL
		$url = $is_slashed ? html_entity_decode( stripslashes( preg_replace( '/\\\u([\da-fA-F]{4})/', '&#x\1;', $url ) ) ) : ( $url );
		$is_uploaded = Optml_Media_Offload::is_not_processed_image( $url );
		if ( $is_uploaded === true ) {
			$attachment_id = [];
			preg_match( '/\/' . Optml_Media_Offload::KEYS['not_processed_flag'] . '([^\/]*)\//', $url, $attachment_id );
			if ( ! isset( $attachment_id[1] ) ) {
				return $url;
			}
			$id_and_filename = [];
			preg_match( '/\/(' . Optml_Media_Offload::KEYS['uploaded_flag'] . '.*)/', $url, $id_and_filename );
			if ( isset( $id_and_filename[0] ) ) {
				$id_and_filename = $id_and_filename[0];
			}
			$sizes = $this->parse_dimension_from_optimized_url( $url );
			if ( isset( $sizes[0] ) && $sizes[0] !== false ) {
				$args['width'] = $sizes[0];
			}
			if ( isset( $sizes[1] ) && $sizes[1] !== false ) {
				$args['height'] = $sizes[1];
			}
			$unoptimized_url = false;
			if ( $attachment_id[1] === 'media_cloud' ) {
				$tmp_url = explode( '/', $id_and_filename, 3 );
				if ( isset( $tmp_url[2] ) ) {
					$unoptimized_url = $tmp_url[2];
				}
			} else {
				$unoptimized_url = Optml_Media_Offload::get_original_url( (int) $attachment_id[1] );
			}
			if ( $unoptimized_url !== false ) {
				$url = $unoptimized_url;
			}
		}
		if ( strpos( $url, Optml_Config::$service_url ) !== false && ! $this->url_has_dam_flag( $url ) ) {
			return $original_url;
		}

		if ( ! $this->can_replace_url( $url ) ) {
			return $original_url;
		}

		// Remove any query strings that might affect conversion.
		$url = strtok( $url, '?' );
		$ext = $this->is_valid_mimetype_from_url( $url, self::$filters[ Optml_Settings::FILTER_TYPE_OPTIMIZE ][ Optml_Settings::FILTER_EXT ] );
		if ( false === $ext ) {
			return $original_url;
		}

		if ( isset( $args['quality'] ) && ! empty( $args['quality'] ) ) {
			$args['quality'] = $this->to_accepted_quality( $args['quality'] );
		}

		if ( isset( $args['minify'] ) && ! empty( $args['minify'] ) ) {
			$args['minify'] = $this->to_accepted_minify( $args['minify'] );
		}

		// this will authorize the image
		if ( ! empty( $this->site_mappings ) ) {
			$url = str_replace( array_keys( $this->site_mappings ), array_values( $this->site_mappings ), $url );
		}
		if ( substr( $url, 0, 2 ) === '//' ) {
			$url = sprintf( '%s:%s', is_ssl() ? 'https' : 'http', $url );
		}
		$normalized_ext = strtolower( $ext );
		if ( isset( Optml_Config::$image_extensions[ $normalized_ext ] ) ) {
			$new_url = $this->normalize_image( $url, $original_url, $args, $is_uploaded, $normalized_ext );
			if ( $is_uploaded ) {
				$new_url = str_replace( '/' . $url, $id_and_filename, $new_url );
			}
		} else {
			$new_url = ( new Optml_Asset( $url, $args, $this->active_cache_buster_assets, $this->is_css_minify_on, $this->is_js_minify_on ) )->get_url();
		}
		return $is_slashed ? addcslashes( $new_url, '/' ) : $new_url;
	}

	/**
	 * Apply extra normalization to image.
	 *
	 * @param string $url Image url.
	 * @param string $original_url Original image url.
	 * @param array  $args Args.
	 *
	 * @return string
	 */
	private function normalize_image( $url, $original_url, $args, $is_uploaded = false, $ext = '' ) {

		$new_url = $this->strip_image_size_from_url( $url );

		if ( $new_url !== $url || $is_uploaded === true ) {
			if ( ! isset( $args['quality'] ) || $args['quality'] !== 'eco' ) {
				if ( $is_uploaded === true ) {
					$crop = false;
				}
				if ( $is_uploaded !== true ) {
					list($args['width'], $args['height'], $crop) = $this->parse_dimensions_from_filename( $url );
				}
				if ( ! $crop ) {
					$sizes2crop = self::size_to_crop();
					$crop       = isset( $sizes2crop[ $args['width'] . $args['height'] ] ) ? $sizes2crop[ $args['width'] . $args['height'] ] : false;
				}
				if ( $crop ) {
					$args['resize'] = $this->to_optml_crop( $crop );
				}
			}
			$url = $new_url;
		}

		if ( ! isset( $args['width'] ) ) {
			$args['width'] = $this->limit_dimensions_enabled ? $this->limit_width : 'auto';
		}
		if ( ! isset( $args['height'] ) ) {
			$args['height'] = $this->limit_dimensions_enabled ? $this->limit_height : 'auto';
		}

		$args['width']  = (int) $args['width'];
		$args['height'] = (int) $args['height'];

		if ( $this->limit_dimensions_enabled ) {
			if ( $args['width'] > $this->limit_width || $args['height'] > $this->limit_height ) {
				$scale = min( $this->limit_width / $args['width'], $this->limit_height / $args['height'] );

				if ( $scale < 1 ) {
					$args['width']  = (int) floor( $scale * $args['width'] );
					$args['height'] = (int) floor( $scale * $args['height'] );
				}
			}

			if ( isset( $args['resize'], $args['resize']['enlarge'] ) ) {
				$args['resize']['enlarge'] = false;
			}
		}

		if ( isset( $args['resize'], $args['resize']['gravity'] ) && $this->settings->is_smart_cropping() ) {
			$args['resize']['gravity'] = Optml_Resize::GRAVITY_SMART;
		}

		$args = apply_filters( 'optml_image_args', $args, $original_url );

		$arguments = [
			'apply_watermark' => apply_filters( 'optml_apply_watermark_for', true, $url ),
		];

		if ( isset( $args['format'] ) && ! empty( $args['format'] ) ) {
			$arguments['format'] = $args['format'];
		}

		// If format is not already set, we use best format if it's enabled.
		if ( ! isset( $arguments['format'] ) && $this->settings->is_best_format() ) {
			$arguments['format'] = 'best';
		}

		if ( $this->settings->get( 'strip_metadata' ) === 'disabled' ) {
			$arguments['strip_metadata'] = '0';
		}

		if ( ! apply_filters( 'optml_should_avif_ext', true, $ext, $original_url ) ) {
			$arguments['ignore_avif'] = true;
		}

		if ( ! isset( $arguments['ignore_avif'] ) && $this->settings->get( 'avif' ) === 'disabled' ) {
			$arguments['ignore_avif'] = true;
		}

		return  ( new Optml_Image( $url, $args, $this->active_cache_buster ) )->get_url( $arguments );

	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @codeCoverageIgnore
	 * @access public
	 * @return void
	 * @since  1.0.0
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'optimole-wp' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @codeCoverageIgnore
	 * @access public
	 * @return void
	 * @since  1.0.0
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'optimole-wp' ), '1.0.0' );
	}
}
