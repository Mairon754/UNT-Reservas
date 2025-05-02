document.addEventListener('DOMContentLoaded', function() {
    const calendarGrid = document.getElementById('calendar-grid');
    
    // Función para generar el calendario
    function generateCalendar(month, year) {
        // Limpiar el grid actual
        while (calendarGrid.children.length > 7) {
            calendarGrid.removeChild(calendarGrid.lastChild);
        }
        
        // Determinar el primer día del mes (0 = Domingo, 1 = Lunes, ...)
        const firstDay = new Date(year, month - 1, 1);
        const firstDayIndex = firstDay.getDay(); // 0 para domingo, 1 para lunes, etc.
        
        // Último día del mes
        const lastDay = new Date(year, month, 0);
        const daysInMonth = lastDay.getDate();
        
        // Último día del mes anterior
        const prevMonthLastDay = new Date(year, month - 1, 0);
        const prevMonthDays = prevMonthLastDay.getDate();
        
        // Crear los días del mes anterior
        for (let i = firstDayIndex; i > 0; i--) {
            const day = document.createElement('div');
            day.classList.add('calendar-day', 'other-month');
            day.innerHTML = `
                <div class="calendar-day-number">${prevMonthDays - i + 1}</div>
                <div class="reservation-events"></div>
            `;
            calendarGrid.appendChild(day);
        }
        
        // Crear los días del mes actual
        const today = new Date();
        for (let i = 1; i <= daysInMonth; i++) {
            const currentDate = new Date(year, month - 1, i);
            const formattedDate = formatDate(currentDate);
            
            const day = document.createElement('div');
            day.classList.add('calendar-day');
            
            // Marcar el día actual
            if (i === today.getDate() && month === today.getMonth() + 1 && year === today.getFullYear()) {
                day.classList.add('today');
            }
            
            day.setAttribute('data-date', formattedDate);
            day.innerHTML = `
                <div class="calendar-day-number">${i}</div>
                <div class="reservation-events"></div>
            `;
            
            // Agregar evento click para seleccionar día
            day.addEventListener('click', function() {
                document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('fecha_inicio').value = formattedDate;
                document.getElementById('fecha_fin').value = formattedDate;
            });
            
            calendarGrid.appendChild(day);
        }
        
        // Agregar días del próximo mes para completar la última semana
        const totalDaysShown = calendarGrid.children.length - 7; // Resta los encabezados
        const remainingCells = 42 - totalDaysShown; // 6 filas de 7 días
        
        for (let i = 1; i <= remainingCells; i++) {
            const day = document.createElement('div');
            day.classList.add('calendar-day', 'other-month');
            day.innerHTML = `
                <div class="calendar-day-number">${i}</div>
                <div class="reservation-events"></div>
            `;
            calendarGrid.appendChild(day);
        }
        
        // Mostrar reservas en el calendario
        displayReservations();
    }
    
    // Función para mostrar las reservas en el calendario
    function displayReservations() {
        if (!reservations || !Array.isArray(reservations)) {
            console.error('Las reservas no están disponibles o no son un array');
            return;
        }
        
        reservations.forEach(reservation => {
            const reservationDate = reservation.reservation_date;
            const day = document.querySelector(`[data-date="${reservationDate}"]`);
            
            if (day) {
                const eventDiv = document.createElement('div');
                eventDiv.classList.add('reservation-event');
                
                // Formatear la hora para mostrar
                const time = reservation.reservation_time.substring(0, 5); // Obtener solo HH:MM
                
                eventDiv.innerHTML = `${time} ${reservation.resource_name}`;
                eventDiv.setAttribute('title', `${reservation.responsible_person} - ${reservation.resource_name}`);
                
                // Añadir evento para mostrar más detalles al hacer clic
                eventDiv.addEventListener('click', function(e) {
                    e.stopPropagation();
                    alert(`Reserva: ${reservation.resource_name}\nResponsable: ${reservation.responsible_person}\nFecha: ${reservationDate}\nHora: ${time}`);
                });
                
                day.querySelector('.reservation-events').appendChild(eventDiv);
            }
        });
    }
    
    // Formato de fecha YYYY-MM-DD
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // Inicializar variables globales
    let currentMonthVar = currentMonth;
    let currentYearVar = currentYear;
    
    // Generar el calendario inicial
    generateCalendar(currentMonthVar, currentYearVar);
    
    // Navegación del calendario
    document.getElementById('prev-month').addEventListener('click', function() {
        currentMonthVar--;
        if (currentMonthVar < 1) {
            currentMonthVar = 12;
            currentYearVar--;
        }
        window.location.href = `reservas.php?month=${currentMonthVar}&year=${currentYearVar}`;
    });
    
    document.getElementById('next-month').addEventListener('click', function() {
        currentMonthVar++;
        if (currentMonthVar > 12) {
            currentMonthVar = 1;
            currentYearVar++;
        }
        window.location.href = `reservas.php?month=${currentMonthVar}&year=${currentYearVar}`;
    });
    
    document.getElementById('today').addEventListener('click', function() {
        const today = new Date();
        window.location.href = `reservas.php?month=${today.getMonth() + 1}&year=${today.getFullYear()}`;
    });
    
    // Cambiar vista (mes, semana, día)
    document.getElementById('month-view').addEventListener('click', function() {
        document.querySelectorAll('.calendar-view-options button').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        // Implementar cambio de vista
    });
    
    document.getElementById('week-view').addEventListener('click', function() {
        document.querySelectorAll('.calendar-view-options button').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        // Implementar cambio de vista
    });
    
    document.getElementById('day-view').addEventListener('click', function() {
        document.querySelectorAll('.calendar-view-options button').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        // Implementar cambio de vista
    });
    
    // Establecer valores por defecto en el formulario
    document.getElementById('fecha_inicio').valueAsDate = new Date();
    document.getElementById('fecha_fin').valueAsDate = new Date();
    
    // Mostrar mensaje de error si existe en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const errorMsg = urlParams.get('error');
    if (errorMsg) {
        const errorModal = document.getElementById('error-modal');
        const errorMessage = document.getElementById('error-message');
        errorMessage.textContent = decodeURIComponent(errorMsg);
        errorModal.style.display = "block";
        
        // Cerrar modal
        document.querySelector('.close').addEventListener('click', function() {
            errorModal.style.display = "none";
        });
        
        // Cerrar modal al hacer clic fuera
        window.addEventListener('click', function(event) {
            if (event.target == errorModal) {
                errorModal.style.display = "none";
            }
        });
    }
});