<?php 
require_once 'db_connect.php'; 

// Handle Add Task
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_task'])) {
    $subject_id = $_POST['subject_id'];
    $title = trim($_POST['title']);
    $deadline = $_POST['deadline'];
    
    if (!empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO tasks (subject_id, title, deadline) VALUES (?, ?, ?)");
        $stmt->execute([$subject_id, $title, $deadline]);
        header("Location: tasks.php?success=1");
        exit();
    }
}

// Handle Status Toggle (via GET)
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE tasks SET status = IF(status='pending', 'completed', 'pending') WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: tasks.php");
    exit();
}

// Handle Delete Task (via GET)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: tasks.php");
    exit();
}

// Fetch Subjects for Dropdown
$stmt = $pdo->query("SELECT * FROM subjects ORDER BY name");
$subjects = $stmt->fetchAll();

// Fetch Tasks grouped by status and then deadline
$all_tasks_query = $pdo->query("
    SELECT t.*, sub.name as subject_name, sub.color
    FROM tasks t
    JOIN subjects sub ON t.subject_id = sub.id
    ORDER BY t.status ASC, t.deadline ASC, t.id DESC
");
$all_tasks = $all_tasks_query->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tasks - O/L Master Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        .task-list-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .task-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--border);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .task-card:hover {
            transform: translateX(4px);
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .task-card.completed {
            background-color: #f1f5f9;
            opacity: 0.7;
            border-color: transparent;
        }
        .task-card.completed .task-text {
            text-decoration: line-through;
            color: var(--text-dim);
        }
        .check-btn {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 2px solid var(--border);
            margin-right: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }
        .check-btn.active {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }
        .check-btn:hover {
            border-color: var(--primary);
        }
        .task-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        .subject-badge {
            font-size: 0.7rem;
            font-weight: 800;
            padding: 0.2rem 0.6rem;
            border-radius: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .deadline-text {
            font-size: 0.75rem;
            color: var(--text-dim);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .delete-btn {
            color: #ef4444;
            padding: 0.5rem;
            border-radius: 0.5rem;
            opacity: 0.2;
            transition: all 0.2s;
        }
        .task-card:hover .delete-btn {
            opacity: 1;
        }
        .delete-btn:hover {
            background: #fee2e2;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-dim);
        }
        .success-toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #1e293b;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: slideInUp 0.3s ease-out;
        }
        @keyframes slideInUp {
            from { transform: translateY(100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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
            <a href="tasks.php" class="active">Tasks</a>
            <a href="timetable.php">Schedule</a>
            <a href="subjects.php">Subjects</a>
            <a href="focus.php" style="background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);">Focus</a>
        </nav>
    </header>

    <div class="grid" style="grid-template-columns: 1fr 2fr;">
        <!-- Input Section -->
        <div class="input-sidebar">
            <div class="card" style="position: sticky; top: 2rem;">
                <h2 style="font-size: 1.5rem; letter-spacing: -0.02em;">Add Study Task</h2>
                <p style="color: var(--text-dim); margin-bottom: 2rem;">Break down your goals into small, achievable steps.</p>
                
                <form action="tasks.php" method="POST">
                    <input type="hidden" name="add_task" value="1">
                    
                    <div class="form-group">
                        <label>What needs to be done?</label>
                        <input type="text" name="title" required placeholder="e.g. Complete Science Unit 1 Past Paper" autofocus>
                    </div>

                    <div class="form-group">
                        <label>Related Subject</label>
                        <select name="subject_id" required>
                            <?php foreach($subjects as $sub): ?>
                                <option value="<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Target Date</label>
                        <input type="date" name="deadline" value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">
                        <i data-lucide="plus-circle"></i> ADD TO LIST
                    </button>
                </form>
            </div>
        </div>

        <!-- Task List Section -->
        <div class="task-viewer">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; padding: 0 0.5rem;">
                <div>
                   <h2 style="margin: 0; font-size: 1.75rem;">Your Roadmap</h2>
                   <p style="color: var(--text-dim);">Keep track of what's next in your 8-month journey.</p>
                </div>
                <div style="font-weight: 700; color: var(--primary); background: #eef2ff; padding: 0.3rem 0.8rem; border-radius: 0.5rem; font-size: 0.85rem;">
                   <?php 
                     $count = count(array_filter($all_tasks, function($t) { return $t['status'] == 'pending'; }));
                     echo $count . " TASKS REMAINING";
                   ?>
                </div>
            </div>

            <div class="task-list-section">
                <?php if (empty($all_tasks)): ?>
                    <div class="card empty-state">
                        <i data-lucide="clipboard-list" style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p>Your list is empty. Start adding tasks to see your roadmap!</p>
                    </div>
                <?php else: ?>
                    <?php foreach($all_tasks as $task): ?>
                        <div class="task-card <?php echo $task['status'] == 'completed' ? 'completed' : ''; ?>">
                            <div style="display: flex; align-items: center; flex: 1;">
                                <a href="tasks.php?toggle=<?php echo $task['id']; ?>" class="check-btn <?php echo $task['status'] == 'completed' ? 'active' : ''; ?>">
                                    <?php if($task['status'] == 'completed') echo '<i data-lucide="check" style="width: 16px;"></i>'; ?>
                                </a>
                                <div style="flex: 1;">
                                    <span class="task-text" style="font-size: 1.15rem; font-weight: 600; color: #1e293b;">
                                        <?php echo htmlspecialchars($task['title']); ?>
                                    </span>
                                    <div class="task-meta">
                                        <span class="subject-badge" style="background: <?php echo $task['color']; ?>15; color: <?php echo $task['color']; ?>;">
                                            <?php echo htmlspecialchars($task['subject_name']); ?>
                                        </span>
                                        <?php if($task['deadline']): ?>
                                            <span class="deadline-text">
                                                <i data-lucide="calendar" style="width: 14px;"></i>
                                                <?php 
                                                  $d = new DateTime($task['deadline']);
                                                  echo $d->format('M j');
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <a href="tasks.php?delete=<?php echo $task['id']; ?>" class="delete-btn" onclick="return confirm('Remove task?')">
                                    <i data-lucide="trash-2" style="width: 20px;"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if(isset($_GET['success'])): ?>
    <div class="success-toast" id="toast">
        <i data-lucide="check-circle-2" style="color: #22c55e;"></i>
        Task added successfully!
    </div>
    <script>
        setTimeout(() => {
            const toast = document.getElementById('toast');
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.5s';
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    </script>
<?php endif; ?>

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
    
    // Auto-focus title input when page loads if no error
    window.onload = function() {
        if(document.querySelector('input[name="title"]')) document.querySelector('input[name="title"]').focus();
    };
</script>

</body>
</html>
