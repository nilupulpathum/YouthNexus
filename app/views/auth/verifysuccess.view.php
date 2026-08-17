<style>
  .popup-top { background: #e2e8f0; height: 85px; position: relative; }
  .popup-dot { position: absolute; border-radius: 50%; }
  .popup-dot.pink { width: 26px; height: 26px; background: #e8c4b8; top: 14px; right: 85px; }
  .popup-dot.blue { width: 18px; height: 18px; background: #c8d4e5; bottom: 8px; left: 85px; }
  .popup-top .popup-icon {
    width: 72px; height: 72px; background: #002d72; border-radius: 50%;
    position: absolute; bottom: -36px; left: 50%; transform: translateX(-50%);
    display: flex; align-items: center; justify-content: center;
  }
  .popup-top .popup-icon svg { width: 28px; height: 28px; }
  .popup-content { padding: 52px 25px 25px; text-align: center; }
  .popup-content h2 { color: #1a202c; font-size: 22px; margin-bottom: 10px; }
  .popup-content p  { color: #666; font-size: 14px; margin-bottom: 25px; }
  .popup-bottom { height: 5px; background: #002d72; }
</style>

<div class="popup-overlay">
  <div class="popup-box" style="padding:0;">
    <div class="popup-top">
      <div class="popup-dot pink"></div>
      <div class="popup-dot blue"></div>
      <div class="popup-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
    </div>
    <div class="popup-content">
      <h2>Email Verified Successfully</h2>
      <p>Your account has been created and verified.</p>
      <a href="<?= ROOT ?>/registration/step1" class="popup-btn">Continue &rarr;</a>
    </div>
    <div class="popup-bottom"></div>
  </div>
</div>
