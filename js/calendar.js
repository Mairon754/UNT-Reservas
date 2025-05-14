document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let currentMonthVar = window.currentMonth || new Date().getMonth() + 1;
    let currentYearVar = window.currentYear || new Date().getFullYear();
    let currentView = 'month'; // Vista predeterminada: mes
    let selectedDate = new Date(); // Fecha seleccionada actual
    let reservations = []; // Almacenará las reservas cargadas desde la base de datos
    
    // Elementos del DOM
    const calendarGrid = document.getElementById('calendar-grid');
    const calendarTitle = document.querySelector('.calendar-title');
    const prevButton = document.getElementById('prev-month');
    const nextButton = document.getElementById('next-month');
    const todayButton = document.getElementById('today');
    
    // Botones de vista
    const monthViewBtn = document.getElementById('month-view');
    const weekViewBtn = document.getElementById('week-view');
    const dayViewBtn = document.getElementById('day-view');
    
    // Días de la semana en español
    const weekdays = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    const months = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    // Función para generar el calendario según la vista actual
    function renderCalendar() {
        switch(currentView) {
            case 'month':
                renderMonthView();
                break;
            case 'week':
                renderWeekView();
                break;
            case 'day':
                renderDayView();
                break;
            default:
                renderMonthView();
        }
        
        // Actualizar el título del calendario
        updateCalendarTitle();
    }
    
    // Función para actualizar el título del calendario según la vista
    function updateCalendarTitle() {
        switch(currentView) {
            case 'month':
                calendarTitle.textContent = `${months[currentMonthVar-1]} ${currentYearVar}`;
                break;
            case 'week':
                const weekStart = getWeekStartDate(selectedDate);
                const weekEnd = new Date(weekStart);
                weekEnd.setDate(weekStart.getDate() + 6);
                
                if (weekStart.getMonth() === weekEnd.getMonth()) {
                    calendarTitle.textContent = `${weekStart.getDate()} - ${weekEnd.getDate()} de ${months[weekStart.getMonth()]} ${weekStart.getFullYear()}`;
                } else {
                    calendarTitle.textContent = `${weekStart.getDate()} ${months[weekStart.getMonth()]} - ${weekEnd.getDate()} ${months[weekEnd.getMonth()]} ${weekStart.getFullYear()}`;
                }
                break;
            case 'day':
                calendarTitle.textContent = `${selectedDate.getDate()} de ${months[selectedDate.getMonth()]} ${selectedDate.getFullYear()}`;
                break;
        }
    }
    
    // Vista de mes
    function renderMonthView() {
        // Limpiar el grid
        calendarGrid.innerHTML = '';
        calendarGrid.className = 'calendar-grid';
        
        // Agregar encabezados de días
        weekdays.forEach(day => {
            const dayHeader = document.createElement('div');
            dayHeader.className = 'calendar-day-header';
            dayHeader.textContent = day;
            calendarGrid.appendChild(dayHeader);
        });
        
        // Determinar el primer día del mes (0 = Domingo, 1 = Lunes, ...)
        const firstDay = new Date(currentYearVar, currentMonthVar - 1, 1);
        const firstDayIndex = firstDay.getDay(); // 0 para domingo, 1 para lunes, etc.
        
        // Último día del mes
        const lastDay = new Date(currentYearVar, currentMonthVar, 0);
        const daysInMonth = lastDay.getDate();
        
        // Último día del mes anterior
        const prevMonthLastDay = new Date(currentYearVar, currentMonthVar - 1, 0);
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
            const currentDate = new Date(currentYearVar, currentMonthVar - 1, i);
            const formattedDate = formatDate(currentDate);
            
            const day = document.createElement('div');
            day.classList.add('calendar-day');
            
            // Marcar el día actual
            if (i === today.getDate() && currentMonthVar === today.getMonth() + 1 && currentYearVar === today.getFullYear()) {
                day.classList.add('today');
            }
            
            day.setAttribute('data-date', formattedDate);
            
            // Número del día
            const dayNumber = document.createElement('div');
            dayNumber.className = 'calendar-day-number';
            dayNumber.textContent = i;
            day.appendChild(dayNumber);
            
            // Contenedor para eventos
            const eventsContainer = document.createElement('div');
            eventsContainer.className = 'reservation-events';
            day.appendChild(eventsContainer);
            
            // Agregar evento click para seleccionar día
            day.addEventListener('click', function() {
                document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('fecha_inicio').value = formattedDate;
                document.getElementById('fecha_fin').value = formattedDate;
                
                // Actualizar la fecha seleccionada
                selectedDate = new Date(currentYearVar, currentMonthVar - 1, i);
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
    
    // Vista de semana
    function renderWeekView() {
        // Limpiar el grid y aplicar clase especial para vista semanal
        calendarGrid.innerHTML = '';
        calendarGrid.className = 'calendar-grid week-view';
        
        // Obtener el primer día de la semana (domingo)
        const weekStart = getWeekStartDate(selectedDate);
        
        // Agregar encabezados con fecha
        for (let i = 0; i < 7; i++) {
            const date = new Date(weekStart);
            date.setDate(weekStart.getDate() + i);
            
            const dayHeader = document.createElement('div');
            dayHeader.className = 'calendar-day-header';
            dayHeader.innerHTML = `${weekdays[i]}<br>${date.getDate()}/${date.getMonth() + 1}`;
            
            // Marcar el día actual
            if (isToday(date)) {
                dayHeader.classList.add('today');
            }
            
            calendarGrid.appendChild(dayHeader);
        }
        
        // Crear celdas para cada día con horas
        for (let hour = 8; hour <= 20; hour++) {
            // Crear etiqueta de hora
            const hourLabel = document.createElement('div');
            hourLabel.className = 'hour-label';
            hourLabel.textContent = `${hour}:00`;
            calendarGrid.appendChild(hourLabel);
            
            // Crear celdas para cada día de la semana
            for (let i = 0; i < 7; i++) {
                const date = new Date(weekStart);
                date.setDate(weekStart.getDate() + i);
                
                const hourCell = document.createElement('div');
                hourCell.className = 'hour-cell';
                
                // Formatear la fecha para comparar con las reservas
                const dayDate = formatDate(date);
                
                // Buscar reservas para esta hora y día
                const reservationsToCheck = reservations.length > 0 ? reservations : 
                                         (window.reservations && Array.isArray(window.reservations) ? window.reservations : []);
                
                const hourReservations = reservationsToCheck.filter(res => {
                    const resDate = res.reservation_date;
                    const resHour = parseInt(res.reservation_time.split(':')[0]);
                    return resDate === dayDate && resHour === hour;
                });
                
                // Agregar reservas a la celda de hora
                hourReservations.forEach(reservation => {
                    const eventEl = createEventElement(reservation);
                    hourCell.appendChild(eventEl);
                });
                
                calendarGrid.appendChild(hourCell);
            }
        }
    }
    
    // Vista de día
    function renderDayView() {
        // Limpiar el grid y aplicar clase especial para vista diaria
        calendarGrid.innerHTML = '';
        calendarGrid.className = 'calendar-grid day-view';
        
        // Crear encabezado para el día seleccionado
        const dayHeader = document.createElement('div');
        dayHeader.className = 'calendar-day-header full-width';
        dayHeader.textContent = `${weekdays[selectedDate.getDay()]} ${selectedDate.getDate()}/${selectedDate.getMonth() + 1}`;
        
        // Marcar si es hoy
        if (isToday(selectedDate)) {
            dayHeader.classList.add('today');
        }
        
        calendarGrid.appendChild(dayHeader);
        
        // Crear celdas para cada hora (8am - 8pm)
        for (let hour = 8; hour <= 20; hour++) {
            // Celda de hora
            const hourRow = document.createElement('div');
            hourRow.className = 'hour-row';
            
            // Etiqueta de hora
            const hourLabel = document.createElement('div');
            hourLabel.className = 'hour-label';
            hourLabel.textContent = `${hour}:00`;
            hourRow.appendChild(hourLabel);
            
            // Celda para eventos de esa hora
            const hourCell = document.createElement('div');
            hourCell.className = 'hour-content';
            
            // Formatear la fecha para comparar con las reservas
            const dayDate = formatDate(selectedDate);
            
            // Buscar reservas para esta hora y día
            const reservationsToCheck = reservations.length > 0 ? reservations : 
                                     (window.reservations && Array.isArray(window.reservations) ? window.reservations : []);
            
            const hourReservations = reservationsToCheck.filter(res => {
                const resDate = res.reservation_date;
                const resHour = parseInt(res.reservation_time.split(':')[0]);
                return resDate === dayDate && resHour === hour;
            });
            
            // Agregar reservas a la celda de hora
            hourReservations.forEach(reservation => {
                const eventEl = createEventElement(reservation);
                hourCell.appendChild(eventEl);
            });
            
            hourRow.appendChild(hourCell);
            calendarGrid.appendChild(hourRow);
        }
    }
    
    // Función para crear elemento de evento de reserva
    function createEventElement(reservation) {
        const eventEl = document.createElement('div');
        eventEl.className = 'event-item';
        eventEl.setAttribute('data-id', reservation.id);
        
        // Formatear la hora para mostrar
        const time = reservation.reservation_time.substring(0, 5); // Obtener solo HH:MM
        
        // Crear contenido del evento
        const resourceName = reservation.resource_name || 'Recurso';
        
        eventEl.innerHTML = `
            <div class="event-time">${time}</div>
            <div class="event-title">${reservation.responsible_person}</div>
            <div class="event-resource">${resourceName}</div>
        `;
        
        // Agregar título completo para tooltip
        eventEl.setAttribute('title', `${reservation.responsible_person} - ${resourceName}`);
        
        // Agregar doble clic para editar
        eventEl.addEventListener('dblclick', function(e) {
            e.stopPropagation();
            window.location.href = `../server/reservation/editReservation.php?id=${reservation.id}`;
        });
        
        // Añadir evento para mostrar más detalles al hacer clic simple
        eventEl.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Crear un modal para mostrar detalles
            const detailsHtml = `
                <div class="reservation-details-modal">
                    <div class="reservation-details-content">
                        <span class="close-details">&times;</span>
                        <h3>Detalles de Reserva</h3>
                        <p><strong>Recurso:</strong> ${resourceName}</p>
                        <p><strong>Responsable:</strong> ${reservation.responsible_person}</p>
                        <p><strong>Fecha:</strong> ${reservation.reservation_date}</p>
                        <p><strong>Hora:</strong> ${time}</p>
                        ${reservation.observations ? `<p><strong>Observaciones:</strong> ${reservation.observations}</p>` : ''}
                        <div class="modal-buttons">
                            <button class="btn-edit" onclick="window.location.href='../server/reservation/editReservation.php?id=${reservation.id}'">Editar</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Agregar el modal al DOM
            const modalContainer = document.createElement('div');
            modalContainer.innerHTML = detailsHtml;
            document.body.appendChild(modalContainer);
            
            // Mostrar el modal
            const modal = modalContainer.querySelector('.reservation-details-modal');
            modal.style.display = 'block';
            
            // Cerrar el modal al hacer clic en X
            const closeBtn = modal.querySelector('.close-details');
            closeBtn.addEventListener('click', function() {
                document.body.removeChild(modalContainer);
            });
            
            // Cerrar el modal al hacer clic fuera del contenido
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    document.body.removeChild(modalContainer);
                }
            });
        });
        
        return eventEl;
    }
    
    // Función para mostrar las reservas en el calendario (vista mensual)
    function displayReservations() {
        console.log('Mostrando reservas en el calendario...');
        
        // Intentar usar las reservas locales primero, luego las globales si están disponibles
        const reservationsToShow = reservations.length > 0 ? reservations : 
                                (window.reservations && Array.isArray(window.reservations) ? window.reservations : []);
        
        console.log('Reservas a mostrar:', reservationsToShow.length);
        
        if (reservationsToShow.length === 0) {
            console.warn('No hay reservas disponibles para mostrar');
            return;
        }
        
        let reservasVisibles = 0;
        
        reservationsToShow.forEach(reservation => {
            const reservationDate = reservation.reservation_date;
            const day = document.querySelector(`[data-date="${reservationDate}"]`);
            
            if (day) {
                const eventEl = createEventElement(reservation);
                day.querySelector('.reservation-events').appendChild(eventEl);
                reservasVisibles++;
            } else {
                console.log(`No se encontró celda para la fecha ${reservationDate}`);
            }
        });
        
        console.log(`Se mostraron ${reservasVisibles} de ${reservationsToShow.length} reservas en el calendario`);
    }

    // Función para mostrar los detalles de una reserva al hacer clic
function showReservationDetails(reservationId) {
    // Encontrar la reserva en el array de reservas
    const reservation = reservations.find(r => r.id == reservationId);
    if (!reservation) return;
    
    // Comprobar si el usuario actual es el creador de la reserva
    const isOwner = reservation.user_id == currentUserId;
    
    // Crear contenido HTML para el modal
    let detailsHTML = `
        <div>
            <h3>Detalles de la Reserva</h3>
            <p><strong>Recurso:</strong> ${reservation.resource_name || 'No especificado'}</p>
            <p><strong>Responsable:</strong> ${reservation.responsible_person}</p>
            <p><strong>Desde:</strong> ${reservation.reservation_date} ${reservation.reservation_time}</p>
            <p><strong>Hasta:</strong> ${reservation.end_date || reservation.reservation_date} ${reservation.end_time || reservation.reservation_time}</p>
            <p><strong>Observaciones:</strong> ${reservation.observations || 'No hay observaciones'}</p>
            <div class="actions">
    `;
    
    // Añadir botones de acción solo si el usuario es el creador
    if (isOwner) {
        detailsHTML += `
            <a href="../../server/reservation/editReservation.php?id=${reservation.id}" class="btn-edit">Editar</a>
            <button class="btn-delete" onclick="deleteReservation(${reservation.id})">Eliminar</button>
        `;
    } else {
        detailsHTML += `
            <p class="info-message">Solo el creador de esta reserva puede editarla o eliminarla.</p>
        `;
    }
    
    detailsHTML += `
            </div>
        </div>
    `;
    
    // Mostrar el modal con los detalles
    showModal(detailsHTML);
}

// Función para eliminar una reserva (validará en el servidor si el usuario es el creador)
function deleteReservation(reservationId) {
    if (confirm('¿Estás seguro que deseas eliminar esta reserva?')) {
        // Enviar solicitud AJAX para eliminar la reserva
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../../server/reservation/deleteReservation.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert(response.message);
                            // Recargar la página o actualizar el calendario
                            window.location.reload();
                        } else {
                            alert(response.message);
                        }
                    } catch (e) {
                        alert('Error en la respuesta del servidor');
                    }
                } else {
                    alert('Error en la solicitud');
                }
            }
        };
        xhr.send('id=' + reservationId);
    }
}

// Función auxiliar para mostrar un modal
function showModal(content) {
    // Crear el modal si no existe
    let modal = document.getElementById('reservation-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'reservation-modal';
        modal.className = 'modal';
        document.body.appendChild(modal);
    }
    
    // Asignar el contenido y mostrar
    modal.innerHTML = content;
    modal.style.display = 'block';
    
    // Cerrar el modal al hacer clic fuera de él
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };
}
    
    // Función para verificar si una fecha es hoy
    function isToday(date) {
        const today = new Date();
        return date.getDate() === today.getDate() &&
               date.getMonth() === today.getMonth() &&
               date.getFullYear() === today.getFullYear();
    }
    
    // Obtener la fecha de inicio de la semana (domingo) para una fecha dada
    function getWeekStartDate(date) {
        const d = new Date(date);
        const day = d.getDay(); // 0 = domingo, 6 = sábado
        const diff = d.getDate() - day;
        return new Date(d.setDate(diff));
    }
    
    // Formato de fecha YYYY-MM-DD
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // Navegación: mes/semana/día anterior
    function navigatePrevious() {
        switch(currentView) {
            case 'month':
                currentMonthVar--;
                if (currentMonthVar < 1) {
                    currentMonthVar = 12;
                    currentYearVar--;
                }
                break;
            case 'week':
                selectedDate.setDate(selectedDate.getDate() - 7);
                currentMonthVar = selectedDate.getMonth() + 1;
                currentYearVar = selectedDate.getFullYear();
                break;
            case 'day':
                selectedDate.setDate(selectedDate.getDate() - 1);
                currentMonthVar = selectedDate.getMonth() + 1;
                currentYearVar = selectedDate.getFullYear();
                break;
        }
        
        if (currentView === 'month') {
            window.location.href = `reservas.php?month=${currentMonthVar}&year=${currentYearVar}`;
        } else {
            renderCalendar();
        }
    }
    
    // Navegación: mes/semana/día siguiente
    function navigateNext() {
        switch(currentView) {
            case 'month':
                currentMonthVar++;
                if (currentMonthVar > 12) {
                    currentMonthVar = 1;
                    currentYearVar++;
                }
                break;
            case 'week':
                selectedDate.setDate(selectedDate.getDate() + 7);
                currentMonthVar = selectedDate.getMonth() + 1;
                currentYearVar = selectedDate.getFullYear();
                break;
            case 'day':
                selectedDate.setDate(selectedDate.getDate() + 1);
                currentMonthVar = selectedDate.getMonth() + 1;
                currentYearVar = selectedDate.getFullYear();
                break;
        }
        
        if (currentView === 'month') {
            window.location.href = `reservas.php?month=${currentMonthVar}&year=${currentYearVar}`;
        } else {
            renderCalendar();
        }
    }
    
    // Ir al día actual
    function goToToday() {
        const today = new Date();
        selectedDate = today;
        currentMonthVar = today.getMonth() + 1;
        currentYearVar = today.getFullYear();
        
        if (currentView === 'month') {
            window.location.href = `reservas.php?month=${currentMonthVar}&year=${currentYearVar}`;
        } else {
            renderCalendar();
        }
    }
    
    // Cambiar entre las vistas (mes, semana, día)
    function changeView(view) {
        currentView = view;
        
        // Actualizar clases de botones activos
        monthViewBtn.classList.remove('active');
        weekViewBtn.classList.remove('active');
        dayViewBtn.classList.remove('active');
        
        switch(view) {
            case 'month':
                monthViewBtn.classList.add('active');
                break;
            case 'week':
                weekViewBtn.classList.add('active');
                break;
            case 'day':
                dayViewBtn.classList.add('active');
                break;
        }
        
        renderCalendar();
    }
    
    // Función para cargar las reservas desde la base de datos
    function loadReservations() {
        console.log('Iniciando carga de reservas...');
        
        // Primero verificar si ya tenemos reservas cargadas desde PHP
        if (window.reservations && Array.isArray(window.reservations) && window.reservations.length > 0) {
            console.log('Usando reservas precargadas:', window.reservations.length);
            reservations = window.reservations;
            renderCalendar();
            return;
        }
        
        // Si no hay reservas precargadas, intentar obtenerlas mediante AJAX
        console.log('No hay reservas precargadas, intentando AJAX...');
        
        // Crear una petición AJAX para obtener las reservas
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '../server/reservation/getReservation.php', true);
        
        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    // Parsear la respuesta JSON
                    const response = JSON.parse(this.responseText);
                    console.log('Respuesta AJAX recibida:', response);
                    
                    if (response.success) {
                        // Almacenar las reservas en la variable global
                        reservations = response.data;
                        window.reservations = reservations;
                        
                        // Mostrar las reservas en el calendario
                        renderCalendar();
                        
                        console.log('Reservas cargadas por AJAX:', reservations.length);
                    } else {
                        console.error('Error al cargar reservas:', response.message);
                    }
                } catch (e) {
                    console.error('Error al procesar reservas:', e, 'Texto recibido:', this.responseText);
                }
            } else {
                console.error('Error en la solicitud AJAX:', this.status);
            }
        };
        
        xhr.onerror = function() {
            console.error('Error de red al cargar reservas');
        };
        
        xhr.send();
    }
    
    // Función para configurar todos los event listeners
    function setupEventListeners() {
        // Navegación del calendario
        prevButton.addEventListener('click', navigatePrevious);
        nextButton.addEventListener('click', navigateNext);
        todayButton.addEventListener('click', goToToday);
        
        // Cambio de vista
        monthViewBtn.addEventListener('click', () => changeView('month'));
        weekViewBtn.addEventListener('click', () => changeView('week'));
        dayViewBtn.addEventListener('click', () => changeView('day'));
        
        // Configurar listener para fechas del formulario
        const fechaInicio = document.getElementById('fecha_inicio');
        const fechaFin = document.getElementById('fecha_fin');
        
        if (fechaInicio && fechaFin) {
            fechaInicio.addEventListener('change', function() {
                // Si la fecha fin está vacía o es anterior a la fecha inicio, actualizar
                if (!fechaFin.value || new Date(fechaFin.value) < new Date(this.value)) {
                    fechaFin.value = this.value;
                }
            });
        }
        
        // Escuchar evento de nueva reserva creada
        document.addEventListener('reservationCreated', function() {
            loadReservations(); // Recargar las reservas cuando se crea una nueva
        });
    }
    
    // Función para manejar la creación de nuevas reservas
    function handleNewReservation() {
        // Obtener el formulario de reserva
        const reservationForm = document.getElementById('reserva-form');
        
        if (reservationForm) {
            reservationForm.addEventListener('submit', function(e) {
                // No hacemos e.preventDefault() ya que queremos que el formulario se envíe normalmente
                
                // Agregar un listener al evento de éxito en el formulario
                // Este listener se activará cuando la página se recargue después de la creación
                sessionStorage.setItem('reservationJustCreated', 'true');
            });
        }
        
        // Verificar si acabamos de crear una reserva (después de recargar la página)
        if (sessionStorage.getItem('reservationJustCreated') === 'true') {
            // Limpiar el flag
            sessionStorage.removeItem('reservationJustCreated');
            
            // Recargar las reservas
            loadReservations();
            
            // Mostrar mensaje de éxito
            alert('Reserva creada con éxito');
        }
    }
    
    // Inicializar el calendario
    function init() {
        console.log('Inicializando calendario...');
        
        // Establecer valores por defecto en el formulario
        const fechaInicio = document.getElementById('fecha_inicio');
        const fechaFin = document.getElementById('fecha_fin');
        
        if (fechaInicio && fechaFin) {
            fechaInicio.valueAsDate = new Date();
            fechaFin.valueAsDate = new Date();
        }
        
        // Mostrar mensaje de error si existe en la URL
        const urlParams = new URLSearchParams(window.location.search);
        const errorMsg = urlParams.get('error');
        if (errorMsg) {
            const errorModal = document.getElementById('error-modal');
            const errorMessage = document.getElementById('error-message');
            if (errorModal && errorMessage) {
                errorMessage.textContent = decodeURIComponent(errorMsg);
                errorModal.style.display = "block";
                
                // Cerrar modal
                const closeBtn = document.querySelector('.close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function() {
                        errorModal.style.display = "none";
                    });
                }
                
                // Cerrar modal al hacer clic fuera
                window.addEventListener('click', function(event) {
                    if (event.target == errorModal) {
                        errorModal.style.display = "none";
                    }
                });
            }
        }
        
        // Configurar listeners de eventos
        setupEventListeners();
        
        // Asegurarse de que las reservas estén disponibles
        if (window.reservations && Array.isArray(window.reservations)) {
            console.log('Reservas precargadas disponibles:', window.reservations.length);
            reservations = window.reservations;
        } else {
            console.warn('No hay reservas precargadas disponibles');
            window.reservations = [];
            reservations = [];
        }
        
        // Renderizar el calendario (con o sin reservas)
        renderCalendar();
        
        // Cargar reservas desde la base de datos
        loadReservations();
        
        // Configurar manejo de nuevas reservas
        handleNewReservation();
        
        console.log('Calendario inicializado');
    }
    
    // Iniciar todo
    init();
});

// Agregar estilos para los modales y vistas
const style = document.createElement('style');
style.textContent = `
    /* Estilos para el modal de detalles */
    .reservation-details-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }
    
    .reservation-details-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 500px;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .close-details {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .close-details:hover,
    .close-details:focus {
        color: black;
        text-decoration: none;
    }
    
    /* Estilos para la vista semanal */
    .calendar-grid.week-view {
        grid-template-columns: repeat(7, 1fr);
        grid-auto-rows: minmax(80px, auto);
    }
    
    .calendar-grid.week-view .hour-label {
        font-weight: bold;
        padding: 5px;
        background-color: #f5f5f5;
        text-align: center;
        border-bottom: 1px solid #ddd;
    }
    
    .calendar-grid.week-view .hour-cell {
        border: 1px solid #ddd;
        min-height: 60px;
        padding: 5px;
    }
    
    /* Estilos para la vista diaria */
    .calendar-grid.day-view {
        display: grid;
        grid-template-columns: 80px 1fr;
        grid-auto-rows: minmax(60px, auto);
    }
    
    .calendar-grid.day-view .full-width {
        grid-column: 1 / -1;
        text-align: center;
        padding: 10px;
        background-color: #f0f0f0;
        font-weight: bold;
    }
    
    .calendar-grid.day-view .hour-row {
        display: contents;
    }
    
    .calendar-grid.day-view .hour-label {
        padding: 10px;
        font-weight: bold;
        background-color: #f5f5f5;
        border-bottom: 1px solid #ddd;
        text-align: center;
    }
    
    .calendar-grid.day-view .hour-content {
        border: 1px solid #ddd;
        padding: 5px;
        min-height: 50px;
    }
    
    /* Estilos para eventos */
    .event-item {
        background-color: #4CAF50;
        color: white;
        border-radius: 4px;
        padding: 5px;
        margin-bottom: 5px;
        cursor: pointer;
        font-size: 12px;
        overflow: hidden;
    }
    
    .event-item:hover {
        background-color: #45a049;
    }
    
    .event-time {
        font-weight: bold;
    }
    
    .event-title {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .event-resource {
        font-size: 10px;
        opacity: 0.8;
    }
    
    /* Botones en el modal */
    .modal-buttons {
        margin-top: 15px;
        text-align: right;
    }
    
    .modal-buttons button {
        padding: 8px 15px;
        margin-left: 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .btn-edit {
        background-color: #2196F3;
        color: white;
    }
    
    .btn-edit:hover {
        background-color: #0b7dda;
    }`