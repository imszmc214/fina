<?php
$registrationError = "";

$username = $email = $givenname = $initial = $surname = $address = $age = $contact = $pin = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'connection.php';

    // Sanitize inputs
    $username   = $_POST["username"] ?? '';
    $email      = $_POST["email"] ?? '';
    $givenname  = $_POST["givenname"] ?? '';
    $initial    = $_POST["initial"] ?? '';
    $surname    = $_POST["surname"] ?? '';
    $address    = $_POST["address"] ?? '';
    $age        = $_POST["age"] ?? '';
    $contact    = $_POST["contact"] ?? '';
    $password   = $_POST["password"] ?? '';
    $cpassword  = $_POST["cpassword"] ?? '';
    $pin        = $_POST["pin"] ?? '';

    // Email validation - must be @gmail.com
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/', $email)) {
        $registrationError = "Please enter a valid email address (@gmail.com)!";
    } 
    // Check if email already exists
    else {
        $checkEmail = $conn->prepare("SELECT id FROM userss WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $checkEmail->store_result();
        
        if ($checkEmail->num_rows > 0) {
            $registrationError = "This email is already registered!";
            $checkEmail->close();
        } else {
            $checkEmail->close();

            // Password strength validation
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
                $registrationError = "Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character!";
            } 
            else if ($password !== $cpassword) {
                $registrationError = "Passwords don't match!";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user (role = NULL, account_status = 'new user')
                $stmt = $conn->prepare("INSERT INTO userss 
                    (username, email, gname, minitial, surname, address, age, contact, password, pin, account_status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new user')");

                $stmt->bind_param("ssssssisss", 
                    $username, $email, $givenname, $initial, $surname, $address, $age, $contact, $hashedPassword, $pin
                );

                if ($stmt->execute()) {
                    echo '<script>alert("Registration successful!"); window.location.href = "login.php";</script>';
                    exit();
                } else {
                    $registrationError = "Error: " . $stmt->error;
                }

                $stmt->close();
            }
        }
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Quicksand:wght@300;400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .font-poppins { font-family: 'Poppins', sans-serif; }
    .font-quicksand { font-family: 'Quicksand', sans-serif; }
    .font-bricolage { font-family: 'Bricolage Grotesque', sans-serif; }
    
    .strength-meter {
      height: 6px;
      border-radius: 3px;
      margin-top: 8px;
      transition: all 0.3s ease;
      background-color: #e2e8f0;
    }
    
    .strength-meter-fill {
      height: 100%;
      border-radius: 3px;
      transition: width 0.3s ease;
    }
    
    .strength-text {
      font-size: 0.75rem;
      margin-top: 4px;
      text-align: right;
    }
  </style>
  <script>
    function toggleVisibility(id, iconId) {
      const input = document.getElementById(id);
      const icon = document.getElementById(iconId);
      input.type = input.type === "password" ? "text" : "password";
      icon.classList.toggle("fa-eye");
      icon.classList.toggle("fa-eye-slash");
    }

    function generatePassword() {
      const lowercase = "abcdefghijklmnopqrstuvwxyz";
      const uppercase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
      const numbers = "0123456789";
      const special = "@$!%*?&";
      
      let password = "";
      
      // Ensure at least one character from each required category
      password += lowercase[Math.floor(Math.random() * lowercase.length)];
      password += uppercase[Math.floor(Math.random() * uppercase.length)];
      password += numbers[Math.floor(Math.random() * numbers.length)];
      password += special[Math.floor(Math.random() * special.length)];
      
      // Fill the rest with random characters from all categories
      const allChars = lowercase + uppercase + numbers + special;
      const remainingLength = 8 + Math.floor(Math.random() * 5); // 8-12 characters total
      
      for (let i = password.length; i < remainingLength; i++) {
        password += allChars[Math.floor(Math.random() * allChars.length)];
      }
      
      // Shuffle the password to make it more random
      password = password.split('').sort(() => Math.random() - 0.5).join('');
      
      document.getElementById("password").value = password;
      document.getElementById("cpassword").value = password;
      updatePasswordStrength(); // Update password strength meter
    }

    function validateEmail() {
      const email = document.getElementById('email').value;
      const emailError = document.getElementById('emailError');
      
      if (email && (!email.includes('@gmail.com') || !/\S+@\S+\.\S+/.test(email))) {
        emailError.textContent = 'Please enter a valid email address "@gmail.com"';
        return false;
      } else {
        emailError.textContent = '';
        return true;
      }
    }

    function updatePasswordStrength() {
      const password = document.getElementById('password').value;
      const strengthMeter = document.getElementById('strength-meter-fill');
      const strengthText = document.getElementById('strength-text');
      
      // Calculate password strength
      let strength = 0;
      let feedback = "";
      
      // Check password length
      if (password.length >= 8) strength += 25;
      
      // Check for uppercase letters
      if (/[A-Z]/.test(password)) strength += 25;
      
      // Check for lowercase letters
      if (/[a-z]/.test(password)) strength += 25;
      
      // Check for numbers
      if (/[0-9]/.test(password)) strength += 12.5;
      
      // Check for special characters
      if (/[@$!%*?&]/.test(password)) strength += 12.5;
      
      // Update the strength meter
      strengthMeter.style.width = strength + '%';
      
      // Update colors and text based on strength
      if (strength < 50) {
        strengthMeter.style.backgroundColor = '#ef4444'; // red
        strengthText.textContent = 'Weak';
        strengthText.className = 'strength-text text-red-500';
      } else if (strength < 75) {
        strengthMeter.style.backgroundColor = '#f59e0b'; // amber
        strengthText.textContent = 'Fair';
        strengthText.className = 'strength-text text-amber-500';
      } else if (strength < 100) {
        strengthMeter.style.backgroundColor = '#3b82f6'; // blue
        strengthText.textContent = 'Good';
        strengthText.className = 'strength-text text-blue-500';
      } else {
        strengthMeter.style.backgroundColor = '#10b981'; // green
        strengthText.textContent = 'Strong';
        strengthText.className = 'strength-text text-green-500';
      }
      
      return strength === 100;
    }

    function validateForm() {
      const isEmailValid = validateEmail();
      const isPasswordStrong = updatePasswordStrength();
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('cpassword').value;
      
      if (!isEmailValid) {
        alert('Please enter a valid email address "@gmail.com"');
        return false;
      }
      
      if (!isPasswordStrong) {
        alert('Please ensure your password is strong enough (at least 8 characters with uppercase, lowercase, number, and special character)');
        return false;
      }
      
      if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return false;
      }
      
      return true;
    }

    // Initialize password strength meter on page load
    document.addEventListener('DOMContentLoaded', function() {
      updatePasswordStrength();
    });
  </script>
</head>
<body class="min-h-screen flex items-center justify-center px-4 font-poppins">
  <div class="w-full max-w-6xl flex flex-col md:flex-row gap-8 items-stretch font-poppins">
    
    <!-- Left Side -->
    <div class="flex-1 flex flex-col justify-center px-6">
      <h1 class="text-5xl font-bold text-purple-800 mb-6">About Us</h1>
      <p class="text-sm text-black">
        ViaHale stands for Vehicle integrated access for high quality Assistance,<br>
        Logistics, and Experience — a proudly Filipino-built transport service<br>
        dedicated to making every journey safe, smooth, and accessible for everyone.
      </p>
    </div>

    <!-- Registration Form -->
    <div class="w-full max-w-md overflow-y-auto max-h-[90vh] p-6 rounded-xl shadow-lg" style="background: linear-gradient(135deg, #9A66FF, #6532C9, #4311A5);">
      <h2 class="text-2xl font-bold text-white mb-4">Register</h2>
      <p class="text-purple-100 mb-6">Please fill out the form to create your account.</p>
      
      <?php if ($registrationError): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          <?php echo htmlspecialchars($registrationError); ?>
        </div>
      <?php endif; ?>
      
      <form action="register.php" method="POST" class="space-y-4 text-left" onsubmit="return validateForm()">

        <!-- Username -->
        <div>
          <label for="username" class="text-white font-semibold">Username</label>
          <input type="text" id="username" name="username" placeholder="Enter username" value="<?php echo htmlspecialchars($username); ?>"
            class="w-full mt-1 px-4 py-2 rounded-lg bg-purple-200 bg-opacity-20 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400" required />
        </div>

        <!-- Email -->
        <div>
          <label for="email" class="text-white font-semibold">Email</label>
          <input type="email" id="email" name="email" placeholder="Enter Gmail address" value="<?php echo htmlspecialchars($email); ?>"
            class="w-full mt-1 px-4 py-2 rounded-lg bg-purple-200 bg-opacity-20 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400" 
            onblur="validateEmail()" required />
          <div id="emailError" class="text-red-300 text-sm mt-1"></div>
        </div>

        <!-- Given Name / Middle Initial / Surname -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="givenname" class="text-white font-semibold">Given Name</label>
            <input type="text" id="givenname" name="givenname" placeholder="Enter given name" value="<?php echo htmlspecialchars($givenname); ?>"
              class="w-full mt-1 px-4 py-2 rounded-lg bg-purple-200 bg-opacity-20 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400" required />
          </div>
          <div>
            <label for="initial" class="text-white font-semibold">Middle Initial</label>
            <input type="text" id="initial" name="initial" placeholder="Enter middle initial" value="<?php echo htmlspecialchars($initial); ?>"
              class="w-full mt-1 px-4 py-2 rounded-lg bg-purple-200 bg-opacity-20 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400" />
          </div>
          <div>
            <label for="surname" class="text-white font-semibold">Surname</label>
            <input type="text" id="surname" name="surname" placeholder="Enter surname" value="<?php echo htmlspecialchars($surname); ?>"
              class="w-full mt-1 px-4 py-2 rounded-lg bg-purple-200 bg-opacity-20 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400" required />
          </div>
        </div>

        <!-- Address -->
        <div>
          <label for="address" class="text-white font-semibold">Address</label>
          <input type="text" id="address" name="address" placeholder="Enter address" value="<?php echo htmlspecialchars($address); ?>"
            class="w-full mt-1 px-4 py-2 rounded-lg bg-purple-200 bg-opacity-20 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400" required />
        </div>

        <!-- Age & Contact -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="age" class="text-white font-semibold">Age</label>
            <input type="number" id="age" name="age" placeholder="Enter your age" value="<?php echo htmlspecialchars($age); ?>"
              class="w-full mt-1 px-4 py-2 rounded-lg bg-purple-200 bg-opacity-20 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400" required />
          </div>
          <div>
            <label for="contact" class="text-white font-semibold">Contact Number</label>
            <input type="text" id="contact" name="contact" placeholder="Enter contact number" value="<?php echo htmlspecialchars($contact); ?>"
              class="w-full mt-1 px-4 py-2 rounded-lg bg-purple-200 bg-opacity-20 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400" required />
          </div>
        </div>

        <!-- PIN -->
        <div>
          <label for="pin" class="text-white font-semibold">6-digit PIN</label>
          <input type="text" id="pin" name="pin" maxlength="6" pattern="\d{6}" placeholder="Enter 6-digit PIN" value="<?php echo htmlspecialchars($pin); ?>"
            class="w-full mt-1 px-4 py-2 rounded-lg bg-purple-200 bg-opacity-20 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400" required />
        </div>

        <!-- Generate Password -->
        <div>
          <button type="button" onclick="generatePassword()" class="w-full py-2 bg-white text-purple-800 font-semibold rounded-lg hover:bg-purple-100 transition">
            Generate Strong Password
          </button>
        </div>

        <!-- Password Fields -->
        <div class="relative">
          <label for="password" class="text-white font-semibold">Password</label>
          <input type="password" id="password" name="password" placeholder="Enter Password"
            class="w-full mt-1 px-4 py-2 rounded-lg bg-purple-200 bg-opacity-20 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400" 
            onkeyup="updatePasswordStrength()" required />
          <i id="eyeicon" class="fas fa-eye-slash absolute right-4 top-12 transform -translate-y-1/2 text-purple-300 cursor-pointer hover:text-white"
             onclick="toggleVisibility('password','eyeicon')"></i>
          
          <!-- Password Strength Meter -->
          <div class="strength-meter">
            <div id="strength-meter-fill" class="strength-meter-fill" style="width: 0%;"></div>
          </div>
          <div id="strength-text" class="strength-text"></div>
        </div>

        <div class="relative">
          <label for="cpassword" class="text-white font-semibold">Confirm Password</label>
          <input type="password" id="cpassword" name="cpassword" placeholder="Confirm Password"
            class="w-full mt-1 px-4 py-2 rounded-lg bg-purple-200 bg-opacity-20 text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-400" required />
          <i id="cpass_eyeicon" class="fas fa-eye-slash absolute right-4 top-12 transform -translate-y-1/2 text-purple-300 cursor-pointer hover:text-white"
             onclick="toggleVisibility('cpassword','cpass_eyeicon')"></i>
        </div>

        <!-- Buttons -->
        <div class="flex gap-4 pt-4">
          <button type="submit" name="register" class="w-full py-2 bg-white text-purple-800 font-semibold rounded-lg hover:bg-purple-100 transition">
            Register
          </button>
          <a href="login.php" class="w-full">
            <button type="button" class="w-full py-2 bg-red-500 text-white font-semibold rounded-lg hover:bg-red-600 transition">
              Cancel
            </button>
          </a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>