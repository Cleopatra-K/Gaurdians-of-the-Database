document.addEventListener("DOMContentLoaded", function () {
    const signupForm = document.getElementById("signup-form");
    console.log("DEBUG(JS - Signup Init): DOMContentLoaded fired. Signup form element found:", !!signupForm);

    if (signupForm) {
        signupForm.addEventListener("submit", function (e) {
            e.preventDefault();
            console.log("DEBUG(JS - Signup Init): Signup form submission intercepted.");
            validateSignUpForm();
        });

        const roleSelect = document.querySelector('select[name="role"]');
        console.log("DEBUG(JS - Signup Init): Role select element found:", !!roleSelect);
        if (roleSelect) {
            roleSelect.addEventListener('change', toggleRoleSpecificFields);
            // Call on load to set initial state based on default selected role
            toggleRoleSpecificFields();
        }
    }
});

function containsNumbers(str) {
    console.log("DEBUG(JS - Signup): Checking if '" + str + "' contains numbers.");
    return /\d/.test(str);
}

function validatorOfEmail(email) {
    console.log("DEBUG(JS - Signup): Validating email format for:", email);
    const emailR = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return emailR.test(email);
}

function validatorOfPassword(password) {
    console.log("DEBUG(JS - Signup): Validating password strength.");
    const passwordR = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]{8,}$/;
    return passwordR.test(password);
}

function clearErrors(formId) {
    console.log("DEBUG(JS - Signup): Clearing errors for form:", formId);
    let errorMsgElement;
    if (formId === 'login-form') {
        errorMsgElement = document.getElementById('login-error-messages');
    } else {
        errorMsgElement = document.getElementById('error-messages'); // For signup form
    }

    if (errorMsgElement) {
        errorMsgElement.innerHTML = ''; // Clear content
        errorMsgElement.style.display = 'none'; // **Hide the container when empty**
        console.log("DEBUG(JS - Signup): Error messages cleared and container hidden for", formId);
    } else {
        console.log("DEBUG(JS - Signup): No error message element found for", formId, "to clear.");
    }
}

/**
 * Displays error messages on the specified form and makes the container visible.
 * @param {string[]} errors - An array of error strings to display.
 * @param {string} formId - The ID of the form ('login-form' or 'signup-form').
 */
function displayErrors(errors, formId) {
    console.log("DEBUG(JS - Signup): Displaying errors for form:", formId, "Errors:", errors);
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
        console.log("DEBUG(JS - Signup): Created new error message element:", errorMsgElement.id);
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
    console.log("DEBUG(JS - Signup): Error messages displayed for", formId);
}

// Function to dynamically show/hide input fields based on the selected role
function toggleRoleSpecificFields() {
    const role = document.querySelector('select[name="role"]').value;
    console.log("DEBUG(JS - Signup): Toggling fields for role:", role);

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
            console.log("DEBUG(JS - Signup): Hiding section:", sectionElement.id);
        }
        inputs.forEach(input => {
            input.removeAttribute('required'); // Remove required attribute
            input.value = ''; // Clear value when hidden
            console.log("DEBUG(JS - Signup): Removed 'required' and cleared value for input:", input.name || input.id);
        });
    }

    // Helper function to show a section and enable its required inputs
    function showSection(sectionElement, inputs) {
        if (sectionElement) {
            sectionElement.classList.remove('hidden');
            console.log("DEBUG(JS - Signup): Showing section:", sectionElement.id);
        }
        inputs.forEach(input => {
            input.setAttribute('required', 'required');
            console.log("DEBUG(JS - Signup): Added 'required' for input:", input.name || input.id);
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
    console.log("DEBUG(JS - Signup): Starting validateSignUpForm().");
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
        password_hashed: password, // This will be hashed on the backend
        role: role
    };
    console.log("DEBUG(JS - Signup): Collected form data:", userData);

    if (!username) {
        errors.push('Username is required.');
    }

    if (!name) {
        errors.push('Name is required.');
    } else if (!/^[a-zA-Z\s\-]{2,50}$/.test(name)) {
        errors.push('Name can only contain letters, spaces and hyphens (2-50 characters).');
    }

    if (!email) {
        errors.push('Email is required.');
    } else if (!validatorOfEmail(email)) {
        errors.push('Invalid email format.');
    }

    if (!phone_num) {
        errors.push('Phone number is required.');
    } else if (!/^\d+$/.test(phone_num)) {
        errors.push('Phone number must contain only digits.');
    }

    if (!password) {
        errors.push('Password is required.');
    } else if (!validatorOfPassword(password)) {
        errors.push('Password must be at least 8 characters, include uppercase, lowercase, digit, and a special character.');
    }

    if (!role || role === "") {
        errors.push('Role is required.');
    } else {
        if (role === 'Customer') {
            const surname = document.querySelector('input[name="surname"]').value.trim();
            console.log("DEBUG(JS - Signup): Customer role selected. Surname:", surname);
            if (!surname) {
                errors.push('Surname is required for customers.');
            } else if (!/^[a-zA-Z\s\-]{2,50}$/.test(surname)) {
                errors.push('Surname can only contain letters, spaces and hyphens (2-50 characters).');
            }
            userData.surname = surname;
        } else if (role === 'Seller') {
            const address = document.querySelector('input[name="address"]').value.trim();
            const website = document.querySelector('input[name="website"]').value.trim();
            const business_reg_num = document.querySelector('input[name="business_reg_num"]').value.trim();
            console.log("DEBUG(JS - Signup): Seller role selected. Address:", address, "Website:", website, "Business Reg Num:", business_reg_num);

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
            console.log("DEBUG(JS - Signup): Admin role selected. Access Level:", access_level);
            if (!access_level) {
                errors.push('Access Level is required for admins.');
            }
            userData.access_level = access_level;
        }
    }

    if (errors.length > 0) {
        console.log("DEBUG(JS - Signup): Validation failed. Errors:", errors);
        displayErrors(errors, 'signup-form');
    } else {
        console.log("DEBUG(JS - Signup): Validation successful. Calling sendToAPI with userData.");
        clearErrors('signup-form');
        sendToAPI(userData);
    }
}

// Function to send user registration data to the API
function sendToAPI(userData) {
    console.log("DEBUG(JS - Signup): Sending registration payload to API:", { ...userData, type: 'Register' });
    fetch('/GotD/GOTapi.php', { // Ensure this path is correct for your setup!
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        // Send all user data, including plaintext password (which will be hashed on the backend)
        body: JSON.stringify({ ...userData, type: 'Register' })
    })
    .then(response => {
        console.log("DEBUG(JS - Signup): Registration fetch response status:", response.status, response.statusText);
        if (!response.ok) {
            return response.text().then(text => { // Get as text to catch malformed JSON
                console.error("DEBUG(JS - Signup): Registration API returned non-OK status. Raw response:", text);
                try {
                    const errorData = JSON.parse(text);
                    throw new Error(errorData.message || 'Registration failed with an unexpected server error.');
                } catch (e) {
                    throw new Error('Server responded with an error, but response was not valid JSON.');
                }
            });
        }
        return response.text(); // Get as text first
    })
    .then(text => {
        console.log("DEBUG(JS - Signup): Raw registration API response text:", text);
        try {
            const data = JSON.parse(text); // Attempt to parse as JSON
            console.log("DEBUG(JS - Signup): Parsed registration API response data:", data);

            if (data.status === "success") {
                console.log("DEBUG(JS - Signup): Registration successful!");
                document.getElementById('signup-form').classList.add('hidden');
                document.getElementById('login-form')?.classList.add('hidden');

                const formContainer = document.getElementById('form-container');
                let messageBox = document.getElementById('success-message-box');

                if (!messageBox) {
                    messageBox = document.createElement('div');
                    messageBox.id = 'success-message-box';
                    messageBox.style.color = 'green';
                    messageBox.style.marginTop = '10px';
                    messageBox.style.padding = '15px';
                    messageBox.style.border = '1px solid #4CAF50';
                    messageBox.style.backgroundColor = '#e8f5e9';
                    messageBox.style.borderRadius = '5px';
                    formContainer.prepend(messageBox);
                }

                // --- CRITICAL DEBUGGING HERE ---
                const apiMsg = data?.api_key || 'API key not available';
                // Attempt to get user_id from various possible locations in the response
                const userIdToStore = data.user?.user_id || data.user?.id || data.user_id || data.id;
                const userNameToStore = data.user?.name ? (data.user.name + ' ' + (data.user.surname || '')) : (userData.name + ' ' + (userData.surname || ''));
                const userRoleToStore = data.user?.role || userData.role;

                console.log("DEBUG(JS - Signup): Attempting to store sessionStorage for signup:");
                console.log("DEBUG(JS - Signup): Extracted userId for storage (from API response):", userIdToStore);
                console.log("DEBUG(JS - Signup): Extracted api_key for storage (from API response):", apiMsg);
                console.log("DEBUG(JS - Signup): Extracted userName for storage (from API response/form data):", userNameToStore);
                console.log("DEBUG(JS - Signup): Extracted userRole for storage (from API response/form data):", userRoleToStore);


                sessionStorage.setItem('user_id', userIdToStore);
                sessionStorage.setItem('userApiKey', apiMsg);
                sessionStorage.setItem('userName', userNameToStore);
                sessionStorage.setItem('userRole', userRoleToStore);

                console.log("DEBUG(JS - Signup): sessionStorage **after** setting values:");
                console.log("user_id:", sessionStorage.getItem('user_id'));
                console.log("userApiKey:", sessionStorage.getItem('userApiKey'));
                console.log("userName:", sessionStorage.getItem('userName'));
                console.log("userRole:", sessionStorage.getItem('userRole'));

                messageBox.innerHTML = `
                    <p><strong>${data.message || 'Registration successful!'}</strong></p>
                    <p>You will be redirected to the home page shortly.</p>
                `;

                setTimeout(() => {
                    console.log("DEBUG(JS - Signup): Redirecting to page based on role:", userRoleToStore);
                    if (userRoleToStore === 'Seller') {
                        window.location.href = '../php/seller.php';
                    } else if (userRoleToStore === 'Customer') {
                        window.location.href = '../php/products.php';
                    } else if (userRoleToStore === 'Admin') {
                        window.location.href = '../php/admin.php';
                    } else {
                        window.location.href = '../php/products.php'; // Default fallback
                    }
                }, 5000); // 5 seconds
            } else {
                console.log("DEBUG(JS - Signup): Registration failed according to API response. Message:", data.message);
                displayErrors([data.message || 'Registration failed.'], 'signup-form');
            }
        } catch (error) {
            console.error("DEBUG(JS - Signup): JSON parsing error or unexpected API response during registration:", error, "Original text:", text);
            displayErrors(['An unexpected error occurred during registration. Please try again.'], 'signup-form');
        }
    })
    .catch(error => {
        console.error("DEBUG(JS - Signup): Network or API call error during registration:", error);
        displayErrors(['Failed to connect to the server. Please check your internet connection.'], 'signup-form');
    });
}