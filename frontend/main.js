$(document).ready(function() {
    // Add hover effect to all buttons
    $('button').addClass('btn');

    // Add hover effect to stat cards
    $('.stat-card').hover(
        function() {
            $(this).addClass('transform -translate-y-1');
        },
        function() {
            $(this).removeClass('transform -translate-y-1');
        }
    );

    // Add hover effect to activity items
    $('.activity-item').hover(
        function() {
            $(this).addClass('transform translate-x-1');
        },
        function() {
            $(this).removeClass('transform translate-x-1');
        }
    );

    // Handle New Volunteer button click
    $('button:contains("New Volunteer")').click(function() {
        // Show a modal or navigate to new volunteer form
        alert('New Volunteer form will open here');
    });

    // Handle View Reports button click
    $('button:contains("View Reports")').click(function() {
        // Navigate to reports page
        alert('Reports page will open here');
    });

    // Handle Profile button click
    $('button:contains("Profile")').click(function() {
        // Show profile dropdown or navigate to profile page
        alert('Profile menu will open here');
    });

    // Add smooth scrolling to all links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $(this.hash);
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 20
            }, 500);
        }
    });

    // Add ripple effect to buttons
    $('.btn').on('click', function(e) {
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

    // Add loading animation to stat cards
    $('.stat-card').each(function(index) {
        const card = $(this);
        const number = card.find('h3');
        const target = parseInt(number.text());
        let current = 0;
        
        const increment = target / 20;
        const interval = setInterval(function() {
            current += increment;
            if (current >= target) {
                clearInterval(interval);
                current = target;
            }
            number.text(Math.floor(current));
        }, 50);
    });
});
