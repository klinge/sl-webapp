
// Add this new function to check and update Turnstile theme
function updateTurnstileTheme() {
    const bodyTheme = document.documentElement.getAttribute('data-bs-theme');
    const turnstileWidgets = document.querySelectorAll('.cf-turnstile');
    
    if (bodyTheme === 'dark') {
        turnstileWidgets.forEach(widget => {
            widget.setAttribute('data-theme', 'dark');
        });
    }
}

function onLoginSubmit() {
    const loginForm = document.getElementById('loginForm');
    const rememberUsername = document.getElementById('rememberMe').checked;

    //Save email to local storage if the user selected that
    if (rememberUsername) {
        let username = document.getElementById('email').value;
        localStorage.setItem('rememberedUsername', username);
        console.log("Saved username:" + localStorage.getItem('rememberedUsername'));
    } else {
        localStorage.removeItem('rememberedUsername');
    }
    //And then submit the form
    loginForm.submit();
    return true;
}

function onRegisterSubmit() {
    const form = document.getElementById('registerForm');
    form.submit();
    return true;
}

function onFormSubmit() {
    const form = document.getElementById('bytlosenForm');
    form.submit();
    return true;
}

function checkPasswords() {
    const password = document.getElementById('password').value;
    const repeatPassword = document.getElementById('password2').value;
    const submitButton = document.getElementById('submitBtn');
    const errorElement = document.getElementById('passwordError');

    if (password === repeatPassword && password !== '') {
        submitButton.classList.remove('disabled');
        errorElement.classList.add('d-none');
        return true;
    } else {
        submitButton.classList.add('disabled');
        if (repeatPassword !== '') {
            errorElement.textContent = 'Lösenorden matchar inte!';
            errorElement.classList.remove('d-none');
            return false;
        } else {
            errorElement.classList.add('d-none');
            return false;
        }
    }
}
