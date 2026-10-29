<?php

$student = $student ?? [];
$errors = $errors ?? [];
$courses = $courses ?? [];
$formAction = $formAction ?? '';
$submitLabel = $submitLabel ?? 'Save student';
$fields = [
    'first_name' => 'First name',
    'last_name' => 'Last name',
    'email' => 'Email address',
    'phone' => 'Phone (optional)',
    'enrolled_on' => 'Enrollment date',
];
?>
<?php if (isset($errors['_form'])): ?>
    <div class="alert alert-error" role="alert"><?= e($errors['_form']) ?></div>
<?php endif; ?>
<form class="student-form panel" method="post" action="<?= e($formAction) ?>">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <?php foreach ($fields as $field => $label): ?>
        <div class="form-field">
            <label for="<?= e($field) ?>"><?= e($label) ?></label>
            <input
                id="<?= e($field) ?>"
                name="<?= e($field) ?>"
                type="<?= $field === 'email' ? 'email' : ($field === 'enrolled_on' ? 'date' : 'text') ?>"
                value="<?= e($student[$field] ?? '') ?>"
                <?= $field === 'phone' ? 'inputmode="numeric"' : '' ?>
                <?= in_array($field, ['first_name', 'last_name', 'email', 'enrolled_on'], true) ? 'required' : '' ?>
                <?= isset($errors[$field]) ? 'aria-invalid="true" aria-describedby="' . e($field) . '-error"' : '' ?>
            >
            <?php if (isset($errors[$field])): ?>
                <span class="field-error" id="<?= e($field) ?>-error"><?= e($errors[$field]) ?></span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="form-field">
        <label for="course">Course</label>
        <select
            id="course"
            name="course"
            required
            <?= isset($errors['course']) ? 'aria-invalid="true" aria-describedby="course-error"' : '' ?>
        >
            <option value="">Select a course</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?= e($course) ?>" <?= ($student['course'] ?? '') === $course ? 'selected' : '' ?>>
                    <?= e($course) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['course'])): ?>
            <span class="field-error" id="course-error"><?= e($errors['course']) ?></span>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <a class="button button-secondary" href="index.php">Cancel</a>
        <button class="button" type="submit"><?= e($submitLabel) ?></button>
    </div>
</form>
