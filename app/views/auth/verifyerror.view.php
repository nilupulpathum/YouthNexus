<style>
  .error-icon  { position: relative; width: 80px; height: 80px; margin: 0 auto 25px; }
  .error-circle { position: absolute; border-radius: 50%; top: 50%; left: 50%; transform: translate(-50%,-50%); }
  .error-circle.big   { width: 80px; height: 80px; background: #ffe5e5; }
  .error-circle.small { width: 50px; height: 50px; background: #ffcccc; }
  .error-x { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); color: #cc0000; font-size: 30px; font-weight: bold; }
  .error-btn.try    { background: #002d72; color: #fff; }
  .error-btn.resend { background: #fff; color: #002d72; border: 1px solid #ccc; }
  .error-footer { font-size: 12px; color: #666; margin-top: 10px; line-height: 1.5; }
  .error-footer a { color: #002d72; text-decoration: none; font-weight: bold; }
</style>

<div class="error-overlay">
  <div class="error-box">
    <div class="error-icon">
      <div class="error-circle big"></div>
      <div class="error-circle small"></div>
      <div class="error-x">✕</div>
    </div>
    <h2>Verification Failed</h2>
    <p>The 6-digit code you entered is incorrect or has expired. Please double-check your inbox or request a new code.</p>
    <a href="<?= ROOT ?>/auth/verify" class="error-btn try">Try Again</a>
    <a href="<?= ROOT ?>/auth/verify?resend=1" class="error-btn resend">↻ Resend Code</a>
    <div class="error-footer">
      Didn't receive the email? Check your spam folder or <a href="#">Contact Support</a>
    </div>
  </div>
</div>
