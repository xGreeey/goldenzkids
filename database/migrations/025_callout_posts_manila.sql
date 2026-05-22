-- Replace duty post catalog with Manila sites only.
-- Old posts are deactivated (not deleted) so historical report post_name values remain valid.
-- Safe to re-run.

SET NAMES utf8mb4;

INSERT IGNORE INTO callout_posts (post_name) VALUES
    ('Quiapo, Manila'),
    ('Tondo, Manila'),
    ('Sta. Ana, Manila');

UPDATE callout_posts
SET is_active = 0
WHERE post_name NOT IN ('Quiapo, Manila', 'Tondo, Manila', 'Sta. Ana, Manila');

UPDATE callout_posts
SET is_active = 1
WHERE post_name IN ('Quiapo, Manila', 'Tondo, Manila', 'Sta. Ana, Manila');

UPDATE callout_post_assignments a
INNER JOIN callout_posts p ON p.post_id = a.post_id
SET a.is_active = 0
WHERE p.is_active = 0 AND a.is_active = 1;

UPDATE guards g
INNER JOIN callout_posts p ON p.post_name = g.Post_Assigned AND p.is_active = 0
SET g.Post_Assigned = NULL
WHERE g.Post_Assigned IS NOT NULL;
