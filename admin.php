<?php
    session_start();

    // Check if the user is an admin
    if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
        header("Location: home.php"); // Redirect if not an admin
        exit();
    }

    include 'db.php';

    date_default_timezone_set('Asia/Manila'); // Set your timezone

    // Function to handle database errors
    function handleDbError($stmt) {
        if ($stmt->error) {
            die("Database error: " . htmlspecialchars($stmt->error));
        }
    }

    // Handle adding new item
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item'])) {
        $itemType = $_POST['itemtype'];
        $itemName = $_POST['itemname'];

        // Insert new item into the database
        $stmt = $conn->prepare("INSERT INTO items (itemtype, itemname) VALUES (?, ?)");
        $stmt->bind_param("ss", $itemType, $itemName);
        $stmt->execute();
        handleDbError($stmt);
        $stmt->close();
    }

    // Handle removing item
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_item'])) {
        $itemId = $_POST['item_id'];

        // Delete the item from the database
        $stmt = $conn->prepare("DELETE FROM items WHERE item_id = ?");
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        handleDbError($stmt);
        $stmt->close();
    }

    // Handle reservation actions (accept, reject, cancel)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
        $reservationId = $_POST['reservation_id'];
        $action = $_POST['action'];

        // Update the status based on the action
        $status = ($action == 'accept') ? 'accepted' : (($action == 'reject') ? 'rejected' : 'pending');

        // Update reservation status in the database
        $stmt = $conn->prepare("UPDATE prereservation SET status = ? WHERE reservation_id = ?");
        $stmt->bind_param("si", $status, $reservationId);
        $stmt->execute();
        handleDbError($stmt);
        $stmt->close();
    }

    // Handle borrowing actions (accept, reject)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['borrow_action'])) {
        $borrowingId = $_POST['borrowing_id'];
        $action = $_POST['borrow_action'];

        // Update the status based on the action
        $status = ($action == 'accept') ? 'approved' : 'rejected';

        // Update borrowing status in the database
        $stmt = $conn->prepare("UPDATE borrowing SET status = ? WHERE borrowing_id = ?");
        $stmt->bind_param("si", $status, $borrowingId);
        $stmt->execute();
        handleDbError($stmt);
        $stmt->close();
    }
    // Handle adding a sanction
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['violation_id'])) {
        $studentId = null;

        // Check if the sanction is for a reservation or a borrowing
        if (!empty($_POST['sanction_type']) && $_POST['sanction_type'] == 'reservation') {
            $reservationId = $_POST['sanction_id'];

            // Fetch the student_id from the reservation
            $stmt = $conn->prepare("SELECT student_id FROM prereservation WHERE reservation_id = ?");
            $stmt->bind_param("i", $reservationId);
            $stmt->execute();
            handleDbError($stmt);
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $studentId = $row['student_id'];
            $stmt->close();
        } elseif (!empty($_POST['sanction_type']) && $_POST['sanction_type'] == 'borrowing') {
            $borrowingId = $_POST['sanction_id'];

            // Fetch the student_id from the borrowing
            $stmt = $conn->prepare("SELECT student_id FROM borrowing WHERE borrowing_id = ?");
            $stmt->bind_param("i", $borrowingId);
            $stmt->execute();
            handleDbError($stmt);
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $studentId = $row['student_id'];
            $stmt->close();
        }

        // Insert the sanction into the database
        if ($studentId !== null) {
            $violationId = $_POST['violation_id'];

            $stmt = $conn->prepare("INSERT INTO sanctions (student_id, violation_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $studentId, $violationId);
            $stmt->execute();
            handleDbError($stmt);
            $stmt->close();

            $_SESSION['message'] = "Sanction added successfully.";
        } else {
            $_SESSION['message'] = "Failed to add sanction: Student ID not found.";
        }
    }

//for modal borrowing
    $borrowAction = $_POST['borrow_action'] ?? null; // Check if 'borrow_action' exists in the POST request

if ($borrowAction === 'reject') {
    $borrowingId = $_POST['borrowing_id'] ?? null; // ID of the borrowing
    $reason = $_POST['rejection_reason'] ?? 'No reason provided';
    $rejectionDatetime = date('Y-m-d H:i:s'); // Current datetime for the rejection

    if ($borrowingId) {
        // Insert into rejected_borrowings table
        $stmt = $conn->prepare(
            "INSERT INTO rejected_borrowings (borrowing_id, reject_reason, rejection_datetime) VALUES (?, ?, ?)"
        );
        if (!$stmt) {
            die("Query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("iss", $borrowingId, $reason, $rejectionDatetime);
        $stmt->execute();

        if ($stmt->error) {
            die("Insert into rejected_borrowings failed: " . $stmt->error);
        }
        $stmt->close();

        // Update the borrowing table to mark as rejected
        $status = 'rejected';
        $stmt = $conn->prepare("UPDATE borrowing SET status = ? WHERE borrowing_id = ?");
        if (!$stmt) {
            die("Query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("si", $status, $borrowingId);
        $stmt->execute();

        if ($stmt->error) {
            die("Update borrowing table failed: " . $stmt->error);
        }
        $stmt->close();

        echo "Borrowing rejected successfully.";
    } else {
        echo "Missing borrowing ID.";
    }
}

//for modal pending reservations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reservation_action']) && $_POST['reservation_action'] === 'reject') {
        $reservationId = $_POST['reservation_id'];
        $rejectionReason = $_POST['rejection_reason'];
        $rejectionDateTime = date("Y-m-d H:i:s"); // Current datetime

        // Insert rejected reservation into rejected_reservations table
        $stmt = $conn->prepare("INSERT INTO rejected_reservations (reservation_id, reject_reason, rejection_datetime) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $reservationId, $rejectionReason, $rejectionDateTime);
        if (!$stmt->execute()) {
            die("Error inserting rejection: " . $stmt->error);
        }
        $stmt->close();

        // Update the status in the prereservation table
        $stmt = $conn->prepare("UPDATE prereservation SET status = 'rejected' WHERE reservation_id = ?");
        $stmt->bind_param("i", $reservationId);
        if (!$stmt->execute()) {
            die("Error updating reservation status: " . $stmt->error);
        }
        $stmt->close();

        echo "Reservation rejected successfully!";
    } elseif (isset($_POST['reservation_action']) && $_POST['reservation_action'] === 'accept') {
        $reservationId = $_POST['reservation_id'];

        // Update the status in the prereservation table
        $stmt = $conn->prepare("UPDATE prereservation SET status = 'accepted' WHERE reservation_id = ?");
        $stmt->bind_param("i", $reservationId);
        if (!$stmt->execute()) {
            die("Error updating reservation status: " . $stmt->error);
        }
        $stmt->close();

        echo "Reservation accepted successfully!";
    }
}

    
    // Fetch all items from the database
    $stmt = $conn->prepare("SELECT * FROM items");
    $stmt->execute();
    handleDbError($stmt);
    $itemsResult = $stmt->get_result();

    // Fetch borrowing requests
    $stmt = $conn->prepare("SELECT * FROM borrowing");
    $stmt->execute();
    handleDbError($stmt);
    $borrowingResult = $stmt->get_result();

    // Fetch all violations for the dropdown
    $violationStmt = $conn->prepare("SELECT violation_id, violation FROM violation");
    $violationStmt->execute();
    handleDbError($violationStmt);
    $violationResult = $violationStmt->get_result();

    // Check for success message for sanctions
    if (isset($_SESSION['message'])) {
        echo "<script>alert('" . htmlspecialchars($_SESSION['message']) . "');</script>";
        unset($_SESSION['message']);
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/admin.css">
        <link rel="stylesheet" href="css/AdminNavbar.css">
        <title>Admin Dashboard</title>
        
    </head>
    <body>

    <?php include 'AdminNavbar.php'; ?>

    <h1>Admin Dashboard</h1>

    <!-- Items List -->
    <h2>Items List</h2>
    <ul>
        <?php while ($row = $itemsResult->fetch_assoc()): ?>
            <li>
                <?php echo htmlspecialchars("{$row['itemname']} ({$row['itemtype']})"); ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($row['item_id']); ?>">
                    <button type="submit" name="remove_item">Remove</button>
                </form>
            </li>
        <?php endwhile; ?>
    </ul>

    <!-- Add New Item Form -->
    <h2>Add New Item</h2>
    <form method="post">
        <label for="itemtype">Item Type:</label>
        <select id="itemtype" name="itemtype" required>
            <option value="item">Item</option>
            <option value="book">Book</option>
        </select><br>

        <label for="itemname">Item Name:</label>
        <input type="text" id="itemname" name="itemname" required><br>

        <button type="submit" name="add_item">Add Item</button>
    </form>

    <!-- Pending Reservations -->
<h2>Pending Reservations</h2>
<ul>
    <?php
    $stmt = $conn->prepare("SELECT reservation_id, reservation_name, reservation_datetime, reservation_end_datetime FROM prereservation WHERE status = 'pending'");
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    $stmt->execute();
    $pendingReservations = $stmt->get_result();
    if (!$pendingReservations) {
        die("Query execution failed: " . $conn->error);
    }
    while ($row = $pendingReservations->fetch_assoc()) {
        echo "<li>Reservation: " . htmlspecialchars($row['reservation_name']) . 
             " from " . htmlspecialchars($row['reservation_datetime']) . 
             " to " . htmlspecialchars($row['reservation_end_datetime']) . " - ";
        echo "<form method='post' style='display:inline;' action='admin.php'>
                <input type='hidden' name='reservation_id' value='" . htmlspecialchars($row['reservation_id']) . "'>
                <button type='submit' name='reservation_action' value='accept'>Accept</button>
                <button type='button' class='reject-res-btn' data-id='" . htmlspecialchars($row['reservation_id']) . "'>Reject with Reason</button>
              </form>";
        echo "</li>";
    }
    $stmt->close();
    ?>
</ul>

<!-- Reason for Rejection Modal -->
<div id="reservationRejectionModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Provide Reason for Rejection</h2>
        <form id="reservationRejectionForm" method="post" action="admin.php">
            <input type="hidden" name="reservation_id" id="rejectionReservationId">
            <input type="hidden" name="reservation_action" value="reject">
            <label for="reservationRejectionReason">Reason:</label>
            <textarea id="reservationRejectionReason" name="rejection_reason" rows="4" required></textarea><br>
            <button type="submit">Submit</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Open the modal
        document.querySelectorAll('.reject-res-btn').forEach(button => {
            button.addEventListener('click', function () {
                const reservationId = this.getAttribute('data-id');
                document.getElementById('rejectionReservationId').value = reservationId;
                document.getElementById('reservationRejectionModal').style.display = 'flex';
            });
        });

        // Close the modal
        document.querySelector('.modal .close').addEventListener('click', function () {
            document.getElementById('reservationRejectionModal').style.display = 'none';
        });

        // Close modal if clicking outside the content
        window.addEventListener('click', function (event) {
            if (event.target === document.getElementById('reservationRejectionModal')) {
                document.getElementById('reservationRejectionModal').style.display = 'none';
            }
        });
    });
</script>


    <!-- Accepted Reservations -->
    <h2>Accepted Reservations</h2>
    <ul>
        <?php
        $stmt = $conn->prepare("SELECT * FROM prereservation WHERE status = 'accepted'");
        $stmt->execute();
        handleDbError($stmt);
        $acceptedReservations = $stmt->get_result();
        while ($row = $acceptedReservations->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($row['reservation_name']) . " from " . htmlspecialchars($row['reservation_datetime']) . " to " . htmlspecialchars($row['reservation_end_datetime']) . " - ";
            echo "<form method='post' style='display:inline;'>
                    <input type='hidden' name='reservation_id' value='" . htmlspecialchars($row['reservation_id']) . "'>
                    <button type='submit' name='action' value='cancel'>Cancel</button>
                </form>";
            
            // Add Sanction Button
            echo "<button class='sanction-btn' data-id='" . htmlspecialchars($row['reservation_id']) . "' data-type='reservation'>Add Sanction</button>";
            echo "</li>";
        }
        $stmt->close();
        ?>
    </ul>


    <!-- Rejected Reservations -->
<h2>Rejected Reservations</h2>
<ul>
    <?php
    $stmt = $conn->prepare("SELECT * FROM prereservation WHERE status = 'rejected'");
    $stmt->execute();
    handleDbError($stmt);
    $rejectedReservations = $stmt->get_result();
    while ($row = $rejectedReservations->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['reservation_name']) . " from " . htmlspecialchars($row['reservation_datetime']) . " to " . htmlspecialchars($row['reservation_end_datetime']) . " - ";
        echo "<form method='post' style='display:inline;'>
                <input type='hidden' name='reservation_id' value='" . htmlspecialchars($row['reservation_id']) . "'>
                <button type='submit' name='action' value='accept'>Accept</button>
              </form>";
        echo "</li>";
    }
    $stmt->close();
    ?>
</ul>



<!-- Pending Borrowings -->
<h2>Pending Borrowings</h2>
<ul>
    <?php
    $stmt = $conn->prepare("SELECT borrowing_id, item_id, student_id, borrow_datetime, end_borrow_datetime FROM borrowing WHERE status = 'pending'");

    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    $stmt->execute();
    $pendingBorrowings = $stmt->get_result();
    if (!$pendingBorrowings) {
        die("Query execution failed: " . $conn->error);
    }
    while ($row = $pendingBorrowings->fetch_assoc()) {
        echo "<li>Item ID: " . htmlspecialchars($row['item_id']) . 
             " by Student ID: " . htmlspecialchars($row['student_id']) . 
             " from " . htmlspecialchars($row['borrow_datetime']) . 
             " to " . htmlspecialchars($row['end_borrow_datetime']) . " - ";
        echo "<form method='post' style='display:inline;' action='admin.php'>
                <input type='hidden' name='borrowing_id' value='" . htmlspecialchars($row['borrowing_id']) . "'>
                <button type='submit' name='borrow_action' value='accept'>Accept</button>
                <button type='button' class='reject-btn' data-id='" . htmlspecialchars($row['borrowing_id']) . "'>Reject with Reason</button>
              </form>";
        echo "</li>";
    }
    $stmt->close();
    ?>
</ul>

<!-- Reason for Rejection Modal -->
<div id="rejectionModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Provide Reason for Rejection</h2>
        <form id="rejectionForm" method="post" action="admin.php">
            <input type="hidden" name="borrowing_id" id="rejectionBorrowingId">
            <input type="hidden" name="borrow_action" value="reject">
            <label for="rejectionReason">Reason:</label>
            <textarea id="rejectionReason" name="rejection_reason" rows="4" required></textarea><br>
            <button type="submit">Submit</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Open the modal
        document.querySelectorAll('.reject-btn').forEach(button => {
            button.addEventListener('click', function () {
                const borrowingId = this.getAttribute('data-id');
                document.getElementById('rejectionBorrowingId').value = borrowingId;
                document.getElementById('rejectionModal').style.display = 'flex';
            });
        });

        // Close the modal
        document.querySelector('.modal .close').addEventListener('click', function () {
            document.getElementById('rejectionModal').style.display = 'none';
        });

        // Close modal if clicking outside the content
        window.addEventListener('click', function (event) {
            if (event.target === document.getElementById('rejectionModal')) {
                document.getElementById('rejectionModal').style.display = 'none';
            }
        });
    });
</script>




    <!-- Accepted Borrowings -->
    <h2>Accepted Borrowings</h2>
    <ul>
        <?php
        $stmt = $conn->prepare("SELECT * FROM borrowing WHERE status = 'approved'");
        $stmt->execute();
        handleDbError($stmt);
        $approvedBorrowings = $stmt->get_result();
        while ($row = $approvedBorrowings->fetch_assoc()) {
            echo "<li>Item ID: " . htmlspecialchars($row['item_id']) . " by Student ID: " . htmlspecialchars($row['student_id']) . " from " . htmlspecialchars($row['borrow_datetime']) . " to " . htmlspecialchars($row['end_borrow_datetime']) . " - ";
            echo "<form method='post' style='display:inline;'>
                    <input type='hidden' name='borrowing_id' value='" . htmlspecialchars($row['borrowing_id']) . "'>
                    <button type='submit' name='borrow_action' value='reject'>Reject</button>
                </form>";

            // Add Sanction Button
            echo "<button class='sanction-btn' data-id='" . htmlspecialchars($row['borrowing_id']) . "' data-type='borrowing'>Add Sanction</button>";
            echo "</li>";
        }
        $stmt->close();
        ?>
    </ul>

    <!-- Modal for Adding Sanction -->
    <div id="sanctionModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add Sanction</h2>
            <form id="sanctionForm" method="post">
                <input type="hidden" id="sanction_id" name="sanction_id">
                <input type="hidden" id="sanction_type" name="sanction_type">

                <label for="violation">Violation:</label><br>
                <select id="violation" name="violation_id" required>
                    <option value="">Select a violation</option>
                    <?php while ($violationRow = $violationResult->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($violationRow['violation_id']); ?>">
                            <?php echo htmlspecialchars($violationRow['violation']); ?>
                        </option>
                    <?php endwhile; ?>
                </select><br><br>

                <button type="submit">Submit</button>
            </form>
        </div>
    </div>

    <script>
    // Get the modal and close button
    const modal = document.getElementById('sanctionModal');
    const closeModal = document.querySelector('.close');

    // Show modal when a "Sanction" button is clicked
    document.querySelectorAll('.sanction-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const type = this.getAttribute('data-type');
            document.getElementById('sanction_id').value = id;
            document.getElementById('sanction_type').value = type;
            modal.style.display = 'flex';
        });
    });

    // Close the modal when the 'X' is clicked
    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    // Close the modal when clicked outside
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
    </script>

    <!-- Rejected Borrowings -->
    <h2>Rejected Borrowings</h2>
    <ul>
        <?php
        $stmt = $conn->prepare("SELECT * FROM borrowing WHERE status = 'rejected'");
        $stmt->execute();
        handleDbError($stmt);
        $rejectedBorrowings = $stmt->get_result();
        while ($row = $rejectedBorrowings->fetch_assoc()) {
            echo "<li>Item ID: " . htmlspecialchars($row['item_id']) . " by Student ID: " . htmlspecialchars($row['student_id']) . " from " . htmlspecialchars($row['borrow_datetime']) . " to " . htmlspecialchars($row['end_borrow_datetime']) . " - ";
            echo "<form method='post' style='display:inline;'>
                    <input type='hidden' name='borrowing_id' value='" . htmlspecialchars($row['borrowing_id']) . "'>
                    <button type='submit' name='borrow_action' value='accept'>Accept</button>
                </form>";
            echo "</li>";
        }
        $stmt->close();
        ?>
    </ul>

    </body>
    </html>

    <?php
    $conn->close();
    ?>