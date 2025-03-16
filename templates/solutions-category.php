<?php
/**
 * Template for the [solutions_category] shortcode
 *
 * @package APW_Solutions_Plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="apw-solutions-category-container">
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
            <p class="apw-solutions-empty">No solutions found for this category.</p>
        <?php endif; ?>
    </div>
</div>