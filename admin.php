<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Admin</title><link rel="stylesheet" href="style.css"></head>
<body><div class="app-shell"><main class="panel" style="padding:16px;"><h2>Admin</h2><p><a href="./">Back to calendar</a></p>
<section class="event-card"><h3>My Profile</h3><label>First Name</label><input id="profileFirst"><label>Last Name</label><input id="profileLast"><button id="saveProfile" class="accent">Save Profile</button></section>
<section id="adminOnly" hidden>
<article class="event-card"><h3>User approvals and permissions</h3><div id="usersRoot"></div></article>
<article class="event-card"><h3>Calendar Timezone</h3><select id="timezoneSelect"></select><button id="saveTimezone" class="accent">Save Timezone</button></article>
<article class="event-card"><h3>Countries</h3><p>One line per country in format CODE|Name</p><textarea id="countriesText" rows="8"></textarea><br><button id="saveCountries" class="accent">Save Countries</button></article>
</section><p id="adminMsg"></p></main></div><script src="admin.js"></script></body></html>
