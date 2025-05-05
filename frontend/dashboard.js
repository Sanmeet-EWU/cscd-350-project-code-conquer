$(document).ready(function() {
    // Side Navigation Toggle
    $('#toggleNav').click(function() {
        const sideNav = $('#sideNav');
        const mainContent = $('#mainContent');
        
        if (sideNav.hasClass('w-64')) {
            sideNav.removeClass('w-64').addClass('w-0');
            mainContent.removeClass('ml-64').addClass('ml-0');
        } else {
            sideNav.removeClass('w-0').addClass('w-64');
            mainContent.removeClass('ml-0').addClass('ml-64');
        }
    });

    // Navigation Item Click Handler
    $('.nav-item').click(function(e) {
        e.preventDefault();
        
        // Remove active class from all nav items
        $('.nav-item').removeClass('active bg-forest-accent');
        
        // Add active class to clicked nav item
        $(this).addClass('active bg-forest-accent');
        
        // Hide all content sections
        $('.content-section').addClass('hidden');
        
        // Show the selected content section
        const target = $(this).attr('href');
        $(target).removeClass('hidden');
    });

    // Initialize Charts
    initializeCharts();

    // Form Submission Handlers
    $('#account form').submit(function(e) {
        e.preventDefault();
        showSuccess('Profile updated successfully!');
    });

    $('#settings form').submit(function(e) {
        e.preventDefault();
        showSuccess('Password updated successfully!');
    });

    // Toggle Switch Handlers
    $('input[type="checkbox"]').change(function() {
        const label = $(this).siblings('div');
        if ($(this).is(':checked')) {
            label.addClass('bg-forest-accent');
        } else {
            label.removeClass('bg-forest-accent');
        }
    });

    // Export Data Button Handler
    $('button:contains("Export Data")').click(function() {
        simulateLoading();
        setTimeout(() => {
            showSuccess('Data exported successfully!');
        }, 1500);
    });

    // QR Code Generation
    $('#generate-qr').click(function() {
        const eventSelect = $('#event-select');
        const qrContainer = $('#qr-container');
        
        if (!eventSelect.val()) {
            showError('Please select an event first');
            return;
        }

        // Simulate QR code generation
        simulateLoading();
        
        // In a real implementation, this would make an API call to generate a unique QR code
        setTimeout(() => {
            const eventName = eventSelect.find('option:selected').text();
            const qrCode = generateDummyQR(eventName);
            
            qrContainer.html(`
                <div class="text-center">
                    <div class="mb-4">${qrCode}</div>
                    <p class="text-sm text-gray-600">${eventName}</p>
                    <p class="text-xs text-gray-500 mt-2">Valid for today's event only</p>
                </div>
            `);
        }, 1500);
    });

    // Helper Functions
    function initializeCharts() {
        // Volunteer Hours Chart
        const hoursCtx = document.getElementById('hoursChart').getContext('2d');
        new Chart(hoursCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Volunteer Hours',
                    data: [120, 190, 170, 210, 240, 280],
                    borderColor: '#4a7c59',
                    backgroundColor: 'rgba(74, 124, 89, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Event Participation Chart
        const participationCtx = document.getElementById('participationChart').getContext('2d');
        new Chart(participationCtx, {
            type: 'bar',
            data: {
                labels: ['Forest Cleanup', 'Tree Planting', 'Trail Maintenance', 'Wildlife Survey', 'Education Program'],
                datasets: [{
                    label: 'Participants',
                    data: [45, 30, 25, 20, 15],
                    backgroundColor: '#4a7c59',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function showSuccess(message) {
        const successDiv = $('<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"></div>');
        successDiv.html(`
            <span class="block sm:inline">${message}</span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                <i class="fas fa-times cursor-pointer"></i>
            </span>
        `);
        
        $('form').prepend(successDiv);
        
        // Remove success message on click
        successDiv.find('.fa-times').click(function() {
            successDiv.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            successDiv.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    function simulateLoading() {
        const button = $('button:contains("Export Data")');
        const originalText = button.html();
        
        button.prop('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin mr-2"></i>Exporting...');
        
        setTimeout(() => {
            button.prop('disabled', false);
            button.html(originalText);
        }, 1500);
    }

    function generateDummyQR(eventName) {
        // This is a placeholder for actual QR code generation
        // In a real implementation, you would use a QR code library
        const size = 200;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext('2d');
        
        // Draw a simple pattern as a placeholder
        ctx.fillStyle = '#4a7c59';
        ctx.fillRect(0, 0, size, size);
        ctx.fillStyle = 'white';
        ctx.fillRect(size/4, size/4, size/2, size/2);
        
        return `<img src="${canvas.toDataURL()}" alt="QR Code" class="mx-auto">`;
    }

    function showError(message) {
        const errorDiv = $('<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"></div>');
        errorDiv.html(`
            <span class="block sm:inline">${message}</span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                <i class="fas fa-times cursor-pointer"></i>
            </span>
        `);
        
        $('#qr-container').before(errorDiv);
        
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

    // Add hover effect to stat cards
    $('.stat-card').hover(
        function() {
            $(this).addClass('transform -translate-y-1');
        },
        function() {
            $(this).removeClass('transform -translate-y-1');
        }
    );

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
}); 