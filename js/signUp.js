function showSignUpForm(type) {
    console.log('Form type:', type);
    hideSignupForm(); 
    if (type === 'admin') {
        document.getElementById('adminFormContainer').style.display = 'block';
    } else if (type === 'student') {
        document.getElementById('studentFormContainer').style.display = 'block';
    } else if (type === 'instructor') {
        document.getElementById('instructorFormContainer').style.display = 'block';
    }
}

function hideSignupForm() {
    document.getElementById('adminFormContainer').style.display = 'none';
    document.getElementById('studentFormContainer').style.display = 'none';
    document.getElementById('instructorFormContainer').style.display = 'none';
    
    clearErrorMessage('adminError');
    clearErrorMessage('instructorError');
    clearErrorMessage('studentError');
}

document.addEventListener('DOMContentLoaded', hideSignupForm);

// Event listeners to handle form submission with fetch
document.getElementById('adminSignUpForm').addEventListener('submit', handleFormSubmission);
document.getElementById('studentSignUpForm').addEventListener('submit', handleFormSubmission);
document.getElementById('instructorSignUpForm').addEventListener('submit', handleFormSubmission);

function handleFormSubmission(event) {
    event.preventDefault(); 

    const form = event.target; 
    const formData = new FormData(form);
    let errorContainerId;

    if (form.id === 'adminSignUpForm') {
        errorContainerId = 'adminError';
    } else if (form.id === 'studentSignUpForm') {
        errorContainerId = 'studentError';
    } else if (form.id === 'instructorSignUpForm') {
        errorContainerId = 'instructorError';
    }

    fetch(form.action, {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'error') {
            displayErrorMessage(errorContainerId, data.message);
        } else if (data.status === 'success') {
            // Updated line to use data.redirect
            window.location.href = data.redirect;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        displayErrorMessage(errorContainerId, 'An error occurred. Please try again.');
    });
}

function displayErrorMessage(containerId, message) {
    const errorContainer = document.getElementById(containerId);
    if (errorContainer) {
        errorContainer.textContent = message;
        errorContainer.style.color = 'red';
    }
}

function clearErrorMessage(containerId) {
    const errorContainer = document.getElementById(containerId);
    if (errorContainer) {
        errorContainer.textContent = ''; 
    }
}
