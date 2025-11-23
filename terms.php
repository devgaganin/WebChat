<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .terms-page {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: var(--surface-color);
            border-radius: 12px;
        }
        .terms-page h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        .terms-page h2 {
            color: var(--text-primary);
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        .terms-page p, .terms-page li {
            line-height: 1.8;
            color: var(--text-secondary);
            margin-bottom: 15px;
        }
        .terms-page ul {
            margin-left: 20px;
        }
        .highlight-box {
            background: rgba(255, 107, 107, 0.1);
            border-left: 4px solid #FF6B6B;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .highlight-box strong {
            color: #FF6B6B;
        }
        .btn-back {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 24px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="terms-page">
        <h1>Terms of Service</h1>
        <p><em>Last Updated: <?php echo date('F d, Y'); ?></em></p>

        <div class="highlight-box">
            <strong>IMPORTANT NOTICE:</strong>
            <p>By using this platform, you acknowledge and agree that:</p>
            <ul>
                <li><strong>Platform administrators can view all conversations</strong> for legal compliance, safety monitoring, and to prevent illegal activity</li>
                <li>While we implement user-to-user privacy protections (anti-screenshot features, self-destruct messages), these are <strong>not foolproof</strong> and cannot guarantee complete privacy</li>
                <li>This platform is <strong>NOT for illegal activities</strong> of any kind</li>
                <li>You use this service <strong>at your own risk</strong></li>
            </ul>
        </div>

        <h2>1. Acceptance of Terms</h2>
        <p>By creating an account and using SPY CHAT, you agree to be bound by these Terms of Service. If you do not agree, do not use this platform.</p>

        <h2>2. User Registration & Anonymity</h2>
        <p>While SPY CHAT allows registration with only a username and password (no email or phone required), you acknowledge that:</p>
        <ul>
            <li>Your IP address and device information may be logged</li>
            <li>Law enforcement requests will be honored where legally required</li>
            <li>Absolute anonymity cannot be guaranteed on any internet platform</li>
        </ul>

        <h2>3. Privacy & Monitoring</h2>
        <p><strong>Transparency Disclosure:</strong></p>
        <ul>
            <li>Platform administrators have access to view all messages and files shared on the platform</li>
            <li>Conversations may be monitored for illegal content, abuse, or violations of these terms</li>
            <li>We implement security measures to protect your conversations from OTHER USERS (anti-screenshot, self-destruct), but admin access exists for legal compliance</li>
            <li>We do not sell or share your data with third parties, except as required by law</li>
        </ul>

        <h2>4. User-to-User Privacy Features</h2>
        <p>We provide features to protect your messages from other users:</p>
        <ul>
            <li>Self-destruct timers for automatic message deletion</li>
            <li>Anti-screenshot protection (best-effort, not foolproof)</li>
            <li>Secure file sharing with expiring tokens</li>
            <li>Chat deletion capabilities</li>
        </ul>
        <p><strong>However:</strong> No technical measure can completely prevent determined users from capturing content (screen photos, external cameras, etc.). Use discretion.</p>

        <h2>5. Prohibited Activities</h2>
        <p>You may <strong>NOT</strong> use SPY CHAT for:</p>
        <ul>
            <li>Any illegal activities including but not limited to: drug trafficking, terrorism, child exploitation, fraud, harassment, or threats</li>
            <li>Sharing illegal content including CSAM, pirated material, or malware</li>
            <li>Impersonating others or creating fake accounts</li>
            <li>Spam, phishing, or scam attempts</li>
            <li>Circumventing platform security measures</li>
        </ul>

        <h2>6. Content & Behavior</h2>
        <ul>
            <li>You are solely responsible for all content you send</li>
            <li>Do not share sensitive personal information (social security numbers, credit cards, etc.)</li>
            <li>Harassment, hate speech, and threats will result in immediate ban</li>
            <li>We reserve the right to remove content and ban users at our discretion</li>
        </ul>

        <h2>7. Self-Destruct & File Deletion</h2>
        <ul>
            <li>Self-destructing messages are deleted from our servers after the timer expires</li>
            <li>When you delete a chat, it is permanently removed for BOTH users</li>
            <li>Files are automatically deleted when associated messages expire</li>
            <li>We cannot recover deleted content</li>
        </ul>

        <h2>8. Account Termination</h2>
        <p>We reserve the right to suspend or terminate your account immediately for:</p>
        <ul>
            <li>Violation of these Terms of Service</li>
            <li>Illegal activity</li>
            <li>Abuse of other users</li>
            <li>Attempting to compromise platform security</li>
        </ul>

        <h2>9. Disclaimers</h2>
        <ul>
            <li>This platform is provided "AS IS" without warranties of any kind</li>
            <li>We do not guarantee uninterrupted service or absolute security</li>
            <li>Anti-screenshot protections are best-effort and not foolproof</li>
            <li>We are not liable for any damages arising from use of this platform</li>
        </ul>

        <h2>10. Legal Compliance</h2>
        <ul>
            <li>We comply with applicable laws and regulations</li>
            <li>We will cooperate with law enforcement when legally required</li>
            <li>User data may be disclosed pursuant to valid legal process</li>
        </ul>

        <h2>11. Age Requirement</h2>
        <p>You must be at least 18 years old to use this platform. By registering, you certify that you meet this requirement.</p>

        <h2>12. Changes to Terms</h2>
        <p>We may update these Terms at any time. Continued use after changes constitutes acceptance of the new Terms.</p>

        <h2>13. Contact</h2>
        <p>For questions about these Terms, contact the platform administrator.</p>

        <div class="highlight-box">
            <strong>By clicking "I Accept" during registration, you confirm that you have read, understood, and agree to these Terms of Service.</strong>
        </div>

        <a href="register.php" class="btn-back">Back to Registration</a>
    </div>
</body>
</html>
