<?php

require_once MVS_PLUGIN_DIR . 'includes/class-mvs-data-ingestion.php';
require_once MVS_PLUGIN_DIR . 'includes/class-mvs-database-handler.php';

class MVS_Admin_Interface {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_page']);
    }

    public static function add_admin_page() {
        add_menu_page(
            'MiracleVet Social Strategy',
            'MVS Agent',
            'manage_options',
            'mvs-agent',
            [__CLASS__, 'display_admin_page']
        );
    }

    public static function display_admin_page() {
        // Handle "Update Data" button
        if (isset($_POST['update_data'])) {
            MVS_Data_Ingestion::ingest_data();
            echo '<div class="updated"><p>Data has been updated successfully!</p></div>';
        }

        echo '<div class="wrap">';
        echo '<h1>MiracleVet Social Strategy Agent</h1>';

        // "Update Data" button form
        echo '<form method="POST">';
        echo '<input type="hidden" name="update_data" value="1">';
        echo '<button type="submit" class="button button-primary">Update Data</button>';
        echo '</form>';

        // Display grounding data
        echo '<h2>Grounding Data</h2>';
        self::display_grounding_data();

        echo '</div>';
    }

    // Function to fetch and display grounding data
    public static function display_grounding_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . MVS_Database_Handler::TABLE_NAME;
        $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
    
        if ($results) {
            echo '<table class="widefat fixed" cellspacing="0">';
            echo '<thead><tr><th>ID</th><th>Content Type</th><th>Data Key</th><th>Data Value</th><th>Last Updated</th></tr></thead>';
            echo '<tbody>';
    
            foreach ($results as $row) {
                // Check if data is serialized and unserialize it if needed
                $data_value = maybe_unserialize($row['data_value']);
    
                // Limit the display length of data_value for readability
                $display_value = is_string($data_value) && strlen($data_value) > 100 
                    ? substr($data_value, 0, 100) . '...'
                    : print_r($data_value, true);
    
                echo '<tr>';
                echo '<td>' . esc_html($row['id']) . '</td>';
                echo '<td>' . esc_html($row['content_type']) . '</td>';
                echo '<td>' . esc_html($row['data_key']) . '</td>';
                echo '<td>' . esc_html($display_value) . '</td>';
                echo '<td>' . esc_html($row['last_updated']) . '</td>';
                echo '</tr>';
            }
    
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>No grounding data found.</p>';
        }
    }
}
