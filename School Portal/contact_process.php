<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php#contact');
    exit;
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
    header('Location: index.php?contact=error#contact');
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare(
    'INSERT INTO contact_messages (full_name, email, phone, subject, message)
     VALUES (:name, :email, :phone, :subject, :message)'
);
$stmt->execute([
    'name'    => $name,
    'email'   => $email,
    'phone'   => $phone ?: null,
    'subject' => $subject ?: 'General Enquiry',
    'message' => $message,
]);

header('Location: index.php?contact=sent#contact');
exit;
