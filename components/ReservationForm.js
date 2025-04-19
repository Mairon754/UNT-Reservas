// ReservationForm.js: Handles resource reservation functionality

document.addEventListener('DOMContentLoaded', function() {
    const reservationForm = document.getElementById('reservation-form');

    reservationForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const resourceId = document.getElementById('resource-id').value;
        const responsiblePerson = document.getElementById('responsible-person').value;
        const date = document.getElementById('reservation-date').value;
        const time = document.getElementById('reservation-time').value;

        // Perform validation
        if (resourceId.trim() === '' || responsiblePerson.trim() === '' || date.trim() === '' || time.trim() === '') {
            alert('Por favor, complete todos los campos');
            return;
        }

        // Send reservation data via fetch to the backend
        fetch('server/task/createReservation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                resourceId: resourceId,
                responsiblePerson: responsiblePerson,
                date: date,
                time: time,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reserva realizada con éxito');
                window.location.reload(); // Refresh the page to show updated reservations
            } else {
                alert('Error al realizar la reserva');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Hubo un error al intentar hacer la reserva');
        });
    });
});
