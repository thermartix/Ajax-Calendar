<?php
session_start();
header('X-Robots-Tag: noindex, nofollow', true);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Speaker Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-shell">
        <header class="topbar">
            <div class="brand">
                <img id="brandLogo" src="assets/logo.png" alt="Immunotec Logo">
                <div>
                    <h1 id="speakerManageTitle">Speaker Management</h1>
                    <p id="speakerManageSubtitle">Create and maintain speaker profiles</p>
                </div>
            </div>
            <div class="header-right">
                <a href="./"><button type="button" id="speakerManageBackBtn">Back to calendar</button></a>
            </div>
        </header>

        <main class="panel speaker-admin-panel">
            <section class="speaker-admin-head">
                <button id="newSpeakerBtn" class="accent" type="button">New speaker</button>
            </section>
            <section id="speakerCards" class="speaker-cards"></section>
        </main>
    </div>

    <dialog id="speakerEditorDialog">
        <form id="speakerEditorForm" method="dialog" class="speaker-editor-form">
            <div class="event-form-head">
                <h3 id="speakerDialogTitle">Speaker profile</h3>
            </div>
            <div class="event-dialog-tabs">
                <button type="button" id="speakerTabEdit" class="event-dialog-tab is-active">Edit Form</button>
                <button type="button" id="speakerTabVisitor" class="event-dialog-tab">Visitor View</button>
            </div>

            <section id="speakerEditPanel" class="speaker-edit-grid">
                <input id="speakerId" type="hidden">
                <label for="speakerName">Name</label>
                <input id="speakerName" maxlength="180" required>

                <label for="speakerImage">Photo URL</label>
                <input id="speakerImage" maxlength="255" placeholder="/assets/uploads/... or https://...">

                <label for="speakerBio">Short info / Bio</label>
                <textarea id="speakerBio" rows="8" placeholder="Introduce the speaker..."></textarea>
            </section>

            <section id="speakerVisitorPanel" class="event-dialog-visitor-panel" hidden></section>

            <div class="dialog-actions">
                <button type="button" id="speakerCancelBtn">Cancel</button>
                <button type="submit" class="accent">Save</button>
            </div>
        </form>
    </dialog>

    <script>window.APP_I18N_BASE = 'includes/i18n/';</script>
    <script src="includes/i18n/en.js"></script>
    <script src="https://unpkg.com/i18next@23.16.4/dist/umd/i18next.min.js"></script>
    <script src="includes/i18n-loader.js"></script>
    <script src="speaker_manage.js"></script>
</body>
</html>
