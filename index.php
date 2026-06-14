<?php require_once 'includes/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartCare | Health Management System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="hero-section">
    <div class="container">
        <div class="hero-content">
            <span class="hero-badge">SmartCare HMS</span>
            <h1 class="hero-title">Patient Care,<br><span class="text-accent">Simplified.</span></h1>
            <p class="hero-sub">Manage patients, doctors, appointments, treatments, prescriptions and billing, all in one place.</p>
        </div>
    </div>
</div>

<div class="container py-5">
    <?php
        $patients = $pdo->query("SELECT COUNT(*) FROM patient")->fetchColumn();
        $doctors  = $pdo->query("SELECT COUNT(*) FROM doctor")->fetchColumn();
        $appointments = $pdo->query("SELECT COUNT(*) FROM appointment")->fetchColumn();
        $pending_bills = $pdo->query("SELECT COUNT(*) FROM billing WHERE payment_status='Pending'")->fetchColumn();
    ?>

    <div class="row g-4 mb-5">
        <div class="col-6 col-md-3">
            <div class="stat-card stat-blue">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-number"><?= $patients ?></div>
                <div class="stat-label">Patients</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card stat-teal">
                <div class="stat-icon"><i class="bi bi-person-badge-fill"></i></div>
                <div class="stat-number"><?= $doctors ?></div>
                <div class="stat-label">Doctors</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card stat-violet">
                <div class="stat-icon"><i class="bi bi-calendar2-check-fill"></i></div>
                <div class="stat-number"><?= $appointments ?></div>
                <div class="stat-label">Appointments</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card stat-amber">
                <div class="stat-icon"><i class="bi bi-receipt"></i></div>
                <div class="stat-number"><?= $pending_bills ?></div>
                <div class="stat-label">Pending Bills</div>
            </div>
        </div>
    </div>

    <div class="section-label">Manage</div>
    <h2 class="section-title mb-4">What would you like to do?</h2>

    <div class="row g-4">
        <?php
        $modules = [
            ['icon'=>'bi-people-fill',        'color'=>'blue',   'title'=>'Patients',      'desc'=>'Register and manage patient records.',       'href'=>'pages/patients.php'],
            ['icon'=>'bi-person-badge-fill',   'color'=>'teal',   'title'=>'Doctors',       'desc'=>'Add and update doctor profiles.',             'href'=>'pages/doctors.php'],
            ['icon'=>'bi-calendar2-check-fill','color'=>'violet', 'title'=>'Appointments',  'desc'=>'Schedule and track appointments.',            'href'=>'pages/appointments.php'],
            ['icon'=>'bi-clipboard2-pulse-fill','color'=>'rose',  'title'=>'Treatments',    'desc'=>'Log diagnoses and treatment details.',        'href'=>'pages/treatments.php'],
            ['icon'=>'bi-capsule-pill',        'color'=>'green',  'title'=>'Prescriptions', 'desc'=>'Issue and view medicine prescriptions.',      'href'=>'pages/prescriptions.php'],
            ['icon'=>'bi-credit-card-2-front-fill','color'=>'amber','title'=>'Billing',     'desc'=>'Manage invoices and payment status.',         'href'=>'pages/billing.php'],
        ];
        foreach ($modules as $m): ?>
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="<?= $m['href'] ?>" class="module-card module-<?= $m['color'] ?>">
                <div class="module-icon"><i class="bi <?= $m['icon'] ?>"></i></div>
                <div class="module-body">
                    <div class="module-title"><?= $m['title'] ?></div>
                    <div class="module-desc"><?= $m['desc'] ?></div>
                </div>
                <i class="bi bi-arrow-right module-arrow"></i>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>