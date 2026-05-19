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
    <title>Immunotec Zoom and Event calendar</title>
    <link rel="icon" href="/s/assets/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-shell">
        <header class="topbar">
            <div class="brand">
                <img id="brandLogo" src="assets/logo.png" alt="Immunotec Logo">
                <div>
                    <h1 id="appTitle">Event Calendar</h1>
                    <div id="audienceLegend" class="audience-legend"></div>
                </div>
            </div>
            <div class="header-right">
                <div class="auth" id="langBlock"></div>
                <div class="auth" id="authBlock"></div>
            </div>
        </header>

        <main class="panel">
            <section class="toolbar">
                <div class="nav-group">
                    <button id="prevBtn">Previous</button>
                    <button id="todayBtn">Today</button>
                    <button id="nextBtn">Next</button>
                    <h2 id="rangeLabel"></h2>
                </div>
                <div class="controls-group">
                    <select id="viewSelect">
                        <option value="month">Month</option>
                        <option value="year">Year</option>
                        <option value="week">Week</option>
                        <option value="day">Day</option>
                        <option value="list">List</option>
                    </select>
                    <select id="countryFilter"></select>
                    <select id="languageFilter">
                        <option value="">All languages</option>
                    </select>
                    <button id="newEventBtn" class="accent" hidden>New Event</button>
                </div>
            </section>

            <section id="calendarRoot" class="calendar-root"></section>
        </main>
    </div>

    <dialog id="eventDialog">
        <form id="eventForm" method="dialog">
            <div class="event-form-head">
                <h3 id="eventDialogTitle">New Event</h3>
                <button type="button" id="copyEventBtn" class="accent small-action" hidden>Copy Event</button>
            </div>
            <div id="eventDialogTabs" class="event-dialog-tabs" hidden>
                <button type="button" id="eventDialogTabEdit" class="event-dialog-tab is-active">Edit Form</button>
                <button type="button" id="eventDialogTabVisitor" class="event-dialog-tab">Visitor View</button>
            </div>
            <div id="eventDialogVisitorHint" class="event-dialog-visitor-hint" hidden>Visitor view is available for saved events.</div>
            <input type="hidden" id="eventId">
            <input type="hidden" id="copyFromId">
            <div id="eventDialogEditPanel">
            <div id="eventFormImagePreview" class="event-form-image-preview"></div>

            <label for="eventTitle">Title</label>
            <input id="eventTitle" required maxlength="180">

            <div class="description-mode-head">
                <label for="eventDescription">Description</label>
                <div class="event-mode-inline">
                    <label id="eventModeLabel">Event type</label>
                    <div class="option-group" role="radiogroup" aria-labelledby="eventModeLabel">
                        <label class="option-chip" for="eventModeOnline">
                            <input id="eventModeOnline" type="radio" name="eventMode" value="online" checked>
                            <span id="eventModeOnlineLabel">Online</span>
                        </label>
                        <label class="option-chip" for="eventModeOffline">
                            <input id="eventModeOffline" type="radio" name="eventMode" value="offline">
                            <span id="eventModeOfflineLabel">Offline</span>
                        </label>
                    </div>
                </div>
            </div>
            <textarea id="eventDescription" rows="4" placeholder="Agenda, notes, speakers..."></textarea>

            <section class="event-datetime-block">
                <div class="event-datetime-row">
                    <label for="eventStartDate">Event date</label>
                    <div class="dt-row">
                        <input id="eventStartDate" type="text" required placeholder="DD.MM.YYYY">
                        <button type="button" id="eventStartDatePickBtn">Pick</button>
                        <input id="eventStartDatePicker" type="date" class="picker-proxy">
                    </div>
                    <label class="switch-row" for="eventMultiDay">
                        <input id="eventMultiDay" type="checkbox" value="1">
                        <span>Multi day event</span>
                    </label>
                </div>
                <div id="eventEndDateWrap" class="event-datetime-row" hidden>
                    <label for="eventEndDate">End date</label>
                    <div class="dt-row">
                        <input id="eventEndDate" type="text" placeholder="DD.MM.YYYY">
                        <button type="button" id="eventEndDatePickBtn">Pick</button>
                        <input id="eventEndDatePicker" type="date" class="picker-proxy">
                    </div>
                </div>
                <div class="event-datetime-row">
                    <label for="eventStartTime">Start time</label>
                    <input id="eventStartTime" type="text" required placeholder="HH:MM">
                    <label for="eventEndTime">End time</label>
                    <input id="eventEndTime" type="text" required placeholder="HH:MM">
                </div>
            </section>

            <div id="eventOnlineWrap">
                <label for="eventLinkOnline" id="eventLinkLabel">Meeting Link</label>
                <input id="eventLinkOnline" type="url" placeholder="https://zoom.us/... or https://event-site.com">
            </div>

            <div id="eventOfflineWrap">
                <label for="eventVenueAddress" id="eventVenueAddressLabel">Venue Address</label>
                <textarea id="eventVenueAddress" rows="3" placeholder="Street, city, ZIP..."></textarea>
                <label for="eventTicketUrl" id="eventTicketUrlLabel">Ticket URL</label>
                <input id="eventTicketUrl" type="url" placeholder="https://tickets.example.com/event">
                <label class="switch-row" for="eventSoldOut">
                    <input id="eventSoldOut" type="checkbox" value="1">
                    <span id="eventSoldOutSwitchLabel">Sold out</span>
                </label>
                <label for="eventVenueImage" id="eventVenueImageLabel">Venue Photo</label>
                <input id="eventVenueImage" type="file" accept=".jpg,.jpeg,.png,.webp">
                <div id="eventVenueImagePreview" class="event-form-image-preview"></div>
            </div>

            <label id="eventAudienceTypeLabel">Audience</label>
            <div class="option-group option-group-wrap" role="radiogroup" aria-labelledby="eventAudienceTypeLabel">
                <label class="option-chip" for="eventAudienceGuests">
                    <input id="eventAudienceGuests" type="radio" name="eventAudienceType" value="customers_guests" checked>
                    <span id="eventAudienceGuestsLabel">Customers and guests</span>
                </label>
                <label class="option-chip" for="eventAudienceConsultantMeeting">
                    <input id="eventAudienceConsultantMeeting" type="radio" name="eventAudienceType" value="consultant_meeting">
                    <span id="eventAudienceConsultantMeetingLabel">Consultant meeting</span>
                </label>
                <label class="option-chip" for="eventAudienceConsultantTraining">
                    <input id="eventAudienceConsultantTraining" type="radio" name="eventAudienceType" value="consultant_training">
                    <span id="eventAudienceConsultantTrainingLabel">Consultant training</span>
                </label>
            </div>
            <label for="eventImage">Header Image (1200x420 recommended)</label>
            <input id="eventImage" type="file" accept=".jpg,.jpeg,.png,.webp">

            <label for="eventCountry">Country</label>
            <select id="eventCountry" multiple size="6"></select>

            <label for="eventLanguageCountry">Event Language</label>
            <select id="eventLanguageCountry"></select>

            <label for="eventInterpretationCountries">Interpretation</label>
            <select id="eventInterpretationCountries" multiple size="5"></select>

            <input id="eventStart" type="hidden">
            <input id="eventEnd" type="hidden">
            <input id="eventStartPicker" type="hidden">
            <input id="eventEndPicker" type="hidden">

            <label for="eventRecurrenceType">Recurrence</label>
            <select id="eventRecurrenceType">
                <option value="none">No recurrence</option>
                <option value="monthly_nth_weekday">Monthly: nth weekday</option>
            </select>
            <div id="recurrenceMonthlyWrap">
                <label for="eventRecurWeek">Week in month</label>
                <select id="eventRecurWeek" multiple size="5">
                    <option value="1">1st</option>
                    <option value="2">2nd</option>
                    <option value="3">3rd</option>
                    <option value="4">4th</option>
                    <option value="5">5th</option>
                </select>
                <label for="eventRecurWeekday">Weekday</label>
                <select id="eventRecurWeekday">
                    <option value="1">Monday</option>
                    <option value="2">Tuesday</option>
                    <option value="3">Wednesday</option>
                    <option value="4">Thursday</option>
                    <option value="5">Friday</option>
                    <option value="6">Saturday</option>
                    <option value="0">Sunday</option>
                </select>
                <label for="eventRecurrenceUntil">Recurrence End (optional)</label>
                <div class="dt-row">
                    <input id="eventRecurrenceUntil" placeholder="DD/MM/YYYY HH:MM">
                    <button type="button" id="eventRecurrenceUntilPickBtn">Pick</button>
                    <input id="eventRecurrenceUntilPicker" type="datetime-local" class="picker-proxy">
                </div>
                <section id="hiddenOccurrencesWrap" class="hidden-occurrences-wrap" hidden>
                    <h4>Hidden events</h4>
                    <table class="hidden-occurrences-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="hiddenOccurrencesBody"></tbody>
                    </table>
                </section>
            </div>
            </div>
            <section id="eventDialogVisitorPanel" class="event-dialog-visitor-panel" hidden></section>

            <div class="dialog-actions">
                <button type="button" id="deleteEventBtn" class="danger" hidden>Delete</button>
                <button type="button" id="cancelEventBtn">Cancel</button>
                <button type="submit" class="accent">Save</button>
            </div>
        </form>
    </dialog>

    <dialog id="eventViewDialog">
        <article class="event-view">
            <button type="button" id="shareEventBtn" class="share-btn" title="Copy direct link">Share</button>
            <button type="button" id="closeEventViewIconBtn" class="close-icon-btn" title="Close">×</button>
            <div id="eventViewHero" class="event-view-hero"></div>
            <div class="event-view-body">
                <h3 id="eventViewTitle"></h3>
                <div id="eventViewCountriesRow"></div>
                <p id="eventViewMeta" class="event-meta"></p>
                <p id="eventViewRecurrence" class="event-meta"></p>
                <p id="eventViewTicketWrap"></p>
                <p id="eventViewDescription"></p>
                <div class="event-view-main">
                    <div class="event-view-info">
                        <div id="eventViewLinkWrap"></div>
                        <div id="eventViewQrWrap"></div>
                    </div>
                    <div class="event-view-qr">
                        <img id="eventViewQrImg" alt="QR code">
                    </div>
                </div>
                <p id="eventViewAuthor" class="event-author"></p>
            </div>
        </article>
    </dialog>

    <dialog id="profileDialog">
        <form id="profileForm" method="dialog">
            <h3 id="profileDialogTitle">Profile</h3>
            <div class="profile-grid profile-stack">
                <div class="profile-row">
                    <label for="profileFirstName">First Name</label>
                    <input id="profileFirstName">
                </div>
                <div class="profile-row">
                    <label for="profileLastName">Last Name</label>
                    <input id="profileLastName">
                </div>
                <div class="profile-row">
                    <label for="profileMemberId">ID</label>
                    <input id="profileMemberId">
                </div>
                <div class="profile-row" id="profileCountryRow">
                    <label for="profileCountry">Default Country</label>
                    <select id="profileCountry"></select>
                </div>
                <div class="profile-row">
                    <label for="profileDatetimeFormat">Date/Time Format</label>
                    <select id="profileDatetimeFormat">
                        <option value="us">US (MM/DD/YYYY, 12h)</option>
                        <option value="eu">European (DD/MM/YYYY, 24h)</option>
                    </select>
                </div>
            </div>
            <div class="profile-password-block">
                <h4>Change Password</h4>
                <div class="profile-grid profile-stack">
                    <div class="profile-row">
                        <label for="profileCurrentPassword">Current Password</label>
                        <input id="profileCurrentPassword" type="password" autocomplete="current-password">
                    </div>
                    <div class="profile-row">
                        <label for="profileNewPassword">New Password</label>
                        <input id="profileNewPassword" type="password">
                    </div>
                    <div class="profile-row">
                        <label for="profileNewPassword2">Repeat New Password</label>
                        <input id="profileNewPassword2" type="password">
                    </div>
                </div>
            </div>
            <div class="dialog-actions">
                <button type="button" id="cancelProfileBtn">Cancel</button>
                <button type="submit" class="accent">Save</button>
            </div>
        </form>
    </dialog>

    <dialog id="errorDialog">
        <div class="error-dialog-body">
            <h3>Error Details</h3>
            <textarea id="errorDialogText" rows="10" readonly></textarea>
            <div class="dialog-actions">
                <button type="button" id="copyErrorBtn">Copy</button>
                <button type="button" id="closeErrorBtn">Close</button>
            </div>
        </div>
    </dialog>

    <dialog id="unsavedChangesDialog">
        <div class="error-dialog-body">
            <h3 id="unsavedChangesTitle">Unsaved changes</h3>
            <p id="unsavedChangesText">Do you want to save your changes before closing?</p>
            <div class="dialog-actions">
                <button type="button" id="unsavedCancelBtn">Cancel</button>
                <button type="button" id="unsavedDiscardBtn">Discard</button>
                <button type="button" id="unsavedSaveBtn" class="accent">Save</button>
            </div>
        </div>
    </dialog>

    <dialog id="recurringDeleteDialog">
        <div class="error-dialog-body">
            <h3>Delete Recurring Event</h3>
            <p>Do you really want to delete this meeting offering?</p>
            <div class="dialog-actions">
                <button type="button" id="recurringDeleteCancelBtn">Cancel</button>
                <button type="button" id="recurringDeleteOccurrenceBtn" class="accent">Only this event</button>
                <button type="button" id="recurringDeleteFromHereBtn">All from here</button>
                <button type="button" id="recurringDeleteSeriesBtn" class="danger">All in this series</button>
            </div>
        </div>
    </dialog>

    <dialog id="recurringSaveScopeDialog">
        <div class="error-dialog-body">
            <h3>Save Recurring Event Changes</h3>
            <p>Should these changes apply only to this event or to all events in the series?</p>
            <div class="dialog-actions">
                <button type="button" id="recurringSaveScopeCancelBtn">Cancel</button>
                <button type="button" id="recurringSaveScopeOccurrenceBtn" class="accent">Only this event</button>
                <button type="button" id="recurringSaveScopeSeriesBtn">All events in series</button>
            </div>
        </div>
    </dialog>

    <dialog id="recurringOverwriteOverridesDialog">
        <div class="error-dialog-body">
            <h3>Overwrite Individual Event Edits?</h3>
            <p id="recurringOverwriteOverridesText"></p>
            <div class="dialog-actions">
                <button type="button" id="recurringOverwriteNoBtn">No (keep individual edits)</button>
                <button type="button" id="recurringOverwriteYesBtn" class="danger">Yes (overwrite all)</button>
            </div>
        </div>
    </dialog>

    <dialog id="authDialog">
        <div class="auth-grid">
            <section>
                <h3>Login</h3>
                <label for="loginUsername">Username</label>
                <input id="loginUsername">
                <label for="loginPassword">Password</label>
                <input id="loginPassword" type="password">
                <button id="loginBtn" class="accent">Login</button>
            </section>
            <section>
                <h3>Sign Up</h3>
                <label for="signupUsername">Username</label>
                <input id="signupUsername">
                <label for="signupPassword">Password</label>
                <input id="signupPassword" type="password">
                <label for="signupPassword2">Repeat Password</label>
                <input id="signupPassword2" type="password">
                <label for="signupRole">Role</label>
                <select id="signupRole">
                    <option value="editor">Editor</option>
                    <option value="admin">Admin</option>
                </select>
                <label for="signupCountry">Country (for category editor)</label>
                <select id="signupCountry"></select>
                <button id="signupBtn" class="accent">Create Account</button>
            </section>
        </div>
        <p id="authMessage"></p>
    </dialog>

    <script src="main.js"></script>
</body>
</html>
