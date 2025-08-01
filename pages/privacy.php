<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../includes/config.php';

$page_title = "Privacy Policy - BluFox Studio";
$page_description = "BluFox Studio's privacy policy, including information about cookies, data collection, and your privacy rights.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/components/head.php' ?>
    <link rel="stylesheet" href="/assets/css/pages/legal.css">
</head>
<body>
    <?php include '../includes/components/header.php'; ?>
    
    <main class="legal-page">
        <div class="legal-container">
            <header class="legal-header">
                <h1>Privacy Policy</h1>
                <p class="last-updated">Last updated: <?php echo date('F j, Y'); ?></p>
            </header>

            <div class="legal-content">
                <section id="introduction">
                    <h2>Introduction</h2>
                    <p>
                        BluFox Studio ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy 
                        explains how we collect, use, disclose, and safeguard your information when you visit our website 
                        <a href="<?php echo SITE_URL; ?>"><?php echo SITE_URL; ?></a> and use our services.
                    </p>
                    <p>
                        Please read this privacy policy carefully. If you do not agree with the terms of this privacy policy, 
                        please do not access the site.
                    </p>
                </section>

                <section id="information-collection">
                    <h2>Information We Collect</h2>
                    
                    <h3>Information You Provide</h3>
                    <ul>
                        <li><strong>Account Information:</strong> When you create an account, we collect your Roblox username and user ID through OAuth authentication</li>
                        <li><strong>Contact Information:</strong> Email addresses when you contact us or subscribe to newsletters</li>
                        <li><strong>Project Information:</strong> Details about your Roblox projects when you use our services</li>
                        <li><strong>Communications:</strong> Messages you send to us through contact forms or support channels</li>
                    </ul>

                    <h3>Information Collected Automatically</h3>
                    <ul>
                        <li><strong>Usage Data:</strong> Pages visited, time spent on site, referring websites</li>
                        <li><strong>Device Information:</strong> Browser type, device type, operating system</li>
                        <li><strong>IP Address:</strong> For security and analytics purposes</li>
                        <li><strong>Cookies and Tracking:</strong> As described in our Cookie Policy below</li>
                    </ul>
                </section>

                <section id="cookie-policy">
                    <h2>Cookie Policy</h2>
                    <p>
                        We use cookies and similar tracking technologies to enhance your browsing experience, 
                        provide personalized content, and analyze our traffic. You can control your cookie 
                        preferences at any time.
                    </p>

                    <h3>Types of Cookies We Use</h3>
                    
                    <div class="cookie-category">
                        <h4>Necessary Cookies</h4>
                        <p>
                            These cookies are essential for the website to function properly. They enable core 
                            functionality such as security, network management, and accessibility.
                        </p>
                        <div class="cookie-list">
                            <strong>Cookies used:</strong>
                            <ul>
                                <li><code>PHPSESSID</code> - Maintains your session state</li>
                                <li><code>csrf_token</code> - Protects against cross-site request forgery</li>
                                <li><code>cookie_consent</code> - Stores your cookie preferences</li>
                            </ul>
                        </div>
                    </div>

                    <div class="cookie-category">
                        <h4>Functional Cookies</h4>
                        <p>
                            These cookies enable enhanced functionality and personalization, such as remembering 
                            your preferences and settings.
                        </p>
                        <div class="cookie-list">
                            <strong>Cookies used:</strong>
                            <ul>
                                <li><code>theme_preference</code> - Remembers your theme choice (dark/light mode)</li>
                                <li><code>language_preference</code> - Stores your language preference</li>
                                <li><code>nav_state</code> - Remembers navigation panel state</li>
                            </ul>
                        </div>
                    </div>

                    <div class="cookie-category">
                        <h4>Analytics Cookies</h4>
                        <p>
                            These cookies help us understand how visitors interact with our website by collecting 
                            and reporting information anonymously.
                        </p>
                        <div class="cookie-list">
                            <strong>Cookies used:</strong>
                            <ul>
                                <li><code>_ga</code> - Google Analytics: Distinguishes users</li>
                                <li><code>_gid</code> - Google Analytics: Distinguishes users</li>
                                <li><code>_gat</code> - Google Analytics: Throttles request rate</li>
                                <li><code>page_views</code> - Internal analytics for page tracking</li>
                                <li><code>user_analytics</code> - Internal user behavior analysis</li>
                            </ul>
                        </div>
                    </div>

                    <div class="cookie-category">
                        <h4>Marketing Cookies</h4>
                        <p>
                            These cookies are used to deliver personalized content and track the effectiveness 
                            of our marketing campaigns.
                        </p>
                        <div class="cookie-list">
                            <strong>Cookies used:</strong>
                            <ul>
                                <li><code>_fbp</code> - Facebook Pixel: Tracks conversions</li>
                                <li><code>_fbc</code> - Facebook Pixel: Stores click IDs</li>
                                <li><code>marketing_id</code> - Internal marketing tracking</li>
                                <li><code>ad_preferences</code> - Stores advertising preferences</li>
                            </ul>
                        </div>
                    </div>

                    <div class="cookie-controls">
                        <h3>Managing Your Cookie Preferences</h3>
                        <p>
                            You can manage your cookie preferences at any time by clicking the cookie settings 
                            button or by using your browser settings.
                        </p>
                        <button id="open-cookie-preferences" class="btn btn-primary">
                            Manage Cookie Preferences
                        </button>
                    </div>
                </section>

                <section id="data-use">
                    <h2>How We Use Your Information</h2>
                    <p>We use the information we collect for the following purposes:</p>
                    <ul>
                        <li><strong>Service Provision:</strong> To provide and maintain our Roblox development services</li>
                        <li><strong>Authentication:</strong> To verify your identity and manage your account</li>
                        <li><strong>Communication:</strong> To respond to your inquiries and provide customer support</li>
                        <li><strong>Improvement:</strong> To analyze usage patterns and improve our website and services</li>
                        <li><strong>Security:</strong> To detect and prevent fraud, abuse, and security threats</li>
                        <li><strong>Legal Compliance:</strong> To comply with applicable laws and regulations</li>
                    </ul>
                </section>

                <section id="data-sharing">
                    <h2>Information Sharing and Disclosure</h2>
                    <p>We do not sell, trade, or rent your personal information to third parties. We may share your information in the following circumstances:</p>
                    
                    <h3>Service Providers</h3>
                    <p>
                        We may share information with trusted third-party service providers who assist us in 
                        operating our website and providing services, subject to confidentiality agreements.
                    </p>

                    <h3>Legal Requirements</h3>
                    <p>
                        We may disclose your information if required by law, court order, or governmental request, 
                        or to protect our rights, property, or safety.
                    </p>

                    <h3>Business Transfers</h3>
                    <p>
                        In the event of a merger, acquisition, or sale of assets, your information may be 
                        transferred as part of the transaction.
                    </p>
                </section>

                <section id="data-security">
                    <h2>Data Security</h2>
                    <p>
                        We implement appropriate technical and organizational security measures to protect your 
                        personal information against unauthorized access, alteration, disclosure, or destruction. 
                        These measures include:
                    </p>
                    <ul>
                        <li>SSL/TLS encryption for data transmission</li>
                        <li>Secure server infrastructure</li>
                        <li>Regular security audits and updates</li>
                        <li>Access controls and authentication</li>
                        <li>Data minimization and retention policies</li>
                    </ul>
                </section>

                <section id="data-retention">
                    <h2>Data Retention</h2>
                    <p>
                        We retain your personal information only for as long as necessary to fulfill the purposes 
                        outlined in this privacy policy, unless a longer retention period is required by law. 
                        Cookie consent records are retained for up to 3 years for compliance purposes.
                    </p>
                </section>

                <section id="your-rights">
                    <h2>Your Privacy Rights</h2>
                    <p>Depending on your location, you may have the following rights regarding your personal information:</p>
                    
                    <div class="rights-grid">
                        <div class="right-item">
                            <h4>Access</h4>
                            <p>Request a copy of the personal information we hold about you</p>
                        </div>
                        <div class="right-item">
                            <h4>Rectification</h4>
                            <p>Request correction of inaccurate or incomplete information</p>
                        </div>
                        <div class="right-item">
                            <h4>Erasure</h4>
                            <p>Request deletion of your personal information (right to be forgotten)</p>
                        </div>
                        <div class="right-item">
                            <h4>Portability</h4>
                            <p>Request transfer of your data to another service provider</p>
                        </div>
                        <div class="right-item">
                            <h4>Object</h4>
                            <p>Object to processing of your personal information</p>
                        </div>
                        <div class="right-item">
                            <h4>Restrict</h4>
                            <p>Request restriction of processing in certain circumstances</p>
                        </div>
                    </div>

                    <p>
                        To exercise these rights, please contact us at 
                        <a href="mailto:privacy@blufox-studio.com">privacy@blufox-studio.com</a>. 
                        We will respond to your request within 30 days.
                    </p>
                </section>

                <section id="international-transfers">
                    <h2>International Data Transfers</h2>
                    <p>
                        Your information may be transferred to and processed in countries other than your country 
                        of residence. We ensure that such transfers comply with applicable data protection laws 
                        and implement appropriate safeguards.
                    </p>
                </section>

                <section id="children-privacy">
                    <h2>Children's Privacy</h2>
                    <p>
                        Our services are not directed to children under 13 years of age. We do not knowingly 
                        collect personal information from children under 13. If we become aware that we have 
                        collected such information, we will delete it promptly.
                    </p>
                </section>

                <section id="third-party-links">
                    <h2>Third-Party Links</h2>
                    <p>
                        Our website may contain links to third-party websites. We are not responsible for the 
                        privacy practices of these external sites. We encourage you to review their privacy 
                        policies before providing any information.
                    </p>
                </section>

                <section id="updates">
                    <h2>Updates to This Policy</h2>
                    <p>
                        We may update this privacy policy from time to time. We will notify you of any material 
                        changes by posting the new policy on this page and updating the "Last updated" date. 
                        Your continued use of our services constitutes acceptance of the updated policy.
                    </p>
                </section>

                <section id="contact">
                    <h2>Contact Information</h2>
                    <p>If you have any questions about this privacy policy or our privacy practices, please contact us:</p>
                    
                    <div class="contact-info">
                        <div class="contact-method">
                            <strong>Email:</strong> 
                            <a href="mailto:privacy@blufox-studio.com">privacy@blufox-studio.com</a>
                        </div>
                        <div class="contact-method">
                            <strong>General Contact:</strong> 
                            <a href="mailto:<?php echo CONTACT_EMAIL; ?>"><?php echo CONTACT_EMAIL; ?></a>
                        </div>
                        <div class="contact-method">
                            <strong>Data Protection Officer:</strong> 
                            <a href="mailto:dpo@blufox-studio.com">dpo@blufox-studio.com</a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('open-cookie-preferences')?.addEventListener('click', function() {
            if (window.CookieConsent) {
                window.CookieConsent.showBanner();
            }
        });
    </script>
</body>
</html>