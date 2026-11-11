<?php
session_start();
require_once 'config.php';

// CSRF Security
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// Safe output function
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$error = '';
$success = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = 'Invalid request. Please refresh the page.';
    } else {

        $action = $_POST['action'] ?? '';

        try {

            // ADD STUDENT
            if ($action === 'add') {

                $stmt = $pdo->prepare(
                    'INSERT INTO Students
                    (StudentName, Email, Phone, DepartmentID, Semester, CGPA)
                    VALUES (?, ?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    trim($_POST['name'] ?? ''),
                    trim($_POST['email'] ?? ''),
                    trim($_POST['phone'] ?? ''),
                    (int)($_POST['department'] ?? 0),
                    (int)($_POST['semester'] ?? 0),
                    ($_POST['cgpa'] ?? '') === '' ? null : $_POST['cgpa']
                ]);

                $_SESSION['success'] = 'Student added successfully!';
            }

            // UPDATE STUDENT
            elseif ($action === 'update') {

                $stmt = $pdo->prepare(
                    'UPDATE Students
                    SET StudentName=?, Email=?, Phone=?,
                    DepartmentID=?, Semester=?, CGPA=?
                    WHERE StudentID=?'
                );

                $stmt->execute([
                    trim($_POST['name'] ?? ''),
                    trim($_POST['email'] ?? ''),
                    trim($_POST['phone'] ?? ''),
                    (int)($_POST['department'] ?? 0),
                    (int)($_POST['semester'] ?? 0),
                    ($_POST['cgpa'] ?? '') === '' ? null : $_POST['cgpa'],
                    (int)($_POST['id'] ?? 0)
                ]);

                $_SESSION['success'] = 'Student updated successfully!';
            }

            // DELETE STUDENT
            elseif ($action === 'delete') {

                $stmt = $pdo->prepare(
                    'DELETE FROM Students WHERE StudentID=?'
                );

                $stmt->execute([
                    (int)($_POST['id'] ?? 0)
                ]);

                $_SESSION['success'] = 'Student deleted successfully!';
            }

            header('Location: index.php');
            exit;

        } catch (PDOException $ex) {

            if ($ex->getCode() == '23000') {
                $error = 'This email already exists or the selected department is invalid.';
            } else {
                $error = 'Database error. Please check your database configuration.';
            }
        }
    }
}

// Success Message
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Get Departments
$departments = $pdo->query(
    'SELECT * FROM Departments ORDER BY DepartmentName'
)->fetchAll(PDO::FETCH_ASSOC);

// Search Students
$search = trim($_GET['search'] ?? '');

$sql = 'SELECT s.*, d.DepartmentName
        FROM Students s
        LEFT JOIN Departments d
        ON s.DepartmentID = d.DepartmentID';

$params = [];

if ($search !== '') {
    $sql .= ' WHERE s.StudentName LIKE ?
              OR s.Email LIKE ?
              OR d.DepartmentName LIKE ?';

    $term = "%$search%";
    $params = [$term, $term, $term];
}

$sql .= ' ORDER BY s.StudentID DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Student Statistics
$stats = $pdo->query(
    'SELECT
    COUNT(*) AS total,
    AVG(CGPA) AS average_cgpa,
    MAX(CGPA) AS highest_cgpa,
    MIN(CGPA) AS lowest_cgpa
    FROM Students'
)->fetch(PDO::FETCH_ASSOC);

// Get Student for Editing
$editStudent = null;

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare(
        'SELECT * FROM Students WHERE StudentID=?'
    );

    $stmt->execute([(int)$_GET['edit']]);
    $editStudent = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Record Management System</title>

    <!-- CSS File: Three assets folders -->
    <link rel="stylesheet" href="assets/assets/assets/style.css">

</head>

<body>

<header>
    <div class="container">

        <h1>Student Record Management System</h1>

        <p>Manage Student Information Easily</p>

    </div>
</header>

<main class="container">

    <!-- Messages -->

    <?php if ($error): ?>
        <p class="alert">
            <?= e($error) ?>
        </p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="success">
            <?= e($success) ?>
        </p>
    <?php endif; ?>


    <!-- Student Statistics -->

    <section class="stats">

        <div class="stat">
            <span>Total Students</span>
            <strong><?= e($stats['total']) ?></strong>
        </div>

        <div class="stat">
            <span>Average CGPA</span>
            <strong>
                <?= e(number_format((float)$stats['average_cgpa'], 2)) ?>
            </strong>
        </div>

        <div class="stat">
            <span>Highest CGPA</span>
            <strong>
                <?= e($stats['highest_cgpa'] ?? '—') ?>
            </strong>
        </div>

        <div class="stat">
            <span>Lowest CGPA</span>
            <strong>
                <?= e($stats['lowest_cgpa'] ?? '—') ?>
            </strong>
        </div>

    </section>


    <!-- Add / Update Student Form -->

    <section class="panel">

        <h2>
            <?= $editStudent ? 'Update Student' : 'Add New Student' ?>
        </h2>

        <form method="POST">

            <input type="hidden" name="csrf"
                   value="<?= e($_SESSION['csrf']) ?>">

            <input type="hidden" name="action"
                   value="<?= $editStudent ? 'update' : 'add' ?>">

            <?php if ($editStudent): ?>
                <input type="hidden" name="id"
                       value="<?= e($editStudent['StudentID']) ?>">
            <?php endif; ?>

            <div class="form-grid">

                <label>
                    Student Name
                    <input
                        type="text"
                        name="name"
                        required
                        value="<?= e($editStudent['StudentName'] ?? '') ?>"
                        placeholder="Enter student name">
                </label>

                <label>
                    Email
                    <input
                        type="email"
                        name="email"
                        required
                        value="<?= e($editStudent['Email'] ?? '') ?>"
                        placeholder="Enter email address">
                </label>

                <label>
                    Phone
                    <input
                        type="text"
                        name="phone"
                        value="<?= e($editStudent['Phone'] ?? '') ?>"
                        placeholder="Enter phone number">
                </label>

                <label>
                    Department

                    <select name="department" required>

                        <option value="">Choose Department</option>

                        <?php foreach ($departments as $d): ?>

                            <option
                                value="<?= e($d['DepartmentID']) ?>"
                                <?= isset($editStudent['DepartmentID'])
                                && $editStudent['DepartmentID'] == $d['DepartmentID']
                                ? 'selected' : '' ?>>

                                <?= e($d['DepartmentName']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </label>

                <label>
                    Semester
                    <input
                        type="number"
                        name="semester"
                        min="1"
                        max="12"
                        required
                        value="<?= e($editStudent['Semester'] ?? '') ?>"
                        placeholder="Enter semester">
                </label>

                <label>
                    CGPA
                    <input
                        type="number"
                        name="cgpa"
                        min="0"
                        max="4"
                        step="0.01"
                        value="<?= e($editStudent['CGPA'] ?? '') ?>"
                        placeholder="Enter CGPA">
                </label>

            </div>

            <div class="actions">

                <button type="submit">
                    <?= $editStudent ? 'Update Student' : 'Add Student' ?>
                </button>

                <?php if ($editStudent): ?>

                    <a href="index.php" class="button secondary">
                        Cancel
                    </a>

                <?php endif; ?>

            </div>

        </form>

    </section>


    <!-- Student Records -->

    <section class="panel">

        <div class="list-heading">

            <h2>Student Records</h2>

            <form method="GET" class="search">

                <input
                    type="text"
                    name="search"
                    placeholder="Search name, email or department"
                    value="<?= e($search) ?>">

                <button type="submit">Search</button>

                <?php if ($search !== ''): ?>
                    <a href="index.php" class="button secondary">
                        Clear
                    </a>
                <?php endif; ?>

            </form>

        </div>


        <div class="table-wrap">

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>CGPA</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($students as $s): ?>

                        <tr>

                            <td><?= e($s['StudentID']) ?></td>

                            <td><?= e($s['StudentName']) ?></td>

                            <td><?= e($s['Email']) ?></td>

                            <td><?= e($s['Phone']) ?></td>

                            <td><?= e($s['DepartmentName']) ?></td>

                            <td><?= e($s['Semester']) ?></td>

                            <td><?= e($s['CGPA']) ?></td>

                            <td class="row-actions">

                                <a href="?edit=<?= e($s['StudentID']) ?>">
                                    Edit
                                </a>

                                <form
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this student?')">

                                    <input type="hidden" name="csrf"
                                           value="<?= e($_SESSION['csrf']) ?>">

                                    <input type="hidden" name="action"
                                           value="delete">

                                    <input type="hidden" name="id"
                                           value="<?= e($s['StudentID']) ?>">

                                    <button type="submit" class="danger">
                                        Delete
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                    <?php if (!$students): ?>

                        <tr>
                            <td colspan="8">
                                No student records found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- Footer -->

    <footer>
        Student Record Management System
        <br>
        PHP &amp; MySQL
    </footer>

</main>

</body>
</html>
