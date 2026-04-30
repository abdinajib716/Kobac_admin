<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Kobac Privacy Policy</title>
        <meta
            name="description"
            content="Kobac privacy policy covering account data, business records, payments, notifications, retention, deletion, and support contact details."
        >
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=literata:500,700|manrope:400,500,600,700" rel="stylesheet" />
        <style>
            :root {
                color-scheme: light;
                --bg: #f4efe6;
                --bg-accent: #d9efe6;
                --paper: rgba(255, 252, 247, 0.88);
                --paper-strong: #fffdfa;
                --ink: #18231f;
                --muted: #5c6b65;
                --line: rgba(24, 35, 31, 0.12);
                --accent: #0f7a61;
                --accent-deep: #0b5d4a;
                --accent-soft: #d8f0e8;
                --warning: #8a3f1c;
                --warning-soft: #fff0e7;
                --shadow: 0 24px 60px rgba(24, 35, 31, 0.12);
            }

            * {
                box-sizing: border-box;
            }

            html {
                scroll-behavior: smooth;
            }

            body {
                margin: 0;
                font-family: 'Manrope', sans-serif;
                color: var(--ink);
                background:
                    radial-gradient(circle at top left, rgba(15, 122, 97, 0.12), transparent 30%),
                    radial-gradient(circle at top right, rgba(217, 239, 230, 0.9), transparent 26%),
                    linear-gradient(180deg, #f8f3eb 0%, var(--bg) 100%);
                line-height: 1.7;
            }

            a {
                color: var(--accent-deep);
            }

            .page {
                width: min(980px, calc(100% - 32px));
                margin: 0 auto;
                padding: 40px 0 56px;
            }

            .hero {
                position: relative;
                overflow: hidden;
                padding: 40px;
                border: 1px solid rgba(255, 255, 255, 0.5);
                border-radius: 28px;
                background:
                    linear-gradient(135deg, rgba(255, 255, 255, 0.88), rgba(255, 248, 240, 0.92)),
                    linear-gradient(135deg, rgba(15, 122, 97, 0.12), transparent 60%);
                box-shadow: var(--shadow);
            }

            .hero::after {
                content: '';
                position: absolute;
                inset: auto -120px -120px auto;
                width: 280px;
                height: 280px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(15, 122, 97, 0.18), transparent 68%);
                pointer-events: none;
            }

            .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 14px;
                padding: 8px 12px;
                border-radius: 999px;
                background: rgba(15, 122, 97, 0.1);
                color: var(--accent-deep);
                font-size: 0.85rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            h1,
            h2,
            h3 {
                margin: 0;
                font-family: 'Literata', serif;
                line-height: 1.15;
            }

            h1 {
                max-width: 12ch;
                font-size: clamp(2.6rem, 7vw, 4.8rem);
            }

            .lead {
                margin: 18px 0 0;
                color: var(--muted);
                font-size: 1.08rem;
            }

            .intro {
                max-width: 68ch;
                margin: 20px 0 0;
                font-size: 1.02rem;
            }

            .publish-note,
            .toc,
            .policy-section {
                margin-top: 22px;
                border: 1px solid var(--line);
                border-radius: 24px;
                background: var(--paper);
                backdrop-filter: blur(12px);
                box-shadow: var(--shadow);
            }

            .publish-note {
                padding: 24px;
                background:
                    linear-gradient(180deg, rgba(255, 240, 231, 0.96), rgba(255, 252, 247, 0.96)),
                    var(--paper);
            }

            .publish-note h2 {
                font-size: 1.3rem;
                color: var(--warning);
            }

            .publish-note p {
                margin: 10px 0 0;
            }

            .checklist,
            .bullet-list {
                margin: 14px 0 0;
                padding-left: 1.15rem;
            }

            .checklist li,
            .bullet-list li {
                margin: 0.45rem 0;
            }

            .toc {
                padding: 22px 24px;
            }

            .toc h2 {
                font-size: 1.1rem;
                margin-bottom: 14px;
            }

            .toc-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px 18px;
            }

            .toc a {
                display: block;
                padding: 11px 14px;
                border-radius: 14px;
                background: rgba(15, 122, 97, 0.06);
                text-decoration: none;
                transition: background-color 0.2s ease, transform 0.2s ease;
            }

            .toc a:hover,
            .toc a:focus-visible {
                background: rgba(15, 122, 97, 0.12);
                transform: translateY(-1px);
            }

            .policy-section {
                padding: 28px;
            }

            .policy-section + .policy-section {
                margin-top: 18px;
            }

            .section-number {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 38px;
                height: 38px;
                margin-bottom: 14px;
                border-radius: 50%;
                background: var(--accent-soft);
                color: var(--accent-deep);
                font-size: 0.95rem;
                font-weight: 800;
            }

            .policy-section h2 {
                font-size: clamp(1.5rem, 4vw, 2rem);
                margin-bottom: 12px;
            }

            .policy-section h3 {
                margin-top: 18px;
                font-size: 1.15rem;
            }

            .policy-section p {
                margin: 10px 0 0;
            }

            .policy-section strong {
                color: var(--ink);
            }

            .contact-card {
                padding: 18px 20px;
                margin-top: 16px;
                border-radius: 18px;
                border: 1px solid var(--line);
                background: var(--paper-strong);
            }

            .footer {
                margin-top: 24px;
                padding: 18px 6px 0;
                color: var(--muted);
                font-size: 0.95rem;
                text-align: center;
            }

            @media (max-width: 720px) {
                .page {
                    width: min(100% - 18px, 980px);
                    padding: 18px 0 32px;
                }

                .hero,
                .publish-note,
                .toc,
                .policy-section {
                    border-radius: 22px;
                }

                .hero,
                .policy-section,
                .publish-note,
                .toc {
                    padding: 22px;
                }

                .toc-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <div class="page">
            <header class="hero">
                <div class="eyebrow">Legal Page</div>
                <h1>Kobac Privacy Policy</h1>
                <p class="lead"><strong>Effective date:</strong> April 14, 2026</p>
                <p class="intro">
                    This Privacy Policy explains how Kobac collects, uses, stores, and protects information when you
                    use the Kobac mobile application and related services.
                </p>
            </header>

            <section class="publish-note" aria-labelledby="publish-note-title">
                <h2 id="publish-note-title">Publication Details</h2>
                <p>These are the official public contact and legal page details for Kobac.</p>
                <ul class="checklist">
                    <li>Company / Developer name: <strong>Abdinajib Mohamed Karshe</strong></li>
                    <li>Contact email: <a href="mailto:abdinajiibmohamedkarshe716@gmail.com">abdinajiibmohamedkarshe716@gmail.com</a></li>
                    <li>Privacy Policy URL: <a href="https://kobac.cajiibcreative.com/privacy-policy">https://kobac.cajiibcreative.com/privacy-policy</a></li>
                    <li>Account deletion URL: <a href="https://kobac.cajiibcreative.com/account-deletion">https://kobac.cajiibcreative.com/account-deletion</a></li>
                </ul>
            </section>

            <nav class="toc" aria-labelledby="toc-title">
                <h2 id="toc-title">Contents</h2>
                <div class="toc-grid">
                    <a href="#about">1. About Kobac</a>
                    <a href="#collect">2. Information We Collect</a>
                    <a href="#use">3. How We Use Information</a>
                    <a href="#share">4. How We Share Information</a>
                    <a href="#third-party">5. Third-Party Services</a>
                    <a href="#security">6. Data Storage and Security</a>
                    <a href="#retention">7. Data Retention</a>
                    <a href="#deletion">8. Account Deletion and Data Deletion</a>
                    <a href="#rights">9. Your Choices and Rights</a>
                    <a href="#notifications">10. Push Notifications</a>
                    <a href="#children">11. Children's Privacy</a>
                    <a href="#transfers">12. International Data Transfers</a>
                    <a href="#changes">13. Changes to This Privacy Policy</a>
                    <a href="#contact">14. Contact Us</a>
                </div>
            </nav>

            <main>
                <section class="policy-section" id="about">
                    <div class="section-number">1</div>
                    <h2>About Kobac</h2>
                    <p>
                        Kobac is a mobile business management application that helps users track income, expenses,
                        stock, customers, vendors, accounts, reports, branches, business users, subscriptions,
                        payments, and notifications.
                    </p>
                </section>

                <section class="policy-section" id="collect">
                    <div class="section-number">2</div>
                    <h2>Information We Collect</h2>
                    <p>We may collect the following information depending on how you use the app:</p>

                    <h3>a. Account information</h3>
                    <p>
                        We may collect your name, phone number, email address, password or authentication details,
                        account type, user profile information, and login/session information.
                    </p>

                    <h3>b. Business information</h3>
                    <p>
                        If you create or manage a business account, we may collect business name, business profile
                        details, branch information, staff or business user details, permissions, subscription status,
                        and related business setup information.
                    </p>

                    <h3>c. Financial and business records entered by you</h3>
                    <p>
                        Kobac may store the records you enter into the app, including income, expenses, stock items,
                        accounts, customer records, vendor records, receivables, payables, payments, profit/loss
                        reports, business activity, and report exports.
                    </p>
                    <p>
                        Kobac is a record-keeping and business management tool. It is not a bank, lender, insurance
                        provider, investment platform, cryptocurrency platform, or money transfer provider.
                    </p>

                    <h3>d. Payment and subscription information</h3>
                    <p>
                        If you subscribe to a plan or make a payment, we may process payment-related details such as
                        plan selected, payment amount, payment type, wallet/payment method, transaction reference,
                        payment status, and approval status.
                    </p>
                    <p>
                        Payments may be handled through local mobile money or offline payment methods, such as
                        Waafi/USSD or other supported local payment channels. We do not collect or store credit card
                        numbers in the mobile app unless this is added later and clearly disclosed.
                    </p>

                    <h3>e. Notifications and device information</h3>
                    <p>
                        If you allow notifications, we may collect and use a Firebase Cloud Messaging device token so
                        that we can send push notifications related to your account, business activity, subscription,
                        payment status, and app updates.
                    </p>
                    <p>
                        We may also collect limited device and technical information such as device type, operating
                        system, app version, network status, crash/error information, and logs needed to operate,
                        secure, and improve the app.
                    </p>

                    <h3>f. Support communications</h3>
                    <p>
                        If you contact us for support, we may collect your message, contact details, and any
                        information you choose to provide.
                    </p>
                </section>

                <section class="policy-section" id="use">
                    <div class="section-number">3</div>
                    <h2>How We Use Information</h2>
                    <p>We use information to:</p>
                    <ul class="bullet-list">
                        <li>create and manage user accounts</li>
                        <li>provide login and authentication</li>
                        <li>provide business management features</li>
                        <li>store and sync the records you enter</li>
                        <li>manage subscription status and payment requests</li>
                        <li>send app notifications when permitted</li>
                        <li>provide customer support</li>
                        <li>troubleshoot bugs and improve app performance</li>
                        <li>prevent fraud, abuse, unauthorized access, and misuse</li>
                        <li>comply with legal, regulatory, tax, accounting, or platform requirements</li>
                    </ul>
                </section>

                <section class="policy-section" id="share">
                    <div class="section-number">4</div>
                    <h2>How We Share Information</h2>
                    <p>We do not sell your personal information.</p>
                    <p>We may share information only when needed for the following purposes:</p>
                    <ul class="bullet-list">
                        <li>
                            with service providers that help us operate the app, server infrastructure, hosting, notifications,
                            analytics, payment processing, or support
                        </li>
                        <li>with Firebase/Google services for push notifications and app infrastructure</li>
                        <li>
                            with payment service providers or local payment channels when needed to process
                            subscription or payment requests
                        </li>
                        <li>with authorized business account owners or team members according to permissions inside the app</li>
                        <li>when required by law, regulation, legal process, or a government request</li>
                        <li>
                            when needed to protect the rights, safety, and security of Kobac, our users, or others
                        </li>
                    </ul>
                </section>

                <section class="policy-section" id="third-party">
                    <div class="section-number">5</div>
                    <h2>Third-Party Services</h2>
                    <p>Kobac may use third-party services including:</p>
                    <ul class="bullet-list">
                        <li>Firebase Cloud Messaging for push notifications</li>
                        <li>Google/Firebase services for app infrastructure</li>
                        <li>
                            local mobile money or offline payment service providers for subscription/payment
                            processing
                        </li>
                        <li>server hosting and database services used to operate the app</li>
                    </ul>
                    <p>
                        These third-party services may process information according to their own privacy policies and
                        service terms.
                    </p>
                </section>

                <section class="policy-section" id="security">
                    <div class="section-number">6</div>
                    <h2>Data Storage and Security</h2>
                    <p>
                        We use reasonable technical and organizational measures to protect user information. The app
                        uses secure storage for sensitive local values such as authentication tokens where supported by
                        the device.
                    </p>
                    <p>
                        However, no method of transmission or storage is completely secure. We cannot guarantee
                        absolute security, but we work to protect information from unauthorized access, loss, misuse,
                        alteration, or disclosure.
                    </p>
                </section>

                <section class="policy-section" id="retention">
                    <div class="section-number">7</div>
                    <h2>Data Retention</h2>
                    <p>
                        We keep information for as long as needed to provide the app, maintain business records,
                        support subscriptions and payments, meet legal or accounting requirements, resolve disputes,
                        prevent fraud, and enforce our terms.
                    </p>
                    <p>
                        When information is no longer needed, we may delete, anonymize, or archive it according to our
                        data retention practices and legal obligations.
                    </p>
                </section>

                <section class="policy-section" id="deletion">
                    <div class="section-number">8</div>
                    <h2>Account Deletion and Data Deletion</h2>
                    <p>
                        If you created an account, you may request account deletion and deletion of associated personal
                        data.
                    </p>

                    <div class="contact-card">
                        <p><strong>Account deletion request page:</strong> <a href="https://kobac.cajiibcreative.com/account-deletion">https://kobac.cajiibcreative.com/account-deletion</a></p>
                        <p><strong>Support email for deletion requests:</strong> <a href="mailto:abdinajiibmohamedkarshe716@gmail.com">abdinajiibmohamedkarshe716@gmail.com</a></p>
                    </div>

                    <p>
                        Some information may be retained where required by law, accounting rules, fraud prevention,
                        dispute resolution, payment records, security logs, or legitimate business obligations.
                    </p>
                </section>

                <section class="policy-section" id="rights">
                    <div class="section-number">9</div>
                    <h2>Your Choices and Rights</h2>
                    <p>Depending on your location and applicable law, you may have the right to:</p>
                    <ul class="bullet-list">
                        <li>access information we hold about you</li>
                        <li>correct inaccurate information</li>
                        <li>request deletion of your account or personal data</li>
                        <li>object to or restrict certain processing</li>
                        <li>withdraw consent where processing is based on consent</li>
                        <li>disable push notifications from your device settings</li>
                    </ul>
                    <p>To make a request, contact us at: <a href="mailto:abdinajiibmohamedkarshe716@gmail.com">abdinajiibmohamedkarshe716@gmail.com</a></p>
                </section>

                <section class="policy-section" id="notifications">
                    <div class="section-number">10</div>
                    <h2>Push Notifications</h2>
                    <p>
                        You can allow or deny push notifications from your device settings. If you deny notifications,
                        the app may still work, but you may not receive alerts about business activity, subscription
                        status, payment status, or important updates.
                    </p>
                </section>

                <section class="policy-section" id="children">
                    <div class="section-number">11</div>
                    <h2>Children's Privacy</h2>
                    <p>
                        Kobac is intended for business users and is not directed to children. We do not knowingly
                        collect personal information from children. If you believe a child has provided personal
                        information, contact us so we can review and delete it if required.
                    </p>
                </section>

                <section class="policy-section" id="transfers">
                    <div class="section-number">12</div>
                    <h2>International Data Transfers</h2>
                    <p>
                        Your information may be processed and stored in countries other than your own, depending on
                        where our server infrastructure, Firebase, hosting, support, and service providers operate. We take
                        reasonable steps to protect information according to this Privacy Policy.
                    </p>
                </section>

                <section class="policy-section" id="changes">
                    <div class="section-number">13</div>
                    <h2>Changes to This Privacy Policy</h2>
                    <p>
                        We may update this Privacy Policy from time to time. If we make important changes, we may
                        notify users through the app, website, or other reasonable methods. The updated policy will be
                        effective when posted unless stated otherwise.
                    </p>
                </section>

                <section class="policy-section" id="contact">
                    <div class="section-number">14</div>
                    <h2>Contact Us</h2>
                    <p>
                        If you have questions about this Privacy Policy or want to request account/data deletion,
                        contact us:
                    </p>

                    <div class="contact-card">
                        <p><strong>Developer / Company:</strong> Abdinajib Mohamed Karshe</p>
                        <p><strong>Email:</strong> <a href="mailto:abdinajiibmohamedkarshe716@gmail.com">abdinajiibmohamedkarshe716@gmail.com</a></p>
                        <p><strong>Privacy Policy URL:</strong> <a href="https://kobac.cajiibcreative.com/privacy-policy">https://kobac.cajiibcreative.com/privacy-policy</a></p>
                        <p><strong>Account deletion URL:</strong> <a href="https://kobac.cajiibcreative.com/account-deletion">https://kobac.cajiibcreative.com/account-deletion</a></p>
                    </div>
                </section>
            </main>

            <p class="footer">
                For more information, contact Kobac support at
                <a href="mailto:abdinajiibmohamedkarshe716@gmail.com">abdinajiibmohamedkarshe716@gmail.com</a>.
            </p>
        </div>
    </body>
</html>
