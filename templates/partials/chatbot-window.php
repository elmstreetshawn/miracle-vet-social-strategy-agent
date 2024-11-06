function mvs_chatbot_ui() {
    ob_start();
    ?>
    <div id="mvs-chatbot">
        <div id="mvs-chatbot-conversation">
            <!-- Conversation logs will appear here -->
        </div>
        <input type="text" id="mvs-chatbot-input" placeholder="Ask me anything...">
        <button id="mvs-chatbot-send">Send</button>
    </div>

    <script>
        document.getElementById('mvs-chatbot-send').addEventListener('click', function() {
            let input = document.getElementById('mvs-chatbot-input').value;
            if (input) {
                // Send input to the server via AJAX
                mvsSendMessage(input);
                document.getElementById('mvs-chatbot-input').value = '';
            }
        });

        function mvsSendMessage(input) {
            jQuery.ajax({
                url: mvs_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'mvs_chatbot_query',
                    message: input
                },
                success: function(response) {
                    document.getElementById('mvs-chatbot-conversation').innerHTML += `<div class="user-message">${input}</div><div class="bot-message">${response}</div>`;
                }
            });
        }
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('mvs_chatbot', 'mvs_chatbot_ui');