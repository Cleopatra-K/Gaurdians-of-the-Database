<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="WheelDeal: Your trusted platform for buying and selling vehicles.">
    <meta name="keywords" content="WheelDeal, cars, vehicles, buy, sell, automotive, marketplace">
    <title>WheelDeal - Sign Up</title>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../css/login.css">
    
    <style>
        .hidden {
            display: none;
        }
    </style>
</head>

<body>
<div class="page-container">
    <div class="welcome container">
        <div class="img-title">
            <div id="logo-img">
                <img src="../img/logo3.png" alt="Wheel Deal logo">
            </div>
            <div class="button">
                <p>Welcome to WheelDeal. Please sign in or create an account.</p>
                <button onclick="showForm('login')" class="btn-style">Login</button>
                <button onclick="showForm('signup')" class="btn-style">Create Account</button>
            </div>
            <div class="slogan">
                <p>Get the best prices on your next ride.</p>
            </div>
        </div>

        <div id="form-container">
            <form id="login-form" class="hidden" onsubmit="return false;">
                <h2>Login</h2>
                <input type="text" id="login-username" name="login-username" placeholder="Username" required>            
                <input type="password" id="login-password" name="login-password" placeholder="Password" required>
                <button type="submit" class="btn-style" >Login</button>
                <p>Don't have an account? <a href="#" onclick="showForm('signup')">Sign Up</a></p>
                <div id="login-error-messages" style="color: red; margin-top: 10px;"></div>
            </form>

            <form id="signup-form" class="hidden" onsubmit="return false;">
                <h2>Create Account</h2>

                <input type="text" id="username" name="username" placeholder="Username" required>
                <input type="text" id="name" name="name" placeholder="First Name" required>
                <input type="email" id="email" name="email" placeholder="Email" required>
                <input type="text" id="phone_num" name="phone_num" placeholder="Phone Number" required>
                <input type="password" id="password" name="password" placeholder="Password" required>

                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="Customer">Customer</option>
                    <option value="Seller">Seller</option>
                    <option value="Admin">Admin</option>
                </select>

                <div id="customer-fields" class="hidden">
                    <input type="text" name="surname" placeholder="Surname" required>
                </div>

                <div id="seller-fields" class="hidden">
                    <input type="text" name="address" placeholder="Address" required>
                    <input type="url" name="website" placeholder="Website (e.g., https://www.example.com)" required>
                    <input type="text" name="business_reg_num" placeholder="Business Registration Number" required>
                </div>

                <div id="admin-fields" class="hidden">
                    <input type="text" name="access_level" placeholder="Access Level" required>
                </div>

                <button type="submit" class="btn-style" >Sign Up</button>

                <p>Already have an account? <a href="#" onclick="showForm('login')">Login</a></p>

                <div id="error-messages"></div> </form>
        </div>
    </div>
</div>

<script src="../js/chooserOfForm.js"></script>  
<script src="../js/loginValidation.js"></script> 
<script src="../js/signup.js"></script>          
</body>
</html>
<?php
 include 'footer.php'; // If you have a footer, include it here
?>