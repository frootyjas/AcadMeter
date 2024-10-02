function showSignUpForm(type) {
    console.log('Form type:', type);
    if (type === 'admin') {
        document.getElementById('adminFormContainer').style.display = 'block';
        document.getElementById('studentFormContainer').style.display = 'none';
        document.getElementById('instructorFormContainer').style.display = 'none';
    } else if (type === 'student') {
        document.getElementById('adminFormContainer').style.display = 'none';
        document.getElementById('studentFormContainer').style.display = 'block';
        document.getElementById('instructorFormContainer').style.display = 'none';
    } else if (type === 'instructor') {
        document.getElementById('adminFormContainer').style.display = 'none';
        document.getElementById('studentFormContainer').style.display = 'none';
        document.getElementById('instructorFormContainer').style.display = 'block';
    }
}


function hideSignupForm() {
    document.getElementById('adminFormContainer').style.display = 'none';
    document.getElementById('studentFormContainer').style.display = 'none';
    document.getElementById('instructorFormContainer').style.display = 'none';
}
