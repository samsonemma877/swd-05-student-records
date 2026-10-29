<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';

$pageTitle = 'Students';
$students = [];
$databaseError = false;

try {
    $students = db()
        ->query(
            'SELECT id, first_name, last_name, email, course, enrolled_on
             FROM students
             ORDER BY last_name, first_name'
        )
        ->fetchAll();
} catch (PDOException $exception) {
    $databaseError = true;
    error_log('Student records database error: ' . $exception->getMessage());
}

require __DIR__ . '/partials/header.php';
?>
<section aria-labelledby="page-title">
    <div class="page-heading">
        <div>
            <h1 id="page-title">Students</h1>
            <p class="page-description">Manage student enrolments and records.</p>
        </div>
        <a class="button" href="create.php">Add student</a>
    </div>

    <?php if ($databaseError): ?>
        <div class="panel empty-state" role="alert">
            <strong>Student records are temporarily unavailable.</strong>
            We could not connect to the database. Please make sure MySQL is running in XAMPP, then refresh this page.
        </div>
    <?php elseif ($students === []): ?>
        <div class="panel empty-state">
            <strong>No students found</strong>
            Add a student to get started.
        </div>
    <?php else: ?>
        <div class="panel table-wrap">
            <table class="student-table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Course</th>
                        <th scope="col">Enrolled</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td>
                                <a class="student-name" href="show.php?id=<?= e($student['id']) ?>">
                                    <?= e(trim($student['first_name'] . ' ' . $student['last_name'])) ?>
                                </a>
                            </td>
                            <td><?= e($student['email']) ?></td>
                            <td><?= e($student['course']) ?></td>
                            <td>
                                <?php
                                $enrolledOn = DateTime::createFromFormat('!Y-m-d', $student['enrolled_on']);
                                echo $enrolledOn ? e($enrolledOn->format('d M Y')) : e($student['enrolled_on']);
                                ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="edit.php?id=<?= e($student['id']) ?>">Edit</a>
                                    <form
                                        method="post"
                                        action="delete.php"
                                        data-confirm="<?= e('Delete ' . trim($student['first_name'] . ' ' . $student['last_name']) . '?') ?>"
                                        onsubmit="return confirm(this.dataset.confirm)"
                                    >
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="id" value="<?= e($student['id']) ?>">
                                        <button class="action-delete action-button" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
