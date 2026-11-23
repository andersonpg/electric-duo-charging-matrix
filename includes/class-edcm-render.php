<?php
/**
 * Front-end rendering: shortcode, assets, markup and structured data.
 *
 * The full matrix is rendered server-side as semantic HTML so search engines
 * (and visitors without JavaScript) get all the content. JavaScript only
 * enhances it (tabs, filters, detail dialogs, CSV export, full screen).
 *
 * @package ElectricDuoChargingMatrix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renderer.
 */
class EDCM_Render {

	/** @var int Instance counter (supports several matrices on one page). */
	private static $instance = 0;

	/** @var bool */
	private static $sprite_done = false;

	/** @var bool */
	private static $schema_done = false;

	/**
	 * Hook everything up.
	 */
	public static function init() {
		add_shortcode( 'ev_charging_matrix', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue' ) );
	}

	/**
	 * Plugin settings merged with defaults.
	 *
	 * @return array
	 */
	public static function settings() {
		$defaults = array(
			'intro'        => '',
			'load_fonts'   => 0,
			'show_faq'     => 1,
			'schema'       => 1,
			// 0 = the table grows with the page and its header row stays pinned to the top of the screen.
			// A number (320 to 1600) = scroll inside a box of that height instead.
			'table_height' => 0,
			// Pixels of fixed site header to sit below when pinning. Empty = detect automatically.
			'sticky_top'   => '',
			'organization' => '',
		);
		$saved = get_option( 'edcm_settings', array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
	}

	/**
	 * Default intro paragraph.
	 *
	 * @return string
	 */
	public static function default_intro() {
		return __( 'Find out which US charging networks your car or charging app can use, and how: Plug & Charge, from the car\'s screen, in the automaker\'s app, or through roaming. Click any icon for the details.', 'ed-charging-matrix' );
	}

	/* ------------------------------------------------------------------ */
	/* Assets                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * Register styles and scripts.
	 */
	public static function register_assets() {
		$settings = self::settings();
		$deps     = array();
		if ( ! empty( $settings['load_fonts'] ) ) {
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google Fonts URL is versioned by the CSS API.
			wp_register_style( 'edcm-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Oswald:wght@500;700&family=Poppins:wght@500;600&display=swap', array(), null );
			$deps[] = 'edcm-fonts';
		}
		$css_ver = file_exists( EDCM_DIR . 'assets/css/edcm.css' ) ? filemtime( EDCM_DIR . 'assets/css/edcm.css' ) : EDCM_VERSION;
		$js_ver  = file_exists( EDCM_DIR . 'assets/js/edcm.js' ) ? filemtime( EDCM_DIR . 'assets/js/edcm.js' ) : EDCM_VERSION;
		wp_register_style( 'edcm', EDCM_URL . 'assets/css/edcm.css', $deps, $css_ver );
		wp_register_script(
			'edcm',
			EDCM_URL . 'assets/js/edcm.js',
			array(),
			$js_ver,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Enqueue early (in <head>) when the current post contains the shortcode.
	 */
	public static function maybe_enqueue() {
		self::register_assets();
		if ( is_singular() ) {
			$post = get_post();
			if ( $post && has_shortcode( (string) $post->post_content, 'ev_charging_matrix' ) ) {
				self::enqueue();
			}
		}
	}

	/**
	 * Enqueue assets.
	 */
	public static function enqueue() {
		if ( ! wp_style_is( 'edcm', 'registered' ) ) {
			self::register_assets();
		}
		wp_enqueue_style( 'edcm' );
		wp_enqueue_script( 'edcm' );
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Parse a loose boolean shortcode attribute.
	 *
	 * @param string $value   Attribute value.
	 * @param bool   $default Default when empty/invalid.
	 * @return bool
	 */
	private static function bool_att( $value, $default ) {
		if ( '' === $value || null === $value ) {
			return (bool) $default;
		}
		$parsed = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
		return null === $parsed ? (bool) $default : $parsed;
	}

	/**
	 * Human list: "A, B and C".
	 *
	 * @param array $items Strings.
	 * @return string
	 */
	private static function join_list( $items ) {
		$items = array_values( $items );
		$n     = count( $items );
		if ( 0 === $n ) {
			return '';
		}
		if ( 1 === $n ) {
			return $items[0];
		}
		$last = array_pop( $items );
		/* translators: 1: comma separated list, 2: last item. */
		return sprintf( __( '%1$s and %2$s', 'ed-charging-matrix' ), implode( ', ', $items ), $last );
	}

	/**
	 * Icon symbol id for a status code.
	 *
	 * @param string $code Status code.
	 * @return string
	 */
	private static function icon_id( $code ) {
		$map = array(
			'PC'   => 'edcm-i-pc',
			'BOTH' => 'edcm-i-car',
			'APP'  => 'edcm-i-app',
			'ROAM' => 'edcm-i-roam',
			'NO'   => 'edcm-i-no',
		);
		return isset( $map[ $code ] ) ? $map[ $code ] : 'edcm-i-app';
	}

	/**
	 * Inline <svg><use/></svg> for an icon.
	 *
	 * @param string $id    Symbol id.
	 * @param string $class Extra class.
	 * @return string
	 */
	private static function icon( $id, $class = '' ) {
		return '<svg class="edcm-i' . ( '' !== $class ? ' ' . esc_attr( $class ) : '' ) . '" aria-hidden="true" width="20" height="20"><use href="#' . esc_attr( $id ) . '"/></svg>';
	}

	/**
	 * The shared SVG sprite (printed once per page).
	 *
	 * @return string
	 */
	private static function sprite() {
		if ( self::$sprite_done ) {
			return '';
		}
		self::$sprite_done = true;
		$s                 = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
		return '<svg class="edcm-sprite" xmlns="http://www.w3.org/2000/svg" width="0" height="0" aria-hidden="true" focusable="false" style="position:absolute;width:0;height:0;overflow:hidden"><defs>'
			. '<symbol id="edcm-i-pc" viewBox="0 0 24 24"><path fill="currentColor" d="M13.3 2 5 13.4h5.4L9.6 22 19 10.2h-5.5z"/></symbol>'
			. '<symbol id="edcm-i-app" viewBox="0 0 24 24"><rect x="7" y="2.5" width="10" height="19" rx="2.4" ' . $s . '/><path d="M10.5 18.2h3" ' . $s . '/></symbol>'
			. '<symbol id="edcm-i-car" viewBox="0 0 24 24"><path d="M5 16.5V13l1.7-4.3A2 2 0 0 1 8.6 7.5h6.8a2 2 0 0 1 1.9 1.2L19 13v3.5M3.5 13h17" ' . $s . '/><circle cx="7.6" cy="16.6" r="1.7" ' . $s . '/><circle cx="16.4" cy="16.6" r="1.7" ' . $s . '/></symbol>'
			. '<symbol id="edcm-i-roam" viewBox="0 0 24 24"><rect x="3" y="5.5" width="18" height="13" rx="2.4" ' . $s . '/><path d="M3 10h18M6.5 14.6h4" ' . $s . '/></symbol>'
			. '<symbol id="edcm-i-no" viewBox="0 0 24 24"><path d="M8 8l8 8M16 8l-8 8" ' . $s . '/></symbol>'
			. '<symbol id="edcm-i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5" ' . $s . '/><path d="m20 20-4.2-4.2" ' . $s . '/></symbol>'
			. '<symbol id="edcm-i-download" viewBox="0 0 24 24"><path d="M12 4v11m0 0-4.5-4.5M12 15l4.5-4.5M5 19.5h14" ' . $s . '/></symbol>'
			. '<symbol id="edcm-i-expand" viewBox="0 0 24 24"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" ' . $s . '/></symbol>'
			. '<symbol id="edcm-i-collapse" viewBox="0 0 24 24"><path d="M9 4v5H4M15 4v5h5M9 20v-5H4M15 20v-5h5" ' . $s . '/></symbol>'
			. '<symbol id="edcm-i-columns" viewBox="0 0 24 24"><rect x="3.5" y="4.5" width="17" height="15" rx="2" ' . $s . '/><path d="M9.2 4.5v15M14.8 4.5v15" ' . $s . '/></symbol>'
			. '<symbol id="edcm-i-left" viewBox="0 0 24 24"><path d="m14.5 6-6 6 6 6" ' . $s . '/></symbol>'
			. '<symbol id="edcm-i-right" viewBox="0 0 24 24"><path d="m9.5 6 6 6-6 6" ' . $s . '/></symbol>'
			. '<symbol id="edcm-i-close" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" ' . $s . '/></symbol>'
			. '<symbol id="edcm-i-link" viewBox="0 0 24 24"><path d="M14 5h5v5M19 5l-8 8M18 14v4a1.5 1.5 0 0 1-1.5 1.5h-10A1.5 1.5 0 0 1 5 18V8a1.5 1.5 0 0 1 1.5-1.5H10" ' . $s . '/></symbol>'
			. '</defs></svg>';
	}

	/**
	 * Pill markup for a status.
	 *
	 * @param string $code     Status code.
	 * @param string $label    Visible label (visually hidden in the compact table).
	 * @param string $modifier Extra class.
	 * @return string
	 */
	private static function pill( $code, $label, $modifier = '' ) {
		return '<span class="edcm__pill' . ( '' !== $modifier ? ' ' . esc_attr( $modifier ) : '' ) . '" data-code="' . esc_attr( $code ) . '">'
			. self::icon( self::icon_id( $code ) )
			. '<span class="edcm__lbl">' . esc_html( $label ) . '</span></span>';
	}

	/**
	 * Date formatted for display.
	 *
	 * @param string $ymd Y-m-d.
	 * @return string
	 */
	private static function pretty_date( $ymd ) {
		if ( '' === $ymd ) {
			return '';
		}
		$ts = strtotime( $ymd . ' 12:00:00 UTC' );
		return $ts ? date_i18n( get_option( 'date_format' ), $ts ) : $ymd;
	}

	/**
	 * Count integrated (non-"NO") networks for a row.
	 *
	 * @param array $row Row.
	 * @return int
	 */
	private static function integrated_count( $row ) {
		$n = 0;
		foreach ( $row['status'] as $s ) {
			if ( 'NO' !== $s['code'] ) {
				++$n;
			}
		}
		return $n;
	}

	/**
	 * Find a network by id.
	 *
	 * @param array  $data Dataset.
	 * @param string $id   Network id.
	 * @return array|null
	 */
	private static function find_network( $data, $id ) {
		foreach ( $data['networks'] as $net ) {
			if ( $net['id'] === $id ) {
				return $net;
			}
		}
		return null;
	}

	/* ------------------------------------------------------------------ */
	/* Shortcode                                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * [ev_charging_matrix] handler.
	 *
	 * Attributes: view (both|apps|brands), default (apps|brands), title,
	 * heading_level (2-4), intro (1/0), faq (1/0), schema (1/0), height (px,
	 * scroll inside a box of this height instead of pinning the header to the
	 * screen), sticky_top (px of fixed site header to sit below; default auto),
	 * align (wide|full; adds the block-theme alignment class).
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'view'          => 'both',
				'default'       => '',
				'title'         => '',
				'heading_level' => '2',
				'intro'         => '',
				'faq'           => '',
				'schema'        => '',
				'height'        => '',
				'sticky_top'    => '',
				'align'         => '',
			),
			is_array( $atts ) ? $atts : array(),
			'ev_charging_matrix'
		);

		$data = EDCM_Data::get();
		if ( empty( $data['networks'] ) || empty( $data['sheets'] ) ) {
			return current_user_can( 'manage_options' )
				? '<p>' . esc_html__( 'EV Charging Matrix: no data available. Check the plugin settings.', 'ed-charging-matrix' ) . '</p>'
				: '';
		}

		self::enqueue();
		++self::$instance;
		$iid      = self::$instance;
		$settings = self::settings();

		/* Which sheets to show. */
		$all_keys = array_keys( $data['sheets'] );
		$keys     = $all_keys;
		if ( 'both' !== strtolower( (string) $atts['view'] ) ) {
			$wanted = array_filter( array_map( 'trim', explode( ',', strtolower( (string) $atts['view'] ) ) ) );
			$keys   = array_values( array_intersect( $all_keys, $wanted ) );
			if ( empty( $keys ) ) {
				$keys = $all_keys;
			}
		}
		$initial = in_array( strtolower( (string) $atts['default'] ), $keys, true ) ? strtolower( (string) $atts['default'] ) : $keys[0];

		$level     = max( 2, min( 4, (int) $atts['heading_level'] ) );
		$h_main    = 'h' . $level;
		$h_section = 'h' . min( 6, $level + 1 );
		$h_item    = 'h' . min( 6, $level + 2 );

		$show_intro = self::bool_att( $atts['intro'], true );
		$show_faq   = self::bool_att( $atts['faq'], ! empty( $settings['show_faq'] ) );
		$do_schema  = self::bool_att( $atts['schema'], ! empty( $settings['schema'] ) );
		$height     = (int) ( '' !== $atts['height'] ? $atts['height'] : $settings['table_height'] );
		$box        = $height > 0;
		$height     = max( 320, min( 1600, $height ) );
		$stick_raw  = '' !== trim( (string) $atts['sticky_top'] ) ? $atts['sticky_top'] : $settings['sticky_top'];
		$sticky_top = is_numeric( $stick_raw ) ? (string) max( 0, min( 400, (int) $stick_raw ) ) : '';

		$align_class = '';
		if ( in_array( strtolower( (string) $atts['align'] ), array( 'wide', 'full' ), true ) ) {
			$align_class = ' align' . strtolower( (string) $atts['align'] );
		}

		$title = '' !== $atts['title'] ? $atts['title'] : ( '' !== $data['meta']['title'] ? $data['meta']['title'] : __( 'EV Charging Compatibility Matrix', 'ed-charging-matrix' ) );
		$intro = '' !== trim( (string) $settings['intro'] ) ? $settings['intro'] : self::default_intro();

		$net_count  = count( $data['networks'] );
		$count_bits = array(
			/* translators: %d: number of networks. */
			sprintf( _n( '%d network', '%d networks', $net_count, 'ed-charging-matrix' ), $net_count ),
		);
		foreach ( $keys as $k ) {
			$sheet        = $data['sheets'][ $k ];
			$count_bits[] = number_format_i18n( count( $sheet['rows'] ) ) . ' ' . strtolower( $sheet['entity_plural'] );
		}

		$i18n = array(
			'showing'       => __( 'Showing {shown} of {total} {plural}', 'ed-charging-matrix' ),
			'noMatches'     => __( 'No matches. Try clearing a filter.', 'ed-charging-matrix' ),
			'integrated'    => __( 'Integrated with {n} of {total} networks', 'ed-charging-matrix' ),
			'close'         => __( 'Close', 'ed-charging-matrix' ),
			'previous'      => __( 'Previous', 'ed-charging-matrix' ),
			'next'          => __( 'Next', 'ed-charging-matrix' ),
			'whoWorksWith'  => __( 'Works with {name}', 'ed-charging-matrix' ),
			'allFor'        => __( 'All networks for {name}', 'ed-charging-matrix' ),
			'aboutNet'      => __( 'About {name}', 'ed-charging-matrix' ),
			'system'        => __( 'Charging system', 'ed-charging-matrix' ),
			'oemApp'        => __( 'Automaker app', 'ed-charging-matrix' ),
			'notes'         => __( 'Notes', 'ed-charging-matrix' ),
			'nobody'        => __( 'No direct integrations listed.', 'ed-charging-matrix' ),
			'officialSite'  => __( 'Official site', 'ed-charging-matrix' ),
			'sources'       => __( 'Sources', 'ed-charging-matrix' ),
			'fullscreen'    => __( 'Full screen', 'ed-charging-matrix' ),
			'exitFullscreen' => __( 'Exit full screen', 'ed-charging-matrix' ),
			'notIntegrated' => $data['statuses']['NO']['label'],
			'columns'       => __( 'Networks', 'ed-charging-matrix' ),
			'csvName'       => 'ev-charging-matrix',
		);

		$faq_items = $show_faq ? self::faq_items( $data ) : array();

		ob_start();
		echo self::sprite(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static, escaped internally.
		?>
<div class="edcm<?php echo esc_attr( $align_class . ( $box ? ' edcm--box' : '' ) ); ?>" id="edcm-<?php echo (int) $iid; ?>" data-edcm data-initial="<?php echo esc_attr( $initial ); ?>" data-only-supported="1"<?php echo '' !== $sticky_top ? ' data-sticky-top="' . esc_attr( $sticky_top ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?> data-i18n="<?php echo esc_attr( wp_json_encode( $i18n ) ); ?>"<?php echo $box ? ' style="--edcm-maxh:' . (int) $height . 'px"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- integer. ?>>
	<noscript><style>#edcm-<?php echo (int) $iid; ?> .edcm__panel[hidden]{display:block!important}#edcm-<?php echo (int) $iid; ?> .edcm__tabs{display:none!important}</style></noscript>
	<div class="edcm__inner">
		<header class="edcm__header">
			<<?php echo esc_html( $h_main ); ?> class="edcm__title"><?php echo esc_html( $title ); ?></<?php echo esc_html( $h_main ); ?>>
			<?php if ( $show_intro ) : ?>
				<p class="edcm__intro"><?php echo wp_kses_post( $intro ); ?></p>
			<?php endif; ?>
			<p class="edcm__meta">
				<?php if ( '' !== $data['meta']['last_reviewed'] ) : ?>
					<span><?php esc_html_e( 'Data last reviewed', 'ed-charging-matrix' ); ?> <time datetime="<?php echo esc_attr( $data['meta']['last_reviewed'] ); ?>"><?php echo esc_html( self::pretty_date( $data['meta']['last_reviewed'] ) ); ?></time></span>
					<span class="edcm__dot" aria-hidden="true">&middot;</span>
				<?php endif; ?>
				<span><?php echo esc_html( implode( ' / ', $count_bits ) ); ?></span>
			</p>
		</header>

		<?php if ( count( $keys ) > 1 ) : ?>
			<div class="edcm__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Choose a matrix', 'ed-charging-matrix' ); ?>">
				<?php foreach ( $keys as $k ) : ?>
					<button type="button" role="tab" class="edcm__tab" id="edcm-<?php echo (int) $iid; ?>-tab-<?php echo esc_attr( $k ); ?>" aria-controls="edcm-<?php echo (int) $iid; ?>-panel-<?php echo esc_attr( $k ); ?>" aria-selected="<?php echo $k === $initial ? 'true' : 'false'; ?>" tabindex="<?php echo $k === $initial ? '0' : '-1'; ?>" data-sheet="<?php echo esc_attr( $k ); ?>">
						<?php echo esc_html( $data['sheets'][ $k ]['title'] ); ?>
						<span class="edcm__tabcount"><?php echo (int) count( $data['sheets'][ $k ]['rows'] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php echo self::legend( $data, $h_section ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped internally. ?>

		<?php
		foreach ( $keys as $k ) {
			echo self::panel( $k, $data['sheets'][ $k ], $data, $iid, $k === $initial, $h_section, count( $keys ) > 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped internally.
		}
		?>

		<?php echo self::about_networks( $data, $iid, $h_section, $h_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped internally. ?>

		<?php if ( ! empty( $faq_items ) ) : ?>
			<section class="edcm__faq" aria-labelledby="edcm-<?php echo (int) $iid; ?>-faq-h">
				<<?php echo esc_html( $h_section ); ?> class="edcm__h" id="edcm-<?php echo (int) $iid; ?>-faq-h"><?php esc_html_e( 'Frequently asked questions', 'ed-charging-matrix' ); ?></<?php echo esc_html( $h_section ); ?>>
				<?php foreach ( $faq_items as $item ) : ?>
					<details class="edcm__faqitem">
						<summary><?php echo esc_html( $item['q'] ); ?></summary>
						<p><?php echo esc_html( $item['a'] ); ?></p>
					</details>
				<?php endforeach; ?>
			</section>
		<?php endif; ?>

		<p class="edcm__disclaimer">
			<?php esc_html_e( 'Compiled from automaker help pages, network announcements and press coverage. Integrations change often and can differ by model year, so confirm in your app or car before a trip. Not affiliated with any network or automaker.', 'ed-charging-matrix' ); ?>
		</p>
	</div>
	<dialog class="edcm__dialog" aria-labelledby="edcm-<?php echo (int) $iid; ?>-dlg-title"><div class="edcm__dlg" data-dlg></div></dialog>
</div>
		<?php
		// Collapse template indentation: a few hundred KB smaller on a full matrix.
		$html = (string) preg_replace( array( '/\s{2,}/', '/>\s+</' ), array( ' ', '><' ), ob_get_clean() );

		if ( $do_schema && ! self::$schema_done ) {
			self::$schema_done = true;
			$html             .= self::schema_script( $data, $faq_items, $settings, $title );
		}

		/**
		 * Filter the final matrix HTML.
		 *
		 * @param string $html Markup.
		 * @param array  $data Dataset.
		 */
		return apply_filters( 'edcm_html', $html, $data );
	}

	/* ------------------------------------------------------------------ */
	/* Sections                                                            */
	/* ------------------------------------------------------------------ */

	/**
	 * Legend block.
	 *
	 * @param array  $data      Dataset.
	 * @param string $h_section Heading tag for sections.
	 * @return string
	 */
	private static function legend( $data, $h_section ) {
		ob_start();
		?>
		<section class="edcm__legend" aria-label="<?php esc_attr_e( 'Legend', 'ed-charging-matrix' ); ?>">
			<ul class="edcm__legendlist">
				<?php foreach ( array( 'PC', 'BOTH', 'APP', 'ROAM', 'NO' ) as $code ) : ?>
					<li><?php echo self::pill( $code, $data['statuses'][ $code ]['label'], 'edcm__pill--label' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
				<?php endforeach; ?>
			</ul>
			<details class="edcm__legenddetails">
				<summary><?php esc_html_e( 'What do these mean?', 'ed-charging-matrix' ); ?></summary>
				<dl>
					<?php foreach ( array( 'PC', 'BOTH', 'APP', 'ROAM', 'NO' ) as $code ) : ?>
						<dt><?php echo esc_html( $data['statuses'][ $code ]['label'] ); ?></dt>
						<dd><?php echo esc_html( $data['statuses'][ $code ]['desc'] ); ?></dd>
					<?php endforeach; ?>
				</dl>
				<?php if ( '' !== $data['meta']['tesla_note'] ) : ?>
					<p class="edcm__note"><strong><?php esc_html_e( 'Tesla Superchargers:', 'ed-charging-matrix' ); ?></strong> <?php echo esc_html( $data['meta']['tesla_note'] ); ?></p>
				<?php endif; ?>
			</details>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * One sheet panel: toolbar plus the table.
	 *
	 * @param string $key       Sheet key.
	 * @param array  $sheet     Sheet data.
	 * @param array  $data      Dataset.
	 * @param int    $iid       Instance id.
	 * @param bool   $active    Whether it is the initially active panel.
	 * @param string $h_section Heading tag.
	 * @param bool   $tabbed    Whether the sheets are presented as tabs.
	 * @return string
	 */
	private static function panel( $key, $sheet, $data, $iid, $active, $h_section, $tabbed = true ) {
		$networks = $data['networks'];
		$n_net    = count( $networks );
		$pid      = 'edcm-' . $iid . '-panel-' . $key;
		$tid      = 'edcm-' . $iid . '-tab-' . $key;
		$top      = (array) apply_filters( 'edcm_top_networks', array( 'tesla', 'ea', 'ionna', 'evgo', 'chargepoint' ) );
		$plural   = $sheet['entity_plural'];
		$total    = count( $sheet['rows'] );

		ob_start();
		?>
		<section class="edcm__panel" id="<?php echo esc_attr( $pid ); ?>"<?php echo $tabbed ? ' role="tabpanel" aria-labelledby="' . esc_attr( $tid ) . '"' : ' aria-labelledby="' . esc_attr( $tid . '-h' ) . '"'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-sheet="<?php echo esc_attr( $key ); ?>" data-plural="<?php echo esc_attr( $plural ); ?>" data-singular="<?php echo esc_attr( $sheet['entity_singular'] ); ?>"<?php echo $active ? '' : ' hidden'; ?>>
			<<?php echo esc_html( $h_section ); ?> class="edcm__h" id="<?php echo esc_attr( $tid ); ?>-h"><?php echo esc_html( '' !== $sheet['heading'] ? $sheet['heading'] : $sheet['title'] ); ?></<?php echo esc_html( $h_section ); ?>>

			<div class="edcm__toolbar">
				<label class="edcm__field edcm__field--search edcm-js-only">
					<span class="edcm-sr"><?php echo esc_html( sprintf( /* translators: %s: plural entity name, e.g. "apps". */ __( 'Search %s', 'ed-charging-matrix' ), $plural ) ); ?></span>
					<?php echo self::icon( 'edcm-i-search', 'edcm__fieldicon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<input type="search" class="edcm__search" autocomplete="off" placeholder="<?php echo esc_attr( sprintf( /* translators: %s: plural entity name. */ __( 'Search %s…', 'ed-charging-matrix' ), $plural ) ); ?>">
				</label>
				<label class="edcm__field edcm-js-only">
					<span class="edcm-sr"><?php esc_html_e( 'Category', 'ed-charging-matrix' ); ?></span>
					<select class="edcm__cat">
						<option value=""><?php esc_html_e( 'All categories', 'ed-charging-matrix' ); ?></option>
						<?php foreach ( $sheet['categories'] as $ck => $cl ) : ?>
							<option value="<?php echo esc_attr( $ck ); ?>"><?php echo esc_html( $cl ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="edcm__field edcm-js-only">
					<span class="edcm-sr"><?php esc_html_e( 'Works with network', 'ed-charging-matrix' ); ?></span>
					<select class="edcm__netsel">
						<option value=""><?php esc_html_e( 'Works with any network', 'ed-charging-matrix' ); ?></option>
						<?php foreach ( $networks as $net ) : ?>
							<option value="<?php echo esc_attr( $net['id'] ); ?>"><?php echo esc_html( $net['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<details class="edcm__cols edcm-js-only">
					<summary class="edcm__btn"><?php echo self::icon( 'edcm-i-columns' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Networks', 'ed-charging-matrix' ); ?> <span class="edcm__colcount"><?php echo (int) $n_net; ?>/<?php echo (int) $n_net; ?></span></summary>
					<div class="edcm__colpop">
						<div class="edcm__colactions">
							<button type="button" class="edcm__link" data-cols="all"><?php esc_html_e( 'All', 'ed-charging-matrix' ); ?></button>
							<button type="button" class="edcm__link" data-cols="top" data-top="<?php echo esc_attr( implode( ',', array_map( 'sanitize_key', $top ) ) ); ?>"><?php esc_html_e( 'Top 5', 'ed-charging-matrix' ); ?></button>
							<button type="button" class="edcm__link" data-cols="none"><?php esc_html_e( 'None', 'ed-charging-matrix' ); ?></button>
						</div>
						<ul class="edcm__collist">
							<?php foreach ( $networks as $net ) : ?>
								<li><label><input type="checkbox" checked data-net="<?php echo esc_attr( $net['id'] ); ?>"> <?php echo esc_html( $net['name'] ); ?></label></li>
							<?php endforeach; ?>
						</ul>
					</div>
				</details>
				<label class="edcm__check edcm__mobile-only edcm-js-only">
					<input type="checkbox" class="edcm__showall"> <?php esc_html_e( 'Show networks without support', 'ed-charging-matrix' ); ?>
				</label>
				<div class="edcm__actions edcm-js-only">
					<button type="button" class="edcm__btn" data-action="csv"><?php echo self::icon( 'edcm-i-download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <span><?php esc_html_e( 'CSV', 'ed-charging-matrix' ); ?></span></button>
					<button type="button" class="edcm__btn" data-action="fullscreen" aria-pressed="false"><?php echo self::icon( 'edcm-i-expand' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <span><?php esc_html_e( 'Full screen', 'ed-charging-matrix' ); ?></span></button>
				</div>
			</div>

			<div class="edcm__statusrow">
				<p class="edcm__count" role="status" aria-live="polite"><?php echo esc_html( sprintf( /* translators: 1: shown, 2: total, 3: plural noun. */ __( 'Showing %1$d of %2$d %3$s', 'ed-charging-matrix' ), $total, $total, $plural ) ); ?></p>
				<div class="edcm__scrollbtns edcm-js-only" aria-hidden="true">
					<button type="button" class="edcm__iconbtn" data-scroll="-1" tabindex="-1" title="<?php esc_attr_e( 'Scroll table left', 'ed-charging-matrix' ); ?>"><?php echo self::icon( 'edcm-i-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					<button type="button" class="edcm__iconbtn" data-scroll="1" tabindex="-1" title="<?php esc_attr_e( 'Scroll table right', 'ed-charging-matrix' ); ?>"><?php echo self::icon( 'edcm-i-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				</div>
			</div>

			<div class="edcm__scroller" tabindex="0" role="region" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: sheet title. */ __( '%s compatibility table, scrollable', 'ed-charging-matrix' ), $sheet['title'] ) ); ?>">
				<table class="edcm__table" role="table" style="--edcm-cols:<?php echo (int) $n_net; ?>">
					<caption class="edcm-sr"><?php echo esc_html( sprintf( /* translators: 1: sheet heading, 2: number of networks. */ __( '%1$s. Each row shows how the %2$s works with %3$d US charging networks.', 'ed-charging-matrix' ), '' !== $sheet['heading'] ? $sheet['heading'] : $sheet['title'], $sheet['entity_singular'], $n_net ) ); ?></caption>
					<thead>
						<tr role="row">
							<th scope="col" role="columnheader" class="edcm__corner"><?php echo esc_html( $sheet['entity_label'] ); ?></th>
							<?php foreach ( $networks as $net ) : ?>
								<th scope="col" role="columnheader" class="edcm__nethead" data-net="<?php echo esc_attr( $net['id'] ); ?>">
									<button type="button" class="edcm__netbtn" data-net="<?php echo esc_attr( $net['id'] ); ?>" title="<?php echo esc_attr( $net['name'] . ' - ' . $net['type'] ); ?>"><span class="edcm__netname"><?php echo esc_html( $net['short'] ); ?></span></button>
								</th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $sheet['rows'] as $row ) {
							echo self::row( $key, $row, $sheet, $networks, $data, $iid ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped internally.
						}
						?>
					</tbody>
				</table>
			</div>
			<p class="edcm__empty" hidden><?php esc_html_e( 'No matches. Try clearing a filter.', 'ed-charging-matrix' ); ?></p>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * One table row.
	 *
	 * @param string $skey     Sheet key.
	 * @param array  $row      Row.
	 * @param array  $sheet    Sheet.
	 * @param array  $networks Networks.
	 * @param array  $data     Dataset.
	 * @param int    $iid      Instance id.
	 * @return string
	 */
	private static function row( $skey, $row, $sheet, $networks, $data, $iid ) {
		$n_net   = count( $networks );
		$n_ok    = self::integrated_count( $row );
		$cat_lbl = isset( $sheet['categories'][ $row['category'] ] ) ? $sheet['categories'][ $row['category'] ] : $row['category'];
		$sub     = array_filter( array( $row['owner'], $cat_lbl ) );
		$search  = strtolower( implode( ' ', array_filter( array( $row['name'], $row['owner'], $cat_lbl, $row['notes'], $row['system'], $row['oem_app'] ) ) ) );

		ob_start();
		?>
		<tr class="edcm__row" role="row" id="edcm-<?php echo (int) $iid; ?>-<?php echo esc_attr( $skey . '-' . $row['id'] ); ?>"
			data-row="<?php echo esc_attr( $row['id'] ); ?>"
			data-cat="<?php echo esc_attr( $row['category'] ); ?>"
			data-search="<?php echo esc_attr( $search ); ?>"
			data-name="<?php echo esc_attr( $row['name'] ); ?>"
			data-owner="<?php echo esc_attr( $row['owner'] ); ?>"
			data-catlabel="<?php echo esc_attr( $cat_lbl ); ?>"
			data-notes="<?php echo esc_attr( $row['notes'] ); ?>"
			data-system="<?php echo esc_attr( $row['system'] ); ?>"
			data-oemapp="<?php echo esc_attr( $row['oem_app'] ); ?>"
			data-ok="<?php echo (int) $n_ok; ?>">
			<th scope="row" role="rowheader" class="edcm__rowhead">
				<button type="button" class="edcm__rowbtn">
					<span class="edcm__name"><?php echo esc_html( $row['name'] ); ?></span>
					<span class="edcm__owner"><?php echo esc_html( implode( ' · ', $sub ) ); ?></span>
				</button>
				<span class="edcm__badge" title="<?php echo esc_attr( sprintf( /* translators: 1: integrated count, 2: total networks. */ __( 'Integrated with %1$d of %2$d networks', 'ed-charging-matrix' ), $n_ok, $n_net ) ); ?>"><b><?php echo (int) $n_ok; ?></b>/<?php echo (int) $n_net; ?></span>
				<?php if ( '' !== $row['system'] ) : ?>
					<span class="edcm__sys"><?php echo esc_html( $row['system'] ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $row['notes'] ) : ?>
					<span class="edcm__notes"><?php echo esc_html( $row['notes'] ); ?></span>
				<?php endif; ?>
			</th>
			<?php
			foreach ( $networks as $net ) {
				$s     = $row['status'][ $net['id'] ];
				$code  = $s['code'];
				$label = $data['statuses'][ $code ]['label'];
				$text  = '' !== $s['text'] ? $s['text'] : '';
				?>
				<td class="edcm__cell" role="cell" data-net="<?php echo esc_attr( $net['id'] ); ?>" data-code="<?php echo esc_attr( $code ); ?>">
					<span class="edcm__netlabel"><?php echo esc_html( $net['name'] ); ?></span>
					<button type="button" class="edcm__cellbtn" aria-label="<?php echo esc_attr( $net['name'] . ': ' . $label ); ?>">
						<?php echo self::pill( $code, $label ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
					<span class="edcm__detail"><?php echo esc_html( $text ); ?></span>
				</td>
				<?php
			}
			?>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * "About the networks" block (also feeds the network dialog).
	 *
	 * @param array  $data      Dataset.
	 * @param int    $iid       Instance id.
	 * @param string $h_section Section heading tag.
	 * @param string $h_item    Item heading tag.
	 * @return string
	 */
	private static function about_networks( $data, $iid, $h_section, $h_item ) {
		ob_start();
		?>
		<section class="edcm__about" aria-labelledby="edcm-<?php echo (int) $iid; ?>-about-h">
			<<?php echo esc_html( $h_section ); ?> class="edcm__h" id="edcm-<?php echo (int) $iid; ?>-about-h"><?php esc_html_e( 'About the charging networks', 'ed-charging-matrix' ); ?></<?php echo esc_html( $h_section ); ?>>
			<details class="edcm__aboutdetails">
				<summary><?php echo esc_html( sprintf( /* translators: %d: number of networks. */ _n( 'Show details for the %d network', 'Show details for all %d networks', count( $data['networks'] ), 'ed-charging-matrix' ), count( $data['networks'] ) ) ); ?></summary>
				<div class="edcm__netgrid">
					<?php foreach ( $data['networks'] as $net ) : ?>
						<article class="edcm__netinfo" id="edcm-<?php echo (int) $iid; ?>-net-<?php echo esc_attr( $net['id'] ); ?>" data-net="<?php echo esc_attr( $net['id'] ); ?>">
							<<?php echo esc_html( $h_item ); ?> class="edcm__neth"><?php echo esc_html( $net['name'] ); ?></<?php echo esc_html( $h_item ); ?>>
							<p class="edcm__nettype"><?php echo esc_html( $net['type'] ); ?></p>
							<p class="edcm__netabout"><?php echo esc_html( $net['about'] ); ?></p>
							<?php if ( '' !== $net['url'] ) : ?>
								<p class="edcm__netlink"><a href="<?php echo esc_url( $net['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( sprintf( /* translators: %s: network name. */ __( '%s official site', 'ed-charging-matrix' ), $net['name'] ) ); ?></a></p>
							<?php endif; ?>
							<?php if ( ! empty( $net['sources'] ) ) : ?>
								<ul class="edcm__netsrc">
									<?php foreach ( $net['sources'] as $src ) : ?>
										<li><a href="<?php echo esc_url( $src['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $src['label'] ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			</details>
		</section>
		<?php
		return ob_get_clean();
	}

	/* ------------------------------------------------------------------ */
	/* FAQ and structured data                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Names of rows in a sheet grouped by status code for one network.
	 *
	 * @param array  $rows   Sheet rows.
	 * @param string $net_id Network id.
	 * @return array code => names.
	 */
	private static function group_by_code( $rows, $net_id ) {
		$out = array(
			'PC'   => array(),
			'BOTH' => array(),
			'APP'  => array(),
			'ROAM' => array(),
		);
		foreach ( $rows as $row ) {
			$code = isset( $row['status'][ $net_id ] ) ? $row['status'][ $net_id ]['code'] : 'NO';
			if ( isset( $out[ $code ] ) ) {
				$out[ $code ][] = $row['name'];
			}
		}
		return $out;
	}

	/**
	 * Data-driven FAQ entries (also used for FAQPage structured data).
	 *
	 * @param array $data Dataset.
	 * @return array List of array( 'q' => , 'a' => ).
	 */
	private static function faq_items( $data ) {
		$items = array();
		$date  = self::pretty_date( $data['meta']['last_reviewed'] );

		$names = wp_list_pluck( $data['networks'], 'name' );
		$items[] = array(
			'q' => __( 'Which EV charging networks does this matrix cover?', 'ed-charging-matrix' ),
			/* translators: 1: number of networks, 2: list of network names. */
			'a' => sprintf( __( 'It compares %1$d US charging networks: %2$s.', 'ed-charging-matrix' ), count( $names ), self::join_list( $names ) ),
		);

		$brands = isset( $data['sheets']['brands'] ) ? $data['sheets']['brands'] : null;
		$faq_nets = (array) apply_filters( 'edcm_faq_networks', array( 'tesla', 'ionna', 'ea', 'evgo', 'chargepoint' ) );
		if ( $brands ) {
			foreach ( $faq_nets as $nid ) {
				$net = self::find_network( $data, $nid );
				if ( ! $net ) {
					continue;
				}
				$g     = self::group_by_code( $brands['rows'], $net['id'] );
				$parts = array();
				if ( $g['PC'] ) {
					/* translators: %s: list of car brands. */
					$parts[] = sprintf( __( 'Plug & Charge: %s.', 'ed-charging-matrix' ), self::join_list( $g['PC'] ) );
				}
				if ( $g['BOTH'] ) {
					/* translators: %s: list of car brands. */
					$parts[] = sprintf( __( 'From the car screen and the automaker app: %s.', 'ed-charging-matrix' ), self::join_list( $g['BOTH'] ) );
				}
				if ( $g['APP'] ) {
					/* translators: %s: list of car brands. */
					$parts[] = sprintf( __( 'From the automaker app: %s.', 'ed-charging-matrix' ), self::join_list( $g['APP'] ) );
				}
				if ( $g['ROAM'] ) {
					/* translators: %s: list of car brands. */
					$parts[] = sprintf( __( 'Through roaming: %s.', 'ed-charging-matrix' ), self::join_list( $g['ROAM'] ) );
				}
				if ( empty( $parts ) ) {
					/* translators: %s: network name. */
					$answer = sprintf( __( 'No car brand in this matrix has a direct in-car or app integration with %s yet.', 'ed-charging-matrix' ), $net['name'] );
				} else {
					/* translators: 1: review date, 2: network name, 3: breakdown. */
					$answer = sprintf( __( 'As of our %1$s review, these car brands can start or pay for charging at %2$s through the car or the automaker\'s app. %3$s', 'ed-charging-matrix' ), $date, $net['name'], implode( ' ', $parts ) );
				}
				$items[] = array(
					/* translators: %s: network name. */
					'q' => sprintf( __( 'Which car brands work with %s?', 'ed-charging-matrix' ), $net['name'] ),
					'a' => $answer,
				);
			}
		}

		/* Spotlight: a network with no (or few) integrations, e.g. Walmart. */
		$walmart = self::find_network( $data, 'walmart' );
		if ( $walmart ) {
			$apps_ok = array();
			$car_ok  = array();
			foreach ( $data['sheets'] as $skey => $sheet ) {
				foreach ( $sheet['rows'] as $row ) {
					if ( isset( $row['status']['walmart'] ) && 'NO' !== $row['status']['walmart']['code'] ) {
						if ( 'brands' === $skey ) {
							$car_ok[] = $row['name'];
						} else {
							$apps_ok[] = $row['name'];
						}
					}
				}
			}
			$bits = array();
			if ( empty( $car_ok ) ) {
				$bits[] = __( 'No car brand in this matrix has a direct in-car or automaker-app integration with it yet.', 'ed-charging-matrix' );
			} else {
				/* translators: %s: list of car brands. */
				$bits[] = sprintf( __( 'Car brands with a direct integration: %s.', 'ed-charging-matrix' ), self::join_list( $car_ok ) );
			}
			if ( ! empty( $apps_ok ) ) {
				/* translators: %s: list of apps. */
				$bits[] = sprintf( __( 'Apps that can start a session: %s.', 'ed-charging-matrix' ), self::join_list( $apps_ok ) );
			}
			$items[] = array(
				/* translators: %s: network name. */
				'q' => sprintf( __( 'Can I use my car\'s app or a charging app at %s chargers?', 'ed-charging-matrix' ), $walmart['name'] ),
				'a' => trim( $walmart['about'] . ' ' . implode( ' ', $bits ) ),
			);
		}

		/* Plug & Charge explainer plus data-driven leaders. */
		if ( $brands ) {
			$scores = array();
			foreach ( $brands['rows'] as $row ) {
				$n = 0;
				foreach ( $row['status'] as $s ) {
					if ( 'PC' === $s['code'] ) {
						++$n;
					}
				}
				if ( $n > 0 ) {
					$scores[ $row['name'] ] = $n;
				}
			}
			arsort( $scores );
			$lead   = array_slice( array_keys( $scores ), 0, 3 );
			$answer = $data['statuses']['PC']['desc'];
			if ( $lead ) {
				/* translators: %s: list of car brands. */
				$answer .= ' ' . sprintf( __( 'In this matrix, %s support it on the most networks.', 'ed-charging-matrix' ), self::join_list( $lead ) );
			}
			$items[] = array(
				'q' => __( 'What is Plug & Charge?', 'ed-charging-matrix' ),
				'a' => trim( $answer ),
			);
		}

		$items[] = array(
			'q' => __( 'How current is this data and where does it come from?', 'ed-charging-matrix' ),
			'a' => sprintf(
				/* translators: %s: review date. */
				__( 'The data was last reviewed on %s. It is compiled from automaker help pages, network announcements and press coverage; sources are listed under each network. Integrations change often and can vary by model year, so confirm in your app or car before a trip.', 'ed-charging-matrix' ),
				'' !== $date ? $date : __( '(date not set)', 'ed-charging-matrix' )
			),
		);

		foreach ( $data['faq'] as $extra ) {
			$items[] = $extra;
		}

		return (array) apply_filters( 'edcm_faq_items', $items, $data );
	}

	/**
	 * JSON-LD script (Dataset and FAQPage).
	 *
	 * @param array  $data      Dataset.
	 * @param array  $faq_items FAQ entries.
	 * @param array  $settings  Settings.
	 * @param string $title     Title.
	 * @return string
	 */
	private static function schema_script( $data, $faq_items, $settings, $title ) {
		$org = '' !== trim( (string) $settings['organization'] ) ? $settings['organization'] : get_bloginfo( 'name' );
		$url = is_singular() ? get_permalink() : home_url( '/' );

		$keywords = array( 'EV charging', 'Plug and Charge', 'NACS', 'CCS', 'charging network compatibility' );
		foreach ( $data['networks'] as $net ) {
			$keywords[] = $net['name'];
		}

		$dataset = array(
			'@type'               => 'Dataset',
			'name'                => $title,
			'description'         => '' !== $data['meta']['description'] ? $data['meta']['description'] : $title,
			'url'                 => $url,
			'inLanguage'          => 'en-US',
			'isAccessibleForFree' => true,
			'spatialCoverage'     => array(
				'@type' => 'Country',
				'name'  => 'United States',
			),
			'keywords'            => $keywords,
			'creator'             => array(
				'@type' => 'Organization',
				'name'  => $org,
				'url'   => home_url( '/' ),
			),
		);
		if ( '' !== $data['meta']['last_reviewed'] ) {
			$dataset['dateModified'] = $data['meta']['last_reviewed'];
		}

		$graph = array( $dataset );

		if ( ! empty( $faq_items ) ) {
			$entities = array();
			foreach ( $faq_items as $item ) {
				$entities[] = array(
					'@type'          => 'Question',
					'name'           => $item['q'],
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $item['a'],
					),
				);
			}
			$graph[] = array(
				'@type'      => 'FAQPage',
				'mainEntity' => $entities,
			);
		}

		/**
		 * Filter the JSON-LD graph (return an empty array to output nothing).
		 *
		 * @param array $graph Schema graph nodes.
		 * @param array $data  Dataset.
		 */
		$graph = (array) apply_filters( 'edcm_schema_graph', $graph, $data );
		if ( empty( $graph ) ) {
			return '';
		}

		$json = wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
		);
		return "\n" . '<script type="application/ld+json">' . $json . '</script>' . "\n";
	}
}
