// ResourceCard.js: Handles resource display on dashboard

function createResourceCard(resource) {
    const card = document.createElement('div');
    card.classList.add('resource-card');
    
    const title = document.createElement('h3');
    title.textContent = resource.name;
    card.appendChild(title);

    const type = document.createElement('p');
    type.textContent = `Tipo: ${resource.type}`;
    card.appendChild(type);

    const status = document.createElement('p');
    status.textContent = `Estado: ${resource.status}`;
    card.appendChild(status);

    const button = document.createElement('button');
    button.textContent = 'Ver más';
    button.onclick = () => {
        // Implement the functionality to view more details about the resource
        alert(`Mostrando detalles de ${resource.name}`);
    };
    card.appendChild(button);

    return card;
}

document.addEventListener('DOMContentLoaded', function() {
    const resourceContainer = document.getElementById('resource-cards');
    
    // Fetch resources from backend and create cards
    fetch('server/task/getResources.php')
        .then(response => response.json())
        .then(data => {
            data.resources.forEach(resource => {
                const card = createResourceCard(resource);
                resourceContainer.appendChild(card);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Hubo un error al cargar los recursos');
        });
});
