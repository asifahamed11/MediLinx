<?php
session_start();
const API_KEY = '**************'; // GEMINI API KEY
const CACHE_LIFETIME = 3; // 3s cache lifetime for tips

function generate_health_tips()
{
    // Check for cached tips in a file to reduce API calls
    $cache_file = __DIR__ . '/cache/health_tips.json';
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < CACHE_LIFETIME)) {
        return file_get_contents($cache_file);
    }

    $prompt = "Generate 21 professional health tips in this exact format:
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

function get_fallback_tips()
{
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

$CATEGORY_ICONS = [
    'nutrition' => 'fa-apple-alt',
    'exercise' => 'fa-dumbbell',
    'mental health' => 'fa-brain',
    'sleep' => 'fa-moon',
    'hydration' => 'fa-glass-water'
];

function get_category_icon($category)
{
    global $CATEGORY_ICONS;
    return $CATEGORY_ICONS[strtolower($category)] ?? 'fa-check';
}


$CATEGORY_TIPS_CACHE = [];

function get_category_tips($category)
{
    global $CATEGORY_TIPS_CACHE;

    $category = strtolower($category);


    if (isset($CATEGORY_TIPS_CACHE[$category])) {
        return $CATEGORY_TIPS_CACHE[$category];
    }

    switch ($category) {
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
function get_or_create_tips()
{
    $cache_key = 'health_tips_' . date('Ymd');
    $session_key = 'tips_' . $cache_key;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh'])) {
        $tips = generate_health_tips();
        $_SESSION[$session_key] = $tips;
        return $tips;
    }


    if (isset($_SESSION[$session_key])) {
        return $_SESSION[$session_key];
    }


    $tips = generate_health_tips();
    $_SESSION[$session_key] = $tips;
    return $tips;
}


ob_start();


$tips_content = get_or_create_tips();


$parsed_tips = [];
$tips = explode("\n", trim($tips_content));
foreach ($tips as $tip) {
    if (preg_match('/\[Category:\s*(.*?)\s*\].*Tip\s+\d+:\s*(.*?)\s*\|\s*(.*)/', $tip, $matches)) {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2A9D8F;
            --primary-light: #4ecdc4;
            --primary-dark: #1a7168;
            --accent: #F4A261;
            --accent-light: #f8c291;
            --background: #f8fafc;
            --card-bg: #ffffff;
            --text: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
            --shadow: rgba(0, 0, 0, 0.1);
            --shadow-lg: rgba(0, 0, 0, 0.15);
            --gradient-start: #2A9D8F;
            --gradient-end: #264653;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --fast-transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="dark"] {
            --primary: #4ecdc4;
            --primary-light: #64dfb4;
            --primary-dark: #2A9D8F;
            --accent: #F4A261;
            --accent-light: #f8c291;
            --background: #111827;
            --card-bg: #1f2937;
            --text: rgb(212, 216, 221);
            --text-light: #9ca3af;
            --border: #374151;
            --shadow: rgba(0, 0, 0, 0.25);
            --shadow-lg: rgba(0, 0, 0, 0.35);
            --gradient-start: #4ecdc4;
            --gradient-end: #1f2937;
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
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
            animation: slideDown 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .health-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
            display: inline-block;
            animation: float 3s ease-in-out infinite;
            background: linear-gradient(to right, var(--primary), var(--accent));
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .title {
            font-size: 3rem;
            color: var(--text);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            position: relative;
        }

        .title i {
            color: var(--primary);
            animation: pulse 3s infinite;
        }

        .title::after {
            content: '';
            position: absolute;
            bottom: -0.5rem;
            left: 50%;
            transform: translateX(-50%);
            width: 180px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-light), var(--primary-dark));
            border-radius: 3px;
        }

        .subtitle {
            color: var(--text-light);
            font-size: 1.25rem;
            max-width: 700px;
            margin: 1.5rem auto 2.5rem;
            line-height: 1.8;
        }

        .search-container {
            max-width: 700px;
            margin: 0 auto;
            position: relative;
        }

        .search-wrapper {
            position: relative;
            margin-bottom: 2rem;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .search-wrapper:focus-within {
            transform: translateY(-5px);
        }

        .search-bar {
            width: 100%;
            padding: 1.25rem 1.5rem 1.25rem 3.5rem;
            border: 2px solid var(--border);
            border-radius: 3rem;
            background-color: var(--card-bg);
            color: var(--text);
            font-size: 1.1rem;
            transition: var(--transition);
            box-shadow: 0 4px 6px var(--shadow);
        }

        .search-bar:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 8px 16px var(--shadow), 0 0 0 4px rgba(42, 157, 143, 0.2);
        }

        .search-icon {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.25rem;
            transition: var(--transition);
        }

        .search-wrapper:focus-within .search-icon {
            color: var(--primary);
        }

        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
            margin-bottom: 2.5rem;
            perspective: 600px;
        }

        .filter-btn {
            padding: 0.9rem 1.8rem;
            border: none;
            border-radius: 2rem;
            background-color: var(--card-bg);
            color: var(--text);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 1px solid var(--border);
            font-weight: 600;
            box-shadow: 0 4px 6px var(--shadow);
            position: relative;
            overflow: hidden;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transform-style: preserve-3d;
        }

        .filter-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, var(--primary-light), var(--primary));
            z-index: -1;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .filter-btn:hover {
            color: white;
            transform: translateY(-5px) rotateX(10deg);
            box-shadow: 0 10px 20px var(--shadow-lg);
            border-color: var(--primary);
        }

        .filter-btn:hover::before {
            transform: scaleX(1);
        }

        .filter-btn i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .filter-btn:hover i {
            transform: scale(1.2);
        }

        .filter-btn.active {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            border-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 15px var(--shadow-lg);
        }

        .filter-btn.active::before {
            transform: scaleX(0);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2.5rem;
            padding: 1rem 0;
            perspective: 1000px;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 1.5rem;
            box-shadow: 0 6px 12px var(--shadow);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
            opacity: 0;
            transform: translateY(20px) rotateX(10deg);
            transform-style: preserve-3d;
            transform-origin: top center;
            height: 100%;
            border: 1px solid var(--border);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            transform: scaleX(0);
            transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform-origin: left;
            z-index: 10;
        }

        .card:hover {
            transform: translateY(-10px) rotateX(3deg) scale(1.03);
            box-shadow: 0 15px 30px var(--shadow-lg);
            border-color: var(--primary-light);
        }

        .card:hover::before {
            transform: scaleX(1);
        }

        .card-content {
            padding: 2.5rem;
            position: relative;
            z-index: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
            pointer-events: none;
        }

        .card:hover::after {
            opacity: 1;
        }

        .card h2 {
            color: var(--text);
            font-size: 1.4rem;
            margin-bottom: 1.25rem;
            line-height: 1.4;
            font-weight: 700;
            position: relative;
            padding-bottom: 0.75rem;
        }

        .card h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
            transition: width 0.4s ease;
        }

        .card:hover h2::after {
            width: 80px;
            background: linear-gradient(to right, var(--primary), var(--accent));
        }

        .card p {
            color: var(--text-light);
            margin-bottom: auto;
            font-size: 1.05rem;
            line-height: 1.7;
        }

        .category-tag {
            position: absolute;
            right: 0.5rem;
            bottom: 0.5rem;
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 2rem;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            transform: translateY(0);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 3px 6px var(--shadow);
            z-index: 10;
        }

        .card:hover .category-tag {
            transform: translateY(-5px) scale(1.05);
            background: linear-gradient(to right, var(--primary-dark), var(--primary));
            box-shadow: 0 6px 12px var(--shadow-lg);
        }

        .category-tag i {
            font-size: 1rem;
        }

        .refresh-container {
            text-align: center;
            margin: 2.5rem 0;
            animation: fadeIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.6s backwards;
        }

        .refresh-button {
            padding: 1.2rem 2.5rem;
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 3rem;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            font-weight: 600;
            box-shadow: 0 6px 12px var(--shadow);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .refresh-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, var(--primary-dark), var(--primary));
            z-index: -1;
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .refresh-button:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 20px var(--shadow-lg);
        }

        .refresh-button:hover::before {
            transform: scaleX(1);
        }

        .refresh-button i {
            transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .refresh-button:hover i {
            transform: rotate(360deg);
        }

        .theme-toggle {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            background: var(--card-bg);
            border: 2px solid var(--border);
            color: var(--text);
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 4px 8px var(--shadow);
            z-index: 1000;
            font-size: 1.2rem;
        }

        .theme-toggle:hover {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            transform: rotate(360deg) scale(1.1);
            box-shadow: 0 8px 16px var(--shadow-lg);
            border-color: var(--primary);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
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
                opacity: 1;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .title {
                font-size: 2.2rem;
            }

            .subtitle {
                font-size: 1.1rem;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .filter-buttons {
                gap: 0.5rem;
                margin-bottom: 2rem;
            }

            .filter-btn {
                padding: 0.7rem 1.2rem;
                font-size: 0.9rem;
            }

            .card {
                margin: 0 0.5rem;
            }

            .theme-toggle {
                top: auto;
                bottom: 1.5rem;
                right: 1.5rem;
            }

            .search-bar {
                padding: 1rem 1rem 1rem 3rem;
                font-size: 1rem;
            }

            .refresh-button {
                padding: 1rem 2rem;
                font-size: 1rem;
            }
        }

        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.4s ease;
        }

        .loading.active {
            opacity: 1;
            pointer-events: auto;
        }

        .loading-spinner {
            width: 70px;
            height: 70px;
            position: relative;
        }

        .loading-spinner:before,
        .loading-spinner:after {
            content: '';
            position: absolute;
            border-radius: 50%;
            animation: pulsOut 1.8s ease-in-out infinite;
            filter: drop-shadow(0 0 1rem var(--primary-light));
        }

        .loading-spinner:before {
            width: 100%;
            height: 100%;
            background-color: rgba(42, 157, 143, 0.6);
            animation-delay: 0.35s;
        }

        .loading-spinner:after {
            width: 75%;
            height: 75%;
            background-color: rgba(42, 157, 143, 0.8);
            top: 12.5%;
            left: 12.5%;
        }

        @keyframes pulsOut {

            0%,
            100% {
                transform: scale(0);
                opacity: 1;
            }

            50% {
                transform: scale(1);
                opacity: 0;
            }
        }

        .back {
            position: fixed;
            top: 1.5rem;
            left: 1.5rem;
            padding: 1rem 1.8rem;
            background: linear-gradient(to right, #E76F51, #F4A261);
            border: none;
            border-radius: 2rem;
            color: white;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .back:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            background: linear-gradient(to right, #E57245, #E5A261);
        }

        .back i {
            transition: transform 0.3s ease;
        }

        .back:hover i {
            transform: translateX(-5px);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            background: var(--background);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 6px;
            border: 3px solid var(--background);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Card animations */
        @keyframes cardAppear {
            from {
                opacity: 0;
                transform: translateY(30px) rotateX(20deg);
            }

            to {
                opacity: 1;
                transform: translateY(0) rotateX(0);
            }
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--card-bg);
            color: var(--text);
            padding: 1rem 2rem;
            border-radius: 2rem;
            box-shadow: 0 5px 15px var(--shadow-lg);
            z-index: 2000;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 1px solid var(--border);
        }

        .toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        .toast i {
            color: var(--primary);
            font-size: 1.2rem;
        }

        /* Keyboard shortcut hints */
        .keyboard-hint {
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: var(--border);
            color: var(--text-light);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            opacity: 0.5;
            transition: var(--transition);
        }

        .search-wrapper:focus-within .keyboard-hint {
            opacity: 0;
        }

        /* Additional animations */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            opacity: 0.4;
        }

        .animated-bg-circle {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(to right, var(--primary-light), var(--primary));
            opacity: 0.1;
            filter: blur(60px);
            animation: moveAround 20s ease-in-out infinite alternate;
        }

        .animated-bg-circle:nth-child(1) {
            width: 400px;
            height: 400px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .animated-bg-circle:nth-child(2) {
            width: 350px;
            height: 350px;
            top: 60%;
            right: 5%;
            background: linear-gradient(to right, var(--accent-light), var(--accent));
            animation-delay: -5s;
        }

        .animated-bg-circle:nth-child(3) {
            width: 250px;
            height: 250px;
            bottom: 5%;
            left: 30%;
            background: linear-gradient(to right, var(--primary-dark), var(--primary));
            animation-delay: -10s;
        }

        @keyframes moveAround {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(100px, 50px);
            }
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: var(--card-bg);
            color: var(--text);
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
            box-shadow: 0 3px 6px var(--shadow);
            border: 1px solid var(--border);
            font-size: 0.8rem;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>

<body>
    <div class="animated-bg">
        <div class="animated-bg-circle"></div>
        <div class="animated-bg-circle"></div>
        <div class="animated-bg-circle"></div>
    </div>

    <div class="loading">
        <div class="loading-spinner"></div>
    </div>

    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toast-message">Changes saved successfully!</span>
    </div>

    <a href="index.php" class="back"><i class="fas fa-arrow-left"></i> Back to Home</a>

    <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle theme">
        <i class="fas fa-moon"></i>
    </button>

    <div class="container">
        <header class="header">
            <div class="health-icon">
                <i class="fas fa-heartbeat"></i>
            </div>
            <h1 class="title">
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
                        id="search-input">
                    <i class="fas fa-search search-icon"></i>
                    <span class="keyboard-hint">Press / to search</span>
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
                <form method="post" class="refresh-container" onsubmit="showLoading(); showToast('Generating fresh health tips for you!');">
                    <button type="submit" name="refresh" class="refresh-button">
                        <i class="fas fa-sync-alt"></i>
                        Generate New Tips
                    </button>
                </form>
            </div>
        </header>

        <div class="grid" id="tips-grid">
            <?php foreach ($parsed_tips as $index => $tip): ?>
                <div class="card" data-category="<?= htmlspecialchars($tip['category'], ENT_QUOTES, 'UTF-8') ?>" style="animation-delay: <?= $index * 0.1 ?>s;">
                    <div class="card-content">
                        <h2><?= htmlspecialchars($tip['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p><?= htmlspecialchars($tip['content'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="category-tag">
                            <i class="fas <?= get_category_icon($tip['category']) ?>"></i>

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

        // Enhanced search functionality with animations
        function filterCards(searchTerm) {
            const cards = document.querySelectorAll('.card');
            const lowercaseSearch = searchTerm.toLowerCase();
            let visibleCount = 0;
            let hasResults = false;

            cards.forEach(card => {
                const content = card.textContent.toLowerCase();
                const category = card.dataset.category;
                const isVisible = content.includes(lowercaseSearch) ||
                    category.includes(lowercaseSearch);

                if (isVisible) {
                    card.style.display = 'block';
                    // Reset animation to trigger it again
                    card.style.animation = 'none';
                    card.offsetHeight; // Force reflow
                    card.style.animation = `cardAppear 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards ${visibleCount * 0.08}s`;
                    visibleCount++;
                    hasResults = true;
                } else {
                    card.style.display = 'none';
                }
            });

            // Show message when no results
            if (!hasResults && searchTerm) {
                showToast('No tips found matching "' + searchTerm + '"');
            }
        }

        // Enhanced category filtering with animations
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
                    // Reset animation to trigger it again
                    card.style.animation = 'none';
                    card.offsetHeight; // Force reflow
                    card.style.animation = `cardAppear 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards ${visibleCount * 0.08}s`;
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (category !== 'all') {
                showToast(`Showing ${visibleCount} ${category} tips`);
            } else {
                showToast('Showing all health tips');
            }
        }

        // Toast notification system
        function showToast(message, duration = 3000) {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');

            toastMessage.textContent = message;
            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
            }, duration);
        }

        // Theme management with enhanced animation
        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            const themeIcon = document.querySelector('.theme-toggle i');
            themeIcon.className = `fas fa-${newTheme === 'dark' ? 'sun' : 'moon'}`;

            showToast(`Switched to ${newTheme} mode`);
        }

        // Loading state management
        function showLoading() {
            document.querySelector('.loading').classList.add('active');

            // Auto hide after timeout (fallback safety)
            setTimeout(() => {
                document.querySelector('.loading').classList.remove('active');
            }, 10000);
        }

        // Initialize theme, animations and event listeners
        document.addEventListener('DOMContentLoaded', () => {
            // Set theme
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);

            const themeIcon = document.querySelector('.theme-toggle i');
            themeIcon.className = `fas fa-${savedTheme === 'dark' ? 'sun' : 'moon'}`;

            // Animate cards on load with staggered timing
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.animation = `cardAppear 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards ${index * 0.08}s`;
            });

            // Add keyboard navigation
            document.addEventListener('keydown', (e) => {
                // Press '/' to focus search
                if (e.key === '/' && document.activeElement.tagName !== 'INPUT') {
                    e.preventDefault();
                    document.getElementById('search-input').focus();
                }

                // Escape key to clear search
                if (e.key === 'Escape') {
                    document.getElementById('search-input').value = '';
                    filterCards('');
                }

                // Number keys 1-6 for category filtering
                if (!isNaN(parseInt(e.key)) && parseInt(e.key) >= 1 && parseInt(e.key) <= 6 && document.activeElement.tagName !== 'INPUT') {
                    const categories = ['all', 'nutrition', 'exercise', 'mental health', 'sleep', 'hydration'];
                    const selectedCategory = categories[parseInt(e.key) - 1];
                    filterByCategory(selectedCategory);
                }
            });

            // Add card click to copy functionality
            cards.forEach(card => {
                card.addEventListener('click', function(e) {
                    // Don't copy if clicking on category tag
                    if (e.target.closest('.category-tag')) return;

                    const title = this.querySelector('h2').textContent;
                    const content = this.querySelector('p').textContent;
                    const category = this.dataset.category;

                    const textToCopy = `${title}: ${content} (${category})`;

                    navigator.clipboard.writeText(textToCopy).then(() => {
                        showToast('Tip copied to clipboard!');
                    }).catch(err => {
                        console.error('Could not copy text: ', err);
                    });
                });
            });

            // Show welcome toast
            setTimeout(() => {
                showToast('Welcome to Smart Health Advisor! 💪');
            }, 1000);
        });

        // Handle service worker for offline support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(err => {
                    console.log('ServiceWorker registration failed:', err);
                });
            });
        }

        // Add animation to filter buttons
        document.querySelectorAll('.filter-btn').forEach((btn, index) => {
            btn.style.animationDelay = `${0.3 + (index * 0.1)}s`;
        });
    </script>
</body>

</html>
<?php
// Flush the output buffer and send to browser
ob_end_flush();
?>
