<?php 
require_once 'db_connect.php'; 

// Fetch current timetable slot if any
$now = date('H:i');
$day = date('l');

$stmt = $pdo->prepare("
    SELECT t.*, s.name as subject_name, s.color 
    FROM timetable t
    JOIN subjects s ON t.subject_id = s.id
    WHERE t.day_of_week = ? AND ? BETWEEN t.start_time AND t.end_time
    LIMIT 1
");
$stmt->execute([$day, $now]);
$current_slot = $stmt->fetch();

$is_locked = isset($_GET['lock']) || $current_slot;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Focus Mode - O/L Master</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            overflow: hidden;
            background: #020617; /* Deepest black for focus */
        }
        .focus-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at center, #1e1b4b 0%, #020617 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            color: white;
            text-align: center;
            backdrop-filter: blur(20px);
        }
        .timer-display {
            font-size: 8rem;
            font-weight: 900;
            letter-spacing: -2px;
            margin: 1rem 0;
            background: linear-gradient(to bottom, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .focus-subject {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.2em;
        }
        .lock-icon {
            font-size: 3rem;
            margin-bottom: 2rem;
            color: var(--warning);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1); opacity: 0.8; }
        }
        .unlock-btn {
            margin-top: 3rem;
            opacity: 0.3;
            transition: opacity 0.3s;
        }
        .unlock-btn:hover {
            opacity: 1;
        }
        .quote {
            max-width: 600px;
            font-size: 1.25rem;
            color: #64748b;
            margin-top: 2rem;
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="focus-overlay" id="focusArea">
    <div class="lock-icon">
        <i data-lucide="lock" style="width: 64px; height: 64px;"></i>
    </div>
    
    <div class="focus-subject">
        <?php echo $current_slot ? 'STUDYING: ' . htmlspecialchars($current_slot['subject_name']) : 'FOCUS MODE ACTIVE'; ?>
    </div>
    
    <div class="timer-display" id="mainTimer">00:00:00</div>
    
    <div id="lockTimer" style="margin-top: 1rem; font-size: 1rem; color: #94a3b8; font-weight: 700; letter-spacing: 0.1em;">
        STAY FOCUSED: <span id="unlockCountdown">02:00:00</span> REMAINING
    </div>

    <div class="quote">
        "The secret of getting ahead is getting started."
    </div>

    <div id="unlockZone" style="display: none;">
        <a href="index.php" class="btn btn-ghost unlock-btn" style="opacity: 1;">
            <i data-lucide="unlock"></i> SESSION COMPLETE - EXIT
        </a>
    </div>

    <?php if($current_slot): ?>
        <div style="margin-top: 3rem; color: var(--text-dim); font-weight: 600;">
            SESSION ENDS AT <?php echo date('H:i', strtotime($current_slot['end_time'])); ?>
        </div>
        <p style="margin-top: 1rem; color: #ef4444; font-size: 0.8rem; font-weight: 700;">
            LOCKING SYSTEM ENGAGED. PLEASE FINISH YOUR STUDY SESSION.
        </p>
    <?php endif; ?>
    <div class="music-widget" id="musicWidget" style="bottom: 3rem; left: 50%; transform: translateX(-50%); border-color: rgba(255,255,255,0.1); background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px);">
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
    
    // Auto-play on focus start
    document.addEventListener('click', () => {
        if (!isPlaying) toggleMusic();
    }, { once: true });

    // 2-Hour Lock Logic
    const startTime = Date.now();
    const lockDuration = 2 * 60 * 60 * 1000; // 2 hours
    const unlockTime = startTime + lockDuration;

    function updateTimer() {
        const now = new Date();
        document.getElementById('mainTimer').innerText = now.toLocaleTimeString();

        // Update unlock countdown
        const remaining = unlockTime - Date.now();
        if (remaining > 0) {
            const h = Math.floor(remaining / 3600000);
            const m = Math.floor((remaining % 3600000) / 60000);
            const s = Math.floor((remaining % 60000) / 1000);
            document.getElementById('unlockCountdown').innerText = 
                `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        } else {
            document.getElementById('lockTimer').style.display = 'none';
            document.getElementById('unlockZone').style.display = 'block';
        }
    }
    setInterval(updateTimer, 1000);
    updateTimer();

    // Block Escape Key and stay in fullscreen
    document.addEventListener('keydown', (e) => {
        if (e.key === "Escape") {
            e.preventDefault();
            // Try to force fullscreen back if they managed to exit
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            }
        }
    });

    // Prevent exiting fullscreen
    document.addEventListener('fullscreenchange', () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        }
    });

    // Prevent accidental escape
    window.onbeforeunload = function() {
        return "STAY FOCUSED! You haven't finished your 2-hour goal yet.";
    };

    // Fullscreen behavior
    document.addEventListener('click', () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(e => {
                console.error(`Error attempting to enable full-screen mode: ${e.message}`);
            });
        }
    });

    // If it's a scheduled lock, check every minute if study time is over
    <?php if($current_slot): ?>
    setInterval(() => {
        fetch('api_check_lock.php')
            .then(res => res.json())
            .then(data => {
                if (!data.locked) {
                    window.location.href = 'index.php?session_complete=1';
                }
            });
    }, 30000); // Check every 30 seconds
    <?php endif; ?>
</script>

</body>
</html>
