/**
 * Events Interaction — W.10 (rewired fix/club-event-wiring)
 *
 * Detail data travels via the card's data-event attribute (server-escaped
 * JSON), never through inline handlers. Filters, sort and the result count
 * run client-side against the card data attributes. RSVP responses come from
 * the EventRsvp store; pressing the already-active option clears back to
 * undecided by posting 'none'.
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

    title.textContent = data.title || '';
    scope.textContent = data.scope || '';
    scope.className = 'scope-badge ' + String(data.scope || '').toLowerCase().replace(' ', '-');

    status.textContent = data.status || '';
    status.className = 'status-badge ' + String(data.status || '').toLowerCase();

    date.textContent = data.date || '';
    location.textContent = data.location || '';
    description.textContent = data.description || '';
    remaining.textContent = data.remaining || '';

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

    const grid = document.getElementById('events-grid');
    if (!grid) {
        return;
    }
    const cards = Array.from(grid.querySelectorAll('.event-card'));
    const countEl = document.getElementById('events-count');
    const emptyEl = document.getElementById('events-empty');
    const sortSelect = document.getElementById('events-sort');
    const clearBtn = document.querySelector('.clear-filters-btn');
    let popupEventId = '';

    // RSVP responses post to the server; the card state updates in place.
    const rsvpUrl = grid.dataset.rsvpUrl || '';

    function csrfToken() {
        return grid.dataset.csrf || '';
    }

    function paintRsvp(card, state) {
        card.dataset.rsvp = state || '';
        card.querySelectorAll('[data-action="rsvp"]').forEach(btn => {
            btn.classList.toggle('is-active', btn.dataset.value === state);
        });
    }

    function sendRsvp(card, eventId, response) {
        if (!rsvpUrl || !eventId) {
            return;
        }
        const body = new URLSearchParams({
            event_id: String(eventId),
            response,
            csrf_token: csrfToken(),
        });
        fetch(rsvpUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body,
        })
            .then(response => response.json().catch(() => ({ ok: false })))
            .then(data => {
                if (data && data.ok) {
                    // The server echoes the stored state (null when cleared).
                    paintRsvp(card, data && 'rsvp_status' in data ? data.rsvp_status : response);
                    syncPopupRsvp(card);
                    applyFilters();
                    return;
                }
                window.alert((data && data.error) || 'Could not save your response.');
            })
            .catch(() => {
                window.alert('Network error. Please try again.');
            });
    }

    function syncPopupRsvp(card) {
        const state = card ? (card.dataset.rsvp || '') : '';
        document.querySelectorAll('[data-popup-rsvp]').forEach(btn => {
            btn.classList.toggle('is-active', (btn.dataset.popupRsvp || '') === state && state !== '');
        });
    }

    grid.querySelectorAll('.event-card [data-action="rsvp"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const card = btn.closest('.event-card');
            if (!card) {
                return;
            }
            // Pressing the active option again clears back to undecided.
            const value = btn.dataset.value || '';
            const next = (card.dataset.rsvp || '') === value && value !== '' ? 'none' : value;
            sendRsvp(card, card.dataset.eventId || '', next);
        });
    });

    // Popup RSVP buttons act on the open event, then mirror the card.
    document.querySelectorAll('[data-popup-rsvp]').forEach(btn => {
        btn.addEventListener('click', () => {
            const card = popupEventId
                ? grid.querySelector('.event-card[data-event-id="' + popupEventId + '"]')
                : null;
            if (!card) {
                return;
            }
            const value = btn.dataset.popupRsvp || '';
            const next = (card.dataset.rsvp || '') === value && value !== '' ? 'none' : value;
            sendRsvp(card, popupEventId, next);
        });
    });

    // "View Details" reads the card payload — no inline handlers.
    document.querySelectorAll('.event-card [data-action="view"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const card = btn.closest('.event-card');
            try {
                openEvent(JSON.parse(card.getAttribute('data-event') || '{}'));
            } catch (err) {
                /* malformed payload — leave popup closed */
                return;
            }
            popupEventId = card && card.dataset.eventId ? String(card.dataset.eventId) : '';
            syncPopupRsvp(card);
        });
    });

    function checkedValues(name) {
        return Array.from(document.querySelectorAll('.events-filters input[name="' + name + '"]:checked'))
            .map(input => input.value);
    }

    function postedInRange(postedIso, range) {
        if (range === 'all' || !postedIso) {
            return range === 'all';
        }
        const posted = new Date(postedIso);
        if (Number.isNaN(posted.getTime())) {
            return false;
        }
        const now = new Date();
        if (range === 'this-month') {
            return posted.getFullYear() === now.getFullYear() && posted.getMonth() === now.getMonth();
        }
        const days = range === '7days' ? 7 : (range === '30days' ? 30 : 90);
        return (now - posted) / 86400000 <= days;
    }

    function cardMatches(card) {
        const rsvpRadio = document.querySelector('.events-filters input[name="rsvp"]:checked');
        const rsvpMode = rsvpRadio ? rsvpRadio.value : 'all';
        if (rsvpMode !== 'all') {
            const state = card.dataset.rsvp || '';
            if (rsvpMode === 'no-response') {
                if (state !== '') {
                    return false;
                }
            } else if (state !== rsvpMode) {
                return false;
            }
        }
        const levels = checkedValues('level[]');
        if (levels.indexOf('all') === -1 && levels.indexOf(card.dataset.level || '') === -1) {
            return false;
        }
        const statuses = checkedValues('status[]');
        if (statuses.indexOf('all') === -1 && statuses.indexOf(card.dataset.status || '') === -1) {
            return false;
        }
        const dateRadio = document.querySelector('.events-filters input[name="date"]:checked');
        if (!postedInRange(card.dataset.posted || '', dateRadio ? dateRadio.value : 'all')) {
            return false;
        }
        return true;
    }

    function sortCards(visible) {
        const mode = sortSelect ? sortSelect.value : 'upcoming';
        const startOf = (card) => {
            const time = new Date(card.dataset.start || '').getTime();
            return Number.isNaN(time) ? Number.POSITIVE_INFINITY : time;
        };
        const postedOf = (card) => {
            const time = new Date(card.dataset.posted || '').getTime();
            return Number.isNaN(time) ? 0 : time;
        };
        const attendanceOf = (card) => parseInt(card.dataset.attendance || '0', 10) || 0;
        visible.sort((a, b) => {
            if (mode === 'recent') {
                return postedOf(b) - postedOf(a);
            }
            if (mode === 'attendance-high') {
                return attendanceOf(b) - attendanceOf(a);
            }
            if (mode === 'attendance-low') {
                return attendanceOf(a) - attendanceOf(b);
            }
            return startOf(a) - startOf(b);
        });
        return visible;
    }

    function applyFilters() {
        const visible = cards.filter(cardMatches);
        const ordered = sortCards(visible);
        cards.forEach(card => {
            card.hidden = true;
        });
        ordered.forEach(card => {
            card.hidden = false;
            grid.appendChild(card);
        });
        if (countEl) {
            countEl.textContent = String(ordered.length);
        }
        if (emptyEl) {
            emptyEl.hidden = ordered.length !== 0;
        }
    }

    function syncActiveStates() {
        document.querySelectorAll('.events-filters .filter-option').forEach(option => {
            const input = option.querySelector('input');
            option.classList.toggle('is-active', !!(input && input.checked));
        });
    }

    document.querySelectorAll('.events-filters input').forEach(input => {
        input.addEventListener('change', () => {
            // "All" options are exclusive within checkbox groups.
            if (input.type === 'checkbox') {
                const group = input.closest('.filter-group');
                const allBox = group ? group.querySelector('input[value="all"]') : null;
                if (input.value === 'all' && input.checked && allBox) {
                    group.querySelectorAll('input[type="checkbox"]').forEach(box => {
                        if (box !== allBox) {
                            box.checked = false;
                        }
                    });
                } else if (input.value !== 'all' && input.checked && allBox) {
                    allBox.checked = false;
                }
            }
            syncActiveStates();
            applyFilters();
        });
    });

    // Radio groups keep the single-select highlight.
    document.querySelectorAll('.events-filters input[type="radio"]').forEach(radio => {
        radio.addEventListener('click', () => {
            const group = radio.closest('.filter-group');
            if (group) {
                group.querySelectorAll('.filter-option').forEach(opt => opt.classList.remove('is-active'));
                const option = radio.closest('.filter-option');
                if (option) {
                    option.classList.add('is-active');
                }
            }
        });
    });

    if (sortSelect) {
        sortSelect.addEventListener('change', applyFilters);
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            document.querySelectorAll('.events-filters input').forEach(input => {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = input.value === 'all';
                }
            });
            if (sortSelect) {
                sortSelect.value = 'upcoming';
            }
            syncActiveStates();
            applyFilters();
        });
    }

    syncActiveStates();
    applyFilters();
});
