<?php

class Studio24_Preview_Settings {

	static $instance = false;

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) );
		add_action( 'admin_init', array( $this, 'setup_sections' ) );
		add_action( 'admin_init', array( $this, 'setup_fields' ) );
	}

	public function create_plugin_settings_page() {
		$page_title = "Studio 24 - Preview Page";
		$menu_title = 'Preview Page';
		$capability = 'manage_options';
		$slug       = 'studio24_preview';
		$callback   = array( $this, 'plugin_settings_page_content' );
		$icon       = 'dashicons-admin-plugins';
		$position   = 100;

		add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
	}

	public function plugin_settings_page_content() {
		?>
        <style>
            .wrap, table.form-table th, table.form-table td, th {
                text-align: center;
            }

            table.form-table input {
                width: 50%;
            }

            p.submit {
                text-align: center;
            }
        </style>
        <div class="wrap">
            <h2>Studio 24 - Preview Plugin</h2>
            <form method="post" action="options.php">
				<?php
				settings_fields( 'studio24_preview' );
				do_settings_sections( 'studio24_preview' );
				submit_button();
				?>
            </form>
        </div>
		<?php
	}

	public function setup_sections() {
		add_settings_section( 'frontend_url_section', 'Setup frontend URL', array(
			$this,
			'section_callback'
		), 'studio24_preview' );
		add_settings_section( 'frontend_cron_section', 'Setup time for cron', array(
			$this,
			'section_callback'
		), 'studio24_preview' );
	}

	public function section_callback( $arguments ) {
		switch ( $arguments['id'] ) {
			case 'frontend_url_section':
				echo '<p>Here you can change the link that the preview page should redirect to.</p>';
				break;
			case 'frontend_cron_section':
				echo '<p>A cron job is running to clean up the unused tokens in the database. Here you can adjust the time between cleanups. The default is 1 hour.</p>';
		}
	}

	public function setup_fields() {
		add_settings_field( 'frontend_url_field', 'URL', array(
			$this,
			'url_field_callback'
		), 'studio24_preview', 'frontend_url_section' );
		add_settings_field( 'frontend_cron_field', 'CRON', array(
			$this,
			'cron_field_callback'
		), 'studio24_preview', 'frontend_cron_section' );
		register_setting( 'studio24_preview', 'frontend_url_field' );
		register_setting( 'studio24_preview', 'frontend_cron_field' );
	}

	public function url_field_callback( $arguments ) {
		echo '<input name="frontend_url_field" id="frontend_url_field" type="url" value="' . get_option( 'frontend_url_field' ) . '" />';
	}

	public function cron_field_callback( $arguments ) {
		$cron_field = get_option( 'frontend_cron_field' );
		$value      = $cron_field && $cron_field > 0 ? $cron_field : 1;
		echo '<input name="frontend_cron_field" id="frontend_cron_field" min=1 type="number" value="' . $value . '" />';
	}

	public static function getInstance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
}