<div class="mch-modal-backdrop" id="mchDetailModal" role="dialog" aria-modal="true" aria-labelledby="mchModalTitle">
    <div class="mch-modal-card mch-detail-modal">
        <!-- Modal Header -->
        <div class="mch-modal-header">
            <div class="mch-modal-title-group">
                <div class="mch-modal-avatar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div>
                    <h2 class="mch-modal-title" id="mchModalTitle">Club Name</h2>
                    <div class="mch-modal-sub-badges">
                        <span class="mch-modal-code-tag" id="mchModalCode">CLB-000000</span>
                        <span class="mch-modal-status-pill green" id="mchModalStatusPill">• Healthy (Green)</span>
                    </div>
                </div>
            </div>
            <button type="button" class="mch-modal-close-btn" id="mchDetailCloseBtn" aria-label="Close modal">&times;</button>
        </div>

        <!-- Modal Body -->
        <div class="mch-modal-body">
            <div class="mch-detail-columns">
                <!-- Left Column: About + Executive Committee -->
                <div>
                    <div class="mch-section-heading-sm">About</div>
                    <p class="mch-about-text" id="mchModalDesc">
                        No description provided.
                    </p>

                    <div class="mch-meta-row">
                        <!-- Issue #1 Fixed: Removed hardcoded Category field -->
                        <div class="mch-meta-field">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>Location: <b id="mchModalLocation">Division</b></span>
                        </div>
                        <div class="mch-meta-field">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <span>Established: <b id="mchModalEstDate">Not available</b></span>
                        </div>
                    </div>

                    <!-- Issue #5 Fixed: Dynamic Executive Committee list -->
                    <div style="margin-top: 24px;">
                        <div class="mch-section-heading-sm">Executive Committee</div>
                        <div class="mch-exec-list" id="mchModalExecList">
                            <!-- Populated dynamically via JS from real User records -->
                        </div>
                    </div>
                </div>

                <!-- Right Column: Performance Overview + Recent Events -->
                <div>
                    <div class="mch-section-heading-sm">Performance Overview</div>
                    <div class="mch-perf-grid">
                        <div class="mch-perf-card">
                            <div class="mch-perf-card-info">
                                <span>Active Members</span>
                                <b id="mchModalActiveMembers">0</b>
                            </div>
                            <div class="mch-perf-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                        </div>

                        <!-- Issue #4 Fixed: Avg. Attendance stat card removed — pending Attendance table in future phase -->
                    </div>

                    <!-- Issue #6 Fixed: Recent Events placeholder container (no fake rows) -->
                    <div class="mch-events-header">
                        <div class="mch-section-heading-sm" style="margin-bottom: 0;">Recent Events</div>
                    </div>
                    <div class="mch-pending-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <p>Event tracking will be available once the Events module is implemented.</p>
                    </div>
                </div>
            </div>

            <!-- Issue #7 Fixed: Health Detail Breakdown (pending state, real overall score) -->
            <div class="mch-health-breakdown">
                <div class="mch-section-heading-sm">Health Detail</div>
                
                <div class="mch-pending-box" style="margin-bottom: 14px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                    <p>Not yet calculated — pending Event/Finance/Attendance modules</p>
                </div>

                <div class="mch-overall-row">
                    <span>Overall Health Score</span>
                    <b id="mchModalOverallScore">0 / 100</b>
                </div>

                <div class="mch-callout-warning">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <span>Overall Health Score is &lt; 30 for 3 months, or a governance issue — this club is read-only and cannot be disbanded except by NYSC Admin.</span>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="mch-modal-footer">
            <button type="button" class="mch-btn-modal-flag" id="mchOpenFlagModalBtn">
                Flag for NYSC Admin Review
            </button>
            <div class="mch-modal-footer-right">
                <button type="button" class="mch-btn-modal-secondary" id="mchEditDetailsBtn">
                    Edit Details
                </button>
                <button type="button" class="mch-btn-modal-primary" id="mchDownloadReportBtn">
                    Download Report
                </button>
            </div>
        </div>
    </div>
</div>
