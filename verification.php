<?php
session_start();

// Database connection
include 'connection.php';

// Check if user is logged in and account is not locked
if (!isset($_SESSION['users_username']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Check if account is locked
$lock_check_sql = "SELECT account_status FROM userss WHERE username=?";
$lock_stmt = $conn->prepare($lock_check_sql);
$lock_stmt->bind_param("s", $_SESSION['users_username']);
$lock_stmt->execute();
$lock_result = $lock_stmt->get_result();

if ($lock_result->num_rows > 0) {
    $user_status = $lock_result->fetch_assoc();
    if ($user_status['account_status'] === 'locked') {
        // Log user out if account is locked
        session_unset();
        session_destroy();
        header("Location: login.php?error=locked");
        exit();
    }
}
$lock_stmt->close();

// Validate user session
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id || !is_numeric($user_id)) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Initialize variables
$set_verification_code = '';
$account_status = 'active';

// Get user data with prepared statement
if ($user_id) {
    $sql = "SELECT pin, account_status, failed_attempts FROM userss WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $set_verification_code = $user['pin'];
        $account_status = $user['account_status'];
        
        // Sync session with database failed attempts
        $_SESSION['failed_attempts'] = $user['failed_attempts'] ?? 0;
    } else {
        // User not found
        session_destroy();
        header("Location: login.php");
        exit();
    }
    $stmt->close();
}

// Initialize session variables if not set
if (!isset($_SESSION['failed_attempts'])) {
    $_SESSION['failed_attempts'] = 0;
}

if (!isset($_SESSION['lockout_until'])) {
    $_SESSION['lockout_until'] = null;
}

// Check if account is locked in database
if ($account_status === 'locked') {
    $_SESSION['error'] = "Your account is locked. Please contact administrator.";
    $_SESSION['failed_attempts'] = 6;
}

// Check if we're currently in a lockout period
$current_time = time();
if ($_SESSION['lockout_until'] && $current_time < $_SESSION['lockout_until']) {
    $remaining_time = $_SESSION['lockout_until'] - $current_time;
    $_SESSION['error'] = "Too many failed attempts. Please try again in <span id='countdown-seconds'>$remaining_time</span> seconds.";
    $_SESSION['countdown_active'] = true;
} else {
    $_SESSION['countdown_active'] = false;
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if account is locked or suspended in database
    if ($account_status === 'locked') {
        $_SESSION['error'] = "Your account is locked. Please contact administrator.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Check if we're in a lockout period
    if ($_SESSION['lockout_until'] && $current_time < $_SESSION['lockout_until']) {
        $remaining_time = $_SESSION['lockout_until'] - $current_time;
        $_SESSION['error'] = "Too many failed attempts. Please try again in <span id='countdown-seconds'>$remaining_time</span> seconds.";
        $_SESSION['countdown_active'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Validate and sanitize OTP inputs
    $entered_code = '';
    for ($i = 1; $i <= 6; $i++) {
        $otp_digit = $_POST['otp' . $i] ?? '';
        if (!preg_match('/^[0-9]$/', $otp_digit)) {
            $_SESSION['error'] = "Invalid PIN format. Please enter digits only.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        $entered_code .= $otp_digit;
    }

    // Verify the code with timing-safe comparison
    if (hash_equals($set_verification_code, $entered_code)) {
    // Reset failed attempts
    $_SESSION['failed_attempts'] = 0;
    $_SESSION['lockout_until'] = null;
    $_SESSION['countdown_active'] = false;
    
    // Update database
    $reset_sql = "UPDATE userss SET failed_attempts = 0 WHERE id = ?";
    $reset_stmt = $conn->prepare($reset_sql);
    $reset_stmt->bind_param("i", $user_id);
    $reset_stmt->execute();
    $reset_stmt->close();
    
    // CRITICAL: Get the updated user data including role
    $user_sql = "SELECT username, role, account_status FROM userss WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        
        // Set all necessary session variables
        $_SESSION['users_username'] = $user_data['username'];
        $_SESSION['username'] = $user_data['username']; // Set both for consistency
        $_SESSION['user_role'] = $user_data['role'];
        $_SESSION['account_status'] = $user_data['account_status'];
        $_SESSION['logged_in'] = true;
        
        error_log("Login successful - User: " . $user_data['username'] . ", Role: " . $user_data['role']);
    }
    $user_stmt->close();
    
    // Regenerate session ID
    session_regenerate_id(true);
    
    // Redirect based on role
    switch($_SESSION['user_role']) {
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
            // If no role or unrecognized role, show error or default page
            error_log("Unrecognized role: " . $_SESSION['user_role']);
            header("Location: unauthorized.php");
            exit;
    }
    exit();
}else {
        // Increment failed attempts
        $_SESSION['failed_attempts']++;
        
        // Update failed attempts in database
        $update_sql = "UPDATE userss SET failed_attempts = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $_SESSION['failed_attempts'], $user_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Check if we've reached the lockout threshold
        if ($_SESSION['failed_attempts'] >= 6) {
            // Permanently lock the account in database
            $lock_sql = "UPDATE userss SET account_status = 'locked' WHERE id = ?";
            $lock_stmt = $conn->prepare($lock_sql);
            $lock_stmt->bind_param("i", $user_id);
            $lock_stmt->execute();
            $lock_stmt->close();
            
            // Update local status
            $account_status = 'locked';
            
            $_SESSION['error'] = "Too many failed attempts. Your account has been locked. Please contact administrator.";
            $_SESSION['countdown_active'] = false;
        } elseif ($_SESSION['failed_attempts'] >= 5) {
            // Set 60-second lockout for 5th failed attempt
            $_SESSION['lockout_until'] = $current_time + 60;
            $remaining_time = 60;
            $_SESSION['error'] = "Too many failed attempts. Please try again in <span id='countdown-seconds'>$remaining_time</span> seconds.";
            $_SESSION['countdown_active'] = true;
        } else {
            // Regular failed attempt message
            $attempts_remaining = 5 - $_SESSION['failed_attempts'];
            $_SESSION['error'] = "Incorrect verification code. $attempts_remaining attempts remaining.";
            $_SESSION['countdown_active'] = false;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Verification</title>
    <link rel="icon" href="logo.png" type="img">
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");

        body {
            font-family: "Inter", sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .shake {
            animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }

        @keyframes shake {
            10%,
            90% {
                transform: translateX(-2px);
            }
            20%,
            80% {
                transform: translateX(4px);
            }
            30%,
            50%,
            70% {
                transform: translateX(-6px);
            }
            40%,
            60% {
                transform: translateX(6px);
            }
        }

        .pin-input {
            transition: all 0.3s ease;
        }

        .container {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-radius: 20px;
        }
        
        .countdown {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 15px;
        }
        
    </style>
</head>
<body>
    <div class="bg-white container p-8 w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <div
                class="w-16 h-16 bg-violet-100 rounded-full flex items-center justify-center mx-auto mb-4"
            >
                <i class="fas fa-lock text-violet-500 text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Secure PIN Verification</h2>
            <p class="text-gray-600 mt-2">Enter your 6-digit security code</p>
            
        </div>

        <!-- Error Message -->
        <?php if (isset($_SESSION['error'])): ?>
        <div
            id="error-message"
            class="bg-red-50 text-red-700 p-3 rounded-lg mb-6 flex items-start"
        >
            <i class="fas fa-exclamation-circle mt-1 mr-2"></i>
            <span><?php echo $_SESSION['error']; ?></span>
        </div>
        <?php else: ?>
        <div
            id="error-message"
            class="hidden bg-red-50 text-red-700 p-3 rounded-lg mb-6 flex items-start"
        >
            <i class="fas fa-exclamation-circle mt-1 mr-2"></i>
            <span>Incorrect verification code. Please try again.</span>
        </div>
        <?php endif; ?>

        <!-- Success Message (Hidden by default) -->
        <div id="success-message" class="hidden bg-green-50 text-green-700 p-3 rounded-lg mb-6 flex items-start">
            <i class="fas fa-check-circle mt-1 mr-2"></i>
            <span>PIN verified successfully!</span>
        </div>

        <!-- PIN Inputs -->
        <form id="pin-form" method="POST" action="">
            <div class="flex justify-between mb-6" id="pin-container">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                <input
                    type="password"
                    name="otp<?php echo $i; ?>"
                    class="pin-input w-12 h-14 border-2 border-purple-500 text-center text-xl rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500"
                    maxlength="1"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    id="pin-<?php echo $i; ?>"
                    autocomplete="one-time-code"
                    <?php echo (($account_status === 'locked') || ($_SESSION['lockout_until'] && $current_time < $_SESSION['lockout_until'])) ? 'disabled' : ''; ?>
                    onpaste="return false;"
                    ondrop="return false;"
                />
                <?php endfor; ?>
            </div>

            <!-- Show PIN Toggle -->
            <div class="flex justify-end mb-6">
                <button
                    type="button"
                    id="toggle-pin"
                    class="text-purple-600 font-medium flex items-center"
                    <?php echo (($account_status === 'locked') || ($_SESSION['lockout_until'] && $current_time < $_SESSION['lockout_until'])) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>
                >
                    <i class="fas fa-eye mr-2"></i> Show PIN
                </button>
            </div>

            <!-- Buttons -->
            <div class="flex space-x-4 mb-6">
                <button
                    type="button"
                    id="clear-btn"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-3 px-4 rounded-lg transition duration-200"
                    <?php echo (($account_status === 'locked') || ($_SESSION['lockout_until'] && $current_time < $_SESSION['lockout_until'])) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>
                >
                    Clear
                </button>
                <button
                    type="submit"
                    id="verify-btn"
                    class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200"
                    <?php echo (($account_status === 'locked') || ($_SESSION['lockout_until'] && $current_time < $_SESSION['lockout_until'])) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>
                >
                    Verify
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const pinInputs = Array.from({ length: 6 }, (_, i) =>
                document.getElementById(`pin-${i + 1}`)
            );
            const pinContainer = document.getElementById("pin-container");
            const togglePinBtn = document.getElementById("toggle-pin");
            const clearBtn = document.getElementById("clear-btn");
            const errorMessage = document.getElementById("error-message");
            const successMessage = document.getElementById("success-message");
            const pinForm = document.getElementById("pin-form");
            let isPinVisible = false;

            // Only focus if not locked or in countdown
            <?php if ($account_status !== 'locked' && (!$_SESSION['lockout_until'] || $current_time >= $_SESSION['lockout_until'])): ?>
            pinInputs[0].focus();
            <?php endif; ?>

            // Countdown timer for error message
            <?php if ($_SESSION['countdown_active'] && $_SESSION['lockout_until'] && $current_time < $_SESSION['lockout_until']): ?>
            let timeLeft = <?php echo $_SESSION['lockout_until'] - $current_time; ?>;
            const countdownInterval = setInterval(() => {
                timeLeft--;
                
                // Update the countdown in the error message
                const countdownSpan = document.querySelector('#error-message span #countdown-seconds');
                if (countdownSpan) {
                    countdownSpan.textContent = timeLeft;
                }
                
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    window.location.reload();
                }
            }, 1000);
            <?php endif; ?>

            document.addEventListener("keydown", function (e) {
                // Only process keys if not locked or in countdown
                <?php if ($account_status === 'locked' || ($_SESSION['lockout_until'] && $current_time < $_SESSION['lockout_until'])): ?>
                e.preventDefault();
                return;
                <?php endif; ?>
                
                if ((e.key >= "0" && e.key <= "9") || e.key === "Backspace") {
                    const currentFocused = document.activeElement;
                    const currentIndex = pinInputs.indexOf(currentFocused);
                    if (e.key >= "0" && e.key <= "9") {
                        if (currentIndex !== -1) {
                            setTimeout(() => {
                                if (currentIndex < 5 && currentFocused.value !== "") {
                                    pinInputs[currentIndex + 1].focus();
                                }
                            }, 10);
                        } else {
                            const firstEmpty = pinInputs.findIndex(
                                (input) => input.value === ""
                            );
                            if (firstEmpty !== -1) {
                                pinInputs[firstEmpty].focus();
                                pinInputs[firstEmpty].value = e.key;
                                if (firstEmpty < 5) pinInputs[firstEmpty + 1].focus();
                            }
                        }
                    }
                }
            });

            pinInputs.forEach((input, index) => {
                input.addEventListener("input", () => {
                    input.value = input.value.replace(/[^0-9]/g, "");
                    if (input.value.length === 1 && index < 5)
                        pinInputs[index + 1].focus();
                    errorMessage.classList.add("hidden");
                    successMessage.classList.add("hidden");
                    input.classList.remove("border-red-500");
                    input.classList.add("border-purple-500");
                });

                input.addEventListener("keydown", (e) => {
                    if (e.key === "Backspace") {
                        if (input.value === "" && index > 0) pinInputs[index - 1].focus();
                        else input.value = "";
                        input.classList.remove("border-red-500");
                        input.classList.add("border-purple-500");
                    }
                    if (e.key === "Enter") pinForm.dispatchEvent(new Event("submit"));
                });
                
                // Prevent paste
                input.addEventListener("paste", (e) => {
                    e.preventDefault();
                    return false;
                });
            });

            // Show PIN toggle
            togglePinBtn.addEventListener("click", () => {
                <?php if ($account_status === 'locked' || ($_SESSION['lockout_until'] && $current_time < $_SESSION['lockout_until'])): ?>
                return;
                <?php endif; ?>
                
                isPinVisible = !isPinVisible;
                pinInputs.forEach((input) => {
                    input.type = isPinVisible ? "text" : "password";
                });
                togglePinBtn.innerHTML = isPinVisible
                    ? '<i class="fas fa-eye-slash mr-2"></i> Hide PIN'
                    : '<i class="fas fa-eye mr-2"></i> Show PIN';

                // Focus back on the last filled box
                let lastFilledIndex = -1;
                for (let i = 0; i < pinInputs.length; i++) {
                    if (pinInputs[i].value !== "") {
                        lastFilledIndex = i;
                    }
                }

                if (lastFilledIndex !== -1) {
                    pinInputs[lastFilledIndex].focus();
                } else {
                    pinInputs[0].focus(); // fallback if empty
                }
            });

            // Clear all inputs
            clearBtn.addEventListener("click", () => {
                <?php if ($account_status === 'locked' || ($_SESSION['lockout_until'] && $current_time < $_SESSION['lockout_until'])): ?>
                return;
                <?php endif; ?>
                
                pinInputs.forEach((input) => {
                    input.value = "";
                    input.classList.remove("border-red-500", "border-green-500");
                    input.classList.add("border-purple-500");
                });
                pinInputs[0].focus();
                errorMessage.classList.add("hidden");
                successMessage.classList.add("hidden");
            });

            // Submit form validation
            pinForm.addEventListener("submit", (e) => {
                <?php if ($account_status === 'locked' || ($_SESSION['lockout_until'] && $current_time < $_SESSION['lockout_until'])): ?>
                e.preventDefault();
                return false;
                <?php endif; ?>
                
                // Check if all fields are filled
                const allFilled = pinInputs.every(input => input.value.length === 1);
                if (!allFilled) {
                    e.preventDefault();
                    errorMessage.querySelector('span').textContent = "Please enter all 6 digits.";
                    errorMessage.classList.remove("hidden");
                    return false;
                }
                
                // Form will be submitted to server for validation
                return true;
            });

            // Show error on page reload if needed
            const pinError = "<?php echo isset($_SESSION['error']) ? '1' : '0'; ?>";
            if (pinError === "1") {
                pinInputs.forEach((input) => {
                    input.value = "";
                    input.classList.remove("border-purple-500");
                    input.classList.add("border-red-500");
                });
                errorMessage.classList.remove("hidden");
                pinContainer.classList.add("shake");
                setTimeout(() => pinContainer.classList.remove("shake"), 500);
                <?php if ($account_status !== 'locked' && (!$_SESSION['lockout_until'] || $current_time >= $_SESSION['lockout_until'])): ?>
                pinInputs[0].focus();
                <?php endif; ?>
            }
        });
    </script>
</body>
<?php 
// Clear error and countdown status after displaying
unset($_SESSION['error']);
unset($_SESSION['countdown_active']);
?>
</html>