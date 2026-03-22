<?php
session_start();
include 'session_manager.php';
include 'connection.php';

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Fetch user details
$stmt = $conn->prepare("SELECT username, gname, minitial, surname, address, age, contact, email, role, profile_picture FROM userss WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle profile picture upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_picture"])) {
    $target_dir = "uploads/profile_pictures/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $imageFileType = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
    $new_filename = "user_" . $user_id . "_" . time() . "." . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    $uploadOk = 1;
    
    // Check if image file is an actual image
    $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
    if ($check === false) {
        $error = "File is not an image.";
        $uploadOk = 0;
    }
    
    // Check file size (max 2MB)
    if ($_FILES["profile_picture"]["size"] > 2097152) {
        $error = "Sorry, your file is too large. Maximum size is 2MB.";
        $uploadOk = 0;
    }
    
    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }
    
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            // Update database with new profile picture path
            $update_stmt = $conn->prepare("UPDATE userss SET profile_picture = ? WHERE id = ?");
            $update_stmt->bind_param("si", $target_file, $user_id);
            if ($update_stmt->execute()) {
                $success = "Profile picture updated successfully!";
                // Update the user array to show new picture immediately
                $user['profile_picture'] = $target_file;
                $_SESSION['profile_picture'] = $target_file;
            } else {
                $error = "Error updating profile picture in database.";
            }
            $update_stmt->close();
        } else {
            $error = "Sorry, there was an error uploading your file.";
        }
    }
}

// Handle PIN change request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_pin"])) {
    $current_pin = $_POST["current_pin"];
    $new_pin = $_POST["new_pin"];
    $confirm_pin = $_POST["confirm_pin"];

    $user_id = $_SESSION["user_id"];

    // Check if PIN is exactly 6 digits
    if (!preg_match('/^\d{6}$/', $new_pin)) {
        $error = "PIN must be exactly 6 digits!";
    } elseif ($new_pin !== $confirm_pin) {
        $error = "New PIN and Confirm PIN do not match!";
    } else {
        // Fetch the user's current PIN from the database
        $stmt_pin = $conn->prepare("SELECT pin FROM userss WHERE id = ?");
        $stmt_pin->bind_param("i", $user_id);
        $stmt_pin->execute();
        $result_pin = $stmt_pin->get_result();
        $user_pin = $result_pin->fetch_assoc();

        // Compare as a simple string since PINs are stored as plain VARCHAR
        if (!$user_pin || $current_pin !== $user_pin["pin"]) {
            $error = "Current PIN is incorrect!";
        } else {
            // Update the PIN directly (without hashing)
            $update_stmt = $conn->prepare("UPDATE userss SET pin = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_pin, $user_id);
            if ($update_stmt->execute()) {
                $success = "PIN changed successfully!";
            } else {
                $error = "Error updating PIN.";
            }
            $update_stmt->close();
        }
        $stmt_pin->close();
    }
}

$stmt->close();
$conn->close();

// Set default profile picture if none exists
$profile_picture = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default_profile.png';
?>

<html>

<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <title>Profile</title>
    <link rel="icon" href="logo.png" type="img">
    <style>
        .profile-picture-container {
            position: relative;
            display: inline-block;
        }
        
        .profile-picture-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            cursor: pointer;
        }
        
        .profile-picture-container:hover .profile-picture-overlay {
            opacity: 1;
        }
        
        .profile-picture-icon {
            color: white;
            font-size: 1.5rem;
        }
        
        #profile-picture-input {
            display: none;
        }
    </style>
</head>

<body class="bg-white">
    <?php include('sidebar.php'); ?>

    <div class="overflow-y-auto h-full px-6">
        <!-- Breadcrumb -->
        <div class="flex justify-between items-center px-6 py-6 font-poppins">
            <h1 class="text-2xl">Profile</h1>
            <div class="text-sm">
                <a href="dashboard.php" class="text-black hover:text-blue-600">Home</a>
                /
                <a class="text-blue-600 hover:text-blue-600">Profile</a>
            </div>
        </div>

        <!-- Main content area -->
        <div class="flex-1 bg-white p-6 h-full w-full">
            <!-- Profile Header -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6">
                <div class="flex items-center mb-6">
                    <!-- Profile Picture with Upload Functionality -->
                    <div class="profile-picture-container">
                        <div class="h-24 w-24 rounded-full bg-zinc-300 overflow-hidden border-[6px] border-violet-500 shadow-lg">
                            <?php if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])): ?>
                                <img src="<?php echo $user['profile_picture']; ?>" 
                                     alt="Profile Picture" 
                                     class="h-full w-full object-cover">
                            <?php else: ?>
                                <div class="h-full w-full bg-zinc-300 uppercase flex items-center font-bold justify-center text-3xl text-gray-600">
                                    <span class="mb-1"><?php echo $_SESSION['users_username'][0] ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="profile-picture-overlay" onclick="document.getElementById('profile-picture-input').click()">
                            <i class="fas fa-camera profile-picture-icon"></i>
                        </div>
                    </div>
                    
                    <form id="profile-picture-form" method="POST" enctype="multipart/form-data" class="hidden">
                        <input type="file" id="profile-picture-input" name="profile_picture" accept="image/*" onchange="this.form.submit()">
                    </form>
                    
                    <div class="ml-6">
                        <p class="text-3xl font-bold"><?php echo $_SESSION['users_username']; ?></p>
                        <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($user['role']); ?></p>
                        <button onclick="document.getElementById('profile-picture-input').click()" 
                                class="mt-2 text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1 transition-all duration-300">
                            <i class="fas fa-edit"></i>
                            Change Profile Picture
                        </button>
                    </div>
                </div>

                <!-- User Information -->
                <div class="mt-6">
                    <div class="flex items-center mb-4 space-x-3 text-purple-700">
                        <i class="far fa-user text-xl"></i>
                        <h2 class="text-2xl font-poppins text-black">User Information</h2>
                    </div>
                    
                    <div class="overflow-x-auto w-full transition-all duration-500">
                        <table class="w-full table-auto bg-white mt-4">
                            <thead>
                                <tr class="text-blue-800 uppercase text-sm leading-normal text-left">
                                    <th class="px-4 py-2">Field</th>
                                    <th class="px-4 py-2">Details</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-900 text-sm font-light">
                                <tr class="border-b hover:bg-gray-100 transition-all duration-300">
                                    <td class="py-3 px-4 font-semibold">Full Name</td>
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($user['gname'] . ' ' . $user['minitial'] . '. ' . $user['surname']); ?></td>
                                </tr>
                                <tr class="border-b hover:bg-gray-100 transition-all duration-300">
                                    <td class="py-3 px-4 font-semibold">Address</td>
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($user['address']); ?></td>
                                </tr>
                                <tr class="border-b hover:bg-gray-100 transition-all duration-300">
                                    <td class="py-3 px-4 font-semibold">Age</td>
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($user['age']); ?></td>
                                </tr>
                                <tr class="border-b hover:bg-gray-100 transition-all duration-300">
                                    <td class="py-3 px-4 font-semibold">Contact</td>
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($user['contact']); ?></td>
                                </tr>
                                <tr class="border-b hover:bg-gray-100 transition-all duration-300">
                                    <td class="py-3 px-4 font-semibold">Email</td>
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($user['email']); ?></td>
                                </tr>
                                <tr class="border-b hover:bg-gray-100 transition-all duration-300">
                                    <td class="py-3 px-4 font-semibold">Role</td>
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($user['role']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- PIN Change Form -->
                <div class="mt-8">
                    <div class="flex items-center mb-4 space-x-3 text-purple-700">
                        <i class="fas fa-lock text-xl"></i>
                        <h2 class="text-2xl font-poppins text-black">Change PIN</h2>
                    </div>

                    <?php if (isset($error)) : ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($success)) : ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="mt-4 bg-gray-50 p-6 rounded-lg border border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current PIN</label>
                                <input type="password" name="current_pin" pattern="\d{6}" title="PIN must be exactly 6 digits" required 
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300" />
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">New PIN (6 digits)</label>
                                <input type="password" name="new_pin" pattern="\d{6}" title="PIN must be exactly 6 digits" required 
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300" />
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New PIN</label>
                                <input type="password" name="confirm_pin" pattern="\d{6}" title="PIN must be exactly 6 digits" required 
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300" />
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" name="change_pin" 
                                class="bg-blue-500 text-white px-6 py-3 rounded-lg flex items-center gap-2 hover:bg-blue-700 transition-all duration-300 font-medium">
                                <i class="fas fa-key"></i>
                                Change PIN
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-submit form when file is selected
        document.getElementById('profile-picture-input').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                // Show loading state
                const overlay = document.querySelector('.profile-picture-overlay');
                overlay.innerHTML = '<i class="fas fa-spinner fa-spin profile-picture-icon"></i>';
                
                // Submit the form
                document.getElementById('profile-picture-form').submit();
            }
        });
        
        // Add click event to the profile picture container
        document.querySelector('.profile-picture-container').addEventListener('click', function() {
            document.getElementById('profile-picture-input').click();
        });
    </script>
</body>

</html>