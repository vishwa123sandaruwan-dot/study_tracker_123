<?php 
require_once 'db_connect.php'; 

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_slot'])) {
    $day = $_POST['day_of_week'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $subject_id = $_POST['subject_id'];
    
    $stmt = $pdo->prepare("INSERT INTO timetable (day_of_week, start_time, end_time, subject_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$day, $start, $end, $subject_id]);
    header("Location: timetable.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM timetable WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: timetable.php");
    exit();
}

// Fetch subjects
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();

// Fetch timetable entries
$timetable_raw = $pdo->query("
    SELECT t.*, s.name as subject_name, s.color 
    FROM timetable t
    JOIN subjects s ON t.subject_id = s.id
    ORDER BY start_time ASC
")->fetchAll();

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$timetable = [];
foreach($days as $day) $timetable[$day] = [];
foreach($timetable_raw as $entry) {
    $timetable[$entry['day_of_week']][] = $entry;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Timetable - O/L Master</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        .timetable-container {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1rem;
            overflow-x: auto;
            min-width: 900px;
        }
        .day-column {
            background: rgba(30, 41, 59, 1);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1rem;
            min-height: 400px;
        }
        .day-header {
            font-weight: 800;
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .slot-card {
            background: rgba(15, 23, 42, 0.5);
            border-radius: 0.75rem;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary);
            position: relative;
            transition: all 0.2s;
        }
        .slot-card:hover {
            transform: scale(1.02);
            background: rgba(15, 23, 42, 0.8);
        }
        .slot-time {
            font-size: 0.7rem;
            color: var(--text-dim);
            font-weight: 700;
        }
        .slot-subject {
            font-size: 0.9rem;
            font-weight: 700;
            margin: 0.2rem 0;
            color: white;
        }
        .delete-slot {
            position: absolute;
            top: 5px;
            right: 5px;
            color: var(--text-dim);
            opacity: 0;
        }
        .slot-card:hover .delete-slot {
            opacity: 1;
        }
        .delete-btn-slot {
            padding: 2px;
            border-radius: 4px;
            color: #ef4444;
        }
        .add-form-card {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 1.5rem;
            margin-bottom: 3rem;
            box-shadow: var(--shadow-lg);
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div class="logo">O/L TRACKER</div>
        <nav class="nav-links">
            <a href="index.php">Dashboard</a>
            <a href="sessions.php">Logs</a>
            <a href="tasks.php">Tasks</a>
            <a href="timetable.php" class="active">Schedule</a>
            <a href="subjects.php">Subjects</a>
            <a href="focus.php" style="background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);">Focus</a>
        </nav>
    </header>

    <div class="main-content">
        <h1 style="margin-bottom: 2rem;">Your Study Schedule</h1>

        <div class="add-form-card card">
            <h3 style="margin-bottom: 1.5rem;">Add Routine Slot</h3>
            <form action="timetable.php" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; align-items: flex-end;">
                <input type="hidden" name="add_slot" value="1">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Day</label>
                    <select name="day_of_week" required>
                        <?php foreach($days as $d): ?>
                            <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Start Time</label>
                    <input type="time" name="start_time" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>End Time</label>
                    <input type="time" name="end_time" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Subject</label>
                    <select name="subject_id" required>
                        <?php foreach($subjects as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="height: 48px;">
                    <i data-lucide="plus"></i> ADD SLOT
                </button>
            </form>
        </div>

        <div class="timetable-wrapper" style="overflow-x: auto; padding-bottom: 2rem;">
            <div class="timetable-container">
                <?php foreach($days as $day): ?>
                    <div class="day-column">
                        <div class="day-header"><?php echo substr($day, 0, 3); ?></div>
                        <?php foreach($timetable[$day] as $slot): ?>
                            <div class="slot-card" style="border-left-color: <?php echo $slot['color']; ?>;">
                                <div class="slot-time"><?php echo date('H:i', strtotime($slot['start_time'])); ?> - <?php echo date('H:i', strtotime($slot['end_time'])); ?></div>
                                <div class="slot-subject"><?php echo htmlspecialchars($slot['subject_name']); ?></div>
                                <div class="delete-slot">
                                    <a href="timetable.php?delete=<?php echo $slot['id']; ?>" onclick="return confirm('Remove slot?')" class="delete-btn-slot">
                                        <i data-lucide="x" style="width: 14px;"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    // Notification Logic
    if (Notification.permission === "default") {
        Notification.requestPermission();
    }

    const timetableData = <?php echo json_encode($timetable_raw); ?>;
    let notifiedSlots = new Set();

    function checkTimetable() {
        if (Notification.permission !== "granted") return;

        const now = new Date();
        const currentDay = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][now.getDay()];
        const currentTime = now.getHours().toString().padStart(2, '0') + ":" + now.getMinutes().toString().padStart(2, '0');

        timetableData.forEach(slot => {
            if (slot.day_of_week === currentDay) {
                // Remove seconds from time for comparison
                const startTimeShort = slot.start_time.substring(0, 5);
                const slotKey = `${slot.id}-${startTimeShort}`;

                if (currentTime === startTimeShort && !notifiedSlots.has(slotKey)) {
                    new Notification("📚 Study Time!", {
                        body: `Time to start studying ${slot.subject_name}!`,
                        icon: "https://cdn-icons-png.flaticon.com/512/3233/3233483.png"
                    });
                    notifiedSlots.add(slotKey);
                }
            }
        });

        // Clear notifiedSlots every hour to prevent memory creep (simple reset)
        if (now.getMinutes() === 59) notifiedSlots.clear();
    }

    setInterval(checkTimetable, 60000); // Check every minute
    checkTimetable(); // Initial check
</script>

</body>
</html>
