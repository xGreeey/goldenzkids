-- Default roles and permissions for admin / guard portals

INSERT INTO roles (slug, name, description) VALUES
    ('admin', 'Administrator', 'Full access to operations dashboard and report inbox'),
    ('guard', 'Security Guard', 'Field reporting, memos, and guard portal')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

INSERT INTO permissions (slug, name, description) VALUES
    ('admin.dashboard.view', 'View admin dashboard', 'Operations dashboard and KPIs'),
    ('admin.inbox.manage', 'Manage report inbox', 'Review and update DGD report status'),
    ('admin.memo.send', 'Send internal memos', 'Broadcast or targeted memos to guards'),
    ('admin.legacy_portal', 'Legacy admin portal', 'Access legacy administrative UI'),
    ('guard.portal.access', 'Guard portal', 'Submit DGD reports and view establishment list'),
    ('guard.inbox.view', 'Guard inbox', 'View own reports and notifications'),
    ('guard.corner.view', 'Guard corner', 'View memos and guard corner content'),
    ('guard.reports.submit', 'Submit reports', 'Upload DGD template evidence')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

-- Admin: all admin.* permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.slug = 'admin' AND p.slug LIKE 'admin.%';

-- Guard: all guard.* permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.slug = 'guard' AND p.slug LIKE 'guard.%';
