<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION["technician_id"])) {
    header("Location: index.php");
    exit;
}

$selectedDeviceId = isset($_GET["device_id"]) ? (int) $_GET["device_id"] : 0;
$message = "";
$error = "";
$newTicketId = null;

$devices = $conn->query("SELECT id_device_tracking, serial_number, model FROM device_tracking ORDER BY model ASC")->fetch_all(MYSQLI_ASSOC);
$technicians = $conn->query("SELECT id_technician_assignment, first_name, last_name FROM technician_assignment ORDER BY first_name ASC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $deviceId = (int) ($_POST["device_id"] ?? 0);
    $technicianId = (int) ($_POST["technician_id"] ?? 0);
    $reportedBy = trim($_POST["reported_by"] ?? "");
    $issue = trim($_POST["issues_description"] ?? "");
    $ticketDate = trim($_POST["date"] ?? "");

    if (!$deviceId || !$technicianId || $reportedBy === "" || $issue === "" || $ticketDate === "") {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO ticket_intake (id_device_tracking, id_technician_assignment, reported_by, issues_description, date) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iisss", $deviceId, $technicianId, $reportedBy, $issue, $ticketDate);
            if ($stmt->execute()) {
                $newTicketId = $stmt->insert_id;
                $message = "Ticket created and technician assigned.";
                $selectedDeviceId = 0;
            } else {
                $error = "Failed to save ticket. Please try again.";
            }
            $stmt->close();
        } else {
            $error = "Unable to prepare ticket statement.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Ticket</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100">
    <div class="max-w-5xl mx-auto px-6 py-10 space-y-8">
        <header class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Step 02</p>
                <h1 class="text-3xl font-semibold text-slate-900">Create Ticket & Assign Technician</h1>
                <p class="text-slate-500">Capture reported issues, link a device, and select the technician responsible.</p>
            </div>
            <a href="dashboard.php" class="px-4 py-2 rounded-full border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-white transition">Back to Dashboard</a>
        </header>
        <section class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8 space-y-6">
            <?php if ($error): ?>
                <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-700">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-700 space-y-3">
                    <p><?php echo htmlspecialchars($message); ?></p>
                    <?php if ($newTicketId): ?>
                        <div class="flex flex-wrap gap-3">
                            <a href="parts.php?ticket_id=<?php echo $newTicketId; ?>" class="px-4 py-2 rounded-full bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-500 transition">Continue to parts decision</a>
                            <a href="feedback.php?technician_id=<?php echo (int) $_POST["technician_id"]; ?>" class="px-4 py-2 rounded-full border border-emerald-200 text-emerald-900 text-sm font-semibold hover:bg-emerald-100 transition">Skip to feedback</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="grid gap-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="device_id" class="block text-sm font-medium text-slate-700 mb-1">Device</label>
                        <select id="device_id" name="device_id" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500">
                            <option value="">Select device</option>
                            <?php foreach ($devices as $device): ?>
                                <option value="<?php echo $device["id_device_tracking"]; ?>" <?php echo ($selectedDeviceId == $device["id_device_tracking"]) ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($device["serial_number"] . " · " . $device["model"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="technician_id" class="block text-sm font-medium text-slate-700 mb-1">Technician</label>
                        <select id="technician_id" name="technician_id" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500">
                            <option value="">Select technician</option>
                            <?php foreach ($technicians as $tech): ?>
                                <option value="<?php echo $tech["id_technician_assignment"]; ?>" <?php echo ($tech["id_technician_assignment"] == ($_POST["technician_id"] ?? $_SESSION["technician_id"])) ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($tech["first_name"] . " " . $tech["last_name"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="reported_by" class="block text-sm font-medium text-slate-700 mb-1">Reported By</label>
                        <input type="text" id="reported_by" name="reported_by" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" placeholder="Name of requester">
                    </div>
                    <div>
                        <label for="date" class="block text-sm font-medium text-slate-700 mb-1">Date</label>
                        <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                </div>
                <div>
                    <label for="issues_description" class="block text-sm font-medium text-slate-700 mb-1">Issue Description</label>
                    <textarea id="issues_description" name="issues_description" rows="4" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" placeholder="Describe the reported problem in detail."></textarea>
                </div>
                <div class="flex flex-wrap gap-4">
                    <button type="submit" class="px-6 py-3 rounded-2xl bg-slate-900 text-white font-semibold hover:bg-slate-700 transition">Create Ticket</button>
                    <a href="device_check.php" class="px-6 py-3 rounded-2xl border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">Lookup another device</a>
                </div>
            </form>
        </section>
    </div>
</body>
</html>

