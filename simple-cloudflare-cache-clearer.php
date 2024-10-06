<?php

/*
Plugin Name: Simple Cloudflare Cache Clearer
Description: Clears Cloudflare cache when SpinupWP cache clear is triggered, also adds a one-click admin menu item to manually clear the CF cache
Version: 0.0.1
Author: NodleStudios
*/

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
    exit;
}

// Function to clear Cloudflare cache
function clear_cloudflare_cache()
{
    // Check if the Cloudflare plugin is active and the Hooks class exists
    if (class_exists('CF\WordPress\Hooks')) {
        $cloudflareHooks = new \CF\WordPress\Hooks();

        // Log debug message
        error_log('Purging Cloudflare cache');

        // Clear the Cloudflare cache
        $cloudflareHooks->purgeCacheEverything();

        return true;
    }
    return false;
}

// Hook our function to the spinupwp_site_purged action
add_action('spinupwp_site_purged', 'clear_cloudflare_cache');

// Add admin bar menu item
function cloudflare_cache_clear_admin_bar_menu($wp_admin_bar)
{
    $wp_admin_bar->add_menu(array(
        'id'    => 'clear-cloudflare-cache',
        'title' => 'Clear CF Cache',
        'href'  => '#',
        'meta'  => array('onclick' => 'clearCloudflareCache(); return false;')
    ));
}
add_action('admin_bar_menu', 'cloudflare_cache_clear_admin_bar_menu', 100);

// Add JavaScript to admin footer
function cloudflare_cache_clear_admin_footer()
{
?>
    <script type="text/javascript">
        function clearCloudflareCache() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    alert(response.data);
                } else {
                    alert('An error occurred while clearing the cache.');
                }
            };
            xhr.send('action=clear_cloudflare_cache&nonce=' + <?php echo json_encode(wp_create_nonce('clear_cloudflare_cache_nonce')); ?>);
        }
    </script>
<?php
}
add_action('admin_footer', 'cloudflare_cache_clear_admin_footer');

// AJAX handler for clearing cache
function ajax_clear_cloudflare_cache()
{
    check_ajax_referer('clear_cloudflare_cache_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('You do not have permission to perform this action.');
    }

    if (clear_cloudflare_cache()) {
        wp_send_json_success('Cloudflare cache has been cleared successfully!');
    } else {
        wp_send_json_error('Failed to clear Cloudflare cache. Make sure the Cloudflare plugin is active.');
    }
}
add_action('wp_ajax_clear_cloudflare_cache', 'ajax_clear_cloudflare_cache');
