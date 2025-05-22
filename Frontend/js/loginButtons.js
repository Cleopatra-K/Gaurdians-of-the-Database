//u23547121 CT Kwenda

console.log("login.js loaded");
function showForm(formName) {
    // const loginForm = document.getElementById("login-form");
    // const signupForm = document.getElementById("signup-form");

    if (formName === 'login') {
        //document.getElementById('login-form').classList.remove('hidden');
        document.getElementById('signup-form').classList.add('hidden');
    } else if (formName === 'signup') {
        document.getElementById('signup-form').classList.remove('hidden');
        //document.getElementById('login-form').classList.add('hidden');
    }
}

