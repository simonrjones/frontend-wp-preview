<?php

class Studio24_Preview_Activator
{

    static $instance = false;

    public function activate()
    {
        $this->setup_preview_db();
        $this->setup_preview_cron();
    }

    public function deactivate()
    {
        $this->cleanup_preview_after_deactivation();
    }

    public function setup_preview_db()
    {
        global $wpdb;
        global $charset_collate;
        $query = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "studio24_preview_tokens (
        token_id VARCHAR(255) NOT  NULL,
        parent_post_id INT NOT NULL,
        creation_time VARCHAR(25) NOT NULL,
        PRIMARY KEY  (token_id)
    ) $charset_collate;";
        include_once ABSPATH . "wp-admin/includes/upgrade.php";
        dbDelta($query);
    }

    public function setup_preview_cron()
    {
        if (! wp_next_scheduled('cleanup_tokens_in_db') ) {
            wp_schedule_event(time(), 'hourly', 'cleanup_tokens_in_db');
        }
    }

    public function clean_up_tokens()
    {
        global $wpdb;
        $time              = time();
        $tokens            = $wpdb->get_results("select * from {$wpdb->prefix}studio24_preview_tokens");
        $cron_setting_time = get_option("studio24_preview_frontend_cron_field");
        $cron_setting_time = $cron_setting_time && $cron_setting_time > 0 ? $cron_setting_time : 1;
        foreach ( $tokens as $token ) {
            $diff = date("H", $time - $token->creation_time);
            if ($diff >= $cron_setting_time ) {
                $wpdb->delete(
                    "{$wpdb->prefix}studio24_preview_tokens", array(
                    "token_id" => $token->token_id
                    ) 
                );
            }
        }
    }

    public function cleanup_preview_after_deactivation()
    {
        global $wpdb;
        $query = "DROP TABLE IF EXISTS " . $wpdb->prefix . "studio24_preview_tokens;";
        $wpdb->query($query);
        $plugin_options = $wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'studio24_preview_%'");

        foreach( $plugin_options as $option ) {
            delete_option($option->option_name);
        }
        
        // find out when the last event was scheduled
        $timestamp = wp_next_scheduled('cleanup_tokens_in_db');
        // unschedule previous event if any
        wp_unschedule_event($timestamp, 'cleanup_tokens_in_db');
    }


    public static function getInstance()
    {
        if (! self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}