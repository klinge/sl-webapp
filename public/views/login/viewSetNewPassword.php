<?php

$APP_DIR = $viewData['APP_DIR'];
// set page headers
$page_title = "";
$turnstileSiteKey = $_SERVER['TURNSTILE_SITE_KEY'];
include_once "views/_layouts/header.php";
?>

<!--Add Cloudflare Turnstile script -->
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" integrity="sha384-1lIn6ASvp1x/vuPW5FZCww6p3g4eQxROcdx92D7d6FxYNUhRp0UXYn8r8goX0j1V" crossorigin="anonymous" defer></script>

<section class="py-3 py-md-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4">
                <div class="card border border-light-subtle rounded-3 shadow-sm">
                    <div class="card-body p-2 p-md-3 p-xl-4">
                        <div class="text-center mb-3">
                            <img src="/assets/img/sl-logo.png" alt="Sofia Linnea" width="75%">
                        </div>
                        <h2 class="fs-4 fw-normal text-center mb-2">Återställ ditt lösenord</h2>
                        <h3 class="fs-6 fw-normal text-secondary text-center mb-4">Du kan nu skapa ett nytt lösenord att logga in med.</h3>
                        <form action="/auth/sparalosenord" method="post" id="resetPasswordForm">
                            <!-- Send some needed data to the server with hidden form fields-->
                            <input type="hidden" name="csrf_token" value="<?php echo $viewData["csrf_token"]; ?>">
                            <input type="hidden" class="form-control" name="email" id="email" value="<?php echo $viewData['email'] ?>">
                            <input type="hidden" class="form-control" name="token" id="token" value="<?php echo $viewData['token'] ?>">
                            <div class="row gy-3 gy-md-4 overflow-hidden">
                                <div class="col-12">
                                    <div class="form-floating mb-2">
                                        <input type="password" class="form-control" name="password" id="password" value="" placeholder="Lösenord" required>
                                        <label for="password" class="form-label">Lösenord</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating mb-2">
                                        <input type="password" class="form-control" name="password2" id="password2" value="" placeholder="Repetera lösenord" required>
                                        <label for="password2" class="form-label">Repetera lösenord</label>
                                    </div>
                                </div>
                                <div id="passwordError" class="alert alert-danger d-none"></div>
                                <div class="col-12">
                                    <div class="d-grid">
                                        <!-- The following line controls and configures the Turnstile widget. -->
                                        <div class="cf-turnstile mb-3" data-sitekey="<?php echo $turnstileSiteKey ?>" data-size="flexible" data-theme="light"></div>
                                        <!-- end. -->
                                        <button class="btn btn-lg btn-primary" type="submit" id="submitBtn">Spara nytt lösenord</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <hr class="mb-2 border-secondary-subtle">
                            <div class="text-end p-3">
                                <a href="/login" class="link-secondary text-decoration-none">Tillbaka till logga in</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php // footer
include_once "views/_layouts/footer.php";
?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('password2').addEventListener('blur', checkPasswords);

        document.getElementById('resetPasswordForm').addEventListener('submit', function(event) {
            event.preventDefault();
            // Don't submit if passwords don't match
            if (checkPasswords()) {
                this.submit();
            }
        });
    });
</script>