<?php
// patient_register.php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Registration - MediLinx</title>
<link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root {
  --primary: #2A9D8F;
  --secondary: #264653;
  --accent: #E76F51;
  --glass: rgba(255, 255, 255, 0.95);
  --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  --text: #1e293b;
  --gradient-1: linear-gradient(135deg, #2A9D8F 0%, #264653 100%);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Roboto', sans-serif;
}

@keyframes gradientFlow {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

body {
  min-height: 100vh;
  background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 50%, #e0f2f1 100%);
  background-size: 200% 200%;
  animation: gradientFlow 15s ease infinite;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

.form-container {
  background: var(--glass);
  padding: 3rem;
  border-radius: 2rem;
  box-shadow: var(--shadow);
  width: 100%;
  max-width: 800px;
  position: relative;
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  animation: slideIn 0.8s cubic-bezier(0.17, 0.84, 0.44, 1);
}

@keyframes slideIn {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

.form-header {
  text-align: center;
  margin-bottom: 2.5rem;
}

.form-header h1 {
  font-family: 'Lato', sans-serif;
  font-size: 2.5rem;
  color: var(--secondary);
  margin-bottom: 1rem;
  background: var(--gradient-1);
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.form-header p {
  color: #64748b;
  font-size: 1.1rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.form-group {
  position: relative;
  margin-bottom: 1.2rem;
}

.input-container {
  background: rgba(241, 245, 249, 0.3);
  border-radius: 1rem;
  border: 2px solid transparent;
  transition: all 0.3s ease;
}

.input-container:focus-within {
  border-color: var(--primary);
  box-shadow: 0 4px 6px rgba(42, 157, 143, 0.1);
}

.form-input {
  width: 100%;
  padding: 1rem;
  border: none;
  background: transparent;
  font-size: 1rem;
  color: var(--text);
}

.form-input:focus {
  outline: none;
}

.floating-label {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
  pointer-events: none;
  transition: all 0.3s ease;
  background: var(--glass);
  padding: 0 0.5rem;
}

.form-input:focus ~ .floating-label,
.form-input:not(:placeholder-shown) ~ .floating-label {
  top: 0;
  font-size: 0.85rem;
  color: var(--primary);
}

select.form-input {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") no-repeat right 1rem center/1em;
}

.file-upload {
  border: 2px dashed #cbd5e1;
  border-radius: 1rem;
  padding: 1.5rem;
  text-align: center;
  margin: 1.5rem 0;
  transition: all 0.3s ease;
}

.file-upload:hover {
  border-color: var(--primary);
  background: rgba(42, 157, 143, 0.05);
}

.upload-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  cursor: pointer;
}

.upload-icon {
  width: 2.5rem;
  height: 2.5rem;
  background: var(--gradient-1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.submit-btn {
  width: 100%;
  padding: 1.2rem;
  background: var(--gradient-1);
  color: white;
  border: none;
  border-radius: 1rem;
  font-size: 1.1rem;
  cursor: pointer;
  transition: transform 0.3s ease;
  font-family: 'Lato', sans-serif;
}

.submit-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(42, 157, 143, 0.2);
}

.login-link {
  text-align: center;
  margin-top: 1.5rem;
}

.login-link a {
  color: var(--primary);
  text-decoration: none;
  font-weight: 600;
}

.login-link a:hover {
  text-decoration: underline;
}

@media (max-width: 768px) {
  .form-container {
    padding: 2rem;
    border-radius: 1.5rem;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .form-header h1 {
    font-size: 2rem;
  }
}

@media (max-width: 480px) {
  body {
    padding: 15px;
  }
  
  .form-container {
    padding: 1.5rem;
    border-radius: 1rem;
  }
  
  .submit-btn {
    padding: 1rem;
    font-size: 1rem;
  }
}
</style>
</head>
<body>
<div class="form-container">
  <div class="form-header">
    <h1>Create Patient Account</h1>
    <p>Join MediLinx for seamless healthcare management</p>
  </div>
  
  <form action="registration.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="role" value="patient">
    
    <div class="form-grid">
      <div class="form-group">
        <div class="input-container">
          <input type="text" name="username" class="form-input" placeholder=" " required>
          <span class="floating-label">Username</span>
        </div>
      </div>
      
      <div class="form-group">
        <div class="input-container">
          <input type="email" name="email" class="form-input" placeholder=" " required>
          <span class="floating-label">Email</span>
        </div>
      </div>
      
      <div class="form-group">
        <div class="input-container">
          <input type="password" name="password" class="form-input" placeholder=" " required>
          <span class="floating-label">Password</span>
        </div>
      </div>
      
      <div class="form-group">
        <div class="input-container">
          <input type="password" name="confirm_password" class="form-input" placeholder=" " required>
          <span class="floating-label">Confirm Password</span>
        </div>
      </div>
      
      <div class="form-group">
        <div class="input-container">
          <input type="tel" name="phone" class="form-input" placeholder=" ">
          <span class="floating-label">Phone Number (Optional)</span>
        </div>
      </div>
      
      <div class="form-group">
        <div class="input-container">
          <input type="date" name="date_of_birth" class="form-input" placeholder=" " required>
          <span class="floating-label">Date of Birth</span>
        </div>
      </div>
      
      <div class="form-group">
        <div class="input-container">
          <select name="gender" class="form-input" required>
            <option value="" disabled selected>Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
          </select>
          <span class="floating-label">Gender</span>
        </div>
      </div>
      
      <div class="form-group">
        <div class="input-container">
          <textarea name="medical_history" class="form-input" placeholder=" " rows="3"></textarea>
          <span class="floating-label">Medical History (Optional)</span>
        </div>
      </div>
    </div>

    <div class="file-upload">
      <label class="upload-label">
        <div class="upload-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
          </svg>
        </div>
        <span>Upload Profile Picture (Optional)</span>
        <input type="file" name="profile_image" accept="image/*" style="display: none;">
      </label>
    </div>

    <button type="submit" class="submit-btn">Create Account</button>
  </form>

  <div class="login-link">
    <a href="login.php">Already have an account? Log in</a>
  </div>
</div>
</body>
</html>