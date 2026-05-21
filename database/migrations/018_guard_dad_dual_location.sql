-- Dual GPS stamps: attendance sheet (step 1) and site evidence (step 2).

SET NAMES utf8mb4;

ALTER TABLE guard_dad_submissions
    ADD COLUMN IF NOT EXISTS sheet_latitude DECIMAL(10, 7) NULL AFTER location_label,
    ADD COLUMN IF NOT EXISTS sheet_longitude DECIMAL(10, 7) NULL AFTER sheet_latitude,
    ADD COLUMN IF NOT EXISTS sheet_accuracy_m DECIMAL(8, 2) NULL AFTER sheet_longitude,
    ADD COLUMN IF NOT EXISTS sheet_location_label VARCHAR(512) NULL AFTER sheet_accuracy_m,
    ADD COLUMN IF NOT EXISTS evidence_latitude DECIMAL(10, 7) NULL AFTER sheet_location_label,
    ADD COLUMN IF NOT EXISTS evidence_longitude DECIMAL(10, 7) NULL AFTER evidence_latitude,
    ADD COLUMN IF NOT EXISTS evidence_accuracy_m DECIMAL(8, 2) NULL AFTER evidence_longitude,
    ADD COLUMN IF NOT EXISTS evidence_location_label VARCHAR(512) NULL AFTER evidence_accuracy_m;

-- Backfill evidence columns from legacy submit_* fields when present.
UPDATE guard_dad_submissions
SET evidence_latitude = submit_latitude,
    evidence_longitude = submit_longitude,
    evidence_accuracy_m = submit_accuracy_m,
    evidence_location_label = location_label
WHERE evidence_latitude IS NULL
  AND submit_latitude IS NOT NULL;
