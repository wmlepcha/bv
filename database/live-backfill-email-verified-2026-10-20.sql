-- Run ONCE on live (phpMyAdmin) BEFORE uploading the email-verification code.
-- Existing users never verified their email (the app did not require it), so mark them verified
-- or they will all be stopped at the "verify your email" page after deploy.
-- Delete the bot/spam accounts first if you do not want them marked verified.
UPDATE users
SET email_verified_at = COALESCE(created_at, NOW())
WHERE email_verified_at IS NULL;
