<?php
header('X-Robots-Tag: noindex, nofollow', true);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Login</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .auth-shell { max-width: 460px; margin: 24px auto; padding: 20px; }
        .auth-links { display: flex; gap: 12px; align-items: center; margin-top: 14px; }
        .auth-links a { cursor: pointer; text-decoration: underline; }
        .divider { margin: 12px 0; border-top: 1px solid #ddd; }
        .hidden { display: none; }
        .field-error { color: #b42318; font-size: 13px; margin-top: 4px; }
        .input-error { border-color: #b42318 !important; outline-color: #b42318 !important; }
        .hint { font-size: 13px; color: #555; margin-top: 8px; }
        #passwordLoginPane label,
        #otpPane label,
        #signupPane label { display: block; margin-top: 10px; margin-bottom: 4px; }
        #passwordLoginPane input,
        #otpPane input,
        #signupPane input,
        #signupPane select { display: block; width: 100%; box-sizing: border-box; }
        #passwordLoginPane button,
        #otpPane button,
        #signupPane button { display: block; margin-top: 12px; }
        .otp-request-row { display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: end; margin-top: 8px; }
        .otp-request-row input { margin: 0; }
        .otp-request-row button { margin: 0; }
        .signup-grid { display: grid; grid-template-columns: 120px 1fr; gap: 10px 12px; align-items: center; }
        .signup-grid label { margin: 0; text-align: left; }
        .signup-grid input,
        .signup-grid select { margin: 0; width: 100%; box-sizing: border-box; }
        .signup-email-error-row { grid-column: 2; margin-top: -6px; }
        .signup-actions { display: flex; justify-content: flex-end; margin-top: 14px; }
        .signup-actions button { margin-top: 0 !important; }
    </style>
</head>
<body>
    <div class="app-shell">
        <main class="panel auth-shell">
            <h2 id="formTitle">Login</h2>
            <p><a href="../">Back to calendar</a></p>
            <section id="passwordLoginPane">
                <label>Username</label>
                <input id="loginUsername" autocomplete="username">
                <label>Password</label>
                <input id="loginPassword" type="password" autocomplete="current-password">
                <button id="loginBtn" class="accent">Login</button>
                <div class="divider"></div>
                <div class="auth-links">
                    <a id="showOtpLink">Login with OTP</a>
                    <span>|</span>
                    <a id="showSignupLink">Sign up</a>
                </div>
            </section>

            <section id="otpPane" class="hidden">
                <label>Username (e-mail)</label>
                <div class="otp-request-row">
                    <input id="otpUsername" autocomplete="username">
                    <button id="otpRequestBtn">Send OTP</button>
                </div>
                <div id="otpAfterSend" class="hidden">
                    <label>OTP</label>
                    <input id="otpCode" placeholder="Enter OTP code">
                    <button id="otpVerifyBtn" class="accent">Login with OTP</button>
                    <p id="otpHint" class="hint"></p>
                </div>
                <div class="auth-links">
                    <a id="resendOtpLink" class="hidden">Resend OTP</a>
                    <a id="backToLoginFromOtp">Back to login</a>
                </div>
            </section>

            <section id="signupPane" class="hidden">
                <div class="signup-grid">
                    <label for="signupEmail">E-mail</label>
                    <input id="signupEmail" type="email" autocomplete="email" placeholder="name@example.com">
                    <div id="signupEmailError" class="field-error hidden signup-email-error-row">Please enter a valid email address.</div>
                    <label for="signupPassword">Password</label>
                    <input id="signupPassword" type="password" autocomplete="new-password">
                    <label for="signupFirst">Name</label>
                    <input id="signupFirst" autocomplete="given-name">
                    <label for="signupLast">Surname</label>
                    <input id="signupLast" autocomplete="family-name">
                    <label for="signupMemberId">ID</label>
                    <input id="signupMemberId" autocomplete="off">
                    <label for="signupCountry">Country</label>
                    <select id="signupCountry"></select>
                </div>
                <div class="signup-actions">
                    <button id="signupBtn" class="accent">Sign up</button>
                </div>
                <div class="auth-links">
                    <a id="backToLoginFromSignup">Back to login</a>
                </div>
            </section>
            <p id="authMessage"></p>
        </main>
    </div>
    <script src="./login.js"></script>
</body>
</html>
