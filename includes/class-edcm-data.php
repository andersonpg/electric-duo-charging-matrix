<?php
/**
 * Data loading, validation and normalisation.
 *
 * @package ElectricDuoChargingMatrix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the bundled dataset (or the site owner's custom dataset) and
 * normalises it into a predictable structure.
 */
class EDCM_Data {

	/** Option holding a custom JSON dataset saved from the admin screen. */
	const OPTION = 'edcm_custom_data';

	/** Status codes the plugin understands (icons and colours are tied to these). */
	const CODES = array( 'PC', 'BOTH', 'APP', 'ROAM', 'NO' );

	/** @var array|null */
	private static $cache = null;

	/**
	 * Absolute path to the bundled dataset.
	 *
	 * @return string
	 */
	public static function default_file() {
		return EDCM_DIR . 'data/matrix-data.json';
	}

	/**
	 * Load and validate the bundled dataset.
	 *
	 * @return array|null Normalised data, or null if the file is unreadable.
	 */
	public static function load_default() {
		$file = self::default_file();
		if ( ! is_readable( $file ) ) {
			return null;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin file.
		$raw  = file_get_contents( $file );
		$data = json_decode( (string) $raw, true );
		$ok   = self::validate( $data );
		return is_wp_error( $ok ) ? null : $ok;
	}

	/**
	 * Raw JSON of the bundled dataset (for the admin editor).
	 *
	 * @return string
	 */
	public static function default_json() {
		$file = self::default_file();
		if ( ! is_readable( $file ) ) {
			return '';
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin file.
		return (string) file_get_contents( $file );
	}

	/**
	 * Whether the site is using a custom dataset.
	 *
	 * @return bool
	 */
	public static function has_custom() {
		$custom = get_option( self::OPTION, '' );
		return is_string( $custom ) && '' !== $custom;
	}

	/**
	 * Active dataset (custom if valid, otherwise bundled).
	 *
	 * @return array
	 */
	public static function get() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$data   = null;
		$custom = get_option( self::OPTION, '' );
		if ( is_string( $custom ) && '' !== $custom ) {
			$decoded = json_decode( $custom, true );
			$valid   = is_array( $decoded ) ? self::validate( $decoded ) : null;
			if ( $valid && ! is_wp_error( $valid ) ) {
				$data = $valid;
			}
		}
		if ( ! $data ) {
			$data = self::load_default();
		}
		if ( ! $data ) {
			$data = array(
				'meta'     => array(
					'version'       => '',
					'last_reviewed' => '',
					'region'        => 'US',
					'title'         => __( 'EV Charging Compatibility Matrix', 'ed-charging-matrix' ),
					'description'   => '',
					'tesla_note'    => '',
				),
				'statuses' => array(),
				'networks' => array(),
				'sheets'   => array(),
				'faq'      => array(),
			);
		}

		/**
		 * Filter the normalised matrix dataset.
		 *
		 * @param array $data Dataset.
		 */
		self::$cache = apply_filters( 'edcm_data', $data );
		return self::$cache;
	}

	/**
	 * Clear the in-request cache (after saving custom data).
	 */
	public static function flush() {
		self::$cache = null;
	}

	/**
	 * Sanitise a plain-text value.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $max   Max characters.
	 * @return string
	 */
	private static function str( $value, $max = 600 ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$value = sanitize_text_field( (string) $value );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $value, 0, $max );
		}
		return substr( $value, 0, $max );
	}

	/**
	 * Sanitise a slug-like id.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function slug( $value ) {
		$value = is_scalar( $value ) ? strtolower( (string) $value ) : '';
		return preg_replace( '/[^a-z0-9_-]/', '', $value );
	}

	/**
	 * Sanitise a URL (http/https only).
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function url( $value ) {
		if ( ! is_scalar( $value ) || '' === (string) $value ) {
			return '';
		}
		return esc_url_raw( (string) $value, array( 'http', 'https' ) );
	}

	/**
	 * Validate and normalise a decoded dataset.
	 *
	 * @param mixed $data Decoded JSON.
	 * @return array|WP_Error
	 */
	public static function validate( $data ) {
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'edcm_invalid', __( 'The data must be a JSON object.', 'ed-charging-matrix' ) );
		}
		foreach ( array( 'statuses', 'networks', 'sheets' ) as $key ) {
			if ( empty( $data[ $key ] ) || ! is_array( $data[ $key ] ) ) {
				/* translators: %s: JSON key name. */
				return new WP_Error( 'edcm_missing', sprintf( __( 'Missing or empty "%s" section.', 'ed-charging-matrix' ), $key ) );
			}
		}

		$out  = array();
		$meta = isset( $data['meta'] ) && is_array( $data['meta'] ) ? $data['meta'] : array();

		$last = isset( $meta['last_reviewed'] ) ? self::str( $meta['last_reviewed'], 10 ) : '';
		if ( '' !== $last && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $last ) ) {
			return new WP_Error( 'edcm_date', __( 'meta.last_reviewed must look like 2026-10-26.', 'ed-charging-matrix' ) );
		}
		$out['meta'] = array(
			'version'       => self::str( isset( $meta['version'] ) ? $meta['version'] : '', 20 ),
			'last_reviewed' => $last,
			'region'        => self::str( isset( $meta['region'] ) ? $meta['region'] : 'US', 40 ),
			'title'         => self::str( isset( $meta['title'] ) ? $meta['title'] : '', 160 ),
			'description'   => self::str( isset( $meta['description'] ) ? $meta['description'] : '', 400 ),
			'tesla_note'    => self::str( isset( $meta['tesla_note'] ) ? $meta['tesla_note'] : '', 600 ),
		);

		/* Statuses. */
		$out['statuses'] = array();
		foreach ( self::CODES as $code ) {
			$src                      = isset( $data['statuses'][ $code ] ) && is_array( $data['statuses'][ $code ] ) ? $data['statuses'][ $code ] : array();
			$out['statuses'][ $code ] = array(
				'label' => self::str( isset( $src['label'] ) ? $src['label'] : $code, 60 ),
				'short' => self::str( isset( $src['short'] ) ? $src['short'] : $code, 20 ),
				'desc'  => self::str( isset( $src['desc'] ) ? $src['desc'] : '', 400 ),
			);
			if ( '' === $out['statuses'][ $code ]['label'] ) {
				$out['statuses'][ $code ]['label'] = $code;
			}
		}

		/* Networks. */
		$out['networks'] = array();
		$seen            = array();
		foreach ( $data['networks'] as $net ) {
			if ( ! is_array( $net ) ) {
				continue;
			}
			$id = self::slug( isset( $net['id'] ) ? $net['id'] : '' );
			if ( '' === $id ) {
				return new WP_Error( 'edcm_net_id', __( 'Every network needs an "id" (letters, numbers, dashes).', 'ed-charging-matrix' ) );
			}
			if ( isset( $seen[ $id ] ) ) {
				/* translators: %s: network id. */
				return new WP_Error( 'edcm_net_dupe', sprintf( __( 'Duplicate network id "%s".', 'ed-charging-matrix' ), $id ) );
			}
			$seen[ $id ] = true;
			$name        = self::str( isset( $net['name'] ) ? $net['name'] : $id, 80 );
			$sources     = array();
			if ( isset( $net['sources'] ) && is_array( $net['sources'] ) ) {
				foreach ( $net['sources'] as $s ) {
					if ( is_array( $s ) && ! empty( $s['url'] ) ) {
						$u = self::url( $s['url'] );
						if ( '' !== $u ) {
							$sources[] = array(
								'label' => self::str( isset( $s['label'] ) ? $s['label'] : $u, 200 ),
								'url'   => $u,
							);
						}
					}
				}
			}
			$out['networks'][] = array(
				'id'      => $id,
				'name'    => $name,
				'short'   => self::str( isset( $net['short'] ) ? $net['short'] : $name, 40 ),
				'type'    => self::str( isset( $net['type'] ) ? $net['type'] : '', 80 ),
				'url'     => self::url( isset( $net['url'] ) ? $net['url'] : '' ),
				'about'   => self::str( isset( $net['about'] ) ? $net['about'] : '', 900 ),
				'sources' => $sources,
			);
		}
		if ( empty( $out['networks'] ) ) {
			return new WP_Error( 'edcm_no_networks', __( 'Add at least one network.', 'ed-charging-matrix' ) );
		}

		/* Sheets. */
		$out['sheets'] = array();
		foreach ( $data['sheets'] as $skey => $sheet ) {
			$skey = self::slug( $skey );
			if ( '' === $skey || ! is_array( $sheet ) || empty( $sheet['rows'] ) || ! is_array( $sheet['rows'] ) ) {
				continue;
			}
			$cats = array();
			if ( isset( $sheet['categories'] ) && is_array( $sheet['categories'] ) ) {
				foreach ( $sheet['categories'] as $ck => $cl ) {
					$cats[ self::str( $ck, 60 ) ] = self::str( $cl, 60 );
				}
			}
			$rows     = array();
			$row_seen = array();
			foreach ( $sheet['rows'] as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$rid = self::slug( isset( $row['id'] ) ? $row['id'] : '' );
				if ( '' === $rid || isset( $row_seen[ $rid ] ) ) {
					return new WP_Error( 'edcm_row_id', sprintf(
						/* translators: %s: sheet key. */
						__( 'Sheet "%s" has a row with a missing or duplicate id.', 'ed-charging-matrix' ),
						$skey
					) );
				}
				$row_seen[ $rid ] = true;

				$status = array();
				foreach ( $out['networks'] as $net ) {
					$raw  = isset( $row['status'][ $net['id'] ] ) ? $row['status'][ $net['id'] ] : null;
					$code = 'NO';
					$text = '';
					if ( is_array( $raw ) ) {
						if ( isset( $raw['code'] ) ) {
							$code = $raw['code'];
							$text = isset( $raw['text'] ) ? $raw['text'] : '';
						} else {
							$code = isset( $raw[0] ) ? $raw[0] : 'NO';
							$text = isset( $raw[1] ) ? $raw[1] : '';
						}
					}
					$code = is_scalar( $code ) ? strtoupper( (string) $code ) : 'NO';
					if ( ! in_array( $code, self::CODES, true ) ) {
						$code = 'NO';
					}
					$status[ $net['id'] ] = array(
						'code' => $code,
						'text' => self::str( $text, 500 ),
					);
				}

				$category = self::str( isset( $row['category'] ) ? $row['category'] : '', 60 );
				$rows[]   = array(
					'id'       => $rid,
					'name'     => self::str( isset( $row['name'] ) ? $row['name'] : $rid, 120 ),
					'owner'    => self::str( isset( $row['owner'] ) ? $row['owner'] : '', 120 ),
					'category' => $category,
					'notes'    => self::str( isset( $row['notes'] ) ? $row['notes'] : '', 600 ),
					'system'   => self::str( isset( $row['system'] ) ? $row['system'] : '', 200 ),
					'oem_app'  => self::str( isset( $row['oem_app'] ) ? $row['oem_app'] : '', 200 ),
					'status'   => $status,
				);
				if ( '' !== $category && ! isset( $cats[ $category ] ) ) {
					$cats[ $category ] = $category;
				}
			}
			if ( empty( $rows ) ) {
				continue;
			}
			$out['sheets'][ $skey ] = array(
				'title'           => self::str( isset( $sheet['title'] ) ? $sheet['title'] : $skey, 60 ),
				'heading'         => self::str( isset( $sheet['heading'] ) ? $sheet['heading'] : '', 160 ),
				'entity_label'    => self::str( isset( $sheet['entity_label'] ) ? $sheet['entity_label'] : 'Name', 40 ),
				'entity_singular' => self::str( isset( $sheet['entity_singular'] ) ? $sheet['entity_singular'] : 'item', 30 ),
				'entity_plural'   => self::str( isset( $sheet['entity_plural'] ) ? $sheet['entity_plural'] : 'items', 30 ),
				'categories'      => $cats,
				'rows'            => $rows,
			);
		}
		if ( empty( $out['sheets'] ) ) {
			return new WP_Error( 'edcm_no_sheets', __( 'Add at least one sheet with rows.', 'ed-charging-matrix' ) );
		}

		/* Optional extra FAQ entries. */
		$out['faq'] = array();
		if ( isset( $data['faq'] ) && is_array( $data['faq'] ) ) {
			foreach ( $data['faq'] as $item ) {
				if ( is_array( $item ) && ! empty( $item['q'] ) && ! empty( $item['a'] ) ) {
					$out['faq'][] = array(
						'q' => self::str( $item['q'], 200 ),
						'a' => self::str( $item['a'], 900 ),
					);
				}
			}
		}

		return $out;
	}
}
