-- Adds the signature image column used by the Profile page and the quotation / invoice / delivery note PDFs.
-- (The CRM also adds this column automatically the first time it is needed, so importing is optional.)
ALTER TABLE `admin_users` ADD COLUMN `signature_path` VARCHAR(255) DEFAULT NULL;
