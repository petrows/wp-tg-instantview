<?php
/**
 * TG InstantView Admin Page
 *
 * @package   TG-InstantView
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TG InstantView Admin settings page class
 */
class Tgiv_Settings {

	/**
	 * Holds the values to be used in the fields callbacks
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page() {
		/* This page will be under "Settings" */
		add_options_page(
			'TG InstantView Admin',
			'TG InstantView',
			'manage_options',
			'tgiv-instantview-setting-admin',
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page() {
		/* Set class property */
		$this->options = tgiv_options();

		?>
		<div class="wrap">
			<h1>TG InstantView settings</h1>
			<form method="post" action="options.php">
			<?php
				/* This prints out all hidden setting fields */
				settings_fields( 'tgiv_instantview' );
				do_settings_sections( 'tgiv-instantview-setting-admin' );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init() {
		register_setting(
			'tgiv_instantview', // Option group.
			'tgiv_instantview_render', // Option name.
			array( $this, 'sanitize' ) // Sanitize.
		);

		add_settings_section(
			'tgiv_render_options', // ID.
			'Render options', // Title.
			array( $this, 'print_section_info_render' ), // Callback.
			'tgiv-instantview-setting-admin' // Page.
		);

		add_settings_field(
			'tgiv_channel_name', // ID.
			'Telegram channel', // Title.
			array( $this, 'channel_name_callback' ), // Callback.
			'tgiv-instantview-setting-admin', // Page.
			'tgiv_render_options' // Section.
		);
		add_settings_field(
			'tgiv_display_date', // ID.
			'Display post date?', // Title.
			array( $this, 'display_date_callback' ), // Callback.
			'tgiv-instantview-setting-admin', // Page.
			'tgiv_render_options' // Section.
		);
		add_settings_field(
			'tgiv_display_author', // ID.
			'Display post author?', // Title.
			array( $this, 'display_author_callback' ), // Callback.
			'tgiv-instantview-setting-admin', // Page.
			'tgiv_render_options' // Section.
		);
		add_settings_field(
			'tgiv_process_media', // ID.
			'Process media?', // Title.
			array( $this, 'process_media_callback' ), // Callback.
			'tgiv-instantview-setting-admin', // Page.
			'tgiv_render_options' // Section.
		);
		add_settings_field(
			'tgiv_media_action', // ID.
			'Media action', // Title.
			array( $this, 'media_action_callback' ), // Callback.
			'tgiv-instantview-setting-admin', // Page.
			'tgiv_render_options' // Section.
		);
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys.
	 */
	public function sanitize( $input ) {
		$new_input = array();
		if ( isset( $input['tgiv_channel_name'] ) ) {
			$channel_name = $input['tgiv_channel_name'];
			$channel_name = trim( $channel_name );
			$channel_name = strtolower( $channel_name );
			$channel_name = preg_replace( '/[^a-z0-9_-]+/', '', $channel_name );
			/* Channel name must starts from @ */
			if ( strlen( $channel_name ) && '@' !== $channel_name[0] ) {
				$channel_name = '@' . $channel_name;
			}
			$new_input['tgiv_channel_name'] = $channel_name;
		}
		$new_input['tgiv_display_date']   = isset( $input['tgiv_display_date'] );
		$new_input['tgiv_display_author'] = isset( $input['tgiv_display_author'] );
		$new_input['tgiv_process_media']  = isset( $input['tgiv_process_media'] );
		$new_input['tgiv_media_action']   = 'remove';
		if ( isset( $input['tgiv_media_action'] ) ) {
			$media_action = sanitize_key( wp_unslash( $input['tgiv_media_action'] ) );
			if ( in_array( $media_action, array( 'remove', 'link' ), true ) ) {
				$new_input['tgiv_media_action'] = $media_action;
			}
		}
		return $new_input;
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info_render() {
		print 'What should be displayed in InstantView page';
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function channel_name_callback() {
		printf(
			'<input type="text" id="tgiv_channel_name" name="tgiv_instantview_render[tgiv_channel_name]" value="%s" />',
			isset( $this->options['tgiv_channel_name'] ) ? esc_attr( $this->options['tgiv_channel_name'] ) : ''
		);
	}

	/**
	 * Display checkbox for date option
	 */
	public function display_date_callback() {
		printf(
			'<input type="checkbox" id="tgiv_display_date" name="tgiv_instantview_render[tgiv_display_date]" value="ON" %s/>',
			$this->options['tgiv_display_date'] ? 'checked="checked"' : ''
		);
	}

	/**
	 * Display checkbox for author option
	 */
	public function display_author_callback() {
		printf(
			'<input type="checkbox" id="tgiv_display_author" name="tgiv_instantview_render[tgiv_display_author]" value="ON" %s/>',
			$this->options['tgiv_display_author'] ? 'checked="checked"' : ''
		);
	}

	/**
	 * Display checkbox for media processing.
	 */
	public function process_media_callback() {
		printf(
			'<label><input type="checkbox" id="tgiv_process_media" name="tgiv_instantview_render[tgiv_process_media]" value="ON" %s/> Remove media from the Instant View body or replace it with links.</label>',
			$this->options['tgiv_process_media'] ? 'checked="checked"' : ''
		);
	}

	/**
	 * Display media processing action selector.
	 */
	public function media_action_callback() {
		?>
		<select id="tgiv_media_action" name="tgiv_instantview_render[tgiv_media_action]">
			<option value="remove" <?php selected( $this->options['tgiv_media_action'], 'remove' ); ?>>Remove media</option>
			<option value="link" <?php selected( $this->options['tgiv_media_action'], 'link' ); ?>>Replace media with links</option>
		</select>
		<p class="description">Applies to images, picture elements, iframes, video, audio, object and embed elements. Open Graph preview images are preserved.</p>
		<?php
	}
}

$tgiv_settings_page = new Tgiv_Settings();
