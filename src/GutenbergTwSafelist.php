<?php

namespace Broskees\GutenbergTwSafelist;

use Roots\Acorn\Application;
use Illuminate\Support\Facades\Cache;

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

        add_action('save_post', function ($post_id, $post) {
            $renderedContent = $this->getRenderedContent($post);

            $classes = $this->getClasses($renderedContent);

            $classes = $this->filterClasses($classes);

            $updated = $this->updateTwPostMeta($post_id, $classes);

            if (!$updated) {
                return;
            }

            $classes = $this->updateAndFetchTwDbTable($post_id, $classes);

            $this->buildAssets($classes);
        }, apply_filters('tw_safelist_action_priority', 10), 2);
    }

    private function getRenderedContent(\WP_Post $post): string
    {
        $post_content = $post->post_content;
        $blocks = parse_blocks($post_content);

        // not a gutenberg page, just return post content
        if (empty($blocks) || !isset($blocks[0]['blockName'])) {
            return $post_content;
        }

        $html = '';

        // get acf rendered block content
        if (class_exists('ACF')) {
            array_map_recursive(function ($block) use ($post, &$html) {
                if (!isset($block['blockName'])) {
                    return $block;
                }

                if (str_starts_with($block['blockName'], 'acf/')) {
                    $attrs = acf_prepare_block($block['attrs']);
                    $attrs['id'] = acf_ensure_block_id_prefix($post->ID);
                    acf_setup_meta($attrs['data'], $attrs['id'], true);
                    ob_start();
                    if (is_callable($attrs['render_callback'])) {
                        $attrs['render_callback']($attrs);
                    } else {
                        do_action('acf_block_render_template', $attrs, '', false, $post->ID, null, false);
                    }
                    $html .= ob_get_clean();
                    acf_reset_meta($attrs['id']);
                }

                return $block;
            }, $blocks);
        }

        // get rendered normal block content
        foreach ($blocks as $block) {
            $html .= render_block($block);
        }

        return $html;
    }


    private function getClasses(string $renderedContent): array
    {
        preg_match_all(
            '/(class="{1}([^"]+ ?)+?"{1}|class=\'{1}([^\']+ ?)+?\'{1})/',
            $renderedContent,
            $class_strings
        );

        $class_strings[2] = array_filter($class_strings[2]);
        $class_strings[3] = array_filter($class_strings[3]);

        if (empty($class_strings[2])
            && empty($class_strings[3])
        ) {
            return [];
        }

        $classes = [];
        array_map(function ($string) use (&$classes) {
            $classes = array_unique([...$classes, ...explode(' ', $string)]);
        }, [...$class_strings[2], ...$class_strings[3]]);

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

        if (add_post_meta($post_id, 'post_content_classes', $base64_classes_after)) {
            return true;
        }

        throw new \Exception('Update Failed');
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

        asort($classes);

        return $classes;
    }

    private function buildAssets(array $classes): void
    {
        $classes_base64 = base64_encode(implode(' ', $classes));
        file_put_contents(get_stylesheet_directory() . '/gutenberg-classes.txt', $classes_base64);

        // automatic builds (requires npm, nvm, node, & yarn on your server)
        if (file_exists(get_stylesheet_directory() . '/.nvmrc')
            && exec_enabled()
            && command_exists('node', 'npm', 'nvm', 'yarn')
        ) {
            \shell_exec('cd ' . get_stylesheet_directory() . ' && nvm use && yarn build 2>&1');
        }
    }
}
