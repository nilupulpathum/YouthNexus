/**
 * Events Interaction — W.10
 */

function openEvent(data) {
    const popup = document.getElementById('event-popup');
    const title = document.getElementById('popup-title');
    const scope = document.getElementById('popup-scope');
    const status = document.getElementById('popup-status');
    const date = document.getElementById('popup-date');
    const location = document.getElementById('popup-location');
    const description = document.getElementById('popup-description');
    const remaining = document.getElementById('popup-remaining');

    title.textContent = data.title;
    scope.textContent = data.scope;
    scope.className = 'scope-badge ' + data.scope.toLowerCase().replace(' ', '-');
    
    status.textContent = data.status;
    status.className = 'status-badge ' + data.status.toLowerCase();

    date.textContent = data.date;
    location.textContent = data.location;
    description.textContent = data.description;
    remaining.textContent = data.remaining;

    popup.hidden = false;
    document.body.style.overflow = 'hidden';
}

document.addEventListener('DOMContentLoaded', () => {
    const popup = document.getElementById('event-popup');
    const closeBtn = document.querySelector('.popup-close');

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            popup.hidden = true;
            document.body.style.overflow = '';
        });
    }

    popup.addEventListener('click', (e) => {
        if (e.target === popup) {
            popup.hidden = true;
            document.body.style.overflow = '';
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !popup.hidden) {
            popup.hidden = true;
            document.body.style.overflow = '';
        }
    });

    // Sidebar filter interaction
    const filterOptions = document.querySelectorAll('.events-filters .filter-option');
    filterOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                // Remove active class from siblings in the same group
                const group = this.closest('.filter-group');
                group.querySelectorAll('.filter-option').forEach(opt => opt.classList.remove('is-active'));
                this.classList.add('is-active');
            }
        });
    });
});
