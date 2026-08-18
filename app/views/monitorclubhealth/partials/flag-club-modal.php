<div class="mch-modal-backdrop" id="mchFlagModal" role="dialog" aria-modal="true" aria-labelledby="mchFlagModalTitle">
    <div class="mch-modal-card mch-flag-modal">
        <!-- Header -->
        <div class="mch-modal-header">
            <h2 class="mch-modal-title" id="mchFlagModalTitle">Flag Club for NYSC Admin Review</h2>
            <button type="button" class="mch-modal-close-btn" id="mchFlagCloseBtn" aria-label="Close modal">&times;</button>
        </div>

        <!-- Body -->
        <div class="mch-modal-body">
            <div class="mch-flag-info-box">
                <div class="mch-flag-info-row">
                    <span>Club</span>
                    <b id="mchFlagClubName">Maharagama Coolers</b>
                </div>
                <div class="mch-flag-info-row">
                    <span>Overall Health Score</span>
                    <b id="mchFlagScore">15 / 100</b>
                </div>
                <div class="mch-flag-info-row">
                    <span>Trigger</span>
                    <span>Score &lt; 30 for 3 consecutive months</span>
                </div>
            </div>

            <div class="mch-form-group">
                <label for="mchFlagSeverity">Severity</label>
                <select id="mchFlagSeverity" class="mch-form-select">
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>

            <div class="mch-form-group">
                <label for="mchFlagComment">Comment (required)</label>
                <textarea id="mchFlagComment" class="mch-form-textarea" placeholder="Describe the governance concern for NYSC Admin..."></textarea>
            </div>

            <div class="mch-flag-notice">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15" stroke="currentColor" stroke-width="2"/></svg>
                <span>Only NYSC Admin can disband a club — this submits a flag and notification, it does not disband anything.</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="mch-modal-footer" style="justify-content: flex-end;">
            <button type="button" class="mch-btn-modal-secondary" id="mchFlagCancelBtn">Cancel</button>
            <button type="button" class="mch-btn-modal-primary" id="mchFlagSubmitBtn">Submit Flag to NYSC Admin</button>
        </div>
    </div>
</div>
