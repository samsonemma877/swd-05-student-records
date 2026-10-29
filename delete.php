<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    $pageTitle = 'Method not allowed';
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="panel empty-state" role="alert">
        <strong>Method not allowed.</strong>
        Student records can only be deleted by submitting the delete form.
    </div>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? null;
if (!isValidCsrfToken(is_string($submittedToken) ? $submittedToken : null)) {
    http_response_code(403);
    $pageTitle = 'Request rejected';
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="panel empty-state" role="alert">
        <strong>Request rejected.</strong>
        The form security check failed. Return to the student list and try again.
    </div>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    flash('error', 'Student not found');
    redirect('index.php');
}

try {
    $statement = db()->prepare('DELETE FROM students WHERE id = :id');
    $statement->bindValue(':id', $id, PDO::PARAM_INT);
    $statement->execute();

    flash('success', $statement->rowCount() > 0 ? 'Student deleted' : 'Student not found');
    redirect('index.php');
} catch (PDOException $exception) {
    error_log('Student record could not be deleted: ' . $exception->getMessage());
    http_response_code(503);
    $pageTitle = 'Student records unavailable';
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="panel empty-state" role="alert">
        <strong>The student could not be deleted.</strong>
        Student records are temporarily unavailable. Please try again later.
    </div>
    <?php
    require __DIR__ . '/partials/footer.php';
}
