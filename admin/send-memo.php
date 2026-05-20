<?php
require_once __DIR__ . '/../config/app.php';

auth_require_permission('admin.memo.send');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
 csrf_verify();

 $distribution_type = trim((string) ($_POST['distribution_type'] ?? ''));
 $target_guard = trim((string) ($_POST['target_guard'] ?? ''));
 $memo_type = trim((string) ($_POST['memo_type'] ?? ''));
 $body_text = trim((string) ($_POST['content'] ?? ''));
 $company_id = (string) ($_SESSION['company_id'] ?? '');
    
   
    


 $sql1 = "INSERT INTO memos (Company_ID, Distribution_Protocol, Category, Body_Text) VALUES (?, ?, ?, ?);";
 $stmt1 = $conn -> prepare($sql1);
 $stmt1 -> bind_param("ssss", $company_id, $distribution_type, $memo_type, $body_text);

 $sql2 = "INSERT INTO memo_reception (Company_ID) VALUES (?);";
 $stmt2 = $conn -> prepare($sql2);
 $stmt2 -> bind_param("s", $company_id);


if ($stmt1->execute() && $stmt2->execute()) {
    redirect_with_alert('Memo sent successfully! (Nasend na ang memo!)', 'dashboard.php');
}
 
}



?>