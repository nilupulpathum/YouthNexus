<?php
// privacy.php - YouthNexus Privacy Policy
// Static page, no backend logic required
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - YouthNexus</title>
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

        /* Bullet List */
        .bullet-list {
            margin: 15px 0 15px 25px;
        }

        .bullet-list li {
            font-size: 14px;
            color: #555;
            margin-bottom: 10px;
            line-height: 1.6;
            list-style: none;
            position: relative;
            padding-left: 15px;
        }

        .bullet-list li::before {
            content: "";
            position: absolute;
            left: 0;
            top: 9px;
            width: 6px;
            height: 6px;
            background-color: #1a237e;
            border-radius: 50%;
        }

        .bullet-list strong {
            color: #333;
        }

        /* Alert Box */
        .alert-box {
            background-color: #eef2ff;
            border-radius: 8px;
            padding: 14px 16px;
            margin-top: 15px;
            font-size: 13px;
            color: #555;
            line-height: 1.6;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-box svg {
            width: 18px;
            height: 18px;
            fill: #1a237e;
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* Three Cards Grid */
        .cards-row {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }

        .info-card {
            flex: 1;
            background-color: #f8f9ff;
            border-radius: 10px;
            padding: 18px;
            border: 1px solid #e8eaf6;
        }

        .info-card h4 {
            font-size: 13px;
            font-weight: bold;
            color: #1a237e;
            margin-bottom: 8px;
        }

        .info-card p {
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }

        /* Sub Items */
        .sub-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 14px;
        }

        .sub-item:last-child {
            margin-bottom: 0;
        }

        .sub-icon {
            width: 18px;
            height: 18px;
            fill: #888;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .sub-item h4 {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 2px;
        }

        .sub-item p {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
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
            background-color: #1a237e;
            border-radius: 12px;
            padding: 55px 30px;
            text-align: center;
            color: #fff;
            margin-top: 10px;
        }

        .banner-icon {
            width: 40px;
            height: 40px;
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
            max-width: 500px;
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
            .cards-row { flex-direction: column; }
            .contact-grid { flex-direction: column; gap: 20px; }
            .footer { flex-direction: column; gap: 12px; text-align: center; }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <div class="navbar">
        <div class="nav-logo">YouthNexus Pulse</div>
        <div class="nav-links">
            <a href="terms.php">Terms</a>
            <a href="#" class="active">Privacy</a>
            <a href="#">Contact</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <h1 class="page-title">Privacy Policy - YouthNexus</h1>
        
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
            At YouthNexus, we are committed to protecting the privacy and digital well-being of our youth members. This policy outlines how we handle data within our ecosystem to ensure a safe, professional, and empowering environment for the next generation of leaders in Sri Lanka.
        </div>

        <!-- Section 1 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                <div class="section-title">1. Information We Collect</div>
            </div>
            <p>We collect information necessary to provide institutional support and verify youth participation:</p>
            <ul class="bullet-list">
                <li><strong>Identity Data:</strong> Full name, NIC number, and institutional affiliations.</li>
                <li><strong>Contact Data:</strong> Email address, phone number, and residential district.</li>
                <li><strong>Usage Data:</strong> Logins, event registrations, and resource interactions.</li>
            </ul>
            <div class="alert-box">
                <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                <span><strong>GPS Location Notice:</strong> We may collect precise location data only during active event check-ins or field initiatives to verify attendance and ensure member safety.</span>
            </div>
        </div>

        <!-- Section 2 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
                <div class="section-title">2. How We Use Your Information</div>
            </div>
            <p>Your data is utilized to personalize your dashboard, facilitate communication between members and the NYSC, and generate anonymized reports for national youth development metrics. We do not use your personal data for automated profiling or commercial marketing.</p>
        </div>

        <!-- Section 3 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4V6h16v12zm-6-7c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-4 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                <div class="section-title">3. Financial Data Clarification</div>
            </div>
            <p>YouthNexus Pulse does not store credit card details or bank credentials on its local servers. All transaction data for grants or event fees is processed through secure, third-party payment gateways compliant with PCI-DSS standards.</p>
        </div>

        <!-- Section 4 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                <div class="section-title">4. Data Sharing & Visibility</div>
            </div>
            <div class="cards-row">
                <div class="info-card">
                    <h4>Horizontal Restriction</h4>
                    <p>Individual member profiles are private. Peer-to-peer data access is strictly prohibited unless explicitly shared within working groups.</p>
                </div>
                <div class="info-card">
                    <h4>Vertical Access</h4>
                    <p>Authorized NYSC administrators and regional officers have tiered access for the purpose of governance and support services.</p>
                </div>
                <div class="info-card">
                    <h4>Public Portal</h4>
                    <p>Our Public Verification Portal only displays membership status and achievement badges, masking all private contact details.</p>
                </div>
            </div>
        </div>

        <!-- Section 5 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                <div class="section-title">5. Protection of Minors</div>
            </div>
            <p>For members under the age of 18, YouthNexus enforces enhanced privacy settings by default. Interaction with external partners through the platform requires explicit parental or guardian digital consent for specific initiatives.</p>
        </div>

        <!-- Section 6 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                <div class="section-title">6. Data Security</div>
            </div>
            <div class="sub-item">
                <svg class="sub-icon" viewBox="0 0 24 24"><path d="M12.65 10C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4H17v4h4v-4h2v-4H12.65zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/></svg>
                <div>
                    <h4>Passwords & Encryption</h4>
                    <p>All passwords are salted and hashed. Data in transit is encrypted using industry-standard SSL/TLS protocols.</p>
                </div>
            </div>
            <div class="sub-item">
                <svg class="sub-icon" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                <div>
                    <h4>Session Management</h4>
                    <p>Inactivity timeouts and multi-device login alerts are enforced to prevent unauthorized terminal access.</p>
                </div>
            </div>
        </div>

        <!-- Section 7 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M9 11.24V7.5C9 6.12 10.12 5 11.5 5S14 6.12 14 7.5v3.74c1.21-.81 2-2.18 2-3.74C16 5.01 13.99 3 11.5 3S7 5.01 7 7.5c0 1.56.79 2.93 2 3.74zm9.84 4.63l-4.54-2.26c-.17-.07-.35-.11-.54-.11H13v-6c0-.83-.67-1.5-1.5-1.5S10 6.67 10 7.5v10.74l-3.43-.72c-.08-.01-.15-.03-.24-.03-.31 0-.59.13-.79.33l-.79.8 4.94 4.94c.27.27.65.44 1.06.44h6.79c.75 0 1.33-.55 1.44-1.28l.75-5.27c.01-.07.02-.14.02-.2 0-.62-.38-1.16-.91-1.38z"/></svg>
                <div class="section-title">7. Data Integrity & Your Rights</div>
            </div>
            <p><strong>Immutability:</strong> To maintain official records, certain verified achievement data is immutable. All other personal info can be updated via the Profile portal.</p>
            <p style="margin-top:12px;"><strong>Right to View:</strong> You may request a machine-readable export of all your stored data at any time.</p>
            <p style="margin-top:12px;"><strong>Right to Deactivation:</strong> You can deactivate your account; however, core registry data may be retained for 5 years as required by NYSC statutory requirements.</p>
        </div>

        <!-- Section 8 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/></svg>
                <div class="section-title">8. Contact Us</div>
            </div>
            <div class="contact-grid">
                <div class="contact-item">
                    <div class="contact-icon-circle">
                        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    </div>
                    <div>
                        <div class="contact-label">Email Inquiry</div>
                        <div class="contact-value">privacy@youthnexus.gov.lk</div>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon-circle">
                        <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                    </div>
                    <div>
                        <div class="contact-label">Official Address</div>
                        <div class="contact-value">National Youth Services Council,<br>No 65, Highlevel Rd, Maharagama,<br>Sri Lanka.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Banner -->
        <div class="banner">
            <svg class="banner-icon" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
            <div class="banner-title">Trust is our foundation.</div>
            <div class="banner-sub">YouthNexus Pulse ensures your data serves your growth, not our profit.</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-left">
            <strong>YouthNexus Pulse</strong>
            &copy; 2026 YouthNexus - University of Colombo School of Computing (SCS2301 Group Project)
        </div>
        <div class="footer-links">
            <a href="terms.php">Terms of Service</a>
            <a href="#">Privacy Policy</a>
            <a href="#">Contact Support</a>
        </div>
    </div>

</body>
</html>