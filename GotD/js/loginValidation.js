// for login form only
document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.getElementById("login-form");
    console.log("DEBUG(JS - Login Init): DOMContentLoaded fired. Login form element found:", !!loginForm);

    if (loginForm) {
        loginForm.addEventListener("submit", function (l) {
            l.preventDefault();
            console.log("DEBUG(JS - Login Init): Login form submission intercepted.");
            validateLoginF();
        });
    }
});

//Email validation
function validatorOfEmail(email) {
    console.log("DEBUG(JS - Login): Validating email format for:", email);
    const emailR = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;
    return emailR.test(email);
}

//Password validator
function validatorOfPassword(password) {
    console.log("DEBUG(JS - Login): Validating password strength.");
    const passwordR = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
    return passwordR.test(password);
}

function clearErrors(formId) {
    console.log("DEBUG(JS - Login): Clearing errors for form:", formId);
    let errorMsgElement;
    if (formId === 'login-form') {
        errorMsgElement = document.getElementById('login-error-messages');
    } else {
        errorMsgElement = document.getElementById('error-messages'); // For signup form
    }

    if (errorMsgElement) {
        errorMsgElement.innerHTML = ''; // Clear content
        errorMsgElement.style.display = 'none'; // **Hide the container when empty**
        console.log("DEBUG(JS - Login): Error messages cleared and container hidden for", formId);
    } else {
        console.log("DEBUG(JS - Login): No error message element found for", formId, "to clear.");
    }
}

/**
 * Displays error messages on the specified form and makes the container visible.
 * @param {string[]} errors - An array of error strings to display.
 * @param {string} formId - The ID of the form ('login-form' or 'signup-form').
 */
function displayErrors(errors, formId) {
    console.log("DEBUG(JS - Login): Displaying errors for form:", formId, "Errors:", errors);
    let errorMsgElement;
    if (formId === 'login-form') {
        errorMsgElement = document.getElementById('login-error-messages');
    } else {
        errorMsgElement = document.getElementById('error-messages'); // For signup form
    }

    // If the div doesn't exist, create it dynamically
    if (!errorMsgElement) {
        errorMsgElement = document.createElement('div');
        errorMsgElement.id = (formId === 'login-form') ? 'login-error-messages' : 'error-messages';
        // Append it to the form
        document.getElementById(formId).prepend(errorMsgElement);
        console.log("DEBUG(JS - Login): Created new error message element:", errorMsgElement.id);
    }

    // Always apply these styles to ensure visibility and consistent appearance
    errorMsgElement.style.color = '#d9534f';
    errorMsgElement.style.backgroundColor = '#fce8e8';
    errorMsgElement.style.border = '1px solid #d9534f';
    errorMsgElement.style.padding = '10px';
    errorMsgElement.style.marginTop = '10px';
    errorMsgElement.style.marginBottom = '10px';
    errorMsgElement.style.borderRadius = '5px';
    errorMsgElement.style.textAlign = 'left';
    errorMsgElement.style.fontSize = '0.9em';
    errorMsgElement.style.fontFamily = 'Arial, sans-serif';
    errorMsgElement.style.display = 'block'; // **Make it visible when displaying errors**

    // Populate with error messages
    errorMsgElement.innerHTML = errors.map(error => `<p>${error}</p>`).join('');
    console.log("DEBUG(JS - Login): Error messages populated for", formId);
}

function validateLoginF() {
    console.log("DEBUG(JS - Login): Starting validateLoginF().");
    let username = document.querySelector('input[name="login-username"]').value.trim();
    let password = document.querySelector('input[name="login-password"]').value.trim();

    console.log("DEBUG(JS - Login): Collected login credentials - Username:", username, "Password (length):", password.length);

    let errors = [];

    if (!username) {
        errors.push('Username is required.');
    } // No specific format validation for username unless your backend has one

    if (!password) {
        errors.push('Password is required.');
    } else if (!validatorOfPassword(password)) {
        errors.push('Password must be at least 8 characters, contain uppercase, lowercase, a digit, and a symbol.');
    }

    if (errors.length > 0) {
        console.log("DEBUG(JS - Login): Login validation failed. Errors:", errors);
        displayErrors(errors, 'login-form');
    } else {
        console.log("DEBUG(JS - Login): Login validation successful. Calling sendToAPILogin().");
        clearErrors('login-form'); // Clear previous errors when submitting
        sendToAPILogin(username, password); // Pass username and password
    }
}


function sendToAPILogin(username, password) { // Function now accepts username
    console.log("DEBUG(JS - Login): Attempting login with username:", username);
    const payload = {
        username: username, // Send username
        password: password,
        type: 'Login'
    };
    console.log("DEBUG(JS - Login): Login Payload sent to API:", payload);

    fetch('/GotD/GOTapi.php', { 
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload) // Send the payload object
    })
    .then(response => {
        console.log("DEBUG(JS - Login): Login fetch response status:", response.status, response.statusText);
        if (!response.ok) {
            // If not OK, read the response as text to get the PHP error message
            return response.json().then(errorData => {
                console.error("DEBUG(JS - Login): Login API returned error JSON:", errorData);
                throw new Error(errorData.message || 'Login failed with an unexpected error.');
            }).catch(() => {
                console.error("DEBUG(JS - Login): Login API returned non-JSON error (check PHP logs for raw output).");
                throw new Error('Incorrect username or password. Please try again.'); // Generic error for non-JSON
            });
        }
        return response.json(); // Parse JSON if response is OK
    })
    .then(data => {
        console.log("DEBUG(JS - Login): Login API success response data:", data);

        // Check for 'user' object to confirm success
        if (data && data.user) {
            alert('Login successful!');
            console.log("DEBUG(JS - Login): User object found in login response:", data.user);

            const userIdFromAPI = data.user.user_id || data.user.id || data.user_id || data.id; // Try multiple paths for user ID
            const userApiKeyFromAPI = data.api_key;
            const userNameFromAPI = data.user.name + ' ' + (data.user.surname || '');
            const userRoleFromAPI = data.user.role;

            console.log("DEBUG(JS - Login): Extracted user_id for sessionStorage:", userIdFromAPI);
            console.log("DEBUG(JS - Login): Extracted userApiKey for sessionStorage:", userApiKeyFromAPI);
            console.log("DEBUG(JS - Login): Extracted userName for sessionStorage:", userNameFromAPI);
            console.log("DEBUG(JS - Login): Extracted userRole for sessionStorage:", userRoleFromAPI);

            sessionStorage.setItem('user_id', userIdFromAPI);
            sessionStorage.setItem('userApiKey', userApiKeyFromAPI);
            sessionStorage.setItem('userName', userNameFromAPI);
            sessionStorage.setItem('userRole', userRoleFromAPI);

            console.log("DEBUG(JS - Login): sessionStorage **after** setting values:");
            console.log("user_id:", sessionStorage.getItem('user_id'));
            console.log("userApiKey:", sessionStorage.getItem('userApiKey'));
            console.log("userName:", sessionStorage.getItem('userName'));
            console.log("userRole:", sessionStorage.getItem('userRole'));

            // Redirect based on role
            if (userRoleFromAPI === 'Seller') {
                console.log("DEBUG(JS - Login): Redirecting to seller.php.");
                window.location.href = '../php/seller.php';
            } else if (userRoleFromAPI === 'Customer') {
                console.log("DEBUG(JS - Login): Redirecting to products.php.");
                window.location.href = '../php/products.php';
            } else if (userRoleFromAPI === 'Admin') {
                console.log("DEBUG(JS - Login): Redirecting to admin.php.");
                window.location.href = 'admin.php'; // Assuming an admin page relative to the current path
            } else {
                console.log("DEBUG(JS - Login): Redirecting to products.php (default fallback).");
                window.location.href = 'products.php'; // Default fallback
            }

        } else {
            // The PHP error handler should send 'message' on failure
            console.log("DEBUG(JS - Login): Login failed, no user object found in response. Message:", data.message);
            displayErrors([data.message || "Login failed. Please check your credentials."], 'login-form');
        }
    })
    .catch(error => {
        console.error("DEBUG(JS - Login): Fetch or API processing error during login:", error);
        displayErrors([error.message || "An unexpected error occurred during login. Please try again."], 'login-form');
    });
}