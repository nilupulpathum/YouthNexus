/**
 * Announcements Interaction — W.14
 */

function openAnnouncement(data) {
    const popup = document.getElementById('announcement-popup');
    const title = document.getElementById('popup-title');
    const scope = document.getElementById('popup-scope');
    const newBadge = document.getElementById('popup-new');
    const date = document.getElementById('popup-date');
    const age = document.getElementById('popup-age');
    const summary = document.getElementById('popup-summary');
    const attachmentBox = document.getElementById('popup-attachment-container');
    const attachmentName = document.getElementById('popup-attachment-name');
    const attachmentSize = document.getElementById('popup-attachment-size');

    title.textContent = data.title;
    scope.textContent = data.scope;
    scope.className = 'scope-badge ' + data.scope.toLowerCase().replace(' ', '-');
    
    if (data.is_new) {
        newBadge.hidden = false;
    } else {
        newBadge.hidden = true;
    }

    date.textContent = data.date;
    age.textContent = data.age;
    summary.textContent = data.summary;

    if (data.attachment) {
        attachmentBox.hidden = false;
        attachmentName.textContent = data.attachment;
        attachmentSize.textContent = data.attachment_size;
    } else {
        attachmentBox.hidden = true;
    }

    popup.hidden = false;
    document.body.style.overflow = 'hidden';
}

document.addEventListener('DOMContentLoaded', () => {
    const popup = document.getElementById('announcement-popup');
    const closeBtn = document.querySelector('.popup-close');

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            popup.hidden = true;
            document.body.style.overflow = '';
        });
    }

    // Close on overlay click
    popup.addEventListener('click', (e) => {
        if (e.target === popup) {
            popup.hidden = true;
            document.body.style.overflow = '';
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !popup.hidden) {
            popup.hidden = true;
            document.body.style.overflow = '';
        }
    });
});
