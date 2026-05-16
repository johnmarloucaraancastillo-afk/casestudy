<?php
require_once 'database.php';
require_once 'pusher-broadcast.php';
require_once __DIR__ . '/csrf.php';
csrf_verify();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Owner','Cashier'])){ header("Location: ../frontend/dashboard.php"); exit(); }

$userID = $_SESSION['userID'];

/* ── helper: check if contactNo is already in use (excludes a given customerID) ── */
function contactExists($conn, $contactNo, $excludeID = 0){
    if(!$contactNo) return false;
    $stmt = $conn->prepare("SELECT customerID FROM customer WHERE contactNo = ? AND dateDeleted IS NULL AND customerID != ?");
    $stmt->bind_param("si", $contactNo, $excludeID);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r !== null;
}

if(isset($_POST['customerSave'])){
    $name    = sanitize($_POST['customerName']);
    $contact = sanitize($_POST['contactNo'] ?? '');
    $email   = sanitize($_POST['email'] ?? '');
    $address = sanitize($_POST['address'] ?? '');

    // Required: name and contact
    if(!$name || !$contact){
        header("Location: ../frontend/customer.php?emptyFields"); exit();
    }

    // Duplicate contact check
    if(contactExists($conn, $contact)){
        header("Location: ../frontend/customer.php?contactExists"); exit();
    }

    $stmt = $conn->prepare("CALL AddCustomer(?,?,?,?)");
    $stmt->bind_param("ssss", $name, $contact, $email, $address);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if($r['result'] === 'duplicate_email'){ header("Location: ../frontend/customer.php?emailExists"); exit(); }
    pusherBroadcast('customer-changed', ['action'=>'added','customerName'=>$name,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/customer.php?savedData"); exit();
}

if(isset($_POST['customerUpdate'])){
    $id      = intval($_POST['customerID']);
    $name    = sanitize($_POST['customerName']);
    $contact = sanitize($_POST['contactNo'] ?? '');
    $email   = sanitize($_POST['email'] ?? '');
    $address = sanitize($_POST['address'] ?? '');

    // Required: name and contact
    if(!$name || !$contact){
        header("Location: ../frontend/customer.php?emptyFields"); exit();
    }

    // Duplicate contact check (exclude self)
    if(contactExists($conn, $contact, $id)){
        header("Location: ../frontend/customer.php?contactExists"); exit();
    }

    $stmt = $conn->prepare("CALL UpdateCustomer(?,?,?,?,?)");
    $stmt->bind_param("issss", $id, $name, $contact, $email, $address);
    $stmt->execute();
    pusherBroadcast('customer-changed', ['action'=>'updated','customerID'=>$id,'customerName'=>$name,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/customer.php?updatedCustomer"); exit();
}

if(isset($_POST['customerDeleted'])){
    if(!in_array($_SESSION['roleName'], ['Admin','Owner'])){ header("Location: ../frontend/customer.php?accessDenied"); exit(); }
    $id   = intval($_POST['customerID']);
    $date = date('Y-m-d');
    $stmt = $conn->prepare("CALL DeleteCustomer(?,?)");
    $stmt->bind_param("is", $id, $date);
    $stmt->execute();
    pusherBroadcast('customer-changed', ['action'=>'deleted','customerID'=>$id,'by'=>$_SESSION['userName']??'']);
    header("Location: ../frontend/customer.php?customerDeleted"); exit();
}

if(isset($_POST['addCredit'])){
    $customerID = intval($_POST['customerID']);
    $amount     = floatval($_POST['amount']);
    $notes      = sanitize($_POST['notes'] ?? '');
    if($amount <= 0){ header("Location: ../frontend/customer.php?invalidAmount"); exit(); }
    $stmt = $conn->prepare("CALL AddCredit(?,?,?,?)");
    $stmt->bind_param("idsi", $customerID, $amount, $notes, $userID);
    $stmt->execute();
    $stmt->close();
    while($conn->more_results()){ $conn->next_result(); }
    $bq = $conn->prepare("SELECT customerName, credit_balance FROM customer WHERE customerID=?");
    $bq->bind_param("i", $customerID); $bq->execute();
    $br = $bq->get_result()->fetch_assoc(); $bq->close();
    pusherBroadcast('credit-changed', [
        'action'=>'utang','customerID'=>$customerID,
        'customerName'=>$br['customerName']??'',
        'amount'=>$amount,'balance'=>(float)($br['credit_balance']??0),
        'by'=>$_SESSION['userName']??'',
    ]);
    header("Location: ../frontend/customer.php?creditAdded"); exit();
}

if(isset($_POST['payCredit'])){
    $customerID = intval($_POST['customerID']);
    $amount     = floatval($_POST['amount']);
    $notes      = sanitize($_POST['notes'] ?? 'Payment');
    if($amount <= 0){ header("Location: ../frontend/customer.php?invalidAmount"); exit(); }
    $stmt = $conn->prepare("CALL PayCredit(?,?,?,?)");
    $stmt->bind_param("idsi", $customerID, $amount, $notes, $userID);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if($r['result'] === 'insufficient_balance'){ header("Location: ../frontend/customer.php?insufficientBalance"); exit(); }
    $stmt->close();
    while($conn->more_results()){ $conn->next_result(); }
    $bq = $conn->prepare("SELECT customerName, credit_balance FROM customer WHERE customerID=?");
    $bq->bind_param("i", $customerID); $bq->execute();
    $br = $bq->get_result()->fetch_assoc(); $bq->close();
    pusherBroadcast('credit-changed', [
        'action'=>'payment','customerID'=>$customerID,
        'customerName'=>$br['customerName']??'',
        'amount'=>$amount,'balance'=>(float)($br['credit_balance']??0),
        'by'=>$_SESSION['userName']??'',
    ]);
    header("Location: ../frontend/customer.php?creditPaid"); exit();
}
?>
