<?php

namespace Broskees\GutenbergTwSafelist;

use Roots\Acorn\Application;

class GutenbergTwSafelist
{
    /**
     * The Application instance.
     *
     * @var \Roots\Acorn\Application
     */
    protected $app;

    /**
     * The GutenbergTwSafelist configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Create a new Poet instance.
     *
     * @param  Roots\Acorn\Application $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $this->app->config->get('tw_version');

        add_action('post_updated', function ($post_id, $post_after) {
            $classes = $this->getClasses($post_after);

            $classes = $this->filterClasses($classes);

            $updated = $this->updateTwPostMeta($post_id, $classes);

            if (!$updated) {
                return;
            }

            $classes = $this->updateAndFetchTwDbTable($post_id, $classes);

            $this->buildAssets($classes);
        }, apply_filters('tw_safelist_action_priority', 10), 2);
    }

    private function getClasses(\WP_Post $post): array
    {
        preg_match_all(
            '/class="(-?[_a-zA-Z]+[_a-zA-Z0-9-:]* ?)+"/',
            $post->post_content,
            $class_strings
        );

        if (empty($class_strings[0])) {
            return [];
        }

        $classes = [];
        array_map(function ($string) use (&$classes) {
            $string = str_replace('class=', '', $string);

            $string = str_replace('"', '', $string);

            $classes = array_unique([...$classes, ...explode(' ', $string)]);
        }, $class_strings[0]);

        asort($classes);

        return $classes;
    }

    /**
     * @todo Eventually this method should filter for only tailwind classes with Regex
     * @todo Filtering should be tailwind verison specific
     */
    private function filterClasses(array $classes): array
    {
        return array_filter(
            array_map(function ($class) {
                if (str_starts_with('wp-', $class)) {
                    return false;
                }

                return $class;
            }, $classes)
        );
    }

    private function updateTwPostMeta(int $post_id, array $classes): bool
    {
        $base64_classes_after = base64_encode(implode(' ', $classes));
        $pm_result = get_post_meta($post_id, 'post_content_classes');
        $base64_classes_before = ($pm_result ? $pm_result[0] : '');

        if ($base64_classes_before == $base64_classes_after) {
            return false;
        }

        if (update_post_meta($post_id, 'post_content_classes', $base64_classes_after)) {
            return true;
        }

        throw new Exception('Update Failed');
    }

    private function updateAndFetchTwDbTable(int $post_id, array $classes): array
    {
        global $wpdb;

        $wpdb->delete("{$wpdb->base_prefix}tw_classes", [
            'post_id' => $post_id
        ]);

        foreach ($classes as $class) {
            $wpdb->insert("{$wpdb->base_prefix}tw_classes", [
                'class_name' => $class,
                'post_id' => $post_id
            ]);
        }

        $classes = $wpdb->get_results("
            SELECT DISTINCT class_name FROM {$wpdb->base_prefix}tw_classes
        ");

        if (!empty($classes)) {
            return array_map(function ($obj) {
                return $obj->class_name;
            }, $classes);
        }

        return $classes;
    }

    private function buildAssets(array $classes): void
    {
        $classes_base64 = base64_encode(implode(' ', $classes));
        file_put_contents(get_stylesheet_directory() . '/gutenberg-classes.txt', $classes_base64);
        shell_exec('cd ' . get_stylesheet_directory() . ' && yarn build:prod');
    }
}
