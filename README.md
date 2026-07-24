AI Email Composer

A lightweight PHP web app that turns a few form fields into a fully drafted,
professional email — no writing required. Fill in the receiver's details,
your purpose, tone, and sign-off, and Google's Gemini API generates a
complete subject + body. Review the draft in a real email-style preview
(editable in place), then send it directly via Gmail SMTP.

**Highlights**
- 🤖 AI-drafted emails via the Gemini API, tuned for formal/official/educational tone
- ✉️ Real email-style preview — not raw HTML, fully editable before sending
- 📨 Zero-dependency SMTP client — hand-rolled PHP socket implementation (no PHPMailer/Composer)
- 🎨 Custom dark, glowing UI — no frameworks, just PHP + vanilla JS + CSS
- 🔒 Preview-before-send flow — nothing goes out without a human check

**Stack:** PHP · Gemini API · Gmail SMTP · Vanilla JS
