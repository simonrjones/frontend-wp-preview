<?php

if (! class_exists("Studio24_Preview") ) {
    class Studio24_Preview
    {

        /**
         * @var bool
         */
        static $instance = false;
        /**
         * @var null
         */
        protected $activator = null;
        /**
         * @var null
         */
        protected $settings = null;
        /**
         * @var null
         */
        protected $endpoints = null;

        /**
         * Studio24_Preview constructor.
         */
        private function __construct()
        {
            $this->load_dependencies();
            $this->define_action_hooks();
            $this->define_filter_hooks();
        }

        /**
         *
         */
        public function load_dependencies()
        {
            include_once plugin_dir_path(dirname(__FILE__)) . 'includes/Studio24_Preview_Activator.php';
            $this->activator = Studio24_Preview_Activator::getInstance();
            include_once plugin_dir_path(dirname(__FILE__)) . 'includes/Studio24_Preview_Settings.php';
            $this->settings = Studio24_Preview_Settings::getInstance();
            include_once plugin_dir_path(dirname(__FILE__)) . 'includes/Studio24_Preview_Endpoints.php';
            $this->endpoints = Studio24_Preview_Endpoints::getInstance();
        }

        /**
         *
         */
        public function activate()
        {
            error_log("Base name in class file: " . plugin_basename(__FILE__));
            $this->activator->activate();
        }

        /**
         *
         */
        public function deactivate()
        {
            $this->activator->deactivate();
        }

        /**
         *
         */
        public function define_action_hooks()
        {
            add_action('add_meta_boxes', array( $this, 'studio24_preview_editor_metabox' ));
            add_action('admin_notices', array( $this, 'studio24_preview_admin_notice__error' ));
        }

        /**
         *
         */
        public function define_filter_hooks()
        {
            add_filter(
                'plugin_action_links_' . plugin_basename(__FILE__), array(
                $this,
                'add_plugin_page_settings_link'
                )
            );
            add_filter('preview_post_link', array( $this, 'change_preview_link' ));
        }

        /**
         *
         */
        function studio24_preview_admin_notice__error()
        {
            if (! $this->inOverview() && ! $this->inEditor() ) {
                return;
            }

            $fontend_url = get_option('frontend_url_field');
            $class       = 'notice is-dismissible';
            $message     = '';

            if (! $fontend_url || empty($fontend_url) ) {
                $class   .= ' notice-error';
                $message = "Studio24 Preview: There is no url set for the headless front-end.";
            } else if (filter_var($fontend_url, FILTER_VALIDATE_URL) === false ) {
                $class   .= ' notice-warning';
                $message = "Studio24 Preview: The url provided is not valid!";
            }
            if (! empty($message) ) {
                printf('<div class="%1$s"><p>%2$s</p><p>You can fix this <a target="_blank" href="' . esc_url(admin_url('/admin.php?page=studio24_preview')) . '">here</a>.</p></div>', esc_attr($class), esc_html($message));
            }
        }

        /**
         *
         */
        public function change_preview_link()
        {
            if ($this->inOverview() ) {
                add_filter(
                    'post_row_actions', function ( $actions, $post ) {
                        if (get_post_status($post) != 'publish' ) {
                            $actions['headless-preview'] = "<a target=\"_blank\"  href='" . $this->get_new_token_url() . "'>Headless preview</a>";
                        }

                        return $actions;
                    }, 10, 2
                );
            }
        }

        // register the meta box

        /**
         *
         */
        function studio24_preview_editor_metabox()
        {
            add_meta_box(
                'headless-preview-options-box',
                'Headless preview',
                array( $this, 'headless_preview_options_box' ),
                '',         // all post types & pages
                'side',
                'high'
            );
        }

        /**
         *
         */
        function headless_preview_options_box()
        {
            $front_end_url = get_option("frontend_url_field");

            $html = '<div id="major-publishing-actions" style="overflow:hidden; text-align: center">';
            $html .= '<div id="publishing-action">';
            $html .= '<a class="preview button" target="_blank" href="';
            $html .= $this->get_new_token_url();
            $html .= '" id="headless-preview">Headless preview<span class="screen-reader-text">(opens in a new tab)</span></a>';
            $html .= '</div></div>';
            $html .= '<div class="preview-plugin-sidebar-info-content">';
            $html .= '<p class="preview-sidebar-header">Settings</p>';
            $html .= '<a class="components-external-link" href="';
            $html .= esc_url(admin_url('/admin.php?page=studio24_preview'));
            $html .= '" target="_blank" rel="external noreferrer noopener">Plugin settings<span class="screen-reader-text">(opens in a new tab)</span></a>';
            $html .= '<p>Current frontend url: <a class="components-external-link" href="' . $front_end_url . '" target="_blank" rel="external noreferrer noopener">';
            $html .= $front_end_url;
            $html .= '<span class="screen-reader-text">(opens in a new tab)</span></a></p></div>';
            echo $html;
        }

        /**
         * @return string
         */
        private function get_new_token_url()
        {
            global $post;
            $base_url = get_bloginfo('url');

            return $base_url . '/wp-json/preview-studio-24/v1/new?post_id=' . $post->ID . '&post_type=' . $post->post_type;
        }

        /**
         * @return int
         */
        private function inOverview()
        {
            global $pagenow;

            return ( in_array($pagenow, array( "edit.php" )) ) ? 1 : 0;
        }

        /**
         * @return int
         */
        private function inEditor()
        {
            global $pagenow;

            return ( ( isset($_GET['action']) && $_GET['action'] === 'edit' ) || in_array(
                $pagenow, array(
                'post.php',
                'post-new.php'
                )
            ) ) ? 1 : 0;
        }

        /**
         * @return bool|Studio24_Preview
         */
        public static function getInstance()
        {
            if (! self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;
        }
    }
}

$Studio24_Preview = Studio24_Preview::getInstance();
