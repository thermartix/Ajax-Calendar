<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-shell">
        <header class="topbar">
            <div class="brand">
                <img id="brandLogo" src="assets/logo.png" alt="Immunotec Logo">
                <div>
                    <h1>Admin Settings</h1>
                    <p>Global calendar and account configuration</p>
                </div>
            </div>
            <div class="header-right">
                <a href="./"><button type="button">Back to calendar</button></a>
            </div>
        </header>

        <main class="panel" style="padding:16px;">
            <section class="event-card">
                <h3>My Profile</h3>
                <label for="profileFirst">First Name</label>
                <input id="profileFirst">
                <label for="profileLast">Last Name</label>
                <input id="profileLast">
                <label for="profileEmail">E-mail</label>
                <input id="profileEmail" readonly>
                <label for="profileNewPassword">New Password</label>
                <input id="profileNewPassword" type="password">
                <label for="profileNewPassword2">Repeat New Password</label>
                <input id="profileNewPassword2" type="password">
                <div class="dialog-actions">
                    <button id="saveProfile" class="accent">Save Profile</button>
                </div>
            </section>

            <section id="adminOnly" hidden>
                <article class="event-card">
                    <h3>Global Calendar Settings</h3>
                    <label for="timezoneSelect">Calendar Timezone</label>
                    <select id="timezoneSelect"></select>
                    <label for="showEventAuthorToggle" style="display:block; margin-top:10px;">
                        <input type="checkbox" id="showEventAuthorToggle" checked>
                        Show "by author name" on event detail (global)
                    </label>
                    <div class="dialog-actions">
                        <button id="saveTimezone" class="accent">Save Global Settings</button>
                    </div>
                </article>

                <article class="event-card">
                    <h3>User Approvals and Permissions</h3>
                    <div id="usersRoot"></div>
                </article>

                <article class="event-card">
                    <h3>Countries</h3>
                    <p>One line per country in format <code>CODE|Name</code></p>
                    <textarea id="countriesText" rows="10"></textarea>
                    <div class="dialog-actions">
                        <button id="saveCountries" class="accent">Save Countries</button>
                    </div>
                </article>
            </section>

            <p id="adminMsg"></p>
        </main>
    </div>
    <script src="admin.js"></script>
</body>
</html>
