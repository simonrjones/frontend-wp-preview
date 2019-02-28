<?php


add_action( 'admin_menu','create_plugin_settings_page' );

function create_plugin_settings_page(){
    $page_title = "Studio 24 - Preview Page";
    $menu_title = 'Preview Page';
    $capability = 'manage_options';
    $slug = 'studio24_preview';
    $callback = 'plugin_settings_page_content';
    $icon = 'dashicons-admin-plugins';
    $position = 100;

//    add_submenu_page( 'options-general.php', $page_title, $menu_title, $capability, $slug, $callback );
    add_menu_page($page_title, $menu_title, $capability, $slug, $callback, $icon, $position);
}

function plugin_settings_page_content(){
    ?>
    <div class="wrap">
        <h2>Studio 24 - Preview Plugin</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('studio24_preview');
            do_settings_sections('studio24_preview');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'setup_sections');

function setup_sections(){
    add_settings_section('frontend_url_section', 'Setup frontend URL', 'section_callback', 'studio24_preview');
}

function section_callback($arguments){
    switch($arguments['id']){
        case 'frontend_url_section':
            echo 'Here you can change the link that the preview page should redirect to.';
            break;
    }
}

add_action('admin_init', 'setup_fields');

function setup_fields(){
    add_settings_field('frontend_url_field', 'URL', 'field_callback', 'studio24_preview', 'frontend_url_section');

}

function field_callback($arguments){
    echo '<input name="frontend_url_field" id="frontend_url_field" type="text" value="' . get_option( 'frontend_url_field' ) . '" />';
    register_setting( 'studio24_preview', 'frontend_url_field');
}