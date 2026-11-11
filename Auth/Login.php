<?php
// auth/login.php
require_once __DIR__ . '/../helpers/auth_guard.php';
requireGuest();

$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IT Helpdesk System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-4 font-sans text-slate-800">

    <div class="w-full max-w-md bg-white rounded-xl shadow-lg border border-slate-200 p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">IT Helpdesk Portal</h1>
            <p class="text-sm text-slate-500 mt-1">Please login to raise or manage ticket</p>
        </div>

        <?php if ($error === 'invalid_credentials'): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                Emel & Password invalid.
            </div>
        <?php elseif ($error === 'empty_fields'): ?>
            <div class="mb-4 p-3 bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-lg">
                Please complete all form.
            </div>
        <?php elseif ($error === 'unauthorized'): ?>
            <div class="mb-4 p-3 bg-slate-100 border border-slate-300 text-slate-700 text-sm rounded-lg">
                Session ended, please login again.
            </div>
        <?php endif; ?>

        <form action="auth_process.php" method="POST" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">E-mel</label>
                <input type="email" id="email" name="email" required
                       placeholder="nama@helpdesk.local"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input type="password" id="password" name="password" required
                       placeholder="••••••••"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
            </div>

            <button type="submit" 
                    class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg text-sm transition shadow-sm">
                Log Masuk
            </button>
        </form>

        <!-- Kotak Info Akaun Ujian (Bagus untuk Portfolio GitHub) -->
        <div class="mt-8 pt-6 border-t border-slate-200">
            <h2 class="text-xs font-semibold uppercase text-slate-400 tracking-wider mb-2">Akaun Ujian (Demo)</h2>
            <div class="text-xs text-slate-600 space-y-1 bg-slate-50 p-3 rounded-lg border border-slate-200">
                <p><span class="font-semibold text-slate-800">Admin:</span> admin@helpdesk.local | <code class="bg-slate-200 px-1 rounded">admin123</code></p>
                <p><span class="font-semibold text-slate-800">Agent:</span> agent@helpdesk.local | <code class="bg-slate-200 px-1 rounded">admin123</code></p>
                <p><span class="font-semibold text-slate-800">User:</span> user@helpdesk.local | <code class="bg-slate-200 px-1 rounded">admin123</code></p>
            </div>
        </div>
    </div>

</body>
</html>