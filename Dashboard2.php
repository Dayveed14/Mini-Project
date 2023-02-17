<?php include 'include/db.php'; ?>
<?php session_start();

if (empty($_SESSION)) {

  header("location:logout.php");
} else {
  ?>



  <!DOCTYPE html>
  <html>

  <head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,300" />
    <link href="Css/style3.css" rel="stylesheet" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
    <script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css" />
  </head>

  <body>
    <div class="head">
      <header>

        <img src="img/logo.png">

        Welcome, Admin
        <?php echo $_SESSION["FName"]; ?>
        <a href="logout.php">Log out </a>
      </header>
    </div>


    <div id="form-main">
      <div id="form-div">
        <form class="form" id="form1">
          <div class="submit" id="button-blue">
            <a href="checkrecord.php"> Check Payment Records </a>
          </div>
          <div class="submit" id="button-blue">
            <a href="changepassword2.php"> Change Password </a>
          </div>
          <div class="submit" id="button-blue">
            <a href="showstudents.php"> Show all Students </a>
          </div>
        </form>
      </div>
    </div>
  </body>
<?php } ?>