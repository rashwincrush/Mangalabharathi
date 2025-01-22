// Admin Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Dashboard card hover effects
    const dashboardCards = document.querySelectorAll('.card');
    dashboardCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('shadow-lg');
        });
        card.addEventListener('mouseleave', function() {
            this.classList.remove('shadow-lg');
        });
    });

    // Sidebar active state
    const currentPage = window.location.pathname;
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
    sidebarLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });

    // Event deletion handling
    const deleteEventButtons = document.querySelectorAll('.delete-event');
    deleteEventButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Disable the button to prevent multiple clicks
            this.disabled = true;
            
            // Get the event ID
            const eventId = this.getAttribute('data-id');
            const row = this.closest('tr');
            
            // Try multiple methods to get event title
            let eventTitle = 'Unknown Event';
            
            // Method 1: Look for .event-title class
            const eventTitleElement = row ? row.querySelector('.event-title') : null;
            if (eventTitleElement) {
                eventTitle = eventTitleElement.textContent.trim();
            }
            
            // Method 2: Fallback to first column (if it contains title)
            if (eventTitle === 'Unknown Event' && row) {
                const firstColumnElement = row.querySelector('td:nth-child(2)');
                if (firstColumnElement) {
                    const columnText = firstColumnElement.textContent.trim();
                    if (columnText && columnText !== 'null') {
                        eventTitle = columnText;
                    }
                }
            }
            
            // Validate event ID
            if (!eventId || eventId === 'null') {
                alert('Invalid event ID. Please refresh the page and try again.');
                this.disabled = false;
                return;
            }
            
            // Confirm deletion with event title
            const confirmMessage = `Are you sure you want to delete the event "${eventTitle}"?`;
            if (confirm(confirmMessage)) {
                // AJAX request to delete event
                fetch('/admin/events/delete_event.php?id=' + eventId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    // Log raw response for debugging
                    console.log('Raw response:', response);
                    console.log('Response status:', response.status);
                    console.log('Response headers:', Object.fromEntries(response.headers.entries()));
                    
                    // Check if response is OK
                    if (!response.ok) {
                        // Try to get error text for more details
                        return response.text().then(errorText => {
                            console.error('Error response text:', errorText);
                            throw new Error(`HTTP error! status: ${response.status}, text: ${errorText}`);
                        });
                    }
                    
                    // Get response text for debugging
                    return response.text().then(text => {
                        console.log('Response text:', text);
                        
                        // Try to parse JSON
                        try {
                            return JSON.parse(text);
                        } catch (parseError) {
                            console.error('JSON parse error:', parseError);
                            console.error('Unparseable text:', text);
                            throw new Error('Failed to parse JSON response');
                        }
                    });
                })
                .then(data => {
                    // Validate response data
                    if (typeof data !== 'object') {
                        console.error('Invalid response data:', data);
                        throw new Error('Invalid response from server');
                    }
                    
                    if (data.success) {
                        // Remove the row from the table
                        if (row) {
                            row.remove();
                        }
                        alert('Event deleted successfully');
                    } else {
                        // Re-enable button if deletion fails
                        this.disabled = false;
                        alert(data.message || 'Failed to delete event');
                    }
                })
                .catch(error => {
                    // Re-enable button on error
                    this.disabled = false;
                    console.error('Full error details:', error);
                    
                    // More descriptive error message
                    const errorMessage = error instanceof Error 
                        ? error.message 
                        : 'An unexpected error occurred while deleting the event';
                    
                    alert(errorMessage);
                });
            } else {
                // Re-enable button if user cancels
                this.disabled = false;
            }
        });
    });

    // Simple confirmation for destructive actions
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });

    // Basic form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
});
