<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - YouthNexus</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f7fb;
            color: #333;
            line-height: 1.6;
        }

        /* Navbar */
        .navbar {
            background-color: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-logo {
            font-size: 20px;
            font-weight: bold;
            color: #1a237e;
            text-decoration: none;
        }

        .nav-links a {
            color: #666;
            text-decoration: none;
            margin-left: 28px;
            font-size: 14px;
        }

        .nav-links a.active {
            color: #1a237e;
            font-weight: bold;
            border-bottom: 2px solid #1a237e;
            padding-bottom: 4px;
        }

        /* Container */
        .container {
            max-width: 860px;
            margin: 0 auto;
            padding: 35px 20px;
        }

        /* Page Header */
        .page-title {
            font-size: 26px;
            font-weight: bold;
            color: #1a237e;
            margin-bottom: 12px;
        }

        .meta-line {
            display: flex;
            gap: 25px;
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 25px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .meta-item svg {
            width: 14px;
            height: 14px;
            fill: #666;
        }

        /* Quote Box */
        .quote-box {
            background-color: #eef2ff;
            border-left: 4px solid #1a237e;
            padding: 18px 22px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 30px;
            font-style: italic;
            color: #555;
            font-size: 14px;
            line-height: 1.7;
        }

        /* Section Cards */
        .section {
            background-color: #fff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 18px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .section-icon {
            width: 22px;
            height: 22px;
            fill: #1a237e;
            flex-shrink: 0;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1a237e;
        }

        .section p {
            font-size: 14px;
            color: #555;
            line-height: 1.7;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .badge-critical {
            background-color: #fff3e0;
            color: #e65100;
            margin-left: auto;
            text-transform: uppercase;
        }

        .badge-protocol {
            background-color: rgba(255,255,255,0.15);
            color: #fff;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 4px 12px;
            margin-bottom: 10px;
        }

        /* Alert Box */
        .alert-box {
            background-color: #fff8e1;
            border-left: 3px solid #ffb300;
            padding: 12px 15px;
            border-radius: 0 6px 6px 0;
            margin-top: 15px;
            font-size: 13px;
            color: #e65100;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-box svg {
            width: 18px;
            height: 18px;
            fill: #e65100;
            flex-shrink: 0;
        }

        /* Dark Section */
        .section-dark {
            background-color: #1a237e;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .section-dark .section-title {
            color: #fff;
        }

        .section-dark p {
            color: #d0d0d0;
        }

        .section-dark .section-icon {
            fill: #fff;
        }

        .dark-bottom {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 20px;
            gap: 20px;
        }

        .status-badges {
            display: flex;
            gap: 10px;
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background-color: #fff;
            color: #1a237e;
        }

        .status-risk {
            background-color: #ff8a80;
            color: #fff;
        }

        .status-dormant {
            background-color: rgba(255,255,255,0.15);
            color: #fff;
        }

        .threshold-box {
            background-color: rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 18px 30px;
            text-align: center;
            min-width: 140px;
        }

        .threshold-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #aaa;
            margin-bottom: 4px;
        }

        .threshold-value {
            font-size: 32px;
            font-weight: bold;
            color: #fff;
            line-height: 1;
        }

        .threshold-sub {
            font-size: 11px;
            color: #aaa;
            margin-top: 4px;
        }

        /* Contact Grid */
        .contact-grid {
            display: flex;
            gap: 50px;
            margin-top: 15px;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .contact-icon-circle {
            width: 36px;
            height: 36px;
            background-color: #eef2ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .contact-icon-circle svg {
            width: 18px;
            height: 18px;
            fill: #1a237e;
        }

        .contact-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #888;
            letter-spacing: 0.8px;
            margin-bottom: 2px;
        }

        .contact-value {
            font-size: 13px;
            color: #1a237e;
            font-weight: 600;
        }

        /* Banner */
        .banner {
            background: linear-gradient(rgba(26,35,126,0.92), rgba(26,35,126,0.92)),
                        url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1200&h=400&fit=crop');
            background-size: cover;
            background-position: center;
            border-radius: 12px;
            padding: 55px 30px;
            text-align: center;
            color: #fff;
            margin-top: 10px;
        }

        .banner-icon {
            width: 36px;
            height: 36px;
            fill: #fff;
            margin-bottom: 15px;
        }

        .banner-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .banner-sub {
            font-size: 14px;
            color: #ccc;
            max-width: 550px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            background-color: #fff;
            border-top: 1px solid #e5e7eb;
            padding: 22px 40px;
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #666;
        }

        .footer-left strong {
            color: #1a237e;
            font-size: 13px;
            display: block;
            margin-bottom: 3px;
        }

        .footer-links a {
            color: #666;
            text-decoration: none;
            margin-left: 20px;
            font-size: 12px;
        }

        .footer-links a:hover {
            color: #1a237e;
        }

        @media (max-width: 768px) {
            .navbar { padding: 16px 20px; }
            .nav-links a { margin-left: 15px; }
            .meta-line { flex-direction: column; gap: 8px; }
            .dark-bottom { flex-direction: column; align-items: flex-start; }
            .contact-grid { flex-direction: column; gap: 20px; }
            .footer { flex-direction: column; gap: 12px; text-align: center; }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <div class="navbar">
        <a href="<?= ROOT ?>" class="nav-logo">YouthNexus Pulse</a>
        <div class="nav-links">
            <a href="<?= ROOT ?>/terms" class="active">Terms</a>
            <a href="<?= ROOT ?>/privacy">Privacy</a>
            <a href="https://mail.google.com/mail/?view=cm&fs=1&to=governance@youthnexus.gov.lk" target="_blank">Contact</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <h1 class="page-title">Terms of Service - YouthNexus</h1>
        
        <div class="meta-line">
            <div class="meta-item">
                <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM9 10H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/></svg>
                Effective Date: May 2026
            </div>
            <div class="meta-item">
                <svg viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10z"/></svg>
                Managed by: NYSC, Sri Lanka
            </div>
        </div>

        <div class="quote-box">
            "By creating an account or accessing the YouthNexus platform, you agree to be legally bound by these Terms of Service. If you do not agree to these terms, you must not use the platform."
        </div>

        <!-- Section 1 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                <div class="section-title">1. Account Roles & Hierarchical Duties</div>
            </div>
            <p>Governance is strictly controlled through Role-Based Access Control (RBAC). Credential sharing is prohibited. Mandatory leadership handover workflows must be completed within 14 days of appointment changes.</p>
        </div>

        <!-- Section 2 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                <div class="section-title">2. Financial Ledger & Audit Rules</div>
                <span class="badge badge-critical">Critical Rule</span>
            </div>
            <p>All financial transactions are recorded on an immutable ledger. No record may be deleted. Voiding a transaction requires multi-signature approval from the Treasurer and President, with full visibility provided to all club members.</p>
            <div class="alert-box">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                Audit Compliance: 100% Transparency required for Divisional Grants.
            </div>
        </div>

        <!-- Section 3 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                <div class="section-title">3. Event Verification & GPS Data</div>
            </div>
            <p>Verification requires GPS metadata extraction from live-captured photos. Spoofing location data is grounds for immediate club suspension and affects the club's Health Score and individual Social CVs.</p>
        </div>

        <!-- Section 4 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                <div class="section-title">4. Digital Certificates & Anti-Fraud</div>
            </div>
            <p>Certificates are issued only for NYSC-verified events. Each document contains a unique cryptographic QR code. Forgery or unauthorized modification of system-generated PDFs is a punishable offense.</p>
        </div>

        <!-- Section 5 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>
                <div class="section-title">5. Asset Management & Custody</div>
            </div>
            <p>Physical assets owned by the club must be logged digitally. The designated custodian bears liability for damage. Annual audits must match the Digital Audit History Log exactly.</p>
        </div>

        <!-- Section 6 -->
        <div class="section section-dark">
            <span class="badge badge-protocol">System Protocol</span>
            <div class="section-header">
                <div class="section-title">6. Automated Club Health & Disbandment</div>
            </div>
            <p>Club viability is calculated via an automated Health Score: Event Participation (40%), Financial Integrity (30%), and Member Attendance (30%). Clubs falling into "Dormant" status for 6 consecutive months are subject to the Automated Disbandment Rule.</p>
            
            <div class="dark-bottom">
                <div class="status-badges">
                    <span class="status-badge status-active">Active</span>
                    <span class="status-badge status-risk">At Risk</span>
                    <span class="status-badge status-dormant">Dormant</span>
                </div>
                <div class="threshold-box">
                    <div class="threshold-label">Threshold</div>
                    <div class="threshold-value">45%</div>
                    <div class="threshold-sub">Minimum Health Score</div>
                </div>
            </div>
        </div>

        <!-- Section 7 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                <div class="section-title">7. System Integrity & Restrictions</div>
            </div>
            <p>Use of bots, scrapers, or any automated interaction tool is strictly forbidden. Any attempt at privilege escalation or unauthorized database access will be reported to law enforcement.</p>
        </div>

        <!-- Section 8 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                <div class="section-title">8. Termination of Access</div>
            </div>
            <p>NYSC reserves the right to suspend or terminate accounts. Violations involving financial fraud will result in a permanent ban of the associated National Identity Card (NIC) from the YouthNexus ecosystem.</p>
        </div>

        <!-- Section 9 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                <div class="section-title">9. Contact & Governance</div>
            </div>
            <div class="contact-grid">
                <div class="contact-item">
                    <div class="contact-icon-circle">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    </div>
                    <div>
                        <div class="contact-label">Governance Inquiry</div>
                        <div class="contact-value"><a href="https://mail.google.com/mail/?view=cm&fs=1&to=governance@youthnexus.gov.lk" target="_blank" style="color:inherit; text-decoration:none;">governance@youthnexus.gov.lk</a></div>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon-circle">
                        <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                    </div>
                    <div>
                        <div class="contact-label">Headquarters</div>
                        <div class="contact-value">National Youth Services Council,<br>No 65, Highlevel Rd, Maharagama,<br>Sri Lanka.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Banner -->
        <div class="banner">
            <svg class="banner-icon" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
            <div class="banner-title">Stewardship through Governance.</div>
            <div class="banner-sub">Ensuring the integrity of the youth development framework through digital accountability.</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-left">
            <strong>YouthNexus Pulse</strong>
            &copy; 2026 YouthNexus - University of Colombo School of Computing (SCS2301 Group Project)
        </div>
        <div class="footer-links">
            <a href="<?= ROOT ?>/terms">Terms of Service</a>
            <a href="<?= ROOT ?>/privacy">Privacy Policy</a>
            <a href="https://mail.google.com/mail/?view=cm&fs=1&to=governance@youthnexus.gov.lk" target="_blank">Contact Support</a>
        </div>
    </div>

</body>
</html>
