-- Add image_path and link columns to announcements table
ALTER TABLE announcements 
ADD COLUMN image_path VARCHAR(255) NULL AFTER content,
ADD COLUMN external_link VARCHAR(500) NULL AFTER image_path;
