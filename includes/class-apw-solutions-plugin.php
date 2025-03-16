<?php
/**
 * Main Plugin Class
 *
 * The main class that coordinates all plugin functionality.
 *
 * @package APW_Solutions_Plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Plugin Class
 */
class APW_Solutions_Plugin {
    /**
     * Class instance.
     *
     * @var APW_Solutions_Plugin
     */
    private static $instance = null;

    /**
     * Logger instance.
     *
     * @var APW_Solutions_Logger
     */
    private $logger;

    /**
     * Example excerpt length for consistent card heights.
     *
     * @var string
     */
    private $excerpt_example = "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore";

    /**
     * Constructor.
     */
    private function __construct() {
        // Initialize logger if debugging is enabled
        if ( defined( 'APW_SOLUTIONS_DEBUG' ) && APW_SOLUTIONS_DEBUG && class_exists( 'APW_Solutions_Logger' ) ) {
            $this->logger = APW_Solutions_Logger::get_instance();
            $this->logger->info( 'Main plugin class initialized' );
        }
    }

    /**
     * Get class instance.
     *
     * @return APW_Solutions_Plugin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the plugin.
     */
    public function init() {
        $this->debug( 'Initializing plugin components' );

        // Initialize shortcodes (also handles AJAX functionality)
        $this->init_shortcodes();
    }

    /**
     * Initialize shortcodes.
     */
    private function init_shortcodes() {
        if ( class_exists( 'APW_Solutions_Shortcodes' ) ) {
            $shortcodes = APW_Solutions_Shortcodes::get_instance();
            $shortcodes->init();
            $this->debug( 'Shortcodes and AJAX handlers initialized' );
        } else {
            $this->debug( 'Shortcodes class not found', 'ERROR' );
        }
    }

    /**
     * Initialize AJAX handlers.
     */
    private function init_ajax() {
        if ( class_exists( 'APW_Solutions_AJAX' ) ) {
            $ajax = APW_Solutions_AJAX::get_instance();
            $ajax->init();
            $this->debug( 'AJAX handlers initialized' );
        } else {
            $this->debug( 'AJAX class not found', 'ERROR' );
        }
    }

    /**
     * Get solution categories.
     * Uses transient cache for performance.
     *
     * @return array Array of categories.
     */
    public function get_solution_categories() {
        $this->debug('Getting solution categories');

        // Check for cached categories
        $categories = get_transient('apw_solutions_categories_cache');

        if (false === $categories) {
            $categories = get_categories(array(
                'taxonomy' => 'category',
                'object_ids' => $this->get_solution_post_ids(),
                'orderby' => 'name',
                'order' => 'ASC',
                'hide_empty' => true,
            ));

            // Cache for 12 hours
            set_transient('apw_solutions_categories_cache', $categories, 12 * HOUR_IN_SECONDS);
            $this->debug('Categories fetched from database and cached');
        } else {
            $this->debug('Categories loaded from cache');
        }

        return $categories;
    }

    /**
     * Get all solution post IDs.
     *
     * @return array Array of post IDs.
     */
    private function get_solution_post_ids() {
        $args = array(
            'post_type' => 'solution',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        );

        $query = new WP_Query($args);
        return $query->posts;
    }

    /**
     * Get solutions by category.
     *
     * @param int|string $category_id Category ID or slug.
     * @return array Array of solution posts.
     */
    public function get_solutions_by_category($category_id = null) {
        $this->debug('Getting solutions' . ($category_id ? ' for category: ' . $category_id : ''));

        $args = array(
            'post_type' => 'solution',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        );

        if ($category_id) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'category',
                    'field' => is_numeric($category_id) ? 'term_id' : 'slug',
                    'terms' => $category_id,
                ),
            );
        }

        $query = new WP_Query($args);

        // Format solutions data for display
        $solutions = array();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $solutions[] = $this->format_solution_data(get_the_ID());
            }
            wp_reset_postdata();
        }

        return $solutions;
    }

    /**
     * Format solution data for display.
     *
     * @param int $post_id Post ID.
     * @return array Formatted solution data.
     */
    public function format_solution_data($post_id) {
        $this->debug('Formatting solution data for post: ' . $post_id);

        $post = get_post($post_id);

        // Get ACF fields
        $description = get_field('solution_archive_description', $post_id);
        $image = get_field('solution_archive_image', $post_id);
        $link = get_field('find_out_more_link', $post_id);

        // Get categories
        $categories = get_the_category($post_id);
        $category_name = !empty($categories) ? $categories[0]->name : '';

        // Format description to match example length
        $excerpt = $this->format_excerpt($description);

        return array(
            'id' => $post_id,
            'title' => get_the_title($post_id),
            'description' => $description,
            'excerpt' => $excerpt,
            'image' => $image,
            'link' => $link,
            'category' => $category_name,
        );
    }

    /**
     * Format excerpt to match example length.
     *
     * @param string $content Raw content.
     * @return string Formatted excerpt.
     */
    public function format_excerpt($content) {
        if (empty($content)) {
            return '';
        }

        // Strip tags and trim whitespace
        $content = wp_strip_all_tags($content);
        $content = trim($content);

        // Get example length
        $max_length = strlen($this->excerpt_example);

        // Trim to max length
        if (strlen($content) > $max_length) {
            $content = substr($content, 0, $max_length) . ' [...]';
        }

        return $content;
    }

    /**
     * Get plugin template.
     *
     * @param string $template_name Template file name.
     * @param array  $args          Arguments to pass to template.
     * @return void
     */
    public function get_template($template_name, $args = array()) {
        $this->debug('Loading template: ' . $template_name);

        if (!empty($args) && is_array($args)) {
            extract($args);
        }

        $template = APW_SOLUTIONS_PLUGIN_DIR . 'templates/' . $template_name;

        if (file_exists($template)) {
            include $template;
        } else {
            $this->debug('Template not found: ' . $template_name, 'ERROR');
        }
    }

    /**
     * Debug helper.
     *
     * @param string $message Message to log.
     * @param string $type    Log type.
     * @param array  $context Additional context.
     */
    private function debug($message, $type = 'DEBUG', $context = array()) {
        if ($this->logger) {
            switch (strtoupper($type)) {
                case 'ERROR':
                    $this->logger->error($message, $context);
                    break;
                case 'WARNING':
                    $this->logger->warning($message, $context);
                    break;
                case 'INFO':
                    $this->logger->info($message, $context);
                    break;
                case 'DEBUG':
                default:
                    $this->logger->debug($message, $context);
                    break;
            }
        }
    }

    /**
     * Render a solution card.
     *
     * Helper function to maintain consistent card rendering across templates.
     * The entire card is clickable.
     *
     * @param array $solution Solution data.
     * @return void
     */
    public function render_solution_card($solution) {
        $this->debug('Rendering solution card for: ' . $solution['title']);
        ?>
        <div class="col-md-4">
            <div class="apw-solution-card" data-link="<?php echo esc_url($solution['link']); ?>">
                <span class="apw-solution-category"><?php echo esc_html($solution['category']); ?></span>
                <h3 class="apw-solution-title"><?php echo esc_html($solution['title']); ?></h3>
                <div class="apw-solution-excerpt"><?php echo esc_html($solution['excerpt']); ?></div>
                <?php if (!empty($solution['image'])) : ?>
                    <div class="apw-solution-image">
                        <img src="<?php echo esc_url($solution['image']['url']); ?>" alt="<?php echo esc_attr($solution['title']); ?>">
                    </div>
                <?php endif; ?>
                <span class="apw-solution-link">Find out more</span>
            </div>
        </div>
        <?php
    }
}