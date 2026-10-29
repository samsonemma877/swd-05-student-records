<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/student-validation.php';

$pageTitle = 'Students';
$perPage = 10;
$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$selectedCourse = isset($_GET['course']) && is_string($_GET['course']) ? $_GET['course'] : '';
$requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = $requestedPage !== false && $requestedPage !== null && $requestedPage > 0
    ? $requestedPage
    : 1;
$students = [];
$courses = [];
$totalStudents = 0;
$totalPages = 0;
$databaseError = false;

try {
    $pdo = db();
    $courses = studentCourses($pdo);

    if (!in_array($selectedCourse, $courses, true)) {
        $selectedCourse = '';
    }

    $conditions = [];
    $parameters = [];
    if ($search !== '') {
        $conditions[] = '(first_name LIKE :first_name OR last_name LIKE :last_name OR email LIKE :email)';
        $likeSearch = '%' . $search . '%';
        $parameters[':first_name'] = $likeSearch;
        $parameters[':last_name'] = $likeSearch;
        $parameters[':email'] = $likeSearch;
    }
    if ($selectedCourse !== '') {
        $conditions[] = 'course = :course';
        $parameters[':course'] = $selectedCourse;
    }

    $whereClause = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
    $countStatement = $pdo->prepare('SELECT COUNT(*) FROM students' . $whereClause);
    $countStatement->execute($parameters);
    $totalStudents = (int) $countStatement->fetchColumn();
    $totalPages = (int) ceil($totalStudents / $perPage);
    $page = $totalPages === 0 ? 1 : min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $listStatement = $pdo->prepare(
        'SELECT id, first_name, last_name, email, course, enrolled_on
         FROM students'
        . $whereClause
        . ' ORDER BY last_name, first_name
            LIMIT :limit OFFSET :offset'
    );
    foreach ($parameters as $name => $value) {
        $listStatement->bindValue($name, $value);
    }
    $listStatement->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $listStatement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $listStatement->execute();
    $students = $listStatement->fetchAll();
} catch (PDOException $exception) {
    $databaseError = true;
    error_log('Student records database error: ' . $exception->getMessage());
} catch (RuntimeException $exception) {
    $databaseError = true;
    error_log('Student records configuration error: ' . $exception->getMessage());
}

$paginationParameters = [];
if ($search !== '') {
    $paginationParameters['q'] = $search;
}
if ($selectedCourse !== '') {
    $paginationParameters['course'] = $selectedCourse;
}
$paginationUrl = static function (int $targetPage) use ($paginationParameters): string {
    return 'index.php?' . http_build_query($paginationParameters + ['page' => $targetPage]);
};

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
    <?php else: ?>
        <form class="student-filters" method="get" action="index.php">
            <div class="filter-field">
                <label for="q">Search students</label>
                <input
                    id="q"
                    name="q"
                    type="search"
                    value="<?= e($search) ?>"
                    placeholder="Name or email"
                >
            </div>
            <div class="filter-field">
                <label for="course">Course</label>
                <select id="course" name="course">
                    <option value="">All courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= e($course) ?>" <?= $selectedCourse === $course ? 'selected' : '' ?>>
                            <?= e($course) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="button" type="submit">Search</button>
            <?php if ($search !== '' || $selectedCourse !== ''): ?>
                <a class="button button-secondary" href="index.php">Clear filters</a>
            <?php endif; ?>
        </form>

        <?php if ($students === []): ?>
            <div class="panel empty-state">
                <strong>No students found</strong>
                <?= $search !== '' || $selectedCourse !== ''
                    ? 'Try changing or clearing your filters.'
                    : 'Add a student to get started.' ?>
            </div>
        <?php else: ?>
            <div class="results-summary" aria-live="polite">
                Showing <?= e((string) ($offset + 1)) ?>–<?= e((string) ($offset + count($students))) ?>
                of <?= e((string) $totalStudents) ?> students
            </div>
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

            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Student list pages">
                    <?php if ($page > 1): ?>
                        <a href="<?= e($paginationUrl($page - 1)) ?>" rel="prev">Previous</a>
                    <?php else: ?>
                        <span class="pagination-disabled">Previous</span>
                    <?php endif; ?>

                    <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                        <?php if ($pageNumber === $page): ?>
                            <span class="pagination-current" aria-current="page"><?= e($pageNumber) ?></span>
                        <?php else: ?>
                            <a href="<?= e($paginationUrl($pageNumber)) ?>"><?= e($pageNumber) ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?= e($paginationUrl($page + 1)) ?>" rel="next">Next</a>
                    <?php else: ?>
                        <span class="pagination-disabled">Next</span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
