<?php
session_start();
include 'connection.php';

// Check if user came from forgot password
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_token'])) {
    header("Location: forgot_password.php");
    exit();
}

$error = '';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['verify_code'])) {
        // Verify the reset code
        $entered_code = trim($_POST['reset_code']);
        
        if ($entered_code === $_SESSION['reset_token']) {
            $_SESSION['code_verified'] = true;
            $message = "Code verified! You can now set your new password.";
        } else {
            $error = "Invalid reset code. Please try again.";
        }
    } 
    elseif (isset($_POST['reset_password'])) {
        // Reset the password
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);
        
        if ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password and clear reset token
            $update_sql = "UPDATE userss SET password=?, reset_token=NULL, token_expiry=NULL WHERE id=?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_password, $_SESSION['reset_user_id']);
            
            if ($update_stmt->execute()) {
                // Clear session
                session_unset();
                session_destroy();
                
                header("Location: login.php?message=password_reset");
                exit();
            } else {
                $error = "Error resetting password. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Password - ViaHale Financials</title>
  <link rel="icon" href="logo.png" type="image/png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    .font-poppins { font-family: 'Poppins', sans-serif; }
  </style>
</head>
<body class="min-h-screen bg-white flex items-center justify-center px-4 font-poppins">
  <div class="min-h-screen flex flex-col items-center justify-center px-4">
    
    <div class="mb-6 text-center">
      <h1 class="text-4xl font-poppins text-purple-1000">Reset Password</h1>
      <p class="text-sm text-gray-600 mt-2">Enter the code sent to your email and set new password</p>
    </div>

    <div class="w-full max-w-lg bg-purple-800 text-white rounded-xl shadow-lg shadow-gray-500 p-8 space-y-6" style="background: linear-gradient(135deg, #9A66FF, #6532C9, #4311A5);">
      <div class="text-left">
        <h2 class="text-2xl font-bold mb-2">RESET PASSWORD</h2>
      </div>

      <!-- Error Message -->
      <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
          <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <!-- Success Message -->
      <?php if (!empty($message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>

      <?php if (!isset($_SESSION['code_verified'])): ?>
        <!-- Code Verification Form -->
        <form action="reset_password.php" method="post" class="space-y-4">
          <div>
            <label class="block text-sm mb-2">Reset Code</label>
            <input type="text" name="reset_code" placeholder="Enter 6-digit code" required 
                   class="w-full px-4 py-2 rounded-xl bg-purple-300 bg-opacity-30 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400"
                   maxlength="6" pattern="[0-9]{6}">
          </div>
          
          <button type="submit" name="verify_code" class="w-full py-2 bg-white text-purple-800 font-semibold rounded-xl hover:bg-purple-100 transition">
            Verify Code
          </button>
        </form>
      <?php else: ?>
        <!-- Password Reset Form -->
        <form action="reset_password.php" method="post" class="space-y-4">
          <div>
            <label class="block text-sm mb-2">New Password</label>
            <input type="password" name="new_password" placeholder="Enter new password" required 
                   class="w-full px-4 py-2 rounded-xl bg-purple-300 bg-opacity-30 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400">
          </div>
          
          <div>
            <label class="block text-sm mb-2">Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Confirm new password" required 
                   class="w-full px-4 py-2 rounded-xl bg-purple-300 bg-opacity-30 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400">
          </div>
          
          <button type="submit" name="reset_password" class="w-full py-2 bg-white text-purple-800 font-semibold rounded-xl hover:bg-purple-100 transition">
            Reset Password
          </button>
        </form>
      <?php endif; ?>

      <div class="flex justify-between text-sm text-yellow-200 space-x-2">
        <a href="forgot_password.php" class="underline hover:text-white">Back</a>
        <a href="login.php" class="underline hover:text-white">Login</a>
      </div>
    </div>
  </div>
</body>
</html>