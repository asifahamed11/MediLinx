<?php
session_start();
const API_KEY = 'AIzaSyA-SczyTDGunUSkDCQL_6kDsSGV1JNvWrY';
const CACHE_LIFETIME = 3; // 1 hour cache lifetime

function generate_health_tips() {
    // Check for cached tips in a file to reduce API calls
    $cache_file = __DIR__ . '/cache/health_tips.json';
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < CACHE_LIFETIME)) {
        return file_get_contents($cache_file);
    }
    
    $prompt = "Generate 12 professional health tips in this exact format:
        [Category: category_name] Tip X: Title | Detailed explanation
        Categories allowed: nutrition, exercise, mental health, sleep, hydration
        Example: [Category: nutrition] Tip 1: Balanced Diet | Aim for colorful fruits and vegetables";

    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . API_KEY
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CONNECTTIMEOUT => 5 // Faster timeout for connection
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('API request failed: ' . curl_error($ch));
        }
        
        if ($http_code !== 200) {
            throw new Exception("API returned error code: $http_code");
        }

        curl_close($ch);
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse API response: ' . json_last_error_msg());
        }
        
        $tips = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        
        if (!$tips) {
            throw new Exception('No tips data in API response');
        }
        
        // Cache the tips to a file
        if (!is_dir(dirname($cache_file))) {
            mkdir(dirname($cache_file), 0755, true);
        }
        file_put_contents($cache_file, $tips);
        
        return $tips;
    } catch (Exception $e) {
        error_log('Health Tips Error: ' . $e->getMessage());
        return get_fallback_tips();
    }
}

function get_fallback_tips() {
    // Using a static array is faster than a long string
    return implode("\n", [
        "[Category: nutrition] Tip 1: Balanced Diet | Include a variety of colorful fruits and vegetables in daily meals.",
        "[Category: exercise] Tip 2: Daily Activity | Aim for at least 30 minutes of moderate exercise daily.",
        "[Category: mental health] Tip 3: Stress Management | Practice mindfulness meditation for 10 minutes each morning.",
        "[Category: sleep] Tip 4: Quality Rest | Maintain consistent sleep and wake times, even on weekends.",
        "[Category: hydration] Tip 5: Water Intake | Drink a glass of water before each meal.",
        "[Category: nutrition] Tip 6: Healthy Snacks | Choose nuts and fruits over processed snacks.",
        "[Category: exercise] Tip 7: Posture Check | Take regular breaks to stretch when sitting for long periods.",
        "[Category: mental health] Tip 8: Digital Detox | Designate screen-free time before bed.",
        "[Category: sleep] Tip 9: Sleep Environment | Keep bedroom dark and cool for better rest.",
        "[Category: hydration] Tip 10: Hydration Reminder | Carry a reusable water bottle throughout the day.",
        "[Category: nutrition] Tip 11: Portion Control | Use smaller plates to avoid overeating.",
        "[Category: exercise] Tip 12: Strength Training | Include resistance exercises in your weekly workout routine."
    ]);
}

// Use static array for icons instead of creating the array each time
$CATEGORY_ICONS = [
    'nutrition' => 'fa-apple-alt',
    'exercise' => 'fa-dumbbell',
    'mental health' => 'fa-brain',
    'sleep' => 'fa-moon',
    'hydration' => 'fa-glass-water'
];

function get_category_icon($category) {
    global $CATEGORY_ICONS;
    return $CATEGORY_ICONS[strtolower($category)] ?? 'fa-check';
}

// Use a static array for better performance
$CATEGORY_TIPS_CACHE = [];

function get_category_tips($category) {
    global $CATEGORY_TIPS_CACHE;
    
    $category = strtolower($category);
    
    // Return from cache if available
    if (isset($CATEGORY_TIPS_CACHE[$category])) {
        return $CATEGORY_TIPS_CACHE[$category];
    }
    
    // Create and cache the tips
    switch($category) {
        case 'hydration':
            $tips = [
                'Stay Hydrated' => 'Drink 8-10 glasses of water daily',
                'Water Quality' => 'Use clean, filtered water when possible',
                'Hydration Schedule' => 'Set regular reminders to drink water',
                'Monitor Hydration' => 'Check urine color for hydration levels',
                'Smart Drinking' => 'Avoid excessive caffeine and alcohol'
            ];
            break;
        case 'nutrition':
            $tips = [
                'Balanced Meals' => 'Include protein, complex carbs, and healthy fats in each meal',
                'Portion Control' => 'Use smaller plates to naturally reduce portion sizes',
                'Meal Planning' => 'Plan meals in advance to reduce unhealthy choices',
                'Mindful Eating' => 'Eat slowly and without distractions',
                'Colorful Diet' => 'Eat a rainbow of vegetables and fruits each day'
            ];
            break;
        case 'exercise':
            $tips = [
                'Consistent Movement' => 'Try to move your body every day',
                'Strength Training' => 'Include resistance exercises at least twice weekly',
                'Cardio Health' => 'Get at least 150 minutes of moderate cardio weekly',
                'Active Transport' => 'Walk or cycle for short trips when possible',
                'Stretching' => 'Make flexibility exercises part of your routine'
            ];
            break;
        case 'mental health':
            $tips = [
                'Mindfulness' => 'Practice being present in the moment',
                'Social Connection' => 'Maintain relationships with supportive people',
                'Stress Management' => 'Learn techniques like deep breathing or meditation',
                'Digital Detox' => 'Take regular breaks from screens and social media',
                'Professional Help' => 'Seek support when needed - therapy is self-care'
            ];
            break;
        case 'sleep':
            $tips = [
                'Sleep Schedule' => 'Go to bed and wake up at consistent times',
                'Sleep Environment' => 'Keep your bedroom cool, dark, and quiet',
                'Pre-Sleep Routine' => 'Develop a relaxing ritual before bed',
                'Screen Limits' => 'Avoid screens 1-2 hours before bedtime',
                'Sleep Duration' => 'Aim for 7-9 hours of quality sleep'
            ];
            break;
        default:
            $tips = [];
    }
    
    // Cache the result
    $CATEGORY_TIPS_CACHE[$category] = $tips;
    
    return $tips;
}

// Use a file-based cache for tips generation with expiry
function get_or_create_tips() {
    $cache_key = 'health_tips_' . date('Ymd');
    $session_key = 'tips_' . $cache_key;
    
    // Check if we need to refresh (from POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh'])) {
        $tips = generate_health_tips();
        $_SESSION[$session_key] = $tips;
        return $tips;
    }
    
    // Check session first (fastest)
    if (isset($_SESSION[$session_key])) {
        return $_SESSION[$session_key];
    }
    
    // Generate new tips
    $tips = generate_health_tips();
    $_SESSION[$session_key] = $tips;
    return $tips;
}

// Use output buffering to improve performance
ob_start();

// Process tips with better handling
$tips_content = get_or_create_tips();

// Parse tips once outside the HTML loop for better performance
$parsed_tips = [];
$tips = explode("\n", trim($tips_content));
foreach($tips as $tip) {
    if(preg_match('/\[Category:\s*(.*?)\s*\].*Tip\s+\d+:\s*(.*?)\s*\|\s*(.*)/', $tip, $matches)) {
        $parsed_tips[] = [
            'category' => strtolower(trim($matches[1])),
            'title' => trim($matches[2]),
            'content' => trim($matches[3])
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Health Advisor - Daily Wellness Tips</title>
    <meta name="description" content="Get personalized health and wellness tips across nutrition, exercise, mental health, sleep, and hydration.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2A9D8F;
            --primary-light: #4ecdc4;
            --primary-dark: #1a7168;
            --accent: #F4A261;
            --background: #f8fafc;
            --card-bg: #ffffff;
            --text: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
            --shadow: rgba(0, 0, 0, 0.1);
            --shadow-lg: rgba(0, 0, 0, 0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="dark"] {
            --primary: #4ecdc4;
            --primary-light: #64dfb4;
            --primary-dark: #2A9D8F;
            --accent: #F4A261;
            --background: #111827;
            --card-bg: #1f2937;
            --text:rgb(212, 216, 221);
            --text-light: #9ca3af;
            --border: #374151;
            --shadow: rgba(0, 0, 0, 0.25);
            --shadow-lg: rgba(0, 0, 0, 0.35);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, system-ui, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
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
            animation: slideDown 0.6s ease-out;
        }

        .title {
            font-size: 2.5rem;
            color: var(--text);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .title i {
            color: var(--primary);
            animation: pulse 2s infinite;
        }

        .subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        .search-container {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }

        .search-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
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
            box-shadow: 0 2px 4px var(--shadow);
        }

        .search-bar:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.2);
            transform: translateY(-1px);
        }

        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .filter-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 1.5rem;
            background-color: var(--card-bg);
            color: var(--text);
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid var(--border);
            font-weight: 500;
            box-shadow: 0 2px 4px var(--shadow);
        }

        .filter-btn:hover {
            background-color: var(--primary-light);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px var(--shadow-lg);
        }

        .filter-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary-dark);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            padding: 1rem 0;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 1.5rem;
            box-shadow: 0 4px 6px var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            opacity: 0;
            transform: translateY(20px);
        }

        .card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 12px var(--shadow-lg);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary);
            transform: scaleX(0);
            transition: var(--transition);
        }

        .card:hover::before {
            transform: scaleX(1);
        }

        .card-content {
            padding: 1.75rem;
        }

        .card h2 {
            color: var(--text);
            font-size: 1.25rem;
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .card p {
            color: var(--text-light);
            margin-bottom: 2rem;
            font-size: 1rem;
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
            transform: translateY(0);
            transition: var(--transition);
        }

        .card:hover .category-tag {
            transform: translateY(-2px);
            background-color: var(--primary-dark);
        }

        .refresh-button {
            padding: 1rem 2rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 2rem;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            box-shadow: 0 4px 6px var(--shadow);
            margin: 2rem 0;
        }

        .refresh-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 6px 8px var(--shadow-lg);
        }

        .refresh-button i {
            transition: transform 0.3s ease;
        }

        .refresh-button:hover i {
            transform: rotate(180deg);
        }

        .theme-toggle {
            position: fixed;
            top: 1rem;
            right: 1rem;
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
            box-shadow: 0 2px 4px var(--shadow);
            z-index: 1000;
        }

        .theme-toggle:hover {
            background-color: var(--primary);
            color: white;
            transform: rotate(180deg);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

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

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }

        @media (max-width: 768px) {
            .title {
                font-size: 2rem;
            }
            
            .grid {
                grid-template-columns: 1fr;
            }
            
            .filter-buttons {
                gap: 0.5rem;
            }
            
            .filter-btn {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }

            .card {
                margin: 0 0.5rem;
            }

            .theme-toggle {
                top: auto;
                bottom: 1rem;
                right: 1rem;
            }
        }

        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .loading.active {
            opacity: 1;
            pointer-events: auto;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--primary-light);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .back {
            position: fixed;
            top: 2rem;
            left: 2rem;
            padding: 0.8rem 1.5rem;
            background:rgba(228, 116, 88, 0.92);
            border: 2px solid var(--border);
            border-radius: 25px;
            color: white;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            backdrop-filter: blur(8px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .back:hover {
            background: #E76F51;
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
<a href="index.php" class="back"><i class="fas fa-arrow-left"></i> Back</a>
    <div class="loading">
        <div class="loading-spinner"></div>
    </div>

    <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle theme">
        <i class="fas fa-moon"></i>
    </button>

    <div class="container">
        <header class="header">
            <h1 class="title">
                <i class="fas fa-heartbeat"></i>
                Smart Health Advisor
            </h1>
            <p class="subtitle">Discover personalized health tips across nutrition, exercise, mental health, sleep, and hydration to improve your daily wellness routine.</p>
            
            <div class="search-container">
                <div class="search-wrapper">
                    <input 
                        type="text" 
                        class="search-bar" 
                        placeholder="Search tips by keyword or category..."
                        onkeyup="debounce(filterCards, 300)(this.value)"
                        aria-label="Search health tips"
                    >
                    <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                </div>
                <div class="filter-buttons">
                    <button onclick="filterByCategory('all')" class="filter-btn active" data-category="all">
                        <i class="fas fa-th-large"></i> All Tips
                    </button>
                    <button onclick="filterByCategory('nutrition')" class="filter-btn" data-category="nutrition">
                        <i class="fas fa-apple-alt"></i> Nutrition
                    </button>
                    <button onclick="filterByCategory('exercise')" class="filter-btn" data-category="exercise">
                        <i class="fas fa-dumbbell"></i> Exercise
                    </button>
                    <button onclick="filterByCategory('mental health')" class="filter-btn" data-category="mental health">
                        <i class="fas fa-brain"></i> Mental Health
                    </button>
                    <button onclick="filterByCategory('sleep')" class="filter-btn" data-category="sleep">
                        <i class="fas fa-moon"></i> Sleep
                    </button>
                    <button onclick="filterByCategory('hydration')" class="filter-btn" data-category="hydration">
                        <i class="fas fa-glass-water"></i> Hydration
                    </button>
                </div>
                <form method="post" class="refresh-container" onsubmit="showLoading()">
                    <button type="submit" name="refresh" class="refresh-button">
                        <i class="fas fa-sync-alt"></i>
                        Generate New Tips
                    </button>
                </form>
            </div>
        </header>

        <div class="grid">
            <?php foreach($parsed_tips as $index => $tip): ?>
                <div class="card" data-category="<?= htmlspecialchars($tip['category'], ENT_QUOTES, 'UTF-8') ?>" style="animation: fadeIn 0.5s ease forwards <?= $index * 0.1 ?>s;">
                    <div class="card-content">
                        <h2><?= htmlspecialchars($tip['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p><?= htmlspecialchars($tip['content'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="category-tag">
                            <i class="fas <?= get_category_icon($tip['category']) ?>"></i>
                            <?= ucfirst(htmlspecialchars($tip['category'], ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>


    <script>
        // Debounce function for search optimization
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        // Enhanced search functionality
        function filterCards(searchTerm) {
            const cards = document.querySelectorAll('.card');
            const lowercaseSearch = searchTerm.toLowerCase();
            let visibleCount = 0;
            
            cards.forEach(card => {
                const content = card.textContent.toLowerCase();
                const category = card.dataset.category;
                const isVisible = content.includes(lowercaseSearch) || 
                                category.includes(lowercaseSearch);
                
                if (isVisible) {
                    card.style.display = 'block';
                    card.style.animation = `fadeIn 0.5s ease forwards ${visibleCount * 0.1}s`;
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Enhanced category filtering
        function filterByCategory(category) {
            const cards = document.querySelectorAll('.card');
            const buttons = document.querySelectorAll('.filter-btn');
            let visibleCount = 0;
            
            buttons.forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.category === category) {
                    btn.classList.add('active');
                }
            });
            
            cards.forEach(card => {
                const isVisible = category === 'all' || card.dataset.category === category;
                
                if (isVisible) {
                    card.style.display = 'block';
                    card.style.animation = `fadeIn 0.5s ease forwards ${visibleCount * 0.1}s`;
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Theme management
        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            const themeIcon = document.querySelector('.theme-toggle i');
            themeIcon.className = `fas fa-${newTheme === 'dark' ? 'sun' : 'moon'}`;
        }

        // Loading state management
        function showLoading() {
            document.querySelector('.loading').classList.add('active');
        }

        // Initialize theme and animations
        document.addEventListener('DOMContentLoaded', () => {
            // Set theme
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);
            
            const themeIcon = document.querySelector('.theme-toggle i');
            themeIcon.className = `fas fa-${savedTheme === 'dark' ? 'sun' : 'moon'}`;

            // Animate cards on load
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.animation = `fadeIn 0.5s ease forwards ${index * 0.1}s`;
            });

            // Add keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === '/') {
                    e.preventDefault();
                    document.querySelector('.search-bar').focus();
                }
            });
        });

        // Handle service worker for offline support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(err => {
                    console.log('ServiceWorker registration failed:', err);
                });
            });
        }
    </script>
</body>
</html>
<?php
// Flush the output buffer and send to browser
ob_end_flush();
?>