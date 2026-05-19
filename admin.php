<?php
header('X-Robots-Tag: noindex, nofollow', true);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
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
                    <h3>Add User</h3>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 8px 12px; align-items:end;">
                        <div>
                            <label for="newUserFirst">First name</label>
                            <input id="newUserFirst">
                        </div>
                        <div>
                            <label for="newUserLast">Last name</label>
                            <input id="newUserLast">
                        </div>
                        <div>
                            <label for="newUserEmail">E-mail</label>
                            <input id="newUserEmail" type="email">
                        </div>
                        <div>
                            <label for="newUserId">ID</label>
                            <input id="newUserId">
                        </div>
                        <div style="grid-column: 1 / span 2;">
                            <label for="newUserRole">User level</label>
                            <select id="newUserRole">
                                <option value="visitor">visitor</option>
                                <option value="editor">editor</option>
                                <option value="admin">admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="dialog-actions">
                        <button id="addUserBtn" class="accent">Add user</button>
                    </div>
                </article>

                <article class="event-card">
                    <h3>Bulk Add Users (CSV)</h3>
                    <p>Columns: <code>name,surname,e-mail,ID,user level</code></p>
                    <div class="dialog-actions" style="justify-content:flex-start; margin-top:0;">
                        <button id="downloadUsersCsvTemplateBtn" type="button">Download CSV template</button>
                    </div>
                    <input id="usersCsvFile" type="file" accept=".csv,text/csv">
                    <div class="dialog-actions">
                        <button id="importUsersBtn" class="accent">Import CSV</button>
                    </div>
                </article>

                <article class="event-card">
                    <h3>User Approvals and Permissions</h3>
                    <label for="usersFilter">Find user</label>
                    <input id="usersFilter" placeholder="Search by email, ID, name, role...">
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
