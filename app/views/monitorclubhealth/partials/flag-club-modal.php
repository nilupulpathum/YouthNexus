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
            </div>

            <div class="mch-flag-signals-box">
                <div class="mch-flag-signals-title">Relevant Signals for Your Review</div>

                <?php if (($_SESSION['user_role'] ?? '') === 'DivisionalSecretary'): ?>
                    <div class="mch-flag-signal-row">
                        <span>Events Conducted (Last 3 Months)</span>
                        <b id="mchFlagEventsConducted">0</b>
                    </div>
                    <div class="mch-flag-signal-row">
                        <span>Average Attendance Rate</span>
                        <b id="mchFlagAvgRate">—</b>
                    </div>
                    <p class="mch-flag-signals-note">These are informational only — you decide the severity, the system does not.</p>

                <?php elseif (($_SESSION['user_role'] ?? '') === 'DivisionalCoordinator'): ?>
                    <div class="mch-flag-signal-row">
                        <span>Events Conducted (Last 3 Months)</span>
                        <b id="mchFlagEventsConducted">0</b>
                    </div>
                    <div class="mch-flag-signal-row">
                        <span>Average Attendance Rate</span>
                        <b id="mchFlagAvgRate">—</b>
                    </div>
                    <div class="mch-flag-signal-row">
                        <span>Financial Standing</span>
                        <b class="mch-flag-signal-unavailable">Not available — Finance module not yet built</b>
                    </div>

                    <div id="mchFlagPriorFlagsContainer" style="display: none;">
                        <div class="mch-flag-prior-flags">
                            <div class="mch-flag-prior-flags-title">Prior Flags Raised on This Club</div>
                            <div id="mchFlagPriorFlagsList"></div>
                        </div>
                    </div>
                <?php endif; ?>
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
