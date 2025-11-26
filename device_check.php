<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION["technician_id"])) {
    header("Location: index.php");
    exit;
}

$serialQuery = trim($_GET["serial"] ?? "");
$device = null;
$message = "";

if ($serialQuery !== "") {
    $stmt = $conn->prepare("SELECT * FROM device_tracking WHERE serial_number = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $serialQuery);
        $stmt->execute();
        $device = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$device) {
            $message = "Device not found. Please register it first.";
        }
    } else {
        $message = "Failed to prepare device lookup.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Lookup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100">
    <div class="max-w-4xl mx-auto px-6 py-10 space-y-8">
        <header class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Step 01</p>
                <h1 class="text-3xl font-semibold text-slate-900">Verify Device</h1>
                <p class="text-slate-500">Search for device by serial number before creating a ticket.</p>
            </div>
            <a href="dashboard.php" class="px-4 py-2 rounded-full border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-white transition">Back to Dashboard</a>
        </header>
        <section class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8 space-y-6">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <input type="text" name="serial" value="<?php echo htmlspecialchars($serialQuery); ?>" placeholder="Enter serial number" class="flex-1 rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" required>
                <button type="submit" class="px-6 py-3 rounded-2xl bg-slate-900 text-white font-semibold hover:bg-slate-700 transition">Search</button>
            </form>
            <?php if ($message && !$device): ?>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-800">
                    <?php echo htmlspecialchars($message); ?>
                    <div class="mt-3">
                        <a href="register_device.php?serial=<?php echo urlencode($serialQuery); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900 hover:underline">Register device →</a>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($device): ?>
                <div class="rounded-3xl bg-slate-900 text-white p-8 space-y-4 shadow-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Device Found</p>
                            <h2 class="text-2xl font-semibold"><?php echo htmlspecialchars($device["model"]); ?></h2>
                        </div>
                        <span class="text-sm text-slate-300">Serial <?php echo htmlspecialchars($device["serial_number"]); ?></span>
                    </div>
                    <dl class="grid md:grid-cols-2 gap-6 text-sm text-slate-200">
                        <div>
                            <dt class="text-slate-400">Location</dt>
                            <dd class="text-lg text-white"><?php echo htmlspecialchars($device["location"]); ?></dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Operating System</dt>
                            <dd class="text-lg text-white"><?php echo htmlspecialchars($device["OS"]); ?></dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Date Issued</dt>
                            <dd class="text-lg text-white"><?php echo htmlspecialchars($device["date_issued"]); ?></dd>
                        </div>
                    </dl>
                    <a href="create_ticket.php?device_id=<?php echo urlencode($device["id_device_tracking"]); ?>" class="inline-flex items-center justify-center w-full rounded-2xl bg-white text-slate-900 font-semibold py-3 hover:bg-slate-200 transition">Create Ticket →</a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>

