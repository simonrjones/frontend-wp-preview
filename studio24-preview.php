<?php
/**
 * Plugin Name: Preview - Studio 24
 * Description: This plugin makes it possible to preview changes in a decoupled environment.
 * Author: <a href="https://www.studio24.net">Studio 24</a>
 * Version: 0.0.1
 */

include_once "preview-cron.php";
include_once 'preview-settings.php';
include_once 'preview-endpoints.php';

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

//add_action( 'in_admin_header', 'change_preview_link' );

add_filter( 'preview_post_link', 'change_preview_link' );

function change_preview_link() {
	global $pagenow;

	$inOverview = ( in_array( $pagenow, array( "edit.php" ) ) ) ? 1 : 0;

	$inEditor = ( ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) || in_array( $pagenow, array(
			'post.php',
			'post-new.php'
		) ) ) ? 1 : 0;

	if ( ! $inOverview && ! $inEditor ) {
		return;
	}

	if ( $inOverview ) {
		add_filter( 'post_row_actions', function ( $actions, $post ) {
			if ( get_post_status( $post ) != 'publish' ) {
				$actions['headless-preview'] = "<a target=\"_blank\"  href='" . get_new_token_url() . "'>Headless preview</a>";
			}

			return $actions;
		}, 10, 2 );
	}
}

// register the meta box
add_action( 'add_meta_boxes', 'my_custom_field_checkboxes' );
function my_custom_field_checkboxes() {
	add_meta_box(
		'headless-preview-options-box',
		'Headless preview',
		'headless_preview_options_box',
		'',         // all post types
		'side',
		'high'
	);
}

// display the metabox
function headless_preview_options_box() {
	$base_url      = get_bloginfo( 'url' );
	$front_end_url = get_option( "frontend_url_field" );

	$html = '<div id="major-publishing-actions" style="overflow:hidden; text-align: center">';
	$html .= '<div id="publishing-action">';
	$html .= '<a class="preview button" target="_blank" href="';
	$html .= get_new_token_url();
	$html .= '" id="headless-preview">Headless preview<span class="screen-reader-text">(opens in a new tab)</span></a>';
	$html .= '</div></div>';
	$html .= '<div class="preview-plugin-sidebar-info-content">';
	$html .= '<p class="preview-sidebar-header">Settings</p>';
	$html .= '<a class="components-external-link" href="';
	$html .= $base_url;
	$html .= '/wp-admin/admin.php?page=studio24_preview" target="_blank" rel="external noreferrer noopener">Plugin settings<span class="screen-reader-text">(opens in a new tab)</span></a>';
	$html .= '<p>Current frontend url: <a class="components-external-link" href="' . $front_end_url . '" target="_blank" rel="external noreferrer noopener">';
	$html .= $front_end_url;
	$html .= '<span class="screen-reader-text">(opens in a new tab)</span></a></p></div>';
	echo $html;
}

function get_new_token_url() {
	global $post;
	$base_url = get_bloginfo( 'url' );

	return $base_url . '/wp-json/preview-studio-24/v1/new?post_id=' . $post->ID . '&post_type=' . $post->post_type;
}

