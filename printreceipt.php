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
  <link href="Css\style9.css" rel="stylesheet" />
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

 <input type="button" onclick="window.print()" value="Print Table" />
</body>
</html>

<?php } ?>