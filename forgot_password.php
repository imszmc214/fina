<?php
session_start();
include 'connection.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    
    // Check if email exists
    $sql = "SELECT id, username, gname FROM userss WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Generate reset token (6-digit code)
        $reset_token = sprintf("%06d", mt_rand(1, 999999));
        $token_expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        
        // Store token in database
        $update_sql = "UPDATE userss SET reset_token=?, token_expiry=? WHERE id=?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $reset_token, $token_expiry, $user['id']);
        
        if ($update_stmt->execute()) {
            // Store in session for verification
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_token'] = $reset_token;
            $_SESSION['reset_user_id'] = $user['id'];
            
            header("Location: reset_password.php");
            exit();
        } else {
            $error = "Error generating reset token. Please try again.";
        }
    } else {
        $error = "Email not found in our system.";
    }
    
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Forgot Password - ViaHale Financials</title>
  <link rel="icon" href="logo.png" type="image/png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    .font-poppins { font-family: 'Poppins', sans-serif; }
  </style>
</head>
<body class="min-h-screen bg-white flex items-center justify-center px-4 font-poppins">
  <div class="min-h-screen flex flex-col items-center justify-center px-4">
    
    <!-- Header text -->
    <div class="mb-6 text-center">
      <h1 class="text-4xl font-poppins text-purple-1000">Reset Your Password</h1>
      <p class="text-sm text-gray-600 mt-2">Enter your email to receive reset instructions</p>
    </div>

    <!-- Purple box -->
    <div class="w-full max-w-lg bg-purple-800 text-white rounded-xl shadow-lg shadow-gray-500 p-8 space-y-6" style="background: linear-gradient(135deg, #9A66FF, #6532C9, #4311A5);">
      <div class="text-left">
        <h2 class="text-2xl font-bold mb-2">FORGOT PASSWORD</h2>
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

      <form action="forgot_password.php" method="post" class="space-y-4">
        Email
        <input type="email" name="email" placeholder="Enter your email" required 
               class="w-full px-4 py-2 rounded-xl bg-purple-300 bg-opacity-30 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400" />
        
        <button type="submit" class="w-full py-2 bg-white text-purple-800 font-semibold rounded-xl hover:bg-purple-100 transition">
          Send Reset Code
        </button>
      </form>

      <div class="flex justify-between text-sm text-yellow-200 space-x-2">
        <a href="login.php" class="underline hover:text-white">Back to Login</a>
        <a href="register.php" class="underline hover:text-white">Sign up</a>
      </div>
    </div>
  </div>
</body>
</html>