// u23547121 CT Kwenda
// for login form only
document.addEventListener("DOMContentLoaded", function () {

    const loginForm = document.getElementById("login-form");

    if (loginForm) {
        loginForm.addEventListener("submit", function (l) {
            l.preventDefault();
            validateLoginF();
        });
    }
});

//Email validation
function validatorOfEmail(email) {
    const emailR = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;
    return emailR.test(email);
}

//Password validator
function validatorOfPassword(password) {
    const passwordR = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
    return passwordR.test(password);
}

function displayErrors(errors, formId) {
    let errorMsg = document.getElementById('error-messages'); // Default to global if not specific
    if (formId === 'login-form') {
        errorMsg = document.getElementById('login-error-messages'); // Use specific login error div
    }

    if (!errorMsg) {
        // If the div doesn't exist, create it dynamically
        errorMsg = document.createElement('div');
        errorMsg.id = (formId === 'login-form') ? 'login-error-messages' : 'error-messages';
        // Add basic styling for visibility if not already in CSS
        errorMsg.style.color = 'red';
        errorMsg.style.backgroundColor = '#ffe0e0';
        errorMsg.style.border = '1px solid red';
        errorMsg.style.padding = '10px';
        errorMsg.style.marginTop = '10px';
        errorMsg.style.borderRadius = '5px';

        document.getElementById(formId).prepend(errorMsg);
    }
    // Clear previous errors and display new ones
    errorMsg.innerHTML = errors.map(error => `<p>${error}</p>`).join('');
}

function validateLoginF() {
    // Changed to 'login-username' to match PHP's expectation
    let username = document.querySelector('input[name="login-username"]').value.trim(); 
    let password = document.querySelector('input[name="login-password"]').value.trim();

    let errors = [];

    // Assuming username is the primary identifier for login now, not email.
    // If users can log in with email OR username, you'll need more complex logic.
    // For now, aligning with handleLogin's `username = ?` SQL query.
    if (!username) {
        errors.push('Username is required.');
    } // No specific format validation for username unless your backend has one

    if (!password) { // Added explicit check for empty password
        errors.push('Password is required.');
    } else if (!validatorOfPassword(password)) {
        // This message should reflect your backend's password validation rules
        errors.push('Password must be at least 8 characters, contain uppercase, lowercase, a digit, and a symbol.');
    }

    if (errors.length > 0) {
        displayErrors(errors, 'login-form');
    } else {
        // Clear previous errors when submitting
        const errorMsgElement = document.getElementById('login-error-messages');
        if (errorMsgElement) {
            errorMsgElement.innerHTML = '';
        }
        sendToAPILogin(username, password); // Pass username and password
    }
}


function sendToAPILogin(username, password) { // Function now accepts username
    console.log("Attempting login with username:", username);
    const payload = {
        username: username, // Send username
        password: password,
        type: 'Login'
    };
    console.log("Login Payload:", payload);

    fetch('http://localhost/GA221/api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload) // Send the payload object
    })
    .then(response => {
        // Check if response is OK (200-299) before trying to parse JSON
        if (!response.ok) {
            // If not OK, read the response as text to get the PHP error message
            return response.json().then(errorData => {
                // If backend sent JSON error, throw it
                throw new Error(errorData.message || 'Login failed with an unexpected error.');
            }).catch(() => {
                // If backend sent non-JSON error (e.g., PHP Fatal error), throw generic error
                throw new Error('Server error: Could not parse login response. Please check server logs.');
            });
        }
        return response.json(); // Parse JSON if response is OK
    })
    .then(data => {
        console.log("Login response:", data);

        // Your PHP sends {"user": user_data, "api_key": api_key} on success
        // Check for 'user' object to confirm success
        if (data && data.user) { 
            alert('Login successful!');
            console.log("API Key received:", data.api_key); // Directly access data.api_key
            localStorage.setItem('userApiKey', data.api_key); // Store the API key
            // Use user.name and user.role from the 'user' object
            localStorage.setItem('userName', data.user.name + ' ' + (data.user.surname || '')); // Surname might be optional
            localStorage.setItem('userRole', data.user.role); // Save role

            // Redirect based on role (as per your existing signup.js logic)
            if (data.user.role === 'Seller') {
                window.location.href = 'sellers.php';
            } else if (data.user.role === 'Customer') {
                window.location.href = 'index.php';
            } else if (data.user.role === 'Admin') {
                window.location.href = 'admin.php'; // Assuming an admin page
            } else {
                window.location.href = 'index.php'; // Default fallback
            }

        } else {
            // If data.user is not present, it's a login failure message from backend
            // The PHP error handler should send 'message' on failure
            console.log("Login failed with message:", data.message);
            displayErrors([data.message || "Login failed. Please check your credentials."], 'login-form');
        }
    })
    .catch(error => {
        console.error("Fetch or API processing error:", error);
        displayErrors([error.message || "An unexpected error occurred during login. Please try again."], 'login-form');
    });
}