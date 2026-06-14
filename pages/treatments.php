<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'create') {
        $stmt = $pdo->prepare("INSERT INTO treatment (diagnosis,description,DATE,appointment_appointment_id) VALUES (?,?,?,?)");
        $stmt->execute([$_POST['diagnosis'],$_POST['description'],$_POST['date'],$_POST['appointment_id']]);
        echo json_encode(['success'=>true,'message'=>'Treatment record added!']);
    }

    if ($_POST['action'] === 'update') {
        $stmt = $pdo->prepare("UPDATE treatment SET diagnosis=?,description=?,DATE=? WHERE treatment_id=?");
        $stmt->execute([$_POST['diagnosis'],$_POST['description'],$_POST['date'],$_POST['treatment_id']]);
        echo json_encode(['success'=>true,'message'=>'Treatment updated!']);
    }

    if ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM treatment WHERE treatment_id=?");
        $stmt->execute([$_POST['treatment_id']]);
        echo json_encode(['success'=>true,'message'=>'Treatment record removed.']);
    }
    exit;
}

$treatments = $pdo->query("
    SELECT t.*, a.appointment_date,
           CONCAT(p.first_name,' ',p.last_name) AS patient_name,
           CONCAT('Dr. ',d.first_name,' ',d.last_name) AS doctor_name
    FROM treatment t
    JOIN appointment a ON t.appointment_appointment_id = a.appointment_id
    JOIN patient p ON a.patient_patient_id = p.patient_id
    JOIN doctor d ON a.doctor_doctor_id = d.doctor_id
    ORDER BY t.DATE DESC
")->fetchAll();

$appointments = $pdo->query("
    SELECT a.appointment_id,
           CONCAT(p.first_name,' ',p.last_name,' — ', DATE_FORMAT(a.appointment_date,'%b %d %Y')) AS label
    FROM appointment a
    JOIN patient p ON a.patient_patient_id = p.patient_id
    WHERE a.status = 'Completed'
    ORDER BY a.appointment_date DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Treatments | SmartCare</title>
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
                <h1><i class="bi bi-clipboard2-pulse-fill me-2" style="color:var(--rose)"></i>Treatments</h1>
                <p>Log diagnoses and treatment details per appointment</p>
            </div>
            <button class="btn-sc btn-primary-sc" onclick="openModal()">
                <i class="bi bi-plus-lg"></i> Add Treatment
            </button>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="sc-table-wrap">
        <?php if (empty($treatments)): ?>
        <div class="empty-state"><i class="bi bi-clipboard2-pulse"></i><p>No treatments logged yet.</p></div>
        <?php else: ?>
        <table class="table sc-table">
            <thead>
                <tr><th>#</th><th>Patient</th><th>Doctor</th><th>Diagnosis</th><th>Description</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($treatments as $t): ?>
                <tr>
                    <td><span style="color:var(--muted);font-size:.78rem"><?= $t['treatment_id'] ?></span></td>
                    <td><strong><?= htmlspecialchars($t['patient_name']) ?></strong></td>
                    <td><?= htmlspecialchars($t['doctor_name']) ?></td>
                    <td><?= htmlspecialchars($t['diagnosis']) ?></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($t['description']) ?>">
                        <?= htmlspecialchars($t['description']) ?>
                    </td>
                    <td><?= date('M d, Y', strtotime($t['DATE'])) ?></td>
                    <td>
                        <button class="btn-sc btn-outline-sc btn-sm me-1"
                            onclick="editTreatment(<?= htmlspecialchars(json_encode($t)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-sc btn-danger-sc btn-sm"
                            onclick="deleteTreatment(<?= $t['treatment_id'] ?>)">
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

<div class="modal fade" id="treatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--radius);border:none">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Treatment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="treatment_id">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Appointment (Completed)</label>
                        <select class="form-select" id="appointment_id">
                            <option value="">Select appointment…</option>
                            <?php foreach ($appointments as $a): ?>
                            <option value="<?= $a['appointment_id'] ?>"><?= htmlspecialchars($a['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Diagnosis</label>
                        <input type="text" class="form-control" id="diagnosis">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="description" rows="3"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Treatment Date</label>
                        <input type="date" class="form-control" id="date">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn-sc btn-outline-sc" data-bs-dismiss="modal">Cancel</button>
                <button class="btn-sc btn-primary-sc" onclick="saveTreatment()">
                    <i class="bi bi-check-lg"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const modal = new bootstrap.Modal(document.getElementById('treatModal'));
let editing = false;

function openModal() {
    editing = false;
    document.getElementById('modalTitle').textContent = 'Add Treatment';
    ['treatment_id','diagnosis','description','date'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('appointment_id').value = '';
    modal.show();
}

function editTreatment(t) {
    editing = true;
    document.getElementById('modalTitle').textContent = 'Edit Treatment';
    document.getElementById('treatment_id').value = t.treatment_id;
    document.getElementById('appointment_id').value = t.appointment_appointment_id;
    document.getElementById('diagnosis').value = t.diagnosis;
    document.getElementById('description').value = t.description;
    document.getElementById('date').value = t.DATE;
    modal.show();
}

function saveTreatment() {
    const data = new FormData();
    data.append('action', editing ? 'update' : 'create');
    data.append('treatment_id', document.getElementById('treatment_id').value);
    data.append('appointment_id', document.getElementById('appointment_id').value);
    data.append('diagnosis', document.getElementById('diagnosis').value.trim());
    data.append('description', document.getElementById('description').value.trim());
    data.append('date', document.getElementById('date').value);

    if (!data.get('diagnosis') || !data.get('date') || (!editing && !data.get('appointment_id'))) {
        Swal.fire({ icon: 'warning', title: 'Required fields', text: 'Please fill in all required fields.', confirmButtonColor: '#3b6ef8' });
        return;
    }

    fetch('treatments.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            modal.hide();
            Swal.fire({ icon: 'success', title: 'Done!', text: res.message, timer: 1800, showConfirmButton: false })
                .then(() => location.reload());
        });
}

function deleteTreatment(id) {
    Swal.fire({
        icon: 'warning', title: 'Remove treatment record?',
        showCancelButton: true, confirmButtonColor: '#e11d48', cancelButtonColor: '#94a3b8', confirmButtonText: 'Remove'
    }).then(result => {
        if (!result.isConfirmed) return;
        const data = new FormData();
        data.append('action', 'delete');
        data.append('treatment_id', id);
        fetch('treatments.php', { method: 'POST', body: data })
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