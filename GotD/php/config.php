<?php

//i need to connect my data base ehhh
//do i download phpadmin so i can acess the databse??

// // Database connection settings
//if ($_SERVER['HTTP_HOST'] == 'localhost') {
//     // Local settings
    $hostname = "localhost";
    $username = "root";
    $password = "";
    //$username = "u23547121";
    //$password = "Q4UWHRzbV7jqkzNm";
    $database = "u23547121_GotD";
// } else {
//     //Wheatley settings
    // $hostname = "wheatley.cs.up.ac.za";
    //  $username = "u23547121";
    //  $password = "Q4UWHRzbV7jqkzNm";
// }

//we lost all hop things arent connecting
//try{
// Creates connection
$mysqli = new mysqli($hostname, $username, $password, $database);

// Checks connection
if ($mysqli->connect_error) {
//    var_dump($mysqli->connect_error); 
    die("Connection failed: " . $mysqli->connect_error);
}
error_log("Connected to database: " . $mysqli->query("SELECT DATABASE()")->fetch_row()[0]);
error_log("Tables in database: " . print_r($mysqli->query("SHOW TABLES")->fetch_all(), true));
// }else{
//   //  var_dump($mysqli); 
//     echo "Connected successfully".$database;
// }

// Start session (for user login status)
//Global var
//my sites name
$siteName= "parcellas.com";

//k1RAH@omn!33
//l0veYOU@300
//heartS4r@r@
//b0b@Marl3y
global $mysqli;
?>