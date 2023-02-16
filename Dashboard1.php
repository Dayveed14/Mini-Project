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
    <title>User Dashboard</title>
    <link href="Css\style3.css" rel="stylesheet" />
    <link href="Css\style6.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,300"/>
  </head>

  <body>
    <div class = "head">
      <header>

        <img src="img/logo.png">
        
        Welcome, <?php echo $_SESSION["Regnumb"]; ?>
        <a href = "logout.php">Log out </a>
      </header>
    </div>


    <div id="form-main">
      <div id="form-div">
        <form class="form" id="form1">
          <div class="submit" id="button-blue">
            <a href="card.PHP"> Add card details </a>
          </div>
          <div class="submit" id="button-blue">
           <a href="changepassword.PHP"> Change Password </a>
          </div>
        </form>
      </div>
    </div>

  </body>
</html>

<?php } ?>