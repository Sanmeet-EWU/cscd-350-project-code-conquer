$(document).ready(function() {
    // Tab switching functionality
    $('#login-tab').click(function() {
        switchToLogin();
    });

    $('#signup-tab').click(function() {
        switchToSignup();
    });

    $('#switch-to-signup').click(function(e) {
        e.preventDefault();
        switchToSignup();
    });

    $('#switch-to-login').click(function(e) {
        e.preventDefault();
        switchToLogin();
    });

    function switchToLogin() {
        $('#login-tab').addClass('text-forest-accent border-forest-accent').removeClass('text-gray-500 border-gray-200');
        $('#signup-tab').addClass('text-gray-500 border-gray-200').removeClass('text-forest-accent border-forest-accent');
        $('#login-form').removeClass('hidden');
        $('#signup-form').addClass('hidden');
    }

    function switchToSignup() {
        $('#signup-tab').addClass('text-forest-accent border-forest-accent').removeClass('text-gray-500 border-gray-200');
        $('#login-tab').addClass('text-gray-500 border-gray-200').removeClass('text-forest-accent border-forest-accent');
        $('#signup-form').removeClass('hidden');
        $('#login-form').addClass('hidden');
    }

    // Form validation and submission
    $('#login-form form').submit(function(e) {
        e.preventDefault();
        const email = $('#email').val();
        const password = $('#password').val();
        
        if (!email || !password) {
            showError('Please fill in all fields');
            return;
        }

        // Simulate login process
        simulateLoading();
        setTimeout(() => {
            // Redirect to dashboard
            window.location.href = 'index.html';
        }, 1500);
    });

    $('#signup-form form').submit(function(e) {
        e.preventDefault();
        const email = $('#signup-email').val();
        const password = $('#signup-password').val();
        const confirmPassword = $('#confirm-password').val();
        const organization = $('#organization').val();
        
        if (!email || !password || !confirmPassword || !organization) {
            showError('Please fill in all fields');
            return;
        }

        if (password !== confirmPassword) {
            showError('Passwords do not match');
            return;
        }

        // Simulate signup process
        simulateLoading();
        setTimeout(() => {
            // Redirect to dashboard
            window.location.href = 'index.html';
        }, 1500);
    });

    // Loading animation
    function simulateLoading() {
        const submitButton = $('button[type="submit"]');
        const originalText = submitButton.text();
        
        submitButton.prop('disabled', true);
        submitButton.html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');
        
        setTimeout(() => {
            submitButton.prop('disabled', false);
            submitButton.text(originalText);
        }, 1500);
    }

    // Error message display
    function showError(message) {
        const errorDiv = $('<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"></div>');
        errorDiv.html(`
            <span class="block sm:inline">${message}</span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                <i class="fas fa-times cursor-pointer"></i>
            </span>
        `);
        
        $('form').prepend(errorDiv);
        
        // Remove error message on click
        errorDiv.find('.fa-times').click(function() {
            errorDiv.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            errorDiv.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Add ripple effect to buttons
    $('button').on('click', function(e) {
        const button = $(this);
        const ripple = $('<span class="ripple"></span>');
        
        const x = e.pageX - button.offset().left;
        const y = e.pageY - button.offset().top;
        
        ripple.css({
            'left': x,
            'top': y
        });
        
        button.append(ripple);
        
        setTimeout(function() {
            ripple.remove();
        }, 600);
    });

    // Input field focus effects
    $('input').focus(function() {
        $(this).parent().addClass('ring-2 ring-forest-accent ring-opacity-50');
    }).blur(function() {
        $(this).parent().removeClass('ring-2 ring-forest-accent ring-opacity-50');
    });
}); 