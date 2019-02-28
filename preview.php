<?php
/**
 * Plugin Name: Preview - Studio 24
 * Description: This plugin makes it possible to preview changes in a decoupled environment.
 * Author: Brian Dendauw, Ben De Boevere
 * Version: 0.0.1
 * @throws Exception
 */

function change_preview_link($link)
{
    global $wpdb;

    $token = bin2hex(random_bytes(32));

    $domain = "http://localhost:5000/"; // todo get this from settings

    $post_id = get_the_ID();

    $wpdb->insert($wpdb->prefix . "studio24_preview_tokens", array(
        "token_id" => $token,
        "parent_post_id" => $post_id,
        "creation_time" => time()
    ));

    $args = array(
        'token' => $token,
        'preview' => 'true'
    );

    return add_query_arg($args, $domain);
}


function get_latest_revision($request)
{
    $token = $request["token"];

    global $wpdb;

    $parent_post_id = $wpdb->get_results($wpdb->prepare(
        "select parent_post_id from {$wpdb->prefix}studio24_preview_tokens where token_id = %s", $token
    ), OBJECT);

    if (count($parent_post_id) === 0) {
        return new WP_Error( 'token_not_found', 'Invalid token id', array( 'status' => 404 ) );
    } else {
        $wpdb->delete("{$wpdb->prefix}studio24_preview_tokens", array(
            "token_id" => $token
        ));
    }

    $parent_post_id = end($parent_post_id)->parent_post_id;

    $revisions = $wpdb->get_results($wpdb->prepare(
        "select ID from {$wpdb->prefix}posts where post_parent = %d and post_type = 'revision'", intval($parent_post_id)
    ), OBJECT);

    if (count($revisions) === 0) {
        $post = get_post($parent_post_id);
        if ($post) {
            return $post;
        } else {
            return new WP_Error( 'post_not_found', 'Invalid post id', array( 'status' => 404 ) );
        }
    } else {
        $last_revision = end($revisions);
        $last_revision = get_post($last_revision->ID);
        return $last_revision;
    }
}


add_action('rest_api_init', function () {
    register_rest_route('preview-studio-24/v1', '(?P<token>[\d\w]+)', array(
        'methods' => 'GET',
        'callback' => 'get_latest_revision',
        'args' => ['token']
    ));
});


function clean_up_tokens($time) {
    global $wpdb;
    $tokens = $wpdb->get_results("select * from {$wpdb->prefix}studio24_preview_tokens");
    foreach ($tokens as $token) {
        $diff = date("H", $time-$token->creation_time);
        if ($diff >= 1) { // todo setting, now 1 hour
            $wpdb->delete("{$wpdb->prefix}studio24_preview_tokens", array(
                "token_id" => $token->token_id
            ));
        }
    }
}

function create_token_table() {
    global $wpdb;
    global $charset_collate;
    $query = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "studio24_preview_tokens (
        token_id VARCHAR(255) NOT  NULL,
        parent_post_id INT NOT NULL,
        creation_time VARCHAR(25) NOT NULL,
        PRIMARY KEY  (token_id)
    ) $charset_collate;";
    require_once(ABSPATH . "wp-admin/includes/upgrade.php");
    dbDelta($query);

    wp_schedule_event(time(), 'hourly', 'clean_up_tokens', array(time()));
}

function delete_token_table() {
    global $wpdb;
    $query = "DROP TABLE IF EXISTS " . $wpdb->prefix . "studio24_preview_tokens;";
    $wpdb->query($query);
}

register_activation_hook( __FILE__, "create_token_table" );

add_filter('the_preview', 'do_something');

add_filter('preview_post_link', 'change_preview_link');

register_deactivation_hook(__FILE__, "delete_token_table");
