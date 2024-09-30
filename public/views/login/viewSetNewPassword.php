<?php

$APP_DIR = $viewData['APP_DIR'];
// set page headers
$page_title = "";
include_once "views/_layouts/header.php";

?>


<!-- Password Reset 3 - Bootstrap Brain Component -->
<section class="p-3 p-md-4 p-xl-5">
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-6 bsb-tpl-bg-platinum">
                <div class="d-flex flex-column justify-content-between h-100 p-3 p-md-4 p-xl-5">
                    <h3 class="m-0">Välkommen!</h3>
                    <img class="img-fluid rounded mx-auto my-4" loading="lazy" src="<?php echo $BASE_URL ?>/assets/img/sl-logo.png" width="245" height="80" alt="Sofia Linnea Logo">
                    <p class="mb-0">Inget konto än? <a href="#!" class="link-secondary text-decoration-none">Registrera dig</a></p>
                </div>
            </div>
            <div class="col-12 col-md-6 bsb-tpl-bg-lotion">
                <div class="p-3 p-md-4 p-xl-5">
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-5">
                                <h2 class="h2">Skapa ett nytt lösenord</h2>
                                <h3 class="fs-6 fw-normal text-secondary m-0">Du kan nu skapa ett nytt lösenord att logga in med.</h3>
                            </div>
                        </div>
                    </div>
                    <form action="<?php echo $BASE_URL ?>/auth/sparalosenord" method="post" id="resetPasswordForm">
                        <!-- Send some needed data to the server with hidden form fields-->
                        <input type="hidden" name="csrf_token" value="<?php echo $viewData["csrf_token"]; ?>">
                        <input type="hidden" class="form-control" name="email" id="email" value="<?php echo $viewData['email'] ?>">
                        <input type="hidden" class="form-control" name="token" id="token" value="<?php echo $viewData['token'] ?>">

                        <div class="row gy-3 gy-md-4 overflow-hidden">
                            <div class="col-12">
                                <div class="form-floating mb-3">
                                    <input type="password" class="form-control" name="password" id="password" value="" placeholder="Lösenord" required>
                                    <label for="password" class="form-label">Lösenord</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating mb-3">
                                    <input type="password" class="form-control" name="password2" id="password2" value="" placeholder="Repetera lösenord" required>
                                    <label for="password2" class="form-label">Repetera lösenord</label>
                                </div>
                            </div>
                            <div id="passwordError" class="alert alert-danger d-none"></div>
                            <div class="col-12">
                                <div class="d-grid">
                                    <button class="btn bsb-btn-xl btn-primary" type="submit" id="submitBtn">Spara nytt lösenord</button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="row">
                        <div class="col-12">
                            <hr class="mt-5 mb-4 border-secondary-subtle">
                            <div class="text-end">
                                <a href="#!" class="link-secondary text-decoration-none">Tillbaka till logga in</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    //Verify that the passwords match, disable submit button if not
    document.getElementById('password2').addEventListener('blur', function() {
        var password = document.getElementById('password').value;
        var confirmPassword = document.getElementById('password2').value;
        var errorElement = document.getElementById('passwordError');
        var submitButton = document.getElementById('submitBtn');

        if (password !== confirmPassword) {
            errorElement.textContent = 'Lösenorden matchar inte';
            errorElement.classList.remove('d-none');
            errorElement.classList.add('d-block');
            submitButton.disabled = true;
        } else {
            errorElement.textContent = '';
            errorElement.classList.remove('d-block');
            errorElement.classList.add('d-none');
            submitButton.disabled = false;
        }
    });
</script>

<?php // footer
include_once "views/_layouts/header.php";
?>