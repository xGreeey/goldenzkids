-- Portal account display names (sidebar, superadmin account forms)

ALTER TABLE users
    ADD COLUMN First_Name VARCHAR(64) NULL AFTER Email,
    ADD COLUMN Last_Name VARCHAR(64) NULL AFTER First_Name;
