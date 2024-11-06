<?php

class MVS_Database_Handler {

    // Define the table name
    const TABLE_NAME = 'mvs_metadata';

    // Create the necessary tables and edit new column
public static function create_tables() {
    global $wpdb;
    $table_name = $wpdb->prefix . self::TABLE_NAME;

    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        data_key VARCHAR(255) NOT NULL,
        data_value LONGTEXT NOT NULL,
        content_type VARCHAR(50) NOT NULL,
        last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Add content_type column if it doesn't exist (for upgrading older versions)
    $column = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'content_type'");
    if (empty($column)) {
        $wpdb->query("ALTER TABLE $table_name ADD content_type VARCHAR(50) NOT NULL");
    }
}

    //handle multiple content types
    public static function insert_metadata($key, $value, $content_type = 'general') {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
    
        // Check if the table exists before attempting to insert data
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            self::create_tables();
        }
    
        // Insert the data
        $wpdb->insert($table_name, [
            'data_key'      => $key,
            'data_value'    => maybe_serialize($value),
            'content_type'  => $content_type,
        ]);
    }

    // Retrieve metadata by key
    public static function get_metadata($key) {
        global $wpdb;
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT data_value FROM " . self::$table_name . " WHERE data_key = %s", $key
        ));
        return maybe_unserialize($result);
    }

        // Clear all metadata
        public static function clear_metadata() {
            global $wpdb;
            $table_name = $wpdb->prefix . self::TABLE_NAME;
            $wpdb->query("TRUNCATE TABLE $table_name");
        }
}
