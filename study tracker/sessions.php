<?php 
require_once 'db_connect.php'; 

// Handle Add Session Post
$alert = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_session'])) {
    $subject_id = $_POST['subject_id'];
    $duration = $_POST['duration'];
    $date = $_POST['study_date'];
    $notes = $_POST['notes'];

    try {
        $stmt = $pdo->prepare("INSERT INTO sessions (subject_id, duration_minutes, study_date, notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$subject_id, $duration, $date, $notes]);
        $alert = '<div style="background: rgba(34, 197, 94, 0.2); color: #22c55e; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem; border: 1px solid #22c55e;">Session Logged Successfully!</div>';
    } catch (PDOException $e) {
        $alert = '<div style="background: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem; border: 1px solid #ef4444;">Error: ' . $e->getMessage() . '</div>';
    }
}

// Fetch Subjects
$stmt = $pdo->query("SELECT * FROM subjects ORDER BY name");
$subjects = $stmt->fetchAll();

// Fetch All Sessions
$sessions_stmt = $pdo->query("
    SELECT s.*, sub.name as subject_name, sub.color
    FROM sessions s
    JOIN subjects sub ON s.subject_id = sub.id
    ORDER BY s.study_date DESC, s.created_at DESC
");
$all_sessions = $sessions_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Logs - O/L Master Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <header>
        <div class="logo">O/L TRACKER</div>
        <nav class="nav-links">
            <a href="index.php">Dashboard</a>
            <a href="sessions.php" class="active">Logs</a>
            <a href="tasks.php">Tasks</a>
            <a href="timetable.php">Schedule</a>
            <a href="subjects.php">Subjects</a>
            <a href="focus.php" style="background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);">Focus</a>
        </nav>
    </header>

    <?php echo $alert; ?>

    <div class="grid" style="grid-template-columns: 1fr 2fr;">
        <!-- Add Session Form -->
        <div class="input-sidebar">
            <div class="card">
                <h2 style="font-size: 1.5rem;">Log Study Time</h2>
                <p style="color: var(--text-dim); margin-bottom: 2rem;">Every minute counts towards your goal.</p>
                <form action="sessions.php" method="POST">
                    <input type="hidden" name="add_session" value="1">
                    <div class="form-group">
                        <label>Select Subject</label>
                        <select name="subject_id" required>
                            <?php foreach($subjects as $sub): ?>
                                <option value="<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Duration (Minutes)</label>
                        <input type="number" name="duration" required placeholder="e.g. 60" min="1">
                    </div>
                    <div class="form-group">
                        <label>Study Date</label>
                        <input type="date" name="study_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>What did you study?</label>
                        <textarea name="notes" rows="4" placeholder="Briefly mention the topics covered..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i data-lucide="save"></i> SAVE SESSION
                    </button>
                </form>
            </div>
        </div>

        <!-- Session List -->
        <div class="session-history">
            <div class="card" style="padding: 1.5rem;">
                <h3 style="margin-bottom: 2rem;">Recent Sessions</h3>
                <?php if (empty($all_sessions)): ?>
                    <div style="text-align: center; padding: 4rem 2rem;">
                        <i data-lucide="book-open" style="width: 48px; height: 48px; color: #e2e8f0; margin-bottom: 1rem;"></i>
                        <p style="color: var(--text-dim);">Your study log is empty. Time to hit the books!</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Duration</th>
                                <th>Date</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($all_sessions as $sess): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div style="width: 10px; height: 10px; border-radius: 50%; background: <?php echo $sess['color']; ?>;"></div>
                                            <span style="font-weight: 700;"><?php echo htmlspecialchars($sess['subject_name']); ?></span>
                                        </div>
                                    </td>
                                    <td><span style="background: #eef2ff; color: var(--primary); padding: 0.2rem 0.6rem; border-radius: 0.5rem; font-weight: 800; font-size: 0.85rem;"><?php echo $sess['duration_minutes']; ?>m</span></td>
                                    <td style="white-space: nowrap; font-size: 0.9rem; color: var(--text-dim);"><?php echo date('M d, Y', strtotime($sess['study_date'])); ?></td>
                                    <td style="max-width: 250px;">
                                        <span style="font-size: 0.85rem; color: var(--text-dim); line-height: 1.4;">
                                            <?php echo htmlspecialchars($sess['notes'] ?: '-'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

    <div class="music-widget" id="musicWidget">
        <div class="music-wave" id="wave" style="display: none;">
            <div class="wave-bar"></div>
            <div class="wave-bar"></div>
            <div class="wave-bar"></div>
        </div>
        <div class="music-info">STUDY AMBIENCE: OFF</div>
        <audio id="studyAudio" loop>
            <source src="Aventure - A Beautiful Garden (freetouse.com).mp3" type="audio/mpeg">
        </audio>
    </div>

<script>
    lucide.createIcons();
    
    const audio = document.getElementById('studyAudio');
    const widget = document.getElementById('musicWidget');
    const info = widget.querySelector('.music-info');
    const wave = document.getElementById('wave');
    let isPlaying = false;

    function toggleMusic() {
        if (isPlaying) {
            audio.pause();
            info.innerText = "STUDY AMBIENCE: OFF";
            wave.style.opacity = '0.3';
            wave.style.display = 'none';
        } else {
            audio.play().catch(e => console.log("Sound play failed", e));
            info.innerText = "STUDY AMBIENCE: ON";
            wave.style.opacity = '1';
            wave.style.display = 'flex';
        }
        isPlaying = !isPlaying;
    }

    widget.addEventListener('click', toggleMusic);
    document.addEventListener('click', () => { if(!isPlaying) toggleMusic(); }, {once: true});
</script>

</body>
</html>
