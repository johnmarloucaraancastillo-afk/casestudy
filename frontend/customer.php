<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';

session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner','Cashier'])){ header("Location: dashboard.php"); exit(); }
$pageTitle = "Customers – 7Evelyn POS";

$result = $conn->query("SELECT * FROM customer WHERE dateDeleted IS NULL ORDER BY customerName ASC");
$customers = [];
while($row = $result->fetch_assoc()) $customers[] = $row;
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<div class="topbar no-print">
    <h5><i class="bi bi-people me-2" style="color:var(--ev-primary);"></i>Customer Management</h5>
    <div class="ms-auto"><div class="user-badge"><i class="bi bi-person-circle" style="color:var(--ev-primary);"></i><span><?php echo htmlspecialchars($_SESSION['userName']); ?></span><span class="role-pill"><?php echo htmlspecialchars($_SESSION['roleName']); ?></span></div></div>
</div>

<?php
$alerts=[
    'savedData'           =>['success','Saved!','Customer added successfully.'],
    'updatedCustomer'     =>['success','Updated!','Customer updated.'],
    'customerDeleted'     =>['success','Deleted!','Customer removed.'],
    'creditAdded'         =>['success','Credit Added','Utang recorded.'],
    'creditPaid'          =>['success','Paid!','Credit payment recorded.'],
    'emailExists'         =>['error','Email Exists','Email already in use.'],
    'contactExists'       =>['error','Duplicate Contact','That contact number is already registered.'],
    'insufficientBalance' =>['error','Insufficient','Payment exceeds balance.'],
    'accessDenied'        =>['error','Access Denied','You do not have permission.'],
    'emptyFields'         =>['warning','Required Fields','Name and Contact No. are required.'],
];
foreach($alerts as $k=>[$i,$t,$tx])
    if(isset($_GET[$k]))
        echo "<script>Swal.fire({icon:'$i',title:'$t',text:'$tx',timer:2500}).then(()=>window.history.replaceState({},document.title,window.location.pathname));</script>";
?>

<div class="page-body">
    <div class="d-flex justify-content-between mb-4 align-items-center">
        <div>
            <h5 class="fw-bold mb-1" style="color:var(--ev-primary-dk);">Customers</h5>
            <div class="text-muted small"><?php echo count($customers); ?> total customer<?php echo count($customers)!==1?'s':''; ?></div>
        </div>
        <?php if(in_array($_SESSION['roleName'], ['Admin','Cashier'])): ?>
        <button class="btn btn-ev" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
            <i class="bi bi-person-plus me-1"></i>Add Customer
        </button>
        <?php endif; ?>
    </div>

    <div class="card card-shadow">
        <div class="card-body p-0">
            <div class="p-3 border-bottom d-flex align-items-center gap-2" style="background:rgba(0,129,97,0.04);border-radius:14px 14px 0 0;">
                <i class="bi bi-people" style="color:var(--ev-primary);font-size:1.1rem;"></i>
                <span class="fw-semibold" style="color:var(--ev-primary-dk);font-size:14px;">Customer List</span>
            </div>
            <div class="p-3">
                <table id="customerTable" class="table table-bordered table-hover text-center align-middle">
                    <thead>
                        <tr style="background:var(--ev-gradient);color:#fff;">
                            <th class="text-start">Name</th>
                            <th>Contact No.</th>
                            <th>Email</th>
                            <th class="text-start">Address</th>
                            <th>Credit Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($customers as $row):
                        $creditClass = $row['credit_balance'] > 0 ? 'text-danger fw-bold' : 'text-success fw-semibold';
                    ?>
                    <tr>
                        <td class="text-start fw-semibold" style="color:var(--ev-primary-dk);">
                            <i class="bi bi-person-circle me-1" style="color:var(--ev-primary);opacity:.7;"></i>
                            <?php echo htmlspecialchars($row['customerName']); ?>
                        </td>
                        <td>
                            <span class="badge" style="background:rgba(0,129,97,0.1);color:var(--ev-primary-dk);font-size:12px;font-weight:600;padding:5px 10px;border-radius:20px;">
                                <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($row['contactNo'] ?? '—'); ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?php echo htmlspecialchars($row['email'] ?? '—'); ?></td>
                        <td class="text-start text-muted small"><?php echo htmlspecialchars($row['address'] ?? '—'); ?></td>
                        <td>
                            <span class="<?php echo $creditClass; ?>">
                                ₱<?php echo number_format($row['credit_balance'],2); ?>
                            </span>
                        </td>
                        <td>
                            <?php if(in_array($_SESSION['roleName'], ['Admin','Owner','Cashier'])): ?>
                            <button class="btn btn-sm me-1"
                                style="background:rgba(0,129,97,0.12);color:var(--ev-primary);border:1px solid rgba(0,129,97,0.2);"
                                onclick="openCreditModal(<?php echo $row['customerID']; ?>)"
                                title="Manage Credit / Utang">
                                <i class="bi bi-wallet2"></i>
                            </button>
                            <?php endif; ?>
                            <?php if($_SESSION['roleName']==='Admin'): ?>
                            <button class="btn btn-sm btn-primary me-1"
                                onclick="openEditModal(<?php echo $row['customerID']; ?>)"
                                title="Edit Customer">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger"
                                onclick="openDeleteModal(<?php echo $row['customerID']; ?>, '<?php echo addslashes($row['customerName']); ?>')"
                                title="Delete Customer">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODALS ==================== -->

<!-- Add Customer Modal -->
<?php if(in_array($_SESSION['roleName'], ['Admin','Cashier'])): ?>
<div class="modal fade" id="addCustomerModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
    <form method="POST" action="../backend/customerAuth.php" id="addCustomerForm" novalidate>
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;border-radius:16px 16px 0 0;padding:16px 20px;">
        <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2"></i>Add Customer</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-3">
            <label class="form-label fw-semibold small">
                Full Name <span class="text-danger">*</span>
            </label>
            <input type="text" name="customerName" id="addCustomerName" class="form-control" placeholder="e.g. Juan dela Cruz" required>
            <div class="invalid-feedback">Name is required.</div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold small">
                Contact No. <span class="text-danger">*</span>
            </label>
            <div class="input-group">
                <span class="input-group-text" style="background:#f0faf7;border-color:#c8ddd8;color:var(--ev-primary);"><i class="bi bi-telephone"></i></span>
                <input type="text" name="contactNo" id="addContactNo" class="form-control"
                    placeholder="e.g. 09xxxxxxxxx"
                    pattern="[0-9\+\-\s\(\)]{7,20}"
                    required>
            </div>
            <div class="form-text text-danger d-none" id="addContactNoError">Contact number is required.</div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold small">Email <span class="text-muted fw-normal">(optional)</span></label>
            <input type="email" name="email" class="form-control" placeholder="example@email.com">
        </div>
        <div class="mb-2">
            <label class="form-label fw-semibold small">Address <span class="text-muted fw-normal">(optional)</span></label>
            <textarea name="address" class="form-control" rows="2" placeholder="Street, Barangay, City..."></textarea>
        </div>
      </div>
      <div class="modal-footer" style="border-top:1px solid #e8f4f0;padding:14px 20px;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="customerSave" class="btn btn-ev px-4">
            <i class="bi bi-check-lg me-1"></i>Save Customer
        </button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; ?>

<!-- Credit / Utang Modal -->
<div class="modal fade" id="creditModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
    <div class="modal-header" style="background:var(--ev-gradient);color:#fff;border-radius:16px 16px 0 0;padding:16px 20px;">
      <h5 class="modal-title fw-bold"><i class="bi bi-wallet2 me-2"></i>Manage Credit – <span id="creditModalName"></span></h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" id="creditModalBody">
      <div class="text-center py-4"><div class="spinner-border" style="color:var(--ev-primary)"></div></div>
    </div>
  </div></div>
</div>

<!-- Edit Customer Modal -->
<?php if($_SESSION['roleName']==='Admin'): ?>
<div class="modal fade" id="editCustomerModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
    <form method="POST" action="../backend/customerAuth.php" id="editCustomerForm" novalidate>
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;border-radius:16px 16px 0 0;padding:16px 20px;">
        <h5 class="modal-title fw-bold"><i class="bi bi-pencil me-2"></i>Edit Customer</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" name="customerID" id="editCustomerID">
        <div class="mb-3">
            <label class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="customerName" id="editCustomerName" class="form-control" required>
            <div class="invalid-feedback">Name is required.</div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold small">Contact No. <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text" style="background:#f0faf7;border-color:#c8ddd8;color:var(--ev-primary);"><i class="bi bi-telephone"></i></span>
                <input type="text" name="contactNo" id="editContactNo" class="form-control"
                    pattern="[0-9\+\-\s\(\)]{7,20}"
                    required>
            </div>
            <div class="form-text text-danger d-none" id="editContactNoError">Contact number is required.</div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold small">Email <span class="text-muted fw-normal">(optional)</span></label>
            <input type="email" name="email" id="editEmail" class="form-control">
        </div>
        <div class="mb-2">
            <label class="form-label fw-semibold small">Address <span class="text-muted fw-normal">(optional)</span></label>
            <textarea name="address" id="editAddress" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer" style="border-top:1px solid #e8f4f0;padding:14px 20px;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="customerUpdate" class="btn btn-ev px-4">
            <i class="bi bi-check-lg me-1"></i>Update
        </button>
      </div>
    </form>
  </div></div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteCustomerModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
    <form method="POST" action="../backend/customerAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient-accent);color:#fff;border-radius:16px 16px 0 0;padding:14px 18px;">
        <h5 class="modal-title fw-bold"><i class="bi bi-trash me-2"></i>Delete Customer</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" name="customerID" id="deleteCustomerID">
        <p class="mb-1">Remove <strong id="deleteCustomerName"></strong>?</p>
        <p class="text-muted small mb-0">This action cannot be undone.</p>
      </div>
      <div class="modal-footer" style="border-top:1px solid #fde8ea;padding:12px 18px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="customerDeleted" class="btn btn-danger btn-sm px-3">
            <i class="bi bi-trash me-1"></i>Delete
        </button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; ?>

<!-- Customer data for JS -->
<script>
const customerData = <?php
    $jsData = [];
    foreach($customers as $c){
        $jsData[$c['customerID']] = [
            'customerID'     => $c['customerID'],
            'customerName'   => $c['customerName'],
            'contactNo'      => $c['contactNo'] ?? '',
            'email'          => $c['email'] ?? '',
            'address'        => $c['address'] ?? '',
            'credit_balance' => $c['credit_balance'],
        ];
    }
    echo json_encode($jsData);
?>;

// Build a set of existing contact numbers for duplicate checking (exclude current customer on edit)
const existingContacts = {};
Object.values(customerData).forEach(c => {
    if(c.contactNo && c.contactNo.trim() !== '') {
        existingContacts[c.contactNo.trim()] = c.customerID;
    }
});

$(document).ready(function(){
    $('#customerTable').DataTable({
        pageLength: 10,
        order: [[0,'asc']],
        columnDefs: [{ orderable: false, targets: 5 }],
        language: {
            search: '<i class="bi bi-search me-1"></i>',
            searchPlaceholder: 'Search customers...',
        }
    });
});

// Client-side validation: required Name + Contact, and no duplicate contact
function validateCustomerForm(formId, contactFieldId, errorDivId, excludeCustomerID = null) {
    const nameField    = document.querySelector('#' + formId + ' [name="customerName"]');
    const contactField = document.getElementById(contactFieldId);
    const errorDiv     = document.getElementById(errorDivId);
    let valid = true;

    // Name required
    if (!nameField.value.trim()) {
        nameField.classList.add('is-invalid');
        valid = false;
    } else {
        nameField.classList.remove('is-invalid');
    }

    // Contact required
    if (!contactField.value.trim()) {
        contactField.classList.add('is-invalid');
        errorDiv.textContent = 'Contact number is required.';
        errorDiv.classList.remove('d-none');
        valid = false;
    } else {
        // Duplicate check
        const val = contactField.value.trim();
        const dup = existingContacts[val];
        if (dup && dup !== excludeCustomerID) {
            contactField.classList.add('is-invalid');
            errorDiv.textContent = 'This contact number is already registered to another customer.';
            errorDiv.classList.remove('d-none');
            valid = false;
        } else {
            contactField.classList.remove('is-invalid');
            errorDiv.classList.add('d-none');
        }
    }
    return valid;
}

// Add form submit
document.getElementById('addCustomerForm')?.addEventListener('submit', function(e){
    if (!validateCustomerForm('addCustomerForm', 'addContactNo', 'addContactNoError')) {
        e.preventDefault();
    }
});

// Edit form submit
document.getElementById('editCustomerForm')?.addEventListener('submit', function(e){
    const cid = parseInt(document.getElementById('editCustomerID').value);
    if (!validateCustomerForm('editCustomerForm', 'editContactNo', 'editContactNoError', cid)) {
        e.preventDefault();
    }
});

// Credit modal
function openCreditModal(customerID){
    const c = customerData[customerID];
    document.getElementById('creditModalName').textContent = c.customerName;
    document.getElementById('creditModalBody').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border" style="color:var(--ev-primary)"></div></div>';
    new bootstrap.Modal(document.getElementById('creditModal')).show();
    fetch('../backend/getCreditDetails.php?customerID=' + customerID)
        .then(r => r.text())
        .then(html => { document.getElementById('creditModalBody').innerHTML = html; })
        .catch(() => { document.getElementById('creditModalBody').innerHTML = '<div class="alert alert-danger m-3">Failed to load credit details.</div>'; });
}

// Edit modal
function openEditModal(customerID){
    const c = customerData[customerID];
    document.getElementById('editCustomerID').value   = c.customerID;
    document.getElementById('editCustomerName').value = c.customerName;
    document.getElementById('editContactNo').value    = c.contactNo;
    document.getElementById('editEmail').value        = c.email;
    document.getElementById('editAddress').value      = c.address;
    // Reset validation states
    ['editCustomerName','editContactNo'].forEach(id => {
        document.getElementById(id)?.classList.remove('is-invalid');
    });
    document.getElementById('editContactNoError')?.classList.add('d-none');
    new bootstrap.Modal(document.getElementById('editCustomerModal')).show();
}

// Delete modal
function openDeleteModal(customerID, customerName){
    document.getElementById('deleteCustomerID').value         = customerID;
    document.getElementById('deleteCustomerName').textContent = customerName;
    new bootstrap.Modal(document.getElementById('deleteCustomerModal')).show();
}
</script>
</div></div>

<!-- Pusher Real-time -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
</body></html>
