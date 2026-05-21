-- Optional: link callout_head_guards rows to head-guard login accounts (users.role = 0).
-- Safe to re-run. Adjust Company_ID values to match your users table.

SET NAMES utf8mb4;

-- Example: roster head guard ABC-2024-0021 -> Jose Abad Cruz (change if your IDs differ)
UPDATE callout_head_guards hg
INNER JOIN users u ON u.Company_ID = 'ABC-2024-0021' AND u.role = 0 AND u.is_active = 1
SET hg.company_id = u.Company_ID
WHERE hg.display_name = 'Jose Abad Cruz' AND (hg.company_id IS NULL OR hg.company_id = '');
