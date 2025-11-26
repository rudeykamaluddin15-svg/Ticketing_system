<?php
require_once __DIR__ . '/config.php';

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if ($firstName === "" || $lastName === "" || $phone === "" || $email === "") {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO technician_assignment (first_name, last_name, phone, email) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $firstName, $lastName, $phone, $email);
            if ($stmt->execute()) {
                $success = "Technician registered successfully. You can now log in.";
            } else {
                if ($conn->errno === 1062) {
                    $error = "Technician with the provided email already exists.";
                } else {
                    $error = "Failed to register technician. Please try again.";
                }
            }
            $stmt->close();
        } else {
            $error = "Failed to prepare the signup statement.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Signup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-6">
    <div class="w-full max-w-3xl bg-white rounded-2xl shadow-xl p-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <p class="text-sm uppercase tracking-wide text-slate-400">Ticket Intake</p>
                <h1 class="text-3xl font-semibold text-slate-900">Create Technician Account</h1>
            </div>
            <a href="index.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-full hover:bg-slate-700 transition">
                Back to Login
            </a>
        </div>
        <?php if ($error): ?>
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="grid md:grid-cols-2 gap-6">
            <div>
                <label for="first_name" class="block text-sm font-medium text-slate-700 mb-1">First Name</label>
                <input type="text" id="first_name" name="first_name" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" placeholder="Juan">
            </div>
            <div>
                <label for="last_name" class="block text-sm font-medium text-slate-700 mb-1">Last Name</label>
                <input type="text" id="last_name" name="last_name" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" placeholder="Dela Cruz">
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                <input type="text" id="phone" name="phone" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" placeholder="+63 900 000 0000">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" placeholder="technician@example.com">
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="w-full rounded-xl bg-slate-900 py-3 text-white font-semibold hover:bg-slate-700 transition">Sign Up</button>
            </div>
        </form>
    </div>
</body>
</html>

