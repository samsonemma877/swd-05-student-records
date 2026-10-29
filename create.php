<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/student-validation.php';

$pageTitle = 'Add student';
$student = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'course' => '',
    'enrolled_on' => date('Y-m-d'),
];
$errors = [];
$courses = [];
$csrfToken = csrfToken();
$submitLabel = 'Add student';

try {
    $pdo = db();
    $courses = studentCourses($pdo);
} catch (PDOException $exception) {
    error_log('Student records database error: ' . $exception->getMessage());
    http_response_code(503);
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="panel empty-state" role="alert">
        <strong>Student records are temporarily unavailable.</strong>
        We could not connect to the database. Please make sure MySQL is running in XAMPP, then try again.
    </div>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
} catch (RuntimeException $exception) {
    error_log('Student records configuration error: ' . $exception->getMessage());
    http_response_code(500);
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
    foreach ($student as $field => $default) {
        $value = $_POST[$field] ?? $default;
        $student[$field] = is_string($value) ? trim($value) : '';
    }

    $submittedToken = $_POST['csrf_token'] ?? null;
    if (!isValidCsrfToken(is_string($submittedToken) ? $submittedToken : null)) {
        $errors['_form'] = 'Your form session expired. Please try submitting again.';
    } else {
        try {
            $errors = validateStudent($pdo, $student, $courses);

            if ($errors === []) {
                $statement = $pdo->prepare(
                    'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
                     VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
                );
                $statement->execute([
                    ':first_name' => $student['first_name'],
                    ':last_name' => $student['last_name'],
                    ':email' => $student['email'],
                    ':phone' => $student['phone'] === '' ? null : $student['phone'],
                    ':course' => $student['course'],
                    ':enrolled_on' => $student['enrolled_on'],
                ]);

                flash('success', 'Student added');
                redirect('index.php');
            }
        } catch (PDOException $exception) {
            error_log('Student record could not be added: ' . $exception->getMessage());
            if (($exception->errorInfo[1] ?? null) === 1062) {
                $errors['email'] = 'That email address is already in use.';
            } else {
                $errors['_form'] = 'The student could not be saved. Please try again.';
            }
        }
    }
}

require __DIR__ . '/partials/header.php';
?>
<section aria-labelledby="page-title">
    <div class="page-heading">
        <div>
            <h1 id="page-title">Add student</h1>
            <p class="page-description">Enter the student details below.</p>
        </div>
    </div>

    <?php require __DIR__ . '/partials/student-form.php'; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
