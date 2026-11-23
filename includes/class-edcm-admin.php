<?php
/**
 * Admin screen: display settings and the JSON data editor.
 *
 * @package ElectricDuoChargingMatrix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings page under Settings > EV Charging Matrix.
 */
class EDCM_Admin {

	const PAGE = 'edcm';

	/**
	 * Hook up admin functionality.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_edcm_save_data', array( __CLASS__, 'handle_save_data' ) );
		add_action( 'admin_post_edcm_reset_data', array( __CLASS__, 'handle_reset_data' ) );
		add_action( 'admin_post_edcm_export_data', array( __CLASS__, 'handle_export_data' ) );
	}

	/**
	 * Add the settings page.
	 */
	public static function menu() {
		add_options_page(
			__( 'EV Charging Matrix', 'ed-charging-matrix' ),
			__( 'EV Charging Matrix', 'ed-charging-matrix' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register the display settings.
	 */
	public static function register_settings() {
		register_setting(
			'edcm_settings_group',
			'edcm_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitise the settings array.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();
		return array(
			'intro'        => isset( $input['intro'] ) ? wp_kses_post( wp_unslash( $input['intro'] ) ) : '',
			'organization' => isset( $input['organization'] ) ? sanitize_text_field( wp_unslash( $input['organization'] ) ) : '',
			'table_height' => ! empty( $input['table_height'] ) ? max( 320, min( 1600, absint( $input['table_height'] ) ) ) : 0,
			'sticky_top'   => isset( $input['sticky_top'] ) && is_numeric( $input['sticky_top'] ) ? (string) min( 400, absint( $input['sticky_top'] ) ) : '',
			'load_fonts'   => empty( $input['load_fonts'] ) ? 0 : 1,
			'show_faq'     => empty( $input['show_faq'] ) ? 0 : 1,
			'schema'       => empty( $input['schema'] ) ? 0 : 1,
		);
	}

	/**
	 * Redirect back to the settings page with a notice code.
	 *
	 * @param string $notice Notice key.
	 * @param string $detail Optional detail (error text).
	 */
	private static function back( $notice, $detail = '' ) {
		$args = array(
			'page'       => self::PAGE,
			'edcm_notice' => $notice,
		);
		if ( '' !== $detail ) {
			$args['edcm_detail'] = rawurlencode( substr( $detail, 0, 300 ) );
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'options-general.php' ) ) );
		exit;
	}

	/**
	 * Save a custom JSON dataset.
	 */
	public static function handle_save_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'ed-charging-matrix' ), 403 );
		}
		check_admin_referer( 'edcm_save_data' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON is validated and re-encoded below.
		$raw     = isset( $_POST['edcm_json'] ) ? wp_unslash( $_POST['edcm_json'] ) : '';
		$decoded = json_decode( (string) $raw, true );
		if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
			self::back( 'error', __( 'That is not valid JSON: ', 'ed-charging-matrix' ) . json_last_error_msg() );
		}
		$valid = EDCM_Data::validate( $decoded );
		if ( is_wp_error( $valid ) ) {
			self::back( 'error', $valid->get_error_message() );
		}

		// Store the original (not the normalised) JSON so nothing the editor wrote is lost.
		update_option( EDCM_Data::OPTION, wp_json_encode( $decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ), false );
		EDCM_Data::flush();
		self::back( 'saved' );
	}

	/**
	 * Remove the custom dataset and return to the bundled one.
	 */
	public static function handle_reset_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'ed-charging-matrix' ), 403 );
		}
		check_admin_referer( 'edcm_reset_data' );
		delete_option( EDCM_Data::OPTION );
		EDCM_Data::flush();
		self::back( 'reset' );
	}

	/**
	 * Download the active dataset as JSON.
	 */
	public static function handle_export_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'ed-charging-matrix' ), 403 );
		}
		check_admin_referer( 'edcm_export_data' );
		$json = self::current_json();
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="ev-charging-matrix-data.json"' );
		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download.
		exit;
	}

	/**
	 * Pretty-printed JSON of the data currently in use (custom or bundled).
	 *
	 * @return string
	 */
	private static function current_json() {
		if ( EDCM_Data::has_custom() ) {
			$decoded = json_decode( (string) get_option( EDCM_Data::OPTION, '' ), true );
			if ( is_array( $decoded ) ) {
				return wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			}
		}
		$default = json_decode( EDCM_Data::default_json(), true );
		return is_array( $default ) ? wp_json_encode( $default, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : '';
	}

	/**
	 * Render the settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s      = EDCM_Render::settings();
		$data   = EDCM_Data::get();
		$custom = EDCM_Data::has_custom();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display only.
		$notice = isset( $_GET['edcm_notice'] ) ? sanitize_key( wp_unslash( $_GET['edcm_notice'] ) ) : '';
		$detail = isset( $_GET['edcm_detail'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['edcm_detail'] ) ) ) : '';
		// phpcs:enable
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'EV Charging Matrix', 'ed-charging-matrix' ); ?></h1>

			<?php if ( 'saved' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Custom data saved.', 'ed-charging-matrix' ); ?></p></div>
			<?php elseif ( 'reset' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Reverted to the data bundled with the plugin.', 'ed-charging-matrix' ); ?></p></div>
			<?php elseif ( 'error' === $notice ) : ?>
				<div class="notice notice-error"><p><strong><?php esc_html_e( 'Data not saved.', 'ed-charging-matrix' ); ?></strong> <?php echo esc_html( $detail ); ?></p></div>
			<?php endif; ?>

			<div class="card" style="max-width:820px">
				<h2><?php esc_html_e( 'How to use', 'ed-charging-matrix' ); ?></h2>
				<p><?php esc_html_e( 'Add the shortcode to any page or post (a dedicated full-width page works best):', 'ed-charging-matrix' ); ?></p>
				<p><code>[ev_charging_matrix]</code></p>
				<p><?php esc_html_e( 'Options:', 'ed-charging-matrix' ); ?></p>
				<ul style="list-style:disc;margin-left:1.5em">
					<li><code>view="apps"</code> / <code>view="brands"</code> <?php esc_html_e( 'show only one sheet (default: both).', 'ed-charging-matrix' ); ?></li>
					<li><code>default="brands"</code> <?php esc_html_e( 'which sheet is open first.', 'ed-charging-matrix' ); ?></li>
					<li><code>title="..."</code>, <code>heading_level="2"</code> <?php esc_html_e( '(2 to 4) so the headings fit your page outline.', 'ed-charging-matrix' ); ?></li>
					<li><code>intro="0"</code>, <code>faq="0"</code>, <code>schema="0"</code> <?php esc_html_e( 'switch parts off.', 'ed-charging-matrix' ); ?></li>
					<li><code>align="wide"</code> / <code>align="full"</code> <?php esc_html_e( 'breaks out of a narrow content column in block themes. In classic and Divi layouts put the shortcode in a full-width section instead.', 'ed-charging-matrix' ); ?></li>
					<li><code>height="640"</code> <?php esc_html_e( 'maximum table height in pixels.', 'ed-charging-matrix' ); ?></li>
				</ul>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: networks, 2: review date. */
							__( 'Currently loaded: %1$d networks, data last reviewed %2$s (%3$s).', 'ed-charging-matrix' ),
							count( $data['networks'] ),
							'' !== $data['meta']['last_reviewed'] ? $data['meta']['last_reviewed'] : __( 'date not set', 'ed-charging-matrix' ),
							$custom ? __( 'custom data', 'ed-charging-matrix' ) : __( 'bundled data', 'ed-charging-matrix' )
						)
					);
					?>
				</p>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'edcm_settings_group' ); ?>
				<h2><?php esc_html_e( 'Display settings', 'ed-charging-matrix' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="edcm_intro"><?php esc_html_e( 'Intro text', 'ed-charging-matrix' ); ?></label></th>
						<td>
							<textarea id="edcm_intro" name="edcm_settings[intro]" rows="3" class="large-text"><?php echo esc_textarea( $s['intro'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Shown under the title. Leave blank for the default text. Basic HTML allowed.', 'ed-charging-matrix' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="edcm_org"><?php esc_html_e( 'Publisher name', 'ed-charging-matrix' ); ?></label></th>
						<td>
							<input type="text" id="edcm_org" name="edcm_settings[organization]" value="<?php echo esc_attr( $s['organization'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
							<p class="description"><?php esc_html_e( 'Used as the dataset creator in the structured data.', 'ed-charging-matrix' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="edcm_h"><?php esc_html_e( 'Fixed table height (px)', 'ed-charging-matrix' ); ?></label></th>
						<td>
							<input type="number" id="edcm_h" name="edcm_settings[table_height]" value="<?php echo (int) $s['table_height']; ?>" min="0" max="1600" step="10" class="small-text">
							<p class="description"><?php esc_html_e( 'Leave at 0 (recommended): the table grows with the page and its header row stays pinned to the top of the screen while you scroll. Enter 320 to 1600 to scroll inside a box of that height instead.', 'ed-charging-matrix' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="edcm_st"><?php esc_html_e( 'Pinned header offset (px)', 'ed-charging-matrix' ); ?></label></th>
						<td>
							<input type="number" id="edcm_st" name="edcm_settings[sticky_top]" value="<?php echo esc_attr( (string) $s['sticky_top'] ); ?>" min="0" max="400" step="1" class="small-text" placeholder="<?php esc_attr_e( 'auto', 'ed-charging-matrix' ); ?>">
							<p class="description"><?php esc_html_e( 'How far from the top of the screen the pinned header sits. Leave empty to detect your theme\'s fixed menu automatically; enter its height if the pinned header ends up hidden behind it.', 'ed-charging-matrix' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Options', 'ed-charging-matrix' ); ?></th>
						<td>
							<fieldset>
								<label><input type="checkbox" name="edcm_settings[show_faq]" value="1" <?php checked( ! empty( $s['show_faq'] ) ); ?>> <?php esc_html_e( 'Show the FAQ section (generated from the data)', 'ed-charging-matrix' ); ?></label><br>
								<label><input type="checkbox" name="edcm_settings[schema]" value="1" <?php checked( ! empty( $s['schema'] ) ); ?>> <?php esc_html_e( 'Output structured data (JSON-LD: Dataset and FAQPage)', 'ed-charging-matrix' ); ?></label><br>
								<label><input type="checkbox" name="edcm_settings[load_fonts]" value="1" <?php checked( ! empty( $s['load_fonts'] ) ); ?>> <?php esc_html_e( 'Load Inter, Oswald and Poppins from Google Fonts (turn on only if your theme does not already load them)', 'ed-charging-matrix' ); ?></label>
							</fieldset>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save settings', 'ed-charging-matrix' ) ); ?>
			</form>

			<hr>
			<h2><?php esc_html_e( 'Data', 'ed-charging-matrix' ); ?></h2>
			<p><?php esc_html_e( 'The matrix reads a JSON file with networks, apps and car brands. Edit it here, or export it, change it in your editor and paste it back. The data is checked before saving; if it is invalid the previous data stays in use. Each status code must be one of PC, BOTH, APP, ROAM or NO.', 'ed-charging-matrix' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="edcm_save_data">
				<?php wp_nonce_field( 'edcm_save_data' ); ?>
				<textarea name="edcm_json" rows="22" class="large-text code" spellcheck="false" aria-label="<?php esc_attr_e( 'Matrix data (JSON)', 'ed-charging-matrix' ); ?>" style="font-family:Menlo,Consolas,monospace;font-size:12px;tab-size:2"><?php echo esc_textarea( self::current_json() ); ?></textarea>
				<p>
					<?php submit_button( __( 'Validate and save data', 'ed-charging-matrix' ), 'primary', 'submit', false ); ?>
				</p>
			</form>
			<div style="margin:1em 0">
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=edcm_export_data' ), 'edcm_export_data' ) ); ?>"><?php esc_html_e( 'Download current JSON', 'ed-charging-matrix' ); ?></a>
				<?php if ( $custom ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('<?php echo esc_js( __( 'Discard your custom data and go back to the data bundled with the plugin?', 'ed-charging-matrix' ) ); ?>');">
						<input type="hidden" name="action" value="edcm_reset_data">
						<?php wp_nonce_field( 'edcm_reset_data' ); ?>
						<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Reset to bundled data', 'ed-charging-matrix' ); ?></button>
					</form>
				<?php endif; ?>
			</div>
			<p class="description"><?php esc_html_e( 'Tip: custom data survives plugin updates. After an update that ships newer bundled data, use "Reset to bundled data" to pick it up.', 'ed-charging-matrix' ); ?></p>
		</div>
		<?php
	}
}
