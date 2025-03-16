/**
 * APW Solutions JavaScript
 *
 * Handles:
 * - AJAX filtering of solutions by category
 * - Making entire solution cards clickable
 *
 * Debug mode controlled via APW_SOLUTIONS_DEBUG constant in PHP
 */
(function($) {
    'use strict';

    // Debug configuration from PHP - centralized control
    var DEBUG = typeof apw_solutions_config !== 'undefined' && apw_solutions_config.debug === true;

    // Initialize on document ready
    $(document).ready(function() {
        debug('Initializing APW Solutions JS', 'info');

        try {
            validateDependencies();
            debug('Dependencies validated', 'info');

            initCategoryFilter();
            debug('Category filter initialized', 'info');

            initClickableCards();
            debug('Clickable cards initialized', 'info');

            debug('APW Solutions initialization complete', 'success');
        } catch (error) {
            debug('Initialization error: ' + error.message, 'error');
            console.error(error);
        }
    });

    /**
     * Validate required dependencies and configuration
     */
    function validateDependencies() {
        debug('Validating dependencies...', 'info');

        // Check for jQuery
        if (typeof $ !== 'function') {
            throw new Error('jQuery is not loaded');
        }

        // Check AJAX configuration
        if (typeof apw_solutions_config === 'undefined') {
            throw new Error('AJAX configuration not found. apw_solutions_config object is missing');
        }

        if (!apw_solutions_config.ajax_url) {
            throw new Error('AJAX URL not defined in apw_solutions_config');
        }

        if (!apw_solutions_config.nonce) {
            throw new Error('AJAX nonce not defined in apw_solutions_config');
        }

        // Check for required DOM elements
        if ($('.apw-solutions-container').length === 0) {
            debug('Warning: No solutions containers found on page', 'warn');
        }

        if ($('.apw-solutions-category-select').length === 0) {
            debug('Warning: No category select dropdowns found on page', 'warn');
        }
    }

    /**
     * Initialize category filter dropdown
     */
    function initCategoryFilter() {
        var $filters = $('.apw-solutions-category-select');
        debug('Found ' + $filters.length + ' category filters', 'info');

        $filters.on('change', function() {
            var select = $(this);
            var container = select.closest('.apw-solutions-container');
            var gridContainer = container.find('.apw-solutions-grid');
            var categoryId = select.val();
            var categoryName = select.find('option:selected').text();

            debug('Category changed to: ' + categoryName + ' (ID: ' + categoryId + ')', 'info');

            // Show loading state
            gridContainer.addClass('loading');
            debug('Added loading state to grid', 'info');

            var startTime = performance.now();

            // Make AJAX request
            $.ajax({
                url: apw_solutions_config.ajax_url,
                type: 'POST',
                data: {
                    action: 'apw_solutions_filter',
                    category: categoryId,
                    nonce: apw_solutions_config.nonce
                },
                success: function(response) {
                    var endTime = performance.now();
                    debug('AJAX request completed in ' + (endTime - startTime).toFixed(2) + 'ms', 'info');

                    if (response.success) {
                        debug('AJAX request successful, got ' + response.data.count + ' solutions', 'success');

                        // Update grid with new content
                        gridContainer.html(response.data.html);
                        debug('Grid content updated', 'info');

                        // Re-initialize clickable cards for new content
                        var cardCount = initClickableCards();
                        debug('Re-initialized ' + cardCount + ' clickable cards', 'info');
                    } else {
                        // Show error message
                        var errorMessage = response.data.message || 'Error loading solutions.';
                        debug('AJAX request failed: ' + errorMessage, 'error');
                        gridContainer.html('<p class="apw-solutions-error">' + errorMessage + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    var endTime = performance.now();
                    debug('AJAX request failed in ' + (endTime - startTime).toFixed(2) + 'ms', 'error');
                    debug('Status: ' + status + ', Error: ' + error, 'error');

                    if (xhr.responseText) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            debug('Server response: ' + JSON.stringify(response), 'error');
                        } catch (e) {
                            debug('Raw server response: ' + xhr.responseText.substring(0, 200) + '...', 'error');
                        }
                    }

                    // Show error message
                    gridContainer.html('<p class="apw-solutions-error">Error connecting to server. Please try again.</p>');
                },
                complete: function() {
                    // Remove loading state
                    gridContainer.removeClass('loading');
                    debug('Removed loading state from grid', 'info');
                }
            });
        });
    }

    /**
     * Make solution cards clickable
     * @returns {number} Number of cards initialized
     */
    function initClickableCards() {
        var $cards = $('.apw-solution-card');
        debug('Found ' + $cards.length + ' solution cards to make clickable', 'info');

        $cards.each(function() {
            var $card = $(this);
            var link = $card.data('link');

            if (!link) {
                debug('Warning: Card missing data-link attribute: ' + $card.find('.apw-solution-title').text(), 'warn');
            }

            $card.css('cursor', 'pointer');
        });

        // Using delegated event handling to work with both initial and AJAX-loaded cards
        $(document).off('click', '.apw-solution-card').on('click', '.apw-solution-card', function(e) {
            var $card = $(this);
            var $clickedElement = $(e.target);
            var url = $card.data('link');

            // Log the click for debugging
            debug('Card clicked: ' + $card.find('.apw-solution-title').text(), 'info');

            // Check if we have a valid URL
            if (!url) {
                debug('Error: Clicked card has no data-link attribute', 'error');
                return;
            }

            // Navigate to the URL
            debug('Navigating to: ' + url, 'info');
            window.location.href = url;
        });

        return $cards.length;
    }

    /**
     * Debug logging function
     *
     * @param {string} message - The message to log
     * @param {string} level - Log level (info, warn, error, success)
     */
    function debug(message, level) {
        if (!DEBUG) return;

        var styles = {
            info: 'color: #0070bb; background: #f0f8ff',
            warn: 'color: #ff9900; background: #fff8f0',
            error: 'color: #ff0000; background: #fff0f0',
            success: 'color: #008800; background: #f0fff0'
        };

        var timestamp = new Date().toISOString().split('T')[1].slice(0, -1);
        var prefix = '[APW Solutions ' + timestamp + ']';

        if (level === 'error') {
            console.error(prefix, message);
        } else if (level === 'warn') {
            console.warn(prefix, message);
        } else {
            console.log('%c ' + prefix + ' %c ' + message, 'color: #666', styles[level] || styles.info);
        }
    }

})(jQuery);