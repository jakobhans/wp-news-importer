<?php
namespace WPNewsImporter;

class AdminMenu
{
    public static function register()
    {
        $instance = new self;
        add_action('admin_menu', [$instance, 'registerOptionsView']);
        add_action('admin_post_import_news', [$instance, 'runImportNews']);
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
            'NewsAPI.org key',
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
        <input name="Submit" type="submit" value="Save API key" />
        </form>
        <h2>Import News</h2>
        <form action="<?= admin_url('admin-post.php') ?>" method="POST">
            <input type="hidden" name="action" value="import_news">
            <input type="hidden" name="news-fetch-url" value="http://newsapi.org/v2/everything?q=bitcoin&from=2020-01-25&sortBy=publishedAt&apiKey=<?php echo $options['news-api-key-field']; ?>">
            <input name="Submit" type="submit" value="Import Now" />
        </form>
        <?php
    }

    public function licenseKeyFieldCallback()
    {
        $options = get_option('news-api-key');
        ?>
        <input type="text" name="news-api-key[news-api-key-field]" size="40" value="<?=$options['news-api-key-field']?>">
        <?php
    }

    public function runImportNews()
    {
        $requestUrl = $_POST['news-fetch-url'];
        $data = wp_remote_get($requestUrl);
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
                        'post_content' => $article->content,
                        'tax_input' => [$article->source->name],
                        'meta_input' => [
                            'Author' => $article->author,
                            'URL' => $article->url,
                        ]
                    ];

                    if (!is_null($article->summary)) {
                        $post_options['post_excerpt'] = $article->summary;
                    }

                    wp_insert_post(
                        $post_options,
                        true
                    );
                }
            }
        }
    }

    private function checkDuplicateArticle($article)
    {
        $articlesArray = get_page_by_title($article->title, ARRAY_A, 'news');

        return !is_null($articlesArray);
    }
}