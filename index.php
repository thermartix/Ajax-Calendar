<?php
session_start();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajax Calendar Clone</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-shell">
        <header class="topbar">
            <div class="brand">
                <img id="brandLogo" src="assets/logo.svg" alt="Logo">
                <div>
                    <h1>Events Calendar</h1>
                    <p>Country-based schedule and meeting planner</p>
                </div>
            </div>
            <div class="auth" id="authBlock"></div>
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
                    <button id="newEventBtn" class="accent" hidden>New Event</button>
                </div>
            </section>

            <section id="calendarRoot" class="calendar-root"></section>
        </main>
    </div>

    <dialog id="eventDialog">
        <form id="eventForm" method="dialog">
            <h3 id="eventDialogTitle">New Event</h3>
            <input type="hidden" id="eventId">

            <label for="eventTitle">Title</label>
            <input id="eventTitle" required maxlength="180">

            <label for="eventDescription">Description</label>
            <textarea id="eventDescription" rows="4" placeholder="Agenda, notes, speakers..."></textarea>

            <label for="eventLink">Event Link</label>
            <input id="eventLink" type="url" placeholder="https://zoom.us/... or https://event-site.com">

            <label for="eventCountry">Country</label>
            <select id="eventCountry"></select>

            <label for="eventStart">Start</label>
            <input id="eventStart" type="datetime-local" required>

            <label for="eventEnd">End</label>
            <input id="eventEnd" type="datetime-local" required>

            <div class="dialog-actions">
                <button type="button" id="deleteEventBtn" class="danger" hidden>Delete</button>
                <button type="button" id="cancelEventBtn">Cancel</button>
                <button type="submit" class="accent">Save</button>
            </div>
        </form>
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
                    <option value="category_editor">Category Editor</option>
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