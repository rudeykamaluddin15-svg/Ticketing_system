<?php
session_start();
require_once __DIR__ . '/config.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    if ($email === "" || $phone === "") {
        $error = "Please provide both email and phone number.";
    } else {
        $stmt = $conn->prepare("SELECT id_technician_assignment, first_name, last_name FROM technician_assignment WHERE email = ? AND phone = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ss", $email, $phone);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($technician = $result->fetch_assoc()) {
                $_SESSION["technician_id"] = $technician["id_technician_assignment"];
                $_SESSION["technician_name"] = $technician["first_name"] . " " . $technician["last_name"];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid credentials. Please try again or sign up.";
            }

            $stmt->close();
        } else {
            $error = "Failed to prepare the login statement.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-6">
    <div class="w-full max-w-5xl grid md:grid-cols-2 gap-8">
        <section class="bg-white shadow-xl rounded-2xl p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <p class="text-sm uppercase tracking-wide text-slate-400">Ticket Intake</p>
                    <h1 class="text-3xl font-semibold text-slate-900">Technician Portal</h1>
                </div>
                <a href="signup.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-full hover:bg-slate-700 transition">
                    Create Account
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
            <form method="POST" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" placeholder="technician@example.com">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                    <input type="text" id="phone" name="phone" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" placeholder="Enter your registered phone number">
                </div>
                <button type="submit" class="w-full rounded-xl bg-slate-900 py-3 text-white font-semibold hover:bg-slate-700 transition">Login</button>
            </form>
        </section>
        <section class="bg-slate-900 text-white rounded-2xl p-8 flex flex-col justify-center shadow-xl">
            <p class="text-sm uppercase tracking-widest text-slate-400 mb-2">Workflow Overview</p>
            <h2 class="text-3xl font-semibold mb-6">Ticketing Lifecycle</h2>
            <ul class="space-y-4 text-slate-100">
                <li class="flex gap-3">
                    <span class="text-slate-400">01</span>
                    <div>
                        <p class="font-semibold">Verify Device</p>
                        <p class="text-sm text-slate-300">Check if the device exists in the system; register new devices instantly.</p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="text-slate-400">02</span>
                    <div>
                        <p class="font-semibold">Create Ticket</p>
                        <p class="text-sm text-slate-300">Capture issues and create tickets linked to the device and reporter.</p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="text-slate-400">03</span>
                    <div>
                        <p class="font-semibold">Assign Technician</p>
                        <p class="text-sm text-slate-300">Automatically assign the right technician to diagnose and fix the issue.</p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="text-slate-400">04</span>
                    <div>
                        <p class="font-semibold">Parts & Feedback</p>
                        <p class="text-sm text-slate-300">Record parts usage when needed and complete post-service feedback to close tickets.</p>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</body>
</html>

