<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="app-shell">
        <main class="panel" style="padding:16px;">
            <h2>Login</h2>
            <p><a href="../">Back to calendar</a></p>
            <div class="auth-grid">
                <section>
                    <h3>Password Login</h3>
                    <label>Username</label>
                    <input id="loginUsername">
                    <label>Password</label>
                    <input id="loginPassword" type="password">
                    <button id="loginBtn" class="accent">Login with Password</button>
                    <hr>
                    <h3>One-Time Password (OTP)</h3>
                    <label>Username</label>
                    <input id="otpUsername">
                    <button id="otpRequestBtn">Send OTP Code</button>
                    <label>OTP Code</label>
                    <input id="otpCode" placeholder="e.g. A9K2M4P8">
                    <button id="otpVerifyBtn" class="accent">Login with OTP</button>
                </section>
                <section>
                    <h3>Sign Up</h3>
                    <label>Username</label>
                    <input id="signupUsername">
                    <label>Email</label>
                    <input id="signupEmail" type="email" placeholder="name@example.com">
                    <label>Password</label>
                    <input id="signupPassword" type="password">
                    <label>Repeat Password</label>
                    <input id="signupPassword2" type="password">
                    <label>First Name</label>
                    <input id="signupFirst">
                    <label>Last Name</label>
                    <input id="signupLast">
                    <label>Primary Country</label>
                    <select id="signupCountry"></select>
                    <button id="signupBtn" class="accent">Create Account</button>
                </section>
            </div>
            <p id="authMessage"></p>
        </main>
    </div>
    <script src="./login.js"></script>
</body>
</html>
