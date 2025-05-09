-- SQL Insert Script for Doctors and Patients
-- 30 Doctors and 8 Patients with profile images

-- Doctors INSERT statements
INSERT INTO `users` (`role`, `username`, `email`, `password`, `phone`, `date_of_birth`, `gender`, `address`, `medical_history`, `specialty`, `medical_license_number`, `work_address`, `consultation_hours`, `profile_image`, `email_verified_at`, `email_verification_pin`, `degrees_and_certifications`, `years_of_experience`, `available_consultation_hours`, `languages_spoken`, `professional_biography`)
VALUES
-- Doctors
('doctor', 'Dr Alam', 'alam@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000001', '1980-03-10', 'Male', 'House 14, Road 7, Dhanmondi, Dhaka', NULL, 'Cardiology', 'BMDC-C-1253', 'Green Life Hospital, Dhaka', '9 AM - 5 PM', 'uploads/profile_images/1 (10).jpg', '2024-01-01 12:00:00', NULL, 'MBBS (DMC), FCPS (Cardiology), Fellowship (UK)', 15, '9 AM - 12 PM, 3 PM - 5 PM', 'Bangla, English', 'Dr. Alam is a highly experienced cardiologist specializing in interventional cardiology and cardiac electrophysiology. He has performed over 1000 successful cardiac procedures.'),

('doctor', 'Dr Rahman', 'rahman@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000002', '1975-07-22', 'Male', 'Gulshan-2, Dhaka', NULL, 'Neurology', 'BMDC-N-2187', 'Evercare Hospital, Dhaka', '10 AM - 4 PM', 'uploads/profile_images/1 (11).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Neurology), Fellowship (USA)', 20, '10 AM - 1 PM, 2 PM - 4 PM', 'Bangla, English, Hindi', 'Dr. Rahman has pioneered several neurological treatment protocols in Bangladesh and is renowned for his expertise in stroke management and neurodegenerative disorders.'),

('doctor', 'Dr Fatima', 'fatima@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000003', '1983-11-05', 'Female', 'Uttara, Dhaka', NULL, 'Gynecology', 'BMDC-G-3456', 'United Hospital, Dhaka', '9 AM - 3 PM', 'uploads/profile_images/1 (12).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, FCPS (Gynecology & Obstetrics)', 12, '9 AM - 12 PM, 1 PM - 3 PM', 'Bangla, English', 'Dr. Fatima is dedicated to womens health, with special focus on high-risk pregnancies and reproductive endocrinology. She has helped thousands of women with fertility issues.'),

('doctor', 'Dr Kamal', 'kamal@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000004', '1978-05-17', 'Male', 'Banani, Dhaka', NULL, 'Orthopedics', 'BMDC-O-2876', 'Apollo Hospital, Dhaka', '10 AM - 6 PM', 'uploads/profile_images/1 (13).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MS (Orthopedics), Fellowship (UK)', 17, '10 AM - 1 PM, 3 PM - 6 PM', 'Bangla, English', 'Dr. Kamal specializes in joint replacement surgeries and sports injuries. He has treated several national athletes and performs minimally invasive orthopedic procedures.'),

('doctor', 'Dr Nasreen', 'nasreen@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000005', '1982-09-30', 'Female', 'Mirpur, Dhaka', NULL, 'Dermatology', 'BMDC-D-4321', 'Labaid Specialized Hospital, Dhaka', '11 AM - 7 PM', 'uploads/profile_images/1 (14).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, DDV, Fellowship (Singapore)', 13, '11 AM - 2 PM, 4 PM - 7 PM', 'Bangla, English', 'Dr. Nasreen is a leading dermatologist with expertise in cosmetic dermatology and treatment of complex skin disorders. She conducts regular workshops on skin health.'),

('doctor', 'Dr Hasan', 'hasan@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000006', '1976-04-12', 'Male', 'Mohammadpur, Dhaka', NULL, 'Gastroenterology', 'BMDC-G-1872', 'Ibn Sina Hospital, Dhaka', '9 AM - 4 PM', 'uploads/profile_images/1 (15).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Gastroenterology), PhD (UK)', 19, '9 AM - 12 PM, 2 PM - 4 PM', 'Bangla, English, Arabic', 'Dr. Hasan is renowned for his diagnostic accuracy in complex gastrointestinal disorders. He has published numerous research papers on inflammatory bowel diseases.'),

('doctor', 'Dr Sultana', 'sultana@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000007', '1985-08-21', 'Female', 'Bashundhara, Dhaka', NULL, 'Ophthalmology', 'BMDC-O-5432', 'Bangladesh Eye Hospital, Dhaka', '10 AM - 5 PM', 'uploads/profile_images/1 (16).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, FCPS (Ophthalmology), Fellowship (Australia)', 10, '10 AM - 1 PM, 3 PM - 5 PM', 'Bangla, English', 'Dr. Sultana specializes in retinal surgery and glaucoma management. She leads the eye camp initiative providing free eye care to underserved communities.'),

('doctor', 'Dr Mahmud', 'mahmud@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000008', '1974-01-15', 'Male', 'Khilgaon, Dhaka', NULL, 'Psychiatry', 'BMDC-P-2986', 'National Institute of Mental Health, Dhaka', '9 AM - 3 PM', 'uploads/profile_images/1 (17).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Psychiatry), MRCPsych (UK)', 22, '9 AM - 12 PM, 1 PM - 3 PM', 'Bangla, English', 'Dr. Mahmud is a compassionate psychiatrist with expertise in mood disorders and addiction psychiatry. He advocates for mental health awareness through his community outreach programs.'),

('doctor', 'Dr Aisha', 'aisha@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000009', '1984-06-28', 'Female', 'Lalmatia, Dhaka', NULL, 'Pediatrics', 'BMDC-P-4567', 'Shishu Hospital, Dhaka', '10 AM - 6 PM', 'uploads/profile_images/1 (18).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, FCPS (Pediatrics), Fellowship (Japan)', 11, '10 AM - 1 PM, 3 PM - 6 PM', 'Bangla, English, Japanese', 'Dr. Aisha is dedicated to childrens health, focusing on pediatric infectious diseases and neonatal care. She has developed protocols for managing childhood asthma and allergies.'),

('doctor', 'Dr Ahmed', 'ahmed@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000010', '1970-12-03', 'Male', 'Gulshan-1, Dhaka', NULL, 'Oncology', 'BMDC-O-1543', 'National Cancer Institute, Dhaka', '9 AM - 4 PM', 'uploads/profile_images/1 (19).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Oncology), Fellowship (USA)', 25, '9 AM - 12 PM, 2 PM - 4 PM', 'Bangla, English, French', 'Dr. Ahmed is a pioneer in oncology research in Bangladesh. He has introduced several innovative cancer treatment protocols and leads the national cancer prevention program.'),

('doctor', 'Dr Jahan', 'jahan@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000011', '1981-02-19', 'Female', 'Wari, Dhaka', NULL, 'Endocrinology', 'BMDC-E-3678', 'BIRDEM Hospital, Dhaka', '10 AM - 5 PM', 'uploads/profile_images/1 (20).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, FCPS (Medicine), MD (Endocrinology)', 14, '10 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English, Urdu', 'Dr. Jahan specializes in diabetes management and thyroid disorders. She conducts groundbreaking research on metabolic syndromes prevalent in South Asian populations.'),

('doctor', 'Dr Kabir', 'kabir@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000012', '1977-07-05', 'Male', 'Motijheel, Dhaka', NULL, 'Nephrology', 'BMDC-N-2176', 'Kidney Foundation Hospital, Dhaka', '9 AM - 3 PM', 'uploads/profile_images/1 (21).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Nephrology), Fellowship (Germany)', 18, '9 AM - 12 PM, 1 PM - 3 PM', 'Bangla, English, German', 'Dr. Kabir has pioneered several kidney transplant techniques in Bangladesh. He is dedicated to improving renal care and dialysis services across the country.'),

('doctor', 'Dr Nusrat', 'nusrat@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000013', '1986-04-14', 'Female', 'Mohakhali, Dhaka', NULL, 'Pulmonology', 'BMDC-P-5123', 'National Chest Hospital, Dhaka', '10 AM - 6 PM', 'uploads/profile_images/1 (22).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, FCPS (Medicine), MD (Pulmonology)', 9, '10 AM - 1 PM, 3 PM - 6 PM', 'Bangla, English', 'Dr. Nusrat specializes in respiratory disorders and sleep medicine. She has established the first comprehensive sleep lab in Bangladesh for diagnosing sleep-related breathing disorders.'),

('doctor', 'Dr Omar', 'omar@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000014', '1972-09-27', 'Male', 'Banasree, Dhaka', NULL, 'Urology', 'BMDC-U-1876', 'Urology & Transplant Foundation, Dhaka', '9 AM - 4 PM', 'uploads/profile_images/1 (23).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MS (Urology), Fellowship (UK)', 23, '9 AM - 12 PM, 2 PM - 4 PM', 'Bangla, English', 'Dr. Omar is a leading urologist specializing in minimally invasive urological surgeries and prostate cancer management. He has trained numerous urologists across the country.'),

('doctor', 'Dr Priya', 'priya@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000015', '1983-03-31', 'Female', 'Malibagh, Dhaka', NULL, 'Rheumatology', 'BMDC-R-3452', 'Popular Medical College Hospital, Dhaka', '10 AM - 5 PM', 'uploads/profile_images/1 (24).png', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Medicine), Fellowship (Rheumatology)', 12, '10 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English, Hindi', 'Dr. Priya is dedicated to treating complex autoimmune disorders and inflammatory arthritis. She leads research on rheumatic diseases in Bangladesh.'),

('doctor', 'Dr Quader', 'quader@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000016', '1976-11-09', 'Male', 'Dhanmondi, Dhaka', NULL, 'Hepatology', 'BMDC-H-2432', 'Liver Foundation, Dhaka', '9 AM - 3 PM', 'uploads/profile_images/1 (25).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Hepatology), PhD (Japan)', 19, '9 AM - 12 PM, 1 PM - 3 PM', 'Bangla, English, Japanese', 'Dr. Quader has made significant contributions to the field of hepatology in Bangladesh, particularly in the management of viral hepatitis and liver cancer.'),

('doctor', 'Dr Rima', 'rima@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000017', '1984-08-16', 'Female', 'Farmgate, Dhaka', NULL, 'Hematology', 'BMDC-H-4789', 'Dhaka Medical College Hospital, Dhaka', '10 AM - 6 PM', 'uploads/profile_images/1 (26).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, FCPS (Medicine), MD (Hematology)', 11, '10 AM - 1 PM, 3 PM - 6 PM', 'Bangla, English', 'Dr. Rima specializes in blood disorders and stem cell transplantation. She has established the first comprehensive hematology care center in the country.'),

('doctor', 'Dr Salam', 'salam@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000018', '1973-05-23', 'Male', 'Uttara, Dhaka', NULL, 'Plastic Surgery', 'BMDC-P-1243', 'Bangladesh Institute of Plastic Surgery, Dhaka', '9 AM - 4 PM', 'uploads/profile_images/1 (27).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MS (Plastic Surgery), Fellowship (Australia)', 22, '9 AM - 12 PM, 2 PM - 4 PM', 'Bangla, English', 'Dr. Salam is renowned for reconstructive surgery and burn management. He leads the national burn prevention program and has trained surgeons across South Asia.'),

('doctor', 'Dr Tabassum', 'tabassum@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000019', '1985-01-07', 'Female', 'Gulshan, Dhaka', NULL, 'Neurosurgery', 'BMDC-N-5234', 'National Neuroscience Institute, Dhaka', '10 AM - 5 PM', 'uploads/profile_images/1 (28).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MS (Neurosurgery), Fellowship (USA)', 10, '10 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English, Spanish', 'Dr. Tabassum specializes in minimally invasive brain surgeries and spinal cord injuries. She has introduced several innovative neurosurgical techniques in Bangladesh.'),

('doctor', 'Dr Uddin', 'uddin@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000020', '1975-10-14', 'Male', 'Mirpur, Dhaka', NULL, 'ENT', 'BMDC-E-1987', 'ENT Foundation Hospital, Dhaka', '9 AM - 3 PM', 'uploads/profile_images/1 (29).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MS (ENT), Fellowship (UK)', 20, '9 AM - 12 PM, 1 PM - 3 PM', 'Bangla, English', 'Dr. Uddin is a leading ENT specialist with expertise in head and neck surgeries. He has pioneered cochlear implant surgeries in Bangladesh.'),

('doctor', 'Dr Vabna', 'vabna@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000021', '1982-07-29', 'Female', 'Khilgaon, Dhaka', NULL, 'Radiology', 'BMDC-R-3765', 'Radiology & Imaging Institute, Dhaka', '10 AM - 6 PM', 'uploads/profile_images/1 (30).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Radiology), Fellowship (Germany)', 13, '10 AM - 1 PM, 3 PM - 6 PM', 'Bangla, English, German', 'Dr. Vabna specializes in interventional radiology and advanced imaging techniques. She has established several imaging centers across Bangladesh.'),

('doctor', 'Dr Wahid', 'wahid@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000022', '1978-02-11', 'Male', 'Bashundhara, Dhaka', NULL, 'Cardiac Surgery', 'BMDC-C-2154', 'National Heart Foundation, Dhaka', '9 AM - 4 PM', 'uploads/profile_images/1 (31).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MS (Cardiac Surgery), Fellowship (USA)', 17, '9 AM - 12 PM, 2 PM - 4 PM', 'Bangla, English', 'Dr. Wahid is a renowned cardiac surgeon who has performed over 2000 open-heart surgeries. He specializes in minimally invasive cardiac procedures and valve replacements.'),

('doctor', 'Dr Yasmin', 'yasmin@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000023', '1986-09-03', 'Female', 'Mohammadpur, Dhaka', NULL, 'Dentistry', 'BMDC-D-4876', 'Dental Care Center, Dhaka', '10 AM - 5 PM', 'uploads/profile_images/1 (32).jpg', '2024-01-01 12:00:00', NULL, 'BDS, MDS (Orthodontics), Fellowship (Singapore)', 9, '10 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Yasmin specializes in orthodontics and cosmetic dentistry. She has pioneered several dental implant techniques in Bangladesh.'),

('doctor', 'Dr Zaman', 'zaman@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000024', '1974-06-18', 'Male', 'Banani, Dhaka', NULL, 'Anesthesiology', 'BMDC-A-1432', 'Square Hospital, Dhaka', '9 AM - 3 PM', 'uploads/profile_images/1 (33).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Anesthesiology), Fellowship (Canada)', 21, '9 AM - 12 PM, 1 PM - 3 PM', 'Bangla, English, French', 'Dr. Zaman is an expert in pain management and critical care anesthesia. He has trained numerous anesthesiologists and established protocols for safe anesthesia practices.'),

('doctor', 'Dr Akbar', 'akbar@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000025', '1983-12-25', 'Male', 'Wari, Dhaka', NULL, 'Infectious Diseases', 'BMDC-I-5321', 'Infectious Disease Hospital, Dhaka', '10 AM - 6 PM', 'uploads/profile_images/1 (34).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Medicine), Fellowship (Infectious Diseases)', 12, '10 AM - 1 PM, 3 PM - 6 PM', 'Bangla, English', 'Dr. Akbar specializes in tropical infections and emerging infectious diseases. He has been instrumental in managing several epidemic outbreaks in Bangladesh.'),

('doctor', 'Dr Bushra', 'bushra@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000026', '1984-04-02', 'Female', 'Uttara, Dhaka', NULL, 'Physical Medicine', 'BMDC-P-3654', 'Physical Rehabilitation Center, Dhaka', '9 AM - 4 PM', 'uploads/profile_images/1 (35).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, FCPS (Physical Medicine), Fellowship (Australia)', 11, '9 AM - 12 PM, 2 PM - 4 PM', 'Bangla, English', 'Dr. Bushra is dedicated to physical rehabilitation medicine, helping patients recover from injuries and surgeries through comprehensive rehabilitation programs.'),

('doctor', 'Dr Chowdhury', 'chowdhury@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000027', '1977-08-19', 'Male', 'Dhanmondi, Dhaka', NULL, 'General Surgery', 'BMDC-G-2187', 'General Hospital, Dhaka', '10 AM - 5 PM', 'uploads/profile_images/1 (36).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MS (Surgery), Fellowship (UK)', 18, '10 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Chowdhury specializes in minimally invasive surgical techniques. He has performed thousands of laparoscopic surgeries and trained surgeons nationwide.'),

('doctor', 'Dr Dilara', 'dilara@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000028', '1981-01-15', 'Female', 'Gulshan, Dhaka', NULL, 'Nutrition', 'BMDC-N-4765', 'Nutrition Institute, Dhaka', '9 AM - 3 PM', 'uploads/profile_images/1 (37).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Nutrition), PhD (USA)', 14, '9 AM - 12 PM, 1 PM - 3 PM', 'Bangla, English', 'Dr. Dilara is a leading nutritionist specializing in clinical nutrition and metabolic disorders. She has developed dietary protocols for various medical conditions.'),

('doctor', 'Dr Elias', 'elias@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000029', '1976-05-28', 'Male', 'Mohakhali, Dhaka', NULL, 'Sports Medicine', 'BMDC-S-1876', 'Sports Medicine Center, Dhaka', '10 AM - 6 PM', 'uploads/profile_images/1 (38).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MS (Orthopedics), Fellowship (Sports Medicine)', 19, '10 AM - 1 PM, 3 PM - 6 PM', 'Bangla, English', 'Dr. Elias specializes in sports injuries and rehabilitation. He has served as the team physician for several national sports teams and Olympic athletes.'),

('doctor', 'Dr Faisal', 'faisal@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01710000030', '1979-03-08', 'Male', 'Baridhara, Dhaka', NULL, 'Pain Medicine', 'BMDC-P-2365', 'Pain Management Center, Dhaka', '9 AM - 5 PM', 'uploads/profile_images/1 (47).jpg', '2024-01-01 12:00:00', NULL, 'MBBS, MD (Anesthesiology), Fellowship (Pain Medicine)', 16, '9 AM - 12 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Faisal is specialized in chronic pain management. He employs multidisciplinary approaches including interventional procedures and innovative non-opioid therapies.');





-- SQL Insert Script for Doctors' Degrees
-- Each doctor has multiple degrees from reputable medical institutions

INSERT INTO `degrees` (`doctor_id`, `degree_name`, `institution`, `passing_year`)
VALUES
-- Dr. Alam (Cardiology)
(85, 'MBBS', 'Dhaka Medical College', 2003),
(85, 'FCPS (Cardiology)', 'Bangladesh College of Physicians and Surgeons', 2010),
(85, 'Fellowship in Interventional Cardiology', 'Royal College of Physicians', 2012),

-- Dr. Rahman (Neurology)
(86, 'MBBS', 'Mymensingh Medical College', 1998),
(86, 'MD (Neurology)', 'Bangabandhu Sheikh Mujib Medical University', 2005),
(86, 'Fellowship in Neuro-intervention', 'Johns Hopkins University', 2007),

-- Dr. Fatima (Gynecology)
(87, 'MBBS', 'Sir Salimullah Medical College', 2006),
(87, 'FCPS (Gynecology & Obstetrics)', 'Bangladesh College of Physicians and Surgeons', 2013),
(87, 'Training in Reproductive Medicine', 'National University Hospital Singapore', 2015),

-- Dr. Kamal (Orthopedics)
(88, 'MBBS', 'Chittagong Medical College', 2001),
(88, 'MS (Orthopedics)', 'Bangabandhu Sheikh Mujib Medical University', 2008),
(88, 'Fellowship in Joint Replacement', 'Imperial College London', 2010),

-- Dr. Nasreen (Dermatology)
(89, 'MBBS', 'Rajshahi Medical College', 2005),
(89, 'DDV (Dermatology)', 'Bangabandhu Sheikh Mujib Medical University', 2012),
(89, 'Fellowship in Cosmetic Dermatology', 'National Skin Centre Singapore', 2014),

-- Dr. Hasan (Gastroenterology)
(90, 'MBBS', 'Sylhet MAG Osmani Medical College', 1999),
(90, 'MD (Gastroenterology)', 'Bangabandhu Sheikh Mujib Medical University', 2006),
(90, 'PhD in Gastrointestinal Disorders', 'University of Manchester', 2009),

-- Dr. Sultana (Ophthalmology)
(91, 'MBBS', 'Dhaka Medical College', 2008),
(91, 'FCPS (Ophthalmology)', 'Bangladesh College of Physicians and Surgeons', 2015),
(91, 'Fellowship in Retinal Surgery', 'University of Sydney', 2017),

-- Dr. Mahmud (Psychiatry)
(92, 'MBBS', 'Sher-e-Bangla Medical College', 1997),
(92, 'MD (Psychiatry)', 'Bangabandhu Sheikh Mujib Medical University', 2002),
(92, 'MRCPsych', 'Royal College of Psychiatrists UK', 2005),

-- Dr. Aisha (Pediatrics)
(93, 'MBBS', 'Comilla Medical College', 2007),
(93, 'FCPS (Pediatrics)', 'Bangladesh College of Physicians and Surgeons', 2014),
(93, 'Fellowship in Pediatric Infectious Diseases', 'Tokyo Medical University', 2016),

-- Dr. Ahmed (Oncology)
(94, 'MBBS', 'Dhaka Medical College', 1995),
(94, 'MD (Oncology)', 'Bangabandhu Sheikh Mujib Medical University', 2000),
(94, 'Fellowship in Medical Oncology', 'Memorial Sloan Kettering Cancer Center', 2003),

-- Dr. Jahan (Endocrinology)
(95, 'MBBS', 'Chittagong Medical College', 2004),
(95, 'FCPS (Medicine)', 'Bangladesh College of Physicians and Surgeons', 2011),
(95, 'MD (Endocrinology)', 'Bangabandhu Sheikh Mujib Medical University', 2014),

-- Dr. Kabir (Nephrology)
(96, 'MBBS', 'Mymensingh Medical College', 2000),
(96, 'MD (Nephrology)', 'Bangabandhu Sheikh Mujib Medical University', 2007),
(96, 'Fellowship in Transplant Nephrology', 'Charité – Universitätsmedizin Berlin', 2009),

-- Dr. Nusrat (Pulmonology)
(97, 'MBBS', 'Rangpur Medical College', 2009),
(97, 'FCPS (Medicine)', 'Bangladesh College of Physicians and Surgeons', 2016),
(97, 'MD (Pulmonology)', 'Bangabandhu Sheikh Mujib Medical University', 2018),

-- Dr. Omar (Urology)
(98, 'MBBS', 'Dhaka Medical College', 1996),
(98, 'MS (Urology)', 'Bangabandhu Sheikh Mujib Medical University', 2002),
(98, 'Fellowship in Urological Surgery', 'Kings College London', 2004),

-- Dr. Priya (Rheumatology)
(99, 'MBBS', 'Sir Salimullah Medical College', 2006),
(99, 'MD (Medicine)', 'Bangabandhu Sheikh Mujib Medical University', 2013),
(99, 'Fellowship in Rheumatology', 'All India Institute of Medical Sciences', 2015),

-- Dr. Quader (Hepatology)
(100, 'MBBS', 'Sher-e-Bangla Medical College', 1999),
(100, 'MD (Hepatology)', 'Bangabandhu Sheikh Mujib Medical University', 2006),
(100, 'PhD in Liver Diseases', 'Osaka University', 2009),

-- Dr. Rima (Hematology)
(101, 'MBBS', 'Chittagong Medical College', 2007),
(101, 'FCPS (Medicine)', 'Bangladesh College of Physicians and Surgeons', 2014),
(101, 'MD (Hematology)', 'Bangabandhu Sheikh Mujib Medical University', 2016),

-- Dr. Salam (Plastic Surgery)
(102, 'MBBS', 'Dhaka Medical College', 1996),
(102, 'MS (Plastic Surgery)', 'Bangabandhu Sheikh Mujib Medical University', 2001),
(102, 'Fellowship in Reconstructive Surgery', 'University of Melbourne', 2003),

-- Dr. Tabassum (Neurosurgery)
(103, 'MBBS', 'Rajshahi Medical College', 2008),
(103, 'MS (Neurosurgery)', 'Bangabandhu Sheikh Mujib Medical University', 2015),
(103, 'Fellowship in Minimally Invasive Neurosurgery', 'Stanford University', 2017),

-- Dr. Uddin (ENT)
(104, 'MBBS', 'Dhaka Medical College', 1998),
(104, 'MS (ENT)', 'Bangabandhu Sheikh Mujib Medical University', 2005),
(104, 'Fellowship in Head and Neck Surgery', 'University College London', 2007),

-- Dr. Vabna (Radiology)
(105, 'MBBS', 'Sylhet MAG Osmani Medical College', 2005),
(105, 'MD (Radiology)', 'Bangabandhu Sheikh Mujib Medical University', 2012),
(105, 'Fellowship in Interventional Radiology', 'Charité – Universitätsmedizin Berlin', 2014),

-- Dr. Wahid (Cardiac Surgery)
(106, 'MBBS', 'Mymensingh Medical College', 2001),
(106, 'MS (Cardiac Surgery)', 'Bangabandhu Sheikh Mujib Medical University', 2008),
(106, 'Fellowship in Minimally Invasive Cardiac Surgery', 'Cleveland Clinic', 2010),

-- Dr. Yasmin (Dentistry)
(107, 'BDS', 'Dhaka Dental College', 2009),
(107, 'MDS (Orthodontics)', 'Bangabandhu Sheikh Mujib Medical University', 2016),
(107, 'Fellowship in Cosmetic Dentistry', 'National University of Singapore', 2018),

-- Dr. Zaman (Anesthesiology)
(108, 'MBBS', 'Dhaka Medical College', 1997),
(108, 'MD (Anesthesiology)', 'Bangabandhu Sheikh Mujib Medical University', 2004),
(108, 'Fellowship in Critical Care', 'University of Toronto', 2006),

-- Dr. Akbar (Infectious Diseases)
(109, 'MBBS', 'Rajshahi Medical College', 2006),
(109, 'MD (Medicine)', 'Bangabandhu Sheikh Mujib Medical University', 2013),
(109, 'Fellowship in Infectious Diseases', 'Johns Hopkins University', 2015),

-- Dr. Bushra (Physical Medicine)
(110, 'MBBS', 'Chittagong Medical College', 2007),
(110, 'FCPS (Physical Medicine)', 'Bangladesh College of Physicians and Surgeons', 2014),
(110, 'Fellowship in Rehabilitation Medicine', 'University of Melbourne', 2016),

-- Dr. Chowdhury (General Surgery)
(111, 'MBBS', 'Dhaka Medical College', 2000),
(111, 'MS (Surgery)', 'Bangabandhu Sheikh Mujib Medical University', 2007),
(111, 'Fellowship in Minimally Invasive Surgery', 'Imperial College London', 2009),

-- Dr. Dilara (Nutrition)
(112, 'MBBS', 'Sir Salimullah Medical College', 2004),
(112, 'MD (Nutrition)', 'Bangabandhu Sheikh Mujib Medical University', 2011),
(112, 'PhD in Clinical Nutrition', 'Harvard University', 2013),

-- Dr. Elias (Sports Medicine)
(113, 'MBBS', 'Dhaka Medical College', 1999),
(113, 'MS (Orthopedics)', 'Bangabandhu Sheikh Mujib Medical University', 2006),
(113, 'Fellowship in Sports Medicine', 'Australian Institute of Sport', 2008),
(113, 'Diploma in Sports Rehabilitation', 'International Olympic Committee', 2010),

-- Dr. Elias (Continued)
(114, 'MBBS', 'Dhaka Medical College', 1999),
(114, 'MS (Orthopedics)', 'Bangabandhu Sheikh Mujib Medical University', 2006),
(114, 'Fellowship in Sports Medicine', 'Australian Institute of Sport', 2008),
(114, 'Diploma in Sports Rehabilitation', 'International Olympic Committee', 2010);
