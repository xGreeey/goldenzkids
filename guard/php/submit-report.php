<?php
require_once __DIR__ . '/../../config/app.php';

use Google\Cloud\DocumentAI\V1\DocumentProcessorServiceClient;
use Google\Cloud\DocumentAI\V1\ProcessRequest;
use Google\Cloud\DocumentAI\V1\RawDocument;

$company_id = $_SESSION['company_id'] ?? '';

// Included by guard portal on every page load — only handle form submissions.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

auth_require_permission('guard.reports.submit');

csrf_verify();

$file_error = null;
$uploadOk = 0;
$sent_file = '';
$template_name = '';

$target_dir = APP_ROOT . '/uploads/template/';
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

if (!isset($_FILES['report_scan']) || $_FILES['report_scan']['error'] !== UPLOAD_ERR_OK) {
    $code = $_FILES['report_scan']['error'] ?? UPLOAD_ERR_NO_FILE;
    $file_error = match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large.',
        UPLOAD_ERR_PARTIAL => 'Upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE => 'Please choose a DGD template photo.',
        default => 'Upload failed. Please try again.',
    };
} else {
    $original_name = basename($_FILES['report_scan']['name']);
    $imageFileType = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $unique_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $original_name);
    $target_file = $target_dir . $unique_name;
    $uploadOk = 1;

    if (@getimagesize($_FILES['report_scan']['tmp_name']) === false) {
        $file_error = 'File is not an image.';
        $uploadOk = 0;
    }
    if ($_FILES['report_scan']['size'] > 10000000) {
        $file_error = 'File size too big (max 10MB).';
        $uploadOk = 0;
    }
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png'], true)) {
        $file_error = 'Only JPG, JPEG, and PNG files are allowed.';
        $uploadOk = 0;
    }

    if ($uploadOk === 1) {
        if (move_uploaded_file($_FILES['report_scan']['tmp_name'], $target_file)) {
            $sent_file = 'uploads/template/' . $unique_name;
            $template_name = $original_name;
        } else {
            $file_error = 'Could not save the uploaded file.';
            $uploadOk = 0;
        }
    }
}

if ($uploadOk !== 1) {
    redirect_with_alert($file_error ?? 'Upload failed.', 'portal.php');
}

$extracted_ai_text = '';
$credentialsPath = APP_ROOT . '/credentials/plm-dgd-document-ai.json';
$projectId = $_ENV['DOCUMENT_AI_PROJECT_ID'] ?? 'YOUR_PROJECT_ID';
$processorId = $_ENV['DOCUMENT_AI_PROCESSOR_ID'] ?? 'YOUR_PROCESSOR_ID';
$location = $_ENV['DOCUMENT_AI_LOCATION'] ?? 'us';
$aiConfigured = is_file($credentialsPath)
    && $projectId !== ''
    && $projectId !== 'YOUR_PROJECT_ID'
    && $processorId !== ''
    && $processorId !== 'YOUR_PROCESSOR_ID';

if ($aiConfigured) {
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialsPath);

    try {
        $client = new DocumentProcessorServiceClient();
        $name = $client->processorName($projectId, $location, $processorId);
        $imageContent = file_get_contents(APP_ROOT . '/' . $sent_file);

        $rawDocument = new RawDocument();
        $rawDocument->setContent($imageContent);
        $rawDocument->setMimeType(mime_content_type(APP_ROOT . '/' . $sent_file) ?: 'image/jpeg');

        $request = new ProcessRequest([
            'name' => $name,
            'raw_document' => $rawDocument,
        ]);
        $response = $client->processDocument($request);
        $extracted_ai_text = $response->getDocument()->getText() ?? '';
    } catch (Exception $e) {
        error_log('Document AI: ' . $e->getMessage());
    } finally {
        if (isset($client)) {
            $client->close();
        }
    }
}

$iv_length = openssl_cipher_iv_length($cipher_algo);
$iv = openssl_random_pseudo_bytes($iv_length);

$raw_establishment = $_POST['Establishment'] ?? '';
$raw_template_path = $sent_file;

$encrypted_est = openssl_encrypt($raw_establishment, $cipher_algo, $master_key, 0, $iv);
$encrypted_temp_path = openssl_encrypt($raw_template_path, $cipher_algo, $master_key, 0, $iv);
$encrypted_ai_text = openssl_encrypt($extracted_ai_text, $cipher_algo, $master_key, 0, $iv);

$iv_base64 = base64_encode($iv);
$time_of_event = date('Y-m-d H:i:s');

$sql = 'INSERT INTO dgd (Company_ID, Establishment, Template_Path, Template, Time_of_Report, AI_Extracted_Text, iv) VALUES (?, ?, ?, ?, ?, ?, ?)';
$stmt = $conn->prepare($sql);

if (!$stmt) {
    redirect_with_alert('Database error. Please contact admin.', 'portal.php');
}

$stmt->bind_param('sssssss', $company_id, $encrypted_est, $encrypted_temp_path, $template_name, $time_of_event, $encrypted_ai_text, $iv_base64);

if ($stmt->execute()) {
    redirect_with_alert('Nasend na ang report! (Report successfully sent!)', 'portal.php');
}

redirect_with_alert('Report failed to save. Please try again.', 'portal.php');
