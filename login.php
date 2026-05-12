<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login</title><link rel="stylesheet" href="style.css"></head>
<body><div class="app-shell"><main class="panel" style="padding:16px;"><h2>Login / Sign Up</h2><p><a href="./">Back to calendar</a></p>
<div class="auth-grid">
<section><h3>Login</h3><label>Username</label><input id="loginUsername"><label>Password</label><input id="loginPassword" type="password"><button id="loginBtn" class="accent">Login</button></section>
<section><h3>Sign Up</h3><label>Username</label><input id="signupUsername"><label>Password</label><input id="signupPassword" type="password"><label>Repeat Password</label><input id="signupPassword2" type="password"><label>First Name</label><input id="signupFirst"><label>Last Name</label><input id="signupLast"><div id="roleWrap"><label>Role</label><select id="signupRole"><option value="category_editor">Category Editor</option><option value="admin">Admin</option></select></div><label>Primary Country</label><select id="signupCountry"></select><button id="signupBtn" class="accent">Create Account</button></section>
</div><p id="authMessage"></p></main></div><script src="login.js"></script></body></html>
