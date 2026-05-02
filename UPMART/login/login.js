document.addEventListener('DOMContentLoaded', () => {
    const authForm = document.getElementById('auth-form');
    const nameField = document.getElementById('name-field');
    const passField = document.getElementById('pass-field');
    const email = document.getElementById('email-field');

    if (email) {
        email.addEventListener('blur', () => {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const emailError = document.getElementById('emailError');
            if (!regex.test(email.value)) {
                email.classList.add('invalid-field');
                if (email) emailError.style.display = 'inline';
            } else {
                email.classList.remove('invalid-field');
                if (emailError) emailError.style.display = 'none';
            }
        });
    }

    if (authForm) {
        authForm.addEventListener('submit', (e) => {
            e.preventDefault();
            let isValid = true;
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            // Basic UP Email Validation
            if (!email.endsWith("@up.edu.ph")) {
                alert("Please use your official @up.edu.ph email.");
                return;
            }

            const nameError = document.getElementById('nameError');
            if (nameField.value.length < 3) {
                nameField.classList.add('invalid-field');
                if (nameError) nameError.style.display = 'inline';
                isValid = false;
            } else {
                nameField.classList.remove('invalid-field');
                if (nameError) nameError.style.display = 'none';
            }

            const passError = document.getElementById('passError');
            if (passField.value.length < 8) {
                passField.classList.add('invalid-field');
                if (passError) passError.style.display = 'inline';
                isValid = false;
            } else {
                passField.classList.remove('invalid-field');
                if (passError) passError.style.display = 'none';
            }

            if (isLogin) {
                console.log("Logging in...", { email, password });
                // Redirect to dashboard
                window.location.href = "main.html";
            } else {
                const name = nameField.value;
                const cPass = passField.value;

                if (password !== cPass) {
                    alert("Passwords do not match!");
                    return;
                }
            }
        });
    }
});