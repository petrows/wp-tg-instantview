<?php

if (!defined("ABSPATH")) {
    exit;
}

class tgiv_settings
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
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
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'tgiv_instantview_render' );

        ?>
        <div class="wrap">
            <h1>TG InstantView settings</h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
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
    public function page_init()
    {
        register_setting(
            'tgiv_instantview', // Option group
            'tgiv_instantview_render', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'tgiv_render_options', // ID
            'Render options', // Title
            array( $this, 'print_section_info_render' ), // Callback
            'tgiv-instantview-setting-admin' // Page
        );

        add_settings_field(
            'tgiv_channel_name', // ID
            'Telegram channel', // Title
            array( $this, 'channel_name_callback' ), // Callback
            'tgiv-instantview-setting-admin', // Page
            'tgiv_render_options' // Section
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['tgiv_channel_name'] ) ) {
            $channel_name = $input['tgiv_channel_name'];
            $channel_name = trim($channel_name);
            $channel_name = strtolower($channel_name);
            $channel_name = preg_replace( '/[^a-z0-9_-]+/', '', $channel_name);
            // Channel name should starts from @
            if (strlen($channel_name) && $channel_name[0] != '@') {
                $channel_name = '@'.$channel_name;
            }
            $new_input['tgiv_channel_name'] = $channel_name;
        }
        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info_render()
    {
        print 'What should be displayed in InstantView page';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function channel_name_callback()
    {
        printf(
            '<input type="text" id="tgiv_channel_name" name="tgiv_instantview_render[tgiv_channel_name]" value="%s" />',
            isset( $this->options['tgiv_channel_name'] ) ? esc_attr( $this->options['tgiv_channel_name']) : ''
        );
    }
}

$tgiv_settings_page = new tgiv_settings();
