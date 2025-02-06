<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Health Content & Tips</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <!-- Font Awesome for Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-p1rX2uZ6U3X9Z7vZx8L1V3i2QvY+5Yj1m8Y6s1XH3u5vJ3aT5X6m7l3rK3j3w9nW6J3M4F5k9iRjX6q5J1X5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    /* Reset and Base Styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: 'Roboto', sans-serif;
      background: #f5f7fa;
      color: #333;
      line-height: 1.6;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    header {
      background: #4CAF50;
      color: #fff;
      padding: 20px 0;
      text-align: center;
    }
    main {
      flex: 1;
      padding: 20px;
      max-width: 1200px;
      margin: 0 auto;
      width: 100%;
    }
    .container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
    }
    .card {
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      padding: 20px;
      transition: transform 0.3s, box-shadow 0.3s;
      position: relative;
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    }
    .card h2 {
      color: #2A9D8F;
      margin-bottom: 10px;
    }
    .card p {
      color: #264653;
      font-size: 1em;
    }
    .back-button {
      display: inline-block;
      margin: 20px auto;
      padding: 10px 20px;
      background: #E76F51;
      color: #fff;
      border: none;
      border-radius: 5px;
      text-decoration: none;
      cursor: pointer;
      transition: background 0.3s;
    }
    .back-button:hover {
      background: #D65F34;
    }
    /* AI Assistant Styles */
    #ai-assistant {
      position: fixed;
      bottom: 20px;
      right: 20px;
      width: 60px;
      height: 60px;
      background: #4CAF50;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
      transition: background 0.3s;
    }
    #ai-assistant:hover {
      background: #45a049;
    }
    #ai-assistant i {
      color: #fff;
      font-size: 24px;
    }
    /* Modal Styles */
    .modal {
      display: none; /* Hidden by default */
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.5);
      padding: 20px;
    }
    .modal-content {
      background: #fff;
      margin: 10% auto;
      padding: 20px;
      border-radius: 10px;
      width: 80%;
      max-width: 600px;
      position: relative;
      animation: fadeIn 0.3s;
    }
    .close {
      position: absolute;
      top: 15px;
      right: 20px;
      color: #333;
      font-size: 24px;
      font-weight: bold;
      cursor: pointer;
    }
    @keyframes fadeIn {
      from {opacity: 0; transform: scale(0.9);}
      to {opacity: 1; transform: scale(1);}
    }
  </style>
</head>
<body>
  <header>
    <h1>Health Content & Tips</h1>
  </header>
  
  <main>
    <div class="container">
      <div class="card">
        <h2>Tip 1: Eat a Balanced Diet</h2>
        <p>Eating a balanced diet is essential for maintaining good health. Include a variety of fruits, vegetables, proteins, and whole grains in your meals.</p>
      </div>
      <div class="card">
        <h2>Tip 2: Regular Exercise</h2>
        <p>Engage in regular physical activity to keep your body fit and reduce the risk of chronic diseases. Even a 30-minute walk daily can make a big difference.</p>
      </div>
      <div class="card">
        <h2>Tip 3: Stay Hydrated</h2>
        <p>Drinking enough water throughout the day is crucial for overall health. Aim to drink at least 8 glasses of water daily.</p>
      </div>
      <!-- Add more tips as needed -->
    </div>
  </main>
  
  <!-- AI Assistant Button -->
  <div id="ai-assistant">
    <i class="fas fa-robot"></i>
  </div>
  
  <!-- AI Assistant Modal -->
  <div id="ai-modal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>AI Health Assistant</h2>
      <div id="ai-chat">
        <div id="ai-messages" style="max-height: 300px; overflow-y: auto; margin-bottom: 10px;">
          <!-- AI messages will appear here -->
        </div>
        <input type="text" id="user-input" placeholder="Type your message..." style="width: 80%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
        <button id="send-button" style="padding: 10px 15px; border: none; background: #4CAF50; color: #fff; border-radius: 5px; cursor: pointer;">Send</button>
      </div>
    </div>
  </div>
  
  <!-- Back to Home Button -->
  <a class="back-button" href="index.php">Back to Home</a>
  
  <!-- JavaScript for AI Assistant -->
  <script>
    const aiAssistant = document.getElementById('ai-assistant');
    const aiModal = document.getElementById('ai-modal');
    const closeModal = document.getElementsByClassName('close')[0];
    const sendButton = document.getElementById('send-button');
    const userInput = document.getElementById('user-input');
    const aiMessages = document.getElementById('ai-messages');

    // Function to send message to AI and get response
    async function sendMessage(message) {
      // Display user message
      const userMessageElement = document.createElement('div');
      userMessageElement.innerHTML = `<strong>You:</strong> ${message}`;
      aiMessages.appendChild(userMessageElement);
      userInput.value = '';

      // Call Google Gemini API
      // Note: You need to set up a backend to handle API requests securely
      // For demonstration, we'll mock the AI response
      // Replace the following with actual API call
      const aiResponse = await mockAIResponse(message);

      // Display AI response
      const aiMessageElement = document.createElement('div');
      aiMessageElement.innerHTML = `<strong>AI Assistant:</strong> ${aiResponse}`;
      aiMessages.appendChild(aiMessageElement);
      aiMessages.scrollTop = aiMessages.scrollHeight;
    }

    // Mock function to simulate AI response
    function mockAIResponse(message) {
      // Simple keyword-based response
      const lowerMessage = message.toLowerCase();
      if (lowerMessage.includes('diet')) {
        return 'A balanced diet includes fruits, vegetables, proteins, and whole grains.';
      } else if (lowerMessage.includes('exercise')) {
        return 'Regular exercise helps maintain physical fitness and reduces the risk of diseases.';
      } else if (lowerMessage.includes('water')) {
        return 'Staying hydrated is essential for overall health. Drink at least 8 glasses of water daily.';
      } else {
        return 'I can help with health tips. How can I assist you today?';
      }
    }

    // Event listener for send button
    sendButton.addEventListener('click', () => {
      const message = userInput.value.trim();
      if (message !== '') {
        sendMessage(message);
      }
    });

    // Event listener for Enter key
    userInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        const message = userInput.value.trim();
        if (message !== '') {
          sendMessage(message);
        }
      }
    });

    // Open AI Assistant Modal
    aiAssistant.addEventListener('click', () => {
      aiModal.style.display = 'block';
    });

    // Close AI Assistant Modal
    closeModal.addEventListener('click', () => {
      aiModal.style.display = 'none';
    });

    // Close modal when clicking outside the modal content
    window.addEventListener('click', (e) => {
      if (e.target == aiModal) {
        aiModal.style.display = 'none';
      }
    });
  </script>
</body>
</html>