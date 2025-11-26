<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION["technician_id"])) {
    header("Location: index.php");
    exit;
}

$serialPrefill = trim($_GET["serial"] ?? "");
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $serial = trim($_POST["serial_number"] ?? "");
    $model = trim($_POST["model"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $os = trim($_POST["os"] ?? "");
    $dateIssued = trim($_POST["date_issued"] ?? "");

    if ($serial === "" || $model === "" || $location === "" || $os === "" || $dateIssued === "") {
        $error = "Please fill out every field.";
    } else {
        $checkStmt = $conn->prepare("SELECT id_device_tracking FROM device_tracking WHERE serial_number = ? LIMIT 1");
        if ($checkStmt) {
            $checkStmt->bind_param("s", $serial);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                $error = "Device already exists. You can proceed directly to ticket creation.";
            } else {
                $insertStmt = $conn->prepare("INSERT INTO device_tracking (serial_number, model, location, OS, date_issued) VALUES (?, ?, ?, ?, ?)");
                if ($insertStmt) {
                    $insertStmt->bind_param("sssss", $serial, $model, $location, $os, $dateIssued);
                    if ($insertStmt->execute()) {
                        $message = "Device registered successfully.";
                    } else {
                        $error = "Failed to register device. Please try again.";
                    }
                    $insertStmt->close();
                } else {
                    $error = "Unable to prepare insert statement.";
                }
            }
            $checkStmt->close();
        } else {
            $error = "Unable to prepare lookup statement.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Device</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100">
    <div class="max-w-4xl mx-auto px-6 py-10 space-y-8">
        <header class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Step 01b</p>
                <h1 class="text-3xl font-semibold text-slate-900">Register New Device</h1>
                <p class="text-slate-500">Add devices that don’t yet exist in the tracking system.</p>
            </div>
            <a href="device_check.php" class="px-4 py-2 rounded-full border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-white transition">Back to Device Lookup</a>
        </header>
        <section class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8 space-y-6">
            <?php if ($error): ?>
                <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-700">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-700">
                    <?php echo htmlspecialchars($message); ?>
                    <div class="mt-3">
                        <a href="create_ticket.php" class="text-sm font-semibold text-emerald-900 hover:underline">Create a ticket now →</a>
                    </div>
                </div>
            <?php endif; ?>
            <form method="POST" class="grid md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="serial_number" class="block text-sm font-medium text-slate-700 mb-1">Serial Number</label>
                    <input type="text" id="serial_number" name="serial_number" value="<?php echo htmlspecialchars($serialPrefill); ?>" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" placeholder="SN-0000-12345">
                </div>
                <div>
                    <label for="model" class="block text-sm font-medium text-slate-700 mb-1">Model</label>
                    <input type="text" id="model" name="model" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" placeholder="Dell Latitude 5520">
                </div>
                <div>
                    <label for="location" class="block text-sm font-medium text-slate-700 mb-1">Location</label>
                    <input type="text" id="location" name="location" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" placeholder="HQ 3F IT Storage">
                </div>
                <div>
                    <label for="os" class="block text-sm font-medium text-slate-700 mb-1">Operating System</label>
                    <input type="text" id="os" name="os" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" placeholder="Windows 11 Pro">
                </div>
                <div>
                    <label for="date_issued" class="block text-sm font-medium text-slate-700 mb-1">Date Issued</label>
                    <input type="date" id="date_issued" name="date_issued" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="w-full rounded-2xl bg-slate-900 py-3 text-white font-semibold hover:bg-slate-700 transition">Save Device</button>
                </div>
            </form>
        </section>
    </div>
</body>
</html>

