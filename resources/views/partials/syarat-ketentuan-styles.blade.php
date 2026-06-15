@once
<style>
    .terms-document-frame {
        background: #eef1f5;
        border: 1px solid #cfd8e3;
        border-radius: 8px;
        box-shadow: inset 0 1px 4px rgba(15, 23, 42, 0.08);
        height: min(72vh, 720px);
        overflow-y: auto;
        padding: 24px;
    }

    .terms-document-frame--approved {
        height: auto;
        margin: 0;
        max-height: none;
        overflow: visible;
        padding: 32px;
    }

    .terms-document {
        background: #ffffff;
        border: 1px solid #d8dee8;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.12);
        color: #111827;
        font-family: "Times New Roman", Times, serif;
        font-size: 16px;
        line-height: 1.62;
        margin: 0 auto;
        max-width: 820px;
        min-height: 1080px;
        padding: 56px 64px;
    }

    .terms-document .header {
        border-bottom: 2px solid #111827;
        margin-bottom: 28px;
        padding-bottom: 18px;
        text-align: center;
    }

    .terms-document .header h1 {
        color: #111827;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 20px;
        font-weight: 800;
        letter-spacing: 0.03em;
        line-height: 1.35;
        margin: 0 0 12px;
        text-transform: uppercase;
    }

    .terms-document__subtitle {
        color: #374151;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.02em;
        line-height: 1.45;
        margin: 0;
        text-transform: uppercase;
    }

    .terms-document .section {
        counter-increment: section;
        margin-bottom: 26px;
    }

    .terms-document .section-title {
        background: transparent;
        border-bottom: 1px solid #9ca3af;
        border-radius: 0;
        color: #111827;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 15px;
        font-weight: 800;
        letter-spacing: 0.02em;
        margin: 0 0 14px;
        padding: 0 0 7px;
        text-transform: uppercase;
    }

    .terms-document .subsection {
        margin-bottom: 18px;
        padding-left: 4px;
    }

    .terms-document .subsection-title {
        background: transparent;
        border: 0;
        color: #111827;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 14px;
        font-weight: 800;
        margin: 0 0 7px;
        padding: 0;
    }

    .terms-document p {
        margin: 0 0 10px;
        text-align: justify;
    }

    .terms-document .requirement-list {
        margin: 0 0 12px;
        padding-left: 30px;
    }

    .terms-document .requirement-list li {
        margin-bottom: 6px;
        padding-left: 4px;
        text-align: justify;
    }

    .terms-document .legal-text {
        background: #fbfbfb;
        border: 1px solid #d1d5db;
        border-left: 4px solid #374151;
        border-radius: 4px;
        font-style: normal;
        margin: 12px 0 14px;
        padding: 14px 16px;
    }

    .terms-document .legal-text h4 {
        color: #111827;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 13px;
        font-weight: 800;
        margin: 12px 0 5px;
    }

    .terms-document .legal-text .requirement-list {
        margin-bottom: 0;
    }

    .terms-document .checkbox-section {
        background: #fbfbfb;
        border: 1px solid #cfd8e3;
        border-radius: 4px;
        margin: 14px 0 24px;
        padding: 14px;
    }

    .terms-document .checkbox-item {
        align-items: flex-start;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 3px;
        display: flex;
        gap: 12px;
        padding: 13px 14px;
    }

    .terms-document__checkbox {
        background: #ffffff;
        border: 1.8px solid #111827;
        border-radius: 2px;
        display: inline-block;
        flex: 0 0 auto;
        height: 16px;
        margin-top: 5px;
        position: relative;
        width: 16px;
    }

    .terms-document__checkbox::after {
        color: #111827;
        content: "";
        font-family: Arial, Helvetica, sans-serif;
        font-size: 14px;
        font-weight: 700;
        left: 2px;
        line-height: 1;
        position: absolute;
        top: -1px;
    }

    .terms-document input.terms-document__checkbox {
        cursor: pointer;
        height: 18px;
        margin: 4px 0 0;
        width: 18px;
    }

    .terms-document input.terms-document__checkbox:disabled {
        cursor: not-allowed;
        opacity: 0.65;
    }

    .terms-document .checkbox-item p {
        margin-bottom: 0;
    }

    .terms-approval-check {
        background: #ffffff;
        border: 1px solid #cfd8e3;
        border-radius: 8px;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
        margin-top: 2px;
        padding: 16px 18px 16px 46px;
    }

    .terms-approval-check .form-check-input {
        margin-left: -28px;
        margin-top: 5px;
    }

    .terms-approval-check .form-check-label {
        color: #1f2937;
        font-weight: 700;
        line-height: 1.5;
    }

    .terms-access-page {
        background: #f3f6fa;
    }

    .terms-access-shell {
        background: #ffffff;
        border: 1px solid #d8dee8;
        border-radius: 8px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.10);
        overflow: hidden;
    }

    .terms-access-header {
        align-items: flex-start;
        border-bottom: 1px solid #d8dee8;
        display: flex;
        gap: 16px;
        justify-content: space-between;
        padding: 22px 24px;
    }

    .terms-access-eyebrow {
        color: #374151;
        display: block;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        margin-bottom: 6px;
        text-transform: uppercase;
    }

    .terms-access-title {
        color: #111827;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .terms-access-subtitle {
        color: #64748b;
        margin-bottom: 0;
        max-width: 760px;
    }

    .terms-access-meta {
        background: #d8dee8;
        display: grid;
        gap: 1px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .terms-access-meta div {
        background: #f8fafc;
        padding: 14px 18px;
    }

    .terms-access-meta span {
        color: #64748b;
        display: block;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
        text-transform: uppercase;
    }

    .terms-access-meta strong {
        color: #111827;
        display: block;
    }

    .terms-document--approved {
        margin-bottom: 0;
        margin-top: 0;
    }

    @media (max-width: 767.98px) {
        .terms-document-frame {
            height: min(68vh, 620px);
            padding: 12px;
        }

        .terms-document-frame--approved {
            height: auto;
            padding: 12px;
        }

        .terms-document {
            font-size: 14px;
            min-height: 0;
            padding: 28px 22px;
        }

        .terms-document .header h1 {
            font-size: 16px;
        }

        .terms-document__subtitle {
            font-size: 11px;
        }

        .terms-access-header {
            display: block;
        }

        .terms-access-header .btn {
            margin-top: 14px;
            width: 100%;
        }

        .terms-access-meta {
            grid-template-columns: 1fr;
        }
    }
</style>
@endonce
