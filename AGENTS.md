# Project guidance for coding agents

## Project

Balangkare Sur Trading is a capstone marketplace for farmers and buyers in Balangkare, Nueva Ecija. The intended stack is PHP 8+, MySQL/MariaDB, HTML, CSS, and browser-side JavaScript. Keep the experience simple, mobile-friendly, and usable by people with different levels of digital experience.

## Expected skills

When helping with this project, apply care and sound practices in:

- PHP application structure, sessions, form handling, and server-side validation.
- MySQL schema design, relationships, indexes, transactions, and PDO prepared statements.
- Accessible, responsive HTML and CSS.
- JavaScript for small, progressive interactions; important validation and permissions must also be enforced by PHP.
- Web application security, especially authentication, authorization, privacy, file uploads, and safe handling of secrets.
- Explaining technical choices in plain language suitable for a student capstone.

## Review-first authority

The user wants to review proposed work before it is carried out.

- Read-only inspection and explanation may be done when requested.
- Before creating, editing, renaming, or deleting project files, first describe the exact proposed changes and wait for the user's explicit approval.
- Before running a command that changes project files, databases, installed software, or other persistent state, first explain what it will change and wait for approval.
- Do not treat a request to inspect, analyze, or recommend as permission to implement the recommendation.
- Do not deploy, publish, push to GitHub, install dependencies, change account or service settings, or connect external services without explaining the specific action and receiving the user's approval.
- Keep work inside this project folder unless the user explicitly approves another location.
- Do not read, print, commit, or share secrets. Keep credentials and API keys out of source code, browser JavaScript, screenshots, and Git history. Ask the user to enter secrets directly into private local configuration when needed.
- When approval is needed, present a concise plan or diff summary so the user can review the concrete change before deciding.

## Security requirements

- Use PDO prepared statements for values in database queries. Never build SQL by concatenating user input.
- Validate and authorize every sensitive action on the server. Check ownership as well as account role.
- Escape user-controlled text when rendering HTML. Use CSRF protection for state-changing forms.
- Store passwords with PHP's password hashing and verification functions. Regenerate the session ID after authentication and use safe session-cookie settings in production.
- Validate uploads by size and detected MIME type, use generated filenames, and prevent uploaded files from being executed as code.
- Keep API keys and production credentials on the server in private configuration or environment variables. Never expose them to browser code or commit them.
- Treat payment preferences in this capstone as coordination details unless the user explicitly approves a payment-provider integration. Use sandbox mode for demonstrations.
- Do not claim the app is secure or production-ready without a review and verification. Explain known limits and remaining security work plainly.

## Product scope

- The marketplace connects farmers and buyers; it does not guarantee crop quality, prices, delivery, or farmer income.
- Make units and prices explicit, such as pesos per kilogram or per sack.
- Order requests are not payments unless a separately approved payment integration is implemented.
- Clearly identify the Farm Guide as a demo until a real AI provider is deliberately configured. Keep AI keys server-side and do not present general crop guidance as a guaranteed local recommendation.

## Working conventions

- Prefer small, understandable changes that fit the existing structure.
- Preserve the user's existing content and design choices unless the approved change calls for an update.
- Do not add or run tests unless the user asks for testing or verification. Never claim checks were run if they were not.
- Update `README.md` when approved setup or operating instructions change.
