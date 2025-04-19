// script.js: General utility functions shared across the app

// Function to format date in a user-friendly way
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const date = new Date(dateString);
    return date.toLocaleDateString('es-CO', options);
}

// Function to format time
function formatTime(timeString) {
    const options = { hour: '2-digit', minute: '2-digit' };
    const time = new Date('1970-01-01T' + timeString + 'Z');
    return time.toLocaleTimeString('es-CO', options);
}
