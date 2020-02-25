<?php
/**
 * Plugin Name: WP News Importer
 * Description: A simple WP plugin to import news from NewsAPI.org
 * Version: 0.1
 * Author: Jakob Renpening
 * Text Domain: wp-news-importer
 */


require __DIR__ . '/vendor/autoload.php';

use WPNewsImporter\News;
use WPNewsImporter\AdminMenu;

News::register();
AdminMenu::register();