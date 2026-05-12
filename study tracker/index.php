<?php 
require_once 'db_connect.php'; 

// Fetch User Data for Countdown
$user_stmt = $pdo->query("SELECT * FROM users LIMIT 1");
$user = $user_stmt->fetch();

// Calculate progress statistics
$session_stats_stmt = $pdo->query("SELECT SUM(duration_minutes) as total_minutes FROM sessions");
$total_minutes = $session_stats_stmt->fetch()['total_minutes'] ?? 0;
$total_hours = round($total_minutes / 60, 1);

// Recent activity (last 5 sessions)
$recent_sessions_stmt = $pdo->query("
    SELECT s.*, sub.name as subject_name, sub.color
    FROM sessions s
    JOIN subjects sub ON s.subject_id = sub.id
    ORDER BY s.study_date DESC, s.created_at DESC
    LIMIT 5
");
$recent_sessions = $recent_sessions_stmt->fetchAll();

// Pending tasks count
$tasks_stmt = $pdo->query("SELECT COUNT(*) as pending FROM tasks WHERE status = 'pending'");
$pending_tasks = $tasks_stmt->fetch()['pending'] ?? 0;

// Growth Data for Dashboard Chart
$growth_query = $pdo->query("
    SELECT study_date, SUM(duration_minutes) as daily_mins 
    FROM sessions 
    WHERE study_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY study_date 
    ORDER BY study_date ASC
");
$growth_data = $growth_query->fetchAll();

$dates = [];
$mins = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('D', strtotime($d)); // Just day name for dash
    $found = false;
    foreach($growth_data as $row) {
        if($row['study_date'] == $d) {
            $mins[] = (int)$row['daily_mins'];
            $found = true;
            break;
        }
    }
    if(!$found) $mins[] = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - O/L Study Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        .hero-banner {
            background: linear-gradient(135deg, var(--primary), #6366f1);
            color: white;
            border-radius: 1.5rem;
            padding: 3rem;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.1);
        }
        .hero-banner h1 { color: white; margin: 0; font-size: 2.25rem; }
        .hero-banner p { opacity: 0.9; margin-top: 0.5rem; }
        
        .countdown-timer-light {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 2rem;
        }
        .timer-part-light {
            background: rgba(255, 255, 255, 0.2);
            padding: 1.5rem;
            border-radius: 1rem;
            min-width: 100px;
            backdrop-filter: blur(10px);
        }
        .timer-num-light { font-size: 2.5rem; font-weight: 800; line-height: 1; }
        .timer-unit-light { font-size: 0.75rem; font-weight: 700; opacity: 0.8; margin-top: 0.5rem; }
        
        .mini-chart {
            height: 120px;
            width: 100%;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div class="logo">O/L TRACKER</div>
        <nav class="nav-links">
            <a href="index.php" class="active">Dashboard</a>
            <a href="sessions.php">Logs</a>
            <a href="tasks.php">Tasks</a>
            <a href="timetable.php">Schedule</a>
            <a href="subjects.php">Subjects</a>
            <a href="focus.php" style="background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);">Focus</a>
        </nav>
    </header>

    <?php if ($growth_data): // Just a marker point ?>
    <div style="margin-bottom: 2rem;">
        <?php
          $current_day = date('l');
          $current_time = date('H:i');
          $lock_stmt = $pdo->prepare("SELECT t.*, s.name as sub_name FROM timetable t JOIN subjects s ON t.subject_id = s.id WHERE t.day_of_week = ? AND ? BETWEEN t.start_time AND t.end_time LIMIT 1");
          $lock_stmt->execute([$current_day, $current_time]);
          $active_slot = $lock_stmt->fetch();
          
          if($active_slot): ?>
            <div class="card" style="background: linear-gradient(135deg,rgba(239, 68, 68, 0.2), rgba(236, 72, 115, 0.2)); border-color: #ef4444; border-style: dashed; text-align: center; padding: 1.5rem;">
                <h3 style="color: #ef4444; margin: 0; font-size: 1.25rem;">📝 STUDY TIME: <?php echo strtoupper($active_slot['sub_name']); ?></h3>
                <p style="margin: 0.5rem 0 1.25rem;">Your schedule says you should be studying right now.</p>
                <a href="focus.php" class="btn btn-primary" style="background: #ef4444; box-shadow: 0 10px 15px rgba(239, 68, 68, 0.3);">
                    <i data-lucide="lock" style="width: 20px;"></i> ENTER FOCUS MODE (EXAM LOCK)
                </a>
            </div>
          <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="hero-banner">
        <h1>Keep Pushing, <?php echo htmlspecialchars($user['name'] ?? 'Scholar'); ?>!</h1>
        <p>Your journey to success has 8 months left. Every hour counts.</p>
        
        <div class="countdown-timer-light" id="countdown" data-target="<?php echo $user['exam_date'] ?? date('Y-m-d', strtotime('+8 months')); ?>">
            <div class="timer-part-light">
                <span class="timer-num-light" id="days">000</span>
                <div class="timer-unit-light">DAYS</div>
            </div>
            <div class="timer-part-light">
                <span class="timer-num-light" id="hours">00</span>
                <div class="timer-unit-light">HOURS</div>
            </div>
            <div class="timer-part-light">
                <span class="timer-num-light" id="mins">00</span>
                <div class="timer-unit-light">MINS</div>
            </div>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <div class="stat-label">DAILY GROWTH</div>
            <div class="mini-chart">
                <canvas id="growthMiniChart"></canvas>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1rem;">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-dim);">LAST 7 DAYS</span>
                <span style="font-size: 1.1rem; font-weight: 800; color: var(--primary);"><?php echo array_sum($mins); ?>m</span>
            </div>
        </div>

        <div class="card">
            <div class="stat-label">TOTAL STUDY TIME</div>
            <div class="stat-value"><?php echo $total_hours; ?> <small style="font-size: 1rem; color: var(--text-dim);">HOURS</small></div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
                <i data-lucide="award" style="color: #f59e0b; width: 18px;"></i>
                <span style="font-size: 0.85rem; font-weight: 600;">You are on the right track!</span>
            </div>
        </div>

        <div class="card">
            <div class="stat-label">REMAINING TASKS</div>
            <div class="stat-value"><?php echo $pending_tasks; ?></div>
            <a href="tasks.php" style="display: inline-flex; align-items: center; gap: 0.3rem; margin-top: 1rem; color: var(--primary); text-decoration: none; font-weight: 800; font-size: 0.85rem;">
                VIEW YOUR GOALS <i data-lucide="arrow-right" style="width: 14px;"></i>
            </a>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3 style="margin: 0;">Recent Activity</h3>
                <a href="sessions.php" class="btn btn-ghost" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">VIEW ALL</a>
            </div>
            
            <?php if (empty($recent_sessions)): ?>
                <div style="text-align: center; padding: 2rem; background: #f8fafc; border-radius: 1rem;">
                    <p style="color: var(--text-dim);">No study sessions logged yet.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Time</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_sessions as $sess): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $sess['color']; ?>;"></div>
                                        <span style="font-weight: 600;"><?php echo htmlspecialchars($sess['subject_name']); ?></span>
                                    </div>
                                </td>
                                <td><span style="background: #f1f5f9; padding: 0.2rem 0.6rem; border-radius: 0.5rem; font-weight: 800; color: var(--primary);"><?php echo $sess['duration_minutes']; ?>m</span></td>
                                <td style="color: var(--text-dim); font-size: 0.9rem;"><?php echo date('M j, Y', strtotime($sess['study_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Quick Start</h3>
            <p style="color: var(--text-dim); font-size: 0.9rem; margin-bottom: 1.5rem;">Started studying? Log your progress now.</p>
            <a href="sessions.php" class="btn btn-primary" style="width: 100%; justify-content: center; box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);">
                <i data-lucide="plus" style="width: 20px;"></i> LOG SESSION
            </a>
        </div>
    </div>
</div>

    <div class="music-widget" id="musicWidget">
        <div class="music-wave" id="wave">
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
            audio.play();
            info.innerText = "STUDY AMBIENCE: ON";
            wave.style.opacity = '1';
            wave.style.display = 'flex';
        }
        isPlaying = !isPlaying;
    }

    widget.addEventListener('click', toggleMusic);

    // Try to auto-play on first interaction
    document.addEventListener('click', () => {
        if (!isPlaying) {
            toggleMusic();
        }
    }, { once: true });

    // Chart.js - Dashboard Mini Chart
    const ctx = document.getElementById('growthMiniChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                data: <?php echo json_encode($mins); ?>,
                backgroundColor: '#4f46e5',
                borderRadius: 4,
                hoverBackgroundColor: '#6366f1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { display: false },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Outfit', size: 10, weight: '700' }, color: '#94a3b8' }
                }
            }
        }
    });

    // Countdown Logic
    const targetDate = new Date(document.getElementById('countdown').dataset.target).getTime();

    function updateCountdown() {
        const now = new Date().getTime();
        const distance = targetDate - now;
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const mins = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

        document.getElementById('days').innerText = days.toString().padStart(3, '0');
        document.getElementById('hours').innerText = hours.toString().padStart(2, '0');
        document.getElementById('mins').innerText = mins.toString().padStart(2, '0');

        if (distance < 0) {
            document.getElementById('countdown').innerHTML = "<h1>EXAM SEASON</h1>";
        }
    }

    setInterval(updateCountdown, 1000);
    updateCountdown();
</script>

</body>
</html>
