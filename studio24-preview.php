<?php
/**
 * Plugin Name: Preview - Studio 24
 * Description: This plugin makes it possible to preview changes in a decoupled environment.
 * Author: <a href="https://www.studio24.net">Studio 24</a>
 * Version: 0.0.1
 */

require_once 'includes/Studio24_Preview.php';


function studio24_preview_activate()
{
    $studio24_preview = Studio24_Preview::getInstance();
    $studio24_preview->activate();
}

function studio24_preview_deactivate()
{
    $studio24_preview = Studio24_Preview::getInstance();
    $studio24_preview->deactivate();
}

function add_plugin_page_settings_link( $links )
{
    $links = array_merge(
        $links, array(
        '<a href="' . esc_url(admin_url('/admin.php?page=studio24_preview')) . '">' . __('Settings', 'textdomain') . '</a>'
        ) 
    );

    return $links;
}

register_activation_hook(__FILE__, "studio24_preview_activate");
register_deactivation_hook(__FILE__, "studio24_preview_deactivate");

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'add_plugin_page_settings_link');
