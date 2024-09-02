<?php

$config = require './config/config.php';
$APP_DIR = $config['APP_DIR'];

// set page headers
$page_title = "Begär nytt lösenord";
include_once $APP_DIR . "/layouts/header.php";
?>

<!-- Password Reset 3 - Bootstrap Brain Component -->
<section class="p-3 p-md-4 p-xl-5">
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-6 bsb-tpl-bg-platinum">
                <div class="d-flex flex-column justify-content-between h-100 p-3 p-md-4 p-xl-5">
                    <h3 class="m-0">Välkommen!</h3>
                    <img class="img-fluid rounded mx-auto my-4" loading="lazy" src="../assets/img/sl-logo.png" width="245" height="80" alt="Sofia Linnea Logo">
                    <p class="mb-0">Inget konto än? <a href="#!" class="link-secondary text-decoration-none">Registrera dig</a></p>
                </div>
            </div>
            <div class="col-12 col-md-6 bsb-tpl-bg-lotion">
                <div class="p-3 p-md-4 p-xl-5">
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-5">
                                <h2 class="h2">Återställ lösenord</h2>
                                <h3 class="fs-6 fw-normal text-secondary m-0">Ange epostadressen för ditt konto för att återställa lösenordet.</h3>
                            </div>
                        </div>
                    </div>
                    <form action="bytlosenord" method="post">
                        <div class="row gy-3 gy-md-4 overflow-hidden">
                            <div class="col-12">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" id="email" placeholder="namn@exempel.se" required>
                            </div>
                            <div class="col-12">
                                <div class="d-grid">
                                    <button class="btn bsb-btn-xl btn-primary" type="submit">Återställ lösenord</button>
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

<?php // footer
include_once $APP_DIR . "/layouts/footer.php";
?>