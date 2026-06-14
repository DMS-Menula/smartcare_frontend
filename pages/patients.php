<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'create') {
        $stmt = $pdo->prepare("INSERT INTO patient (first_name,last_name,age,mobile,gender,registration_date) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$_POST['first_name'],$_POST['last_name'],$_POST['age'],$_POST['mobile'],$_POST['gender'],date('Y-m-d')]);
        echo json_encode(['success'=>true,'message'=>'Patient registered successfully!']);
    }

    if ($_POST['action'] === 'update') {
        $stmt = $pdo->prepare("UPDATE patient SET first_name=?,last_name=?,age=?,mobile=?,gender=? WHERE patient_id=?");
        $stmt->execute([$_POST['first_name'],$_POST['last_name'],$_POST['age'],$_POST['mobile'],$_POST['gender'],$_POST['patient_id']]);
        echo json_encode(['success'=>true,'message'=>'Patient updated successfully!']);
    }

    if ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM patient WHERE patient_id=?");
        $stmt->execute([$_POST['patient_id']]);
        echo json_encode(['success'=>true,'message'=>'Patient deleted.']);
    }
    exit;
}

$patients = $pdo->query("SELECT * FROM patient ORDER BY registration_date DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patients | SmartCare</title>
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
                <h1><i class="bi bi-people-fill text-primary me-2"></i>Patients</h1>
                <p>Register and manage patient records</p>
            </div>
            <button class="btn-sc btn-primary-sc" onclick="openModal()">
                <i class="bi bi-plus-lg"></i> Add Patient
            </button>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="sc-table-wrap">
        <?php if (empty($patients)): ?>
        <div class="empty-state"><i class="bi bi-people"></i><p>No patients registered yet. Add the first one!</p></div>
        <?php else: ?>
        <table class="table sc-table">
            <thead>
                <tr>
                    <th>#</th><th>Name</th><th>Age</th><th>Gender</th><th>Mobile</th><th>Registered</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($patients as $p): ?>
                <tr>
                    <td><span style="color:var(--muted);font-size:.78rem"><?= $p['patient_id'] ?></span></td>
                    <td><strong><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></strong></td>
                    <td><?= $p['age'] ?></td>
                    <td><?= $p['gender'] ?></td>
                    <td><?= $p['mobile'] ?></td>
                    <td><?= date('M d, Y', strtotime($p['registration_date'])) ?></td>
                    <td>
                        <button class="btn-sc btn-outline-sc btn-sm me-1"
                            onclick="editPatient(<?= htmlspecialchars(json_encode($p)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-sc btn-danger-sc btn-sm"
                            onclick="deletePatient(<?= $p['patient_id'] ?>, '<?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?>')">
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

<div class="modal fade" id="patientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--radius);border:none">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="patient_id">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Age</label>
                        <input type="number" class="form-control" id="age" min="1" max="150" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Gender</label>
                        <select class="form-select" id="gender">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mobile (10 digits)</label>
                        <input type="text" class="form-control" id="mobile" maxlength="10" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn-sc btn-outline-sc" data-bs-dismiss="modal">Cancel</button>
                <button class="btn-sc btn-primary-sc" onclick="savePatient()">
                    <i class="bi bi-check-lg"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const modal = new bootstrap.Modal(document.getElementById('patientModal'));
let editing = false;

function openModal() {
    editing = false;
    document.getElementById('modalTitle').textContent = 'Add Patient';
    ['patient_id','first_name','last_name','age','mobile'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('gender').value = 'Male';
    modal.show();
}

function editPatient(p) {
    editing = true;
    document.getElementById('modalTitle').textContent = 'Edit Patient';
    document.getElementById('patient_id').value = p.patient_id;
    document.getElementById('first_name').value = p.first_name;
    document.getElementById('last_name').value = p.last_name;
    document.getElementById('age').value = p.age;
    document.getElementById('mobile').value = p.mobile;
    document.getElementById('gender').value = p.gender;
    modal.show();
}

function savePatient() {
    const data = new FormData();
    data.append('action', editing ? 'update' : 'create');
    data.append('patient_id', document.getElementById('patient_id').value);
    data.append('first_name', document.getElementById('first_name').value.trim());
    data.append('last_name', document.getElementById('last_name').value.trim());
    data.append('age', document.getElementById('age').value);
    data.append('mobile', document.getElementById('mobile').value.trim());
    data.append('gender', document.getElementById('gender').value);

    if (!data.get('first_name') || !data.get('last_name') || !data.get('age') || !data.get('mobile')) {
        Swal.fire({ icon: 'warning', title: 'Required fields', text: 'Please fill in all fields.', confirmButtonColor: '#3b6ef8' });
        return;
    }

    fetch('patients.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            modal.hide();
            Swal.fire({ icon: 'success', title: 'Done!', text: res.message, confirmButtonColor: '#3b6ef8', timer: 1800, showConfirmButton: false })
                .then(() => location.reload());
        });
}

function deletePatient(id, name) {
    Swal.fire({
        icon: 'warning',
        title: 'Delete patient?',
        text: `Remove ${name} from the system?`,
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Delete'
    }).then(result => {
        if (!result.isConfirmed) return;
        const data = new FormData();
        data.append('action', 'delete');
        data.append('patient_id', id);
        fetch('patients.php', { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => {
                Swal.fire({ icon: 'success', title: 'Deleted', text: res.message, confirmButtonColor: '#3b6ef8', timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            });
    });
}
</script>
</body>
</html>