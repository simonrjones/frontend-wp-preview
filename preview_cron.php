<?php
/**
 * Created by PhpStorm.
 * User: bdeboevere
 * Date: 2019-02-28
 * Time: 14:24
 */

function clean_up_tokens()
{
    global $wpdb;
    $time = time();
    $tokens = $wpdb->get_results("select * from {$wpdb->prefix}studio24_preview_tokens");
    error_log("OMG i'm in the cron job", 0);
    foreach ($tokens as $token) {
        $diff = date("H", $time - $token->creation_time);
        if ($diff >= 1) { // todo setting, now 1 hour
            error_log("Deleting...");
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