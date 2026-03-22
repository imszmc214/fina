<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Access Denied</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md text-center">
        <div class="text-red-500 text-6xl mb-4">⛔</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Access Denied</h1>
        <p class="text-gray-600 mb-4">You don't have permission to access this page.</p>
        <a href="javascript:history.back()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Go Back
        </a>
        <a href="logout.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 ml-2">
            Logout
        </a>
    </div>
</body>
</html>