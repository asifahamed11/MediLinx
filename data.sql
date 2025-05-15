-- SQL Insert Script for Doctors and Patients
-- 30 Doctors (matching profile images) and 4 patients with profile images

-- Doctors INSERT statements
INSERT INTO `users` (`role`, `username`, `email`, `password`, `phone`, `date_of_birth`, `gender`, `address`, `medical_history`, `specialty`, `medical_license_number`, `work_address`, `consultation_hours`, `profile_image`, `email_verified_at`, `email_verification_pin`, `degrees_and_certifications`, `years_of_experience`, `available_consultation_hours`, `languages_spoken`, `professional_biography`)
VALUES

-- Female Doctors
('doctor', 'Dr Sarah Rahman', 'sarah@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345601', '1985-03-15', 'Female', 'House 7, Road 5, Dhanmondi, Dhaka', NULL, 'Cardiology', 'BMDC-C-1201', 'National Heart Foundation, Dhaka', '9 AM - 5 PM', 'uploads/profile_images/(female).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, FCPS (Cardiology), Fellowship (UK)', 12, '9 AM - 12 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Sarah is a renowned cardiologist specializing in interventional cardiology with extensive experience in complex cardiac procedures.'),

('doctor', 'Dr Nadia Islam', 'nadia@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345602', '1983-06-22', 'Female', 'Khulna Medical College Road, Khulna', NULL, 'Gynecology', 'BMDC-G-1202', 'Khulna Medical College Hospital', '10 AM - 6 PM', 'uploads/profile_images/(female) (2).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, FCPS (Gynecology), Fellowship (Australia)', 15, '10 AM - 1 PM, 3 PM - 6 PM', 'Bangla, English', 'Dr. Nadia specializes in high-risk pregnancies and reproductive medicine with a focus on infertility treatments.'),

('doctor', 'Dr Farah Khan', 'farah@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345603', '1987-09-10', 'Female', 'Pahartali, Chittagong', NULL, 'Dermatology', 'BMDC-D-1203', 'Chittagong Medical College Hospital', '9 AM - 4 PM', 'uploads/profile_images/(female) (3).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, DDV, Fellowship (Singapore)', 10, '9 AM - 12 PM, 2 PM - 4 PM', 'Bangla, English', 'Dr. Farah is a skilled dermatologist known for her expertise in cosmetic dermatology and skin disorders.'),

('doctor', 'Dr Samira Ahmed', 'samira@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345604', '1982-12-05', 'Female', 'Medical College Road, Sylhet', NULL, 'Pediatrics', 'BMDC-P-1204', 'Sylhet MAG Osmani Medical College Hospital', '8 AM - 3 PM', 'uploads/profile_images/(female) (4).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, FCPS (Pediatrics), Fellowship (USA)', 14, '8 AM - 11 AM, 12 PM - 3 PM', 'Bangla, English', 'Dr. Samira has extensive experience in pediatric care and specializes in childhood development disorders.'),

('doctor', 'Dr Tasnim Haque', 'tasnim@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345605', '1984-08-18', 'Female', 'Medical College Road, Rajshahi', NULL, 'Psychiatry', 'BMDC-P-1205', 'Rajshahi Medical College Hospital', '10 AM - 5 PM', 'uploads/profile_images/(female) (5).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, FCPS (Psychiatry), Fellowship (UK)', 13, '10 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Tasnim is dedicated to mental health awareness and treatment, with expertise in mood disorders and anxiety management.'),

('doctor', 'Dr Rubina Akter', 'rubina@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345606', '1986-04-25', 'Female', 'Band Road, Rangpur', NULL, 'Endocrinology', 'BMDC-E-1206', 'Rangpur Medical College Hospital', '9 AM - 4 PM', 'uploads/profile_images/(female) (6).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Endocrinology), Fellowship (India)', 11, '9 AM - 12 PM, 1 PM - 4 PM', 'Bangla, English, Hindi', 'Dr. Rubina specializes in diabetes management and thyroid disorders with a focus on metabolic diseases.'),

('doctor', 'Dr Nafisa Hossain', 'nafisa@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345607', '1988-01-30', 'Female', 'Barishal City', NULL, 'Ophthalmology', 'BMDC-O-1207', 'Sher-e-Bangla Medical College Hospital, Barishal', '10 AM - 6 PM', 'uploads/profile_images/(female) (7).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, FCPS (Ophthalmology), Fellowship (Malaysia)', 9, '10 AM - 1 PM, 3 PM - 6 PM', 'Bangla, English', 'Dr. Nafisa is an expert in retinal surgery and comprehensive eye care with special interest in pediatric ophthalmology.'),

-- Male Doctors (23 doctors using remaining profile images)
('doctor', 'Dr Karim Uddin', 'karim@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345608', '1975-07-12', 'Male', 'Dhanmondi 27, Dhaka', NULL, 'Cardiology', 'BMDC-C-1208', 'Ibrahim Cardiac Hospital, Dhaka', '9 AM - 5 PM', 'uploads/profile_images/(male).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, FCPS (Cardiology), Fellowship (USA)', 20, '9 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Karim is a pioneer in interventional cardiology with expertise in complex cardiac procedures.'),

('doctor', 'Dr Rahim Khan', 'rahim@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345609', '1978-03-25', 'Male', 'Agrabad, Chittagong', NULL, 'Neurology', 'BMDC-N-1209', 'Chittagong Medical College Hospital', '10 AM - 6 PM', 'uploads/profile_images/(male) (2).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Neurology), Fellowship (UK)', 18, '10 AM - 2 PM, 3 PM - 6 PM', 'Bangla, English', 'Dr. Rahim is a leading neurologist specializing in stroke management and neurodegenerative disorders.'),

-- Continuing with Male Doctors
('doctor', 'Dr Mahbub Hassan', 'mahbub@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345610', '1980-05-15', 'Male', 'Mirpur-10, Dhaka', NULL, 'Orthopedics', 'BMDC-O-1210', 'National Institute of Traumatology & Orthopedic Rehabilitation', '9 AM - 5 PM', 'uploads/profile_images/(male) (3).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MS (Orthopedics), Fellowship (Germany)', 16, '9 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Mahbub is an experienced orthopedic surgeon specializing in joint replacement surgeries.'),

('doctor', 'Dr Zahid Hasan', 'zahid@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345611', '1979-08-20', 'Male', 'Medical College Road, Mymensingh', NULL, 'Pulmonology', 'BMDC-P-1211', 'Mymensingh Medical College Hospital', '10 AM - 6 PM', 'uploads/profile_images/(male) (4).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Chest Diseases), Fellowship (India)', 17, '10 AM - 2 PM, 3 PM - 6 PM', 'Bangla, English', 'Dr. Zahid is a renowned pulmonologist with expertise in respiratory diseases and sleep disorders.'),

('doctor', 'Dr Masud Rahman', 'masud@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345612', '1982-11-08', 'Male', 'Rangpur City', NULL, 'Urology', 'BMDC-U-1212', 'Rangpur Medical College Hospital', '9 AM - 5 PM', 'uploads/profile_images/(male) (5).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MS (Urology), Fellowship (Thailand)', 14, '9 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Masud specializes in minimally invasive urological surgeries and kidney disorders.'),

('doctor', 'Dr Faisal Ahmed', 'faisal@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345613', '1984-03-20', 'Male', 'Sylhet City', NULL, 'Gastroenterology', 'BMDC-G-1213', 'North East Medical College Hospital', '10 AM - 6 PM', 'uploads/profile_images/(male) (6).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Gastroenterology), Fellowship (Japan)', 12, '10 AM - 2 PM, 3 PM - 6 PM', 'Bangla, English', 'Dr. Faisal is an expert in advanced endoscopy and liver diseases.'),

('doctor', 'Dr Anwar Hossain', 'anwar@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345614', '1977-07-15', 'Male', 'Khulna City', NULL, 'Oncology', 'BMDC-O-1214', 'Khulna Medical College Hospital', '9 AM - 5 PM', 'uploads/profile_images/(male) (7).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Medical Oncology)', 'Fellowship (Singapore)', 19, '9 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Anwar specializes in cancer treatment and chemotherapy protocols.'),

('doctor', 'Dr Shahriar Islam', 'shahriar@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345615', '1983-09-28', 'Male', 'Central Road, Bogura', NULL, 'ENT', 'BMDC-E-1215', 'Shaheed Ziaur Rahman Medical College Hospital', '9 AM - 5 PM', 'uploads/profile_images/(male) (8).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MS (Otolaryngology), Fellowship (Malaysia)', 15, '9 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Shahriar is an ENT specialist with expertise in microsurgery and cochlear implants.'),

('doctor', 'Dr Mohsin Ali', 'mohsin@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345616', '1981-12-05', 'Male', 'Station Road, Comilla', NULL, 'Nephrology', 'BMDC-N-1216', 'Comilla Medical College Hospital', '9 AM - 5 PM', 'uploads/profile_images/(male) (9).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Nephrology), Fellowship (India)', 16, '9 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Mohsin is a kidney specialist focusing on dialysis and transplant care.'),



-- Adding patients
INSERT INTO `users` (`role`, `username`, `email`, `password`, `phone`, `date_of_birth`, `gender`, `address`, `medical_history`, `specialty`, `medical_license_number`, `work_address`, `consultation_hours`, `profile_image`, `email_verified_at`, `email_verification_pin`, `degrees_and_certifications`, `years_of_experience`, `available_consultation_hours`, `languages_spoken`, `professional_biography`)
VALUES
('patient', 'Asif Ahmed', 'asif@gmail.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01770396222', '1990-05-15', 'Male', 'Mohammadpur, Dhaka', 'No major health issues', NULL, NULL, NULL, NULL, NULL, 'uploads/profile_images/(male) (24).jpg', '2024-01-01 12:00:00', NULL, NULL, NULL, NULL, 'Bangla, English', NULL),

('patient', 'Maliha Rahman', 'maliha@gmail.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345631', '1988-08-20', 'Female', 'Uttara, Dhaka', 'Mild asthma', NULL, NULL, NULL, NULL, NULL, 'uploads/profile_images/(female) (4).jpg', '2024-01-01 12:00:00', NULL, NULL, NULL, NULL, 'Bangla, English', NULL),

('patient', 'Imran Khan', 'imran@gmail.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345632', '1992-03-10', 'Male', 'Gulshan, Dhaka', 'Type 1 Diabetes', NULL, NULL, NULL, NULL, NULL, 'uploads/profile_images/(male) (25).jpg', '2024-01-01 12:00:00', NULL, NULL, NULL, NULL, 'Bangla, English', NULL),

('patient', 'Nabila Haque', 'nabila@gmail.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345633', '1995-11-25', 'Female', 'Banani, Dhaka', 'No major health issues', NULL, NULL, NULL, NULL, NULL, 'uploads/profile_images/(female) (5).jpg', '2024-01-01 12:00:00', NULL, NULL, NULL, NULL, 'Bangla, English', NULL);

-- Adding degrees for doctors
INSERT INTO `degrees` (`doctor_id`, `degree_name`, `institution`, `passing_year`)
VALUES
-- Dr. Sarah Rahman (Cardiology)
(1, 'MBBS', 'Dhaka Medical College', 2003),
(1, 'FCPS (Cardiology)', 'Bangladesh College of Physicians and Surgeons', 2010),
(1, 'Fellowship in Interventional Cardiology', 'Royal College of Physicians', 2012),

-- Dr. Nadia Islam (Gynecology)
(2, 'MBBS', 'Khulna Medical College', 2001),
(2, 'FCPS (Gynecology)', 'Bangladesh College of Physicians and Surgeons', 2008),
(2, 'Fellowship in Reproductive Medicine', 'Royal Australian and New Zealand College of Obstetricians and Gynaecologists', 2010),

-- Dr. Farah Khan (Dermatology)
(3, 'MBBS', 'Chittagong Medical College', 2005),
(3, 'DDV', 'Bangabandhu Sheikh Mujib Medical University', 2012),
(3, 'Fellowship in Cosmetic Dermatology', 'National Skin Centre Singapore', 2014),

-- Dr. Samira Ahmed (Pediatrics)
(4, 'MBBS', 'Sylhet MAG Osmani Medical College', 2004),
(4, 'FCPS (Pediatrics)', 'Bangladesh College of Physicians and Surgeons', 2011),
(4, 'Fellowship in Pediatric Critical Care', 'Boston Childrens Hospital', 2013),

-- Dr. Tasnim Haque (Psychiatry)
(5, 'MBBS', 'Rajshahi Medical College', 2006),
(5, 'FCPS (Psychiatry)', 'Bangladesh College of Physicians and Surgeons', 2013),
(5, 'Fellowship in Child Psychiatry', 'Royal College of Psychiatrists, UK', 2015),

-- Dr. Rubina Akter (Endocrinology)
(6, 'MBBS', 'Rangpur Medical College', 2008),
(6, 'MD (Endocrinology)', 'Bangabandhu Sheikh Mujib Medical University', 2015),
(6, 'Fellowship in Diabetes Management', 'All India Institute of Medical Sciences', 2017),

-- Dr. Nafisa Hossain (Ophthalmology)
(7, 'MBBS', 'Sher-e-Bangla Medical College', 2010),
(7, 'FCPS (Ophthalmology)', 'Bangladesh College of Physicians and Surgeons', 2017),
(7, 'Fellowship in Retinal Surgery', 'National University of Malaysia', 2019),

-- Dr. Karim Uddin (Cardiology)
(8, 'MBBS', 'Dhaka Medical College', 1998),
(8, 'FCPS (Cardiology)', 'Bangladesh College of Physicians and Surgeons', 2005),
(8, 'Fellowship in Interventional Cardiology', 'Cleveland Clinic, USA', 2007),

-- Dr. Rahim Khan (Neurology)
(9, 'MBBS', 'Chittagong Medical College', 2000),
(9, 'MD (Neurology)', 'Bangabandhu Sheikh Mujib Medical University', 2007),
(9, 'Fellowship in Stroke Medicine', 'Kings College London, UK', 2009),

-- Dr. Mahbub Hassan (Orthopedics)
(10, 'MBBS', 'Sir Salimullah Medical College', 2002),
(10, 'MS (Orthopedics)', 'Bangabandhu Sheikh Mujib Medical University', 2009),
(10, 'Fellowship in Joint Replacement', 'Charité – Universitätsmedizin Berlin', 2011),

-- Dr. Zahid Hasan (Pulmonology)
(11, 'MBBS', 'Mymensingh Medical College', 2001),
(11, 'MD (Chest Diseases)', 'National Institute of Diseases of Chest & Hospital', 2008),
(11, 'Fellowship in Respiratory Medicine', 'All India Institute of Medical Sciences', 2010),

-- Degrees for recently added doctors
-- Dr. Faisal Ahmed (Gastroenterology)
(13, 'MBBS', 'Sylhet MAG Osmani Medical College', 2007),
(13, 'MD (Gastroenterology)', 'Bangabandhu Sheikh Mujib Medical University', 2014),
(13, 'Fellowship in Advanced Endoscopy', 'Osaka University Hospital, Japan', 2016),

-- Dr. Anwar Hossain (Oncology)
(14, 'MBBS', 'Khulna Medical College', 1999),
(14, 'MD (Medical Oncology)', 'National Cancer Institute, Bangladesh', 2006),
(14, 'Fellowship in Clinical Oncology', 'National Cancer Centre Singapore', 2008),

-- Dr. Shahriar Islam (ENT)
(15, 'MBBS', 'Shaheed Ziaur Rahman Medical College', 2006),
(15, 'MS (Otolaryngology)', 'Bangabandhu Sheikh Mujib Medical University', 2013),
(15, 'Fellowship in Advanced Otology', 'University of Malaya Medical Centre', 2015),

-- Dr. Mohsin Ali (Nephrology)
(16, 'MBBS', 'Comilla Medical College', 2004),
(16, 'MD (Nephrology)', 'Institute of Post Graduate Medicine & Research', 2011),
(16, 'Fellowship in Transplant Medicine', 'All India Institute of Medical Sciences', 2013);

