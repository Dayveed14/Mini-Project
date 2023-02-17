<?php include 'db.php'; ?>


<?php

session_start();

$firstname = $_POST['FName'];
$lastname = $_POST['LName'];
$email = $_POST['mail'];
$number = $_POST['Number'];
$regnumber = $_POST['Regnum'];
$role = $_POST['role'];
$password_1 = $_POST['Password_1'];
$password_2 = $_POST['Password_2'];


if ($password_1 != $password_2) {
  die("The two passwords do not match <a href='../index.html'>Return</a> to the main page");
} else {
  $password = md5($password_1);
}


$sql = "SELECT * FROM users WHERE  REGISTRATIONNUMBER = '" . $regnumber . "'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
  echo "User exists.<a href='../index.PHP'>Return</a> to the main page ";
} else {
  $sql = "INSERT INTO users (ID, FIRSTNAME, LASTNAME, EMAIL, PHONENUMBER, REGISTRATIONNUMBER, ROLE, PASSWORD)
  VALUES ( '', '$firstname', '$lastname', '$email', '$number', '$regnumber', '$role', '$password')";
  $result = $conn->query($sql);
  if ($result) {
    echo "User added.<a href='../login.php'>Continue</a> to login page";
  } else {
    echo "Failed to add new user <a href='../index.PHP'>Return</a> to the main page";
  }
}
$conn->close();
?>