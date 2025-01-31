document.addEventListener('DOMContentLoaded', function() {
    const countdown = document.getElementById('countdown');
    if (countdown) {
        const countdownDate = countdown.getAttribute('data-date');
        // Set the date we're counting down to
        const countDownDate = new Date(countdownDate).getTime();

        // Update the count down every 1 second
        const x = setInterval(function() {

        // Get today's date and time
        const now = new Date().getTime();

        // Find the distance between now and the count down date
        const distance = countDownDate - now;

        // Time calculations for days, hours, minutes and seconds
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        // Display the result in the element with id="countdown"
        document.getElementById("days").innerHTML = days;
        document.getElementById("hours").innerHTML = hours;
        document.getElementById("minutes").innerHTML = minutes;
        document.getElementById("seconds").innerHTML = seconds;

        // If the count down is finished, write some text
        if (distance < 0) {
            clearInterval(x);
            document.getElementById("countdown").innerHTML = "Event Started!";
        }
        }, 1000);
    }

    // Handle registration clicks
    document.querySelectorAll('.register-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            const eventId = button.dataset.eventId;
            const card = button.closest('.event-card');

            try {
                const response = await fetch('/evs-home/includes/check-login.php');
                const { loggedIn } = await response.json();

                if (!loggedIn) {
                    window.location.href = '/evs-home/pages/login.php';
                    return;
                }

                const formData = new FormData();
                formData.append('event_id', eventId);

                const registerResponse = await fetch('/evs-home/includes/attend.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await registerResponse.json();

                if (result.success) {
                    // Update the clicked button
                    button.classList.remove('btn-secondary');
                    button.classList.add('btn-success');
                    button.textContent = '✓ Registered';
                    button.disabled = true;
                    
                    // Update all instances of this event's capacity
                    document.querySelectorAll(`[data-event-id="${eventId}"] .capacity-badge`).forEach(badge => {
                        badge.textContent = `${result.new_count}/${badge.dataset.capacity}`;
                        badge.classList.toggle('bg-danger', result.new_count >= badge.dataset.capacity);
                        badge.classList.toggle('bg-primary', result.new_count < badge.dataset.capacity);
                    });
                    
                    showNotification(result.message, 'success');
                } else {
                    showNotification(result.message, 'danger');
                }
            } catch (error) {
                showNotification('An error occurred. Please try again.', 'danger');
            }
        });
    });

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} fixed-top m-3`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => notification.remove(), 3000);
    }
});


/**
 * Validates the login form. If the email or password fields are empty, an alert is
 * shown and the function returns false. Otherwise, the function returns true.
 * @returns {boolean} Whether the form is valid.
 */
const validateLoginForm = () => {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    if (email.trim() === '' || password.trim() === '') {
        alert('Email and password are required.');
        return false;
    }

    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address.');
        return false;
    }

    return true;
};


function validateRegisterForm() {
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const confirm_password = document.getElementById('confirm_password').value;

    if (name.trim() === '' || email.trim() === '' || password.trim() === '' || confirm_password.trim() === '') {
        alert('All fields are required.');
        return false;
    }

    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address.');
        return false;
    }

    if (password !== confirm_password) {
        alert('Passwords do not match.');
        return false;
    }

    return true;
};