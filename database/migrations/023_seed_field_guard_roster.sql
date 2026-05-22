-- Field guard roster requires matching users rows (fk_guards_user).
-- Data is seeded by php/024_seed_field_guard_users_roster.php after this placeholder runs.

SET NAMES utf8mb4;

-- Legacy SQL seed kept for reference; PHP migration performs inserts.
SELECT 1;

/* INSERT IGNORE INTO guards (Company_ID, Head_ID, Rank, Last_Name, First_Name, Middle_Name, Post_Assigned) VALUES
    ('ABC-2026-0301', NULL, NULL, 'Garcia', 'Paulo', 'Emmanuel', NULL),
    ('ABC-2026-0302', NULL, NULL, 'Flores', 'Janelle', 'Marie', NULL),
    ('ABC-2026-0303', NULL, NULL, 'Navarro', 'Christian', 'Paolo', NULL),
    ('ABC-2026-0304', NULL, NULL, 'Torres', 'Samantha', 'Louise', NULL),
    ('ABC-2026-0305', NULL, NULL, 'Bautista', 'Vincent', 'Adrian', NULL),
    ('ABC-2026-0306', NULL, NULL, 'Castillo', 'Patricia', 'Anne', NULL),
    ('ABC-2026-0307', NULL, NULL, 'Herrera', 'John', 'Carlo', NULL),
    ('ABC-2026-0308', NULL, NULL, 'Fernandez', 'Nicole', 'Andrea', NULL),
    ('ABC-2026-0309', NULL, NULL, 'Aquino', 'Rafael', 'Dominic', NULL),
    ('ABC-2026-0310', NULL, NULL, 'Salazar', 'Bea', 'Camille', NULL),
    ('ABC-2026-0311', NULL, NULL, 'Lim', 'Adrian', 'Miguel', NULL),
    ('ABC-2026-0312', NULL, NULL, 'Ramirez', 'Mark', 'Anthony', NULL),
    ('ABC-2026-0313', NULL, NULL, 'Gutierrez', 'Mikaela', 'Joy', NULL),
    ('ABC-2026-0314', NULL, NULL, 'Diaz', 'Kevin', 'Lawrence', NULL),
    ('ABC-2026-0315', NULL, NULL, 'Rivera', 'Alyssa', 'Nicole', NULL),
    ('ABC-2026-0316', NULL, NULL, 'Morales', 'Francis', 'Xavier', NULL),
    ('ABC-2026-0317', NULL, NULL, 'Santiago', 'Katrina', 'Mae', NULL),
    ('ABC-2026-0318', NULL, NULL, 'Cruz', 'Elijah', 'Matthew', NULL),
    ('ABC-2026-0319', NULL, NULL, 'Lopez', 'Camille', 'Therese', NULL),
    ('ABC-2026-0320', NULL, NULL, 'Romero', 'Nathaniel', 'James', NULL),
    ('ABC-2026-0321', NULL, NULL, 'Valdez', 'Bianca', 'Sofia', NULL),
    ('ABC-2026-0322', NULL, NULL, 'Perez', 'Angelo', 'Marcus', NULL),
    ('ABC-2026-0323', NULL, NULL, 'Velasco', 'Chelsea', 'Anne', NULL),
    ('ABC-2026-0324', NULL, NULL, 'Mendoza', 'Carla', 'Denise', NULL),
    ('ABC-2026-0325', NULL, NULL, 'Chavez', 'Gabriel', 'Lorenzo', NULL),
    ('ABC-2026-0326', NULL, NULL, 'Manalo', 'Danielle', 'Faith', NULL),
    ('ABC-2026-0327', NULL, NULL, 'Mercado', 'Joshua', 'Daniel', NULL),
    ('ABC-2026-0328', NULL, NULL, 'Evangelista', 'Trisha', 'Mae', NULL),
    ('ABC-2026-0329', NULL, NULL, 'Ramos', 'Carl', 'Benedict', NULL),
    ('ABC-2026-0330', NULL, NULL, 'Cabrera', 'Princess', 'Mae', NULL),
    ('ABC-2026-0331', NULL, NULL, 'Dominguez', 'Ivan', 'Cedrick', NULL),
    ('ABC-2026-0332', NULL, NULL, 'Soriano', 'Elaine', 'Patricia', NULL),
    ('ABC-2026-0333', NULL, NULL, 'Mendoza', 'Kurt', 'Raphael', NULL),
    ('ABC-2026-0334', NULL, NULL, 'Alonzo', 'Hazel', 'Marie', NULL),
    ('ABC-2026-0335', NULL, NULL, 'Pascual', 'Nathan', 'Kyle', NULL); */
