<?php

namespace Broskees\GutenbergTwSafelist;

use Roots\Acorn\Console\Commands\Command;

class TailwindUpdateDbCommand extends Command
{
    public static function handle()
    {
        $saved_version = (int) get_site_option('tw_classes_db_version');

        if ($saved_version < 100 && self::upgrade100()) {
            update_site_option('tw_classes_db_version', 100);
        }
    }

    private static function upgrade100()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE `{$wpdb->base_prefix}tw_classes` (
            class_id int NOT NULL primary key AUTO_INCREMENT,
            class_name varchar(191) NOT NULL,
            post_id bigint(20) UNSIGNED NOT NULL
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        $success = empty($wpdb->last_error);

        return $success;
    }
}
