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
      <a class="current">Students</a>
      <a href = "logout.php">Log out</a>
    </header>

    </div>
    

    <?php
    $sql = "SELECT * FROM users WHERE ROLE = '1'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
      echo "<div class = 'contain'><table class= 'center' ><tr><th>ID</th><th>FIRST NAME</th><th>LAST NAME</th><th>REGISTRATION NUMBER</th><th>EMAIL</th><th>PHONE NUMBER</th></tr>";
      while($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row["ID"]. "</td><td>" . $row["FIRSTNAME"]. "</td><td>" . $row["LASTNAME"] . "</td><td>" . $row["REGISTRATIONNUMBER"]. "</td><td>" . $row["EMAIL"]. "</td><td> " . $row["PHONENUMBER"]. "</td></tr>";
      }
      echo "</table>";
    } else {
      echo "0 results";
    }
    
    ?>

  </body>
</html>

<?php } ?>