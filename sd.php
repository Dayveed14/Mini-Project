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
  echo "The two passwords do not match <a href='../index.html'>Return</a> to the main page";
}
else {
  $password = md5($password_1);
}


$query = "SELECT * FROM users WHERE  REGISTRATIONNUMBER = '".$regnumber."'";
$users = mysql_query($query);

while ($user = mysql_fetch_array($users)) {
  echo "user exists";
}
else{
  $query = "INSERT INTO users (ID, FIRSTNAME, LASTNAME, EMAIL, PHONENUMBER, REGISTRATIONNUMBER, ROLE, PASSWORD)
  VALUES ( '', '$firstname', '$lastname', '$email', '$number', '$regnumber', '$role', '$password')";
  $data = mysql_query($query);
}
if ($data){
  echo "User added.<a href='../login.php'>Continue</a> to login page";
}
$conn->close();
?>