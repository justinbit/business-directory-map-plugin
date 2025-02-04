<?php

/**
 * Plugin Name: My Companies Plugin
 * Description: A plugin to display companies CPT data with maps using Leaflet.
 * Version: 1.0
 * Author: Your Name
 */

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register the Companies Custom Post Type.
 */
function mcp_register_companies_cpt()
{
    $labels = array(
        'name'               => __('Companies', 'my-companies-plugin'),
        'singular_name'      => __('Company', 'my-companies-plugin'),
        'add_new'            => __('Add New Company', 'my-companies-plugin'),
        'add_new_item'       => __('Add New Company', 'my-companies-plugin'),
        'edit_item'          => __('Edit Company', 'my-companies-plugin'),
        'new_item'           => __('New Company', 'my-companies-plugin'),
        'view_item'          => __('View Company', 'my-companies-plugin'),
        'search_items'       => __('Search Companies', 'my-companies-plugin'),
        'not_found'          => __('No companies found', 'my-companies-plugin'),
        'not_found_in_trash' => __('No companies found in Trash', 'my-companies-plugin'),
        'all_items'          => __('All Companies', 'my-companies-plugin'),
    );

    $args = array(
        'labels'      => $labels,
        'public'      => true,
        'has_archive' => true,
        'rewrite'     => array('slug' => 'companies'),
        'supports'    => array('title', 'thumbnail'),
        // 'show_in_rest' => true, // Uncomment if using the REST API.
    );

    register_post_type('companies', $args);
}
add_action('init', 'mcp_register_companies_cpt');

/**
 * Enqueue Leaflet assets and the custom map script.
 */
function mcp_enqueue_leaflet_assets()
{
    // Enqueue Leaflet CSS.
    wp_enqueue_style(
        'leaflet-css',
        'https://unpkg.com/leaflet@1.9.3/dist/leaflet.css',
        array(),
        '1.9.3'
    );

    // Enqueue Leaflet JS.
    wp_enqueue_script(
        'leaflet-js',
        'https://unpkg.com/leaflet@1.9.3/dist/leaflet.js',
        array(),
        '1.9.3',
        true
    );

    // Enqueue your custom JS file for map initialization.
    wp_enqueue_script(
        'mcp-custom-map',
        plugin_dir_url(__FILE__) . 'js/mcp-map.js',
        array('leaflet-js'),
        '1.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'mcp_enqueue_leaflet_assets');

/**
 * Query companies and pass sanitized latitude and longitude data to JavaScript.
 */
function mcp_localize_map_data()
{
    $companies_data = array();

    $args = array(
        'post_type'      => 'companies',
        'posts_per_page' => -1,
    );

    $companies_query = new WP_Query($args);

    if ($companies_query->have_posts()) {
        while ($companies_query->have_posts()) {
            $companies_query->the_post();

            $latitude  = get_field('latitude');
            $longitude = get_field('longitude');
            $title     = get_the_title();
            $phone     = get_field('phonenumber');
            $email     = get_field('email');
            $website   = get_field('website');
            $facebook  = get_field('facebook');
            $instagram = get_field('instagram');
            $linkedin  = get_field('linkedin');
            $district  = get_field('district');
            $business_sector  = get_field('business_sector');

            // Sanitize the values.
            $lat_sanitized = sanitize_text_field($latitude);
            $lng_sanitized = sanitize_text_field($longitude);

            // Convert to floats.
            $lat = floatval($lat_sanitized);
            $lng = floatval($lng_sanitized);

            // Validate the coordinates.
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                $companies_data[] = array(
                    'lat'       => $lat,
                    'lng'       => $lng,
                    'title'     => esc_html($title),
                    'phone'     => esc_html($phone),
                    'email'     => esc_html($email),
                    'website'   => esc_url($website),
                    'facebook'  => esc_url($facebook),
                    'instagram' => esc_url($instagram),
                    'linkedin'  => esc_url($linkedin),
                    'district'  => esc_html($district),
                    'business_sector'  => esc_html($business_sector),
                );
            }
        }
        wp_reset_postdata();
    }

    // Pass the companies data to the JavaScript file.
    wp_localize_script('mcp-custom-map', 'mcpCompanies', $companies_data);
}
add_action('wp_enqueue_scripts', 'mcp_localize_map_data', 20);

/**
 * Shortcode callback to output the map container.
 */
function mcp_map_shortcode()
{
    ob_start();
?>
    <style>
        #mcp-map-container {
            display: flex;
            flex-direction: column;
            height: 500px;
            max-width: 100%;
        }

        /* Filters Section */
        #mcp-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            /* padding: 10px; */
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        #mcp-filters input,
        #mcp-filters select {
            flex: 1;
            min-width: 150px;
            padding: 5px;
        }

        /* Main Content: Desktop Layout */
        #mcp-main {
            display: flex;
            flex: 1;
            height: 500px;
        }

        #mcp-company-list {
            width: 35%;
            overflow-y: scroll;
            padding: 10px;
            border-right: 1px solid #ddd;
        }

        #map {
            width: 65%;
            height: 100%;
        }

        /* Mobile Layout */
        @media (max-width: 768px) {
            #mcp-filters {
                flex-direction: column;
            }

            #mcp-main {
                flex-direction: column;
            }

            #map {
                width: 100%;
                height: 300px;
            }

            #mcp-company-list {
                width: 100%;
                height: 200px;
                border-right: none;
                border-top: 1px solid #ddd;
            }
        }
    </style>

    <div id="mcp-map-container">
        <!-- Search and Filters -->
        <div id="mcp-filters">
            <input type="text" id="mcp-search" placeholder="Search companies...">
            <select id="mcp-district-filter">
                <option value="">Filter by District</option>
            </select>
            <select id="mcp-sector-filter">
                <option value="">Filter by Business Sector</option>
            </select>
        </div>

        <!-- Main Content (List + Map) -->
        <div id="mcp-main">
            <!-- Map -->
            <div id="map"></div>

            <!-- Company List -->
            <div id="mcp-company-list">
                <ul id="mcp-company-items" style="list-style: none; padding: 0; margin: 0;"></ul>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}
add_shortcode('companies_map', 'mcp_map_shortcode');
