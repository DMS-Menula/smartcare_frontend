<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'create') {
        $stmt = $pdo->prepare("INSERT INTO doctor (first_name,last_name,specialization,phone,email,hire_date) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$_POST['first_name'],$_POST['last_name'],$_POST['specialization'],$_POST['phone'],$_POST['email'],date('Y-m-d')]);
        echo json_encode(['success'=>true,'message'=>'Doctor added successfully!']);
    }

    if ($_POST['action'] === 'update') {
        $stmt = $pdo->prepare("UPDATE doctor SET first_name=?,last_name=?,specialization=?,phone=?,email=? WHERE doctor_id=?");
        $stmt->execute([$_POST['first_name'],$_POST['last_name'],$_POST['specialization'],$_POST['phone'],$_POST['email'],$_POST['doctor_id']]);
        echo json_encode(['success'=>true,'message'=>'Doctor updated successfully!']);
    }

    if ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM doctor WHERE doctor_id=?");
        $stmt->execute([$_POST['doctor_id']]);
        echo json_encode(['success'=>true,'message'=>'Doctor removed.']);
    }
    exit;
}

$doctors = $pdo->query("SELECT * FROM doctor ORDER BY hire_date DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctors | SmartCare</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="page-header">
    <div class="container">
        <a class="back-link" href="../index.php"><i class="bi bi-arrow-left"></i> Dashboard</a>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="bi bi-person-badge-fill me-2" style="color:var(--teal)"></i>Doctors</h1>
                <p>Add and manage doctor profiles</p>
            </div>
            <button class="btn-sc btn-primary-sc" onclick="openModal()">
                <i class="bi bi-plus-lg"></i> Add Doctor
            </button>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="sc-table-wrap">
        <?php if (empty($doctors)): ?>
        <div class="empty-state"><i class="bi bi-person-badge"></i><p>No doctors on record yet.</p></div>
        <?php else: ?>
        <table class="table sc-table">
            <thead>
                <tr><th>#</th><th>Name</th><th>Specialization</th><th>Phone</th><th>Email</th><th>Hired</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($doctors as $d): ?>
                <tr>
                    <td><span style="color:var(--muted);font-size:.78rem"><?= $d['doctor_id'] ?></span></td>
                    <td><strong>Dr. <?= htmlspecialchars($d['first_name'].' '.$d['last_name']) ?></strong></td>
                    <td><?= htmlspecialchars($d['specialization']) ?></td>
                    <td><?= $d['phone'] ?></td>
                    <td><?= htmlspecialchars($d['email']) ?></td>
                    <td><?= date('M d, Y', strtotime($d['hire_date'])) ?></td>
                    <td>
                        <button class="btn-sc btn-outline-sc btn-sm me-1"
                            onclick="editDoctor(<?= htmlspecialchars(json_encode($d)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-sc btn-danger-sc btn-sm"
                            onclick="deleteDoctor(<?= $d['doctor_id'] ?>, '<?= htmlspecialchars($d['first_name'].' '.$d['last_name']) ?>')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="doctorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--radius);border:none">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Doctor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="doctor_id">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Specialization</label>
                        <input type="text" class="form-control" id="specialization">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" maxlength="10">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="email">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn-sc btn-outline-sc" data-bs-dismiss="modal">Cancel</button>
                <button class="btn-sc btn-primary-sc" onclick="saveDoctor()">
                    <i class="bi bi-check-lg"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const modal = new bootstrap.Modal(document.getElementById('doctorModal'));
let editing = false;

function openModal() {
    editing = false;
    document.getElementById('modalTitle').textContent = 'Add Doctor';
    ['doctor_id','first_name','last_name','specialization','phone','email'].forEach(id => document.getElementById(id).value = '');
    modal.show();
}

function editDoctor(d) {
    editing = true;
    document.getElementById('modalTitle').textContent = 'Edit Doctor';
    document.getElementById('doctor_id').value = d.doctor_id;
    document.getElementById('first_name').value = d.first_name;
    document.getElementById('last_name').value = d.last_name;
    document.getElementById('specialization').value = d.specialization;
    document.getElementById('phone').value = d.phone;
    document.getElementById('email').value = d.email;
    modal.show();
}

function saveDoctor() {
    const data = new FormData();
    data.append('action', editing ? 'update' : 'create');
    ['doctor_id','first_name','last_name','specialization','phone','email'].forEach(id => data.append(id, document.getElementById(id).value.trim()));

    if (!data.get('first_name') || !data.get('last_name') || !data.get('specialization')) {
        Swal.fire({ icon: 'warning', title: 'Required fields', text: 'Please fill in all fields.', confirmButtonColor: '#3b6ef8' });
        return;
    }

    fetch('doctors.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            modal.hide();
            Swal.fire({ icon: 'success', title: 'Done!', text: res.message, confirmButtonColor: '#3b6ef8', timer: 1800, showConfirmButton: false })
                .then(() => location.reload());
        });
}

function deleteDoctor(id, name) {
    Swal.fire({
        icon: 'warning', title: 'Remove doctor?', text: `Remove Dr. ${name}?`,
        showCancelButton: true, confirmButtonColor: '#e11d48', cancelButtonColor: '#94a3b8', confirmButtonText: 'Remove'
    }).then(result => {
        if (!result.isConfirmed) return;
        const data = new FormData();
        data.append('action', 'delete');
        data.append('doctor_id', id);
        fetch('doctors.php', { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => {
                Swal.fire({ icon: 'success', title: 'Removed', text: res.message, timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            });
    });
}
</script>
</body>
</html>