<?php
$APP_DIR = $viewData['APP_DIR'];
// set page headers
$page_title = "";
$turnstileSiteKey = $_SERVER['TURNSTILE_SITE_KEY'];
include_once "views/_layouts/header.php";
?>

<!--Add Cloudflare Turnstile script -->
<!-- SRI via https://www.srihash.org/ -->
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" integrity="sha384-ztFLr92O7w7dOlmjOTuEGzcXpUsRKoAe0is03lxXyeYuXAfkccNA3rY0U0HWyuo0" crossorigin="anonymous" defer></script>

<!-- Login 13 - Bootstrap Brain Component -->
<section class="py-1 py-md-3">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4">
                <div class="card border border-light-subtle rounded-3 shadow-sm">
                    <div class="card-body p-2 p-md-3 p-xl-4 auth-bg">
                        <div class="text-center mb-3">
                            <img class="img-fluid" src="../assets/img/sl-logo.png" alt="Sofia Linnea Logo" width="75%">
                        </div>
                        <h2 class="fw-normal text-center mb-4">Skapa nytt konto</h2>
                        <p class="text-center mb-4">
                            För att skapa ett konto måste du vara medlem i föreningen och använda den mailadress du registrerade dig med.
                        </p>
                        <form id="registerForm" action="/auth/register" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $viewData["csrf_token"]; ?>">
                            <div class="row gy-2 overflow-hidden">
                                <div class="col-12">
                                    <div class="form-floating mb-3">
                                        <input type="email" class="form-control" name="email" id="email" placeholder="name@example.com" tabindex="1" required>
                                        <label for="email" class="form-label">Email</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" name="password" id="password" value="" placeholder="Lösenord" tabindex="2" required>
                                        <label for="password" class="form-label">Lösenord</label>
                                        <small id="passwordHelp" class="form-text text-muted">Lösenordet måste vara 8 tecken och innehålla minst en stor bokstav, en liten bokstav och en siffra.</small>
                                    </div>
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" name="verifyPassword" id="verifyPassword" value="" placeholder="Bekräfta lösenord" tabindex="3" required>
                                        <label for="verifyPassword" class="form-label">Bekräfta lösenord</label>
                                    </div>
                                    <div id="passwordError" class="alert alert-danger" style="display: none;"></div>
                                </div>
                                <div class="col-12">
                                    <div class="d-grid mb-3">
                                        <!-- The following line controls and configures the Turnstile widget. -->
                                        <div class="cf-turnstile mb-3" data-sitekey="<?php echo $turnstileSiteKey ?>" data-size="flexible" data-theme="light"></div>
                                        <!-- end. -->
                                        <button class="btn btn-primary btn-lg" id="submit" type="submit" tabindex="4">Skapa konto</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let email = document.getElementById('email');
        email.focus();

        updateTurnstileTheme();

        document.getElementById('password').addEventListener('blur', checkPasswords);
        document.getElementById('verifyPassword').addEventListener('blur', checkPasswords);

    });
</script>

<?php
include_once "views/_layouts/footer.php";
?>