<?php
session_start();
include 'db.php'; // Make sure to include your database connection

// Check if the user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

// Get the logged-in student ID from the session
$studentId = $_SESSION['student_id'];

// Fetch sanctions for the logged-in student
$stmt = $conn->prepare("
    SELECT s.sanction_id, v.violation, v.sanction
    FROM sanctions s
    JOIN violation v ON s.violation_id = v.violation_id
    WHERE s.student_id = ?
");

$stmt->bind_param("i", $studentId);
$stmt->execute();
$sanctionResult = $stmt->get_result();

// Check for errors
if (!$sanctionResult) {
    die("Database error: " . htmlspecialchars($conn->error));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Sanctions</title>
    <link rel="stylesheet" type="text/css" href="css/navbar.css">
    <link rel="stylesheet" href="css/StudentSanctions.css">
</head>

<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <header>
        <h1>Your Sanctions</h1>
    </header>

    <table>
        <thead>
            <tr>
                <th>Violation</th>
                <th>Sanction</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($sanctionResult->num_rows > 0): ?>
                <?php while ($row = $sanctionResult->fetch_assoc()): ?>
                    <tr>
                        <td data-label="Violation"><?php echo htmlspecialchars($row['violation']); ?></td>
                        <td data-label="Sanction"><?php echo htmlspecialchars($row['sanction']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2">No sanctions found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
