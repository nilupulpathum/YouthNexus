/**
 * YouthNexus Announcements
 *
 * Vanilla JavaScript only.
 *
 * Handles:
 * - Create announcement
 * - Save draft
 * - Edit announcement
 * - Publish draft
 * - Edit published announcement
 * - Role targeting
 * - All / Selected recipient targeting
 * - Dynamic recipient loading
 * - Attachments
 * - Delete
 * - Read receipts
 * - Search and filters
 */

document.addEventListener('DOMContentLoaded', () => {

  // ============================================================
  // Shared page values
  // ============================================================

  const toast =
    document.getElementById('annToast');


  if (!toast) {
    return;
  }


  const root =
    toast.dataset.root || '';

  const csrf =
    toast.dataset.csrf || '';


  let toastTimer = null;


  function showToast(message) {
    clearTimeout(toastTimer);

    toast.textContent =
      message;

    toast.classList.add(
      'show'
    );


    toastTimer =
      setTimeout(
        () => {
          toast.classList.remove(
            'show'
          );
        },
        5000
      );
  }


  // ============================================================
  // AJAX helper
  // ============================================================

  async function request(
    path,
    options = {}
  ) {
    const response =
      await fetch(
        `${root}/announcements/${path}`,
        options
      );


    const contentType =
      response.headers.get(
        'content-type'
      ) || '';


    /*
     * Announcement AJAX endpoints must return JSON.
     *
     * A redirect usually means the login session expired.
     */
    if (
      response.redirected
      ||
      !contentType.includes(
        'application/json'
      )
    ) {
      throw new Error(
        'Your session expired or the server returned an unexpected response. Refresh the page and try again.'
      );
    }


    const data =
      await response.json();


    if (!response.ok) {
      throw new Error(
        data.error
        ||
        'The request could not be completed.'
      );
    }


    return data;
  }


  // ============================================================
  // Create / edit modal references
  // ============================================================

  const modal =
    document.getElementById(
      'annCreateModal'
    );


  const form =
    document.getElementById(
      'annCreateForm'
    );


  const fileInput =
    document.getElementById(
      'annFileInput'
    );


  const dropzone =
    document.getElementById(
      'annDropzone'
    );


  const existingAttachments =
    document.getElementById(
      'annExistingAttachments'
    );


  let announcementId = null;

  let editingPublished = false;

  let busy = false;

  let editRequest = 0;

  let previousFocus = null;


  const removedAttachmentIds =
    new Set();


  let selectedFiles = [];


  /*
   * Cache recipient API results by role.
   *
   * Example:
   * recipientCache.get('ClubSecretary')
   */
  const recipientCache =
    new Map();


  // ============================================================
  // Priority
  // ============================================================

  window.setPriority =
    priority => {

      const input =
        document.getElementById(
          'annPriorityInput'
        );


      if (input) {
        input.value =
          priority;
      }


      document
        .querySelectorAll(
          '.ann-priority-toggle button'
        )
        .forEach(
          button => {

            const active =
              button.dataset.p
              === priority;


            button.classList.toggle(
              'active',
              active
            );


            button.setAttribute(
              'aria-pressed',
              String(active)
            );
          }
        );
    };


  // ============================================================
  // Recipient targeting helpers
  // ============================================================

  function getRoleCards() {
    return [
      ...document.querySelectorAll(
        '[data-target-role-card]'
      ),
    ];
  }


  function getRoleCard(role) {
    return getRoleCards()
      .find(
        card =>
          card.dataset.role
          === role
      );
  }


  /**
   * Render eligible users for one actor type.
   */
  function renderRecipientList(
    card,
    recipients
  ) {
    const role =
      card.dataset.role;


    const list =
      card.querySelector(
        '[data-recipient-list]'
      );


    if (!list) {
      return;
    }


    list.replaceChildren();


    if (!recipients.length) {

      const empty =
        document.createElement(
          'p'
        );


      empty.className =
        'ann-field-help';


      empty.textContent =
        'No eligible users were found for this role.';


      list.appendChild(
        empty
      );


      card.dataset.recipientsLoaded =
        '1';


      return;
    }


    recipients.forEach(
      recipient => {

        const label =
          document.createElement(
            'label'
          );


        label.className =
          'ann-recipient-option';


        const input =
          document.createElement(
            'input'
          );


        input.type =
          'checkbox';


        /*
         * PHP receives:
         *
         * target_users[ClubSecretary][]
         */
        input.name =
          `target_users[${role}][]`;


        input.value =
          String(
            recipient.user_id
          );


        const text =
          document.createElement(
            'span'
          );


        /*
         * Show the most useful organisational context.
         */
        const context =
          recipient.club_name
          ||
          recipient.division_name
          ||
          recipient.zonal_name
          ||
          'YouthNexus';


        text.textContent =
          `${recipient.name} — ${context}`;


        /*
         * Browser-side search text.
         */
        label.dataset.searchText =
          [
            recipient.name || '',
            recipient.email || '',
            recipient.club_name || '',
            recipient.division_name || '',
            recipient.zonal_name || '',
          ]
            .join(' ')
            .toLowerCase();


        label.append(
          input,
          text
        );


        list.appendChild(
          label
        );
      }
    );


    card.dataset.recipientsLoaded =
      '1';
  }


  /**
   * Retrieve eligible users for one target role.
   */
  async function loadRecipients(
    card
  ) {
    const role =
      card.dataset.role;


    /*
     * Already rendered for this card.
     */
    if (
      card.dataset.recipientsLoaded
      === '1'
    ) {
      return;
    }


    const list =
      card.querySelector(
        '[data-recipient-list]'
      );


    /*
     * We may have fetched this role earlier.
     */
    if (
      recipientCache.has(role)
    ) {
      renderRecipientList(
        card,
        recipientCache.get(role)
      );

      return;
    }


    if (list) {
      list.textContent =
        'Loading recipients...';
    }


    try {

      const data =
        await request(
          `recipients/${encodeURIComponent(role)}`
        );


      const recipients =
        Array.isArray(
          data.recipients
        )
          ? data.recipients
          : [];


      recipientCache.set(
        role,
        recipients
      );


      renderRecipientList(
        card,
        recipients
      );

    } catch (error) {

      if (list) {
        list.textContent =
          error.message;
      }


      showToast(
        error.message
      );


      throw error;
    }
  }


  /**
   * Update one role card when:
   *
   * - role enabled / disabled
   * - All selected
   * - Selected selected
   */
  async function updateRoleMode(
    card
  ) {
    const enableInput =
      card.querySelector(
        '.ann-target-role-enable'
      );


    const enabled =
      !!enableInput?.checked;


    const settings =
      card.querySelector(
        '.ann-target-role-settings'
      );


    const picker =
      card.querySelector(
        '[data-recipient-picker]'
      );


    const radios = [
      ...card.querySelectorAll(
        'input[type="radio"]'
      ),
    ];


    /*
     * Role disabled.
     */
    if (!enabled) {

      if (settings) {
        settings.hidden =
          true;
      }


      if (picker) {
        picker.hidden =
          true;
      }


      radios.forEach(
        radio => {
          radio.disabled =
            true;
        }
      );


      card
        .querySelectorAll(
          '.ann-recipient-option input'
        )
        .forEach(
          input => {
            input.disabled =
              true;
          }
        );


      return;
    }


    /*
     * Role enabled.
     */
    if (settings) {
      settings.hidden =
        false;
    }


    radios.forEach(
      radio => {
        radio.disabled =
          false;
      }
    );


    let selectedMode =
      card.querySelector(
        'input[type="radio"]:checked'
      );


    /*
     * Default to All.
     */
    if (!selectedMode) {

      selectedMode =
        radios.find(
          radio =>
            radio.value === 'All'
        );


      if (selectedMode) {
        selectedMode.checked =
          true;
      }
    }


    const selectingSpecificUsers =
      selectedMode?.value
      === 'Selected';


    if (picker) {
      picker.hidden =
        !selectingSpecificUsers;
    }


    /*
     * All mode:
     *
     * selected-user checkboxes must not
     * be included in FormData.
     */
    if (!selectingSpecificUsers) {

      card
        .querySelectorAll(
          '.ann-recipient-option input'
        )
        .forEach(
          input => {
            input.disabled =
              true;
          }
        );


      return;
    }


    /*
     * Selected mode.
     */
    await loadRecipients(
      card
    );


    card
      .querySelectorAll(
        '.ann-recipient-option input'
      )
      .forEach(
        input => {
          input.disabled =
            false;
        }
      );
  }


  /**
   * Reset target role UI.
   */
  function resetTargetRoleCards() {

    getRoleCards()
      .forEach(
        card => {

          const enable =
            card.querySelector(
              '.ann-target-role-enable'
            );


          if (enable) {
            enable.checked =
              false;
          }


          const settings =
            card.querySelector(
              '.ann-target-role-settings'
            );


          if (settings) {
            settings.hidden =
              true;
          }


          const picker =
            card.querySelector(
              '[data-recipient-picker]'
            );


          if (picker) {
            picker.hidden =
              true;
          }


          const search =
            card.querySelector(
              '[data-recipient-search]'
            );


          if (search) {
            search.value =
              '';
          }


          const radios = [
            ...card.querySelectorAll(
              'input[type="radio"]'
            ),
          ];


          radios.forEach(
            radio => {

              radio.checked =
                radio.value
                === 'All';


              radio.disabled =
                true;
            }
          );


          card
            .querySelectorAll(
              '.ann-recipient-option'
            )
            .forEach(
              option => {
                option.hidden =
                  false;
              }
            );


          card
            .querySelectorAll(
              '.ann-recipient-option input'
            )
            .forEach(
              input => {

                input.checked =
                  false;

                input.disabled =
                  true;
              }
            );
        }
      );
  }


  /**
   * Bind role targeting controls.
   */
  getRoleCards()
    .forEach(
      card => {

        card
          .querySelector(
            '.ann-target-role-enable'
          )
          ?.addEventListener(
            'change',
            () => {
              updateRoleMode(card)
                .catch(
                  () => {
                    /*
                     * Error already shown by loadRecipients().
                     */
                  }
                );
            }
          );


        card
          .querySelectorAll(
            'input[type="radio"]'
          )
          .forEach(
            radio => {

              radio.addEventListener(
                'change',
                () => {
                  updateRoleMode(card)
                    .catch(
                      () => {
                        /*
                         * Error already shown.
                         */
                      }
                    );
                }
              );
            }
          );


        card
          .querySelector(
            '[data-recipient-search]'
          )
          ?.addEventListener(
            'input',
            event => {

              const query =
                event.target.value
                  .trim()
                  .toLowerCase();


              card
                .querySelectorAll(
                  '.ann-recipient-option'
                )
                .forEach(
                  option => {

                    const haystack =
                      option.dataset
                        .searchText
                      || '';


                    option.hidden =
                      query !== ''
                      &&
                      !haystack.includes(
                        query
                      );
                  }
                );
            }
          );
      }
    );


  // ============================================================
  // Reset modal
  // ============================================================

  function resetForm() {
    if (!form) {
      return;
    }


    form.reset();


    announcementId =
      null;


    editingPublished =
      false;


    removedAttachmentIds.clear();


    selectedFiles =
      [];


    resetTargetRoleCards();


    const expectedStatus =
      document.getElementById(
        'annExpectedStatus'
      );


    if (expectedStatus) {
      expectedStatus.value =
        'Draft';
    }


    const saveDraftButton =
      document.getElementById(
        'annSaveDraftBtn'
      );


    if (saveDraftButton) {
      saveDraftButton.hidden =
        false;
    }


    const submitButton =
      document.getElementById(
        'annSubmitBtn'
      );


    if (submitButton) {
      submitButton.textContent =
        'Publish Announcement';
    }


    existingAttachments
      ?.replaceChildren();


    document
      .getElementById(
        'annAttachList'
      )
      ?.replaceChildren();


    const modalTitle =
      document.getElementById(
        'annModalTitle'
      );


    if (modalTitle) {
      modalTitle.textContent =
        'Create New Announcement';
    }


    window.setPriority(
      'Normal'
    );
  }


  // ============================================================
  // Open / close modal
  // ============================================================

  function openModal() {
    if (
      !modal
      ||
      !form
    ) {
      return;
    }


    previousFocus =
      document.activeElement;


    modal.classList.add(
      'open'
    );


    document.body.classList.add(
      'ann-modal-open'
    );


    document
      .getElementById(
        'annTitle'
      )
      ?.focus();
  }


  window.closeCreateModal =
    () => {

      if (busy) {
        return;
      }


      /*
       * Invalidate any pending edit request.
       */
      editRequest++;


      modal?.classList.remove(
        'open'
      );


      document.body.classList.remove(
        'ann-modal-open'
      );


      previousFocus
        ?.focus();
    };


  document
    .getElementById(
      'annOpenCreateBtn'
    )
    ?.addEventListener(
      'click',
      () => {

        editRequest++;


        resetForm();


        openModal();
      }
    );


  // ============================================================
  // Edit existing announcement
  // ============================================================

  async function openEditor(id) {

    if (
      !form
      ||
      busy
    ) {
      return;
    }


    const requestNumber =
      ++editRequest;


    try {

      const data =
        await request(
          `edit/${encodeURIComponent(id)}`
        );


      /*
       * User may have closed editor while
       * request was running.
       */
      if (
        requestNumber
        !== editRequest
      ) {
        return;
      }


      resetForm();


      const announcement =
        data.announcement;


      if (!announcement) {
        throw new Error(
          'Announcement data could not be loaded.'
        );
      }


      announcementId =
        announcement
          .announcement_id;


      editingPublished =
        announcement.status
        === 'Published';


      const expectedStatus =
        document.getElementById(
          'annExpectedStatus'
        );


      if (expectedStatus) {
        expectedStatus.value =
          announcement.status;
      }


      const saveDraftButton =
        document.getElementById(
          'annSaveDraftBtn'
        );


      if (saveDraftButton) {
        saveDraftButton.hidden =
          editingPublished;
      }


      const submitButton =
        document.getElementById(
          'annSubmitBtn'
        );


      if (submitButton) {

        submitButton.textContent =
          editingPublished
            ? 'Save Changes'
            : 'Publish Announcement';
      }


      const modalTitle =
        document.getElementById(
          'annModalTitle'
        );


      if (modalTitle) {

        modalTitle.textContent =
          editingPublished
            ? 'Edit Announcement'
            : 'Edit Draft';
      }


      const title =
        document.getElementById(
          'annTitle'
        );


      if (title) {
        title.value =
          announcement.title
          || '';
      }


      const body =
        document.getElementById(
          'annBody'
        );


      if (body) {
        body.value =
          announcement.body
          || '';
      }


      const category =
        document.getElementById(
          'annCategory'
        );


      if (category) {
        category.value =
          announcement.category
          || '';
      }


      window.setPriority(
        announcement.priority
        || 'Normal'
      );


      // --------------------------------------------------------
      // Restore role / recipient targeting
      // --------------------------------------------------------

      const audienceTargets =
        Array.isArray(
          data.audience_targets
        )
          ? data.audience_targets
          : [];


      for (
        const target
        of audienceTargets
      ) {

        if (
          requestNumber
          !== editRequest
        ) {
          return;
        }


        const card =
          getRoleCard(
            target.target_role
          );


        /*
         * Ignore a target role no longer
         * permitted for this manager.
         */
        if (!card) {
          continue;
        }


        const enable =
          card.querySelector(
            '.ann-target-role-enable'
          );


        if (enable) {
          enable.checked =
            true;
        }


        const requestedMode =
          target.selection_mode
            === 'Selected'
            ? 'Selected'
            : 'All';


        const modeRadio =
          card.querySelector(
            `input[type="radio"][value="${requestedMode}"]`
          );


        if (modeRadio) {
          modeRadio.checked =
            true;
        }


        /*
         * For Selected mode this also
         * loads eligible users.
         */
        await updateRoleMode(
          card
        );


        if (
          requestNumber
          !== editRequest
        ) {
          return;
        }


        if (
          requestedMode
          === 'Selected'
        ) {

          const selectedIds =
            new Set(
              (
                target.user_ids
                || []
              ).map(
                value =>
                  String(value)
              )
            );


          card
            .querySelectorAll(
              '.ann-recipient-option input'
            )
            .forEach(
              input => {

                input.checked =
                  selectedIds.has(
                    input.value
                  );


                input.disabled =
                  false;
              }
            );
        }
      }


      // --------------------------------------------------------
      // Existing attachments
      // --------------------------------------------------------

      const attachments =
        Array.isArray(
          data.attachments
        )
          ? data.attachments
          : [];


      attachments.forEach(
        attachment => {

          const item =
            document.createElement(
              'li'
            );


          const info =
            document.createElement(
              'div'
            );


          info.className =
            'ann-attachment-info';


          const link =
            document.createElement(
              'a'
            );


          link.textContent =
            attachment.file_name;


          link.href =
            `${root}/announcements/download/${attachment.attachment_id}`;


          const note =
            document.createElement(
              'span'
            );


          note.className =
            'ann-removal-note';


          note.textContent =
            'Will be removed when saved';


          note.hidden =
            true;


          info.append(
            link,
            note
          );


          const remove =
            document.createElement(
              'button'
            );


          remove.type =
            'button';


          remove.className =
            'ann-attachment-remove';


          remove.textContent =
            'Remove';


          remove.setAttribute(
            'aria-label',
            `Remove ${attachment.file_name}`
          );


          remove.setAttribute(
            'aria-pressed',
            'false'
          );


          remove.addEventListener(
            'click',
            () => {

              if (busy) {
                return;
              }


              const attachmentId =
                String(
                  attachment
                    .attachment_id
                );


              const removing =
                !removedAttachmentIds
                  .has(
                    attachmentId
                  );


              if (removing) {

                removedAttachmentIds.add(
                  attachmentId
                );

              } else {

                removedAttachmentIds.delete(
                  attachmentId
                );
              }


              item.classList.toggle(
                'ann-pending-removal',
                removing
              );


              note.hidden =
                !removing;


              remove.textContent =
                removing
                  ? 'Undo'
                  : 'Remove';


              remove.setAttribute(
                'aria-label',

                `${removing
                  ? 'Undo removal of'
                  : 'Remove'
                } ${attachment.file_name}`
              );


              remove.setAttribute(
                'aria-pressed',
                String(removing)
              );
            }
          );


          item.append(
            info,
            remove
          );


          existingAttachments
            ?.appendChild(
              item
            );
        }
      );


      openModal();

    } catch (error) {

      showToast(
        error.message
      );
    }
  }


  /*
   * Edit buttons on announcement cards.
   */
  document
    .querySelectorAll(
      '[data-ann-edit]'
    )
    .forEach(
      button => {

        button.addEventListener(
          'click',
          () => {

            openEditor(
              button.dataset
                .annEdit
            );
          }
        );
      }
    );


  /*
   * Detail page edit link redirects:
   *
   * /announcements?edit=12
   */
  const editId =
    new URLSearchParams(
      window.location.search
    )
      .get('edit');


  if (
    editId
    &&
    /^\d+$/.test(editId)
  ) {
    openEditor(
      editId
    );
  }


  // ============================================================
  // Modal keyboard accessibility
  // ============================================================

  modal?.addEventListener(
    'keydown',
    event => {

      if (
        event.key
        === 'Escape'
      ) {
        window.closeCreateModal();

        return;
      }


      if (
        event.key
        !== 'Tab'
      ) {
        return;
      }


      const controls = [
        ...modal.querySelectorAll(
          'button, input, select, textarea, a[href]'
        ),
      ]
        .filter(
          element =>
            !element.disabled
            &&
            !element.hidden
            &&
            element.type !== 'hidden'
            &&
            element
              .getClientRects()
              .length > 0
        );


      if (!controls.length) {
        return;
      }


      const first =
        controls[0];


      const last =
        controls[
        controls.length - 1
        ];


      if (
        event.shiftKey
        &&
        document.activeElement
        === first
      ) {
        event.preventDefault();

        last.focus();

        return;
      }


      if (
        !event.shiftKey
        &&
        document.activeElement
        === last
      ) {
        event.preventDefault();

        first.focus();
      }
    }
  );


  // ============================================================
  // New attachments
  // ============================================================

  function renderAttachments() {

    const list =
      document.getElementById(
        'annAttachList'
      );


    if (!list) {
      return;
    }


    list.replaceChildren();


    selectedFiles.forEach(
      (file, index) => {

        const item =
          document.createElement(
            'li'
          );


        const name =
          document.createElement(
            'span'
          );


        name.className =
          'ann-attachment-info';


        name.textContent =
          `${file.name} (${(
            file.size / 1024
          ).toFixed(0)} KB)`;


        const remove =
          document.createElement(
            'button'
          );


        remove.type =
          'button';


        remove.className =
          'ann-attachment-remove';


        remove.textContent =
          'Remove';


        remove.setAttribute(
          'aria-label',
          `Remove selected file ${file.name}`
        );


        remove.addEventListener(
          'click',
          () => {

            if (busy) {
              return;
            }


            selectedFiles.splice(
              index,
              1
            );


            renderAttachments();


            const buttons =
              list.querySelectorAll(
                'button'
              );


            const nextButton =
              buttons[
              Math.min(
                index,
                buttons.length - 1
              )
              ];


            (
              nextButton
              ||
              dropzone
            )
              ?.focus();
          }
        );


        item.append(
          name,
          remove
        );


        list.appendChild(
          item
        );
      }
    );
  }


  function addFiles(files) {

    if (
      busy
      ||
      !files
    ) {
      return;
    }


    selectedFiles.push(
      ...Array.from(files)
    );


    if (fileInput) {
      fileInput.value =
        '';
    }


    renderAttachments();
  }


  fileInput?.addEventListener(
    'change',
    () => {

      addFiles(
        fileInput.files
      );
    }
  );


  dropzone?.addEventListener(
    'dragover',
    event => {

      event.preventDefault();


      dropzone.classList.add(
        'dragover'
      );
    }
  );


  dropzone?.addEventListener(
    'dragleave',
    () => {

      dropzone.classList.remove(
        'dragover'
      );
    }
  );


  dropzone?.addEventListener(
    'drop',
    event => {

      event.preventDefault();


      dropzone.classList.remove(
        'dragover'
      );


      if (
        busy
        ||
        !event.dataTransfer
        ||
        !event.dataTransfer
          .files.length
      ) {
        return;
      }


      addFiles(
        event.dataTransfer.files
      );
    }
  );


  // ============================================================
  // Validate audiences before save
  // ============================================================

  function validateTargetSelection(
    requireAudience
  ) {

    const enabledCards =
      getRoleCards()
        .filter(
          card =>
            card
              .querySelector(
                '.ann-target-role-enable'
              )
              ?.checked
        );


    /*
     * Published announcements require
     * at least one role.
     */
    if (
      requireAudience
      &&
      enabledCards.length === 0
    ) {

      showToast(
        'Select at least one target role before publishing.'
      );


      getRoleCards()[0]
        ?.querySelector(
          '.ann-target-role-enable'
        )
        ?.focus();


      return false;
    }


    for (
      const card
      of enabledCards
    ) {

      const role =
        card.dataset.role;


      const mode =
        card.querySelector(
          'input[type="radio"]:checked'
        )?.value;


      if (
        mode !== 'All'
        &&
        mode !== 'Selected'
      ) {

        showToast(
          `Select how ${role} recipients should be targeted.`
        );


        return false;
      }


      /*
       * Selected mode requires one or
       * more specific users.
       */
      if (
        mode === 'Selected'
      ) {

        const checkedUsers =
          card.querySelectorAll(
            '.ann-recipient-option input:checked'
          );


        if (
          checkedUsers.length === 0
        ) {

          showToast(
            `Select at least one specific ${role} recipient.`
          );


          card
            .querySelector(
              '[data-recipient-search]'
            )
            ?.focus();


          return false;
        }
      }
    }


    return true;
  }


  // ============================================================
  // Save / publish
  // ============================================================

  async function save(
    publish
  ) {

    if (
      !form
      ||
      busy
    ) {
      return;
    }


    /*
     * Native browser HTML validation.
     */
    if (
      !form.reportValidity()
    ) {
      return;
    }


    /*
     * Existing Published announcement
     * must retain an audience.
     */
    const requireAudience =
      publish
      ||
      editingPublished;


    if (
      !validateTargetSelection(
        requireAudience
      )
    ) {
      return;
    }


    /*
     * Publishing a Draft/new announcement
     * asks for confirmation.
     *
     * Editing an already Published item
     * uses Save Changes and does not show
     * publish confirmation again.
     */
    if (
      publish
      &&
      !confirm(
        'Publish this announcement? You can edit it later; changes will display an Edited date.'
      )
    ) {
      return;
    }


    /*
     * Disabled fields are not added to FormData.
     *
     * This is useful here:
     *
     * - disabled role = no target_modes entry
     * - All mode = specific user checkboxes disabled
     * - Selected mode = selected IDs included
     */
    const body =
      new FormData(form);


    /*
     * Attachments are staged manually.
     */
    body.delete(
      'attachments[]'
    );


    selectedFiles.forEach(
      file => {

        body.append(
          'attachments[]',
          file
        );
      }
    );


    removedAttachmentIds.forEach(
      id => {

        body.append(
          'remove_attachments[]',
          id
        );
      }
    );


    let path;


    if (announcementId) {

      path =
        `${publish
          ? 'publish'
          : 'update'
        }/${announcementId}`;

    } else {

      path =
        publish
          ? 'create'
          : 'saveDraft';
    }


    busy =
      true;


    /*
     * Disable controls while request is
     * being processed.
     */
    const controls = [
      ...form.querySelectorAll(
        'input, select, textarea, button'
      ),
    ];


    const closeButton =
      modal?.querySelector(
        '.ann-modal-close'
      );


    if (closeButton) {
      controls.push(
        closeButton
      );
    }


    controls.forEach(
      control => {

        control.disabled =
          true;
      }
    );


    try {

      const data =
        await request(
          path,
          {
            method:
              'POST',

            body,
          }
        );


      /*
       * Move directly to detail page.
       *
       * Prevents accidental duplicate
       * creation from a second click.
       */
      window.location.assign(
        `${root}/announcements/view/${data.id}`
      );

    } catch (error) {

      busy =
        false;


      /*
       * Re-enable basic controls first.
       */
      controls.forEach(
        control => {

          control.disabled =
            false;
        }
      );


      /*
       * Restore correct target-role
       * disabled states.
       */
      for (
        const card
        of getRoleCards()
      ) {

        try {

          await updateRoleMode(
            card
          );

        } catch (_) {

          /*
           * Error was already displayed.
           */
        }
      }


      showToast(
        error.message
      );
    }
  }


  form?.addEventListener(
    'submit',
    event => {

      event.preventDefault();


      /*
       * New:
       * submit -> Publish
       *
       * Draft:
       * submit -> Publish
       *
       * Published:
       * submit -> Save Changes
       */
      save(
        !editingPublished
      );
    }
  );


  window.saveDraft =
    () => {

      save(
        false
      );
    };


  // ============================================================
  // Delete announcement
  // ============================================================

  window.deleteAnnouncement =
    async id => {

      if (busy) {
        return;
      }


      const confirmed =
        confirm(
          'Delete this announcement? It will no longer appear in announcement feeds.'
        );


      if (!confirmed) {
        return;
      }


      busy =
        true;


      try {

        await request(
          `delete/${encodeURIComponent(id)}`,
          {
            method:
              'POST',

            body:
              new URLSearchParams({
                csrf_token:
                  csrf,
              }),
          }
        );


        window.location.assign(
          `${root}/announcements`
        );

      } catch (error) {

        busy =
          false;


        showToast(
          error.message
        );
      }
    };


  // ============================================================
  // Mark as read
  // ============================================================

  window.markAsRead =
    async id => {

      const button =
        document.getElementById(
          'annMarkReadBtn'
        );


      if (
        !button
        ||
        button.disabled
      ) {
        return;
      }


      button.disabled =
        true;


      try {

        await request(
          `markRead/${encodeURIComponent(id)}`,
          {
            method:
              'POST',

            body:
              new URLSearchParams({
                csrf_token:
                  csrf,
              }),
          }
        );


        button.textContent =
          'Read';


        document
          .getElementById(
            'annNewBadge'
          )
          ?.remove();


        showToast(
          'Marked as read.'
        );

      } catch (error) {

        button.disabled =
          false;


        showToast(
          error.message
        );
      }
    };


  // ============================================================
  // List filtering
  // ============================================================

  const grid =
    document.getElementById(
      'annGrid'
    );


  const search =
    document.getElementById(
      'annSearchInput'
    );


  const status =
    document.getElementById(
      'annFilterStatus'
    );


  const roleFilter =
    document.getElementById(
      'annFilterRole'
    );


  const priority =
    document.getElementById(
      'annFilterPriority'
    );


  const tab =
    document.getElementById(
      'annTabSelect'
    );


  const statCards =
    document.querySelectorAll(
      '[data-ann-status]'
    );


  function applyFilters() {

    /*
     * Detail page doesn't contain list
     * filters, so stop safely.
     */
    if (!status) {
      return;
    }


    /*
     * Highlight active summary card.
     */
    statCards.forEach(
      card => {

        const active =
          card.dataset.annStatus
          === status.value;


        card.classList.toggle(
          'is-active',
          active
        );


        card.setAttribute(
          'aria-pressed',
          String(active)
        );
      }
    );


    if (!grid) {
      return;
    }


    const query =
      (
        search?.value
        || ''
      )
        .trim()
        .toLowerCase();


    let visible =
      0;


    grid
      .querySelectorAll(
        '.ann-card'
      )
      .forEach(
        card => {

          const searchableText =
            [
              card.dataset.title
              || '',
              card.dataset.body
              || '',
            ]
              .join(' ')
              .toLowerCase();


          const targetRoles =
            (
              card.dataset.targetRoles
              || ''
            )
              .split('|')
              .filter(Boolean);


          const matchesSearch =
            !query
            ||
            searchableText.includes(
              query
            );


          const matchesStatus =
            !status.value
            ||
            card.dataset.status
            === status.value;


          const matchesRole =
            !roleFilter?.value
            ||
            targetRoles.includes(
              roleFilter.value
            );


          const matchesPriority =
            !priority?.value
            ||
            card.dataset.priority
            === priority.value;


          const matches =
            matchesSearch
            &&
            matchesStatus
            &&
            matchesRole
            &&
            matchesPriority;


          card.hidden =
            !matches;


          if (matches) {
            visible++;
          }
        }
      );


    const noMatches =
      document.getElementById(
        'annNoMatches'
      );


    if (noMatches) {
      noMatches.hidden =
        visible > 0;
    }
  }


  search?.addEventListener(
    'input',
    applyFilters
  );


  roleFilter?.addEventListener(
    'change',
    applyFilters
  );


  priority?.addEventListener(
    'change',
    applyFilters
  );


  function selectStatus(
    value
  ) {

    if (!status) {
      return;
    }


    status.value =
      value;


    if (tab) {

      tab.value =
        value === 'Draft'
          ? 'Drafts'
          : (
            value
            || 'All Announcements'
          );
    }


    applyFilters();
  }


  status?.addEventListener(
    'change',
    () => {

      selectStatus(
        status.value
      );
    }
  );


  tab?.addEventListener(
    'change',
    () => {

      let value =
        '';


      if (
        tab.value
        === 'Drafts'
      ) {

        value =
          'Draft';

      } else if (
        tab.value
        === 'Published'
      ) {

        value =
          'Published';
      }


      selectStatus(
        value
      );
    }
  );


  statCards.forEach(
    card => {

      card.addEventListener(
        'click',
        () => {

          /*
           * Clicking summary card clears
           * other filters.
           */
          if (roleFilter) {
            roleFilter.value =
              '';
          }


          if (priority) {
            priority.value =
              '';
          }


          if (search) {
            search.value =
              '';
          }


          selectStatus(
            card.dataset.annStatus
            || ''
          );
        }
      );
    }
  );


  document
    .getElementById(
      'annFilterBtn'
    )
    ?.addEventListener(
      'click',
      event => {

        const panel =
          document.getElementById(
            'annFilterPanel'
          );


        if (!panel) {
          return;
        }


        const open =
          panel.classList.toggle(
            'open'
          );


        event.currentTarget
          .setAttribute(
            'aria-expanded',
            String(open)
          );
      }
    );


  document
    .getElementById(
      'annClearFilterBtn'
    )
    ?.addEventListener(
      'click',
      () => {

        if (roleFilter) {
          roleFilter.value =
            '';
        }


        if (priority) {
          priority.value =
            '';
        }


        if (search) {
          search.value =
            '';
        }


        selectStatus(
          ''
        );
      }
    );

});