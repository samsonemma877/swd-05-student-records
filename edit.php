<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/student-validation.php';

$pageTitle = 'Edit student';
$errors = [];
$courses = [];
$csrfToken = csrfToken();
$submitLabel = 'Save changes';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id < 1) {
    http_response_code(404);
    $student = [];
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="panel empty-state" role="alert">
        <strong>Student not found.</strong>
        The requested student record could not be found.
    </div>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

try {
    $pdo = db();
    $courses = studentCourses($pdo);
    $statement = $pdo->prepare(
        'SELECT id, first_name, last_name, email, phone, course, enrolled_on
         FROM students
         WHERE id = :id'
    );
    $statement->execute([':id' => $id]);
    $student = $statement->fetch();

    if ($student === false) {
        http_response_code(404);
        $student = [];
        require __DIR__ . '/partials/header.php';
        ?>
        <div class="panel empty-state" role="alert">
            <strong>Student not found.</strong>
            The requested student record could not be found.
        </div>
        <?php
        require __DIR__ . '/partials/footer.php';
        exit;
    }
} catch (PDOException $exception) {
    error_log('Student records database error: ' . $exception->getMessage());
    http_response_code(503);
    $student = [];
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="panel empty-state" role="alert">
        <strong>Student records are temporarily unavailable.</strong>
        We could not load this student. Please make sure MySQL is running in XAMPP, then try again.
    </div>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
} catch (RuntimeException $exception) {
    error_log('Student records configuration error: ' . $exception->getMessage());
    http_response_code(500);
    $student = [];
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="panel empty-state" role="alert">
        <strong>The student form could not be loaded.</strong>
        Please check the students table configuration and try again.
    </div>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['first_name', 'last_name', 'email', 'phone', 'course', 'enrolled_on'] as $field) {
        $value = $_POST[$field] ?? '';
        $student[$field] = is_string($value) ? trim($value) : '';
    }

    $submittedToken = $_POST['csrf_token'] ?? null;
    if (!isValidCsrfToken(is_string($submittedToken) ? $submittedToken : null)) {
        $errors['_form'] = 'Your form session expired. Please try submitting again.';
    } else {
        try {
            $errors = validateStudent($pdo, $student, $courses, (int) $id);

            if ($errors === []) {
                $statement = $pdo->prepare(
                    'UPDATE students
                     SET first_name = :first_name,
                         last_name = :last_name,
                         email = :email,
                         phone = :phone,
                         course = :course,
                         enrolled_on = :enrolled_on
                     WHERE id = :id'
                );
                $statement->execute([
                    ':first_name' => $student['first_name'],
                    ':last_name' => $student['last_name'],
                    ':email' => $student['email'],
                    ':phone' => $student['phone'] === '' ? null : $student['phone'],
                    ':course' => $student['course'],
                    ':enrolled_on' => $student['enrolled_on'],
                    ':id' => $id,
                ]);

                flash('success', 'Student updated');
                redirect('index.php');
            }
        } catch (PDOException $exception) {
            error_log('Student record could not be updated: ' . $exception->getMessage());
            if (($exception->errorInfo[1] ?? null) === 1062) {
                $errors['email'] = 'That email address is already in use.';
            } else {
                $errors['_form'] = 'The student could not be saved. Please try again.';
            }
        }
    }
}

$pageTitle = 'Edit student';
$formAction = 'edit.php?id=' . (int) $id;

require __DIR__ . '/partials/header.php';
?>
<section aria-labelledby="page-title">
    <div class="page-heading">
        <div>
            <h1 id="page-title">Edit student</h1>
            <p class="page-description">Update the student details below.</p>
        </div>
    </div>

    <?php require __DIR__ . '/partials/student-form.php'; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
