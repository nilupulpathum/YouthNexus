# Broadcast announcement checks

The feature uses the existing PHP MVC structure, PDO, native JavaScript, and CSS. No framework, package, email, or notification service is required by these changes.

## Behavior

- The list and detail pages supply the configured application URL to JavaScript through `data-root`.
- Only the creating Divisional Secretary may view, edit, or publish a draft. Published announcements remain visible to the three existing divisional official roles in the same division.
- Saving an existing draft updates the same announcement ID, including when no fields have changed. Publishing locks and checks that draft before changing its status. Existing attachments are retained; additional uploads can be added.
- Uploads are validated before persistence. If persistence fails, the database transaction is rolled back and newly saved files are removed.
- Downloads use `/announcements/download/{attachment_id}` and check the parent announcement's access rules. The attachment directory denies direct Apache requests via `.htaccess`.
- Urgent is a visual priority. The unsupported Resend control and email-delivery promise have been removed.
- The member-facing feed on other branches is not integrated by this change.

## XAMPP verification

Use the existing database containing `Announcement`, `AnnouncementAttachment`, `AnnouncementRead`, and `AuditLog`. No schema changes are introduced. Use the real sign-in flow and existing test accounts; do not add a session-forging script to the app.

1. Sign in as a Divisional Secretary and open `/announcements` under the configured `ROOT`. In browser developer tools, confirm requests include the application's complete subdirectory path.
2. Create a draft with a title, body, optional category, and a small PDF or image. Leave the audience empty. Save Draft should open its detail page.
3. Use Edit Draft from both the list and detail page. Confirm the form contains the saved values and links to existing attachments.
4. Save without changing anything, then edit and save again. The announcement ID and number of records must stay the same.
5. Add another attachment. Confirm the original is retained and both downloads return the correct files.
6. Try an unsupported file or one larger than 10 MB. The form should report the error without partially saving the announcement.
7. Publish without an audience: validation should block it. Select an audience and publish: the same record should become Published and editing should no longer be available.
8. Open the same draft in two tabs; publish in one, then save in the other. The second request must be rejected without overwriting the publication.
9. As a different secretary or coordinator/treasurer, verify another person's draft is absent and direct edit/detail/download requests are rejected. Verify published announcements in another division are inaccessible.
10. Mark a published announcement as read. The badge should disappear. Repeating the request must not add a duplicate read row; requests without a valid CSRF token must fail.
11. Test attachment access through its download endpoint, then try its direct `/uploads/announcement_attachments/...` URL. The direct URL must be denied by Apache. `AllowOverride` must permit the committed `.htaccess` rules.
12. Test body-text search, no-match feedback, switching between status filters and tabs, and the list/modal at a narrow viewport.

## Validation performed during implementation

PHP syntax checks, JavaScript syntax checks, and isolated HTTP fixture tests cover the save/publish lifecycle, ownership/division checks, CSRF, real multipart uploads, actual download bytes, path containment, and rollback cleanup. The HTTP fixture uses SQLite with minimal MySQL dialect adaptation; it does not validate MySQL row-lock concurrency or Apache configuration. The XAMPP checks above cover those deployment-specific behaviors.
