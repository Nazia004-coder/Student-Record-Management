<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = 'Invalid request. Please refresh the page.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare('INSERT INTO Students
                    (StudentName, Email, Phone, DepartmentID, Semester, CGPA)
                    VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    trim($_POST['name']), trim($_POST['email']),
                    trim($_POST['phone']), (int)$_POST['department'],
                    (int)$_POST['semester'], $_POST['cgpa'] === '' ? null : $_POST['cgpa']
                ]);
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare('DELETE FROM Students WHERE StudentID = ?');
                $stmt->execute([(int)$_POST['id']]);
            } elseif ($action === 'update') {
                $stmt = $pdo->prepare('UPDATE Students SET StudentName=?, Email=?, Phone=?,
                    DepartmentID=?, Semester=?, CGPA=? WHERE StudentID=?');
                $stmt->execute([
                    trim($_POST['name']), trim($_POST['email']), trim($_POST['phone']),
                    (int)$_POST['department'], (int)$_POST['semester'],
                    $_POST['cgpa'] === '' ? null : $_POST['cgpa'], (int)$_POST['id']
                ]);
            }
            header('Location: index.php'); exit;
        } catch (PDOException $ex) {
            $error = 'Could not save the record. Check that the email is unique and all fields are valid.';
        }
    }
}

$departments = $pdo->query('SELECT * FROM Departments ORDER BY DepartmentName')->fetchAll();
$search = trim($_GET['search'] ?? '');
$sql = 'SELECT s.*, d.DepartmentName FROM Students s
        LEFT JOIN Departments d ON s.DepartmentID=d.DepartmentID';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE s.StudentName LIKE ? OR s.Email LIKE ? OR d.DepartmentName LIKE ?';
    $term = "%$search%"; $params = [$term, $term, $term];
}
$sql .= ' ORDER BY s.StudentID DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$students = $stmt->fetchAll();
$stats = $pdo->query('SELECT COUNT(*) total, AVG(CGPA) average_cgpa,
    MAX(CGPA) highest_cgpa, MIN(CGPA) lowest_cgpa FROM Students')->fetch();
$editStudent = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM Students WHERE StudentID=?');
    $stmt->execute([(int)$_GET['edit']]); $editStudent = $stmt->fetch();
}
?>
<!doctype html>
<html lang="en">
<head>
  <link rel="stylesheet" href="assets/style.css">
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Student Record Management System</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header><div class="container"><h1>Student Record Management System</h1>
<p>Manage student information easily</p></div></header>
<main class="container">
<?php if ($error): ?><p class="alert"><?=e($error)?></p><?php endif; ?>
<section class="stats">
<div class="stat"><span>Total Students</span><strong><?=e($stats['total'])?></strong></div>
<div class="stat"><span>Average CGPA</span><strong><?=e(number_format((float)$stats['average_cgpa'],2))?></strong></div>
<div class="stat"><span>Highest CGPA</span><strong><?=e($stats['highest_cgpa'] ?? '—')?></strong></div>
<div class="stat"><span>Lowest CGPA</span><strong><?=e($stats['lowest_cgpa'] ?? '—')?></strong></div>
</section>
<section class="panel">
<h2><?=$editStudent ? 'Update Student' : 'Add New Student'?></h2>
<form method="post">
<input type="hidden" name="csrf" value="<?=e($_SESSION['csrf'])?>">
<input type="hidden" name="action" value="<?=$editStudent ? 'update' : 'add'?>">
<?php if ($editStudent): ?><input type="hidden" name="id" value="<?=e($editStudent['StudentID'])?>"><?php endif; ?>
<div class="form-grid">
<label>Student Name<input required name="name" value="<?=e($editStudent['StudentName'] ?? '')?>"></label>
<label>Email<input required type="email" name="email" value="<?=e($editStudent['Email'] ?? '')?>"></label>
<label>Phone<input name="phone" value="<?=e($editStudent['Phone'] ?? '')?>"></label>
<label>Department<select required name="department"><option value="">Choose department</option>
<?php foreach ($departments as $d): ?><option value="<?=e($d['DepartmentID'])?>" <?=isset($editStudent['DepartmentID']) && $editStudent['DepartmentID']==$d['DepartmentID']?'selected':''?>><?=e($d['DepartmentName'])?></option><?php endforeach; ?>
</select></label>
<label>Semester<input required type="number" min="1" max="12" name="semester" value="<?=e($editStudent['Semester'] ?? '')?>"></label>
<label>CGPA<input type="number" min="0" max="4" step="0.01" name="cgpa" value="<?=e($editStudent['CGPA'] ?? '')?>"></label>
</div>
<div class="actions"><button type="submit"><?=$editStudent ? 'Update Student' : 'Add Student'?></button>
<?php if ($editStudent): ?><a class="button secondary" href="index.php">Cancel</a><?php endif; ?></div>
</form></section>
<section class="panel">
<div class="list-heading"><h2>Student Records</h2>
<form method="get" class="search"><input name="search" placeholder="Search name, email or department" value="<?=e($search)?>"><button>Search</button></form></div>
<div class="table-wrap"><table><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Department</th><th>Semester</th><th>CGPA</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($students as $s): ?><tr>
<td><?=e($s['StudentID'])?></td><td><?=e($s['StudentName'])?></td><td><?=e($s['Email'])?></td><td><?=e($s['Phone'])?></td><td><?=e($s['DepartmentName'])?></td><td><?=e($s['Semester'])?></td><td><?=e($s['CGPA'])?></td>
<td class="row-actions"><a href="?edit=<?=e($s['StudentID'])?>">Edit</a>
<form method="post" onsubmit="return confirm('Delete this student record?')"><input type="hidden" name="csrf" value="<?=e($_SESSION['csrf'])?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=e($s['StudentID'])?>"><button class="danger">Delete</button></form></td>
</tr><?php endforeach; ?>
<?php if (!$students): ?><tr><td colspan="8">No student records found.</td></tr><?php endif; ?>
</tbody></table></div></section>
<footer>Student Record Management System · PHP &amp; MySQL</footer>
</main></body></html>
