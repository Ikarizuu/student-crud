<?php
//Start session (to track flash messages)
session_start();
include 'config.php';

$error = "";
$message = "";

//Retrieve pending flash status parameters from global session state
if (isset($_SESSION['msg'])) {
    $message = $_SESSION['msg'];
    unset($_SESSION['msg']);
}
if (isset($_SESSION['err'])) {
    $error = $_SESSION['err'];
    unset($_SESSION['err']);
}

//================CREATE FUNCTION===============
if (isset($_POST['submit'])) {
    $name = trim($_POST['name'] ?? '');
    $course = trim($_POST['course'] ?? '');

    if ($name == "" || $course == "") {
        $_SESSION['err'] = "All fields are required!";
        header("Location: index.php");
        exit();
    } else {
        $response = callGoogleSheetAPI([
            'action' => 'create',
            'name' => $name,
            'course' => $course
        ], true);

        if (isset($response['status']) && $response['status'] === "success") {
            $_SESSION['msg'] = "Student record created successfully!";
        } else {
            $_SESSION['err'] = "Failed to write to Google Sheets.";
        }
        header("Location: index.php");
        exit();
    }
}

//================UPDATE FUNCTION===============
if (isset($_POST['update'])) {
    $id = intval($_POST['student_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $course = trim($_POST['course'] ?? '');

    if ($name == "" || $course == "") {
        $_SESSION['err'] = "All update parameters are required!";
        header("Location: index.php");
        exit();
    } else {
        $response = callGoogleSheetAPI([
            'action' => 'update',
            'id' => $id,
            'name' => $name,
            'course' => $course
        ], true);

        if (isset($response['status']) && $response['status'] === "success") {
            $_SESSION['msg'] = "Student record updated successfully!";
        } else {
            $_SESSION['err'] = "Failed to update Google Sheets.";
        }
        header("Location: index.php");
        exit();
    }
}

//Fetch students array from Google Sheets (GET request)
$students = callGoogleSheetAPI(['action' => 'read'], false);
if (!is_array($students)) {
    $students = [];
}

//Fetch operational load for edit initialization
$edit_mode = false;
$edit_id = "";
$edit_name = "";
$edit_course = "";
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = intval($_GET['edit']);
    foreach ($students as $row) {
        if (isset($row['id']) && intval($row['id']) === $id) {
            $edit_id = $row['id'];
            $edit_name = $row['name'];
            $edit_course = $row['course'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Sheet CRUD System</title>
    <!--Bootstrap 5 CSS CDN-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!--Font Awesome Icons-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            --card-bg: #ffffff;
            --primary-accent: #2563eb;
            --primary-hover: #1d4ed8;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
        }

        body {
            background: var(--bg-gradient);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        /*Navigation Bar Design*/
        .navbar-custom {
            background-color: #0f172a !important;
            padding: 16px 0;
            box-shadow: var(--shadow-sm);
        }

        .navbar-custom .navbar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        /*Polished Cards Framework*/
        .custom-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            padding: 32px;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .custom-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08);
        }

        .card-header-title {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text-main);
        }

        /*Form Inputs Custom Styling*/
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .input-group-text {
            background-color: #f8fafc;
            border: 1.5px solid var(--border-color);
            border-right: none;
            color: var(--text-muted);
            border-radius: 8px 0 0 8px;
            padding-left: 14px;
            padding-right: 14px;
        }

        .form-control {
            border: 1.5px solid var(--border-color);
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 0.95rem;
            color: var(--text-main);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary-accent);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        /*Modern Custom Action Buttons*/
        .btn-custom-primary {
            background-color: var(--primary-accent);
            border: none;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 12px 24px;
            border-radius: 8px;
            transition: background-color 0.2s;
            width: 100%;
        }

        .btn-custom-primary:hover {
            background-color: var(--primary-hover);
        }

        .btn-custom-secondary {
            background-color: #e2e8f0;
            border: none;
            color: #334155;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            transition: background-color 0.2s;
            width: 100%;
        }

        .btn-custom-secondary:hover {
            background-color: #cbd5e1;
            color: #1e293b;
        }

        /*Sleek Action Icons*/
        .action-btn {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
            border: 1.5px solid transparent;
        }

        .action-btn-edit {
            background-color: #fef3c7;
            color: #d97706;
        }

        .action-btn-edit:hover {
            background-color: #fcd34d;
            transform: scale(1.05);
        }

        .action-btn-delete {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .action-btn-delete:hover {
            background-color: #fca5a5;
            transform: scale(1.05);
        }

        /*Table Refinements*/
        .table-responsive {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f8fafc;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            border-bottom: 1.5px solid var(--border-color);
            padding: 16px 20px;
        }

        .table tbody td {
            padding: 16px 20px;
            color: var(--text-main);
            border-bottom: 1px solid var(--border-color);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .id-badge {
            background: #f1f5f9;
            color: #475569;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <!--Navigation Bar Component-->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom mb-5">
        <div class="container">
            <span class="navbar-brand mb-0 h1"><i class="fas fa-graduation-cap me-2"></i>Student List Portal</span>
        </div>
    </nav>

    <div class="container">
        <div class="row g-4">
            
            <!--Left Grid: Registration Form Area (Create/Update)-->
            <div class="col-lg-4">
                <div class="custom-card">
                    <h4 class="card-header-title mb-4">
                        <i class="fas <?= $edit_mode ? 'fa-user-edit text-warning' : 'fa-user-plus text-primary' ?> me-2"></i>
                        <?= $edit_mode ? 'Modify Student Record' : 'Register New Student' ?>
                    </h4>

                    <!--Alert dialog banner-->
                    <div id="js-error-alert" class="alert alert-danger align-items-center mb-3 d-none" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i> <span id="js-error-msg"></span>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?= htmlentities($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                            <i class="fas fa-check-circle me-2"></i> <?= htmlentities($message) ?>
                        </div>
                    <?php endif; ?>

                    <form action="index.php" method="POST" onsubmit="return validateBootstrapForm()" novalidate>
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="student_id" value="<?= $edit_id ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="name" class="form-label">FULL NAME</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="name" name="name" placeholder="John Doe" value="<?= htmlentities($edit_name) ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="course" class="form-label">COURSE</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-book-open"></i></span>
                                <input type="text" class="form-control" id="course" name="course" placeholder="BS Information Technology" value="<?= htmlentities($edit_course) ?>" required>
                            </div>
                        </div>

                        <?php if ($edit_mode): ?>
                            <div class="d-flex gap-2">
                                <button type="submit" name="update" class="btn-custom-primary" style="background-color: #d97706;"><i class="fas fa-save me-1"></i> Save Changes</button>
                                <a href="index.php" class="btn-custom-secondary"><i class="fas fa-times-circle me-1"></i> Cancel</a>
                            </div>
                        <?php else: ?>
                            <button type="submit" name="submit" class="btn-custom-primary"><i class="fas fa-plus-circle me-1"></i> Add Student</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!--Right Grid: Master Directory Table Display-->
            <div class="col-lg-8">
                <div class="custom-card">
                    <h4 class="card-header-title mb-4"><i class="fas fa-database text-primary me-2"></i>Registered Student Directory</h4>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 15%;">ID</th>
                                    <th scope="col" style="width: 40%;">Student Name</th>
                                    <th scope="col" style="width: 30%;">Course / Track</th>
                                    <th scope="col" class="text-center" style="width: 15%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (count($students) > 0) {
                                    // Loop through array elements in reverse to display latest entries on top
                                    foreach (array_reverse($students) as $row) {
                                        if (!isset($row['id'])) continue;
                                        echo "<tr>";
                                        echo "<td><span class='id-badge'>#" . htmlentities($row['id']) . "</span></td>";
                                        echo "<td><strong class='fw-semibold'>" . htmlentities($row['name'] ?? '') . "</strong></td>";
                                        echo "<td><span class='text-secondary'>" . htmlentities($row['course'] ?? '') . "</span></td>";
                                        echo "<td class='text-center'>
                                                <div class='d-inline-flex gap-2'>
                                                    <a href='index.php?edit=" . $row['id'] . "' class='action-btn action-btn-edit' title='Edit student'><i class='fas fa-edit'></i></a>
                                                    <a href='delete.php?id=" . $row['id'] . "' class='action-btn action-btn-delete' title='Delete student' onclick='return confirm(\"Are you sure you want to delete this student record?\");'><i class='fas fa-trash-alt'></i></a>
                                                </div>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center py-5 text-secondary'><i class='fas fa-info-circle me-2'></i>No student records found in the directory.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!--Bootstrap 5 JavaScript Bundle CDN-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!--Client-Side JavaScript Form Validation-->
    <script>
    function validateBootstrapForm() {
        const nameField = document.getElementById("name");
        const courseField = document.getElementById("course");
        const alertBox = document.getElementById("js-error-alert");
        const alertMsg = document.getElementById("js-error-msg");

        const nameValue = nameField.value.trim();
        const courseValue = courseField.value.trim();

        // 1. Check for empty inputs (Challenge requirement)
        if (nameValue === "" || courseValue === "") {
            alertMsg.textContent = "Please fill out all input fields!";
            alertBox.classList.remove("d-none");
            alertBox.classList.add("d-flex");
            return false;
        }

        // 2. Name validation: Ensure it contains only letters, spaces, or periods
        const namePattern = /^[a-zA-Z\s.]+$/;
        if (!namePattern.test(nameValue)) {
            alertMsg.textContent = "Name should only contain letters, spaces, or periods!";
            alertBox.classList.remove("d-none");
            alertBox.classList.add("d-flex");
            return false;
        }

        // 3. Length check
        if (nameValue.length < 2) {
            alertMsg.textContent = "Name is too short! It must be at least 2 characters.";
            alertBox.classList.remove("d-none");
            alertBox.classList.add("d-flex");
            return false;
        }

        alertBox.classList.remove("d-flex");
        alertBox.classList.add("d-none");
        return true;
    }
    </script>
</body>
</html>