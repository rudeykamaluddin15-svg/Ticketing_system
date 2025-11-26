<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION["technician_id"])) {
    header("Location: index.php");
    exit;
}

$technicianId = $_SESSION["technician_id"];
$technicianName = $_SESSION["technician_name"] ?? "Technician";

$totalDevices = $conn->query("SELECT COUNT(*) AS total FROM device_tracking")->fetch_assoc()["total"] ?? 0;
$totalTickets = $conn->query("SELECT COUNT(*) AS total FROM ticket_intake")->fetch_assoc()["total"] ?? 0;
$openTickets = $conn->query("SELECT COUNT(*) AS total FROM ticket_intake ti LEFT JOIN post_service_feedback psf ON psf.id_technician_assignment = ti.id_technician_assignment WHERE psf.id_post_service_feedback IS NULL")->fetch_assoc()["total"] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100">
    <header class="bg-white shadow-sm">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-slate-400 tracking-[0.2em]">Ticket Intake</p>
                <h1 class="text-2xl font-semibold text-slate-900">Welcome back, <?php echo htmlspecialchars($technicianName); ?></h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="device_check.php" class="px-4 py-2 rounded-full bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700 transition">Start Workflow</a>
                <a href="logout.php" class="px-4 py-2 rounded-full border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 transition">Logout</a>
            </div>
        </div>
    </header>
    <main class="max-w-6xl mx-auto px-6 py-10 space-y-10">
        <section class="grid md:grid-cols-3 gap-6">
            <article class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
                <p class="text-sm text-slate-500 mb-2">Registered Devices</p>
                <p class="text-3xl font-semibold text-slate-900"><?php echo $totalDevices; ?></p>
            </article>
            <article class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
                <p class="text-sm text-slate-500 mb-2">Total Tickets</p>
                <p class="text-3xl font-semibold text-slate-900"><?php echo $totalTickets; ?></p>
            </article>
            <article class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
                <p class="text-sm text-slate-500 mb-2">Open Tickets</p>
                <p class="text-3xl font-semibold text-slate-900"><?php echo $openTickets; ?></p>
            </article>
        </section>
        <section class="bg-slate-900 text-white rounded-3xl p-10 shadow-xl">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <p class="text-sm uppercase tracking-[0.3em] text-slate-400 mb-2">Workflow</p>
                    <h2 class="text-3xl font-semibold mb-4">Resolve tickets with confidence</h2>
                    <p class="text-slate-200 max-w-2xl">Move through the guided steps: confirm the device, capture the issue, assign yourself or teammates, log repairs, optionally track parts usage, and close with customer-facing feedback.</p>
                </div>
                <div class="grid grid-cols-1 gap-3 w-full md:w-80">
                    <a href="device_check.php" class="flex items-center justify-between px-5 py-4 rounded-2xl bg-white/5 hover:bg-white/10 transition">
                        <div>
                            <p class="text-sm text-slate-300">Step 01</p>
                            <p class="text-lg font-semibold text-white">Device Verification</p>
                        </div>
                        <span class="text-white text-xl">→</span>
                    </a>
                    <a href="create_ticket.php" class="flex items-center justify-between px-5 py-4 rounded-2xl bg-white/5 hover:bg-white/10 transition">
                        <div>
                            <p class="text-sm text-slate-300">Step 02</p>
                            <p class="text-lg font-semibold text-white">Create Ticket</p>
                        </div>
                        <span class="text-white text-xl">→</span>
                    </a>
                    <a href="parts.php" class="flex items-center justify-between px-5 py-4 rounded-2xl bg-white/5 hover:bg-white/10 transition">
                        <div>
                            <p class="text-sm text-slate-300">Step 03</p>
                            <p class="text-lg font-semibold text-white">Parts & Repairs</p>
                        </div>
                        <span class="text-white text-xl">→</span>
                    </a>
                    <a href="feedback.php" class="flex items-center justify-between px-5 py-4 rounded-2xl bg-white/5 hover:bg-white/10 transition">
                        <div>
                            <p class="text-sm text-slate-300">Step 04</p>
                            <p class="text-lg font-semibold text-white">Post-Service Feedback</p>
                        </div>
                        <span class="text-white text-xl">→</span>
                    </a>
                </div>
            </div>
        </section>
        <section class="bg-white rounded-3xl shadow-sm border border-slate-100">
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Recent tickets you touched</p>
                    <h3 class="text-xl font-semibold text-slate-900">Latest assignments</h3>
                </div>
                <a href="create_ticket.php" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900 hover:underline">New ticket →</a>
            </div>
            <div class="divide-y divide-slate-100">
                <?php
                $recentTicketsStmt = $conn->prepare("SELECT ti.id_ticket_intake, ti.reported_by, ti.issues_description, ti.date, dt.serial_number FROM ticket_intake ti INNER JOIN device_tracking dt ON dt.id_device_tracking = ti.id_device_tracking WHERE ti.id_technician_assignment = ? ORDER BY ti.date DESC LIMIT 5");
                if ($recentTicketsStmt) {
                    $recentTicketsStmt->bind_param("i", $technicianId);
                    $recentTicketsStmt->execute();
                    $recentTickets = $recentTicketsStmt->get_result();
                    if ($recentTickets->num_rows > 0) {
                        while ($ticket = $recentTickets->fetch_assoc()) {
                            echo '<article class="px-8 py-5 flex items-center justify-between">';
                            echo '<div>';
                            echo '<p class="text-sm text-slate-500">' . htmlspecialchars($ticket["date"]) . ' · Serial: ' . htmlspecialchars($ticket["serial_number"]) . '</p>';
                            echo '<p class="text-lg font-semibold text-slate-900">' . htmlspecialchars($ticket["issues_description"]) . '</p>';
                            echo '<p class="text-sm text-slate-500">Reported by ' . htmlspecialchars($ticket["reported_by"]) . '</p>';
                            echo '</div>';
                            echo '<a href="parts.php?ticket_id=' . urlencode($ticket["id_ticket_intake"]) . '" class="text-sm font-semibold text-slate-900 hover:underline">Continue →</a>';
                            echo '</article>';
                        }
                    } else {
                        echo '<p class="px-8 py-6 text-slate-500">No tickets assigned to you yet.</p>';
                    }
                    $recentTicketsStmt->close();
                } else {
                    echo '<p class="px-8 py-6 text-red-500">Unable to load tickets right now.</p>';
                }
                ?>
            </div>
        </section>
    </main>
</body>
</html>

