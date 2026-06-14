<?php
$current = basename($_SERVER['PHP_SELF']);
$dir = basename(dirname($_SERVER['PHP_SELF']));
$isSubPage = $dir === 'pages';

$navLinks = [
    'index.php'        => ['label'=>'Dashboard',    'icon'=>'bi-house-fill'],
    'patients.php'     => ['label'=>'Patients',      'icon'=>'bi-people-fill'],
    'doctors.php'      => ['label'=>'Doctors',       'icon'=>'bi-person-badge-fill'],
    'appointments.php' => ['label'=>'Appointments',  'icon'=>'bi-calendar2-check-fill'],
    'treatments.php'   => ['label'=>'Treatments',    'icon'=>'bi-clipboard2-pulse-fill'],
    'prescriptions.php'=> ['label'=>'Prescriptions', 'icon'=>'bi-capsule-pill'],
    'billing.php'      => ['label'=>'Billing',       'icon'=>'bi-credit-card-2-front-fill'],
];
?>
<nav class="navbar navbar-expand-lg sc-navbar">
    <div class="container">
        <a class="navbar-brand sc-brand" href="<?= $isSubPage ? '../index.php' : 'index.php' ?>">
            <span class="brand-dot"></span>SmartCare
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#scNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="scNav">
            <ul class="navbar-nav ms-auto gap-1">
                <?php foreach ($navLinks as $file => $meta):
                    $href   = ($file === 'index.php') ? ($isSubPage ? '../index.php' : 'index.php') : ($isSubPage ? $file : 'pages/'.$file);
                    $active = ($current === $file);
                ?>
                <li class="nav-item">
                    <a class="nav-link sc-nav-link <?= $active ? 'active' : '' ?>" href="<?= $href ?>">
                        <i class="bi <?= $meta['icon'] ?>"></i>
                        <span><?= $meta['label'] ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>