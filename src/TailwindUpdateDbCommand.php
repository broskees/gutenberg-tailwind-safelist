<?php

namespace Broskees\GutenbergTwSafelist;

use Roots\Acorn\Console\Commands\Command;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;

class TailwindUpdateDbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = <<<SIGNATURE
    acorn updatetwdb
    SIGNATURE;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the DB Table used to create the Tailwind Safelist.';

    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Contracts\Foundation\Application $app
     * @return void
     */
    public function __construct(Filesystem $files, Application $app)
    {
        parent::__construct();

        $this->app = $app;
        $this->files = $files;
    }

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
