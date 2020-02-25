<?php

namespace WPNewsImporter;

use WP_Error;

class AdminMenu
{
    public static function register()
    {
        $instance = new self;
        add_action('admin_menu', [$instance, 'registerOptionsView']);
        add_action('admin_post_import_news', [$instance, 'runImportNews']);
        add_action('admin_post_show_news', [$instance, 'runShowNews']);
        add_action('admin_post_show_news_for_taxonomy', [$instance, 'runShowNewsForTaxonomy']);
    }

    public function registerOptionsView()
    {
        $instance = new self;
        add_menu_page(
            'WP News Importer',
            'WP News Importer Options',
            'manage_options',
            'wp-news-importer-options',
            [$instance, 'renderWPNewsImporterOptions']
        );

        register_setting(
            'news-api-key',
            'news-api-key'
        );

        add_settings_section(
            'wp-news-importer-main-settings',
            'Main Settings',
            null,
            'wp-news-importer-options'
        );

        add_settings_field(
            'news-api-key-field',
            'NewsAPI.org URL',
            [$instance, 'licenseKeyFieldCallback'],
            'wp-news-importer-options',
            'wp-news-importer-main-settings'
        );
    }

    public function renderWPNewsImporterOptions()
    {
        $options = get_option('news-api-key');
?>
        <h1>WP News Importer</h1>
        <form action="options.php" method="POST">
            <?php settings_fields('news-api-key'); ?>
            <?php do_settings_sections('wp-news-importer-options'); ?>
            <input name="Submit" type="submit" value="Save API route" />
        </form>
        <h2>Import News</h2>
        <?php
        if (isset($_GET['import_success'])) {
            if ($_GET['import_success'] === 'true') {
                echo '<h4 style="color: green;">Import was successfully completed.</h4>';
            } else {
                echo '<h4 style="color: red;">There was an error while importing content.</h4><h4>Error: ' . urldecode($_GET['import_error']) . '</h4>';
            }
        }
        ?>
        <form action="<?= admin_url('admin-post.php') ?>" method="POST">
            <input type="hidden" name="action" value="import_news">
            <input type="hidden" name="news-fetch-url" value="<?php echo $options['news-api-key-field']; ?>">
            <input name="Submit" type="submit" value="Import Now" />
        </form>
        <h2>Show All News</h2>
        <form action="<?= admin_url('admin-post.php') ?>" method="POST">
            <input type="hidden" name="action" value="show_news">
            <input name="Submit" type="submit" value="Show all News" />
        </form>
        <h2>Show News by Source</h2>
        <?php
        $terms = get_terms('news_source');
        foreach ($terms as $term) {
        ?>
            <form action="<?= admin_url('admin-post.php') ?>" method="POST">
                <input type="hidden" name="action" value="show_news_for_taxonomy">
                <input type="hidden" name="taxonomy" value="<?= $term->name ?>">
                <input name="Submit" type="submit" value="<?= $term->name ?>" />
            </form>
        <?php
        }
        ?>

    <?php
    }

    public function licenseKeyFieldCallback()
    {
        $options = get_option('news-api-key');
    ?>
        <input type="text" name="news-api-key[news-api-key-field]" size="40" value="<?= $options['news-api-key-field'] ?>">
<?php
    }

    public function runImportNews()
    {
        $requestUrl = $_POST['news-fetch-url'];
        try {
            $data = wp_remote_get($requestUrl);
        } catch (WP_Error $error) {
            wp_redirect($_SERVER["HTTP_REFERER"] . '&import_success=false&import_error=' . urlencode($error->get_error_message()));
            exit;
        }
        $body = json_decode($data['body']);
        if ($body->status === 'ok') {
            foreach ($body->articles as $article) {
                if (!$this->checkDuplicateArticle($article)) {
                    $taxonomySlug = strtolower($article->source->name);
                    if (!taxonomy_exists($article->source->name)) {
                        wp_insert_term(
                            $article->source->name,
                            'news_source',
                            [
                                'slug' => $taxonomySlug
                            ]
                        );
                    }
                    $post_options = [
                        'post_title' => $article->title,
                        'post_type' => 'news',
                        'post_date' => $article->publishedAt,
                        'post_status' => 'publish',
                        'post_content' => $article->content,
                        'tax_input' => [
                            'news_source' => [
                                $article->source->name
                            ]
                        ],
                        'meta_input' => [
                            'URL' => $article->url,
                        ]
                    ];

                    if (!is_null($article->summary)) {
                        $post_options['post_excerpt'] = $article->summary;
                    }

                    if (!is_null($article->author) && $article->author !== '') {
                        $post_options['meta_input']['Author'] = $article->author;
                    }

                    wp_insert_post(
                        $post_options,
                        true
                    );
                }
            }
        } elseif ($body->status === 'error') {
            wp_redirect($_SERVER["HTTP_REFERER"] . '&import_success=false&import_error=' . urlencode($body->message));
            exit;
        }
        wp_redirect($_SERVER["HTTP_REFERER"] . '&import_success=true');
        exit;
    }

    private function checkDuplicateArticle($article)
    {
        global $wpdb;

        $articles_query = $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_title = %s AND post_type = 'news' AND post_status = 'publish'", $article->title);
        $articles = $wpdb->get_results($articles_query);

        return count($articles) > 0;
    }

    public function runShowNews()
    {
        global $wpdb;

        $articles_query = $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = 'news' AND post_status = 'publish'");
        $articles = $wpdb->get_results($articles_query);

        foreach ($articles as $article) {
        }

        var_dump($articles);
        die();
    }

    public function runShowNewsForTaxonomy()
    {
        $taxonomy = $_POST['taxonomy'];

        $posts = get_posts([
            'post_type' => 'news',
            'post_status' => 'publish',
            'numberposts' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'news_source',
                    'field' => 'name',
                    'terms' => $taxonomy,
                    'include_children' => FALSE
                ]
            ]
        ]);
        echo '<h1>Source: ' . $taxonomy . '</h1>';
        var_dump($posts);
        die();
    }
}
