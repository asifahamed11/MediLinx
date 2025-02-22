<?php
// doctor_register.php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Registration - MediLinx</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2A9D8F;
            --secondary: #264653;
            --accent: #4CAF50;
            --background: #F5F9FF;
            --text: #2C3E50;
            --neumorphic-shadow: 8px 8px 16px #d9d9d9, 
                                -8px -8px 16px #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--background);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .container {
            background: var(--background);
            padding: 3rem;
            border-radius: 30px;
            box-shadow: var(--neumorphic-shadow);
            max-width: 900px;
            width: 100%;
            transform: translateY(20px);
            opacity: 0;
            animation: fadeInUp 0.8s cubic-bezier(0.22, 0.61, 0.36, 1) forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            color: var(--primary);
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }

        .form-section {
            margin: 2.5rem 0;
            opacity: 0;
            transform: translateY(20px);
            animation: formEntrance 0.6s ease-out forwards;
            animation-delay: 0.3s;
        }

        @keyframes formEntrance {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group input,
        .input-group textarea,
        .input-group select {
            width: 100%;
            padding: 1.2rem;
            border: none;
            border-radius: 15px;
            background: var(--background);
            box-shadow: inset 5px 5px 10px #d9d9d9,
                        inset -5px -5px 10px #ffffff;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .input-group input:focus,
        .input-group textarea:focus {
            box-shadow: inset 2px 2px 5px #d9d9d9,
                        inset -2px -2px 5px #ffffff;
            outline: none;
        }

        .input-group label {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text);
            pointer-events: none;
            transition: all 0.3s ease;
            background: var(--background);
            padding: 0 0.5rem;
        }

        .input-group input:focus ~ label,
        .input-group input:not(:placeholder-shown) ~ label,
        .input-group textarea:focus ~ label,
        .input-group textarea:not(:placeholder-shown) ~ label {
            top: 0;
            font-size: 0.9rem;
            color: var(--primary);
        }

        .file-input {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            background: var(--background);
            box-shadow: var(--neumorphic-shadow);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .file-input:hover {
            transform: translateY(-3px);
        }

        .file-input input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-input label {
            display: block;
            padding: 1.5rem;
            text-align: center;
            color: var(--primary);
        }

        .preview-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: var(--neumorphic-shadow);
            margin: 1rem auto;
            display: none;
        }

        .btn-submit {
            display: block;
            width: 100%;
            padding: 1.2rem;
            border: none;
            border-radius: 15px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 5px 5px 10px #d9d9d9,
                       -5px -5px 10px #ffffff;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 8px 8px 15px #d9d9d9,
                       -8px -8px 15px #ffffff;
            letter-spacing: 1px;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
            position: relative;
        }

        .progress-step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .progress-step.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            transform: scale(1.1);
        }

        .progress-bar {
            position: absolute;
            top: 50%;
            left: 0;
            height: 4px;
            background: #e0e0e0;
            width: 100%;
            transform: translateY(-50%);
            z-index: 1;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            width: 0;
            transition: width 0.5s ease;
        }

        @media (max-width: 768px) {
            .container {
                padding: 2rem;
                margin: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        .degrees-list {
    display: grid;
    gap: 1rem;
}

.degree-item {
    padding: 1rem;
    border-radius: 0.5rem;
    background: rgba(42, 157, 143, 0.05);
}

.degree-name {
    font-weight: 600;
    color: var(--secondary);
}

.degree-details {
    display: flex;
    justify-content: space-between;
    margin-top: 0.5rem;
    color: var(--text-light);
}

.btn-add-degree {
    background: var(--primary);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    cursor: pointer;
    margin-top: 1rem;
}

.degree-entry {
    display: grid;
    grid-template-columns: 1fr 1fr 100px auto;
    gap: 1rem;
    margin-bottom: 1rem;
    align-items: center;
}

.btn-remove {
    background: transparent;
    border: none;
    color: var(--accent);
    cursor: pointer;
    padding: 0.5rem;
}
        .buttons-container {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.btn-back {
    padding: 1.2rem;
    border: none;
    border-radius: 15px;
    background: var(--background);
    color: var(--primary);
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 5px 5px 10px #d9d9d9,
                -5px -5px 10px #ffffff;
    flex: 1;
}

.btn-back:hover {
    transform: translateY(-2px);
    box-shadow: 8px 8px 15px #d9d9d9,
                -8px -8px 15px #ffffff;
}

.btn-submit {
    flex: 2;
}
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-user-md"></i> Doctor Registration</h2>
        
        <div class="progress-steps">
            <div class="progress-bar"><div class="progress-fill"></div></div>
            <div class="progress-step active">1</div>
            <div class="progress-step">2</div>
            <div class="progress-step">3</div>
        </div>

        <form action="registration.php" method="post" enctype="multipart/form-data" class="animated-form">
            <input type="hidden" name="role" value="doctor">

            <!-- Section 1: Basic Information -->
            <div class="form-section" data-step="1" style="display: block;">
                <div class="form-grid">
                    <div class="input-group">
                        <input type="text" id="username" name="username" required placeholder=" ">
                        <label for="username"><i class="fas fa-user"></i> Username</label>
                    </div>
                    <div class="input-group">
                        <input type="email" id="email" name="email" required placeholder=" ">
                        <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    </div>
                    <div class="input-group">
                        <input type="password" id="password" name="password" required placeholder=" ">
                        <label for="password"><i class="fas fa-lock"></i> Password</label>
                    </div>
                    <div class="input-group">
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder=" ">
                        <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                    </div>
                </div>
            </div>

            <!-- Section 2: Professional Information -->
            <div class="form-section" data-step="2" style="display: none;">
                <div class="form-grid">
                    <div class="input-group">
                        <input type="text" id="specialty" name="specialty" required placeholder=" ">
                        <label for="specialty"><i class="fas fa-stethoscope"></i> Specialty</label>
                    </div>
                    <div class="input-group">
                        <input type="text" id="medical_license_number" name="medical_license_number" required placeholder=" ">
                        <label for="medical_license_number"><i class="fas fa-id-card"></i> License Number</label>
                    </div>
                    <div class="input-group">
                        <input type="number" id="years_of_experience" name="years_of_experience" required placeholder=" ">
                        <label for="years_of_experience"><i class="fas fa-briefcase"></i> Experience (Years)</label>
                    </div>
                    <div class="input-group">
                        <input type="text" id="languages_spoken" name="languages_spoken" required placeholder=" ">
                        <label for="languages_spoken"><i class="fas fa-language"></i> Languages</label>
                    </div>
                    <div class="input-group">
    <div id="degrees-container">
        <div class="degree-entry">
            <input type="text" name="degree_name[]" placeholder="Degree Name" required>
            <input type="text" name="institution[]" placeholder="Institution" required>
            <input type="number" name="passing_year[]" placeholder="Passing Year" min="1900" max="<?= date('Y') ?>" required>
        </div>
    </div>
    <button type="button" onclick="addDegreeField()" class="btn-add-degree">
        <i class="fas fa-plus"></i> Add Another Degree
    </button>
</div>
                </div>
            </div>

            <!-- Section 3: Additional Information -->
            <div class="form-section" data-step="3" style="display: none;">
                <div class="form-grid">
                    <div class="input-group">
                        <input type="tel" id="phone" name="phone" required placeholder=" ">
                        <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                    </div>
                    <div class="input-group">
                        <input type="text" id="work_address" name="work_address" required placeholder=" ">
                        <label for="work_address"><i class="fas fa-hospital"></i> Work Address</label>
                    </div>
                    <div class="input-group">
                        <textarea id="available_consultation_hours" name="available_consultation_hours" required placeholder=" "></textarea>
                        <label for="available_consultation_hours"><i class="fas fa-clock"></i> Consultation Hours</label>
                    </div>
                    <div class="input-group">
                        <textarea id="professional_biography" name="professional_biography" required placeholder=" "></textarea>
                        <label for="professional_biography"><i class="fas fa-file-medical"></i> Professional Bio</label>
                    </div>
                    <div class="file-input">
                        <input type="file" id="profile_image" name="profile_image" accept="image/*">
                        <label for="profile_image">
                            <i class="fas fa-camera"></i> Upload Profile Photo
                        </label>
                        <img src="#" class="preview-image" alt="Profile Preview">
                    </div>
                </div>
            </div>

            <div class="buttons-container">
                <button type="button" class="btn-back" onclick="previousStep()" style="display: none;">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button type="button" class="btn-submit" onclick="nextStep()">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 3;

        function nextStep() {
            if (currentStep < totalSteps) {
                document.querySelector(`[data-step="${currentStep}"]`).style.display = 'none';
                currentStep++;
                document.querySelector(`[data-step="${currentStep}"]`).style.display = 'block';
                updateProgress();
                updateButtons();
            } else {
                document.querySelector('form').submit();
            }
        }

        function updateProgress() {
            const progressSteps = document.querySelectorAll('.progress-step');
            const progressFill = document.querySelector('.progress-fill');
            
            progressSteps.forEach((step, index) => {
                if (index < currentStep) step.classList.add('active');
                else step.classList.remove('active');
            });
            
            progressFill.style.width = `${((currentStep - 1) / (totalSteps - 1)) * 100}%`;
        }

        // Image preview functionality
        const fileInput = document.querySelector('input[type="file"]');
        const previewImage = document.querySelector('.preview-image');

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImage.style.display = 'block';
                previewImage.src = e.target.result;
            }
            
            reader.readAsDataURL(file);
        });
        function addDegreeField() {
    const container = document.getElementById('degrees-container');
    const newEntry = document.createElement('div');
    newEntry.className = 'degree-entry';
    newEntry.innerHTML = `
        <input type="text" name="degree_name[]" placeholder="Degree Name" required>
        <input type="text" name="institution[]" placeholder="Institution" required>
        <input type="number" name="passing_year[]" placeholder="Passing Year" min="1900" max="<?= date('Y') ?>" required>
        <button type="button" onclick="this.parentElement.remove()" class="btn-remove">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(newEntry);
}
        function previousStep() {
    if (currentStep > 1) {
        document.querySelector(`[data-step="${currentStep}"]`).style.display = 'none';
        currentStep--;
        document.querySelector(`[data-step="${currentStep}"]`).style.display = 'block';
        updateProgress();
        updateButtons();
    }
}

function updateButtons() {
    const backButton = document.querySelector('.btn-back');
    const nextButton = document.querySelector('.btn-submit');
    
    backButton.style.display = currentStep === 1 ? 'none' : 'block';
    nextButton.textContent = currentStep === totalSteps ? 'Submit' : 'Next';
    
    if (currentStep === totalSteps) {
        nextButton.innerHTML = 'Submit <i class="fas fa-check"></i>';
    } else {
        nextButton.innerHTML = 'Next <i class="fas fa-arrow-right"></i>';
    }
}
    </script>
</body>
</html>