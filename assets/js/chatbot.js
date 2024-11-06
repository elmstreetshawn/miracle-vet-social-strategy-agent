jQuery(document).ready(function($) {
    $('#mvs-chatbot-send').on('click', function() {
        let userMessage = $('#mvs-chatbot-input').val();
        if (userMessage) {
            // Display the user's message in the chat window
            $('#mvs-chatbot-conversation').append('<div class="user-message">' + userMessage + '</div>');
            
            // Clear the input field
            $('#mvs-chatbot-input').val('');

            // Auto-scroll to the bottom of the chat window
            scrollToBottom();

            // Send the message to the server
            $.ajax({
                url: mvs_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'mvs_chatbot_query',
                    message: userMessage
                },
                success: function(response) {
                    // Display the chatbot's response
                    $('#mvs-chatbot-conversation').append('<div class="bot-message">' + response + '</div>');

                    // Auto-scroll to the bottom after bot response
                    scrollToBottom();
                },
                error: function() {
                    $('#mvs-chatbot-conversation').append('<div class="bot-message error">Error: Could not connect to the chatbot.</div>');

                    // Auto-scroll to the bottom after error message
                    scrollToBottom();
                }
            });
        }
    });

    // Function to scroll the chat window to the bottom
    function scrollToBottom() {
        const conversationDiv = $('#mvs-chatbot-conversation');
        conversationDiv.scrollTop(conversationDiv.prop('scrollHeight'));
    }
});
