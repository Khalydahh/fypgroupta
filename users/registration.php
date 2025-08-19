<?php
session_start(); // Tambah session_start() di awal
include('includes/config.php');
// Set proper error reporting in production
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Initialize variables
$msg = "";
$error = "";
$fullname = $email = $contactno = $contactno2 = $icnumber = $address = "";

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if(isset($_POST['submit']))
{
    // Debug untuk memeriksa token
    error_log("CSRF Token dari borang: " . ($_POST['csrf_token'] ?? 'Tidak wujud'));
    error_log("CSRF Token dari sesi: " . ($_SESSION['csrf_token'] ?? 'Tidak wujud'));
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Ralat penghantaran borang. Sila cuba lagi. (Debug: Token tidak sepadan)";
    } else {
        // Sanitize and validate inputs
        $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'userEmail', FILTER_SANITIZE_EMAIL);
        $contactno = filter_input(INPUT_POST, 'contactno', FILTER_SANITIZE_STRING);
        $contactno2 = filter_input(INPUT_POST, 'contactno2', FILTER_SANITIZE_STRING);
        $icnumber = filter_input(INPUT_POST, 'icnumber', FILTER_SANITIZE_STRING);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Sila masukkan alamat emel yang sah.";
        }
        // Validate required fields
        elseif (empty($fullname) || empty($email) || empty($_POST['password']) || empty($contactno) || empty($icnumber) || empty($address)) {
            $error = "Semua medan yang diperlukan mesti diisi.";
        }
        // Validate password strength
        elseif (strlen($_POST['password']) < 8) {
            $error = "Kata laluan mesti sekurang-kurangnya 8 aksara.";
        }
        else {
            // Check if email already exists
            $stmt = $con->prepare("SELECT userEmail FROM users WHERE userEmail = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Emel telah didaftarkan. Sila gunakan emel lain.";
            } else {
                // Hash password with secure algorithm
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $status = 1;
                
                // Use prepared statement to prevent SQL injection
                $stmt = $con->prepare("INSERT INTO users (fullName, userEmail, password, contactno, contactno2, icnumber, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssi", $fullname, $email, $password, $contactno, $contactno2, $icnumber, $address, $status);
                
                if ($stmt->execute()) {
                    $msg = "Pendaftaran berjaya. Anda boleh log masuk sekarang!";
                    // Clear form data on success
                    $fullname = $email = $contactno = $contactno2 = $icnumber = $address = "";
                    // Generate new CSRF token
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $error = "Pendaftaran gagal: " . $stmt->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PTA Service Management System - User Registration">
    <meta name="author" content="PTA Admin">
    <title>PTA | User Registration</title>
    <link rel="icon" href="logopta.png" type="image/png">
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
  </head>

  <body>
    <div id="login-page">
      <div class="container">
        <h3 align="center" style="color:#fff"><a href="https://ptadmin.tvetikmb.com/" style="color:#fff">PTA Service Management System</a></h3>
        <hr />
        <form class="form-login" method="post" id="registration-form">
          <h2 class="form-login-heading">User Registration</h2>
          
          <?php if($msg): ?>
          <div class="alert alert-success">
            <?php echo htmlentities($msg); ?>
          </div>
          <?php endif; ?>
          
          <?php if($error): ?>
          <div class="alert alert-danger">
            <?php echo htmlentities($error); ?>
          </div>
          <?php endif; ?>
          
          <div class="login-wrap">
            <div class="form-group">
              <input type="text" class="form-control" placeholder="Full Name" name="fullname" required value="<?php echo htmlentities($fullname); ?>">
            </div>
            
            <div class="form-group">
              <input type="email" class="form-control" placeholder="Email ID" name="userEmail" id="userEmail" required value="<?php echo htmlentities($email); ?>">
              <div id="email-validation-message"></div>
            </div>
            
            <div class="form-group">
              <input type="password" class="form-control" placeholder="Password (minimum 8 characters)" name="password" id="password" required minlength="8">
              <div class="password-strength-meter mt-2"></div>
            </div>
            
            <div class="form-group">
              <input type="password" class="form-control" placeholder="Confirm Password" id="confirm-password" required>
              <div id="password-match-message"></div>
            </div>
            
            <div class="form-group">
              <input type="tel" class="form-control" name="contactno" placeholder="Primary Phone Number" required pattern="[0-9]+" value="<?php echo htmlentities($contactno); ?>">
            </div>
            
            <div class="form-group">
              <input type="tel" class="form-control" name="contactno2" placeholder="Secondary Phone Number (Optional)" pattern="[0-9]+" value="<?php echo htmlentities($contactno2); ?>">
            </div>
            
            <div class="form-group">
              <input type="text" class="form-control" name="icnumber" placeholder="IC Number" required value="<?php echo htmlentities($icnumber); ?>">
            </div>
            
            <div class="form-group">
              <textarea class="form-control" name="address" placeholder="Address" required rows="4"><?php echo htmlentities($address); ?></textarea>
            </div>
            
            <!-- CSRF Protection -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <button class="btn btn-theme btn-block" type="submit" name="submit" id="submit-btn"><i class="fa fa-user"></i> Register</button>
            <hr>
            
            <div class="registration">
              Already Registered<br/>
              <a class="" href="index.php">Sign in</a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- JS resources -->
    <script src="assets/js/jquery.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="assets/js/jquery.backstretch.min.js"></script>
    
    <script>
      $.backstretch("assets/img/login-bg.jpg", {speed: 500});
      
      // Email validation
      $('#userEmail').on('blur', function() {
        const email = $(this).val();
        if (email) {
          $.ajax({
            url: "check_availability.php",
            data: {email: email},
            type: "POST",
            success: function(data) {
              $("#email-validation-message").html(data);
            }
          });
        }
      });
      
      // Password validation
      $('#confirm-password').on('keyup', function() {
        const password = $('#password').val();
        const confirmPassword = $(this).val();
        
        if (password === confirmPassword) {
          $('#password-match-message').html('<span class="text-success">Passwords match</span>');
        } else {
          $('#password-match-message').html('<span class="text-danger">Passwords do not match</span>');
        }
      });
      
      // Form validation
      $('#registration-form').on('submit', function(e) {
        const password = $('#password').val();
        const confirmPassword = $('#confirm-password').val();
        
        if (password !== confirmPassword) {
          e.preventDefault();
          alert('Passwords do not match!');
        }
      });
    </script>
  </body>
</html>
