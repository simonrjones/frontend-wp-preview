<?php

class Studio24_Preview_Endpoints
{

    static $instance = false;

    private function __construct()
    {
        $this->register_endpoints();
    }

    private function register_endpoints()
    {
        add_action(
            'rest_api_init', function () {
                register_rest_route(
                    'preview-studio-24/v1', '/new', array(
                    'methods'  => 'GET',
                    'callback' => array($this, 'generate_token_and_redirect'),
                    'args'     => [ 'post_id', 'post_type' ]
                    ) 
                );
            } 
        );

        add_action(
            'rest_api_init', function () {
                register_rest_route(
                    'preview-studio-24/v1', '/revision/(?P<token>[\d\w]+)', array(
                    'methods'  => 'GET',
                    'callback' => array($this, 'get_latest_revision'),
                    'args'     => [ 'token' ]
                    ) 
                );
            } 
        );
    }

    function get_latest_revision( $request )
    {
        $token = $request["token"];

        global $wpdb;

        $parent_post_id = $wpdb->get_results(
            $wpdb->prepare(
                "select parent_post_id from {$wpdb->prefix}studio24_preview_tokens where token_id = %s", $token
            ), OBJECT 
        );

        if (count($parent_post_id) === 0 ) {
            return new WP_Error('token_not_found', 'Invalid token id', array( 'status' => 404 ));
        } else {
            // Delete token when fetched.
            $wpdb->delete(
                "{$wpdb->prefix}studio24_preview_tokens", array(
                "token_id" => $token
                ) 
            );
        }

        $parent_post_id = end($parent_post_id)->parent_post_id;

        $revisions = $wpdb->get_results(
            $wpdb->prepare(
                "select ID from {$wpdb->prefix}posts where post_parent = %d and post_type = 'revision'", intval($parent_post_id)
            ), OBJECT 
        );

        if (count($revisions) === 0 ) {
            $post = get_post($parent_post_id);
            if ($post ) {
                return $post;
            } else {
                return new WP_Error('post_not_found', 'Invalid post id', array( 'status' => 404 ));
            }
        } else {
            $last_revision = end($revisions);
            $last_revision = get_post($last_revision->ID);

            return $last_revision;
        }
    }

    function generate_token_and_redirect( $request )
    {
        global $wpdb;

        $post_id            = $request['post_id'];
        $expected_post_type = $request['post_type'];

        $actual_post_type = get_post_type($post_id);

        if (! $actual_post_type ) {
            return new WP_Error('post_not_found', 'Invalid post id', array( 'status' => 404, 'text' => 'Not Found' ));
        }
        if ($actual_post_type !== $expected_post_type ) {
            return new WP_Error(
                'post_type_not_matching', 'Invalid post type', array(
                'status' => 400,
                'text'   => 'Bad Request'
                ) 
            );
        }

        $token = bin2hex(random_bytes(32));

        $preview_url = get_option('studio24_preview_frontend_url_field') . "/" . $token; // todo check if trailing slash is in frontend_url

        $preview_url_with_args = add_query_arg(
            [
            'post_type' => $actual_post_type
            ],
            $preview_url
        );

        $wpdb->insert(
            $wpdb->prefix . "studio24_preview_tokens", array(
            "token_id"       => $token,
            "parent_post_id" => $post_id,
            "creation_time"  => time()
            ) 
        );
        header("Location: " . $preview_url_with_args);
        exit();
    }

    public static function getInstance()
    {
        if (! self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

}