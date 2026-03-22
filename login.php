<?php
session_start();
include 'session_manager.php';

// Check if PHPMailer is installed via Composer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    // Load PHPMailer via Composer
    require 'vendor/autoload.php';
} else {
    // If not using Composer, manually include PHPMailer
    // Make sure PHPMailer files are in a 'PHPMailer' directory
    $phpmailer_path = __DIR__ . '/PHPMailer/';
    
    // Check for different PHPMailer versions
    if (file_exists($phpmailer_path . 'src/PHPMailer.php')) {
        // PHPMailer 6.0+ structure
        require $phpmailer_path . 'src/PHPMailer.php';
        require $phpmailer_path . 'src/Exception.php';
        require $phpmailer_path . 'src/SMTP.php';
    } elseif (file_exists($phpmailer_path . 'PHPMailer.php')) {
        // Older PHPMailer structure
        require $phpmailer_path . 'PHPMailer.php';
        require $phpmailer_path . 'Exception.php';
        require $phpmailer_path . 'SMTP.php';
    } else {
        die("PHPMailer not found. Please install via Composer: 'composer require phpmailer/phpmailer' or download manually.");
    }
}

// Now use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'connection.php';

    // Check if this is OTP verification step
    if (isset($_POST['verify_otp'])) {
        $entered_otp = $_POST['otp_code'];
        $username = $_SESSION['temp_username'];
        
        // Verify OTP
        $otp_sql = "SELECT otp_code, otp_expiry FROM userss WHERE username=?";
        $otp_stmt = $conn->prepare($otp_sql);
        $otp_stmt->bind_param("s", $username);
        $otp_stmt->execute();
        $otp_result = $otp_stmt->get_result();
        
        if ($otp_result->num_rows > 0) {
            $otp_data = $otp_result->fetch_assoc();
            $stored_otp = $otp_data['otp_code'];
            $otp_expiry = $otp_data['otp_expiry'];
            
            // After OTP verification - complete login
            if ($stored_otp === $entered_otp && strtotime($otp_expiry) > time()) {
                // OTP verified successfully - complete login
                $user_sql = "SELECT * FROM userss WHERE username=?";
                $user_stmt = $conn->prepare($user_sql);
                $user_stmt->bind_param("s", $username);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                $user = $user_result->fetch_assoc();
                
                // Set session variables
                $_SESSION['users_username'] = $username;
                $_SESSION['logged_in'] = true;
                $_SESSION['user_pin'] = $user['pin'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION["user_id"] = $user["id"];
                $_SESSION['givenname'] = $user['gname'];
                $_SESSION['surname'] = $user['surname'];
                $_SESSION['pin_verified'] = false; // PIN not verified yet

                // Mark user as logged in
                log_user_in($username, $conn);
                
                // Clear OTP after successful verification
                $clear_otp_sql = "UPDATE userss SET otp_code = NULL, otp_expiry = NULL WHERE username = ?";
                $clear_otp_stmt = $conn->prepare($clear_otp_sql);
                $clear_otp_stmt->bind_param("s", $username);
                $clear_otp_stmt->execute();
                $clear_otp_stmt->close();
                
                // Clear temporary session
                unset($_SESSION['temp_username']);
                unset($_SESSION['otp_required']);
                
                // ==== ROLE-BASED REDIRECTION ====
                $user_role = $user['role'];
                
                switch($user_role) {
                    case 'financial admin':
                        header("Location: dashboard_admin.php");
                        break;
                    case 'auditor':
                        header("Location: dashboard_auditor.php");
                        break;
                    case 'budget manager':
                        header("Location: dashboard_budget_manager.php");
                        break;
                    case 'collector':
                        header("Location: dashboard_collector.php");
                        break;
                    case 'disburse officer':
                        header("Location: dashboard_disburse_officer.php");
                        break;
                    default:
                        header("Location: dashboard_admin.php");
                        break;
                }
                exit();
            } else {
                echo '<script>alert("Invalid or expired OTP code!"); window.history.back();</script>';
            }
        } else {
            echo '<script>alert("OTP verification failed!"); window.history.back();</script>';
        }
        
        $otp_stmt->close();
        $conn->close();
        exit();
    }

    // Original login process (username/password verification)
    $email = $_POST["username"]; // This is actually the email from the form
    $password = $_POST["password"];

    // Email validation - must be @gmail.com
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/', $email)) {
        echo '<script>alert("Please enter a valid email address (@gmail.com)!"); window.history.back();</script>';
        exit();
    }

    // Check if account is locked or suspended before proceeding
    $lock_check_sql = "SELECT account_status FROM userss WHERE email=?";
    $lock_stmt = $conn->prepare($lock_check_sql);
    $lock_stmt->bind_param("s", $email);
    $lock_stmt->execute();
    $lock_result = $lock_stmt->get_result();
    
    $account_locked = false;
    $account_suspended = false;
    
    if ($lock_result->num_rows > 0) {
        $user_status = $lock_result->fetch_assoc();
        if ($user_status['account_status'] === 'locked') {
            $account_locked = true;
        }
        if ($user_status['account_status'] === 'suspended') {
            $account_suspended = true;
        }
    }
    $lock_stmt->close();
    
    if ($account_locked) {
        echo '<script>alert("Your account is locked. Please contact administrator."); window.history.back();</script>';
        $conn->close();
        exit();
    }
    
    if ($account_suspended) {
        echo '<script>alert("Your account is suspended. Please contact administrator."); window.history.back();</script>';
        $conn->close();
        exit();
    }

    // CHANGED: Check by email instead of username
    $sql = "SELECT * FROM userss WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Check if account is locked or suspended again after fetching user data
        if ($user['account_status'] === 'locked') {
            echo '<script>alert("Your account is locked. Please contact administrator."); window.history.back();</script>';
            $stmt->close();
            $conn->close();
            exit();
        }
        
        if ($user['account_status'] === 'suspended') {
            echo '<script>alert("Your account is suspended. Please contact administrator."); window.history.back();</script>';
            $stmt->close();
            $conn->close();
            exit();
        }

        if (password_verify($password, $user['password'])) {
            // Check if the user is already logged in
            if (is_user_logged_in($user['username'])) {
                echo '<script>alert("User is already logged in from another session!"); window.history.back();</script>';
            } else {
                // Generate and send OTP
                $otp_code = generate_otp();
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                // Store OTP in database
                $otp_sql = "UPDATE userss SET otp_code = ?, otp_expiry = ? WHERE email = ?";
                $otp_stmt = $conn->prepare($otp_sql);
                $otp_stmt->bind_param("sss", $otp_code, $otp_expiry, $email);
                $otp_stmt->execute();
                $otp_stmt->close();
                
                // Send OTP to user using PHPMailer
                $email_sent = send_otp_with_phpmailer($user['email'], $otp_code, $user['username'], $user['gname'] . ' ' . $user['surname']);
                
                if ($email_sent) {
                    // Store username in session for OTP verification
                    $_SESSION['temp_username'] = $user['username'];
                    $_SESSION['otp_required'] = true;
                    
                    // Redirect to OTP verification page
                    header("Location: login.php?otp=1");
                    exit();
                } else {
                    echo '<script>alert("Failed to send OTP email. Please try again."); window.history.back();</script>';
                }
            }
        } else {
            echo '<script>alert("Invalid email or password!"); window.history.back();</script>';
        }
    } else {
        echo '<script>alert("Invalid email or password!"); window.history.back();</script>';
    }

    $stmt->close();
    $conn->close();
}

// Function to generate 6-digit OTP
function generate_otp() {
    return sprintf("%06d", mt_rand(1, 999999));
}

// Function to send OTP using PHPMailer with embedded logo
function send_otp_with_phpmailer($email, $otp_code, $username, $fullname) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'viahalefinancials@gmail.com';
        $mail->Password   = 'lave czxg trib uqwq';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('noreply@viahale.com', 'ViaHale Financials');
        $mail->addAddress($email, $fullname);
        $mail->addReplyTo('viahalefinancials@gmail.com', 'ViaHale Support');

        // Embed logo image
        $logo_path = __DIR__ . '/logo.png';
        if (file_exists($logo_path)) {
            $mail->addEmbeddedImage($logo_path, 'logo', 'logo.png');
            $logo_cid = 'cid:logo';
        } else {
            $logo_cid = '';
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code for ViaHale Financials';
        
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { 
                        font-family: 'Arial', sans-serif; 
                        background-color: #f4f4f4; 
                        padding: 20px; 
                        margin: 0;
                    }
                    .container { 
                        background-color: white; 
                        padding: 30px; 
                        border-radius: 10px; 
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
                        max-width: 600px; 
                        margin: 0 auto; 
                    }
                    .logo-container {
                        text-align: center;
                        margin-bottom: 20px;
                        padding-bottom: 20px;
                        border-bottom: 2px solid #4311A5;
                    }
                    .logo {
                        max-width: 150px;
                        height: auto;
                    }
                    .otp-code { 
                        font-size: 32px; 
                        font-weight: bold; 
                        color: #4311A5; 
                        text-align: center; 
                        letter-spacing: 5px; 
                        margin: 20px 0; 
                        padding: 15px; 
                        background: #f8f9fa; 
                        border-radius: 8px;
                        border: 2px dashed #4311A5;
                    }
                    .footer { 
                        margin-top: 20px; 
                        padding-top: 20px; 
                        border-top: 1px solid #eee; 
                        color: #666; 
                        font-size: 12px; 
                        text-align: center;
                    }
                    .warning { 
                        background: #fff3cd; 
                        color: #856404; 
                        padding: 12px; 
                        border-radius: 5px; 
                        margin: 15px 0; 
                        border-left: 4px solid #ffc107;
                    }
                    .content {
                        color: #333;
                        line-height: 1.6;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='logo-container'>
                        " . ($logo_cid ? "<img src='$logo_cid' alt='ViaHale Financials Logo' class='logo'>" : "<h1>ViaHale Financials</h1>") . "
                    </div>
                    
                    <div class='content'>
                        <h2 style='color: #4311A5; text-align: center;'>OTP Verification</h2>
                        <p>Hello <strong>$fullname</strong>,</p>
                        <p>Your One-Time Password (OTP) for login to ViaHale Financials is:</p>
                        
                        <div class='otp-code'>$otp_code</div>
                        
                        <div class='warning'>
                            <strong>⚠️ Security Notice:</strong> This OTP will expire in <strong>10 minutes</strong>.
                            Do not share this code with anyone.
                        </div>
                        
                        <p>If you didn't request this OTP, please ignore this email or contact our support team immediately.</p>
                    </div>
                    
                    <div class='footer'>
                        <p><strong>ViaHale Financials Team</strong></p>
                        <p>Email: support@viahale.com | Phone: +1 (555) 123-4567</p>
                        <p>© 2025 ViaHale Financials. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "VIAHALE FINANCIALS\n\nHello $fullname,\n\nYour OTP code is: $otp_code\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this, please ignore this message.\n\nBest regards,\nViaHale Financials Team\nsupport@viahale.com";

        $mail->send();
        error_log("OTP email with embedded logo sent successfully to: $email");
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        return false;
    }
}

// Resend OTP functionality
if (isset($_GET['resend_otp']) && isset($_SESSION['temp_username'])) {
    include 'connection.php';
    
    $username = $_SESSION['temp_username'];
    
    // Generate new OTP
    $new_otp = generate_otp();
    $new_otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Update OTP in database
    $resend_sql = "UPDATE userss SET otp_code = ?, otp_expiry = ? WHERE username = ?";
    $resend_stmt = $conn->prepare($resend_sql);
    $resend_stmt->bind_param("sss", $new_otp, $new_otp_expiry, $username);
    $resend_stmt->execute();
    
    // Get user details for email
    $user_sql = "SELECT email, gname, surname FROM userss WHERE username = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("s", $username);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();
    
    // Send new OTP
    $email_sent = send_otp_with_phpmailer($user['email'], $new_otp, $username, $user['gname'] . ' ' . $user['surname']);
    
    if ($email_sent) {
        echo '<script>alert("New OTP sent to your email!"); window.location.href = "login.php?otp=1";</script>';
    } else {
        echo '<script>alert("Failed to resend OTP. Please try again."); window.location.href = "login.php?otp=1";</script>';
    }
    
    $resend_stmt->close();
    $user_stmt->close();
    $conn->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ViaHale Financials - Login</title>
  <link rel="icon" href="logo.png" type="image/png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .font-poppins {
      font-family: 'Poppins', sans-serif;
    }
    
    .gradient-bg {
      background: linear-gradient(135deg, #9A66FF, #6532C9, #4311A5);
    }
    
    .input-focus:focus {
      box-shadow: 0 0 0 2px rgba(67, 17, 165, 0.2);
      border-color: #4311A5;
    }
    
    .btn-hover {
      transition: all 0.3s ease;
    }
    
    .btn-hover:hover {
      transform: translateY(-1px);
    }
    
    .fade-in {
      animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .otp-input {
      letter-spacing: 8px;
      font-size: 24px;
      text-align: center;
    }

    .error-message {
      color: #fbd38d;
      font-size: 0.875rem;
      margin-top: 0.25rem;
      display: none;
    }
  </style>
</head>
<body class="min-h-screen bg-white flex items-center justify-center px-4 font-poppins fade-in">
  <div class="w-full max-w-lg">
    
    <!-- Welcome Header -->
    <div class="text-center mb-8">
      <h1 class="text-4xl font-poppins text-violet-900 mb-4">Welcome to ViaHale!</h1>
      <p class="text-gray-600 text-sm">Please enter your credentials to access the dashboard.</p>
    </div>
    
    <!-- Login Card -->
    <div class="rounded-2xl shadow-lg overflow-hidden">
      
      <!-- Card Body -->
      <div class="gradient-bg text-white p-8">
        <!-- Card Header - Only show "LOGIN" text on login form, not OTP form -->
        <?php if (!isset($_GET['otp'])): ?>
        <div class="text-white pb-6 text-left">
          <h2 class="text-2xl font-semibold">LOGIN</h2>
        </div>
        <?php endif; ?>
        
        <?php if (!isset($_GET['otp'])): ?>
        <!-- Email/Password Form -->
        <form action="login.php" method="post" class="space-y-6" onsubmit="return validateEmail()">
          <!-- Email Field -->
          <div>
            <label class="block text-white text-medium font-semibold mb-2" for="username">
              Email
            </label>
            <div class="relative">
              <input 
                type="text" 
                id="username"
                name="username" 
                placeholder="Enter your email" 
                class="w-full px-4 py-2 rounded-2xl border border-gray-300 focus:outline-none input-focus bg-purple-300 bg-opacity-30 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400" 
                required 
                onblur="validateEmailFormat()"
              />
            </div>
            <div id="emailError" class="error-message">Please enter a valid email address "@gmail.com"</div>
          </div>
          
          <!-- Password Field -->
          <div>
            <label class="block text-white text-medium font-semibold mb-2" for="password">
              Password
            </label>
            <div class="relative">
              <input 
                type="password" 
                id="password"
                name="password" 
                placeholder="Enter your password" 
                class="w-full px-4 py-2 rounded-2xl border border-gray-300 focus:outline-none input-focus bg-purple-300 bg-opacity-30 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400" 
                required 
              />
              <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <button type="button" class="text-white hover:text-purple-300 focus:outline-none" id="togglePassword">
                  <i class="fas fa-eye-slash"></i>
                </button>
              </div>
            </div>
          </div>
          
          <!-- Login Button -->
          <button type="submit" class="w-full bg-white text-purple-600 font-bold py-2 rounded-2xl btn-hover text-lg">
            Login
          </button>         
        </form>
        
        <?php else: ?>
        <!-- OTP Verification Form -->
        <form action="login.php" method="post" class="space-y-6">
          <input type="hidden" name="verify_otp" value="1">
          
          <div class="text-center mb-4">
            <h3 class="text-2xl font-bold text-white">OTP Verification</h3>
            <p class="text-purple-200 text-sm mt-2">Enter the 6-digit code sent to your email</p>
          </div>
          
          <div>
            <label class="block text-white text-medium font-semibold mb-2" for="otp_code">
              One-Time Password
            </label>
            <div class="relative">
              <input 
                type="text" 
                id="otp_code"
                name="otp_code" 
                placeholder="Enter 6-digit code" 
                maxlength="6" 
                pattern="[0-9]{6}" 
                class="w-full px-4 py-2 rounded-2xl border border-gray-300 focus:outline-none input-focus bg-purple-300 bg-opacity-30 text-white placeholder-purple-300 otp-input" 
                required 
              />
            </div>
          </div>
          
          <button type="submit" class="w-full bg-white text-purple-600 font-bold py-2 rounded-2xl btn-hover text-lg">
            Verify & Continue
          </button>
          
          <div class="text-center space-y-4">
            <p class="text-sm text-purple-200">Didn't receive the code?</p>
            <div class="flex justify-center space-x-6">
              <a href="login.php?resend_otp=1" class="text-yellow-200 hover:text-white font-semibold flex items-center">
                <i class="fas fa-redo-alt mr-2"></i> Resend OTP
              </a>
              <a href="login.php" class="text-yellow-200 hover:text-white font-semibold flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to Login
              </a>
            </div>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>
        <!-- Security Notice -->
    <div class="mt-6 text-center">
      <div class="inline-flex items-center text-xs text-gray-500 bg-white bg-opacity-70 rounded-full px-4 py-2">
        <i class="fas fa-shield-check text-green-500 mr-2"></i>
        <span>Your data is securely encrypted</span>
      </div>
    </div>
  </div>

  <script>
    // Email validation function
    function validateEmailFormat() {
      const emailInput = document.getElementById('username');
      const emailError = document.getElementById('emailError');
      const email = emailInput.value;
      
      if (email && (!email.includes('@gmail.com') || !/\S+@\S+\.\S+/.test(email))) {
        emailError.style.display = 'block';
        return false;
      } else {
        emailError.style.display = 'none';
        return true;
      }
    }

    // Form validation
    function validateEmail() {
      return validateEmailFormat();
    }

    // Auto-focus OTP input and move to next field
    document.addEventListener('DOMContentLoaded', function() {
        const otpInput = document.querySelector('input[name="otp_code"]');
        if (otpInput) {
            otpInput.focus();
            
            // Auto-submit when 6 digits are entered
            otpInput.addEventListener('input', function() {
                if (this.value.length === 6) {
                    this.form.submit();
                }
            });
        }
        
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const passwordInput = document.getElementById('password');
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle eye icon
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
    });
  </script>
</body>
</html>