<?php include 'db.php'; ?>


<?php

session_start();

$cardnumber = $_POST['cardnumber'];
//$expdate = $_POST['expdate'];
$cvv = $_POST['cvv'];

$regno = $_POST['regno'];
$month1 = $_POST['month1'];
$year1 = $_POST['year1'];


$sql = "INSERT INTO card (ID, CARDNUMBER, Phonenumber,month1,year1, CVV) VALUES ( '', '$cardnumber', '$regno','$month1','$year1', '$cvv')";
$result = $conn->query($sql);
if ($result){
  echo "Card added.<a href='../Dashboard1.php'>Back</a> to main page";
}
  else {
    echo "Failed to add Card <a href='../Dashboard1.php'>Return</a> to the main page";
  }

$conn->close();
?>