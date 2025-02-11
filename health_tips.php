<?php
session_start();
const API_KEY = 'AIzaSyADIxTwoGUdHq2ZYUE1d28qC1HuDnUT5jA'; //  API key

function generate_health_tips() {
    $prompt = "Generate 12 professional health tips in this exact format:
        [Category: category_name] Tip X: Title | Detailed explanation
        Categories allowed: nutrition, exercise, mental health, sleep, hydration
        Example: [Category: nutrition] Tip 1: Balanced Diet | Aim for colorful fruits and vegetables";

    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . API_KEY
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('API request failed: ' . curl_error($ch));
        }
        
        if ($httpCode !== 200) {
            throw new Exception('API returned HTTP code: ' . $httpCode);
        }

        $data = json_decode($response, true);
        curl_close($ch);

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception('Invalid API response structure');
        }

        return $data['candidates'][0]['content']['parts'][0]['text'];
    } catch (Exception $e) {
        error_log('Health Tips Error: ' . $e->getMessage());
        return get_fallback_tips();
    }
}

function get_fallback_tips() {
    return "[Category: nutrition] Tip 1: Balanced Diet | Include a variety of colorful fruits and vegetables in daily meals.
[Category: exercise] Tip 2: Daily Activity | Aim for at least 30 minutes of moderate exercise daily.
[Category: mental health] Tip 3: Stress Management | Practice mindfulness meditation for 10 minutes each morning.
[Category: sleep] Tip 4: Quality Rest | Maintain consistent sleep and wake times, even on weekends.
[Category: hydration] Tip 5: Water Intake | Drink a glass of water before each meal.
[Category: nutrition] Tip 6: Healthy Snacks | Choose nuts and fruits over processed snacks.
[Category: exercise] Tip 7: Posture Check | Take regular breaks to stretch when sitting for long periods.
[Category: mental health] Tip 8: Digital Detox | Designate screen-free time before bed.
[Category: sleep] Tip 9: Sleep Environment | Keep bedroom dark and cool for better rest.
[Category: hydration] Tip 10: Hydration Reminder | Carry a reusable water bottle throughout the day.
[Category: nutrition] Tip 11: Portion Control | Use smaller plates to avoid overeating.
[Category: exercise] Tip 12: Strength Training | Include resistance exercises in your weekly workout routine.";
}

function get_category_icon($category) {
    $icons = [
        'nutrition' => 'fa-apple-alt',
        'exercise' => 'fa-dumbbell',
        'mental health' => 'fa-brain',
        'sleep' => 'fa-moon',
        'hydration' => 'fa-glass-water'
    ];
    return $icons[strtolower($category)] ?? 'fa-check';
}

// Tip generation and caching logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh'])) {
    $_SESSION['tips'] = generate_health_tips();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!isset($_SESSION['tips'])) {
    $_SESSION['tips'] = generate_health_tips();
}

$tips_content = $_SESSION['tips'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Health Advisor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2A9D8F;
            --primary-dark:rgb(21, 133, 120);
            --accent:rgb(67, 240, 220);
            --background: #ffffff;
            --card-bg: #ffffff;
            --text: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
            --shadow: rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        [data-theme="dark"] {
            --primary: #2A9D8F;
            --primary-dark:rgb(21, 133, 120);
            --accent:rgb(67, 240, 220);
            --background: #111827;
            --card-bg: #1f2937;
            --text: #f3f4f6;
            --text-light: #9ca3af;
            --border: #374151;
            --shadow: rgba(0, 0, 0, 0.25);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--background);
            color: var(--text);
            line-height: 1.6;
            transition: var(--transition);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .title {
            font-size: 2.5rem;
            color: var(--text);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .search-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .search-wrapper {
            position: relative;
            margin-bottom: 1rem;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        .search-bar {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--border);
            border-radius: 2rem;
            background-color: var(--card-bg);
            color: var(--text);
            font-size: 1rem;
            transition: var(--transition);
        }

        .search-bar:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 1.5rem;
            background-color: var(--card-bg);
            color: var(--text);
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid var(--border);
        }

        .filter-btn:hover,
        .filter-btn.active {
            background-color: var(--primary);
            color: white;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            padding: 1rem 0;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 1rem;
            box-shadow: 0 4px 6px var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px var(--shadow);
        }

        .card-content {
            padding: 1.5rem;
        }

        .card h2 {
            color: var(--text);
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .card p {
            color: var(--text-light);
            margin-bottom: 2rem;
        }

        .category-tag {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            background-color: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .theme-toggle-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
        }

        .theme-toggle {
            background: var(--card-bg);
            border: 2px solid var(--border);
            color: var(--text);
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .theme-toggle:hover {
            background-color: var(--primary);
            color: white;
        }

        .refresh-container {
            text-align: center;
            margin: 2rem 0;
        }

        .refresh-button {
            padding: 0.8rem 2rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 2rem;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .refresh-button:hover {
            background-color: var(--primary-dark);
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .title {
                font-size: 2rem;
            }
            
            .grid {
                grid-template-columns: 1fr;
            }
            
            .filter-buttons {
                flex-direction: column;
            }
            
            .filter-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="theme-toggle-container">
        <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle theme">
            <i class="fas fa-moon"></i>
        </button>
    </div>

    <div class="container">
        <header class="header">
            <h1 class="title">
                <i class="fas fa-heartbeat"></i>
                Smart Health Advisor
            </h1>
            <div class="search-container">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input 
                        type="text" 
                        class="search-bar" 
                        placeholder="Search tips by category..."
                        onkeyup="filterCards(this.value)"
                    >
                </div>
                <div class="filter-buttons">
                    <button onclick="filterByCategory('all')" class="filter-btn active">All</button>
                    <button onclick="filterByCategory('nutrition')" class="filter-btn">Nutrition</button>
                    <button onclick="filterByCategory('exercise')" class="filter-btn">Exercise</button>
                    <button onclick="filterByCategory('mental health')" class="filter-btn">Mental Health</button>
                    <button onclick="filterByCategory('sleep')" class="filter-btn">Sleep</button>
                    <button onclick="filterByCategory('hydration')" class="filter-btn">Hydration</button>
                </div>
                <form method="post" class="refresh-container">
                    <button type="submit" name="refresh" class="refresh-button">
                        <i class="fas fa-sync-alt"></i>
                        Generate New Tips
                    </button>
                </form>
            </div>
        </header>

        <div class="grid">
            <?php
            $tips = explode("\n", trim($tips_content));
            foreach($tips as $tip): 
                if(preg_match('/\[Category:\s*(.*?)\s*\].*Tip\s+\d+:\s*(.*?)\s*\|\s*(.*)/', $tip, $matches)):
                    $category = strtolower(trim($matches[1]));
                    $title = trim($matches[2]);
                    $content = trim($matches[3]);
            ?>
                <div class="card" data-category="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="card-content">
                        <h2><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
                        <p><?= htmlspecialchars($content, ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="category-tag">
                            <i class="fas <?= get_category_icon($category) ?>"></i>
                            <?= ucfirst(htmlspecialchars($category, ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    </div>
                </div>
            <?php endif; endforeach; ?>
        </div>
    </div>

    <script>
        // Theme toggling
        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            
            const themeIcon = document.querySelector('.theme-toggle i');
            themeIcon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            localStorage.setItem('theme', newTheme);
        }

        // Filter cards by search input
        function filterCards(searchTerm) {
            const cards = document.querySelectorAll('.card');
            const lowercaseSearch = searchTerm.toLowerCase();
            
            cards.forEach(card => {
                const content = card.textContent.toLowerCase();
                const category = card.dataset.category;
                const isVisible = content.includes(lowercaseSearch) || 
                                  category.includes(lowercaseSearch);
                card.style.display = isVisible ? 'block' : 'none';
            });
        }

        // Filter cards by category
        function filterByCategory(category) {
            const cards = document.querySelectorAll('.card');
            const buttons = document.querySelectorAll('.filter-btn');
            
            buttons.forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.toLowerCase() === category || 
                    (category === 'all' && btn.textContent === 'All')) {
                    btn.classList.add('active');
                }
            });
            
            cards.forEach(card => {
                card.style.display = category === 'all' ? 'block' : 
                    card.dataset.category === category ? 'block' : 'none';
            });
        }

        // Initialize theme and animations
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);
            
            const themeIcon = document.querySelector('.theme-toggle i');
            themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.animation = `fadeIn 0.5s ease forwards ${index * 0.1}s`;
                card.style.opacity = '0';
            });
        });

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>