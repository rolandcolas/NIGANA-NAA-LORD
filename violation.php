<?php
session_start();
include('db.php');

// Handle the form submission for adding a violation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['violation']) && isset($_POST['sanction']) && !isset($_POST['action'])) {
    $violation = $_POST['violation'];
    $sanction = $_POST['sanction'];

    // Prepare the SQL statement
    $sql = "INSERT INTO violation (violation, sanction) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die('MySQL prepare error: ' . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("ss", $violation, $sanction);

    if ($stmt->execute()) {
        echo "<p>New record created successfully</p>";
    } else {
        echo "<p>Error: " . htmlspecialchars($stmt->error) . "</p>";
    }
    $stmt->close();
}

// Handle the form submission for deleting a violation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_violation_id'])) {
    $violationId = $_POST['delete_violation_id'];

    $sql = "DELETE FROM violation WHERE violation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $violationId);

    if ($stmt->execute()) {
        echo "<p>Record deleted successfully</p>";
    } else {
        echo "<p>Error: " . htmlspecialchars($stmt->error) . "</p>";
    }
    $stmt->close();
}

// Handle the form submission for updating a violation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update' && isset($_POST['update_violation_id'])) {
    $violationId = $_POST['update_violation_id'];
    $violation = $_POST['violation'];
    $sanction = $_POST['sanction'];

    $sql = "UPDATE violation SET violation = ?, sanction = ? WHERE violation_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die('MySQL prepare error: ' . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("ssi", $violation, $sanction, $violationId);

    if ($stmt->execute()) {
        echo "<p>Record updated successfully</p>";
    } else {
        echo "<p>Error: " . htmlspecialchars($stmt->error) . "</p>";
    }
    $stmt->close();
}

// Fetch all violations
$sql = "SELECT violation_id, violation, sanction FROM violation";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Violations</title>
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 30%;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .table th {
            background-color: #f2f2f2;
        }

        .btn {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 8px 16px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 4px 2px;
            cursor: pointer;
        }

        .btn-danger {
            background-color: #f44336;
        }
    </style>
</head>
<body>
    <?php include 'AdminNavbar.php'; ?>
    <div class="container">
        <h2>Manage Violations</h2>

        <!-- Add Violation Modal -->
        <div id="addViolationModal" class="modal">
            <div class="modal-content">
                <h2>Add Violation</h2>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                    <div>
                        <label for="violation">Violation:</label>
                        <input type="text" id="violation" name="violation" required>
                    </div>
                    <div>
                        <label for="sanction">Sanction:</label>
                        <input type="text" id="sanction" name="sanction" required>
                    </div>
                    <div>
                        <button type="submit" class="btn">Save</button>
                        <button type="button" class="btn" onclick="closeModal()">Close</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Update Violation Modal -->
        <div id="updateViolationModal" class="modal">
            <div class="modal-content">
                <h2>Update Violation</h2>
                <form method="post" id="updateForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="update_violation_id" id="update_violation_id">
                    <div>
                        <label for="update_violation">Violation:</label>
                        <input type="text" id="update_violation" name="violation" required>
                    </div>
                    <div>
                        <label for="update_sanction">Sanction:</label>
                        <input type="text" id="update_sanction" name="sanction" required>
                    </div>
                    <div>
                        <button type="submit" class="btn">Update</button>
                        <button type="button" class="btn" onclick="closeUpdateModal()">Close</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Violation Table -->
        <table class="table">
            <thead>
                <tr>
                    <th>Violation</th>
                    <th>Sanction</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['violation']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['sanction']) . "</td>";
                        echo "<td>
                                <form method='post' style='display:inline;'>
                                    <input type='hidden' name='delete_violation_id' value='" . htmlspecialchars($row['violation_id']) . "'>
                                    <button type='submit' class='btn btn-danger'>Delete</button>
                                </form>
                                <button class='btn' onclick='openUpdateModal(" . htmlspecialchars($row['violation_id']) . ", `" . htmlspecialchars($row['violation']) . "`, `" . htmlspecialchars($row['sanction']) . "`)'>Update</button>
                            </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>No violations found</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <button type="button" class="btn" onclick="openModal()">Add Violation</button>
    </div>

    <script>
        function openModal() {
            document.getElementById("addViolationModal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("addViolationModal").style.display = "none";
        }

        function openUpdateModal(id, violation, sanction) {
            document.getElementById("update_violation_id").value = id;
            document.getElementById("update_violation").value = violation;
            document.getElementById("update_sanction").value = sanction;
            document.getElementById("updateViolationModal").style.display = "block";
        }

        function closeUpdateModal() {
            document.getElementById("updateViolationModal").style.display = "none";
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>
