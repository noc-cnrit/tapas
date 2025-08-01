// image-picker.js
// This script handles the frontend functionality of the WordPress image picker.

(function() {
    // Function to open the WordPress media picker
    function openWordPressMediaPicker(pickerUrl, targetInputId) {
        // Open the picker in a new window
        const pickerWindow = window.open(pickerUrl, 'wpMediaPicker', 'width=1200,height=800,resizable=yes,scrollbars=yes');
        
        // Listen for messages from the picker window
        window.addEventListener('message', function(event) {
            // Basic security check: ensure message is from a trusted origin
            // Note: In a production environment, you should replace '*' with the actual origin of your WordPress site.
            if (event.origin === window.location.origin) { 
                if (event.data && event.data.type === 'wp_media_selected') {
                    // Handle the selected media
                    if (targetInputId) {
                        const targetInput = document.getElementById(targetInputId);
                        if (targetInput) {
                            targetInput.value = event.data.url;
                            
                            // Optional: Trigger a change event for frameworks like React or Vue
                            const changeEvent = new Event('change', { bubbles: true });
                            targetInput.dispatchEvent(changeEvent);
                        }
                    }
                    
                    // Optional: Update an image preview element
                    const previewElement = document.getElementById(targetInputId + '-preview');
                    if (previewElement) {
                        previewElement.src = event.data.url;
                        previewElement.style.display = 'block';
                    }
                    
                    // Close the picker window
                    if (pickerWindow) {
                        pickerWindow.close();
                    }
                    
                    // Optional: Call a custom callback function
                    if (typeof onWpMediaSelected === 'function') {
                        onWpMediaSelected(event.data);
                    }
                }
            }
        }, false);
    }

    // Expose the function to the global scope
    window.openWordPressMediaPicker = openWordPressMediaPicker;

    // Example of a custom callback function (optional)
    // window.onWpMediaSelected = function(media) {
    //     console.log('Selected media:', media);
    //     alert('You selected: ' + media.title);
    // };

})();

