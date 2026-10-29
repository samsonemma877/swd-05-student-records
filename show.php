<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';

$pageTitle = 'Student details';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id < 1) {
    http_response_code(404);
    $student = false;
} else {
    try {
        $statement = db()->prepare(
            'SELECT id, first_name, last_name, email, phone, course, enrolled_on, created_at, updated_at
             FROM students
             WHERE id = :id'
        );
        $statement->execute([':id' => $id]);
        $student = $statement->fetch();
        if ($student === false) {
            http_response_code(404);
        }
    } catch (PDOException $exception) {
        error_log('Student records database error: ' . $exception->getMessage());
        http_response_code(503);
        $student = null;
    }
}

require __DIR__ . '/partials/header.php';
?>
<?php if ($student === null): ?>
    <div class="panel empty-state" role="alert">
        <strong>Student records are temporarily unavailable.</strong>
        We could not load this student. Please make sure MySQL is running in XAMPP, then try again.
    </div>
<?php elseif ($student === false): ?>
    <div class="panel empty-state" role="alert">
        <strong>Student not found.</strong>
        The requested student record could not be found.
    </div>
<?php else: ?>
    <section aria-labelledby="page-title">
        <div class="page-heading">
            <div>
                <h1 id="page-title">
                    <?= e(trim($student['first_name'] . ' ' . $student['last_name'])) ?>
                </h1>
                <p class="page-description">Student record details.</p>
            </div>
            <a class="button" href="edit.php?id=<?= e($student['id']) ?>">Edit student</a>
        </div>

        <dl class="panel student-details">
            <div><dt>Email</dt><dd><?= e($student['email']) ?></dd></div>
            <div><dt>Phone</dt><dd><?= e($student['phone'] ?: 'Not provided') ?></dd></div>
            <div><dt>Course</dt><dd><?= e($student['course']) ?></dd></div>
            <div>
                <dt>Enrolled</dt>
                <dd>
                    <?php
                    $enrolledOn = DateTime::createFromFormat('!Y-m-d', $student['enrolled_on']);
                    echo $enrolledOn ? e($enrolledOn->format('d M Y')) : e($student['enrolled_on']);
                    ?>
                </dd>
            </div>
            <div><dt>Created</dt><dd><?= e($student['created_at']) ?></dd></div>
            <div><dt>Last updated</dt><dd><?= e($student['updated_at']) ?></dd></div>
        </dl>
        <p><a href="index.php">Back to students</a></p>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
