<?php
/**
 * Template for the [solutions] shortcode
 *
 * @package APW_Solutions_Plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="apw-solutions-container" id="<?php echo esc_attr( $container_id ); ?>">
    <div class="apw-solutions-header">
        <h2 class="apw-solutions-title">Solution By</h2>
        <div class="apw-solutions-filter">
            <select class="apw-solutions-category-select">
                <?php foreach ( $categories as $category ) : ?>
                    <option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( $default_category->term_id, $category->term_id ); ?>>
                        <?php echo esc_html( $category->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <i class="fa-solid fa-caret-down" aria-hidden="true"></i>
        </div>
    </div>

    <div class="apw-solutions-grid">
        <?php if ( ! empty( $solutions ) ) : ?>
            <div class="row">
                <?php
                // Use the helper function to render each solution card
                foreach ( $solutions as $solution ) {
                    $plugin->render_solution_card( $solution );
                }
                ?>
            </div>
        <?php else : ?>
            <p class="apw-solutions-empty">No solutions found.</p>
        <?php endif; ?>
    </div>
</div>