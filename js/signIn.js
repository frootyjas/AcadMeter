function handleSubmit(event) {
    event.preventDefault();  // Prevent the default form submission
    console.log('Form submission triggered');  // Test

    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const userType = document.querySelector('select[name="userType"]').value;

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
    .then(response => response.json()) // Ensure response is JSON
    .then(data => {
        if (data.status === 'success') {
            // Redirect based on user type
            if (userType === 'Admin') {
                window.location.href = '../PHP/admin_dashboard.php';  // Redirect to Admin Dashboard
            } else if (userType === 'Instructor') {
                window.location.href = 'instructor_dashboard.php';  // Redirect to Instructor Dashboard
            } else {
                window.location.href = 'student_dashboard.php';  // Redirect to Student Dashboard
            }
        } else {
            alert(data.message);  // Display an error message if login failed
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Something went wrong. Please try again.");
    });
}
