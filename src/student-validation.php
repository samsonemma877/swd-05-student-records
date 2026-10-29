<?php

function studentCourses(PDO $pdo): array
{
    $column = $pdo->query("SHOW COLUMNS FROM students LIKE 'course'")->fetch();

    if (!is_array($column) || !isset($column['Type'])) {
        throw new RuntimeException('The students course column could not be inspected.');
    }

    if (!preg_match('/^enum\((.*)\)$/i', $column['Type'], $matches)) {
        throw new RuntimeException('The students course column is not an ENUM.');
    }

    return str_getcsv($matches[1], ',', "'", '\\');
}

function validateStudent(PDO $pdo, array $student, array $courses, ?int $ignoreId = null): array
{
    $errors = [];

    foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $field => $label) {
        $value = $student[$field] ?? '';

        if ($value === '') {
            $errors[$field] = $label . ' is required.';
        } elseif (!preg_match('/^[\p{L} -]{2,60}$/u', $value)) {
            $errors[$field] = $label . ' must be 2-60 characters and contain only letters, spaces, or hyphens.';
        }
    }

    $email = $student['email'] ?? '';
    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address.';
    } else {
        $sql = 'SELECT id FROM students WHERE email = :email';
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :ignore_id';
        }
        $sql .= ' LIMIT 1';

        $statement = $pdo->prepare($sql);
        $statement->bindValue(':email', $email);
        if ($ignoreId !== null) {
            $statement->bindValue(':ignore_id', $ignoreId, PDO::PARAM_INT);
        }
        $statement->execute();

        if ($statement->fetch() !== false) {
            $errors['email'] = 'That email address is already in use.';
        }
    }

    $phone = $student['phone'] ?? '';
    if ($phone !== '' && !preg_match('/^0[789][01]\d{8}$/', $phone)) {
        $errors['phone'] = 'Enter a valid phone number or leave this field blank.';
    }

    if (!in_array($student['course'] ?? '', $courses, true)) {
        $errors['course'] = 'Select a valid course.';
    }

    $enrolledOn = $student['enrolled_on'] ?? '';
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $enrolledOn);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (
        $date === false
        || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        || $date->format('Y-m-d') !== $enrolledOn
    ) {
        $errors['enrolled_on'] = 'Enter a valid enrollment date.';
    } elseif ($enrolledOn > date('Y-m-d')) {
        $errors['enrolled_on'] = 'The enrollment date cannot be in the future.';
    }

    return $errors;
}
