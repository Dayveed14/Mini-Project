<?php include 'include/db.php'; ?>

<?php session_start(); 

if(empty($_SESSION)){

 header("location:logout.php");
} else{
?>


<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>Students</title>
  <link href="Css\style5.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,300"/>
</head>
<body>
  <div class = "head">
    <header>
      <a href = "Dashboard2.php">Home</a>
      <a  href="showstudents.php" class="current">Students</a>
      <a href = "logout.php">Log out</a>
    </header>

  </div>

  <?php
  $sql = "SELECT paid.paidid, users.FIRSTNAME, users.LASTNAME, users.REGISTRATIONNUMBER,
  paid.status, paid.phonenumber FROM users INNER JOIN paid ON users.PHONENUMBER = paid.phonenumber";
  $result = $conn->query($sql);
  if ($result->num_rows > 0) {
    echo "
    <div class = 'container'>
    <h3 align = 'center'>Paid Students</h3>
    <br />
    <div class = 'table-responsive'>
    <table id = 'students' class = table table-striped table-bordered'>
    <thead>
    <tr>
    <td>ID</td>
    <td>FIRST NAME</td>
    <td>LAST NAME</td>
    <td>REGISTRATION NUMBER</td>
    <td>STATUS</td>
    <td>PHONE NUMBER</td>
    </tr>
    </thead>";

    while($row = $result->fetch_assoc()){
      echo "
      <tr>
      <td>". $row["paidid"]. "</td>
      <td>". $row["FIRSTNAME"]. "</td>
      <td>". $row["LASTNAME"]. "</td>
      <td>". $row["REGISTRATIONNUMBER"]. "</td>
      <td>". $row["status"]. "</td>
      <td>". $row["phonenumber"]. "</td>
      </tr>
      ";
    }
    echo "</table>";
  }
  else {
    echo "0 results";
  }
  ?>
  <div class='form1'>
    <form>
      <div class='button-section'>
        <input type="button" onclick="window.print()" value="Print Receipt" />
      </div>
    </form>
  </div>

</body>
</html>

<?php } ?>