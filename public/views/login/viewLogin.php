<?php
$APP_DIR = $viewData['APP_DIR'];
// set page headers
$page_title = "";
include_once "views/_layouts/header.php";
?>

<!-- Login 9 - Bootstrap Brain Component -->
<section class="bg-primary py-3 py-md-4 py-xl-8 mt-md-4">
    <div class="container">

        <div class="row gy-4 align-items-center">
            <div class="col-12 col-md-6 col-xl-6">
                <div class="d-flex justify-content-center text-bg-primary">
                    <div class="col-12 col-xl-9">
                        <img class="img-fluid rounded mb-4" loading="lazy" src="./assets/img/sl-logo.png" width="245" height="80" alt="BootstrapBrain Logo">
                        <hr class="border-primary-subtle mb-4">
                        <h2 class="h1 mb-4">Medlemsregister</h2>
                        <p class="lead mb-5">Ett medlems- och seglingsregister för Sofia Linnea.</p>
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-grip-horizontal h2" viewBox="0 0 16 16">
                                <path d="M2 8a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm0-3a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm3 3a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm0-3a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm3 3a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm0-3a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm3 3a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm0-3a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm3 3a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm0-3a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 col-xl-5">

                <nav>
                    <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                        <button class="nav-link active" id="nav-login-tab" data-bs-toggle="tab" data-bs-target="#nav-login" type="button" role="tab" aria-controls="nav-login" aria-selected="true">Logga in</button>
                        <button class="nav-link" id="nav-register-tab" data-bs-toggle="tab" data-bs-target="#nav-register" type="button" role="tab" aria-controls="nav-register" aria-selected="false">Registrera dig</button>
                    </div>
                </nav>
                <div class="tab-content" id="nav-tabContent">
                    <!-- Inloggning -->
                    <div class="tab-pane fade show active" id="nav-login" role="tabpanel" aria-labelledby="nav-login-tab">
                        <div class="card border-0 rounded-0">
                            <div class="card-body p-3 p-md-4 p-xl-5">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-4">
                                            <h3>Logga in</h3>
                                            <p>Har du inget konto? <a href="#nav-register" data-bs-toggle="tab">Registrera dig</a></p>
                                        </div>
                                    </div>
                                </div>
                                <form id="loginForm" action="./login" method="POST">
                                    <div class="row gy-3 overflow-hidden">
                                        <div class="col-12">
                                            <div class="form-floating mb-3">
                                                <input type="email" class="form-control" name="email" id="loginEmail" placeholder="name@example.com" required>
                                                <label for="loginEmail" class="form-label">Mail</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-floating mb-3">
                                                <input type="password" class="form-control" name="password" id="password" value="" placeholder="Password" required>
                                                <label for="password" class="form-label">Lösenord</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="" name="rememberMe" id="rememberMe">
                                                <label class="form-check-label text-secondary" for="rememberMe">
                                                    Kom ihåg mitt användarnamn
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="d-grid">
                                                <button class="btn btn-primary btn-lg" type="submit">Logga in</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="d-flex gap-2 gap-md-4 flex-column flex-md-row justify-content-md-end mt-4">
                                            <a href="auth/bytlosenord">Glömt lösenord</a>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!-- Registrering -->
                    <div class="tab-pane fade" id="nav-register" role="tabpanel" aria-labelledby="nav-register-tab">
                        <div class="card border-0 rounded-0">
                            <div class="card-body p-3 p-md-4 p-xl-5">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-4">
                                            <h3>Registrera dig</h3>
                                            <p>Här kan du som är medlem i Föreningen Sofia Linnea registrera ett lösenord så att du kan logga in</p>
                                        </div>
                                    </div>
                                </div>
                                <form id="registerForm" action="./register" method="POST">
                                    <div class="row gy-3 overflow-hidden">
                                        <div class="col-12">
                                            <div class="form-floating mb-3">
                                                <input type="email" class="form-control" name="email" id="registerEmail" placeholder="name@example.com" required>
                                                <label for="RegisterEmail" class="form-label">Mail</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-floating mb-3">
                                                <input type="password" class="form-control" name="password" id="registerPassword" value="" placeholder="Password" describedBy="passwordHelp" required>
                                                <label for="registerPassword" class="form-label">Lösenord</label>
                                                <small id="passwordHelp" class="form-text text-muted">Lösenordet måste vara 8 tecken och innehålla minst en stor bokstav, en liten bokstav och en siffra.</small>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-floating mb-3">
                                                <input type="password" class="form-control" name="passwordRepeat" id="registerPasswordRepeat" value="" placeholder="Password" required>
                                                <label for="registerPasswordRepeat" class="form-label">Repetera lösenord</label>
                                            </div>
                                        </div>
                                        <div id="passwordError" class="alert alert-danger" style="display: none;"></div>
                                        <div class="col-12">
                                            <div class="d-grid">
                                                <button class="btn btn-primary btn-lg disabled" id="registerSubmit" type="submit">Registrera</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.getElementById('loginForm');
        const usernameInput = document.getElementById('loginEmail');
        const rememberCheckbox = document.getElementById('rememberMe');

        // Check if there's a stored username and populate the input field
        const storedUsername = localStorage.getItem('rememberedUsername');
        if (storedUsername) {
            usernameInput.value = storedUsername;
            rememberCheckbox.checked = true;
        }

        loginForm.addEventListener('submit', function(e) {
            const username = usernameInput.value;
            //Save email to local storage if the user selected that
            const rememberUsername = rememberCheckbox.checked;
            if (rememberUsername) {
                localStorage.setItem('rememberedUsername', username);
                console.log("Saved username:" + localStorage.getItem('rememberedUsername'));
            } else {
                localStorage.removeItem('rememberedUsername');
            }
        });

        //If the user switches to the register tab, the email from the login form is copied to the register form
        var registerTab = document.getElementById('nav-register-tab');
        registerTab.addEventListener('show.bs.tab', function() {
            const loginEmailValue = document.getElementById('loginEmail').value;
            document.getElementById('registerEmail').value = loginEmailValue;
        });

        document.getElementById('registerPassword').addEventListener('blur', checkPasswords);
        document.getElementById('registerPasswordRepeat').addEventListener('blur', checkPasswords);

    });

    function checkPasswords() {
        const password = document.getElementById('registerPassword').value;
        const repeatPassword = document.getElementById('registerPasswordRepeat').value;
        const submitButton = document.getElementById('registerSubmit');
        const errorElement = document.getElementById('passwordError');

        if (password === repeatPassword && password !== '') {
            submitButton.classList.remove('disabled');
            errorElement.style.display = 'none';
        } else {
            submitButton.classList.add('disabled');
            if (repeatPassword !== '') {
                errorElement.textContent = 'Lösenorden matchar inte!';
                errorElement.style.display = 'block';
            } else {
                errorElement.style.display = 'none';
            }
        }
    }
</script>

<?php
include_once "views/_layouts/footer.php";
?>