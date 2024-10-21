function handleSubmit(event) {
    event.preventDefault();  // Prevent the default form submission
    console.log('Form submission triggered');  // Debugging log

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    let userType = document.querySelector('select[name="userType"]').value.trim();

    // Convert userType to lowercase to match the server's expectations
    userType = userType.toLowerCase();

    // Log the form data for debugging
    console.log('Username:', username);
    console.log('Password:', password);
    console.log('User Type:', userType);

    // Ensure a user type is selected
    if (!userType) {
        alert("Please select a user type.");
        return;
    }

    const formData = new FormData();
    formData.append('username', username);
    formData.append('password', password);
    formData.append('userType', userType);

    // Send data to the login_process.php using fetch
    fetch('../PHP/login_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response received:', response);
        return response.json();
    })
    .then(data => {
        console.log('Parsed Data:', data);
        if (data.status === 'success') {
            // Use the redirect URL provided by the server
            window.location.href = data.redirect;
        } else {
            alert(data.message);  // Display an error message if login failed
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Something went wrong. Please try again.");
    });
}

// Function to handle forgot password submission
function handleForgotPassword(event) {
    event.preventDefault();  // Prevent default form submission
    console.log('Forgot Password triggered');  // Debugging log

    const email = document.getElementById('resetEmail').value.trim();

    if (!email) {
        alert("Please enter your email address.");
        return;
    }

    const formData = new FormData();
    formData.append('resetEmail', email);

    // Send the forgot password request to the server
    fetch('../PHP/forgot_password_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Forgot password response:', response);
        return response.json();
    })
    .then(data => {
        const messageContainer = document.getElementById('forgotPasswordMessage');
        if (data.status === 'success') {
            messageContainer.style.color = 'green';
            messageContainer.textContent = "Reset link sent successfully. Please check your email.";
        } else {
            messageContainer.style.color = 'red';
            messageContainer.textContent = data.message;
        }
    })
    .catch(error => {
        console.error('Error during forgot password:', error);
        const messageContainer = document.getElementById('forgotPasswordMessage');
        messageContainer.style.color = 'red';
        messageContainer.textContent = "Something went wrong. Please try again.";
    });
}
