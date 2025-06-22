import { render } from '@wordpress/element';
import App from './App';

// Función para inicializar la aplicación
function initBookshelfApp() {
    const root = document.getElementById('bookshelf-root');
    
    if (root) {
        const view = root.getAttribute('data-view') || 'list';
        const context = root.getAttribute('data-context') || 'frontend';
        
        render(<App view={view} context={context} />, root);
    }
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBookshelfApp);
} else {
    initBookshelfApp();
}

// También inicializar en caso de que se cargue dinámicamente
window.addEventListener('load', () => {
    // Verificar si hay elementos que no se han inicializado
    const uninitializedRoots = document.querySelectorAll('#bookshelf-root:not([data-initialized])');
    uninitializedRoots.forEach(root => {
        root.setAttribute('data-initialized', 'true');
        const view = root.getAttribute('data-view') || 'list';
        const context = root.getAttribute('data-context') || 'frontend';
        render(<App view={view} context={context} />, root);
    });
});