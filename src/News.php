<?php
namespace WPNewsImporter;

class News {
    public static function register()
    {
        $instance = new self;
        add_action('init', [$instance, 'registerPostType']);
    }

    public function registerPostType()
    {
        register_post_type(
            'news',
            [
                'label' => 'News',
                'description' => 'News post type',
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true
            ]
        );

        register_taxonomy(
            'news_source',
            'news',
            [
                'labels' => [
                    'name' => 'News Sources',
                    'singular_name' => 'News Source',
                ],
                'public' => TRUE
            ]
        );

        flush_rewrite_rules();
    }

    public function apiKeyMetabox()
    {
        echo 'Holi';
    }
}
