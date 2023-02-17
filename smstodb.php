<?php

/*  PHP example of an HTTP handler that handles requests from SMS Enabler.
 *  You can modify and use it for processing of incoming SMS messages and sending automatic SMS replies.
 *  To use it, put this file on your website and specify its URL in SMS Enabler's SMS-to-Webserver settings, 
 *  such as http://yourserv.com/sms.php
 */

$senderPhone = $_POST['sender']; /* sender's phone number */
$messageText = $_POST['text']; /* text of the message   */

$sent_dt = $_POST['sc_datetime']; /* date and time when the message was sent, in the local time zone of the computer on which SMS Enabler is running */
$sent_dt_utc = $_POST['sc_datetime_utc']; /* date and time when the message was sent, in UTC */
/* note: date and time values are expressed using "YYYY-MM-DD HH:MM:SS" format */

$smsc = $_POST['smsc']; /* SMS center number (not supported when SMS Enabler is connected to a Nokia phone). */
$tag = $_POST['tag']; /* Tag value. You can define this in SMS Enabler's settings, and use it to pass additional information. */

$is_incomplete = $_POST['has_missing_parts']; /* This variable can be set to "yes" or "no". */
/* "yes" - if the message is a multipart (concatenated) message and not all of its parts have arrived */
/* "no"  - if the message is complete */


/* TODO: IMPLEMENT ANY PROCESSING HERE THAT YOU NEED TO PERFORM UPON RECEIPT OF A MESSAGE */

$con = new mysqli('localhost', 'root', '', 'project');

// important: escape string values 
$messageText = mysqli_real_escape_string($con, $messageText);
$senderPhone = mysqli_real_escape_string($con, $senderPhone);
if ($messageText == 'Pay' or $messageText == 'pay' or $messageText == 'PAY') {
  // creating an sql statement to insert the message into the sms_in table
  $sql = "INSERT INTO sms_in(sms_text,sender_number,sent_dt) VALUES ('$messageText','$senderPhone','$sent_dt')";
  // executing the sql statement
  $insert_sms_success = mysqli_query($con, $sql);

  // closing the connection
  //mysqli_close($con);
  //processing the message
  header('Content-Type: text/plain; charset=utf-8');
  // Comment the next line out if you do not want to send a reply
  //echo 'PROCESSING';




  //check phone number
  $servername = "localhost";
  $username = "root";
  $password = "";
  $dbname = "project";

  // Create connection
  $conn = mysqli_connect($servername, $username, $password, $dbname);
  // Check connection
  if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
  }

  $sql = "SELECT * FROM users WHERE PHONENUMBER = '" . $senderPhone . "'";
  $result = mysqli_query($conn, $sql);


  if (mysqli_num_rows($result) > 0) {
    // output data of each row

    while ($row = mysqli_fetch_assoc($result)) {
      // echo  $row["EMAIL"];


      $sql1 = "SELECT * FROM card WHERE Phonenumber = '" . $senderPhone . "'";

      $result1 = mysqli_query($conn, $sql1);
      if (mysqli_num_rows($result1) > 0) {

        $runny = rand(0, 9999999);


        ///paystack

        $result = array();
        //Set other parameters as keys in the $postdata array
        $postdata = array('email' => $row["EMAIL"], 'amount' => 170000, "reference" => $runny);
        $url = "https://api.paystack.co/transaction/initialize";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata)); //Post Fields
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = [
          'Authorization: Bearer sk_test_30fcebdf06aa8647805878e92c01a7f60a1daa1e',
          'Content-Type: application/json',

        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $request = curl_exec($ch);

        //var_dump($request);
        curl_close($ch);
        echo "PAY CONFIRMED";
        if ($request) {
          $result = json_decode($request, true);
        }


        ////end paystack



        $sqll = "INSERT INTO paid (phonenumber, status)
VALUES ('" . $senderPhone . "', 'Paid')";

        if (mysqli_query($conn, $sqll)) {
          // echo "New record created successfully";
        } else {
          //echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }




      } else {
        echo 'REGISTER CARD';
      }



    }

  } else {
    echo "REGISTER PHONE";
  }








  mysqli_close($conn);






} else {
  /* ---- Sending a reply SMS ---- */

  // Setting the recipients of the reply. If not set, the reply is sent
// back to the sender of the origial SMS message
// header('X-SMS-To: +97771234567 +15550987654');


  // Setting the content type and character set
  header('Content-Type: text/plain; charset=utf-8');
  // Comment the next line out if you do not want to send a reply
  echo 'Wrong Code';
}
?>