<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <!-- Using Bootswatch to get some styling -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5/dist/sandstone/bootstrap.min.css" crossorigin="anonymous">
    <!-- original Bootstrap CSS
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
     -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11/font/bootstrap-icons.min.css" integrity="sha256-9kPW/n5nn53j4WMRYAxe9c1rCY96Oogo/MKSVdKzPmI=" crossorigin="anonymous">
    <link href="https://cdn.datatables.net/2.0.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <title><?php echo $page_title; ?></title>
</head>

<body>

    <nav class="navbar navbar-expand-lg bg-primary" id="slnav" data-bs-theme="dark" style="color:azure;" aria-label="Besättningsregister navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Sofia Linnea</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#slNavbar" aria-controls="slNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="slNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <?php $isActive = ($page_title == "Besättning") ? "active" : ""; ?>
                        <a class="nav-link <?= $isActive ?>" aria-current="page" href="viewMedlem.php">Besättning</a>
                    </li>
                    <li class="nav-item">
                        <?php $isActive = ($page_title == "Seglingar") ? "active" : ""; ?>
                        <a class="nav-link <?= $isActive ?>" href="viewSegling.php">Seglingar</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="dropdown05" data-bs-toggle="dropdown" aria-expanded="false">Dropdown</a>
                        <ul class="dropdown-menu" aria-labelledby="dropdown05">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                        </ul>
                    </li>
                </ul>
                <div class="dark-mode-toggle">
                    <i id="darkModeIcon" class="bi bi-moon"></i>
                </div>
            </div>
        </div>
    </nav>

    <!-- container -->
    <div class="container">
        <div class='page-header'>
            <h1><?php echo $page_title ?></h1>
        </div>