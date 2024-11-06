<?php
/*
Plugin Name: MiracleVet Social Strategy Agent
Description: Conversational agent for MiracleVet’s go-to-market and social media strategy.
Version: 1.0
Author: Your Name
*/

// Define constants
define('MVS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MVS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload necessary files
function mvs_autoload_classes($class_name) {
    if (strpos($class_name, 'MVS_') !== false) {
        $file = MVS_PLUGIN_DIR . 'includes/' . strtolower(str_replace('_', '-', $class_name)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
spl_autoload_register('mvs_autoload_classes');


function mvs_enqueue_chatbot_assets() {
    wp_enqueue_style('mvs-chatbot-css', plugins_url('assets/css/chatbot.css', __FILE__));
    wp_enqueue_script('mvs-chatbot-js', plugins_url('assets/js/chatbot.js', __FILE__), array('jquery'), null, true);

    // Pass the AJAX URL to JavaScript
    wp_localize_script('mvs-chatbot-js', 'mvs_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
}
add_action('wp_enqueue_scripts', 'mvs_enqueue_chatbot_assets');


// Activation hook to set up tables and run initial data ingestion
function mvs_activate_plugin() {
    require_once MVS_PLUGIN_DIR . 'includes/class-mvs-database-handler.php';
    require_once MVS_PLUGIN_DIR . 'includes/class-mvs-data-ingestion.php';
    require_once MVS_PLUGIN_DIR . 'api/class-mvs-api-controller.php';

    // Initialize API controller
    MVS_API_Controller::init();

    // Create necessary tables
    MVS_Database_Handler::create_tables();

    // Run initial data ingestion
    MVS_Data_Ingestion::ingest_data();
}
register_activation_hook(__FILE__, 'mvs_activate_plugin');

// Initialize plugin
function mvs_init_plugin() {
    // Load admin interface
    if (is_admin()) {
        require_once MVS_PLUGIN_DIR . 'includes/class-mvs-admin-interface.php';
        MVS_Admin_Interface::init();
    }
    
    // Load other core classes
    require_once MVS_PLUGIN_DIR . 'includes/class-mvs-agent-init.php';
    MVS_Agent_Init::init();
}
add_action('plugins_loaded', 'mvs_init_plugin');

// Shortcode to render chatbot window
function mvs_render_chatbot_window() {
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/partials/chatbot-window.php';
    return ob_get_clean();
}
add_shortcode('mvs_chatbot', 'mvs_render_chatbot_window');