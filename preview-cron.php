<?php
/**
 * Created by PhpStorm.
 * User: bdeboevere
 * Date: 2019-02-28
 * Time: 14:24
 */

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

function clean_up_tokens()
{
    global $wpdb;
    $time = time();
    $tokens = $wpdb->get_results("select * from {$wpdb->prefix}studio24_preview_tokens");
    foreach ($tokens as $token) {
        $diff = date("H", $time - $token->creation_time);
        $cron_setting_time = get_option("frontend_cron_field");
        $cron_setting_time = $cron_setting_time && $cron_setting_time > 0 ? $cron_setting_time : 1;
        error_log("this is the cron setting time: {$cron_setting_time}... ");
        if ($diff >= 1) { // todo setting, now 1 hour
            $wpdb->delete("{$wpdb->prefix}studio24_preview_tokens", array(
                "token_id" => $token->token_id
            ));
        }
    }
}

// add custom interval
function cron_add_minute($schedules)
{
    // Adds once every minute to the existing schedules.
    $schedules['everyminute'] = array(
        'interval' => 60,
        'display' => __('Once Every Minute')
    );
    return $schedules;
}

add_filter('cron_schedules', 'cron_add_minute');

add_action('cleanup_tokens_in_db', 'clean_up_tokens');