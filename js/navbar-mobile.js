/**
 * ========================================
 * NAVBAR MOBILE FUNCTIONALITY
 * ========================================
 * 
 * Script para manejar la funcionalidad de la navbar móvil
 * Incluye toggle del menú móvil y dropdown del usuario
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Elementos del DOM
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    const userAvatar = document.querySelector('.user-avatar');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    const navbar = document.querySelector('.navbar');
    
    /**
     * Toggle del menú móvil
     */
    if (mobileMenuToggle && navLinks) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle de las clases
            navLinks.classList.toggle('show');
            navbar.classList.toggle('mobile-menu-active');
            
            // Cambiar el icono del botón
            const icon = mobileMenuToggle.querySelector('i') || mobileMenuToggle;
            if (navLinks.classList.contains('show')) {
                if (icon.classList) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.innerHTML = '✕';
                }
            } else {
                if (icon.classList) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                } else {
                    icon.innerHTML = '☰';
                }
            }
        });
    }
    
    /**
     * Toggle del dropdown del usuario
     */
    if (userAvatar && dropdownMenu) {
        userAvatar.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle del dropdown
            dropdownMenu.classList.toggle('show');
            navbar.classList.toggle('dropdown-active');
        });
    }
    
    /**
     * Cerrar menús al hacer click fuera
     */
    document.addEventListener('click', function(e) {
        // Cerrar menú móvil si está abierto
        if (navLinks && navLinks.classList.contains('show')) {
            if (!navbar.contains(e.target)) {
                navLinks.classList.remove('show');
                navbar.classList.remove('mobile-menu-active');
                
                // Restaurar icono del botón
                const icon = mobileMenuToggle.querySelector('i') || mobileMenuToggle;
                if (icon.classList) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                } else {
                    icon.innerHTML = '☰';
                }
            }
        }
        
        // Cerrar dropdown si está abierto
        if (dropdownMenu && dropdownMenu.classList.contains('show')) {
            if (!userAvatar.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
                navbar.classList.remove('dropdown-active');
            }
        }
    });
    
    /**
     * Cerrar menú móvil al hacer click en un enlace
     */
    if (navLinks) {
        const navLinksItems = navLinks.querySelectorAll('a');
        navLinksItems.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    navLinks.classList.remove('show');
                    navbar.classList.remove('mobile-menu-active');
                    
                    // Restaurar icono del botón
                    const icon = mobileMenuToggle.querySelector('i') || mobileMenuToggle;
                    if (icon.classList) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    } else {
                        icon.innerHTML = '☰';
                    }
                }
            });
        });
    }
    
    /**
     * Manejar cambios de tamaño de pantalla
     */
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            // Ocultar menú móvil en pantallas grandes
            if (navLinks) {
                navLinks.classList.remove('show');
                navbar.classList.remove('mobile-menu-active');
            }
            
            // Restaurar icono del botón
            if (mobileMenuToggle) {
                const icon = mobileMenuToggle.querySelector('i') || mobileMenuToggle;
                if (icon.classList) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                } else {
                    icon.innerHTML = '☰';
                }
            }
        }
        
        // Cerrar dropdown en cambio de tamaño
        if (dropdownMenu) {
            dropdownMenu.classList.remove('show');
            navbar.classList.remove('dropdown-active');
        }
    });
    
    /**
     * Prevenir scroll del body cuando el menú móvil está abierto
     */
    const body = document.body;
    if (navLinks && body) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    if (navLinks.classList.contains('show') && window.innerWidth <= 768) {
                        body.style.overflow = 'hidden';
                    } else {
                        body.style.overflow = '';
                    }
                }
            });
        });
        
        observer.observe(navLinks, {
            attributes: true,
            attributeFilter: ['class']
        });
    }
    
    /**
     * Smooth scroll para los enlaces de la navbar (opcional)
     */
    const smoothScrollLinks = document.querySelectorAll('a[href^="#"]');
    smoothScrollLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            const targetElement = document.querySelector(href);
            
            if (targetElement && href !== '#') {
                e.preventDefault();
                
                // Cerrar menú móvil si está abierto
                if (navLinks && navLinks.classList.contains('show')) {
                    navLinks.classList.remove('show');
                    navbar.classList.remove('mobile-menu-active');
                }
                
                // Scroll suave al elemento
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    /**
     * Highlight del enlace activo en la navbar
     */
    function highlightActiveLink() {
        const currentPath = window.location.pathname;
        const navLinksItems = document.querySelectorAll('.nav-links a');
        
        navLinksItems.forEach(link => {
            link.classList.remove('active');
            
            // Comparar la URL del enlace con la URL actual
            const linkPath = new URL(link.href).pathname;
            if (linkPath === currentPath) {
                link.classList.add('active');
            }
        });
    }
    
    // Ejecutar al cargar la página
    highlightActiveLink();
    
    /**
     * Animación de entrada para el dropdown
     */
    if (dropdownMenu) {
        dropdownMenu.addEventListener('transitionend', function(e) {
            if (e.propertyName === 'opacity' && !this.classList.contains('show')) {
                this.style.display = 'none';
            }
        });
        
        // Observer para manejar la animación de entrada
        const dropdownObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    if (dropdownMenu.classList.contains('show')) {
                        dropdownMenu.style.display = 'block';
                        // Forzar reflow para que la animación funcione
                        dropdownMenu.offsetHeight;
                    }
                }
            });
        });
        
        dropdownObserver.observe(dropdownMenu, {
            attributes: true,
            attributeFilter: ['class']
        });
    }
    
    /**
     * Accessibility: Manejar navegación con teclado
     */
    document.addEventListener('keydown', function(e) {
        // Escape key para cerrar menús
        if (e.key === 'Escape') {
            if (navLinks && navLinks.classList.contains('show')) {
                navLinks.classList.remove('show');
                navbar.classList.remove('mobile-menu-active');
                mobileMenuToggle.focus();
            }
            
            if (dropdownMenu && dropdownMenu.classList.contains('show')) {
                dropdownMenu.classList.remove('show');
                navbar.classList.remove('dropdown-active');
                userAvatar.focus();
            }
        }
        
        // Enter y Space para activar botones
        if ((e.key === 'Enter' || e.key === ' ') && e.target === mobileMenuToggle) {
            e.preventDefault();
            mobileMenuToggle.click();
        }
        
        if ((e.key === 'Enter' || e.key === ' ') && e.target === userAvatar) {
            e.preventDefault();
            userAvatar.click();
        }
    });
    
    /**
     * Añadir atributos de accesibilidad
     */
    if (mobileMenuToggle) {
        mobileMenuToggle.setAttribute('aria-label', 'Abrir menú de navegación');
        mobileMenuToggle.setAttribute('aria-expanded', 'false');
        mobileMenuToggle.setAttribute('aria-controls', 'nav-links');
    }
    
    if (navLinks) {
        navLinks.setAttribute('id', 'nav-links');
    }
    
    if (userAvatar) {
        userAvatar.setAttribute('aria-label', 'Menú de usuario');
        userAvatar.setAttribute('aria-expanded', 'false');
        userAvatar.setAttribute('aria-haspopup', 'true');
    }
    
    // Actualizar aria-expanded cuando cambian los estados
    if (mobileMenuToggle && navLinks) {
        const menuObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    const isOpen = navLinks.classList.contains('show');
                    mobileMenuToggle.setAttribute('aria-expanded', isOpen.toString());
                    mobileMenuToggle.setAttribute('aria-label', 
                        isOpen ? 'Cerrar menú de navegación' : 'Abrir menú de navegación'
                    );
                }
            });
        });
        
        menuObserver.observe(navLinks, {
            attributes: true,
            attributeFilter: ['class']
        });
    }
    
    if (userAvatar && dropdownMenu) {
        const userObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    const isOpen = dropdownMenu.classList.contains('show');
                    userAvatar.setAttribute('aria-expanded', isOpen.toString());
                }
            });
        });
        
        userObserver.observe(dropdownMenu, {
            attributes: true,
            attributeFilter: ['class']
        });
    }
    
    console.log('Navbar mobile functionality initialized successfully');
});