<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION["technician_id"])) {
    header("Location: index.php");
    exit;
}

$technicianId = isset($_GET["technician_id"]) ? (int) $_GET["technician_id"] : $_SESSION["technician_id"];
$message = "";
$error = "";

$technicians = $conn->query("SELECT id_technician_assignment, first_name, last_name FROM technician_assignment ORDER BY first_name ASC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $selectedTech = (int) ($_POST["technician_id"] ?? 0);
    $remarks = trim($_POST["remarks"] ?? "");
    $status = trim($_POST["status"] ?? "");
    $dateSolved = trim($_POST["date_solved"] ?? "");

    if (!$selectedTech || $remarks === "" || $status === "" || $dateSolved === "") {
        $error = "Please complete all fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO post_service_feedback (id_technician_assignment, remarks, status, date_solved) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isss", $selectedTech, $remarks, $status, $dateSolved);
            if ($stmt->execute()) {
                $message = "Feedback recorded. Ticket flow completed.";
                $technicianId = $selectedTech;
            } else {
                $error = "Failed to save feedback.";
            }
            $stmt->close();
        } else {
            $error = "Unable to prepare feedback statement.";
        }
    }
}

$feedbackList = $conn->query(
    "SELECT psf.id_post_service_feedback, psf.remarks, psf.status, psf.date_solved, ta.first_name, ta.last_name
     FROM post_service_feedback psf
     INNER JOIN technician_assignment ta ON ta.id_technician_assignment = psf.id_technician_assignment
     ORDER BY psf.date_solved DESC LIMIT 10"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post-Service Feedback</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100">
    <div class="max-w-5xl mx-auto px-6 py-10 space-y-8">
        <header class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Step 04</p>
                <h1 class="text-3xl font-semibold text-slate-900">Post-Service Feedback</h1>
                <p class="text-slate-500">Capture final status, customer remarks, and closure details.</p>
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
                    <label for="technician_id" class="block text-sm font-medium text-slate-700 mb-1">Technician</label>
                    <select id="technician_id" name="technician_id" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500">
                        <option value="">Select technician</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?php echo $tech["id_technician_assignment"]; ?>" <?php echo ($technicianId == $tech["id_technician_assignment"]) ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($tech["first_name"] . " " . $tech["last_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="remarks" class="block text-sm font-medium text-slate-700 mb-1">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="4" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" placeholder="Summary of fix and customer satisfaction." required></textarea>
                </div>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                        <input type="text" id="status" name="status" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" placeholder="Completed / Pending approval" required>
                    </div>
                    <div>
                        <label for="date_solved" class="block text-sm font-medium text-slate-700 mb-1">Date Solved</label>
                        <input type="date" id="date_solved" name="date_solved" value="<?php echo date('Y-m-d'); ?>" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-slate-500 focus:ring-slate-500" required>
                    </div>
                </div>
                <div class="flex flex-wrap gap-4">
                    <button type="submit" class="px-6 py-3 rounded-2xl bg-slate-900 text-white font-semibold hover:bg-slate-700 transition">Submit Feedback</button>
                    <a href="dashboard.php" class="px-6 py-3 rounded-2xl border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">Finish</a>
                </div>
            </form>
        </section>
        <section class="bg-white rounded-3xl shadow-sm border border-slate-100">
            <div class="px-8 py-6 border-b border-slate-100">
                <p class="text-sm text-slate-500">Recent closures</p>
                <h3 class="text-xl font-semibold text-slate-900">Feedback log</h3>
            </div>
            <div class="divide-y divide-slate-100">
                <?php if ($feedbackList): ?>
                    <?php foreach ($feedbackList as $entry): ?>
                        <article class="px-8 py-5">
                            <p class="text-sm text-slate-500"><?php echo htmlspecialchars($entry["date_solved"]); ?></p>
                            <p class="text-lg font-semibold text-slate-900"><?php echo htmlspecialchars($entry["status"]); ?></p>
                            <p class="text-sm text-slate-500">By <?php echo htmlspecialchars($entry["first_name"] . " " . $entry["last_name"]); ?></p>
                            <p class="text-slate-600 mt-2"><?php echo htmlspecialchars($entry["remarks"]); ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="px-8 py-6 text-slate-500">No feedback recorded yet.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>

