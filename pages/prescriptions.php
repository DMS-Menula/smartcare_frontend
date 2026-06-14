<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'create') {
        $stmt = $pdo->prepare("INSERT INTO prescription (medicine_name,dosage,duration,treatment_treatment_id) VALUES (?,?,?,?)");
        $stmt->execute([$_POST['medicine_name'],$_POST['dosage'],$_POST['duration'],$_POST['treatment_id']]);
        echo json_encode(['success'=>true,'message'=>'Prescription issued!']);
    }

    if ($_POST['action'] === 'update') {
        $stmt = $pdo->prepare("UPDATE prescription SET medicine_name=?,dosage=?,duration=? WHERE prescription_id=?");
        $stmt->execute([$_POST['medicine_name'],$_POST['dosage'],$_POST['duration'],$_POST['prescription_id']]);
        echo json_encode(['success'=>true,'message'=>'Prescription updated!']);
    }

    if ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM prescription WHERE prescription_id=?");
        $stmt->execute([$_POST['prescription_id']]);
        echo json_encode(['success'=>true,'message'=>'Prescription removed.']);
    }
    exit;
}

$prescriptions = $pdo->query("
    SELECT pr.*, t.diagnosis,
           CONCAT(p.first_name,' ',p.last_name) AS patient_name,
           CONCAT('Dr. ',d.first_name,' ',d.last_name) AS doctor_name
    FROM prescription pr
    JOIN treatment t ON pr.treatment_treatment_id = t.treatment_id
    JOIN appointment a ON t.appointment_appointment_id = a.appointment_id
    JOIN patient p ON a.patient_patient_id = p.patient_id
    JOIN doctor d ON a.doctor_doctor_id = d.doctor_id
    ORDER BY pr.prescription_id DESC
")->fetchAll();

$treatments = $pdo->query("
    SELECT t.treatment_id,
           CONCAT(p.first_name,' ',p.last_name,' — ',t.diagnosis) AS label
    FROM treatment t
    JOIN appointment a ON t.appointment_appointment_id = a.appointment_id
    JOIN patient p ON a.patient_patient_id = p.patient_id
    ORDER BY t.DATE DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prescriptions | SmartCare</title>
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
                <h1><i class="bi bi-capsule-pill me-2" style="color:var(--green)"></i>Prescriptions</h1>
                <p>Issue and manage medicine prescriptions</p>
            </div>
            <button class="btn-sc btn-primary-sc" onclick="openModal()">
                <i class="bi bi-plus-lg"></i> Issue Prescription
            </button>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="sc-table-wrap">
        <?php if (empty($prescriptions)): ?>
        <div class="empty-state"><i class="bi bi-capsule"></i><p>No prescriptions issued yet.</p></div>
        <?php else: ?>
        <table class="table sc-table">
            <thead>
                <tr><th>#</th><th>Patient</th><th>Doctor</th><th>Diagnosis</th><th>Medicine</th><th>Dosage</th><th>Duration</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($prescriptions as $pr): ?>
                <tr>
                    <td><span style="color:var(--muted);font-size:.78rem"><?= $pr['prescription_id'] ?></span></td>
                    <td><strong><?= htmlspecialchars($pr['patient_name']) ?></strong></td>
                    <td><?= htmlspecialchars($pr['doctor_name']) ?></td>
                    <td><em style="color:var(--muted)"><?= htmlspecialchars($pr['diagnosis']) ?></em></td>
                    <td><strong><?= htmlspecialchars($pr['medicine_name']) ?></strong></td>
                    <td><?= htmlspecialchars($pr['dosage']) ?></td>
                    <td><?= htmlspecialchars($pr['duration']) ?></td>
                    <td>
                        <button class="btn-sc btn-outline-sc btn-sm me-1"
                            onclick="editPr(<?= htmlspecialchars(json_encode($pr)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-sc btn-danger-sc btn-sm"
                            onclick="deletePr(<?= $pr['prescription_id'] ?>)">
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

<div class="modal fade" id="prModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--radius);border:none">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Issue Prescription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="prescription_id">
                <div class="row g-3">
                    <div class="col-12" id="treatmentRow">
                        <label class="form-label">Treatment</label>
                        <select class="form-select" id="treatment_id">
                            <option value="">Select treatment…</option>
                            <?php foreach ($treatments as $t): ?>
                            <option value="<?= $t['treatment_id'] ?>"><?= htmlspecialchars($t['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Medicine Name</label>
                        <input type="text" class="form-control" id="medicine_name">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Dosage</label>
                        <input type="text" class="form-control" id="dosage" placeholder="e.g. 500mg twice daily">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Duration</label>
                        <input type="text" class="form-control" id="duration" placeholder="e.g. 7 days">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn-sc btn-outline-sc" data-bs-dismiss="modal">Cancel</button>
                <button class="btn-sc btn-primary-sc" onclick="savePr()">
                    <i class="bi bi-check-lg"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const modal = new bootstrap.Modal(document.getElementById('prModal'));
let editing = false;

function openModal() {
    editing = false;
    document.getElementById('modalTitle').textContent = 'Issue Prescription';
    document.getElementById('treatmentRow').style.display = '';
    ['prescription_id','medicine_name','dosage','duration'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('treatment_id').value = '';
    modal.show();
}

function editPr(pr) {
    editing = true;
    document.getElementById('modalTitle').textContent = 'Edit Prescription';
    document.getElementById('treatmentRow').style.display = 'none';
    document.getElementById('prescription_id').value = pr.prescription_id;
    document.getElementById('medicine_name').value = pr.medicine_name;
    document.getElementById('dosage').value = pr.dosage;
    document.getElementById('duration').value = pr.duration;
    modal.show();
}

function savePr() {
    const data = new FormData();
    data.append('action', editing ? 'update' : 'create');
    data.append('prescription_id', document.getElementById('prescription_id').value);
    data.append('treatment_id', document.getElementById('treatment_id').value);
    data.append('medicine_name', document.getElementById('medicine_name').value.trim());
    data.append('dosage', document.getElementById('dosage').value.trim());
    data.append('duration', document.getElementById('duration').value.trim());

    if (!data.get('medicine_name') || !data.get('dosage') || !data.get('duration')) {
        Swal.fire({ icon: 'warning', title: 'Required fields', text: 'Please fill in all fields.', confirmButtonColor: '#3b6ef8' });
        return;
    }

    fetch('prescriptions.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            modal.hide();
            Swal.fire({ icon: 'success', title: 'Done!', text: res.message, timer: 1800, showConfirmButton: false })
                .then(() => location.reload());
        });
}

function deletePr(id) {
    Swal.fire({
        icon: 'warning', title: 'Remove prescription?',
        showCancelButton: true, confirmButtonColor: '#e11d48', cancelButtonColor: '#94a3b8', confirmButtonText: 'Remove'
    }).then(result => {
        if (!result.isConfirmed) return;
        const data = new FormData();
        data.append('action', 'delete');
        data.append('prescription_id', id);
        fetch('prescriptions.php', { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => {
                Swal.fire({ icon: 'success', title: 'Removed', timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            });
    });
}
</script>
</body>
</html>