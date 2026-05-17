<?php
require_once __DIR__ . '/../config/app.php';

if($_SERVER["REQUEST_METHOD"] == "POST") {
 $distribution_type = $_POST["distribution_type"];
 $target_guard = $_POST["target_guard"];
 $memo_type = $_POST["memo_type"];
 $body_text = $_POST["content"];
    
   
    


 $sql1 = "INSERT INTO memos (Company_ID, Distribution_Protocol, Category, Body_Text) VALUES (?, ?, ?, ?);";
 $stmt1 = $conn -> prepare($sql1);
 $stmt1 -> bind_param("ssss", $company_id, $distribution_type, $memo_type, $body_text);

 $sql2 = "INSERT INTO memo_reception (Company_ID) VALUES (?);";
 $stmt2 = $conn -> prepare($sql2);
 $stmt2 -> bind_param("s", $company_id);


if ($stmt1 ->execute() AND $stmt2 -> execute()) {
echo   "<script> alert('Memo sent successfully! (Nasend na ang memo!)');
                window.location.href = 'dashboard.php';
              </script>;";
    
}
 
}



?>