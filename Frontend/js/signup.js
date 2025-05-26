document.addEventListener("DOMContentLoaded", function () {
    const signupForm = document.getElementById("signup-form");

    if (signupForm) {
        signupForm.addEventListener("submit", function (e) {
            e.preventDefault();
            validateSignUpForm();
        });

        // Add event listener for role change to dynamically show/hide fields
        const roleSelect = document.querySelector('select[name="role"]');
        if (roleSelect) {
            roleSelect.addEventListener('change', toggleRoleSpecificFields);
            // Call on load to set initial state based on default selected role
            toggleRoleSpecificFields(); 
        }
    }
});

function containsNumbers(str) {
    return /\d/.test(str);
}

function validatorOfEmail(email) {
    // Updated email regex to be more robust, matching the backend's expected format
    const emailR = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return emailR.test(email);
}

function validatorOfPassword(password) {
    // Password must be at least 8 characters, include uppercase, lowercase, digit, and a special character.
    // This regex is aligned with the backend validation.
    const passwordR = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]{8,}$/;
    return passwordR.test(password);
}


function clearErrors(formId) {
    let errorMsgElement;
    if (formId === 'login-form') {
        errorMsgElement = document.getElementById('login-error-messages');
    } else {
        errorMsgElement = document.getElementById('error-messages'); // For signup form
    }

    if (errorMsgElement) {
        errorMsgElement.innerHTML = ''; // Clear content
        errorMsgElement.style.display = 'none'; // **Hide the container when empty**
    }
}

/**
 * Displays error messages on the specified form and makes the container visible.
 * @param {string[]} errors - An array of error strings to display.
 * @param {string} formId - The ID of the form ('login-form' or 'signup-form').
 */
function displayErrors(errors, formId) {
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
    }

    // styles to ensure visibility and consistent appearance
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
}

// Function to dynamically show/hide input fields based on the selected role
function toggleRoleSpecificFields() {
    const role = document.querySelector('select[name="role"]').value;
    
    // Get all role-specific field containers
    const customerFields = document.getElementById('customer-fields');
    const sellerFields = document.getElementById('seller-fields');
    const adminFields = document.getElementById('admin-fields');

    // Get all input/select fields within these containers
    const customerInputs = customerFields ? customerFields.querySelectorAll('input, select') : [];
    const sellerInputs = sellerFields ? sellerFields.querySelectorAll('input, select') : [];
    const adminInputs = adminFields ? adminFields.querySelectorAll('input, select') : [];

    // Helper function to hide a section and disable its required inputs
    function hideSection(sectionElement, inputs) {
        if (sectionElement) {
            sectionElement.classList.add('hidden');
        }
        inputs.forEach(input => {
            input.removeAttribute('required'); // Remove required attribute
            input.value = ''; // Clear value when hidden 
        });
    }

    // Helper function to show a section and enable its required inputs
    function showSection(sectionElement, inputs) {
        if (sectionElement) {
            sectionElement.classList.remove('hidden');
        }
        inputs.forEach(input => {
    
            input.setAttribute('required', 'required'); 
        });
    }

    // Hide all sections and remove required attributes first
    hideSection(customerFields, customerInputs);
    hideSection(sellerFields, sellerInputs);
    hideSection(adminFields, adminInputs);

    // Show and enable required attributes for the relevant section
    if (role === 'Customer') {
        showSection(customerFields, customerInputs);
    } else if (role === 'Seller') {
        showSection(sellerFields, sellerInputs);
    } else if (role === 'Admin') {
        showSection(adminFields, adminInputs);
    }
}


function validateSignUpForm() {
    const username = document.querySelector('input[name="username"]').value.trim();
    const name = document.querySelector('input[name="name"]').value.trim();
    const email = document.getElementById("email").value.trim();
    const phone_num = document.querySelector('input[name="phone_num"]').value.trim();
    const password = document.getElementById("password").value.trim();
    const role = document.querySelector('select[name="role"]').value;

    let errors = [];
    let userData = {
        username: username,
        name: name,
        email: email,
        phone_num: phone_num,
        password_hashed: password, 
        role: role
    };

    if (!username) {
        errors.push('Username is required.');
    }

    if (!name) {
        errors.push('Name is required.');
    } else if (!/^[a-zA-Z\s\-]{2,50}$/.test(name)) { // Aligned with backend validation for name
        errors.push('Name can only contain letters, spaces and hyphens (2-50 characters).');
    }

    if (!email) {
        errors.push('Email is required.');
    } else if (!validatorOfEmail(email)) { // Using the updated validatorOfEmail
        errors.push('Invalid email format.');
    }

    if (!phone_num) {
        errors.push('Phone number is required.');
    } else if (!/^\d+$/.test(phone_num)) {
        errors.push('Phone number must contain only digits.');
    }

    if (!password) {
        errors.push('Password is required.');
    } else if (!validatorOfPassword(password)) { // Using the updated validatorOfPassword
        errors.push('Password must be at least 8 characters, include uppercase, lowercase, digit, and a special character.');
    }

    if (!role || role === "") { // Ensure role is selected
        errors.push('Role is required.');
    } else {
        // Collect role-specific data and validate
        if (role === 'Customer') {
            const surname = document.querySelector('input[name="surname"]').value.trim();
            if (!surname) {
                errors.push('Surname is required for customers.');
            } else if (!/^[a-zA-Z\s\-]{2,50}$/.test(surname)) { // Assuming similar validation for surname
                errors.push('Surname can only contain letters, spaces and hyphens (2-50 characters).');
            }
            userData.surname = surname;
        } else if (role === 'Seller') {
            const address = document.querySelector('input[name="address"]').value.trim();
            const website = document.querySelector('input[name="website"]').value.trim();
            const business_reg_num = document.querySelector('input[name="business_reg_num"]').value.trim();

            if (!address) {
                errors.push('Address is required for sellers.');
            }
            if (!website) {
                errors.push('Website is required for sellers.');
            } else if (!/^https?:\/\/(?:www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&\/=]*)$/.test(website)) {
                 errors.push('Invalid website format.');
            }
            if (!business_reg_num) {
                errors.push('Business Registration Number is required for sellers.');
            } else if (!/^\d+$/.test(business_reg_num)) {
                errors.push('Business Registration Number must contain only digits.');
            }

            userData.address = address;
            userData.website = website;
            userData.business_reg_num = business_reg_num;

        } else if (role === 'Admin') {
            const access_level = document.querySelector('input[name="access_level"]').value.trim();
            if (!access_level) {
                errors.push('Access Level is required for admins.');
            }
            userData.access_level = access_level;
        }
    }

    if (errors.length > 0) {
        displayErrors(errors, 'signup-form');
    } else {
        // Clear previous errors if any
        const errorMsgElement = document.getElementById('error-messages');
        if (errorMsgElement) {
            errorMsgElement.innerHTML = '';
        }

        sendToAPI(userData);
    }
}


// Function to send user registration data to the API
function sendToAPI(userData) {
    fetch('http://localhost/GA221/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        // Send all user data, including plaintext password (which will be hashed on the backend)
        body: JSON.stringify({ ...userData, type: 'Register' })
    })
    .then(response => response.text()) // Get response as text first to catch non-JSON errors
    .then(text => {
        try {
            const data = JSON.parse(text); // Attempt to parse as JSON

            if (data.status === "success") {
                // Hide forms and show success message
                document.getElementById('signup-form').classList.add('hidden');
                document.getElementById('login-form')?.classList.add('hidden'); 

                const formContainer = document.getElementById('form-container');
                let messageBox = document.getElementById('success-message-box');

                if (!messageBox) {
                    messageBox = document.createElement('div');
                    messageBox.id = 'success-message-box';
                    // These styles are better handled by CSS, but keeping them here for direct function
                    messageBox.style.color = 'green';
                    messageBox.style.marginTop = '10px';
                    messageBox.style.padding = '15px';
                    messageBox.style.border = '1px solid #4CAF50';
                    messageBox.style.backgroundColor = '#e8f5e9';
                    messageBox.style.borderRadius = '5px';
                    formContainer.prepend(messageBox);
                }

                const apiMsg = data?.api_key || 'API key not available'; // Corrected to directly access api_key

                messageBox.innerHTML = `
                    <p><strong>${data.message || 'Registration successful!'}</strong></p>    
                    <p>You will be redirected to the home page shortly.</p>
                `;
                
                localStorage.setItem('userApiKey', apiMsg); // Store API key - 
                localStorage.setItem('userName', userData.name + ' ' + userData.username); // Store user's display name - 

                // Redirect after a short delay based on the role
                setTimeout(() => {
                    if (userData.role === 'Seller') {
                        window.location.href = '../php/sellers.php'; // Assuming admins go to admin page
                    } else if (userData.role === 'Customer') {
                        window.location.href = '../php/index.php'; // Assuming customers go to index
                    } else if (userData.role === 'Admin') {
                        window.location.href = '../php/admin.php'; // Assuming admins go to admin page
                    } else {
                        window.location.href = '../php/index.php'; // Default fallback
                    }
                }, 5000);
            } else {
                // Display errors received from the API
                displayErrors([data.message || 'Registration failed.'], 'signup-form');
            }
        } catch (error) {
            console.error("JSON parsing error or unexpected API response:", text);
            displayErrors(['An unexpected error occurred during registration. Please try again.'], 'signup-form');
        }
    })
    .catch(error => {
        console.error("Network or API call error:", error);
        displayErrors(['Failed to connect to the server. Please check your internet connection.'], 'signup-form');
    });
}