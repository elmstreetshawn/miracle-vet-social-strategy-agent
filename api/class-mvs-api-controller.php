<?php

class MVS_API_Controller {

    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes() {
        // Register routes for each GPT agent
        register_rest_route('mvs/v1', '/agent/intention', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_intention'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('mvs/v1', '/agent/data_chunking', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_data_chunking'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('mvs/v1', '/agent/expectation', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_expectation'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('mvs/v1', '/agent/conductor', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_conductor'],
            'permission_callback' => '__return_true',
        ]);
    }

    // Agent 1: Intention Handler
    public static function handle_intention($request) {
        $user_message = sanitize_text_field($request->get_param('message'));
        $prompt = "Analyze the following question and determine the primary intent:\n\n" . $user_message;
        return self::call_gpt_api($prompt);
    }

    // Agent 2: Data Chunking and Retrieval Handler
    public static function handle_data_chunking($request) {
        $intention = sanitize_text_field($request->get_param('intention'));
    
        // Retrieve grounded information from the database for context
        $grounded_data = self::get_grounded_information();
    
        // Formulate the prompt with grounded information included
        $prompt = "Based on the intention: '$intention', and using the following context:\n\n" . $grounded_data .
                  "\n\nretrieve relevant information to answer the user's implicit question. Structure the information as follows...";
    
        return self::call_gpt_api($prompt);
    }
    
    // Helper function to get grounded information from the database
    private static function get_grounded_information() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mvs_metadata';
    
        // Retrieve relevant grounding data for MiracleVet (e.g., audience profile, product info)
        $results = $wpdb->get_results("SELECT data_value FROM $table_name WHERE content_type = 'blog' OR content_type = 'product'", ARRAY_A);
    
        $grounded_data = "";
        foreach ($results as $row) {
            $grounded_data .= maybe_unserialize($row['data_value']) . "\n";
        }
    
        return $grounded_data;
    }

    // Agent 3: Expectation vs Given Handler
    public static function handle_expectation($request) {
        $data = sanitize_text_field($request->get_param('data'));
        $prompt = "Compare this data against the user's original intention and ensure all relevant information is provided. Identify any gaps.";
        return self::call_gpt_api($prompt);
    }

    // Agent 4: Conductor Handler
    public static function handle_conductor($request) {
        $contextual_data = sanitize_text_field($request->get_param('contextual_data'));
        $prompt = "Combine the following data into a coherent response for the user, adding specific examples where possible:\n\n" . $contextual_data;
        return self::call_gpt_api($prompt);
    }

    // Helper function to make the GPT API call
    private static function call_gpt_api($prompt) {
        $api_key = '';
        $response = wp_remote_post('https://api.openai.com/v1/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-4o-mini',
                'prompt' => $prompt,
                'max_tokens' => 7550,
            ]),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'Error contacting the AI service');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['choices'][0]['text'] ?? 'No response from AI service';
    }
}

// Initialize the API controller
MVS_API_Controller::init();
