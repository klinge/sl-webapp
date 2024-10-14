<?php
$APP_DIR = $viewData['APP_DIR'];
// set page headers
$page_title = "";
$turnstileSiteKey = $_SERVER['TURNSTILE_SITE_KEY'];
include_once "views/_layouts/header.php";
?>

<!--Add Cloudflare Turnstile script -->
<!-- SRI via https://www.srihash.org/ -->
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" integrity="sha384-1lIn6ASvp1x/vuPW5FZCww6p3g4eQxROcdx92D7d6FxYNUhRp0UXYn8r8goX0j1V" crossorigin="anonymous" defer></script>

<!-- Login 8 - Bootstrap Brain Component -->
<section class="p-3 p-md-3 p-xl-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-xxl-11">
                <div class="card border-light-subtle shadow-sm">
                    <div class="row g-0">
                        <div class="col-12 col-md-6 d-none d-md-block">
                            <img class="img-fluid rounded-start w-100 h-80 object-fit-cover" loading="lazy" src="../assets/img/sl-segel.webp" alt="Välkommeen tillbaka till Sofia Linnea Medlemssidor!">
                            <div class="card-img-overlay col-12 col-md-6">
                                <h2 class="card-title text-light fw-bolder text-uppercase pt-5">Medlemssidorna</h2>
                                <p class="card-text text-light fs-5">
                                    Här kan du som är medlem i föreningen logga in och <br />
                                    administrera dina medlemsuppgifter.
                                </p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 d-flex align-items-center justify-content-center">
                            <div class="col-12 col-lg-11 col-xl-10">
                                <div class="card-body p-3 p-md-4 p-xl-5">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="mb-5">
                                                <div class="text-center mb-4">
                                                    <img class="img-fluid" src="../assets/img/sl-logo.png" alt="Sofia Linnea Logo">
                                                </div>
                                                <h3 class="text-center">Välkommen tillbaka!</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <form id="loginForm" action="./login" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo $viewData["csrf_token"]; ?>">
                                        <div class="row gy-3 overflow-hidden">
                                            <div class="col-12">
                                                <div class="form-floating mb-3">
                                                    <input type="email" class="form-control" name="email" id="email" placeholder="name@example.com" tabindex="1" required autofocus>
                                                    <label for="email" class="form-label">Email</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating mb-3">
                                                    <input type="password" class="form-control" name="password" id="password" value="" placeholder="Lösenord" tabindex="2" required>
                                                    <label for="password" class="form-label">Lösenord</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="" name="rememberMe" id="rememberMe" tabindex="3">
                                                    <label class="form-check-label text-secondary" for="rememberMe">
                                                        Kom ihåg mitt användarnamn
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-grid">
                                                    <!-- The following line controls and configures the Turnstile widget. -->
                                                    <div class="cf-turnstile mb-3" data-sitekey="<?php echo $turnstileSiteKey ?>" data-size="flexible" data-theme="light"></div>
                                                    <!-- end. -->
                                                    <button class="btn btn-primary btn-lg"
                                                        onclick="onLoginSubmit();"
                                                        tabindex="4">Logga in
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="d-flex gap-2 gap-md-4 flex-column flex-md-row justify-content-md-center mt-5">
                                                <a href="/auth/register" class="link-secondary text-decoration-none">Skapa nytt konto</a>
                                                <a href="/auth/bytlosenord" tabindex="5" class="link-secondary text-decoration-none">Glömt lösenord</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
        const usernameInput = document.getElementById('email');
        const rememberCheckbox = document.getElementById('rememberMe');

        updateTurnstileTheme();

        // Check if there's a stored username and populate the input field
        const storedUsername = localStorage.getItem('rememberedUsername');
        console.log("Stored username is: " + storedUsername);
        if (storedUsername) {
            usernameInput.value = storedUsername;
            rememberCheckbox.checked = true;
        }
    });
</script>

<?php
include_once "views/_layouts/footer.php";
?>