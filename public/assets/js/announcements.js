/** Native browser interactions for announcement creation, editing, publishing and read tracking. */
document.addEventListener('DOMContentLoaded', () => {
  const toast = document.getElementById('annToast');
  // PHP supplies the deployment path on both pages, including XAMPP subdirectory installs.
  const root = toast.dataset.root;
  const csrf = toast.dataset.csrf;
  let toastTimer;
  function showToast(message) {
    clearTimeout(toastTimer);
    toast.textContent = message;
    toast.classList.add('show');
    toastTimer = setTimeout(() => toast.classList.remove('show'), 5000);
  }

  async function request(path, options = {}) {
    const response = await fetch(`${root}/announcements/${path}`, options);
    const contentType = response.headers.get('content-type') || '';
    if (response.redirected || !contentType.includes('application/json')) {
      throw new Error('Your session expired or the server returned an unexpected response. Refresh the page and try again.');
    }
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'The request could not be completed.');
    return data;
  }

  const modal = document.getElementById('annCreateModal');
  const form = document.getElementById('annCreateForm');
  const fileInput = document.getElementById('annFileInput');
  const dropzone = document.getElementById('annDropzone');
  const existingAttachments = document.getElementById('annExistingAttachments');
  let announcementId = null;
  let editingPublished = false;
  let busy = false;
  let editRequest = 0;
  let previousFocus;
  const removedAttachmentIds = new Set();
  let selectedFiles = [];

  window.setPriority = priority => {
    document.getElementById('annPriorityInput').value = priority;
    document.querySelectorAll('.ann-priority-toggle button').forEach(button => {
      button.classList.toggle('active', button.dataset.p === priority);
      button.setAttribute('aria-pressed', String(button.dataset.p === priority));
    });
  };

  function resetForm() {
    form.reset();
    announcementId = null;
    editingPublished = false;
    removedAttachmentIds.clear();
    selectedFiles = [];
    document.getElementById('annExpectedStatus').value = 'Draft';
    document.getElementById('annSaveDraftBtn').hidden = false;
    document.getElementById('annSubmitBtn').textContent = 'Publish Announcement';
    existingAttachments.replaceChildren();
    document.getElementById('annAttachList').replaceChildren();
    document.getElementById('annModalTitle').textContent = 'Create New Announcement';
    window.setPriority('Normal');
  }

  function openModal() {
    previousFocus = document.activeElement;
    modal.classList.add('open');
    document.body.classList.add('ann-modal-open');
    document.getElementById('annTitle').focus();
  }

  window.closeCreateModal = () => {
    if (busy) return;
    editRequest++;
    modal?.classList.remove('open');
    document.body.classList.remove('ann-modal-open');
    previousFocus?.focus();
  };

  document.getElementById('annOpenCreateBtn')?.addEventListener('click', () => {
    editRequest++;
    resetForm();
    openModal();
  });

  async function openEditor(id) {
    if (!form || busy) return;
    const requestNumber = ++editRequest;
    try {
      const data = await request(`edit/${encodeURIComponent(id)}`);
      if (requestNumber !== editRequest) return;
      resetForm();
      const announcement = data.announcement;
      announcementId = announcement.announcement_id;
      editingPublished = announcement.status === 'Published';
      document.getElementById('annExpectedStatus').value = announcement.status;
      document.getElementById('annSaveDraftBtn').hidden = editingPublished;
      document.getElementById('annSubmitBtn').textContent = editingPublished ? 'Save Changes' : 'Publish Announcement';
      document.getElementById('annModalTitle').textContent = editingPublished ? 'Edit Announcement' : 'Edit Draft';
      document.getElementById('annTitle').value = announcement.title;
      document.getElementById('annBody').value = announcement.body;
      document.getElementById('annAudience').value = announcement.target_audience || '';
      document.getElementById('annCategory').value = announcement.category || '';
      window.setPriority(announcement.priority);
      data.attachments.forEach(attachment => {
        const item = document.createElement('li');
        const link = document.createElement('a');
        link.textContent = attachment.file_name;
        link.href = `${root}/announcements/download/${attachment.attachment_id}`;
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'ann-attachment-remove';
        remove.textContent = 'Remove';
        remove.setAttribute('aria-label', `Remove ${attachment.file_name}`);
        remove.setAttribute('aria-pressed', 'false');
        const note = document.createElement('span');
        note.className = 'ann-removal-note';
        note.textContent = 'Will be removed when saved';
        note.hidden = true;
        const info = document.createElement('div');
        info.className = 'ann-attachment-info';
        info.append(link, note);
        remove.addEventListener('click', () => {
          if (busy) return;
          const id = String(attachment.attachment_id);
          const removing = !removedAttachmentIds.has(id);
          if (removing) removedAttachmentIds.add(id);
          else removedAttachmentIds.delete(id);
          item.classList.toggle('ann-pending-removal', removing);
          note.hidden = !removing;
          remove.textContent = removing ? 'Undo' : 'Remove';
          remove.setAttribute('aria-label', `${removing ? 'Undo removal of' : 'Remove'} ${attachment.file_name}`);
          remove.setAttribute('aria-pressed', String(removing));
        });
        item.append(info, remove);
        existingAttachments.appendChild(item);
      });
      openModal();
    } catch (error) { showToast(error.message); }
  }

  document.querySelectorAll('[data-ann-edit]').forEach(button => {
    button.addEventListener('click', () => openEditor(button.dataset.annEdit));
  });
  const editId = new URLSearchParams(window.location.search).get('edit');
  if (editId && /^\d+$/.test(editId)) openEditor(editId);

  modal?.addEventListener('keydown', event => {
    if (event.key === 'Escape') window.closeCreateModal();
    if (event.key !== 'Tab') return;
    const controls = [...modal.querySelectorAll('button, input, select, textarea, a[href]')]
      .filter(element => !element.disabled && !element.hidden && element.type !== 'hidden' && element.getClientRects().length > 0);
    const first = controls[0], last = controls[controls.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  });

  function renderAttachments() {
    const list = document.getElementById('annAttachList');
    list.replaceChildren();
    selectedFiles.forEach((file, index) => {
      const item = document.createElement('li');
      const name = document.createElement('span');
      name.className = 'ann-attachment-info';
      name.textContent = `${file.name} (${(file.size / 1024).toFixed(0)} KB)`;
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'ann-attachment-remove';
      remove.textContent = 'Remove';
      remove.setAttribute('aria-label', `Remove selected file ${file.name}`);
      remove.addEventListener('click', () => {
        if (busy) return;
        selectedFiles.splice(index, 1);
        renderAttachments();
        const next = list.querySelectorAll('button');
        (next[Math.min(index, next.length - 1)] || dropzone).focus();
      });
      item.append(name, remove);
      list.appendChild(item);
    });
  }
  function addFiles(files) {
    if (busy) return;
    selectedFiles.push(...Array.from(files));
    fileInput.value = '';
    renderAttachments();
  }
  fileInput?.addEventListener('change', () => addFiles(fileInput.files));
  dropzone?.addEventListener('dragover', event => { event.preventDefault(); dropzone.classList.add('dragover'); });
  dropzone?.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
  dropzone?.addEventListener('drop', event => {
    event.preventDefault();
    dropzone.classList.remove('dragover');
    if (busy || !event.dataTransfer.files.length) return;
    addFiles(event.dataTransfer.files);
  });

  async function save(publish) {
    if (!form || busy) return;
    // A draft may have no audience yet; publishing requires a real audience.
    const audience = document.getElementById('annAudience');
    audience.required = publish || editingPublished;
    const valid = form.reportValidity();
    audience.required = true;
    if (!valid) return;
    if (publish && !confirm('Publish this announcement? You can edit it later; changes will display an Edited date.')) return;
    const body = new FormData(form);
    // Keep staged additions/removals in memory until the complete edit is saved.
    body.delete('attachments[]');
    selectedFiles.forEach(file => body.append('attachments[]', file));
    removedAttachmentIds.forEach(id => body.append('remove_attachments[]', id));
    const path = announcementId ? `${publish ? 'publish' : 'update'}/${announcementId}` : (publish ? 'create' : 'saveDraft');
    busy = true;
    const controls = [...form.querySelectorAll('input, select, textarea, button'), modal.querySelector('.ann-modal-close')];
    controls.forEach(control => { control.disabled = true; });
    try {
      const data = await request(path, { method: 'POST', body });
      // Navigate immediately so a second click cannot duplicate a successful creation.
      window.location.assign(`${root}/announcements/view/${data.id}`);
    } catch (error) {
      busy = false;
      controls.forEach(control => { control.disabled = false; });
      showToast(error.message);
    }
  }
  form?.addEventListener('submit', event => { event.preventDefault(); save(!editingPublished); });
  window.saveDraft = () => save(false);

  window.markAsRead = async id => {
    const button = document.getElementById('annMarkReadBtn');
    if (button.disabled) return;
    button.disabled = true;
    try {
      await request(`markRead/${id}`, { method: 'POST', body: new URLSearchParams({ csrf_token: csrf }) });
      button.textContent = 'Read';
      document.getElementById('annNewBadge')?.remove();
      showToast('Marked as read.');
    } catch (error) { button.disabled = false; showToast(error.message); }
  };

  const grid = document.getElementById('annGrid');
  const search = document.getElementById('annSearchInput');
  const status = document.getElementById('annFilterStatus');
  const audience = document.getElementById('annFilterAudience');
  const priority = document.getElementById('annFilterPriority');
  const tab = document.getElementById('annTabSelect');
  const statCards = document.querySelectorAll('[data-ann-status]');
  function applyFilters() {
    statCards.forEach(card => {
      const active = card.dataset.annStatus === status.value;
      card.classList.toggle('is-active', active);
      card.setAttribute('aria-pressed', String(active));
    });
    if (!grid) return;
    const query = search.value.trim().toLowerCase();
    let visible = 0;
    grid.querySelectorAll('.ann-card').forEach(card => {
      const matches = (!query || `${card.dataset.title} ${card.dataset.body}`.toLowerCase().includes(query))
        && (!status.value || card.dataset.status === status.value)
        && (!audience.value || card.dataset.audience === audience.value)
        && (!priority.value || card.dataset.priority === priority.value);
      card.hidden = !matches;
      if (matches) visible++;
    });
    document.getElementById('annNoMatches').hidden = visible > 0;
  }
  search?.addEventListener('input', applyFilters);
  audience?.addEventListener('change', applyFilters);
  priority?.addEventListener('change', applyFilters);
  function selectStatus(value) {
    status.value = value;
    tab.value = status.value === 'Draft' ? 'Drafts' : (status.value || 'All Announcements');
    applyFilters();
  }
  status?.addEventListener('change', () => selectStatus(status.value));
  tab?.addEventListener('change', () => {
    selectStatus(tab.value === 'Drafts' ? 'Draft' : (tab.value === 'Published' ? 'Published' : ''));
  });
  statCards.forEach(card => {
    card.addEventListener('click', () => {
      // A summary card shows its entire group, matching the total printed on the card.
      audience.value = priority.value = search.value = '';
      selectStatus(card.dataset.annStatus);
    });
  });
  document.getElementById('annFilterBtn')?.addEventListener('click', event => {
    const open = document.getElementById('annFilterPanel').classList.toggle('open');
    event.currentTarget.setAttribute('aria-expanded', String(open));
  });
  document.getElementById('annClearFilterBtn')?.addEventListener('click', () => {
    audience.value = priority.value = search.value = '';
    selectStatus('');
  });
});
