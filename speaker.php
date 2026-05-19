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
    <title>Speaker Profile</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-shell">
        <header class="topbar">
            <div class="brand"><h1 id="speakerName">Speaker</h1></div>
            <div class="header-right"><a href="./"><button type="button" id="speakerBackBtn">Back to calendar</button></a></div>
        </header>
        <main class="panel" style="padding:16px;">
            <article class="event-card">
                <div id="speakerImageWrap"></div>
                <p id="speakerBio" style="white-space: pre-line;"></p>
            </article>
            <article class="event-card">
                <h3 id="speakerRecentEventsTitle">Recent events</h3>
                <div id="speakerEvents"></div>
            </article>
        </main>
    </div>
    <script>window.APP_I18N_BASE = 'includes/i18n/';</script>
    <script src="includes/i18n/en.js"></script>
    <script src="https://unpkg.com/i18next@23.16.4/dist/umd/i18next.min.js"></script>
    <script src="includes/i18n-loader.js"></script>
    <script src="speaker.js"></script>
</body>
</html>
