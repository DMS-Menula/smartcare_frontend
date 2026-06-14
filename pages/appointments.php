<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'create') {
        $stmt = $pdo->prepare("INSERT INTO appointment (appointment_date,status,notes,patient_patient_id,doctor_doctor_id) VALUES (?,?,?,?,?)");
        $stmt->execute([$_POST['appointment_date'],$_POST['status'],$_POST['notes'],$_POST['patient_id'],$_POST['doctor_id']]);
        echo json_encode(['success'=>true,'message'=>'Appointment scheduled!']);
    }

    if ($_POST['action'] === 'update') {
        $stmt = $pdo->prepare("UPDATE appointment SET appointment_date=?,status=?,notes=?,patient_patient_id=?,doctor_doctor_id=? WHERE appointment_id=?");
        $stmt->execute([$_POST['appointment_date'],$_POST['status'],$_POST['notes'],$_POST['patient_id'],$_POST['doctor_id'],$_POST['appointment_id']]);
        echo json_encode(['success'=>true,'message'=>'Appointment updated!']);
    }

    if ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM appointment WHERE appointment_id=?");
        $stmt->execute([$_POST['appointment_id']]);
        echo json_encode(['success'=>true,'message'=>'Appointment cancelled.']);
    }
    exit;
}

$appointments = $pdo->query("
    SELECT a.*, CONCAT(p.first_name,' ',p.last_name) AS patient_name,
           CONCAT('Dr. ',d.first_name,' ',d.last_name) AS doctor_name,
           d.specialization
    FROM appointment a
    JOIN patient p ON a.patient_patient_id = p.patient_id
    JOIN doctor d ON a.doctor_doctor_id = d.doctor_id
    ORDER BY a.appointment_date DESC
")->fetchAll();

$patients = $pdo->query("SELECT patient_id, CONCAT(first_name,' ',last_name) AS name FROM patient ORDER BY first_name")->fetchAll();
$doctors  = $pdo->query("SELECT doctor_id, CONCAT('Dr. ',first_name,' ',last_name) AS name, specialization FROM doctor ORDER BY first_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Appointments | SmartCare</title>
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
                <h1><i class="bi bi-calendar2-check-fill me-2" style="color:var(--violet)"></i>Appointments</h1>
                <p>Schedule and track patient appointments</p>
            </div>
            <button class="btn-sc btn-primary-sc" onclick="openModal()">
                <i class="bi bi-plus-lg"></i> New Appointment
            </button>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="sc-table-wrap">
        <?php if (empty($appointments)): ?>
        <div class="empty-state"><i class="bi bi-calendar2"></i><p>No appointments scheduled yet.</p></div>
        <?php else: ?>
        <table class="table sc-table">
            <thead>
                <tr><th>#</th><th>Patient</th><th>Doctor</th><th>Date & Time</th><th>Status</th><th>Notes</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($appointments as $a):
                $statusClass = match(strtolower($a['status'])) {
                    'completed' => 'badge-completed',
                    'cancelled' => 'badge-cancelled',
                    default     => 'badge-scheduled'
                };
            ?>
                <tr>
                    <td><span style="color:var(--muted);font-size:.78rem"><?= $a['appointment_id'] ?></span></td>
                    <td><strong><?= htmlspecialchars($a['patient_name']) ?></strong></td>
                    <td>
                        <?= htmlspecialchars($a['doctor_name']) ?>
                        <div style="font-size:.74rem;color:var(--muted)"><?= htmlspecialchars($a['specialization']) ?></div>
                    </td>
                    <td><?= date('M d, Y H:i', strtotime($a['appointment_date'])) ?></td>
                    <td><span class="badge-status <?= $statusClass ?>"><?= $a['status'] ?></span></td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($a['notes']) ?>">
                        <?= htmlspecialchars($a['notes']) ?>
                    </td>
                    <td>
                        <button class="btn-sc btn-outline-sc btn-sm me-1"
                            onclick="editAppt(<?= htmlspecialchars(json_encode($a)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-sc btn-danger-sc btn-sm"
                            onclick="deleteAppt(<?= $a['appointment_id'] ?>)">
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

<div class="modal fade" id="apptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--radius);border:none">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">New Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="appointment_id">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Patient</label>
                        <select class="form-select" id="patient_id">
                            <option value="">Select patient…</option>
                            <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['patient_id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Doctor</label>
                        <select class="form-select" id="doctor_id">
                            <option value="">Select doctor…</option>
                            <?php foreach ($doctors as $d): ?>
                            <option value="<?= $d['doctor_id'] ?>"><?= htmlspecialchars($d['name']) ?> — <?= htmlspecialchars($d['specialization']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Date & Time</label>
                        <input type="datetime-local" class="form-control" id="appointment_date">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="status">
                            <option value="Scheduled">Scheduled</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" rows="3"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn-sc btn-outline-sc" data-bs-dismiss="modal">Cancel</button>
                <button class="btn-sc btn-primary-sc" onclick="saveAppt()">
                    <i class="bi bi-check-lg"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const modal = new bootstrap.Modal(document.getElementById('apptModal'));
let editing = false;

function openModal() {
    editing = false;
    document.getElementById('modalTitle').textContent = 'New Appointment';
    ['appointment_id','notes'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('patient_id').value = '';
    document.getElementById('doctor_id').value = '';
    document.getElementById('status').value = 'Scheduled';
    document.getElementById('appointment_date').value = '';
    modal.show();
}

function editAppt(a) {
    editing = true;
    document.getElementById('modalTitle').textContent = 'Edit Appointment';
    document.getElementById('appointment_id').value = a.appointment_id;
    document.getElementById('patient_id').value = a.patient_patient_id;
    document.getElementById('doctor_id').value = a.doctor_doctor_id;
    document.getElementById('appointment_date').value = a.appointment_date.replace(' ', 'T');
    document.getElementById('status').value = a.status;
    document.getElementById('notes').value = a.notes;
    modal.show();
}

function saveAppt() {
    const data = new FormData();
    data.append('action', editing ? 'update' : 'create');
    data.append('appointment_id', document.getElementById('appointment_id').value);
    data.append('patient_id', document.getElementById('patient_id').value);
    data.append('doctor_id', document.getElementById('doctor_id').value);
    data.append('appointment_date', document.getElementById('appointment_date').value.replace('T', ' '));
    data.append('status', document.getElementById('status').value);
    data.append('notes', document.getElementById('notes').value.trim());

    if (!data.get('patient_id') || !data.get('doctor_id') || !data.get('appointment_date')) {
        Swal.fire({ icon: 'warning', title: 'Required fields', text: 'Please select a patient, doctor, and date.', confirmButtonColor: '#3b6ef8' });
        return;
    }

    fetch('appointments.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            modal.hide();
            Swal.fire({ icon: 'success', title: 'Done!', text: res.message, confirmButtonColor: '#3b6ef8', timer: 1800, showConfirmButton: false })
                .then(() => location.reload());
        });
}

function deleteAppt(id) {
    Swal.fire({
        icon: 'warning', title: 'Cancel appointment?',
        showCancelButton: true, confirmButtonColor: '#e11d48', cancelButtonColor: '#94a3b8', confirmButtonText: 'Yes, cancel it'
    }).then(result => {
        if (!result.isConfirmed) return;
        const data = new FormData();
        data.append('action', 'delete');
        data.append('appointment_id', id);
        fetch('appointments.php', { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => {
                Swal.fire({ icon: 'success', title: 'Cancelled', timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            });
    });
}
</script>
</body>
</html>