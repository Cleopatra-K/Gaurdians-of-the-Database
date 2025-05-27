<?php
session_start();

// Clear all session data
$_SESSION = array();

// Delete the session cookie properly
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear all authentication cookies
$authCookies = ['userApiKey', 'userId', 'userName'];
foreach ($authCookies as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time() - 3600, '/');
    }
}

// Prevent caching of the logout page
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging Out...</title>
    <script type="text/javascript">
        // Clear the browser console
        console.clear();
        console.log("Session cleared. Redirecting...");

        // Redirect after a short delay to allow console.clear() to execute
        window.location.href = "signup.php";
    </script>
</head>
<body>
    <p>You are being logged out. Please wait...</p>
</body>
</html>
<?php
exit();
?>