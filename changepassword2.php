<?php

session_start();
$user = $_SESSION["FName"];
$password = md5($_SESSION["password"]);
if ($user) {
    if (isset($_POST['submit'])){

    	$oldpassword = md5($_POST['oldpassword']);
		$newpassword = md5($_POST['newpassword']);
		$repeatnewpassword = md5($_POST['repeatnewpassword']);

       include ('Include/db.php');

        $sql = "SELECT PASSWORD FROM users WHERE USERNAME = '$user' limit 1";
		$result = $conn->query($sql);

		$oldpassword = $password;

		if ($oldpassword==$password){

			if ($newpassword==$repeatnewpassword){

				$sql = "UPDATE users SET PASSWORD = '$newpassword' WHERE FIRSTNAME = '$user'";
				$result = $conn->query($sql);

				session_destroy();

				echo "Your password has been changed.<a href='Dashboard2.php'>Return</a> to the main page";

			}
			else die("New passwords dont match");


		}

		else die("Old paswords dont match <a href='Dashboard2.php'>Return</a> to the main page");
	}

	else {
		echo"
		<!DOCTYPE html>
		<html>
		<head>
		<meta charset='UTF-8' />
    	<title>Change password</title>
    	<link href='Css\style4.css' rel='stylesheet' />
        <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Open+Sans:400,600,300'/>
  		</head>
  		<body>
        <div class = 'head'>
        <header>
        <a href = 'Dashboard2.php'>Home</a>
        <a href = 'logout.php'>Log out </a>
        </header>
        </div>

        <div class='form1'>
        <h1>Change your password</h1>
        <form method = 'POST' action = 'changepassword.php'>
        <div class='section'>Change your Password</div>
        <div class='section'><span>Password</span></div>
        <div class='inner-wrap'>
        <label>Old Password <input type='password' name='oldpassword' /></label>
        <label>New Password <input type='password' name='newpassword' /></label>
        <label>Confirm Password <input type='password' name='repeatnewpassword' /></label>
        </div>


        <div class='button-section'>
        <input type='submit' name='submit' value='Change Password' />
        </div>
        </form>
        </div>
  		</body>
		</html>
		";
	}

}
 else
 	die("You must be logged in to change your password");







?>
