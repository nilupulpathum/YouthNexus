/**
 * Volunteer Hours Interaction — A.09
 */

document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');

    if (dropZone && fileInput) {
        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = 'var(--db-sidebar-bg)';
            dropZone.style.background = '#f8fafc';
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.style.borderColor = 'var(--db-border)';
            dropZone.style.background = '';
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = 'var(--db-border)';
            dropZone.style.background = '';
            
            if (e.dataTransfer.files.length > 0) {
                console.log('Files dropped:', e.dataTransfer.files);
                const p = dropZone.querySelector('p');
                p.innerHTML = `<strong>${e.dataTransfer.files.length} files selected</strong>`;
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                const p = dropZone.querySelector('p');
                p.innerHTML = `<strong>${fileInput.files.length} files selected</strong>`;
            }
        });
    }

    const form = document.querySelector('.volunteer-form');
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            alert('Hours submitted for verification!');
            form.reset();
            const p = dropZone.querySelector('p');
            p.innerHTML = 'Drag and drop files here or <span>browse</span>';
        });
    }
});
