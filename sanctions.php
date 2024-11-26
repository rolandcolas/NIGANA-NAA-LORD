<?php
session_start();
include 'db.php'; // Include your database connection

// Check if the user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

// Get the logged-in student ID from the session
$studentId = $_SESSION['student_id'];

// Fetch all sanctions for all students
$stmtSanctions = $conn->prepare("
    SELECT s.sanction_id, s.student_id, st.firstname, st.lastname, v.violation, v.sanction
    FROM sanctions s
    JOIN violation v ON s.violation_id = v.violation_id
    JOIN students st ON s.student_id = st.student_id
");
$stmtSanctions->execute();
$sanctionResult = $stmtSanctions->get_result();

// Handle the "Done" action
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['sanction_id']) && isset($_POST['action'])) {
        $sanctionId = $_POST['sanction_id'];
        $action = $_POST['action'];

        if ($action == 'done') {
            // Check if the sanction exists before adding to sanction_completed
            $stmtCheck = $conn->prepare("SELECT sanction_id FROM sanctions WHERE sanction_id = ?");
            $stmtCheck->bind_param("i", $sanctionId);
            $stmtCheck->execute();
            $checkResult = $stmtCheck->get_result();

            if ($checkResult->num_rows > 0) {
                // Insert into sanction_completed table
                $stmtComplete = $conn->prepare("
                    INSERT INTO sanction_completed (student_id, sanction_id)
                    VALUES (?, ?)
                ");
                $stmtComplete->bind_param("ii", $studentId, $sanctionId);
                if ($stmtComplete->execute()) {
                    // If insertion is successful, delete from sanctions
                    $stmtDelete = $conn->prepare("
                        DELETE FROM sanctions
                        WHERE sanction_id = ?
                    ");
                    $stmtDelete->bind_param("i", $sanctionId);
                    if ($stmtDelete->execute()) {
                        echo "Sanction marked as completed and deleted from the list.";
                    } else {
                        echo "Error deleting sanction: " . htmlspecialchars($stmtDelete->error);
                    }
                    $stmtDelete->close();
                } else {
                    echo "Error completing sanction: " . htmlspecialchars($stmtComplete->error);
                }
                $stmtComplete->close();
            } else {
                echo "Sanction does not exist.";
            }
            $stmtCheck->close();
        } elseif ($action == 'delete') {
            // Delete from sanctions table
            $stmtDelete = $conn->prepare("
                DELETE FROM sanctions
                WHERE sanction_id = ?
            ");
            $stmtDelete->bind_param("i", $sanctionId);
            if ($stmtDelete->execute()) {
                echo "Sanction deleted.";
            } else {
                echo "Error deleting sanction: " . htmlspecialchars($stmtDelete->error);
            }
            $stmtDelete->close();
        }
    }
}

// Close the statement for fetching sanctions
$stmtSanctions->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   
    <link rel="stylesheet" href="css/AdminNavbar.css">
    <link rel="stylesheet" href="css/adminsanction.css">
    <title>All Students' Sanctions</title>
    <style>
        /* Add your custom styles here 
        .container {
            width: 90%;
            margin: auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .table-container {
            overflow-x: auto;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
        } */

    </style>
</head>
<body>
    <?php include 'AdminNavbar.php'; ?>

    <div class="container">
        <div class="header">
            <h1>All Students' Sanctions</h1>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Violation</th>
                        <th>Sanction</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($sanctionResult->num_rows > 0): ?>
                        <?php while ($row = $sanctionResult->fetch_assoc()): ?>
                            <tr>
                                <td data-label="Student ID"><?php echo htmlspecialchars($row['student_id']); ?></td>
                                <td data-label="First Name"><?php echo htmlspecialchars($row['firstname']); ?></td>
                                <td data-label="Last Name"><?php echo htmlspecialchars($row['lastname']); ?></td>
                                <td data-label="Violation"><?php echo htmlspecialchars($row['violation']); ?></td>
                                <td data-label="Sanction"><?php echo htmlspecialchars($row['sanction']); ?></td>
                                <td data-label="Action">
                                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                                        <input type="hidden" name="sanction_id" value="<?php echo $row['sanction_id']; ?>">
                                        <button type="submit" name="action" value="done" class="btn btn-success">Done</button>
                                        <button type="submit" name="action" value="delete" class="btn btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No sanctions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>