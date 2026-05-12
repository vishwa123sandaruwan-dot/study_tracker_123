<?php 
require_once 'db_connect.php'; 

// Handle Subject Add/Delete
if (isset($_GET['delete_subject'])) {
    $id = $_GET['delete_subject'];
    $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: subjects.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subject'])) {
    $name = trim($_POST['name']);
    $color = $_POST['color'];
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO subjects (name, color) VALUES (?, ?)");
        $stmt->execute([$name, $color]);
        header("Location: subjects.php");
        exit();
    }
}

// Handle User Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $name = $_POST['user_name'];
    $date = $_POST['exam_date'];
    $stmt = $pdo->prepare("UPDATE users SET name = ?, exam_date = ? LIMIT 1");
    $stmt->execute([$name, $date]);
    header("Location: subjects.php");
    exit();
}

// Fetch Subjects with Progress
$stmt = $pdo->query("
    SELECT sub.*, 
    (SELECT COALESCE(SUM(duration_minutes), 0) FROM sessions WHERE subject_id = sub.id) as total_mins,
    (SELECT COUNT(*) FROM sessions WHERE subject_id = sub.id) as session_count
    FROM subjects sub
    ORDER BY total_mins DESC
");
$all_subjects = $stmt->fetchAll();

// Fetch Data for Growth Chart (Last 7 Days)
$growth_query = $pdo->query("
    SELECT study_date, SUM(duration_minutes) as daily_mins 
    FROM sessions 
    WHERE study_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY study_date 
    ORDER BY study_date ASC
");
$growth_data = $growth_query->fetchAll();

// Prepare JS arrays for chart
$dates = [];
$mins = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('M d', strtotime($d));
    $found = false;
    foreach ($growth_data as $row) {
        if ($row['study_date'] == $d) {
            $mins[] = (int)$row['daily_mins'];
            $found = true;
            break;
        }
    }
    if (!$found) $mins[] = 0;
}

// Fetch User
$user_stmt = $pdo->query("SELECT * FROM users LIMIT 1");
$user = $user_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Subjects - O/L Master Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        .subject-card-light {
            background: #1e293b;
            padding: 1.25rem 1.5rem;
            border-radius: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            transition: all 0.2s;
            border: 1px solid var(--border);
        }
        .subject-card-light:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            background: #243048;
        }
        .progress-mini-bar {
            height: 6px;
            background: #0f172a;
            border-radius: 3px;
            margin-top: 0.75rem;
            overflow: hidden;
            width: 100%;
        }
        .progress-mini-fill {
            height: 100%;
            border-radius: 3px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            margin-top: 2rem;
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
            <a href="timetable.php">Schedule</a>
            <a href="subjects.php" class="active">Subjects</a>
            <a href="focus.php" style="background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);">Focus</a>
        </nav>
    </header>

    <div class="grid" style="grid-template-columns: 1fr 2fr;">
        <!-- Sidebar: Settings -->
        <div class="settings-sidebar">
            <div class="card" style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1.5rem;">Profile Settings</h3>
                <form action="subjects.php" method="POST">
                    <input type="hidden" name="update_user" value="1">
                    <div class="form-group">
                        <label>Student Name</label>
                        <input type="text" name="user_name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Final Exam Date</label>
                        <input type="date" name="exam_date" value="<?php echo $user['exam_date']; ?>" required>
                    </div>
                    <button type="submit" class="btn btn-ghost" style="width: 100%; justify-content: center;">SAVE PROFILE</button>
                </form>
            </div>

            <div class="card">
                <h3 style="margin-bottom: 1.5rem;">Add Subject</h3>
                <form action="subjects.php" method="POST">
                    <input type="hidden" name="add_subject" value="1">
                    <div class="form-group">
                        <label>Subject Name</label>
                        <input type="text" name="name" required placeholder="e.g. History">
                    </div>
                    <div class="form-group">
                        <label>Theme Color</label>
                        <input type="color" name="color" value="#4f46e5" style="height: 45px; padding: 0.2rem; cursor: pointer;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">CREATE SUBJECT</button>
                </form>
            </div>
        </div>

        <!-- Main: Charts and Subject List -->
        <div class="main-content">
            <!-- Study Growth Chart -->
            <div class="card" style="margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <div>
                        <h2 style="margin: 0;">Weekly Study Growth</h2>
                        <p style="color: var(--text-dim);">Visualize your momentum over the last 7 days.</p>
                    </div>
                    <div style="text-align: right;">
                         <span style="font-size: 1.5rem; font-weight: 800; color: var(--primary);">
                             <?php echo array_sum($mins); ?> 
                             <small style="font-size: 0.8rem; opacity: 0.6;">MINS TOTAL</small>
                         </span>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-bottom: 2rem;">Subject Breakdown</h3>
                <div class="subject-list">
                    <?php 
                    $max_mins = 0;
                    foreach($all_subjects as $sub) if($sub['total_mins'] > $max_mins) $max_mins = $sub['total_mins'];
                    
                    foreach($all_subjects as $sub): 
                        $perc = ($max_mins > 0) ? ($sub['total_mins'] / $max_mins) * 100 : 0;
                    ?>
                        <div class="subject-card-light">
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="width: 14px; height: 14px; border-radius: 4px; background: <?php echo $sub['color']; ?>;"></div>
                                        <div style="font-weight: 700; font-size: 1.1rem;"><?php echo htmlspecialchars($sub['name']); ?></div>
                                    </div>
                                    <div style="display: flex; gap: 1.5rem; align-items: center;">
                                        <div style="text-align: right;">
                                            <span style="font-weight: 800; color: #1e293b; display: block;"><?php echo round($sub['total_mins'] / 60, 1); ?>h</span>
                                            <span style="font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700;"><?php echo $sub['session_count']; ?> sessions</span>
                                        </div>
                                        <a href="subjects.php?delete_subject=<?php echo $sub['id']; ?>" 
                                           onclick="return confirm('Delete this subject and all its history?')"
                                           style="color: #cbd5e1; transition: color 0.2s;"
                                           onmouseover="this.style.color='#ef4444'" 
                                           onmouseout="this.style.color='#cbd5e1'">
                                            <i data-lucide="trash-2" style="width: 18px;"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="progress-mini-bar">
                                    <div class="progress-mini-fill" style="width: <?php echo $perc; ?>%; background: <?php echo $sub['color']; ?>;"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
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

    // Chart.js - Growth Chart
    const ctx = document.getElementById('growthChart').getContext('2d');
    
    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(79, 70, 229, 0.2)');
    gradient.addColorStop(1, 'rgba(79, 70, 229, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Study Minutes',
                data: <?php echo json_encode($mins); ?>,
                borderColor: '#4f46e5',
                borderWidth: 3,
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#4f46e5',
                pointBorderColor: '#fff',
                pointHoverRadius: 6,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#1e293b' },
                    ticks: { font: { family: 'Outfit', weight: '600' }, color: '#94a3b8' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Outfit', weight: '600' }, color: '#94a3b8' }
                }
            }
        }
    });
</script>

</body>
</html>
