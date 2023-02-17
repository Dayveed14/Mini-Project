<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8" />
  <title>Login</title>
  <link href="Css\style1.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:400,700" />
</head>

<body>
  <div id="form-main">
    <div id="form-div">
      <form class="form" id="form1" action="Include/Process2.php" method="POST">
        <p class="name">
          <input name="username" type="text" class="validate[required,custom[onlyLetter],length[0,100]] feedback-input"
            placeholder="username" id="name" />
        </p>

        <p class="password">
          <input name="password" type="password"
            class="validate[required,custom[password],length[0,100]] feedback-input" placeholder="password"
            id="password" />
        </p>
        <div class="submit">
          <input type="submit" value="SEND" id="button-blue" />
        </div>
      </form>
    </div>
  </div>
</body>

</html>