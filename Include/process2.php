<?php include 'db.php'; ?>


<?php

$username = $_POST['username'];
$password = $_POST['password'];


$sql = "SELECT * FROM users WHERE REGISTRATIONNUMBER = '" . $username . "' AND PASSWORD = '" . MD5($password) . "' limit 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    session_start();
    $_SESSION["FName"] = $row["FIRSTNAME"];
    $_SESSION["LName"] = $row["LASTNAME"];
    $_SESSION["email"] = $row["EMAIL"];
    $_SESSION["Regnumb"] = $row["REGISTRATIONNUMBER"];
    $_SESSION["role"] = $row["ROLE"];
    $_SESSION["number"] = $row["PHONENUMBER"];
    $_SESSION["password"] = $row["PASSWORD"];
    $_SESSION["id"] = $row["ID"];

    if ($row['ROLE'] == '1') {
      header("location:../dashboard1.php");
    } elseif ($row['ROLE'] == '2') {
      header("location:../dashboard2.php");
    }
  }
} else {
  echo 'Username and password is not correct';
}
$conn->close();
?>