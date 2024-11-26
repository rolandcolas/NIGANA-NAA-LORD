<!-- AdminNavbar.php -->
<nav class="admin-navbar">
    <div class="nav-container">
        <div class="nav-logos">
            <img src="../images/logo.png" alt="Logo 1" style="height: 80px;">
            <img src="../images/txt.png" alt="Logo 2" style="height: 80px;">
        </div>
        <ul class="nav-links">
            <li><a href="admin.php">Home</a></li>
            <li>
                <a href="sanctions.php">Sanction</a>
                <button class="dropdown-toggle">▼</button>
                <div class="dropdown">
                    <ul class="dropdown-content">
                        <li><a href="violation.php">Manage Violations</a></li>
                    </ul>
                </div>
            </li>
            <li><a href="home.php">Logout</a></li>
        </ul>
    </div>
</nav>

<style>
    /* Dropdown styling */
    .nav-links {
        position: relative;
        display: flex;
        align-items: center;
    }

    .dropdown {
        display: none; /* Hidden by default */
        position: absolute;
        background-color: white;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        z-index: 1;
    }

    .dropdown-content {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .dropdown-content li {
        padding: 8px 16px;
    }

    .dropdown-content li a {
        text-decoration: none;
        color: black;
    }

    .dropdown-content li a:hover {
        background-color: #f1f1f1;
    }

    /* Show dropdown when active */
    .dropdown.active {
        display: block;
    }

    .dropdown-toggle {
        background: none;
        border: none;
        cursor: pointer;
        margin-left: 5px;
        font-size: 16px; /* Adjust size as needed */
    }
</style>

<script>
    // Toggle dropdown visibility
    document.querySelector('.dropdown-toggle').addEventListener('click', function() {
        const dropdown = this.nextElementSibling;
        dropdown.classList.toggle('active');
    });

    // Close dropdown if clicked outside
    window.addEventListener('click', function(event) {
        const dropdown = document.querySelector('.dropdown');
        if (!event.target.matches('.dropdown-toggle') && dropdown.classList.contains('active')) {
            dropdown.classList.remove('active');
        }
    });
</script>