<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'create') {
        $stmt = $pdo->prepare("INSERT INTO billing (amount,bill_date,payment_status,appointment_appointment_id) VALUES (?,?,?,?)");
        $stmt->execute([$_POST['amount'],date('Y-m-d'),$_POST['payment_status'],$_POST['appointment_id']]);
        echo json_encode(['success'=>true,'message'=>'Bill created!']);
    }

    if ($_POST['action'] === 'update') {
        $stmt = $pdo->prepare("UPDATE billing SET amount=?,payment_status=? WHERE bill_id=?");
        $stmt->execute([$_POST['amount'],$_POST['payment_status'],$_POST['bill_id']]);
        echo json_encode(['success'=>true,'message'=>'Bill updated!']);
    }

    if ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM billing WHERE bill_id=?");
        $stmt->execute([$_POST['bill_id']]);
        echo json_encode(['success'=>true,'message'=>'Bill removed.']);
    }

    if ($_POST['action'] === 'pay') {
        $stmt = $pdo->prepare("UPDATE billing SET payment_status='Paid' WHERE bill_id=?");
        $stmt->execute([$_POST['bill_id']]);
        echo json_encode(['success'=>true,'message'=>'Payment marked as paid!']);
    }
    exit;
}

$bills = $pdo->query("
    SELECT b.*, CONCAT(p.first_name,' ',p.last_name) AS patient_name,
           CONCAT('Dr. ',d.first_name,' ',d.last_name) AS doctor_name,
           a.appointment_date
    FROM billing b
    JOIN appointment a ON b.appointment_appointment_id = a.appointment_id
    JOIN patient p ON a.patient_patient_id = p.patient_id
    JOIN doctor d ON a.doctor_doctor_id = d.doctor_id
    ORDER BY b.bill_date DESC
")->fetchAll();

$appointments = $pdo->query("
    SELECT a.appointment_id,
           CONCAT(p.first_name,' ',p.last_name,' — ',DATE_FORMAT(a.appointment_date,'%b %d %Y')) AS label
    FROM appointment a
    JOIN patient p ON a.patient_patient_id = p.patient_id
    ORDER BY a.appointment_date DESC
")->fetchAll();

$totalPaid    = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM billing WHERE payment_status='Paid'")->fetchColumn();
$totalPending = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM billing WHERE payment_status='Pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Billing | SmartCare</title>
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
                <h1><i class="bi bi-credit-card-2-front-fill me-2" style="color:var(--amber)"></i>Billing</h1>
                <p>Manage invoices and payment status</p>
            </div>
            <button class="btn-sc btn-primary-sc" onclick="openModal()">
                <i class="bi bi-plus-lg"></i> Create Bill
            </button>
        </div>
    </div>
</div>

<div class="container pb-5">

    <div class="row g-4 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card stat-teal">
                <div class="stat-icon" style="color:var(--teal)"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-number">Rs. <?= number_format($totalPaid) ?></div>
                <div class="stat-label">Total Collected</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card stat-amber">
                <div class="stat-icon" style="color:var(--amber)"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-number">Rs. <?= number_format($totalPending) ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
    </div>

    <div class="sc-table-wrap">
        <?php if (empty($bills)): ?>
        <div class="empty-state"><i class="bi bi-receipt"></i><p>No bills generated yet.</p></div>
        <?php else: ?>
        <table class="table sc-table">
            <thead>
                <tr><th>#</th><th>Patient</th><th>Doctor</th><th>Amount</th><th>Bill Date</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($bills as $b): ?>
                <tr>
                    <td><span style="color:var(--muted);font-size:.78rem"><?= $b['bill_id'] ?></span></td>
                    <td><strong><?= htmlspecialchars($b['patient_name']) ?></strong></td>
                    <td><?= htmlspecialchars($b['doctor_name']) ?></td>
                    <td><strong>Rs. <?= number_format($b['amount']) ?></strong></td>
                    <td><?= date('M d, Y', strtotime($b['bill_date'])) ?></td>
                    <td>
                        <span class="badge-status <?= strtolower($b['payment_status']) === 'paid' ? 'badge-paid' : 'badge-pending' ?>">
                            <?= $b['payment_status'] ?>
                        </span>
                    </td>
                    <td class="d-flex gap-1 flex-wrap">
                        <?php if (strtolower($b['payment_status']) !== 'paid'): ?>
                        <button class="btn-sc btn-success-sc btn-sm"
                            onclick="markPaid(<?= $b['bill_id'] ?>)">
                            <i class="bi bi-check-lg"></i> Pay
                        </button>
                        <?php endif; ?>
                        <button class="btn-sc btn-outline-sc btn-sm"
                            onclick="editBill(<?= htmlspecialchars(json_encode($b)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-sc btn-danger-sc btn-sm"
                            onclick="deleteBill(<?= $b['bill_id'] ?>)">
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

<div class="modal fade" id="billModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--radius);border:none">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Create Bill</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="bill_id">
                <div class="row g-3">
                    <div class="col-12" id="apptRow">
                        <label class="form-label">Appointment</label>
                        <select class="form-select" id="appointment_id">
                            <option value="">Select appointment…</option>
                            <?php foreach ($appointments as $a): ?>
                            <option value="<?= $a['appointment_id'] ?>"><?= htmlspecialchars($a['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Amount (Rs.)</label>
                        <input type="number" class="form-control" id="amount" min="1" max="9999">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Payment Status</label>
                        <select class="form-select" id="payment_status">
                            <option value="Pending">Pending</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn-sc btn-outline-sc" data-bs-dismiss="modal">Cancel</button>
                <button class="btn-sc btn-primary-sc" onclick="saveBill()">
                    <i class="bi bi-check-lg"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const modal = new bootstrap.Modal(document.getElementById('billModal'));
let editing = false;

function openModal() {
    editing = false;
    document.getElementById('modalTitle').textContent = 'Create Bill';
    document.getElementById('apptRow').style.display = '';
    ['bill_id','amount'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('appointment_id').value = '';
    document.getElementById('payment_status').value = 'Pending';
    modal.show();
}

function editBill(b) {
    editing = true;
    document.getElementById('modalTitle').textContent = 'Edit Bill';
    document.getElementById('apptRow').style.display = 'none';
    document.getElementById('bill_id').value = b.bill_id;
    document.getElementById('amount').value = b.amount;
    document.getElementById('payment_status').value = b.payment_status;
    modal.show();
}

function saveBill() {
    const data = new FormData();
    data.append('action', editing ? 'update' : 'create');
    data.append('bill_id', document.getElementById('bill_id').value);
    data.append('appointment_id', document.getElementById('appointment_id').value);
    data.append('amount', document.getElementById('amount').value);
    data.append('payment_status', document.getElementById('payment_status').value);

    if (!data.get('amount')) {
        Swal.fire({ icon: 'warning', title: 'Required fields', text: 'Please enter an amount.', confirmButtonColor: '#3b6ef8' });
        return;
    }

    fetch('billing.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            modal.hide();
            Swal.fire({ icon: 'success', title: 'Done!', text: res.message, timer: 1800, showConfirmButton: false })
                .then(() => location.reload());
        });
}

function markPaid(id) {
    Swal.fire({
        icon: 'question', title: 'Mark as Paid?',
        showCancelButton: true, confirmButtonColor: '#16a34a', cancelButtonColor: '#94a3b8', confirmButtonText: 'Yes, mark paid'
    }).then(result => {
        if (!result.isConfirmed) return;
        const data = new FormData();
        data.append('action', 'pay');
        data.append('bill_id', id);
        fetch('billing.php', { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => {
                Swal.fire({ icon: 'success', title: 'Paid!', text: res.message, timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            });
    });
}

function deleteBill(id) {
    Swal.fire({
        icon: 'warning', title: 'Remove bill?',
        showCancelButton: true, confirmButtonColor: '#e11d48', cancelButtonColor: '#94a3b8', confirmButtonText: 'Remove'
    }).then(result => {
        if (!result.isConfirmed) return;
        const data = new FormData();
        data.append('action', 'delete');
        data.append('bill_id', id);
        fetch('billing.php', { method: 'POST', body: data })
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