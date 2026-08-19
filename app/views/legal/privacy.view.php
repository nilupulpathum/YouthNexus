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

        .bullet-list {
            margin-top: 10px;
            margin-left: 20px;
            font-size: 14px;
            color: #555;
        }

        .bullet-list li {
            margin-bottom: 6px;
        }

        /* Highlight Grid / Cards */
        .cards-row {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }

        .card-mini {
            flex: 1;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
        }

        .card-mini-title {
            font-size: 13px;
            font-weight: bold;
            color: #1a237e;
            margin-bottom: 5px;
        }

        .card-mini-text {
            font-size: 12px;
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
            background: linear-gradient(rgba(26,35,126,0.92), rgba(26,35,126,0.92)),
                        url('https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1200&h=400&fit=crop');
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
            .cards-row { flex-direction: column; }
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
            <a href="<?= ROOT ?>/terms">Terms</a>
            <a href="<?= ROOT ?>/privacy" class="active">Privacy</a>
            <a href="https://mail.google.com/mail/?view=cm&fs=1&to=governance@youthnexus.gov.lk" target="_blank">Contact</a>
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
        </div>

        <!-- Section 2 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                <div class="section-title">2. How We Use Your Data</div>
            </div>
            <p>Your data is used strictly for institutional governance, leadership recognition, and national reporting. We do not sell or monetize member data under any circumstances.</p>
            
            <div class="cards-row">
                <div class="card-mini">
                    <div class="card-mini-title">Social CV Verification</div>
                    <div class="card-mini-text">Generating official verification for youth accomplishments and leadership hours.</div>
                </div>
                <div class="card-mini">
                    <div class="card-mini-title">Resource Allocation</div>
                    <div class="card-mini-text">Directing national grants and equipment based on divisional youth activity metrics.</div>
                </div>
            </div>
        </div>

        <!-- Section 3 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                <div class="section-title">3. Data Security & Storage</div>
            </div>
            <p>All sensitive information, including password hashes and verification tokens, is stored using industry-standard encryption. Access to data is protected by multi-factor authentication (2FA) and role-based permissions.</p>
        </div>

        <!-- Section 4 -->
        <div class="section">
            <div class="section-header">
                <svg class="section-icon" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                <div class="section-title">4. Contact & Data Protection Officer</div>
            </div>
            <div class="contact-grid">
                <div class="contact-item">
                    <div class="contact-icon-circle">
                        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    </div>
                    <div>
                        <div class="contact-label">Data Inquiries</div>
                        <div class="contact-value"><a href="https://mail.google.com/mail/?view=cm&fs=1&to=governance@youthnexus.gov.lk" target="_blank" style="color:inherit; text-decoration:none;">governance@youthnexus.gov.lk</a></div>
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
            <a href="<?= ROOT ?>/terms">Terms of Service</a>
            <a href="<?= ROOT ?>/privacy">Privacy Policy</a>
            <a href="https://mail.google.com/mail/?view=cm&fs=1&to=governance@youthnexus.gov.lk" target="_blank">Contact Support</a>
        </div>
    </div>

</body>
</html>
