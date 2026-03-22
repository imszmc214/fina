<?php
// token management.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Fixed authentication
$ADMIN_USER = 'admin';
$ADMIN_PASS = 'admin123';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($username === $ADMIN_USER && $password === $ADMIN_PASS) {
            $_SESSION['admin_logged_in'] = true;
            header("Location: token management.php");
            exit;
        } else {
            $login_error = "Invalid username or password.";
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login | Token Management</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; }
            .glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
        </style>
    </head>
    <body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full glass p-8 rounded-2xl shadow-xl border border-slate-200">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-slate-900">Token Management</h1>
                <p class="text-slate-500 mt-2">Secure access to API administration</p>
            </div>

            <?php if (isset($login_error)): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded-lg text-sm mb-6 border border-red-100 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <?= $login_error ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Username</label>
                    <input type="text" name="username" required 
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all"
                           placeholder="Enter username">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                    <input type="password" name="password" required 
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all"
                           placeholder="••••••••">
                </div>
                <button type="submit" name="login" 
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
                    Sign In
                </button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

include('connection.php');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_token'])) {
        $token = bin2hex(random_bytes(16)); // Auto-generate secure token
        $token_name = $_POST['token_name'];
        $department = $_POST['department'];
        $description = $_POST['description'];
        $callback_url = $_POST['callback_url'] ?? null;
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        
        $stmt = $conn->prepare("INSERT INTO department_tokens (token, token_name, department, description, expires_at, callback_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $token, $token_name, $department, $description, $expires_at, $callback_url);
        $stmt->execute();
        $_SESSION['success_msg'] = "Token generated successfully!";
        header("Location: token management.php");
        exit;
    } elseif (isset($_POST['toggle_token'])) {
        $token_id = $_POST['token_id'];
        $is_active = $_POST['is_active'];
        
        $stmt = $conn->prepare("UPDATE department_tokens SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $is_active, $token_id);
        $stmt->execute();
        header("Location: token management.php");
        exit;
    } elseif (isset($_POST['delete_token'])) {
        $token_id = $_POST['token_id'];
        
        $stmt = $conn->prepare("DELETE FROM department_tokens WHERE id = ?");
        $stmt->bind_param("i", $token_id);
        $stmt->execute();
        header("Location: token management.php");
        exit;
    } elseif (isset($_POST['logout'])) {
        session_destroy();
        header("Location: token management.php");
        exit;
    }
}

// Fetch all tokens
$tokens = $conn->query("SELECT * FROM department_tokens ORDER BY department, created_at DESC");
$departments = ['Administrative', 'Core-1', 'Core-2', 'Human Resource-1', 'Human Resource-2', 'Human Resource-3', 'Human Resource-4', 'Logistic-1', 'Logistic-2', 'Financial'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Management | Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .token-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .token-card:hover { transform: translateY(-4px); }
        .glass-header { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    <!-- Navbar -->
    <nav class="sticky top-0 z-50 glass-header border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-md">
                        <i class="fas fa-key text-white"></i>
                    </div>
                    <span class="text-xl font-bold text-slate-900 tracking-tight">TokenManager</span>
                </div>
                <div class="flex items-center">
                    <form method="POST">
                        <button type="submit" name="logout" class="flex items-center gap-2 text-slate-500 hover:text-red-600 font-medium transition-colors">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div id="successAlert" class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-6 py-4 rounded-2xl mb-8 flex justify-between items-center animate-bounce">
                <div class="flex items-center gap-3">
                    <i class="fas fa-check-circle text-xl"></i>
                    <span class="font-semibold"><?= $_SESSION['success_msg'] ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-600"><i class="fas fa-times"></i></button>
            </div>
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left: Stats & Instructions -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                    <h2 class="text-lg font-bold text-slate-900 mb-4">Quick Stats</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-indigo-50 p-4 rounded-2xl">
                            <div class="text-indigo-600 text-xs font-bold uppercase tracking-wider mb-1">Total Tokens</div>
                            <div class="text-2xl font-bold text-indigo-900"><?= $tokens->num_rows ?></div>
                        </div>
                        <div class="bg-emerald-50 p-4 rounded-2xl">
                            <div class="text-emerald-600 text-xs font-bold uppercase tracking-wider mb-1">Active</div>
                            <div class="text-2xl font-bold text-emerald-900">
                                <?php
                                $count = 0;
                                $tokens->data_seek(0);
                                while($row = $tokens->fetch_assoc()) if($row['is_active']) $count++;
                                echo $count;
                                $tokens->data_seek(0);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-indigo-600 p-8 rounded-3xl shadow-lg relative overflow-hidden">
                    <div class="relative z-10">
                        <h2 class="text-white text-xl font-bold mb-3">API Integration</h2>
                        <p class="text-indigo-100 text-sm leading-relaxed mb-6">Use these tokens to authenticate external requests. Include the token in your HTTP headers:</p>
                        <div class="bg-indigo-900/40 rounded-xl p-4 font-mono text-xs text-indigo-200 border border-indigo-400/30">
                            X-API-Token: YOUR_TOKEN_HERE
                        </div>
                    </div>
                    <div class="absolute -right-8 -bottom-8 opacity-10">
                        <i class="fas fa-terminal text-9xl text-white"></i>
                    </div>
                </div>
            </div>

            <!-- Right: Add Token Form -->
            <div class="lg:col-span-2">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 h-full">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900">Generate New Access Token</h2>
                            <p class="text-slate-500 mt-1">Create a unique identifier for department-level API access.</p>
                        </div>
                    </div>
                    
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-slate-700">Token Name</label>
                                <input type="text" name="token_name" required 
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white outline-none transition-all placeholder:text-slate-400"
                                       placeholder="e.g. ERP Integration">
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-slate-700">Department</label>
                                <select name="department" required 
                                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white outline-none transition-all appearance-none">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept ?>"><?= $dept ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-slate-700">Callback URL <span class="text-slate-400 font-normal">(For Webhooks)</span></label>
                                <input type="url" name="callback_url" 
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white outline-none transition-all placeholder:text-slate-400"
                                       placeholder="https://partner-system.com/api/webhook">
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-slate-700">Expiration Date <span class="text-slate-400 font-normal">(Optional)</span></label>
                                <input type="datetime-local" name="expires_at" 
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white outline-none transition-all">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-slate-700">Description</label>
                            <textarea name="description" rows="3" 
                                      class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white outline-none transition-all placeholder:text-slate-400"
                                      placeholder="What is this token for?"></textarea>
                        </div>
                        <div class="pt-2">
                            <button type="submit" name="add_token" 
                                    class="bg-indigo-600 hover:bg-slate-900 text-white font-bold px-8 py-3 rounded-xl shadow-lg transition-all flex items-center gap-2 group">
                                <i class="fas fa-bolt text-amber-300 group-hover:scale-125 transition-transform"></i>
                                Generate Secure Token
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Token Table Section -->
        <div class="mt-8">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h2 class="text-xl font-bold text-slate-900">Existing Access Tokens</h2>
                    <div class="text-sm text-slate-500 bg-white px-3 py-1 rounded-full border border-slate-200 font-medium">
                        Showing <?= $tokens->num_rows ?> records
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50 text-slate-500 text-xs font-bold uppercase tracking-widest border-b border-slate-100">
                                <th class="px-8 py-4">Department & Info</th>
                                <th class="px-8 py-4">Token Key</th>
                                <th class="px-8 py-4 text-center">Status</th>
                                <th class="px-8 py-4">Usage Details</th>
                                <th class="px-8 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php while($token = $tokens->fetch_assoc()): ?>
                            <tr class="group hover:bg-slate-50/80 transition-colors">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-400">
                                            <i class="fas fa-building text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900"><?= htmlspecialchars($token['token_name']) ?></div>
                                            <div class="text-xs font-semibold text-indigo-600 uppercase"><?= $token['department'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-2 group/token">
                                        <code class="px-3 py-1 bg-slate-100 rounded-lg font-mono text-xs text-slate-600 select-all border border-slate-200">
                                            <?= htmlspecialchars($token['token']) ?>
                                        </code>
                                        <button onclick="navigator.clipboard.writeText('<?= $token['token'] ?>')" class="opacity-0 group-hover/token:opacity-100 text-slate-400 hover:text-indigo-600 transition-all p-1">
                                            <i class="far fa-copy"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold ring-1 ring-inset <?= $token['is_active'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-red-50 text-red-700 ring-red-600/20' ?>">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?= $token['is_active'] ? 'bg-emerald-600' : 'bg-red-600' ?> animate-pulse"></span>
                                        <?= $token['is_active'] ? 'Active' : 'Disabled' ?>
                                    </span>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="space-y-1">
                                        <div class="flex items-center text-xs text-slate-500">
                                            <i class="fas fa-clock w-4"></i>
                                            <span>Expires: <?= $token['expires_at'] ?: 'Never' ?></span>
                                        </div>
                                        <div class="flex items-center text-xs text-slate-500">
                                            <i class="fas fa-link w-4"></i>
                                            <span class="truncate max-w-[150px]" title="<?= $token['callback_url'] ?: 'No callback URL' ?>">
                                                <?= $token['callback_url'] ?: '<span class="italic opacity-50">Not Set</span>' ?>
                                            </span>
                                        </div>
                                        <div class="flex items-center text-xs text-slate-500">
                                            <i class="fas fa-chart-line w-4"></i>
                                            <span>Usage: <span class="font-bold text-slate-700"><?= $token['usage_count'] ?></span> hits</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <div class="flex justify-end gap-2">
                                        <form method="POST">
                                            <input type="hidden" name="token_id" value="<?= $token['id'] ?>">
                                            <input type="hidden" name="is_active" value="<?= $token['is_active'] ? 0 : 1 ?>">
                                            <button type="submit" name="toggle_token" 
                                                    class="p-2 rounded-xl border border-slate-200 text-slate-400 hover:border-indigo-600 hover:text-indigo-600 hover:bg-white transition-all"
                                                    title="<?= $token['is_active'] ? 'Disable' : 'Enable' ?>">
                                                <i class="fas <?= $token['is_active'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('WARNING: Are you sure? Any integrations using this token will break immediately.');">
                                            <input type="hidden" name="token_id" value="<?= $token['id'] ?>">
                                            <button type="submit" name="delete_token" 
                                                    class="p-2 rounded-xl border border-slate-200 text-slate-400 hover:border-red-600 hover:text-red-600 hover:bg-white transition-all"
                                                    title="Delete Token">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if ($tokens->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" class="px-8 py-12 text-center">
                                    <div class="text-slate-300 mb-2 mt-4">
                                        <i class="fas fa-folder-open text-5xl"></i>
                                    </div>
                                    <p class="text-slate-500 font-medium">No tokens found. Start by generating one above.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <footer class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 border-t border-slate-200 mt-8">
        <p class="text-center text-slate-400 text-sm">
            &copy; <?= date('Y') ?> TokenManager Admin System. All rights reserved.
        </p>
    </footer>

    <script>
        // Auto-remove alert after 5s
        const alert = document.getElementById('successAlert');
        if (alert) {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                alert.style.transition = 'all 0.5s ease';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        }
    </script>
</body>
</html>