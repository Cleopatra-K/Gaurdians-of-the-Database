<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: signup.php");
    exit;
}

$page_title = "My Favourites";
$pageCSS = "../css/favourites.css";
include 'header.php';  // Shared header
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo $pageCSS; ?>">
</head>
<body>

<main>
  <header>
    <h1>Favourite Tyres</h1>
  </header>

  <table id="favouritesTable">
    <thead>
      <tr>
        <th>Image</th>
        <th>Tyre ID</th>
        <th>Size</th>
        <th>Load Index</th>
        <th>Has Tube</th>
        <th>Serial #</th>
        <th>Price (ZAR)</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody id="favouritesBody">
      <!-- Populated by JS -->
    </tbody>
  </table>
</main>

<script src="../js/favourites.js"></script>

<?php include 'footer.php'; ?>
</body>
</html>
