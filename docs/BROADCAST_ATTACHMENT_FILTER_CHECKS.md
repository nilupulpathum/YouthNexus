# Announcement attachment editing and summary filters

Apply `broadcast-announcement-attachments-filters.patch` after the published-editing/styles patch on the existing `feat-broadcast-announcement` branch. This follow-up needs no SQL migration. It does not modify the earlier checklist or attachment-directory protection files.

## Behavior

- Existing attachments have Remove and Undo controls in the editor. Removal is staged until Save Changes, Save Draft or Publish succeeds. Cancel leaves the saved announcement and its files untouched.
- Newly selected files have Remove controls too. Browsing or dropping additional files adds them to the pending selection.
- A single save can remove old attachments and add replacements. Every removal ID must belong to the announcement owned by the current secretary in the current division.
- Database attachment deletions, additions, content edits and the audit record share a transaction. Old physical files are removed only after commit. A failed transaction retains the originals and cleans up newly uploaded files.
- Successful removal is a content edit: Edited advances while Created, Published and publication status stay unchanged. If post-commit physical cleanup fails, the download remains blocked and the server logs the cleanup failure.
- Search has a compact 320px desktop width; Filters aligns to the right. Search uses a full row on phones.
- Total Announcements, Published and Your Drafts are keyboard-accessible buttons. Selecting one clears search/audience/priority restrictions and displays its entire group, matching its count. Total shows all announcements visible to the signed-in official; Your Drafts includes only the author's drafts. Status dropdowns and selected-card styling stay synchronized.

## Check locally

1. Edit an announcement containing two files. Mark one for removal, then Undo. Save: both files should remain and an otherwise unchanged save should not advance Edited.
2. Mark a file for removal, then Cancel. Reopen the editor: the original remains available.
3. Remove one existing file, select a replacement and save. The other original remains; the replacement downloads correctly; the removed attachment's old download URL returns 404.
4. Remove all attachments without changing text. Save: no attachments remain; Edited advances; creation/publication dates stay unchanged.
5. Select files in two separate browse actions. Remove one pending upload. Save: only the remaining selected files should be uploaded.
6. Try replacing an attachment with an invalid file. The save must fail while the original remains downloadable. Remove the invalid selection and retry.
7. Click each summary card, including after entering a search or audience filter. Check the results match the selected card's count and the status dropdowns agree.
8. Change status through the dropdown, then Clear Filter. Check the selected summary card follows the current status.
9. Hard-refresh with Ctrl+F5. Check search width, right-aligned Filters, narrow-screen wrapping, long filenames and keyboard focus in your browser.

## Validation

78 isolated HTTP checks passed, including multi-file removal, duplicate removal IDs, stale/foreign IDs, ownership, CSRF, real upload/download bytes, replacement rollback, timestamps and path containment. Production JavaScript was also exercised with DOM/event stand-ins for summary-card filters, dropdown synchronization, repeated upload selection, Remove/Undo/Cancel and replacement request payloads. PHP/JavaScript syntax and Git whitespace checks passed.

HTTP fixtures use SQLite with minimal MySQL adaptation; they do not verify MySQL row locks or Apache configuration. DOM stand-ins verify interaction logic, not browser rendering. Complete the local browser checks above.

Implementation uses native PHP, PDO, JavaScript, HTML and CSS. Filtering is linear in the number of loaded announcements. Staged upload/removal metadata uses space proportional to the number of selected files; file transfer and disk work depend on the files' sizes. No application dependency, notification or email service was added.
