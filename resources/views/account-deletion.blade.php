<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Kobac Account Deletion Request</title>
        <meta
            name="description"
            content="Account deletion request page for Kobac with deletion policy summary and request confirmation."
        >
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=literata:500,700|manrope:400,500,600,700" rel="stylesheet" />
        <style>
            :root {
                --bg: #f3efe7;
                --ink: #17211d;
                --muted: #5c6b65;
                --paper: rgba(255, 252, 247, 0.92);
                --solid: #fffdfa;
                --line: rgba(23, 33, 29, 0.12);
                --accent: #0f7a61;
                --accent-deep: #0a5745;
                --accent-soft: #d9efe7;
                --warn: #8a3f1c;
                --warn-soft: #fff0e6;
                --shadow: 0 24px 60px rgba(23, 33, 29, 0.12);
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
                line-height: 1.65;
                background:
                    radial-gradient(circle at 8% 8%, rgba(15, 122, 97, 0.14), transparent 28%),
                    radial-gradient(circle at 96% 0%, rgba(255, 197, 146, 0.3), transparent 24%),
                    linear-gradient(180deg, #faf6ef 0%, var(--bg) 100%);
            }

            a {
                color: var(--accent-deep);
            }

            h1,
            h2,
            h3 {
                margin: 0;
                font-family: 'Literata', serif;
                line-height: 1.15;
            }

            .page {
                width: min(1120px, calc(100% - 28px));
                margin: 0 auto;
                padding: 28px 0 48px;
            }

            .hero,
            .content {
                display: grid;
                grid-template-columns: 1.1fr 0.9fr;
                gap: 20px;
                align-items: start;
            }

            .card {
                border: 1px solid var(--line);
                border-radius: 28px;
                background: var(--paper);
                box-shadow: var(--shadow);
                backdrop-filter: blur(12px);
            }

            .hero-main {
                position: relative;
                overflow: hidden;
                padding: 36px;
            }

            .hero-main::after {
                content: '';
                position: absolute;
                right: -80px;
                bottom: -90px;
                width: 240px;
                height: 240px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(15, 122, 97, 0.18), transparent 68%);
                pointer-events: none;
            }

            .eyebrow {
                display: inline-flex;
                padding: 8px 12px;
                margin-bottom: 14px;
                border-radius: 999px;
                background: rgba(15, 122, 97, 0.1);
                color: var(--accent-deep);
                font-size: 0.84rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            h1 {
                max-width: 11ch;
                font-size: clamp(2.5rem, 6vw, 4.3rem);
            }

            .hero-main p,
            .muted {
                color: var(--muted);
            }

            .hero-main p {
                max-width: 62ch;
                margin: 18px 0 0;
                font-size: 1.03rem;
            }

            .actions {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                margin-top: 24px;
            }

            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 48px;
                padding: 0 18px;
                border: 0;
                border-radius: 14px;
                font: inherit;
                font-weight: 800;
                text-decoration: none;
                cursor: pointer;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .btn:hover,
            .btn:focus-visible {
                transform: translateY(-1px);
            }

            .btn-primary {
                color: #fff;
                background: linear-gradient(135deg, var(--accent), var(--accent-deep));
                box-shadow: 0 14px 30px rgba(15, 122, 97, 0.24);
            }

            .btn-soft {
                color: var(--accent-deep);
                background: rgba(15, 122, 97, 0.09);
            }

            .summary {
                padding: 28px;
            }

            .summary-item {
                padding: 16px 18px;
                border: 1px solid var(--line);
                border-radius: 18px;
                background: var(--solid);
            }

            .summary-item + .summary-item {
                margin-top: 14px;
            }

            .summary-item strong {
                display: block;
                color: var(--muted);
                font-size: 0.8rem;
                letter-spacing: 0.07em;
                text-transform: uppercase;
            }

            .summary-item span {
                display: block;
                margin-top: 6px;
                font-weight: 800;
                overflow-wrap: anywhere;
            }

            .content {
                margin-top: 20px;
            }

            .form-card,
            .policy-card,
            .success-card {
                padding: 28px;
            }

            h2 {
                margin-bottom: 10px;
                font-size: clamp(1.6rem, 4vw, 2rem);
            }

            h3 {
                margin-top: 22px;
                font-size: 1.15rem;
            }

            .form-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
                margin-top: 18px;
            }

            .field {
                display: grid;
                gap: 8px;
            }

            .field.full {
                grid-column: 1 / -1;
            }

            label {
                font-size: 0.95rem;
                font-weight: 800;
            }

            input,
            select,
            textarea {
                width: 100%;
                padding: 14px 16px;
                border: 1px solid rgba(23, 33, 29, 0.14);
                border-radius: 16px;
                background: white;
                color: var(--ink);
                font: inherit;
                outline: none;
            }

            input:focus,
            select:focus,
            textarea:focus {
                border-color: rgba(15, 122, 97, 0.65);
                box-shadow: 0 0 0 4px rgba(15, 122, 97, 0.12);
            }

            textarea {
                min-height: 120px;
                resize: vertical;
            }

            .checkbox {
                display: flex;
                gap: 12px;
                align-items: flex-start;
                padding: 16px;
                border: 1px solid var(--line);
                border-radius: 18px;
                background: rgba(15, 122, 97, 0.05);
            }

            .checkbox input {
                width: 18px;
                height: 18px;
                margin-top: 3px;
                padding: 0;
            }

            .note {
                margin-top: 18px;
                padding: 16px 18px;
                border: 1px solid rgba(138, 63, 28, 0.16);
                border-radius: 18px;
                background: var(--warn-soft);
                color: var(--warn);
            }

            .policy-list,
            .steps {
                margin: 14px 0 0;
                padding-left: 1.15rem;
            }

            .policy-list li,
            .steps li {
                margin: 0.5rem 0;
            }

            .success-card {
                display: none;
                margin-top: 20px;
                background:
                    linear-gradient(180deg, rgba(217, 239, 231, 0.9), rgba(255, 252, 247, 0.96)),
                    var(--paper);
            }

            .success-card.visible {
                display: block;
            }

            .badge {
                display: inline-flex;
                padding: 8px 12px;
                border-radius: 999px;
                background: rgba(15, 122, 97, 0.12);
                color: var(--accent-deep);
                font-size: 0.82rem;
                font-weight: 900;
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }

            .footer {
                margin-top: 22px;
                color: var(--muted);
                font-size: 0.95rem;
                text-align: center;
            }

            @media (max-width: 900px) {
                .hero,
                .content {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 640px) {
                .page {
                    width: min(100% - 16px, 1120px);
                    padding: 16px 0 28px;
                }

                .hero-main,
                .summary,
                .form-card,
                .policy-card,
                .success-card {
                    padding: 22px;
                    border-radius: 22px;
                }

                .form-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <div class="page">
            <section class="hero">
                <div class="card hero-main">
                    <div class="eyebrow">Account Deletion</div>
                    <h1>Request deletion of your Kobac account</h1>
                    <p>
                        Use this page to request deletion of your Kobac account and related personal data. Please read
                        the policy summary before submitting your request.
                    </p>
                    <div class="actions">
                        <a class="btn btn-primary" href="#deletion-form">Open deletion form</a>
                        <a class="btn btn-soft" href="https://kobac.cajiibcreative.com/privacy-policy">Privacy policy</a>
                    </div>
                </div>

                <aside class="card summary" aria-label="Page summary">
                    <div class="summary-item">
                        <strong>Before you submit</strong>
                        <span>Review what may be deleted or retained</span>
                    </div>
                    <div class="summary-item">
                        <strong>Use account details</strong>
                        <span>Enter the phone or email linked to your account</span>
                    </div>
                    <div class="summary-item">
                        <strong>Verification</strong>
                        <span>We may verify ownership before deleting data</span>
                    </div>
                </aside>
            </section>

            <section class="content">
                <div class="card form-card" id="deletion-form">
                    <h2>Deletion request form</h2>
                    <p class="muted">
                        Fill out the form below to request account deletion. Make sure the contact details match the
                        account you want reviewed.
                    </p>

                    <form id="accountDeletionForm">
                        <div class="form-grid">
                            <div class="field">
                                <label for="full_name">Full name</label>
                                <input id="full_name" name="full_name" type="text" placeholder="Enter your full name" required>
                            </div>

                            <div class="field">
                                <label for="email">Email address</label>
                                <input id="email" name="email" type="email" placeholder="name@example.com" required>
                            </div>

                            <div class="field">
                                <label for="phone">Phone number</label>
                                <input id="phone" name="phone" type="tel" placeholder="+252..." required>
                            </div>

                            <div class="field">
                                <label for="account_type">Account type</label>
                                <select id="account_type" name="account_type" required>
                                    <option value="">Select account type</option>
                                    <option>Personal account</option>
                                    <option>Business owner account</option>
                                    <option>Business staff account</option>
                                </select>
                            </div>

                            <div class="field full">
                                <label for="business_name">Business name</label>
                                <input id="business_name" name="business_name" type="text" placeholder="If applicable">
                            </div>

                            <div class="field full">
                                <label for="reason">Reason for deleting the account</label>
                                <textarea id="reason" name="reason" placeholder="Tell us why you want to delete the account"></textarea>
                            </div>

                            <div class="field full">
                                <label for="confirmation_text">Type DELETE to confirm</label>
                                <input id="confirmation_text" name="confirmation_text" type="text" placeholder="DELETE" required>
                            </div>

                            <div class="field full">
                                <label class="checkbox" for="confirm_request">
                                    <input id="confirm_request" name="confirm_request" type="checkbox" required>
                                    <span>
                                        I confirm that I am requesting account deletion and understand that some data
                                        may be retained where required for legal, accounting, fraud prevention,
                                        payment, dispute, or security reasons.
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="actions">
                            <button class="btn btn-primary" type="submit">Submit deletion request</button>
                            <a class="btn btn-soft" href="https://kobac.cajiibcreative.com/privacy-policy">Read privacy policy</a>
                        </div>

                        <div class="note">
                            By submitting this request, you acknowledge that some records may be retained where
                            required for legal, accounting, fraud prevention, payment, dispute, or security reasons.
                        </div>
                    </form>
                </div>

                <aside class="card policy-card">
                    <h2>Deletion policy summary</h2>
                    <p class="muted">
                        This policy summary helps users understand what happens before they click submit.
                    </p>

                    <h3>What may be deleted</h3>
                    <ul class="policy-list">
                        <li>User profile and account access details</li>
                        <li>Associated personal information where deletion is allowed</li>
                        <li>Business-linked access for the requesting user when applicable</li>
                    </ul>

                    <h3>What may be retained</h3>
                    <ul class="policy-list">
                        <li>Accounting, payment, fraud prevention, or legal compliance records</li>
                        <li>Security logs needed to protect Kobac and its users</li>
                        <li>Records connected to unresolved subscriptions, payments, or disputes</li>
                    </ul>

                    <h3>Suggested user flow</h3>
                    <ol class="steps">
                        <li>User opens this public deletion page.</li>
                        <li>User reads the policy summary.</li>
                        <li>User fills in the deletion form.</li>
                        <li>User clicks submit and sees a confirmation message.</li>
                    </ol>

                    <div class="note">
                        Account deletion is reviewed according to the Kobac Privacy Policy and applicable legal or
                        business record requirements.
                    </div>
                </aside>
            </section>

            <section class="card success-card" id="successState" aria-live="polite">
                <span class="badge">Request submitted</span>
                <h2 style="margin-top: 14px;">Static confirmation</h2>
                <p>
                    Thank you. Your account deletion request has been submitted for review.
                </p>
                <p>
                    Public deletion page URL:
                    <a href="https://kobac.cajiibcreative.com/account-deletion">https://kobac.cajiibcreative.com/account-deletion</a>
                </p>
            </section>

            <p class="footer">
                For more details, please review the
                <a href="https://kobac.cajiibcreative.com/privacy-policy">Kobac Privacy Policy</a>
                or contact
                <a href="mailto:abdinajiibmohamedkarshe716@gmail.com">abdinajiibmohamedkarshe716@gmail.com</a>.
            </p>
        </div>

        <script>
            const form = document.getElementById('accountDeletionForm');
            const successState = document.getElementById('successState');
            const confirmationField = document.getElementById('confirmation_text');

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                if (confirmationField.value.trim().toUpperCase() !== 'DELETE') {
                    confirmationField.focus();
                    confirmationField.setCustomValidity('Please type DELETE to continue.');
                    confirmationField.reportValidity();
                    return;
                }

                confirmationField.setCustomValidity('');
                successState.classList.add('visible');
                successState.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });

            confirmationField.addEventListener('input', function () {
                confirmationField.setCustomValidity('');
            });
        </script>
    </body>
</html>
