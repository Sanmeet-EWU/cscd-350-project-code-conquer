/**
 * VolunTrax Main JavaScript File
 */

$(document).ready(function() {
    // Password visibility toggle
    $('.password-toggle').on('click', function() {
        const passwordField = $(this).siblings('input');
        const icon = $(this).find('i');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Form validation (simple example)
    $('form').on('submit', function(e) {
        let valid = true;
        
        // Check required fields
        $(this).find('input[required]').each(function() {
            if ($(this).val().trim() === '') {
                $(this).addClass('border-red-500');
                valid = false;
            } else {
                $(this).removeClass('border-red-500');
            }
        });
        
        // Check email format if it's an email field
        const emailField = $(this).find('input[type="email"]');
        if (emailField.length && emailField.val().trim() !== '') {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailField.val())) {
                emailField.addClass('border-red-500');
                valid = false;
            }
        }
        
        if (!valid) {
            e.preventDefault();
            // Add a validation message at the top of the form
            const errorMessage = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p>Please fix the errors in the form.</p></div>';
            
            // Remove any existing error messages
            $(this).find('.validation-message').remove();
            
            // Add the new error message
            $(this).prepend('<div class="validation-message">' + errorMessage + '</div>');
            
            // Scroll to the top of the form
            $('html, body').animate({
                scrollTop: $(this).offset().top - 100
            }, 200);
        }
    });
    
    // Clear validation errors when user types in the field
    $('input').on('input', function() {
        $(this).removeClass('border-red-500');
    });
    
    // Mobile menu toggle
    $('.mobile-menu-button').on('click', function() {
        $('.mobile-menu').toggleClass('hidden');
    });
    
    // Notifications dropdown
    $('.notifications-button').on('click', function() {
        $('.notifications-dropdown').toggleClass('hidden');
    });
    
    // Close dropdowns when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.notifications-button, .notifications-dropdown').length) {
            $('.notifications-dropdown').addClass('hidden');
        }
        
        if (!$(e.target).closest('.mobile-menu-button, .mobile-menu').length) {
            $('.mobile-menu').addClass('hidden');
        }
    });
    
    // QR Code Scanner (placeholder for future implementation)
    $('#qr-scanner-button').on('click', function() {
        alert('QR Scanner functionality will be implemented in a future update.');
    });
});

// Function to copy text to clipboard
function copyToClipboard(text) {
    const tempInput = document.createElement('input');
    tempInput.value = text;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
    
    // Show a toast notification
    showToast('Copied to clipboard!');
}

// Function to show a toast notification
function showToast(message, type = 'success') {
    // Remove any existing toasts
    $('.toast').remove();
    
    // Create the toast
    const toast = $('<div>').addClass('toast fixed bottom-4 right-4 bg-white p-4 rounded-lg shadow-lg z-50 flex items-center');
    
    // Add icon based on type
    if (type === 'success') {
        toast.append($('<i>').addClass('fas fa-check-circle text-green-500 mr-2'));
    } else if (type === 'error') {
        toast.append($('<i>').addClass('fas fa-times-circle text-red-500 mr-2'));
    } else if (type === 'warning') {
        toast.append($('<i>').addClass('fas fa-exclamation-circle text-yellow-500 mr-2'));
    } else if (type === 'info') {
        toast.append($('<i>').addClass('fas fa-info-circle text-blue-500 mr-2'));
    }
    
    // Add message
    toast.append($('<span>').text(message));
    
    // Add to body
    $('body').append(toast);
    
    // Animate in
    toast.css('transform', 'translateY(100px)');
    toast.css('opacity', '0');
    setTimeout(() => {
        toast.css('transition', 'all 0.3s ease-out');
        toast.css('transform', 'translateY(0)');
        toast.css('opacity', '1');
    }, 50);
    
    // Animate out after 3 seconds
    setTimeout(() => {
        toast.css('transform', 'translateY(100px)');
        toast.css('opacity', '0');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}