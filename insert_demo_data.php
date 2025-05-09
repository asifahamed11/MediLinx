<?php
require_once 'config.php';

// Connect to the database
$conn = connectDB();

// Clear existing data if needed (comment out if you don't want to clear)
$tables = ['reviews', 'post_likes', 'posts', 'appointments', 'time_slots', 'degrees', 'notifications'];
foreach ($tables as $table) {
    // Check if table exists before trying to truncate
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $conn->query("TRUNCATE TABLE $table");
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    }
}

// Delete users except admin
$conn->query("DELETE FROM users WHERE role != 'admin'");

// Reset auto increment for all tables
$conn->query("ALTER TABLE users AUTO_INCREMENT = 2"); // Starting from 2 to preserve admin user

// Available profile images
$doctor_images = [
    'uploads/profile_images/1 (1).jpg',
    'uploads/profile_images/1 (3).jpg',
    'uploads/profile_images/1 (5).jpg',
    'uploads/profile_images/1 (7).jpg',
    'uploads/profile_images/1 (9).jpg',
    'uploads/profile_images/2 (1).jpg',
    'uploads/profile_images/2 (3).jpg',
    'uploads/profile_images/2 (5).jpg',
];

$patient_images = [
    'uploads/profile_images/1 (2).jpg',
    'uploads/profile_images/1 (4).jpg',
    'uploads/profile_images/1 (6).jpg',
    'uploads/profile_images/1 (8).jpg',
    'uploads/profile_images/2 (2).jpg',
    'uploads/profile_images/2 (4).jpg',
    'uploads/profile_images/67ffbc357cff7_profile.jpg',
];

$post_image = 'uploads/post_images/67ffc3e2e49d0_post.png';

// Doctor specialties
$specialties = [
    'Cardiology',
    'Dermatology',
    'Neurology',
    'Pediatrics',
    'Orthopedics',
    'Psychiatry',
    'Ophthalmology',
    'Gynecology',
    'Urology',
    'Oncology'
];

// Languages
$languages = ['English', 'Spanish', 'French', 'German', 'Mandarin', 'Arabic', 'Hindi'];

// Increase user counts
$total_doctors = 15;
$total_patients = 20;

// Enhanced post types and content
$post_types = [
    'article' => [
        'Understanding Heart Disease Prevention',
        'The Science Behind Vaccinations',
        'Managing Chronic Pain Effectively'
    ],
    'health_tip' => [
        '5 Daily Habits for Better Sleep',
        'Nutrition Tips for Busy Professionals',
        'Exercise Routines for All Ages'
    ],
    'faq' => [
        'When Should You Get a Physical Exam?',
        'Common Questions About Blood Tests',
        'Preparing for Your First Specialist Visit'
    ]
];

// Detailed appointment reasons
$appointment_reasons = [
    'Annual physical examination',
    'Follow-up for chronic condition',
    'Vaccination consultation',
    'Pain management consultation',
    'Diagnostic test results review',
    'Pre-surgical evaluation',
    'Post-operative checkup',
    'Mental health consultation'
];

// ----------------------
// Insert Doctors
// ----------------------
$doctor_ids = [];
$hashed_password = password_hash('a', PASSWORD_DEFAULT);

for ($i = 0; $i < $total_doctors; $i++) {
    $specialty = $specialties[array_rand($specialties)];
    $years = rand(3, 20);
    $languages_spoken = $languages[array_rand($languages)] . ', ' . $languages[array_rand($languages)];
    $profile_image = $doctor_images[$i];

    $stmt = $conn->prepare("INSERT INTO users (role, username, email, password, phone, date_of_birth, gender, address, 
                           specialty, medical_license_number, work_address, profile_image, email_verified_at, 
                           years_of_experience, languages_spoken, professional_biography) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)");

    $username = "Dr" . ucfirst(strtolower(substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 8)));
    $email = strtolower($username) . "@example.com";
    $phone = "555-" . rand(100, 999) . "-" . rand(1000, 9999);
    $dob = date('Y-m-d', strtotime('-' . (30 + $years) . ' years'));
    $gender = rand(0, 1) ? 'Male' : 'Female';
    $address = rand(100, 999) . " Main St, Medical City";
    $license = "ML" . rand(10000, 99999);
    $work_address = rand(100, 999) . " Health Avenue, Medical Center";
    $bio = "Experienced $specialty specialist with $years years of practice. Committed to providing compassionate and effective care.";

    // Create variables for each parameter to avoid reference issues
    $role = 'doctor';
    $email_verified_at = date('Y-m-d H:i:s');

    $stmt->bind_param(
        "ssssssssssssiss",
        $role,
        $username,
        $email,
        $hashed_password,
        $phone,
        $dob,
        $gender,
        $address,
        $specialty,
        $license,
        $work_address,
        $profile_image,
        $years,
        $languages_spoken,
        $bio
    );

    $stmt->execute();
    $doctor_id = $conn->insert_id;
    $doctor_ids[] = $doctor_id;

    // Add degrees for each doctor
    $degrees = [
        ['MD', 'University Medical School', rand(1995, 2015)],
        ['Residency in ' . $specialty, 'City General Hospital', rand(2000, 2018)],
        ['Board Certification', 'American Board of ' . $specialty, rand(2005, 2020)]
    ];

    // Add optional fellowship
    if (rand(0, 1)) {
        $degrees[] = ['Fellowship', 'Specialized ' . $specialty . ' Center', rand(2010, 2020)];
    }

    foreach ($degrees as $degree) {
        $stmt = $conn->prepare("INSERT INTO degrees (doctor_id, degree_name, institution, passing_year) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $doctor_id, $degree[0], $degree[1], $degree[2]);
        $stmt->execute();
    }

    // Add time slots for each doctor (next 7 days)
    for ($day = 0; $day < 7; $day++) {
        $date = date('Y-m-d', strtotime("+$day days"));

        // Morning slots
        $start_time = "$date 09:00:00";
        $end_time = "$date 12:00:00";

        $stmt = $conn->prepare("INSERT INTO time_slots (doctor_id, start_time, end_time, location, status, capacity, booked_count, slot_duration) 
                               VALUES (?, ?, ?, ?, 'available', 5, 0, 30)");
        $stmt->bind_param("isss", $doctor_id, $start_time, $end_time, $work_address);
        $stmt->execute();

        // Afternoon slots
        $start_time = "$date 14:00:00";
        $end_time = "$date 17:00:00";

        $stmt = $conn->prepare("INSERT INTO time_slots (doctor_id, start_time, end_time, location, status, capacity, booked_count, slot_duration) 
                               VALUES (?, ?, ?, ?, 'available', 5, 0, 30)");
        $stmt->bind_param("isss", $doctor_id, $start_time, $end_time, $work_address);
        $stmt->execute();
    }
}

// ----------------------
// Insert Patients
// ----------------------
$patient_ids = [];
for ($i = 0; $i < $total_patients; $i++) {
    $profile_image = $patient_images[$i % count($patient_images)];

    $stmt = $conn->prepare("INSERT INTO users (role, username, email, password, phone, date_of_birth, gender, address, 
                           medical_history, profile_image, email_verified_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $username = ucfirst(strtolower(substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 8)));
    $email = strtolower($username) . "@example.com";
    $phone = "555-" . rand(100, 999) . "-" . rand(1000, 9999);
    $dob = date('Y-m-d', strtotime('-' . rand(18, 70) . ' years'));
    $gender = rand(0, 1) ? 'Male' : 'Female';
    $address = rand(100, 999) . " Residential Ave, Cityville";

    // More varied medical histories
    $medical_conditions = [
        'None',
        'Hypertension',
        'Type 2 Diabetes',
        'Asthma',
        'Arthritis',
        'High Cholesterol'
    ];
    $medical_history = $medical_conditions[array_rand($medical_conditions)];

    $stmt->bind_param(
        "sssssssssss",
        'patient',
        $username,
        $email,
        $hashed_password,
        $phone,
        $dob,
        $gender,
        $address,
        $medical_history,
        $profile_image,
        $email_verified_at
    );

    $stmt->execute();
    $patient_id = $conn->insert_id;
    $patient_ids[] = $patient_id;
}

// ----------------------
// Insert Posts
// ----------------------
$post_ids = [];
foreach ($doctor_ids as $doctor_id) {
    $post_count = rand(3, 8); // More posts per doctor

    foreach ($post_types as $type => $titles) {
        $title = $titles[array_rand($titles)];
        $content = generate_post_content($type, $title);

        $stmt = $conn->prepare("INSERT INTO posts (doctor_id, title, content, image, created_at) 
                               VALUES (?, ?, ?, ?, ?)");

        $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' days'));
        $stmt->bind_param("issss", $doctor_id, $title, $content, $post_image, $created_at);
        $stmt->execute();

        $post_id = $conn->insert_id;
        $post_ids[] = $post_id;
    }
}

// ----------------------
// Insert Post Likes
// ----------------------
foreach ($post_ids as $post_id) {
    // Each post gets 0-5 likes
    $like_count = rand(0, 5);
    $likers = array_rand($patient_ids, min($like_count, count($patient_ids)));

    if (!is_array($likers) && $like_count > 0) {
        $likers = [$likers];
    }

    if (is_array($likers)) {
        foreach ($likers as $liker_index) {
            $user_id = $patient_ids[$liker_index];

            $stmt = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $post_id, $user_id);
            $stmt->execute();
        }
    }
}

// ----------------------
// Insert Appointments
// ----------------------
// Get all time slots
$time_slots = [];
$result = $conn->query("SELECT id, doctor_id, start_time, end_time, location FROM time_slots");
while ($row = $result->fetch_assoc()) {
    $time_slots[] = $row;
}

// Create appointments for patients
foreach ($patient_ids as $patient_id) {
    // Each patient has 1-3 appointments
    $appointment_count = rand(1, 3);

    for ($i = 0; $i < $appointment_count && !empty($time_slots); $i++) {
        // Pick a random time slot
        $slot_index = array_rand($time_slots);
        $slot = $time_slots[$slot_index];

        // Remove used slot
        array_splice($time_slots, $slot_index, 1);

        $doctor_id = $slot['doctor_id'];
        $slot_id = $slot['id'];
        $start_time = $slot['start_time'];
        $end_time = $slot['end_time'];
        $location = $slot['location'];
        $reason = $appointment_reasons[array_rand($appointment_reasons)];
        $status = array_rand(['confirmed' => 1, 'completed' => 2, 'cancelled' => 3]);
        $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));

        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, slot_id, start_time, end_time, location, reason, status, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisssss", $patient_id, $doctor_id, $slot_id, $start_time, $end_time, $location, $reason, $status, $created_at);
        $stmt->execute();

        // Update the time slot status and booked count
        $stmt = $conn->prepare("UPDATE time_slots SET booked_count = booked_count + 1 WHERE id = ?");
        $stmt->bind_param("i", $slot_id);
        $stmt->execute();
    }
}

// ----------------------
// Insert Reviews
// ----------------------
// Only add reviews for completed appointments
$result = $conn->query("SELECT DISTINCT patient_id, doctor_id FROM appointments WHERE status = 'completed'");
$completed_appointments = [];
while ($row = $result->fetch_assoc()) {
    $completed_appointments[] = $row;
}

foreach ($completed_appointments as $appointment) {
    $patient_id = $appointment['patient_id'];
    $doctor_id = $appointment['doctor_id'];

    // Random rating (3.5 to 5.0)
    $rating = (rand(35, 50)) / 10;

    $comments = [
        "Great doctor. Very professional and helpful.",
        "Excellent care and attention to detail.",
        "I was very satisfied with my appointment.",
        "Dr. was knowledgeable and took the time to address all my concerns.",
        "Very thorough examination and clear explanations.",
        "The doctor was attentive and made me feel comfortable.",
        "I appreciated the doctor's expertise and bedside manner."
    ];

    $comment = $comments[array_rand($comments)];
    $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));

    $stmt = $conn->prepare("INSERT INTO reviews (doctor_id, patient_id, rating, comment, created_at) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iidss", $doctor_id, $patient_id, $rating, $comment, $created_at);
    $stmt->execute();
}

// ----------------------
// Insert Notifications
// ----------------------
$notification_types = ['appointment', 'system', 'reminder'];
$notification_messages = [
    'appointment' => [
        'Your appointment has been confirmed.',
        'Reminder: You have an appointment tomorrow.',
        'Your appointment has been rescheduled.'
    ],
    'system' => [
        'Welcome to MediLinx! We\'re glad to have you.',
        'Your profile has been updated successfully.',
        'New health tip has been posted.'
    ],
    'reminder' => [
        'Don\'t forget to take your medication.',
        'It\'s time for your annual check-up.',
        'Reminder to update your medical history.'
    ]
];

foreach (array_merge($patient_ids, $doctor_ids) as $user_id) {
    // Each user gets 2-5 notifications
    $notification_count = rand(2, 5);

    for ($i = 0; $i < $notification_count; $i++) {
        $type = $notification_types[array_rand($notification_types)];
        $message = $notification_messages[$type][array_rand($notification_messages[$type])];
        $is_read = rand(0, 1);
        $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));

        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, is_read, created_at) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $message, $type, $is_read, $created_at);
        $stmt->execute();
    }
}

// Create notification settings for all users
foreach (array_merge($patient_ids, $doctor_ids) as $user_id) {
    $stmt = $conn->prepare("INSERT INTO notification_settings 
                           (user_id, email_notifications, appointment_notifications, system_notifications, reminder_notifications) 
                           VALUES (?, 1, 1, 1, 1)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Close connection
$conn->close();

echo "Demo data has been successfully inserted!";
echo "<br>Created " . count($doctor_ids) . " doctors";
echo "<br>Created " . count($patient_ids) . " patients";
echo "<br>Created " . count($post_ids) . " posts";
echo "<br><a href='index.php'>Return to Home</a>";

// Helper function for rich post content
function generate_post_content($type, $title)
{
    switch ($type) {
        case 'article':
            return "<h2>$title</h2><p>" . implode("</p><p>", [
                "Recent studies have shown important developments in this field.",
                "Clinical trials demonstrate a 30% improvement in outcomes.",
                "Patients should consult their doctors for personalized advice."
            ]) . "</p>";
        case 'health_tip':
            return "<h2>$title</h2><ol><li>First recommendation</li><li>Second suggestion</li><li>Important reminder</li></ol>";
        case 'faq':
            return "<h2>$title</h2><div class='faq'><h3>Common Question</h3><p>Detailed answer</p></div>";
    }
}
