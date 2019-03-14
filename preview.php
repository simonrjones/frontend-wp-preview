<?php
/**
 * Plugin Name: Preview - Studio 24
 * Description: This plugin makes it possible to preview changes in a decoupled environment.
 * Author: <a href="https://www.studio24.net">Studio 24</a>
 * Version: 0.0.1
 */

include_once "preview_cron.php";
include 'preview_settings.php';

function get_latest_revision( $request ) {
	$token = $request["token"];

	global $wpdb;

	$parent_post_id = $wpdb->get_results( $wpdb->prepare(
		"select parent_post_id from {$wpdb->prefix}studio24_preview_tokens where token_id = %s", $token
	), OBJECT );

	if ( count( $parent_post_id ) === 0 ) {
		return new WP_Error( 'token_not_found', 'Invalid token id', array( 'status' => 404 ) );
	} else {
		// Delete token when fetched.
		$wpdb->delete( "{$wpdb->prefix}studio24_preview_tokens", array(
			"token_id" => $token
		) );
	}

	$parent_post_id = end( $parent_post_id )->parent_post_id;

	$revisions = $wpdb->get_results( $wpdb->prepare(
		"select ID from {$wpdb->prefix}posts where post_parent = %d and post_type = 'revision'", intval( $parent_post_id )
	), OBJECT );

	if ( count( $revisions ) === 0 ) {
		$post = get_post( $parent_post_id );
		if ( $post ) {
			return $post;
		} else {
			return new WP_Error( 'post_not_found', 'Invalid post id', array( 'status' => 404 ) );
		}
	} else {
		$last_revision = end( $revisions );
		$last_revision = get_post( $last_revision->ID );

		return $last_revision;
	}
}

function setup_preview_db_cron() {
	global $wpdb;
	global $charset_collate;
	$query = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "studio24_preview_tokens (
        token_id VARCHAR(255) NOT  NULL,
        parent_post_id INT NOT NULL,
        creation_time VARCHAR(25) NOT NULL,
        PRIMARY KEY  (token_id)
    ) $charset_collate;";
	require_once( ABSPATH . "wp-admin/includes/upgrade.php" );
	dbDelta( $query );

	if ( ! wp_next_scheduled( 'cleanup_tokens_in_db' ) ) {
		wp_schedule_event( time(), 'hourly', 'cleanup_tokens_in_db' );
	}
}

function cleanup_preview_after_deactivation() {
	global $wpdb;
	$query = "DROP TABLE IF EXISTS " . $wpdb->prefix . "studio24_preview_tokens;";
	$wpdb->query( $query );
	// find out when the last event was scheduled
	$timestamp = wp_next_scheduled( 'cleanup_tokens_in_db' );
	// unschedule previous event if any
	wp_unschedule_event( $timestamp, 'cleanup_tokens_in_db' );
}

register_activation_hook( __FILE__, "setup_preview_db_cron" );

register_deactivation_hook( __FILE__, "cleanup_preview_after_deactivation" );

add_action( 'rest_api_init', function () {
	register_rest_route( 'preview-studio-24/v1', '(?P<token>[\d\w]+)', array(
		'methods'  => 'GET',
		'callback' => 'get_latest_revision',
		'args'     => [ 'token' ]
	) );
} );


add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'add_plugin_page_settings_link' );

function add_plugin_page_settings_link( $links ) {
	$links = array_merge( $links, array(
		'<a href="' . esc_url( admin_url( '/admin.php?page=studio24_preview' ) ) . '">' . __( 'Settings', 'textdomain' ) . '</a>'
	) );

	return $links;
}

add_action( 'in_admin_header', 'change_preview_link' );

add_filter( 'preview_post_link', 'change_preview_link' );


function updatePreviewToken() {
	change_preview_link();
}

function change_preview_link() {
	global $wpdb;
	global $post;
	global $pagenow;
	global $headless_preview_link;
	$inOverview = ( in_array( $pagenow, array( "edit.php" ) ) ) ? 1 : 0;

	$inEditor = ( ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) || in_array( $pagenow, array(
			'post.php',
			'post-new.php'
		) ) ) ? 1 : 0;
	global $current_screen;
	$isGutenbergEditor = ( ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() )
	                       || ( function_exists( 'is_gutenberg_page' ) ) && is_gutenberg_page() ) ? 1 : 0;

	$token = bin2hex( random_bytes( 32 ) );

	$url_from_option       = get_option( 'frontend_url_field' );
	$headless_preview_link = "{$url_from_option}/{$token}";

	$post_id = get_the_ID();

	$wpdb->insert( $wpdb->prefix . "studio24_preview_tokens", array(
		"token_id"       => $token,
		"parent_post_id" => $post_id,
		"creation_time"  => time()
	) );

	$args = array(
		"post_type" => $post->post_type
	);

	$headless_preview_link = add_query_arg( $args, $headless_preview_link );

	if ( $inEditor ) {
		if ( $isGutenbergEditor ) {
			// create sidebar
			?>
            <script>
                console.log("Updating the token || creating preview sidebar");
                addPreviewSidebar("<?php echo esc_url( admin_url( '/admin.php?page=studio24_preview' ) ); ?>", "<?php echo $headless_preview_link; ?>", "<?php echo $url_from_option; ?>");
            </script>
			<?php
		} else {
			// adding a new button for the normal editor happens in a different hook.
		}
	} elseif ( $inOverview ) {
		// add link to list
		add_filter( 'post_row_actions', function ( $actions, $post ) {
			global $headless_preview_link;
			if ( get_post_status( $post ) != 'publish' ) {
				$actions['headless-preview'] = "<a target=\"_blank\"  href='{$headless_preview_link}'>Headless preview</a>";
			}

			return $actions;
		}, 10, 2 );
	}
}

add_action( "save_post", "updatePreviewToken" );


function preview_sidebar_plugin_register() {
	wp_register_script(
		'preview-sidebar-js',
		plugins_url( 'preview-sidebar.js', __FILE__ ),
		array(
			'wp-plugins',
			'wp-edit-post',
			'wp-element',
			'wp-components'
		)
	);
	wp_register_style(
		'preview-sidebar-css',
		plugins_url( 'preview-sidebar.css', __FILE__ )
	);
}

add_action( 'init', 'preview_sidebar_plugin_register' );

function preview_sidebar_plugin_style_enqueue() {
	wp_enqueue_style( 'preview-sidebar-css' );
}

add_action( 'enqueue_block_assets', 'preview_sidebar_plugin_style_enqueue' );

function preview_sidebar_plugin_script_enqueue() {
	wp_enqueue_script( 'preview-sidebar-js' );
}

add_action( 'enqueue_block_editor_assets', 'preview_sidebar_plugin_script_enqueue' );

// register the meta box
add_action( 'add_meta_boxes', 'my_custom_field_checkboxes' );
function my_custom_field_checkboxes() {
	add_meta_box(
		'headless-preview-options-box',
		'Headless preview',
		'headless_preview_options_box',
		'',         // all
		'side',
		'high'
	);
}

// display the metabox
function headless_preview_options_box() {

	global $headless_preview_link;
	$html = '<div id="major-publishing-actions" style="overflow:hidden; text-align: center">';
	$html .= '<div id="publishing-action">';
	$html .= '<a class="preview button" target="_blank" href="' . $headless_preview_link . '" id="headless-preview">Headless preview<span class="screen-reader-text">(opens in a new tab)</span></a>';
	$html .= '</div>';
	$html .= '</div>';
	$html .= "<div class=\"preview-plugin-sidebar-info-content\">";
	$html .= "<p class=\"preview-sidebar-header\">Settings</p>";
	$html .= "<a class=\"components-external-link\" href=\"http://localhost/wordpress/wp-admin/admin.php?page=studio24_preview\" target=\"_blank\" rel=\"external noreferrer noopener\">Plugin settings<span class=\"screen-reader-text\">(opens in a new tab)</span></a>";
	$html .= "<p class=\"\">Current frontend url: <a class=\"components-external-link\" href=\"http://localhost:5000/preview\" target=\"_blank\" rel=\"external noreferrer noopener\">";
	$html .= "http://localhost:5000/preview<span class=\"screen-reader-text\">(opens in a new tab)</span></a></p></div>";
	echo $html;
}

