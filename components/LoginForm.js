// LoginForm.js: Handles login form functionality

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    
    loginForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        // Perform validation
        if (username.trim() === '' || password.trim() === '') {
            alert('Por favor, complete todos los campos');
            return;
        }

        // Send login data via fetch to the backend
        fetch('login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                username: username,
                password: password,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'index.html'; // Redirect to dashboard
            } else {
                alert('Usuario o contraseña incorrectos');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Hubo un error al intentar iniciar sesión');
        });
    });
});
