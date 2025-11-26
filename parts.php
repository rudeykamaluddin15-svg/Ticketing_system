<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION["technician_id"])) {
    header("Location: index.php");
    exit;
}

$tickets = $conn->query("SELECT ti.id_ticket_intake, ti.issues_description, ti.date, dt.serial_number FROM ticket_intake ti INNER JOIN device_tracking dt ON dt.id_device_tracking = ti.id_device_tracking ORDER BY ti.date DESC")->fetch_all(MYSQLI_ASSOC);

$selectedTicketId = isset($_GET["ticket_id"]) ? (int) $_GET["ticket_id"] : 0;
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ticketId = (int) ($_POST["ticket_id"] ?? 0);
    $partName = trim($_POST["part_name"] ?? "");
    $quantity = (int) ($_POST["quantity"] ?? 0);
    $cost = trim($_POST["cost"] ?? "");
    $date = trim($_POST["date"] ?? "");

    if (!$ticketId || $partName === "" || $quantity <= 0 || $cost === "" || $date === "") {
        $error = "All fields are required and quantity must be positive.";
        $selectedTicketId = $ticketId;
    } else {
        $stmt = $conn->prepare("INSERT INTO part_usage (id_ticket_intake, part_name, quantity, cost, date) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isiss", $ticketId, $partName, $quantity, $cost, $date);
            if ($stmt->execute()) {
                $message = "Part usage recorded successfully.";
                $selectedTicketId = $ticketId;
            } else {
                $error = "Failed to save part usage.";
            }
            $stmt->close();
        } else {
            $error = "Unable to prepare part usage statement.";
        }
    }
}

function getTechnicianIdForTicket(mysqli $conn, int $ticketId): ?int {
    $stmt = $conn->prepare("SELECT id_technician_assignment FROM ticket_intake WHERE id_ticket_intake = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        $stmt->bind_result($techId);
        if ($stmt->fetch()) {
            $stmt->close();
            return $techId;
        }
        $stmt->close();
    }
    return null;
}

$technicianForSelectedTicket = $selectedTicketId ? getTechnicianIdForTicket($conn, $selectedTicketId) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parts Usage</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100">
    <div class="max-w-5xl mx-auto px-6 py-10 space-y-8">
        <header class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Step 03</p>
                <h1 class="text-3xl font-semibold text-slate-900">Parts Decision & Usage</h1>
                <p class="text-slate-500">If repairs required replacement parts, log them here before closing the ticket.</p>
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
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-700">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="grid gap-6">
                <div>
                    <label for="ticket_id" class="block text-sm font-medium text-slate-700 mb-1">Ticket</label>
                    <select id="ticket_id" name="ticket_id" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" onchange="location.href='parts.php?ticket_id=' + this.value;">
                        <option value="">Select ticket</option>
                        <?php foreach ($tickets as $ticket): ?>
                            <option value="<?php echo $ticket["id_ticket_intake"]; ?>" <?php echo ($selectedTicketId == $ticket["id_ticket_intake"]) ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars("Ticket #" . $ticket["id_ticket_intake"] . " · " . $ticket["serial_number"] . " · " . $ticket["issues_description"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="part_name" class="block text-sm font-medium text-slate-700 mb-1">Part Name</label>
                        <input type="text" id="part_name" name="part_name" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" placeholder="SSD 512GB" required>
                    </div>
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-slate-700 mb-1">Quantity</label>
                        <input type="number" id="quantity" name="quantity" min="1" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" required>
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="cost" class="block text-sm font-medium text-slate-700 mb-1">Cost</label>
                        <input type="text" id="cost" name="cost" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" placeholder="PHP 2,500" required>
                    </div>
                    <div>
                        <label for="date" class="block text-sm font-medium text-slate-700 mb-1">Date Used</label>
                        <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" required>
                    </div>
                </div>
                <div class="flex flex-wrap gap-4">
                    <button type="submit" class="px-6 py-3 rounded-2xl bg-slate-900 text-white font-semibold hover:bg-slate-700 transition">Save Part Usage</button>
                    <?php if ($selectedTicketId && $technicianForSelectedTicket): ?>
                        <a href="feedback.php?technician_id=<?php echo $technicianForSelectedTicket; ?>" class="px-6 py-3 rounded-2xl border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">No more parts required</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
    </div>
</body>
</html>

