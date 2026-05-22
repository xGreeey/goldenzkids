-- Remove demo rows seeded for guard_announcements (Guard corner mock announcements).
SET NAMES utf8mb4;

DELETE FROM guard_announcements
WHERE title IN ('Shift briefing', 'Uniform inspection');
