<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seller Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            padding: 20px;
            text-align: center;
        }
        .dashboard {
            background-color: #fff;
            border: 1px solid #ccc;
            padding: 30px;
            border-radius: 10px;
            display: inline-block;
        }
        .logout-btn {
            background-color: #d9534f;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 20px;
        }
        .logout-btn:hover {
            background-color: #c9302c;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1>Welcome, <span id="seller-name">Seller</span>!</h1>
        <p>This is your dashboard. You can manage products, view sales, and more.</p>
        <button class="logout-btn" onclick="logout()">Logout</button>
    </div>

    <script>
        // Set name from localStorage
        const name = localStorage.getItem('userName') || 'Seller';
        document.getElementById('seller-name').textContent = name;

        function logout() {
            // Clear stored data
            localStorage.clear();
            // Redirect to login page
            window.location.href = 'signup.php'; // Adjust path if needed
        }
    </script>
</body>
</html>
