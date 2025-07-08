<?php
session_start();
require_once 'config.php';

// Verify user authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user needs to verify email
if (isset($_SESSION['needs_verification']) && $_SESSION['needs_verification'] === true) {
    header("Location: verify_pin.php");
    exit;
}

// Double check if email is verified in database
$conn = connectDB();
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email_verified_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if ($row['email_verified_at'] === NULL) {
        $_SESSION['needs_verification'] = true;
        header("Location: verify_pin.php");
        exit;
    }
}
$stmt->close();
$conn->close();

// Helper function to sanitize input
function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Helper function to convert language code to language name
function getLanguageName($languageCode)
{
    $languageMap = [
        'af' => 'Afrikaans',
        'ar' => 'Arabic',
        'bg' => 'Bulgarian',
        'bn' => 'Bengali',
        'ca' => 'Catalan',
        'cs' => 'Czech',
        'da' => 'Danish',
        'de' => 'German',
        'el' => 'Greek',
        'en' => 'English',
        'es' => 'Spanish',
        'et' => 'Estonian',
        'fa' => 'Persian',
        'fi' => 'Finnish',
        'fil' => 'Filipino',
        'fr' => 'French',
        'gu' => 'Gujarati',
        'he' => 'Hebrew',
        'hi' => 'Hindi',
        'hr' => 'Croatian',
        'hu' => 'Hungarian',
        'id' => 'Indonesian',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'kn' => 'Kannada',
        'ko' => 'Korean',
        'lt' => 'Lithuanian',
        'lv' => 'Latvian',
        'ml' => 'Malayalam',
        'mr' => 'Marathi',
        'ms' => 'Malay',
        'nl' => 'Dutch',
        'no' => 'Norwegian',
        'pl' => 'Polish',
        'pt' => 'Portuguese',
        'ro' => 'Romanian',
        'ru' => 'Russian',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'sr' => 'Serbian',
        'sv' => 'Swedish',
        'sw' => 'Swahili',
        'ta' => 'Tamil',
        'te' => 'Telugu',
        'th' => 'Thai',
        'tr' => 'Turkish',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'vi' => 'Vietnamese',
        'zh' => 'Chinese'
    ];

    return $languageMap[$languageCode] ?? $languageCode;
}

// Function to search for AI-recommended doctors based on symptoms
function getAIRecommendedDoctors($symptom, $conn)
{
    // Define the API key - we'll use the same key from health_tips.php
    $GEMINI_API_KEY = '**************';//GEMINI API KEY

    // Log the start of the search process
    error_log("AI Doctor Search started for symptom: $symptom");

    // Detect language and translate if not in English
    $originalSymptom = $symptom;
    $detectedLanguage = null;
    $translatedSymptom = null;

    // Check if symptom might not be in English
    if (!preg_match('/^[a-zA-Z0-9\s\.,\-\(\)]+$/', $symptom)) {
        error_log("Detected potentially non-English symptom: $symptom");

        try {
            // Call Google Translate API to detect language and translate to English
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://translation.googleapis.com/language/translate/v2/detect?key=' . $GEMINI_API_KEY,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'q' => $symptom
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 15 // Increased timeout for more reliable results
            ]);

            $detectResponse = curl_exec($ch);
            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                throw new Exception('Language detection failed: ' . curl_error($ch));
            }

            if ($httpStatus != 200) {
                throw new Exception("Language detection API returned status code: $httpStatus");
            }

            curl_close($ch);

            $detectData = json_decode($detectResponse, true);
            if (isset($detectData['data']['detections'][0][0]['language'])) {
                $detectedLanguage = $detectData['data']['detections'][0][0]['language'];
                $detectionConfidence = $detectData['data']['detections'][0][0]['confidence'] ?? 0;
                error_log("Detected language: $detectedLanguage with confidence: $detectionConfidence");

                // Only translate if not English and confidence is reasonable
                if ($detectedLanguage != 'en' && $detectionConfidence > 0.5) {
                    // Translate to English
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => 'https://translation.googleapis.com/language/translate/v2?key=' . $GEMINI_API_KEY,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => json_encode([
                            'q' => $symptom,
                            'source' => $detectedLanguage,
                            'target' => 'en',
                            'format' => 'text'
                        ]),
                        CURLOPT_HTTPHEADER => [
                            'Content-Type: application/json'
                        ],
                        CURLOPT_TIMEOUT => 15
                    ]);

                    $translateResponse = curl_exec($ch);
                    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                    if (curl_errno($ch)) {
                        throw new Exception('Translation failed: ' . curl_error($ch));
                    }

                    if ($httpStatus != 200) {
                        throw new Exception("Translation API returned status code: $httpStatus");
                    }

                    curl_close($ch);

                    $translateData = json_decode($translateResponse, true);
                    if (isset($translateData['data']['translations'][0]['translatedText'])) {
                        $translatedSymptom = $translateData['data']['translations'][0]['translatedText'];
                        $symptom = $translatedSymptom;
                        error_log("Translated symptom: $symptom");
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Translation error: " . $e->getMessage());
            // Continue with original symptom if translation fails
        }
    }

    // Build prompt that will help the AI suggest doctor specialties based on symptoms
    $prompt = "As a medical AI assistant, analyze the following symptom or health concern: \"$symptom\". 
    
    First, identify the most appropriate medical specialist type(s) for this symptom.
    Then, provide your response in this exact JSON format:
    {
      \"specialties\": [\"Primary Specialty\", \"Secondary Specialty\", \"Tertiary Specialty\"],
      \"urgency\": \"routine|urgent|emergency\",
      \"body_system\": \"cardiovascular|respiratory|neurological|digestive|musculoskeletal|dermatological|endocrine|urological|reproductive|psychological\"
    }
    
    For specialties, use standard medical specialty names that would be found in healthcare databases.
    For urgency, use:
    - emergency: for life-threatening conditions requiring immediate care
    - urgent: for serious conditions requiring prompt attention within 24 hours
    - routine: for non-urgent conditions
    
    Return ONLY the JSON, with no explanations before or after.";

    // Direct mapping for emergency symptoms (bypass AI for critical conditions)
    $directMapping = false;
    $emergencyKeywords = [
        'chest pain',
        'difficulty breathing',
        'shortness of breath',
        'severe bleeding',
        'unconscious',
        'unresponsive',
        'stroke',
        'heart attack',
        'seizure',
        'overdose',
        'poisoning',
        'anaphylaxis',
        'allergic reaction',
        'suicide',
        'paro cardíaco',
        'ataque cardíaco',
        'dificultad para respirar',
        'inconsciente',
        'convulsiones'
    ];

    foreach ($emergencyKeywords as $keyword) {
        if (
            stripos($symptom, $keyword) !== false ||
            ($translatedSymptom && stripos($translatedSymptom, $keyword) !== false)
        ) {
            $specialties = "Emergency Medicine, Cardiologist, Critical Care";
            $directMapping = true;
            $urgency = "emergency";
            $bodySystem = "cardiovascular";
            error_log("EMERGENCY DETECTED: Direct mapping for keyword '$keyword' to: $specialties");
            break;
        }
    }

    try {
        $specialtyInfo = [];

        // Call Gemini API to get suggested specialties if we don't have a direct mapping
        if (!$directMapping) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'topP' => 0.8,
                        'topK' => 40
                    ]
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-goog-api-key: ' . $GEMINI_API_KEY
                ],
                CURLOPT_TIMEOUT => 20
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

            // Parse the API response
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to parse API response: ' . json_last_error_msg());
            }

            $responseText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            error_log("Raw AI response: " . $responseText);

            // Try to parse JSON from the response
            try {
                // Clean the response text to ensure it contains only the JSON part
                $jsonText = trim($responseText);

                // Remove any markdown code block markers
                $jsonText = preg_replace('/```json|```/', '', $jsonText);

                // Attempt to parse the JSON
                $specialtyInfo = json_decode($jsonText, true);

                if (json_last_error() !== JSON_ERROR_NONE || !is_array($specialtyInfo)) {
                    throw new Exception('Invalid JSON format in AI response: ' . json_last_error_msg());
                }

                error_log("Successfully parsed specialty info: " . json_encode($specialtyInfo));
            } catch (Exception $e) {
                error_log("Failed to parse JSON from AI response: " . $e->getMessage());

                // Fallback: Try to extract specialties with regex
                preg_match_all('/"([^"]+)"/', $responseText, $matches);
                if (!empty($matches[1])) {
                    $specialtyInfo = [
                        'specialties' => array_slice($matches[1], 0, 3),
                        'urgency' => 'routine',
                        'body_system' => 'unknown'
                    ];
                    error_log("Used regex fallback to extract specialties: " . json_encode($specialtyInfo['specialties']));
                } else {
                    // If all else fails, use default specialties
                    $specialtyInfo = [
                        'specialties' => ['General Practitioner', 'Family Medicine'],
                        'urgency' => 'routine',
                        'body_system' => 'unknown'
                    ];
                    error_log("Used default specialties due to parsing failure");
                }
            }

            // Final validation of specialty info structure
            if (!isset($specialtyInfo['specialties']) || !is_array($specialtyInfo['specialties'])) {
                $specialtyInfo['specialties'] = ['General Practitioner'];
            }
            if (!isset($specialtyInfo['urgency'])) {
                $specialtyInfo['urgency'] = 'routine';
            }
            if (!isset($specialtyInfo['body_system'])) {
                $specialtyInfo['body_system'] = 'unknown';
            }
        } else {
            // Use the direct mapping for emergency cases
            $specialtyInfo = [
                'specialties' => explode(', ', $specialties),
                'urgency' => $urgency,
                'body_system' => $bodySystem
            ];
        }

        // Store the array of specialties for display purposes
        $displaySpecialties = $specialtyInfo['specialties'];
        $urgencyLevel = $specialtyInfo['urgency'];
        $bodySystem = $specialtyInfo['body_system'];

        // Prepare array to store doctors
        $doctors = [];

        // Create a query to find doctors with these specialties
        if (!empty($displaySpecialties)) {
            // Map common specialty variations to standard terms
            $specialtyMap = [
                // Cardiovascular
                'cardiologist' => ['cardiology', 'heart specialist', 'heart doctor', 'cardiac'],
                // Respiratory
                'pulmonologist' => ['pulmonology', 'lung specialist', 'respiratory'],
                // Neurology
                'neurologist' => ['neurology', 'nerve specialist', 'brain specialist'],
                // Gastroenterology
                'gastroenterologist' => ['gastroenterology', 'digestive specialist', 'stomach doctor'],
                // Orthopedics
                'orthopedist' => ['orthopedics', 'orthopaedic', 'bone specialist', 'joint specialist'],
                // Dermatology
                'dermatologist' => ['dermatology', 'skin specialist', 'skin doctor'],
                // Psychology/Psychiatry
                'psychiatrist' => ['psychiatry', 'mental health specialist'],
                'psychologist' => ['psychology', 'counselor', 'therapist'],
                // General
                'general practitioner' => ['gp', 'family doctor', 'primary care'],
                'family medicine' => ['family practice', 'family physician', 'primary care'],
                // Emergency
                'emergency medicine' => ['emergency physician', 'emergency doctor', 'er doctor'],
                // OB/GYN
                'obstetrician' => ['obstetrics', 'pregnancy doctor', 'ob/gyn'],
                'gynecologist' => ['gynecology', 'women\'s health specialist', 'ob/gyn']
            ];

            // Expand specialties with their variations
            $expandedSpecialties = [];
            foreach ($displaySpecialties as $specialty) {
                $specialty = trim(strtolower($specialty));
                $expandedSpecialties[] = $specialty;

                // Add variations from the map
                foreach ($specialtyMap as $mainTerm => $variations) {
                    if ($specialty == $mainTerm || in_array($specialty, $variations)) {
                        // Add the main term and all variations
                        $expandedSpecialties[] = $mainTerm;
                        $expandedSpecialties = array_merge($expandedSpecialties, $variations);
                    }
                }
            }

            // Remove duplicates and standardize
            $expandedSpecialties = array_unique(array_map('trim', $expandedSpecialties));

            // Log what we're searching for (debugging)
            error_log("Searching for expanded specialties: " . implode(", ", $expandedSpecialties));

            // Build the advanced query with prioritization
            $query = "SELECT u.*,
                    COALESCE((SELECT AVG(rating) FROM reviews WHERE doctor_id = u.id), 0) as avg_rating,
                    (SELECT COUNT(*) FROM reviews WHERE doctor_id = u.id) as review_count,
                    GROUP_CONCAT(DISTINCT d.degree_name SEPARATOR ', ') as degrees,
                    CASE 
                        WHEN u.specialty IS NOT NULL AND TRIM(u.specialty) != '' THEN 1
                        ELSE 0
                    END as has_specialty
                    FROM users u
                    LEFT JOIN degrees d ON u.id = d.doctor_id
                    WHERE u.role = 'doctor'";

            $searchTerms = [];
            $conditions = [];

            // First check for exact matches
            foreach ($expandedSpecialties as $specialty) {
                $conditions[] = "LOWER(u.specialty) = LOWER(?)";
                $searchTerms[] = $specialty;
            }

            // Then check for LIKE matches
            foreach ($expandedSpecialties as $specialty) {
                // Use word boundary for more precise matching
                $conditions[] = "LOWER(u.specialty) LIKE ?";
                $searchTerms[] = "%" . strtolower($specialty) . "%";
            }

            // Add specialized LIKE patterns for better matching
            foreach ($expandedSpecialties as $specialty) {
                // Match at beginning of specialty field
                $conditions[] = "LOWER(u.specialty) LIKE ?";
                $searchTerms[] = strtolower($specialty) . "%";

                // Match as a whole word
                $conditions[] = "LOWER(u.specialty) LIKE ?";
                $searchTerms[] = "% " . strtolower($specialty) . " %";
            }

            // Combine all conditions with OR
            if (!empty($conditions)) {
                $query .= " AND (" . implode(" OR ", $conditions) . ")";
            }

            $query .= " GROUP BY u.id";

            // Add ordering logic based on match quality and ratings
            $query .= " ORDER BY 
                    CASE 
                         WHEN LOWER(u.specialty) = LOWER(?) THEN 10 
                         WHEN LOWER(u.specialty) LIKE ? THEN 8
                         WHEN LOWER(u.specialty) LIKE ? THEN 6
                         WHEN LOWER(u.specialty) LIKE ? THEN 4
                         ELSE 1 
                     END DESC,
                     has_specialty DESC,
                     avg_rating DESC, 
                     review_count DESC, 
                     u.years_of_experience DESC 
                     LIMIT 15";

            // Add the primary specialty for ordering parameters
            $primarySpecialty = strtolower(trim($displaySpecialties[0]));
            $searchTerms[] = $primarySpecialty; // Exact match
            $searchTerms[] = "%" . $primarySpecialty . "%"; // Contains
            $searchTerms[] = $primarySpecialty . "%"; // Starts with
            $searchTerms[] = "% " . $primarySpecialty . " %"; // Whole word

            // Set types string to match parameter count
            $types = str_repeat('s', count($searchTerms));

            // Log the query parameters for debugging
            error_log("SQL Query: " . $query);
            error_log("Search terms count: " . count($searchTerms));
            error_log("Search terms: " . implode(", ", array_slice($searchTerms, 0, min(10, count($searchTerms)))));

            // Execute the query
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                error_log("Query preparation failed: " . $conn->error);
                throw new Exception("Query preparation failed: " . $conn->error);
            }

            try {
                // Ensure the types string matches the number of parameters
                if (strlen($types) !== count($searchTerms)) {
                    $types = str_repeat('s', count($searchTerms));
                }

                $stmt->bind_param($types, ...$searchTerms);
                $stmt->execute();
                $result = $stmt->get_result();
                $doctors = $result->fetch_all(MYSQLI_ASSOC);

                // Log the results (debugging)
                error_log("Found " . count($doctors) . " doctors for the query");
            } catch (Exception $e) {
                error_log("Error executing specialty query: " . $e->getMessage());
                // We'll continue to the fallback mechanisms
            }

            // If no doctors found or too few, try a more focused approach with body system
            if (count($doctors) < 3 && !empty($bodySystem) && $bodySystem != 'unknown') {
                error_log("Trying body system fallback with system: $bodySystem");

                $bodySystemKeywords = [
                    'cardiovascular' => ['heart', 'cardio', 'cardiac', 'vascular', 'circulation'],
                    'respiratory' => ['lung', 'pulmonary', 'respiratory', 'breathing', 'pulmonologist'],
                    'neurological' => ['brain', 'neuro', 'nerve', 'neurologist', 'nervous system'],
                    'digestive' => ['gastro', 'digestive', 'stomach', 'intestine', 'gi', 'liver'],
                    'musculoskeletal' => ['muscle', 'bone', 'joint', 'orthopedic', 'skeletal', 'spine'],
                    'dermatological' => ['skin', 'derma', 'rash', 'dermatology'],
                    'endocrine' => ['hormone', 'thyroid', 'diabetes', 'endocrine', 'metabolism'],
                    'urological' => ['kidney', 'bladder', 'urinary', 'urology'],
                    'reproductive' => ['reproductive', 'fertility', 'gynecology', 'obstetrics', 'ob/gyn', 'urology'],
                    'psychological' => ['mental', 'psych', 'psychiatry', 'psychology', 'behavior']
                ];

                if (isset($bodySystemKeywords[$bodySystem])) {
                    $systemTerms = $bodySystemKeywords[$bodySystem];

                    $systemQuery = "SELECT u.*,
                            COALESCE((SELECT AVG(rating) FROM reviews WHERE doctor_id = u.id), 0) as avg_rating,
                            (SELECT COUNT(*) FROM reviews WHERE doctor_id = u.id) as review_count,
                            GROUP_CONCAT(DISTINCT d.degree_name SEPARATOR ', ') as degrees
                            FROM users u
                            LEFT JOIN degrees d ON u.id = d.doctor_id
                        WHERE u.role = 'doctor' AND (";

                    $systemConditions = [];
                    $systemParams = [];
                    $systemTypes = '';

                    foreach ($systemTerms as $term) {
                        $systemConditions[] = "LOWER(u.specialty) LIKE ?";
                        $systemParams[] = "%" . strtolower($term) . "%";
                        $systemTypes .= 's';
                    }

                    $systemQuery .= implode(' OR ', $systemConditions) . ")
                            GROUP BY u.id
                            ORDER BY avg_rating DESC, review_count DESC
                            LIMIT 10";

                    try {
                        $systemStmt = $conn->prepare($systemQuery);
                        $systemStmt->bind_param($systemTypes, ...$systemParams);
                        $systemStmt->execute();
                        $systemResult = $systemStmt->get_result();
                        $systemDoctors = $systemResult->fetch_all(MYSQLI_ASSOC);

                        error_log("Found " . count($systemDoctors) . " doctors with body system approach");

                        // Combine results, ensuring no duplicates
                        $existingIds = array_column($doctors, 'id');
                        foreach ($systemDoctors as $doctor) {
                            if (!in_array($doctor['id'], $existingIds)) {
                                $doctors[] = $doctor;
                                $existingIds[] = $doctor['id'];
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Error in body system query: " . $e->getMessage());
                    }
                }
            }

            // If still no doctors or too few found, try a more basic approach
            if (count($doctors) < 5) {
                error_log("Trying with basic keyword approach");

                // Try a more basic approach with just the main keywords
                $query = "SELECT u.*,
                            COALESCE((SELECT AVG(rating) FROM reviews WHERE doctor_id = u.id), 0) as avg_rating,
                            (SELECT COUNT(*) FROM reviews WHERE doctor_id = u.id) as review_count,
                            GROUP_CONCAT(DISTINCT d.degree_name SEPARATOR ', ') as degrees
                            FROM users u
                            LEFT JOIN degrees d ON u.id = d.doctor_id
                            WHERE u.role = 'doctor'";

                // Just use the core words from each specialty
                $basicTerms = [];
                $basicTypes = '';

                foreach ($displaySpecialties as $specialty) {
                    // Get the base word (without -ist, -ician, etc.)
                    $baseWord = preg_replace('/(ologist|iatrist|ician|ist|ic|ics|y)$/i', '', strtolower(trim($specialty)));
                    if (strlen($baseWord) >= 3) {
                        $query .= " AND LOWER(u.specialty) LIKE ?";
                        $basicTerms[] = "%" . $baseWord . "%";
                        $basicTypes .= 's';
                        error_log("Trying base word: $baseWord from $specialty");
                    }
                }

                if (!empty($basicTerms)) {
                    $query .= " GROUP BY u.id ORDER BY avg_rating DESC, review_count DESC LIMIT 10";

                    try {
                        $stmt = $conn->prepare($query);
                        if ($stmt) {
                            // Ensure the types string matches the parameter count
                            if (strlen($basicTypes) !== count($basicTerms)) {
                                $basicTypes = str_repeat('s', count($basicTerms));
                            }

                            $stmt->bind_param($basicTypes, ...$basicTerms);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $basicDoctors = $result->fetch_all(MYSQLI_ASSOC);
                            error_log("Found " . count($basicDoctors) . " doctors with basic approach");

                            // Merge with existing doctors without duplicates
                            $existingIds = array_column($doctors, 'id');
                            foreach ($basicDoctors as $doctor) {
                                if (!in_array($doctor['id'], $existingIds)) {
                                    $doctors[] = $doctor;
                                    $existingIds[] = $doctor['id'];
                                }
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Error in basic query: " . $e->getMessage());
                    }
                }
            }

            // If still no doctors found, provide fallback with highest rated doctors
            if (empty($doctors)) {
                error_log("No doctors found, showing fallback results");
                $fallbackQuery = "SELECT u.*,
                        COALESCE((SELECT AVG(rating) FROM reviews WHERE doctor_id = u.id), 0) as avg_rating,
                        (SELECT COUNT(*) FROM reviews WHERE doctor_id = u.id) as review_count,
                        GROUP_CONCAT(DISTINCT d.degree_name SEPARATOR ', ') as degrees
                        FROM users u
                        LEFT JOIN degrees d ON u.id = d.doctor_id
                        WHERE u.role = 'doctor'
                        GROUP BY u.id
                        ORDER BY avg_rating DESC, review_count DESC
                    LIMIT 8";

                $fallbackStmt = $conn->prepare($fallbackQuery);
                $fallbackStmt->execute();
                $fallbackResult = $fallbackStmt->get_result();
                $doctors = $fallbackResult->fetch_all(MYSQLI_ASSOC);

                // Flag to indicate we're showing fallback results
                $using_fallback = true;
            } else {
                $using_fallback = false;
            }
        }

        return [
            'specialties' => $displaySpecialties,
            'doctors' => $doctors,
            'using_fallback' => $using_fallback ?? false,
            'original_symptom' => $originalSymptom,
            'translated_symptom' => $translatedSymptom,
            'detected_language' => $detectedLanguage,
            'urgency' => $urgencyLevel ?? 'routine',
            'body_system' => $bodySystem ?? 'unknown'
        ];
    } catch (Exception $e) {
        error_log('AI Doctor Search Error: ' . $e->getMessage());
        return [
            'specialties' => [],
            'doctors' => [],
            'error' => $e->getMessage(),
            'original_symptom' => $originalSymptom,
            'translated_symptom' => $translatedSymptom,
            'detected_language' => $detectedLanguage,
            'urgency' => 'unknown',
            'body_system' => 'unknown'
        ];
    }
}

$conn = connectDB();
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$user_id = (int)$_SESSION['user_id'];

// Get current user info
$user_stmt = $conn->prepare("SELECT role, username, profile_image FROM users WHERE id = ?");
if (!$user_stmt) {
    die("Preparation failed: " . $conn->error);
}
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Process AI doctor search if submitted
$ai_search_results = null;
$symptom = '';
$specialty_debug_info = [];

if (isset($_GET['ai_symptom']) && !empty($_GET['ai_symptom'])) {
    $symptom = sanitizeInput($_GET['ai_symptom']);

    // Let's check the structure of the specialty field to better understand the data
    $specialty_query = "SELECT DISTINCT specialty FROM users WHERE role = 'doctor' AND specialty IS NOT NULL AND specialty != '' ORDER BY specialty LIMIT 20";
    $specialty_debug = $conn->query($specialty_query);
    if ($specialty_debug) {
        while ($row = $specialty_debug->fetch_assoc()) {
            $specialty_debug_info[] = $row['specialty'];
        }
        // Log the specialties for debugging
        error_log("Available specialties in database: " . implode(", ", $specialty_debug_info));
    }

    // Check specifically for cardiology-related entries
    $cardio_query = "SELECT id, username, specialty FROM users WHERE role = 'doctor' AND (
        specialty LIKE '%cardio%' OR 
        specialty LIKE '%heart%' OR 
        specialty LIKE '%cardiac%'
    ) LIMIT 10";
    $cardio_debug = $conn->query($cardio_query);
    if ($cardio_debug) {
        $cardio_doctors = [];
        while ($row = $cardio_debug->fetch_assoc()) {
            $cardio_doctors[] = $row['username'] . " (" . $row['specialty'] . ")";
        }
        if (!empty($cardio_doctors)) {
            error_log("Found cardiology doctors: " . implode(", ", $cardio_doctors));
        } else {
            error_log("No cardiology doctors found in database");
        }
    }

    $ai_search_results = getAIRecommendedDoctors($symptom, $conn);
}

// Sanitize and validate filter parameters
$specialty = isset($_GET['specialty']) ? sanitizeInput($_GET['specialty']) : '';
$location = isset($_GET['location']) ? sanitizeInput($_GET['location']) : '';
$experience = isset($_GET['experience']) ? (int)$_GET['experience'] : '';
$rating = isset($_GET['rating']) ? (float)$_GET['rating'] : '';

// Get available specialties
$specialties_query = "SELECT DISTINCT specialty FROM users WHERE specialty IS NOT NULL AND specialty != '' ORDER BY specialty";
$specialties_result = $conn->query($specialties_query);
$specialties = [];
while ($row = $specialties_result->fetch_assoc()) {
    $specialties[] = $row['specialty'];
}

// Build doctor query
$query = "SELECT u.*,
COALESCE((SELECT AVG(rating) FROM reviews WHERE doctor_id = u.id), 0) as avg_rating,
(SELECT COUNT(*) FROM reviews WHERE doctor_id = u.id) as review_count,
GROUP_CONCAT(DISTINCT d.degree_name SEPARATOR ', ') as degrees
FROM users u
LEFT JOIN degrees d ON u.id = d.doctor_id
WHERE u.role = 'doctor'";

$params = [];
$types = '';

if (!empty($specialty)) {
    $query .= " AND u.specialty LIKE ?";
    $params[] = "%$specialty%";
    $types .= 's';
}

if (!empty($location)) {
    $query .= " AND u.work_address LIKE ?";
    $params[] = "%$location%";
    $types .= 's';
}

if (!empty($experience)) {
    $query .= " AND u.years_of_experience >= ?";
    $params[] = $experience;
    $types .= 'i';
}

$query .= " GROUP BY u.id";

if (!empty($rating)) {
    $query .= " HAVING avg_rating >= ?";
    $params[] = $rating;
    $types .= 'd';
}

$query .= " ORDER BY avg_rating DESC, review_count DESC";

// Execute doctor query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$doctors = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Doctors - MediLinx</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #1D7A8C;
            --primary-light: #23949C;
            --primary-dark: #156573;
            --primary-gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            --secondary: #144E5A;
            --secondary-light: #1D7A8C;
            --accent: #E94F37;
            --accent-light: #FF6F59;
            --success: #27AE60;
            --light-bg: #F5F9FB;
            --card-bg: #FFFFFF;
            --text: #2D3B45;
            --text-light: #546E7A;
            --text-lighter: #90A4AE;
            --border: #E1EDF2;
            --shadow-sm: 0 2px 10px rgba(29, 122, 140, 0.05);
            --shadow-md: 0 4px 20px rgba(29, 122, 140, 0.1);
            --shadow-lg: 0 10px 30px rgba(29, 122, 140, 0.15);
            --shadow-xl: 0 15px 40px rgba(29, 122, 140, 0.2);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --radius-sm: 0.5rem;
            --radius-md: 1rem;
            --radius-lg: 1.5rem;
            --transition-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        body {
            background-color: var(--light-bg);
            background-image:
                radial-gradient(at 80% 0%, hsla(189, 65%, 90%, 0.3) 0px, transparent 50%),
                radial-gradient(at 0% 50%, hsla(355, 65%, 90%, 0.2) 0px, transparent 50%),
                radial-gradient(at 80% 100%, hsla(176, 65%, 90%, 0.3) 0px, transparent 50%);
            background-attachment: fixed;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
        }

        * {
            box-sizing: border-box;
        }

        /* Translation info styles */
        .translation-info {
            display: block;
            margin-top: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: rgba(235, 245, 250, 0.7);
            border-left: 3px solid var(--primary);
            border-radius: 0.25rem;
            font-size: 0.9rem;
            color: var(--text-light);
            transition: all 0.3s ease;
        }

        .translation-info i {
            color: var(--primary);
            margin-right: 0.5rem;
        }

        .translation-info em {
            font-weight: 500;
            color: var(--primary-dark);
        }

        /* Add animated pulse highlight for translated text */
        @keyframes pulse-highlight {

            0%,
            100% {
                background-color: rgba(235, 245, 250, 0.7);
            }

            50% {
                background-color: rgba(29, 122, 140, 0.1);
            }
        }

        .translation-info {
            animation: pulse-highlight 3s ease-in-out 1;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-title {
            font-size: 2rem;
            color: var(--secondary);
            margin-bottom: 2rem;
            position: relative;
            font-weight: 700;
            letter-spacing: -0.02em;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            padding-bottom: 8px;
            text-align: center;
            margin-left: auto;
            margin-right: auto;
            display: block;
            max-width: 1100px;
        }

        .page-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--primary-gradient);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(29, 122, 140, 0.3);
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 1.6rem;
                margin-bottom: 1.5rem;
            }

            .page-title:after {
                width: 80px;
            }
        }

        .search-section {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow:
                0 10px 30px rgba(29, 122, 140, 0.1),
                0 1px 1px rgba(255, 255, 255, 0.5) inset,
                0 -1px 1px rgba(255, 255, 255, 0.3) inset;
            margin-bottom: 2.5rem;
            position: relative;
            overflow: hidden;
            transform: translateZ(0);
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.5);
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
        }

        .search-section:hover {
            box-shadow:
                0 15px 40px rgba(29, 122, 140, 0.15),
                0 1px 1px rgba(255, 255, 255, 0.5) inset,
                0 -1px 1px rgba(255, 255, 255, 0.3) inset;
            transform: translateY(-5px);
        }

        .search-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--primary-gradient);
            z-index: 1;
        }

        .search-section::after {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.8) 0%, rgba(255, 255, 255, 0) 70%);
            opacity: 0;
            transform: translate(-30%, -30%);
            transition: opacity 0.8s ease;
            pointer-events: none;
            z-index: 0;
        }

        .search-section:hover::after {
            opacity: 0.4;
        }

        .search-header {
            text-align: center;
            margin-bottom: 1.75rem;
            position: relative;
            z-index: 1;
        }

        .search-header h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.8rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.25rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            text-shadow: 0 2px 5px rgba(29, 122, 140, 0.1);
            position: relative;
            display: inline-block;
        }

        .search-header h1::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -5px;
            width: 35px;
            height: 3px;
            background: var(--primary-gradient);
            transform: translateX(-50%);
            border-radius: 3px;
        }

        .search-header p {
            color: var(--text-light);
            font-size: 0.95rem;
            max-width: 600px;
            margin: 0.75rem auto 0;
            line-height: 1.5;
        }

        /* Modern tabbed interface for search types */
        .search-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 5;
        }

        .search-tab {
            padding: 0.85rem 1.5rem;
            border: none;
            background: transparent;
            color: var(--text-light);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-tab.active {
            color: var(--primary);
        }

        .search-tab:hover {
            color: var(--primary-dark);
            background: rgba(29, 122, 140, 0.05);
        }

        .search-tab i {
            font-size: 1.1rem;
        }

        .search-tab::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%) scaleX(0);
            width: 30px;
            height: 3px;
            background: var(--primary-gradient);
            transition: transform 0.3s var(--transition-bounce);
            transform-origin: center;
            border-radius: 3px;
        }

        .search-tab.active::after {
            transform: translateX(-50%) scaleX(1);
        }

        .tab-indicator {
            position: absolute;
            bottom: 0;
            height: 3px;
            border-radius: 3px;
            background: var(--primary-gradient);
            transition: all 0.3s var(--transition-bounce);
        }

        .search-content {
            position: relative;
            z-index: 1;
        }

        .search-panel {
            display: none;
            animation: fadeIn 0.5s ease forwards;
        }

        .search-panel.active {
            display: block;
        }

        /* Redesigned search forms */
        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        .search-group {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s var(--transition-bounce);
            background: rgba(255, 255, 255, 0.8);
            color: var(--text);
            box-shadow: var(--shadow-sm), 0 1px 2px rgba(255, 255, 255, 0.5) inset;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        .search-button {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 4px 15px rgba(29, 122, 140, 0.25), 0 1px 2px rgba(255, 255, 255, 0.3) inset;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            position: relative;
            overflow: hidden;
            z-index: 1;
            text-transform: uppercase;
            min-width: 180px;
        }

        /* Redesigned search divider */
        .search-divider {
            display: none;
            /* Hide in tabbed layout */
        }

        /* Enhanced AI search section */
        .ai-search-section {
            text-align: center;
            padding: 0.5rem 0 0;
            animation: fadeIn 0.6s ease-out 0.3s both;
        }

        .ai-search-form {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 1rem;
            max-width: 750px;
            margin: 0 auto;
        }

        .ai-search-input-container {
            flex: 1;
            position: relative;
            padding-right: 45px;
            /* Add space for the microphone button */
        }

        .ai-search-input {
            padding: 1rem 2.8rem;
            font-size: 0.95rem;
            border-width: 2px;
            transition: all 0.4s var(--transition-bounce);
            animation: soft-pulse 3s infinite;
            width: 100%;
        }

        .multilingual-badge {
            position: absolute;
            right: auto;
            left: 2.8rem;
            top: -8px;
            background: linear-gradient(135deg, #6a3093 0%, #a044ff 100%);
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            z-index: 2;
            box-shadow: 0 3px 8px rgba(106, 48, 147, 0.3);
            letter-spacing: 0.5px;
            transform: translateY(0);
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .multilingual-badge i {
            font-size: 0.9rem;
        }

        .ai-search-input-container:hover .multilingual-badge {
            transform: translateY(-3px);
        }

        /* Results section spacing */
        .results-info {
            margin-top: 1rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .search-section {
                padding: 1.5rem;
            }

            .search-header h1 {
                font-size: 1.5rem;
            }

            .search-tab {
                padding: 0.75rem 1rem;
                font-size: 0.85rem;
            }

            .ai-search-form {
                flex-direction: column;
            }

            .search-button,
            .ai-search-button {
                width: 100%;
            }
        }

        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2.5rem;
            padding: 0.5rem 0;
        }

        .doctor-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow:
                0 20px 30px rgba(0, 0, 0, 0.05),
                0 1px 1px rgba(255, 255, 255, 0.7) inset;
            transition: all 0.4s var(--transition-bounce);
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.7);
            height: 100%;
            transform-style: preserve-3d;
            perspective: 1000px;
        }

        .doctor-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow:
                0 30px 60px rgba(0, 0, 0, 0.1),
                0 1px 1px rgba(255, 255, 255, 0.7) inset;
            border: 1px solid rgba(255, 255, 255, 0.9);
        }

        .doctor-image-container {
            position: relative;
            width: 100%;
            height: 260px;
            overflow: hidden;
        }

        .doctor-image-container::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 60%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0) 100%);
            z-index: 1;
            opacity: 0.8;
            transition: opacity 0.5s ease;
        }

        .doctor-card:hover .doctor-image-container::after {
            opacity: 1;
            height: 70%;
        }

        .doctor-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 1s ease;
            transform-origin: center;
        }

        .doctor-card:hover .doctor-image {
            transform: scale(1.1);
        }

        .doctor-content {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            height: calc(100% - 260px);
            position: relative;
            z-index: 2;
        }

        .doctor-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 10%;
            width: 80%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(29, 122, 140, 0.2), transparent);
        }

        .doctor-badge {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 3rem;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(233, 79, 55, 0.4);
            z-index: 2;
            animation: pulse 2s infinite;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(233, 79, 55, 0.7);
                transform: scale(0.95);
            }

            70% {
                box-shadow: 0 0 0 12px rgba(233, 79, 55, 0);
                transform: scale(1.05);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(233, 79, 55, 0);
                transform: scale(0.95);
            }
        }

        .doctor-specialty {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            position: relative;
            display: inline-block;
            padding-bottom: 6px;
        }

        .doctor-specialty::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 30px;
            height: 2px;
            background: var(--primary-gradient);
            border-radius: 3px;
        }

        .doctor-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
            color: var(--secondary);
            letter-spacing: -0.5px;
            transition: all 0.3s var(--transition-bounce);
        }

        .doctor-card:hover .doctor-name {
            color: var(--primary);
            transform: translateY(-2px);
        }

        .doctor-rating {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-bottom: 1.5rem;
        }

        .doctor-rating i {
            color: #FFB400;
            text-shadow: 0 1px 3px rgba(255, 180, 0, 0.3);
        }

        .doctor-rating span {
            color: var(--text-light);
            font-size: 0.95rem;
            margin-left: 0.35rem;
            font-weight: 500;
        }

        .doctor-review-count {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-left: auto;
            opacity: 0.8;
            background: rgba(29, 122, 140, 0.08);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            transition: all 0.3s ease;
        }

        .doctor-card:hover .doctor-review-count {
            background: rgba(29, 122, 140, 0.15);
        }

        .doctor-details {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            margin-bottom: 2rem;
            flex-grow: 1;
            padding-top: 0.5rem;
        }

        .doctor-detail-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-light);
            font-size: 0.95rem;
            transition: all 0.3s var(--transition-bounce);
            padding: 0.5rem 0;
            border-bottom: 1px dashed rgba(29, 122, 140, 0.1);
        }

        .doctor-detail-item:last-child {
            border-bottom: none;
        }

        .doctor-detail-item:hover {
            color: var(--text);
            transform: translateX(5px);
        }

        .doctor-detail-item i {
            color: var(--primary);
            font-size: 1.1rem;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            opacity: 0.9;
            transition: all 0.3s var(--transition-bounce);
            background: rgba(29, 122, 140, 0.1);
            border-radius: 50%;
            padding: 1rem;
        }

        .doctor-detail-item:hover i {
            opacity: 1;
            transform: scale(1.1) rotate(5deg);
            background: rgba(29, 122, 140, 0.2);
            box-shadow: 0 0 0 4px rgba(29, 122, 140, 0.05);
        }

        .view-profile-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            width: 100%;
            padding: 1.25rem;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s var(--transition-bounce);
            text-decoration: none;
            position: relative;
            overflow: hidden;
            z-index: 1;
            font-size: 1rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(29, 122, 140, 0.3);
        }

        .view-profile-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.7s ease;
            z-index: -1;
        }

        .view-profile-btn:hover::before {
            left: 100%;
        }

        .view-profile-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(29, 122, 140, 0.4);
        }

        .view-profile-btn i {
            transition: transform 0.4s var(--transition-bounce);
            font-size: 1.1rem;
        }

        .view-profile-btn:hover i {
            transform: translateX(6px);
        }

        .doctor-languages {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: -0.5rem;
            margin-bottom: 1.25rem;
        }

        .language-tag {
            background: rgba(29, 122, 140, 0.08);
            color: var(--primary);
            font-size: 0.85rem;
            padding: 0.35rem 0.85rem;
            border-radius: 2rem;
            transition: all 0.3s var(--transition-bounce);
            border: 1px solid rgba(29, 122, 140, 0.1);
        }

        .language-tag:hover {
            background: rgba(29, 122, 140, 0.15);
            transform: translateY(-3px);
            box-shadow: 0 3px 8px rgba(29, 122, 140, 0.1);
            border-color: rgba(29, 122, 140, 0.2);
        }

        .no-results {
            text-align: center;
            padding: 5rem 2rem;
            grid-column: 1 / -1;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--radius-lg);
            box-shadow:
                0 20px 30px rgba(0, 0, 0, 0.05),
                0 1px 1px rgba(255, 255, 255, 0.7) inset;
            animation: fadeIn 0.8s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.7);
        }

        .no-results svg {
            width: 120px;
            height: 120px;
            color: var(--primary-light);
            margin-bottom: 2rem;
            opacity: 0.8;
            filter: drop-shadow(0 3px 6px rgba(29, 122, 140, 0.2));
            animation: floatAnimation 3s ease-in-out infinite;
        }

        @keyframes floatAnimation {
            0% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }

            100% {
                transform: translateY(0);
            }
        }

        .no-results h3 {
            color: var(--secondary);
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .no-results p {
            color: var(--text-light);
            max-width: 500px;
            margin: 0 auto 2rem;
            font-size: 1.2rem;
            line-height: 1.6;
        }

        .applied-filters {
            background: rgba(29, 122, 140, 0.05);
            padding: 1.5rem;
            border-radius: var(--radius-md);
            max-width: 500px;
            margin: 0 auto 2rem;
        }

        .applied-filters p {
            margin: 0 0 1rem;
            font-weight: 600;
            color: var(--secondary);
        }

        .applied-filters ul {
            margin: 0;
            padding: 0;
            list-style-type: none;
            text-align: left;
        }

        .applied-filters li {
            padding: 0.5rem 0;
            border-bottom: 1px dashed rgba(29, 122, 140, 0.1);
            color: var(--text-light);
        }

        .applied-filters li:last-child {
            border-bottom: none;
        }

        .reset-search-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s var(--transition-bounce);
            text-decoration: none;
            position: relative;
            overflow: hidden;
            z-index: 1;
            box-shadow: 0 8px 20px rgba(29, 122, 140, 0.3);
        }

        .reset-search-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.7s ease;
            z-index: -1;
        }

        .reset-search-btn:hover::before {
            left: 100%;
        }

        .reset-search-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(29, 122, 140, 0.4);
        }

        .reset-search-btn i {
            transition: transform 0.4s var(--transition-bounce);
            font-size: 1.1rem;
        }

        .reset-search-btn:hover i {
            transform: rotate(360deg);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            transition: all 0.5s ease;
        }

        .loading-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: rgba(255, 255, 255, 0.9);
            padding: 3rem;
            border-radius: var(--radius-lg);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.6);
            animation: pulseScale 2s infinite;
            position: relative;
            overflow: hidden;
        }

        .loading-content::before {
            content: '';
            position: absolute;
            width: 150%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.8), transparent);
            transform: translateX(-100%) skewX(-20deg);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            100% {
                transform: translateX(100%) skewX(-20deg);
            }
        }

        @keyframes pulseScale {

            0%,
            100% {
                transform: scale(1);
                box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            }

            50% {
                transform: scale(1.03);
                box-shadow: 0 20px 60px rgba(29, 122, 140, 0.2);
            }
        }

        .loading-spinner {
            width: 70px;
            height: 70px;
            border: 4px solid rgba(29, 122, 140, 0.1);
            border-radius: 50%;
            position: relative;
            margin-bottom: 1.5rem;
        }

        .loading-spinner::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border-radius: 50%;
            border: 4px solid transparent;
            border-top-color: var(--primary);
            border-bottom-color: var(--primary-light);
            animation: spin 1.5s linear infinite;
        }

        .loading-spinner::after {
            content: '';
            position: absolute;
            top: 4px;
            left: 4px;
            right: 4px;
            bottom: 4px;
            border-radius: 50%;
            border: 4px solid transparent;
            border-left-color: var(--accent);
            border-right-color: var(--accent-light);
            animation: spin 2s linear infinite reverse;
        }

        .loading-text {
            font-size: 1.25rem;
            color: var(--primary);
            font-weight: 600;
            position: relative;
            display: inline-block;
        }

        .loading-text::after {
            content: '...';
            position: absolute;
            right: -20px;
            animation: ellipsis 1.5s infinite;
        }

        @keyframes ellipsis {
            0% {
                content: '.';
            }

            33% {
                content: '..';
            }

            66% {
                content: '...';
            }

            100% {
                content: '.';
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Suggestion Dropdown */
        .suggestions-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border-radius: 0 0 var(--radius-md) var(--radius-md);
            box-shadow: var(--shadow-md);
            z-index: 10;
            display: none;
        }

        .suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .suggestion-item:hover {
            background: rgba(29, 122, 140, 0.08);
        }

        /* Filter Pills */
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin: 1.5rem 0;
        }

        .filter-pill {
            background: rgba(29, 122, 140, 0.1);
            color: var(--primary);
            border-radius: 2rem;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .filter-pill:hover {
            background: rgba(29, 122, 140, 0.2);
        }

        .filter-pill i {
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-pill i:hover {
            color: var(--accent);
            transform: scale(1.1);
        }

        /* Animation for doctor cards */
        .animate-card {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 160px;
            background-color: var(--secondary);
            color: white;
            text-align: center;
            border-radius: var(--radius-sm);
            padding: 0.5rem;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.85rem;
            box-shadow: var(--shadow-md);
        }

        .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: var(--secondary) transparent transparent transparent;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        /* Back to top button */
        .back-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--primary-gradient);
            color: white;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s var(--transition-bounce);
            box-shadow: 0 5px 15px rgba(29, 122, 140, 0.3);
            z-index: 99;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transform: translateY(20px);
        }

        .back-to-top::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(rgba(255, 255, 255, 0.2), transparent);
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .back-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(29, 122, 140, 0.4);
        }

        .back-to-top:hover::before {
            opacity: 1;
        }

        .back-to-top i {
            font-size: 1.25rem;
            transition: transform 0.3s var(--transition-bounce);
        }

        .back-to-top:hover i {
            transform: translateY(-3px);
        }

        @media (max-width: 1200px) {
            .doctors-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1.5rem 1rem;
            }

            .search-section {
                padding: 2rem 1.5rem;
            }

            .search-header h1 {
                font-size: 2rem;
            }

            .search-header p {
                font-size: 1rem;
            }

            .doctors-grid {
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 1.5rem;
            }

            .doctor-image-container {
                height: 220px;
            }

            .doctor-content {
                padding: 1.5rem;
                height: calc(100% - 220px);
            }

            .doctor-name {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 576px) {
            .search-form {
                grid-template-columns: 1fr;
            }

            .doctors-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .search-header h1 {
                font-size: 1.75rem;
            }

            .page-title {
                font-size: 1.6rem;
            }

            .doctor-card {
                max-width: 400px;
                margin: 0 auto;
            }

            .search-button {
                padding: 1rem 1.5rem;
            }

            .search-input {
                padding: 1rem 1.25rem;
            }

            .no-results {
                padding: 3rem 1.5rem;
            }

            .no-results svg {
                width: 80px;
                height: 80px;
            }

            .no-results h3 {
                font-size: 1.5rem;
            }

            .no-results p {
                font-size: 1rem;
            }
        }

        /* AI Doctor Search Styles */
        .search-divider {
            position: relative;
            text-align: center;
            margin: 1.5rem 0;
            height: 15px;
        }

        .search-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(29, 122, 140, 0.15);
            z-index: 1;
        }

        .search-divider span {
            position: relative;
            display: inline-block;
            padding: 0 1rem;
            background: white;
            color: var(--text-light);
            font-weight: 600;
            font-size: 0.85rem;
            z-index: 2;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            background: rgba(255, 255, 255, 0.9);
        }

        .ai-search-section {
            text-align: center;
            padding: 0.75rem 0 0;
            animation: fadeIn 0.6s ease-out 0.3s both;
        }

        .ai-search-section h3 {
            color: var(--primary);
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .ai-search-section h3 i {
            font-size: 1.1rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .ai-search-section p {
            color: var(--text-light);
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .ai-search-form {
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            gap: 1rem;
            max-width: 750px;
            margin: 0 auto;
        }

        .ai-search-input-container {
            flex: 1;
            position: relative;
            padding-right: 45px;
            /* Add space for the microphone button */
        }

        .ai-search-input {
            font-size: 1rem;
            border-width: 2px;
            transition: all 0.4s var(--transition-bounce);
            animation: soft-pulse 3s infinite;
            padding: 1rem 1.25rem 1rem 2.8rem;
            width: 100%;
        }

        .ai-search-input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            transition: all 0.3s ease;
        }

        .ai-search-input:focus+.ai-search-input-icon {
            color: var(--primary-dark);
            transform: translateY(-50%) scale(1.1);
        }

        .multilingual-badge {
            position: absolute;
            right: auto;
            left: 2.8rem;
            top: -8px;
            background: linear-gradient(135deg, #6a3093 0%, #a044ff 100%);
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            z-index: 2;
            box-shadow: 0 3px 8px rgba(106, 48, 147, 0.3);
            letter-spacing: 0.5px;
            transform: translateY(0);
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .multilingual-badge i {
            font-size: 0.9rem;
        }

        .ai-search-input-container:hover .multilingual-badge {
            transform: translateY(-3px);
        }

        @keyframes soft-pulse {

            0%,
            100% {
                box-shadow: 0 0 0 rgba(29, 122, 140, 0);
            }

            50% {
                box-shadow: 0 0 15px rgba(29, 122, 140, 0.15);
            }
        }

        .ai-search-button {
            margin: 0;
            background: linear-gradient(135deg, #6a3093 0%, #a044ff 100%);
            min-width: 180px;
            padding: 1rem 1.25rem;
            white-space: nowrap;
            align-self: flex-start;
        }

        @media (max-width: 650px) {
            .ai-search-form {
                flex-direction: column;
            }

            .ai-search-button {
                margin: 0 auto;
                min-width: 250px;
            }
        }

        /* AI Results Section */
        .ai-results-section {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin: 1.5rem 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.8);
            animation: fadeIn 0.8s ease-out;
        }

        .ai-recommendation {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .ai-recommendation-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .ai-recommendation-header i {
            font-size: 1.2rem;
            color: white;
            background: linear-gradient(135deg, #6a3093 0%, #a044ff 100%);
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            box-shadow: 0 5px 15px rgba(106, 48, 147, 0.3);
        }

        .ai-recommendation-header h3 {
            font-size: 1.3rem;
            color: var(--secondary);
            margin: 0;
        }

        .ai-recommendation p {
            color: var(--text-light);
            font-size: 1rem;
            margin: 0;
        }

        .ai-specialties {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        .ai-specialty {
            background: rgba(29, 122, 140, 0.1);
            color: var(--primary-dark);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(29, 122, 140, 0.1);
        }

        .ai-specialty:hover {
            background: rgba(29, 122, 140, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(29, 122, 140, 0.1);
        }

        .ai-specialty i {
            color: var(--primary);
            font-size: 1rem;
        }

        .ai-doctors-section {
            margin-top: 1.5rem;
        }

        .ai-doctors-title {
            color: var(--primary);
            font-size: 1.3rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .ai-doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            padding: 0.5rem 0;
        }

        .ai-doctor-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow:
                0 15px 25px rgba(0, 0, 0, 0.05),
                0 1px 1px rgba(255, 255, 255, 0.7) inset;
            transition: all 0.4s var(--transition-bounce);
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.7);
            height: 100%;
            transform-style: preserve-3d;
            perspective: 1000px;
        }

        .ai-doctor-image-container {
            position: relative;
            width: 100%;
            height: 220px;
            overflow: hidden;
        }

        .ai-doctor-content {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            height: calc(100% - 220px);
            position: relative;
            z-index: 2;
        }

        .ai-doctor-specialty {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 0.25rem;
            font-size: 0.95rem;
            position: relative;
            display: inline-block;
            padding-bottom: 4px;
        }

        .ai-doctor-name {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0.25rem 0;
            color: var(--secondary);
            letter-spacing: -0.5px;
            transition: all 0.3s var(--transition-bounce);
        }

        .ai-doctor-rating {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-bottom: 1rem;
        }

        .ai-doctor-rating i {
            color: #FFB400;
            text-shadow: 0 1px 3px rgba(255, 180, 0, 0.3);
            font-size: 0.9rem;
        }

        .ai-doctor-rating span {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-left: 0.35rem;
            font-weight: 500;
        }

        .ai-doctor-review-count {
            color: var(--text-light);
            font-size: 0.8rem;
            margin-left: auto;
            opacity: 0.8;
            background: rgba(29, 122, 140, 0.08);
            padding: 0.2rem 0.6rem;
            border-radius: 1rem;
        }

        .ai-doctor-details {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            flex-grow: 1;
            padding-top: 0.25rem;
        }

        .ai-doctor-detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-light);
            font-size: 0.85rem;
            transition: all 0.3s var(--transition-bounce);
            padding: 0.35rem 0;
            border-bottom: 1px dashed rgba(29, 122, 140, 0.1);
        }

        .ai-doctor-detail-item i {
            color: var(--primary);
            font-size: 0.9rem;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            opacity: 0.9;
            transition: all 0.3s var(--transition-bounce);
            background: rgba(29, 122, 140, 0.1);
            border-radius: 50%;
            padding: 0.85rem;
        }

        .ai-doctor-view-profile-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.9rem;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s var(--transition-bounce);
            text-decoration: none;
            position: relative;
            overflow: hidden;
            z-index: 1;
            font-size: 0.9rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(29, 122, 140, 0.3);
        }

        .ai-no-doctors {
            text-align: center;
            padding: 2.5rem 1.5rem;
            grid-column: 1 / -1;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--radius-lg);
            box-shadow:
                0 20px 30px rgba(0, 0, 0, 0.05),
                0 1px 1px rgba(255, 255, 255, 0.7) inset;
            animation: fadeIn 0.8s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.7);
        }

        .ai-no-doctors p {
            color: var(--text-light);
            max-width: 500px;
            margin: 0 auto;
            font-size: 1rem;
            line-height: 1.5;
        }

        .fallback-notice {
            font-size: 0.85rem;
            font-weight: normal;
            color: var(--text-light);
            margin-top: 0.25rem;
            padding: 0.5rem 0.75rem;
            background-color: rgba(255, 248, 225, 0.8);
            border-radius: var(--radius-md);
            border-left: 3px solid #FFB400;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .fallback-notice i {
            color: #FFB400;
            font-size: 1rem;
        }

        .translation-info {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-left: 0.5rem;
            display: inline-block;
        }

        .emergency-badge,
        .urgent-badge {
            background: linear-gradient(135deg, #e53935 0%, #ff5252 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-left: 10px;
            display: inline-flex;
            align-items: center;
            animation: pulse 2s infinite;
            box-shadow: 0 4px 10px rgba(229, 57, 53, 0.3);
        }

        .urgent-badge {
            background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%);
            animation: pulse 3s infinite;
            box-shadow: 0 4px 10px rgba(255, 152, 0, 0.3);
        }

        .emergency-alert,
        .urgent-alert {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            background: rgba(229, 57, 53, 0.1);
            border-left: 4px solid #e53935;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            animation: fadeIn 0.5s ease-out;
            box-shadow: 0 5px 15px rgba(229, 57, 53, 0.1);
        }

        .urgent-alert {
            background: rgba(255, 152, 0, 0.1);
            border-left: 4px solid #ff9800;
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.1);
        }

        .emergency-alert i,
        .urgent-alert i {
            font-size: 1.5rem;
            color: #e53935;
        }

        .urgent-alert i {
            color: #ff9800;
        }

        .emergency-alert strong,
        .urgent-alert strong {
            display: block;
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: #e53935;
        }

        .urgent-alert strong {
            color: #ff9800;
        }

        .emergency-alert p,
        .urgent-alert p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text);
        }

        /* Enhanced specialty tags */
        .ai-specialty {
            background: rgba(29, 122, 140, 0.1);
            color: var(--primary-dark);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(29, 122, 140, 0.1);
        }

        .ai-specialty.primary-specialty {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 10px rgba(29, 122, 140, 0.2);
            transform: scale(1.05);
            border: none;
        }

        .ai-specialty.primary-specialty i {
            color: white;
        }

        .ai-specialty:hover {
            background: rgba(29, 122, 140, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(29, 122, 140, 0.1);
        }

        .ai-specialty.primary-specialty:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 20px rgba(29, 122, 140, 0.25);
        }

        /* Enhanced AI Search Input */
        .ai-search-input-container {
            position: relative;
            transition: all 0.3s ease;
        }

        .ai-search-input {
            font-size: 1rem;
            border-width: 2px;
            transition: all 0.4s var(--transition-bounce);
            animation: soft-pulse 3s infinite;
            padding: 1rem 1.25rem 1rem 2.8rem;
            width: 100%;
        }

        .ai-search-input:focus {
            border-color: #6a3093;
            box-shadow: 0 0 0 4px rgba(106, 48, 147, 0.2), 0 1px 2px rgba(255, 255, 255, 0.5) inset;
        }

        .ai-search-input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            transition: all 0.3s ease;
        }

        .ai-search-input:focus+.ai-search-input-icon {
            color: #6a3093;
            transform: translateY(-50%) scale(1.1);
        }

        .multilingual-badge {
            position: absolute;
            right: auto;
            left: 2.8rem;
            top: -8px;
            background: linear-gradient(135deg, #6a3093 0%, #a044ff 100%);
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            z-index: 2;
            box-shadow: 0 3px 8px rgba(106, 48, 147, 0.3);
            letter-spacing: 0.5px;
            transform: translateY(0);
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .multilingual-badge i {
            font-size: 0.9rem;
        }

        .ai-search-input-container:hover .multilingual-badge {
            transform: translateY(-3px);
        }

        .ai-search-button {
            background: linear-gradient(135deg, #6a3093 0%, #a044ff 100%);
            min-width: 180px;
            padding: 1rem 1.25rem;
            white-space: nowrap;
            align-self: flex-start;
            box-shadow: 0 4px 15px rgba(106, 48, 147, 0.3);
        }

        .ai-search-button:hover {
            background: linear-gradient(135deg, #5d2884 0%, #8e3de0 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(106, 48, 147, 0.4);
        }

        /* Improved AI results section */
        .ai-results-section {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin: 1.5rem 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.8);
            animation: fadeIn 0.8s ease-out;
            position: relative;
            overflow: hidden;
        }

        .ai-results-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, #6a3093, #a044ff);
        }

        .ai-recommendation-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 5px;
        }

        .ai-recommendation-header i {
            font-size: 1.2rem;
            color: white;
            background: linear-gradient(135deg, #6a3093 0%, #a044ff 100%);
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            box-shadow: 0 5px 15px rgba(106, 48, 147, 0.3);
        }

        /* Speech input button */
        .speech-input-button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #6a3093 0%, #a044ff 100%);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            box-shadow: 0 3px 8px rgba(106, 48, 147, 0.3);
            z-index: 5;
            overflow: visible;
        }

        .speech-input-button:hover {
            background: linear-gradient(135deg, #5d2884 0%, #8e3de0 100%);
        }

        .speech-input-button.listening {
            background: #e53935;
            box-shadow: 0 0 0 5px rgba(229, 57, 53, 0.3);
        }

        .recording-indicator {
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: red;
            top: -2px;
            right: -2px;
            z-index: 6;
        }

        /* Additional dynamic styles */
        @keyframes shake-error {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-5px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(5px);
            }
        }

        .shake-error {
            animation: shake-error 0.6s cubic-bezier(.36, .07, .19, .97) both;
            border-color: #e53935 !important;
        }

        .ai-input-error {
            position: absolute;
            bottom: -30px;
            left: 10px;
            background: #e53935;
            color: white;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 4px;
            animation: fadeIn 0.3s;
            z-index: 10;
        }

        /* Speech error tooltip */
        .speech-error-tooltip {
            position: absolute;
            right: 0;
            top: -40px;
            background: rgba(229, 57, 53, 0.9);
            color: white;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 4px;
            white-space: nowrap;
            animation: fadeIn 0.3s;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        }

        /* Ensure page title is properly styled */
        .page-title {
            text-align: center;
            margin-left: auto;
            margin-right: auto;
            display: block;
        }

        /* Make sure AI search input container properly handles the mic button */
        .ai-search-input-container {
            flex: 1;
            position: relative;
            padding-right: 45px;
            /* Add space for the microphone button */
        }
    </style>

</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="dashboard-container">
        <h1 class="page-title">Find Your Doctor</h1>

        <div class="search-section">
            <div class="wave-bg"></div>

            <div class="search-header animate__animated animate__fadeIn">
                <h1>Find Your Healthcare Specialist</h1>
                <p>Connect with trusted medical professionals tailored to your healthcare needs</p>
            </div>

            <div class="search-tabs">
                <button type="button" class="search-tab active" data-tab="regular-search">
                    <i class="fas fa-search"></i> Regular Search
                </button>
                <button type="button" class="search-tab" data-tab="ai-search">
                    <i class="fas fa-robot"></i> AI Doctor Finder
                </button>
            </div>

            <div class="search-content">
                <div class="search-panel active" id="regular-search">
                    <form class="search-form" method="GET" id="doctorSearchForm">
                        <div class="search-group">
                            <input type="text"
                                name="specialty"
                                id="specialtyInput"
                                placeholder="Search by specialty (e.g., Cardiologist)"
                                class="search-input"
                                value="<?= htmlspecialchars($specialty) ?>"
                                autocomplete="off">
                            <div class="suggestions-dropdown" id="specialtySuggestions"></div>
                        </div>

                        <div class="search-group">
                            <input type="text"
                                name="location"
                                placeholder="Location (City or ZIP)"
                                class="search-input"
                                value="<?= htmlspecialchars($location) ?>">
                        </div>

                        <div class="search-group">
                            <select name="experience" class="search-input">
                                <option value="">Years of Experience</option>
                                <option value="5" <?= $experience == 5 ? 'selected' : '' ?>>5+ Years</option>
                                <option value="10" <?= $experience == 10 ? 'selected' : '' ?>>10+ Years</option>
                                <option value="15" <?= $experience == 15 ? 'selected' : '' ?>>15+ Years</option>
                                <option value="20" <?= $experience == 20 ? 'selected' : '' ?>>20+ Years</option>
                            </select>
                        </div>

                        <div class="search-group">
                            <select name="rating" class="search-input">
                                <option value="">Minimum Rating</option>
                                <option value="4.5" <?= $rating == 4.5 ? 'selected' : '' ?>>4.5 Stars</option>
                                <option value="4" <?= $rating == 4 ? 'selected' : '' ?>>★★★★ & Up</option>
                                <option value="3.5" <?= $rating == 3.5 ? 'selected' : '' ?>>★★★½ & Up</option>
                                <option value="3" <?= $rating == 3 ? 'selected' : '' ?>>★★★ & Up</option>
                            </select>
                        </div>

                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                            Find Doctors
                        </button>
                    </form>
                </div>

                <div class="search-panel" id="ai-search">
                    <div class="ai-search-section">
                        <form class="ai-search-form" method="GET" id="aiDoctorSearchForm">
                            <div class="search-group ai-search-input-container">
                                <input type="text"
                                    name="ai_symptom"
                                    id="aiSymptomInput"
                                    placeholder="Describe your symptoms in any language"
                                    class="search-input ai-search-input"
                                    value="<?= htmlspecialchars($symptom) ?>">
                                <span class="ai-search-input-icon fas fa-language"></span>
                                <span class="multilingual-badge"><i class="fas fa-globe"></i> Multilingual</span>
                            </div>
                            <button type="submit" class="search-button ai-search-button">
                                <i class="fas fa-stethoscope"></i>
                                Find AI Doctor
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <?php if ($ai_search_results && !empty($ai_search_results['specialties'])): ?>
                <div class="ai-results-section">
                    <div class="ai-recommendation">
                        <div class="ai-recommendation-header">
                            <i class="fas fa-robot"></i>
                            <h3>AI Recommendation</h3>
                            <?php if ($ai_search_results['urgency'] == 'emergency'): ?>
                                <div class="emergency-badge">Emergency</div>
                            <?php elseif ($ai_search_results['urgency'] == 'urgent'): ?>
                                <div class="urgent-badge">Urgent Care</div>
                            <?php endif; ?>
                        </div>
                        <p>Based on your symptoms
                            <?php if (isset($ai_search_results['translated_symptom']) && $ai_search_results['translated_symptom'] !== null): ?>
                                "<strong><?= htmlspecialchars($ai_search_results['original_symptom']) ?></strong>"
                                <span class="translation-info">
                                    <i class="fas fa-language"></i>
                                    Translated from <?= htmlspecialchars(getLanguageName($ai_search_results['detected_language'])) ?> to English:
                                    "<em><?= htmlspecialchars($ai_search_results['translated_symptom']) ?></em>"
                                </span>
                            <?php else: ?>
                                "<strong><?= htmlspecialchars($symptom) ?></strong>"
                            <?php endif; ?>
                            , AI recommends:
                        </p>

                        <?php if ($ai_search_results['urgency'] == 'emergency'): ?>
                            <div class="emergency-alert">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>
                                    <strong>This may be a medical emergency.</strong>
                                    <p>If you're experiencing a life-threatening condition, please call emergency services (999) or go to your nearest emergency room immediately.</p>
                                </div>
                            </div>
                        <?php elseif ($ai_search_results['urgency'] == 'urgent'): ?>
                            <div class="urgent-alert">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <strong>This condition may require prompt attention.</strong>
                                    <p>Consider visiting an urgent care facility or scheduling a same-day appointment.</p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="ai-specialties">
                            <?php foreach ($ai_search_results['specialties'] as $index => $aiSpecialty): ?>
                                <div class="ai-specialty <?= $index === 0 ? 'primary-specialty' : '' ?>">
                                    <i class="fas fa-user-md"></i>
                                    <span><?= htmlspecialchars(ucfirst($aiSpecialty)) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (!empty($ai_search_results['doctors'])): ?>
                        <div class="ai-doctors-section">
                            <h3 class="ai-doctors-title">
                                <?php if ($ai_search_results['using_fallback']): ?>
                                    Top Rated Doctors
                                    <div class="fallback-notice">
                                        <i class="fas fa-info-circle"></i>
                                        We couldn't find doctors matching the exact specialties. Showing our best doctors instead.
                                    </div>
                                <?php else: ?>
                                    Recommended Doctors
                                <?php endif; ?>
                            </h3>
                            <div class="doctors-grid ai-doctors-grid">
                                <?php foreach ($ai_search_results['doctors'] as $index => $doctor): ?>
                                    <div class="doctor-card ai-doctor-card animate-card" style="animation-delay: <?= $index * 0.1 ?>s">
                                        <?php if (($doctor['avg_rating'] ?? 0) >= 4.5): ?>
                                            <div class="doctor-badge">Top Rated</div>
                                        <?php endif; ?>

                                        <div class="doctor-image-container">
                                            <img src="<?= htmlspecialchars($doctor['profile_image'] ?: 'uploads/default_profile.png') ?>"
                                                class="doctor-image"
                                                alt="<?= htmlspecialchars($doctor['username']) ?>"
                                                onerror="this.src='uploads/default_profile.png'">
                                        </div>

                                        <div class="doctor-content">
                                            <div class="doctor-specialty">
                                                <?= htmlspecialchars($doctor['specialty'] ?? 'General Practitioner') ?>
                                            </div>

                                            <h3 class="doctor-name"><?= htmlspecialchars($doctor['username']) ?></h3>

                                            <div class="doctor-rating">
                                                <?php
                                                $rating = $doctor['avg_rating'] ?? 0;
                                                for ($i = 1; $i <= 5; $i++):
                                                    if ($rating >= $i):
                                                        echo '<i class="fas fa-star"></i>';
                                                    elseif ($rating >= $i - 0.5):
                                                        echo '<i class="fas fa-star-half-alt"></i>';
                                                    else:
                                                        echo '<i class="far fa-star"></i>';
                                                    endif;
                                                endfor;
                                                ?>
                                                <span><?= number_format($doctor['avg_rating'] ?? 0, 1) ?></span>

                                                <div class="doctor-review-count">
                                                    <?= $doctor['review_count'] ?? 0 ?> reviews
                                                </div>
                                            </div>

                                            <?php if (!empty($doctor['degrees'])): ?>
                                                <div class="doctor-languages">
                                                    <?php
                                                    $degreeList = explode(', ', $doctor['degrees']);
                                                    foreach ($degreeList as $degree):
                                                        if (!empty(trim($degree))):
                                                    ?>
                                                            <span class="language-tag"><?= htmlspecialchars($degree) ?></span>
                                                    <?php endif;
                                                    endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <div class="doctor-details">
                                                <?php if (!empty($doctor['work_address'])): ?>
                                                    <div class="doctor-detail-item">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <span><?= htmlspecialchars($doctor['work_address']) ?></span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($doctor['years_of_experience'])): ?>
                                                    <div class="doctor-detail-item">
                                                        <i class="fas fa-briefcase-medical"></i>
                                                        <span><?= htmlspecialchars($doctor['years_of_experience']) ?> Years Experience</span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($doctor['education'])): ?>
                                                    <div class="doctor-detail-item">
                                                        <i class="fas fa-graduation-cap"></i>
                                                        <span><?= htmlspecialchars($doctor['education']) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <a href="doctor-profile.php?id=<?= $doctor['id'] ?>" class="view-profile-btn ai-doctor-view-profile-btn">
                                                View Profile
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="ai-no-doctors">
                            <p>Sorry, we couldn't find any doctors matching the specialties recommended by AI. Please try using different symptoms or search by specialty directly.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($specialty) || !empty($location) || !empty($experience) || !empty($rating)): ?>
                <div class="active-filters">
                    <?php if (!empty($specialty)): ?>
                        <div class="filter-pill">
                            <span>Specialty: <?= htmlspecialchars($specialty) ?></span>
                            <i class="fas fa-times remove-filter" data-filter="specialty"></i>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($location)): ?>
                        <div class="filter-pill">
                            <span>Location: <?= htmlspecialchars($location) ?></span>
                            <i class="fas fa-times remove-filter" data-filter="location"></i>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($experience)): ?>
                        <div class="filter-pill">
                            <span><?= htmlspecialchars($experience) ?>+ Years Experience</span>
                            <i class="fas fa-times remove-filter" data-filter="experience"></i>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($rating)): ?>
                        <div class="filter-pill">
                            <span>Rating: <?= htmlspecialchars($rating) ?>+</span>
                            <i class="fas fa-times remove-filter" data-filter="rating"></i>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($doctors)): ?>
            <div class="results-info">
                <div class="results-count">Found <span><?= count($doctors) ?></span> healthcare professionals</div>
            </div>
        <?php endif; ?>

        <div class="doctors-grid">
            <?php if (!empty($doctors)): ?>
                <?php foreach ($doctors as $index => $doctor): ?>
                    <div class="doctor-card animate-card" style="animation-delay: <?= $index * 0.1 ?>s">
                        <?php if (($doctor['avg_rating'] ?? 0) >= 4.5): ?>
                            <div class="doctor-badge">Top Rated</div>
                        <?php endif; ?>

                        <div class="doctor-image-container">
                            <img src="<?= htmlspecialchars($doctor['profile_image'] ?: 'uploads/default_profile.png') ?>"
                                class="doctor-image"
                                alt="<?= htmlspecialchars($doctor['username']) ?>"
                                onerror="this.src='uploads/default_profile.png'">
                        </div>

                        <div class="doctor-content">
                            <div class="doctor-specialty">
                                <?= htmlspecialchars($doctor['specialty'] ?? 'General Practitioner') ?>
                            </div>

                            <h3 class="doctor-name"><?= htmlspecialchars($doctor['username']) ?></h3>

                            <div class="doctor-rating">
                                <?php
                                $rating = $doctor['avg_rating'] ?? 0;
                                for ($i = 1; $i <= 5; $i++):
                                    if ($rating >= $i):
                                        echo '<i class="fas fa-star"></i>';
                                    elseif ($rating >= $i - 0.5):
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    else:
                                        echo '<i class="far fa-star"></i>';
                                    endif;
                                endfor;
                                ?>
                                <span><?= number_format($doctor['avg_rating'] ?? 0, 1) ?></span>

                                <div class="doctor-review-count">
                                    <?= $doctor['review_count'] ?? 0 ?> reviews
                                </div>
                            </div>

                            <?php if (!empty($doctor['degrees'])): ?>
                                <div class="doctor-languages">
                                    <?php
                                    $degreeList = explode(', ', $doctor['degrees']);
                                    foreach ($degreeList as $degree):
                                        if (!empty(trim($degree))):
                                    ?>
                                            <span class="language-tag"><?= htmlspecialchars($degree) ?></span>
                                    <?php endif;
                                    endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="doctor-details">
                                <?php if (!empty($doctor['work_address'])): ?>
                                    <div class="doctor-detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?= htmlspecialchars($doctor['work_address']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($doctor['years_of_experience'])): ?>
                                    <div class="doctor-detail-item">
                                        <i class="fas fa-briefcase-medical"></i>
                                        <span><?= htmlspecialchars($doctor['years_of_experience']) ?> Years Experience</span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($doctor['education'])): ?>
                                    <div class="doctor-detail-item">
                                        <i class="fas fa-graduation-cap"></i>
                                        <span><?= htmlspecialchars($doctor['education']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <a href="doctor-profile.php?id=<?= $doctor['id'] ?>" class="view-profile-btn">
                                View Profile
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3>No Doctors Found</h3>
                    <p>We couldn't find any healthcare professionals matching your criteria.</p>
                    <?php if (!empty($specialty) || !empty($location) || !empty($experience) || !empty($rating)): ?>
                        <div class="applied-filters">
                            <p>Applied filters:</p>
                            <ul>
                                <?php if (!empty($specialty)): ?>
                                    <li>Specialty: <?= htmlspecialchars($specialty) ?></li>
                                <?php endif; ?>
                                <?php if (!empty($location)): ?>
                                    <li>Location: <?= htmlspecialchars($location) ?></li>
                                <?php endif; ?>
                                <?php if (!empty($experience)): ?>
                                    <li>Minimum Experience: <?= htmlspecialchars($experience) ?>+ years</li>
                                <?php endif; ?>
                                <?php if (!empty($rating)): ?>
                                    <li>Minimum Rating: <?= htmlspecialchars($rating) ?>+ stars</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <a href="find-doctors.php" class="reset-search-btn">
                        <i class="fas fa-redo"></i>
                        Reset Search
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="back-to-top" id="backToTop">
            <i class="fas fa-arrow-up"></i>
        </div>

        <div class="loading-overlay">
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <div class="loading-text">Searching for doctors...</div>
            </div>
        </div>
    </div>

    <script>
        // Show loading overlay on form submit
        document.getElementById('doctorSearchForm').addEventListener('submit', function() {
            const loadingOverlay = document.querySelector('.loading-overlay');
            loadingOverlay.style.display = 'flex';
            loadingOverlay.style.opacity = '0';

            // Trigger a reflow
            void loadingOverlay.offsetWidth;

            // Start fading in
            loadingOverlay.style.opacity = '1';
        });

        // Also handle loading for AI search form
        document.getElementById('aiDoctorSearchForm').addEventListener('submit', function(e) {
            // Basic validation
            const symptomInput = document.getElementById('aiSymptomInput');
            if (!symptomInput.value.trim()) {
                e.preventDefault();

                // Shake the input to indicate error
                symptomInput.classList.add('shake-error');
                setTimeout(() => {
                    symptomInput.classList.remove('shake-error');
                }, 600);

                // Show error tooltip
                const tooltip = document.createElement('div');
                tooltip.className = 'ai-input-error';
                tooltip.textContent = 'Please describe your symptoms';
                symptomInput.parentNode.appendChild(tooltip);

                setTimeout(() => {
                    tooltip.remove();
                }, 3000);

                return;
            }

            const loadingOverlay = document.querySelector('.loading-overlay');
            const loadingText = document.querySelector('.loading-text');

            // Update loading text for AI search
            loadingText.textContent = 'AI is analyzing your symptoms...';
            loadingText.dataset.text = 'AI is analyzing your symptoms';

            loadingOverlay.style.display = 'flex';
            loadingOverlay.style.opacity = '0';

            // Trigger a reflow
            void loadingOverlay.offsetWidth;

            // Start fading in
            loadingOverlay.style.opacity = '1';

            // Start the loading text animation
            animateLoadingText();
        });

        // Function to animate the loading text with more interesting messages
        function animateLoadingText() {
            const loadingText = document.querySelector('.loading-text');
            const baseText = loadingText.dataset.text || 'AI is analyzing your symptoms';

            // Define interesting messages to display during loading
            const messages = [
                'Matching symptoms to specialties...',
                'Finding the right doctors for you...',
                'Analyzing healthcare options...',
                'Consulting medical knowledge...',
                'Reviewing specialist database...',
                'Evaluating your health concerns...'
            ];

            let currentMessage = 0;

            // Start with base text
            loadingText.textContent = baseText + '...';

            // Set interval to change messages
            const messageInterval = setInterval(() => {
                loadingText.textContent = messages[currentMessage];
                currentMessage = (currentMessage + 1) % messages.length;
            }, 2500);

            // Store the interval ID in a dataset attribute so we can clear it later
            loadingText.dataset.intervalId = messageInterval;
        }

        // Clear animation interval when page loads
        window.addEventListener('load', function() {
            const loadingText = document.querySelector('.loading-text');
            if (loadingText && loadingText.dataset.intervalId) {
                clearInterval(parseInt(loadingText.dataset.intervalId));
            }
        });

        // Handle specialty autocomplete
        const specialtyInput = document.getElementById('specialtyInput');
        const specialtySuggestions = document.getElementById('specialtySuggestions');
        const specialties = <?= json_encode($specialties) ?>;

        specialtyInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            if (searchTerm.length < 2) {
                specialtySuggestions.style.display = 'none';
                return;
            }

            const matchingSpecialties = specialties.filter(specialty =>
                specialty.toLowerCase().includes(searchTerm)
            );

            if (matchingSpecialties.length > 0) {
                specialtySuggestions.innerHTML = '';
                matchingSpecialties.forEach(specialty => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.textContent = specialty;
                    div.addEventListener('click', function() {
                        specialtyInput.value = specialty;
                        specialtySuggestions.style.display = 'none';
                        document.getElementById('doctorSearchForm').submit();
                    });
                    specialtySuggestions.appendChild(div);
                });
                specialtySuggestions.style.display = 'block';
            } else {
                specialtySuggestions.style.display = 'none';
            }
        });

        // Close suggestions on click outside
        document.addEventListener('click', function(e) {
            if (e.target !== specialtyInput && !specialtySuggestions.contains(e.target)) {
                specialtySuggestions.style.display = 'none';
            }
        });

        // Handle removing filters
        document.querySelectorAll('.remove-filter').forEach(filter => {
            filter.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent default action
                const filterName = this.getAttribute('data-filter');
                document.querySelector(`[name="${filterName}"]`).value = '';
                document.getElementById('doctorSearchForm').submit();
            });
        });

        // Animation for doctor cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.animate-card').forEach(card => {
            observer.observe(card);
        });

        // Back to top button
        const backToTopButton = document.getElementById('backToTop');

        if (backToTopButton) {
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    backToTopButton.classList.add('visible');
                } else {
                    backToTopButton.classList.remove('visible');
                }
            });

            backToTopButton.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }

        // AI search input enhancements
        const aiSymptomInput = document.getElementById('aiSymptomInput');

        // Smart example symptoms with multilingual examples
        const placeholderExamples = [
            'chest pain with shortness of breath',
            'severe headache and dizziness',
            'sudden vision changes',
            'persistent cough with fever',
            'lower back pain when bending',
            'dolor en el pecho (Spanish for chest pain)',
            'mal de tête sévère (French for severe headache)',
            'Bauchschmerzen (German for stomach pain)',
            'كحة مستمرة (Arabic for persistent cough)',
            'пульсирующая головная боль (Russian for throbbing headache)',
            '喉咙痛和发烧 (Chinese for sore throat and fever)',
            'मसल्स दर्द और थकान (Hindi for muscle pain and fatigue)',
            'dor nas articulações (Portuguese for joint pain)'
        ];

        let currentExample = 0;
        let placeholderInterval;

        // Start with default placeholder
        aiSymptomInput.placeholder = "Describe your symptoms in any language";

        // Focus event - change placeholder to rotating examples when focusing on input
        aiSymptomInput.addEventListener('focus', function() {
            if (this.value === '') {
                startPlaceholderRotation();
            }
        });

        // Blur event - stop rotation when leaving input
        aiSymptomInput.addEventListener('blur', function() {
            stopPlaceholderRotation();
            if (this.value === '') {
                this.placeholder = "Describe your symptoms in any language";
            }
        });

        // Input event - stop rotation when typing
        aiSymptomInput.addEventListener('input', function() {
            stopPlaceholderRotation();
        });

        function startPlaceholderRotation() {
            if (placeholderInterval) return;

            placeholderInterval = setInterval(() => {
                aiSymptomInput.placeholder = placeholderExamples[currentExample];
                currentExample = (currentExample + 1) % placeholderExamples.length;
            }, 2500);

            // Set first example immediately
            aiSymptomInput.placeholder = placeholderExamples[currentExample];
            currentExample = (currentExample + 1) % placeholderExamples.length;
        }

        function stopPlaceholderRotation() {
            if (placeholderInterval) {
                clearInterval(placeholderInterval);
                placeholderInterval = null;
            }
        }

        // Speech recognition for symptom input if supported
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            // Create speech button
            const speechButton = document.createElement('button');
            speechButton.type = 'button';
            speechButton.className = 'speech-input-button';
            speechButton.innerHTML = '<i class="fas fa-microphone"></i>';
            speechButton.title = 'Describe your symptoms by voice';

            // Add it after the input
            aiSymptomInput.parentNode.appendChild(speechButton);

            // Set up speech recognition
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const recognition = new SpeechRecognition();
            recognition.continuous = false;
            recognition.interimResults = true;

            // Auto detect language
            recognition.lang = '';

            // Handle speech recognition
            speechButton.addEventListener('click', function() {
                // Toggle active state
                if (this.classList.contains('listening')) {
                    recognition.stop();
                    this.classList.remove('listening');
                } else {
                    this.classList.add('listening');
                    recognition.start();

                    // Show recording indicator
                    const indicator = document.createElement('div');
                    indicator.className = 'recording-indicator';
                    this.appendChild(indicator);
                }
            });

            recognition.onresult = function(event) {
                const transcript = Array.from(event.results)
                    .map(result => result[0])
                    .map(result => result.transcript)
                    .join('');

                aiSymptomInput.value = transcript;
            };

            recognition.onend = function() {
                speechButton.classList.remove('listening');
                const indicator = speechButton.querySelector('.recording-indicator');
                if (indicator) indicator.remove();
            };

            recognition.onerror = function(event) {
                console.error('Speech recognition error', event.error);
                speechButton.classList.remove('listening');
                const indicator = speechButton.querySelector('.recording-indicator');
                if (indicator) indicator.remove();

                // Show error message
                const errorTooltip = document.createElement('div');
                errorTooltip.className = 'speech-error-tooltip';
                errorTooltip.textContent = 'Could not recognize speech. Please try again.';
                speechButton.appendChild(errorTooltip);

                setTimeout(() => {
                    errorTooltip.remove();
                }, 3000);
            };
        }

        // Highlight emergency warnings
        document.addEventListener('DOMContentLoaded', function() {
            const emergencyAlerts = document.querySelectorAll('.emergency-alert');
            if (emergencyAlerts.length > 0) {
                // Scroll to the emergency alert
                setTimeout(() => {
                    emergencyAlerts[0].scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    // Add attention animation
                    emergencyAlerts[0].classList.add('attention-highlight');
                    setTimeout(() => {
                        emergencyAlerts[0].classList.remove('attention-highlight');
                    }, 2000);
                }, 500);
            }
        });

        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.search-tab');
            const panels = document.querySelectorAll('.search-panel');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs
                    tabs.forEach(t => t.classList.remove('active'));

                    // Add active class to clicked tab
                    this.classList.add('active');

                    // Hide all panels
                    panels.forEach(panel => panel.classList.remove('active'));

                    // Show the corresponding panel
                    const panelId = this.getAttribute('data-tab');
                    document.getElementById(panelId).classList.add('active');
                });
            });
        });
    </script>

    <style>
        /* Additional dynamic styles */
        @keyframes shake-error {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-5px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(5px);
            }
        }

        .shake-error {
            animation: shake-error 0.6s cubic-bezier(.36, .07, .19, .97) both;
            border-color: #e53935 !important;
        }

        .ai-input-error {
            position: absolute;
            bottom: -30px;
            left: 10px;
            background: #e53935;
            color: white;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 4px;
            animation: fadeIn 0.3s;
            z-index: 10;
        }

        /* Speech input button */
        .speech-input-button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #6a3093 0%, #a044ff 100%);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            box-shadow: 0 3px 8px rgba(106, 48, 147, 0.3);
            z-index: 5;
            overflow: visible;
        }

        .speech-input-button:hover {
            background: linear-gradient(135deg, #5d2884 0%, #8e3de0 100%);
        }

        .speech-input-button.listening {
            background: #e53935;
            box-shadow: 0 0 0 5px rgba(229, 57, 53, 0.3);
        }

        .recording-indicator {
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: red;
            top: -2px;
            right: -2px;
            z-index: 6;
        }

        /* Emergency attention highlight */
        @keyframes attention-highlight {

            0%,
            100% {
                box-shadow: 0 5px 15px rgba(229, 57, 53, 0.1);
            }

            50% {
                box-shadow: 0 5px 25px rgba(229, 57, 53, 0.5);
            }
        }

        .attention-highlight {
            animation: attention-highlight 1s ease 3;
        }

        /* Loading text animation */
        .loading-text::after {
            content: '';
            animation: ellipsis 1.5s infinite;
        }

        @keyframes ellipsis {
            0% {
                content: '.';
            }

            33% {
                content: '..';
            }

            66% {
                content: '...';
            }

            100% {
                content: '.';
            }
        }
    </style>
</body>

</html>
