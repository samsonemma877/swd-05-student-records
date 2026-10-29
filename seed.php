<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/student-validation.php';

$pageTitle = 'Seed sample students';

if (($_GET['confirm'] ?? null) !== 'yes') {
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="panel empty-state">
        <strong>Seed 50 sample students?</strong>
        This adds fake student records to the database for local pagination testing.
        <form method="get" action="seed.php">
            <input type="hidden" name="confirm" value="yes">
            <button class="button" type="submit">Insert sample students</button>
        </form>
    </div>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$firstNames = [
    'Adaeze', 'Adebayo', 'Aisha', 'Amaka', 'Babatunde',
    'Chiamaka', 'Chinedu', 'Damilola', 'Emeka', 'Fatima',
    'Ifeoma', 'Ibrahim', 'Kelechi', 'Ngozi', 'Oluwaseun',
];
$lastNames = [
    'Adeleke', 'Adeyemi', 'Bello', 'Chukwu', 'Eze',
    'Ibrahim', 'Nwankwo', 'Okafor', 'Okoye', 'Olawale',
];
$inserted = 0;

try {
    $pdo = db();
    $courses = studentCourses($pdo);
    if ($courses === []) {
        throw new RuntimeException('No course options were found.');
    }

    $statement = $pdo->prepare(
        'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
         VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
    );

    $pdo->beginTransaction();
    for ($index = 0; $index < 50; $index++) {
        $firstName = $firstNames[$index % count($firstNames)];
        $lastName = $lastNames[(int) floor($index / count($firstNames))];
        $email = sprintf('student.%02d@example.test', $index + 1);
        $phone = sprintf('0800001%04d', $index);
        $enrolledOn = (new DateTimeImmutable('today'))
            ->sub(new DateInterval('P' . random_int(0, 1200) . 'D'))
            ->format('Y-m-d');

        $statement->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email,
            ':phone' => $phone,
            ':course' => $courses[$index % count($courses)],
            ':enrolled_on' => $enrolledOn,
        ]);
        $inserted++;
    }
    $pdo->commit();
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Sample student seed failed: ' . $exception->getMessage());
    http_response_code(503);
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="panel empty-state" role="alert">
        <strong>Sample students could not be added.</strong>
        Make sure MySQL is running and the students table is available, then try again.
    </div>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
} catch (RuntimeException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Sample student seed configuration error: ' . $exception->getMessage());
    http_response_code(500);
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="panel empty-state" role="alert">
        <strong>Sample students could not be added.</strong>
        The course options could not be loaded from the students table.
    </div>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

require __DIR__ . '/partials/header.php';
?>
<div class="panel empty-state">
    <strong>Sample data added</strong>
    <?= e($inserted) ?> fake students were inserted.
    <p><a class="button" href="index.php">View students</a></p>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
