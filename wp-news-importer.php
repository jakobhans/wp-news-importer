<?php
/**
 * Plugin Name: WP News Importer
 * Description: A simple WP plugin to get me into a new project interview
 * Version: 0.1
 * Author: Jakob Renpening
 * Text Domain: wp-news-importer
 */


require __DIR__ . '/vendor/autoload.php';

use WPNewsImporter\News;
use WPNewsImporter\AdminMenu;

News::register();
AdminMenu::register();