function showForm(formN) {
    const loginForm = document.getElementById('login-form');
    const signupForm = document.getElementById('signup-form');

    if (formN==='login') {
        loginForm.classList.remove('hidden');
        signupForm.classList.add('hidden');
    } else if (formN==='signup') {
        signupForm.classList.remove('hidden');
        loginForm.classList.add('hidden');
    }
}
