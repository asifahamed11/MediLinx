-- Update admin password to "admin123"
UPDATE `users` 
SET `password` = '$2y$10$0JRjg77Dr33w3dNDV1fBTOv.bZBfnZWSY7GYzISAE81MdQOZ6VF9.'
WHERE `username` = 'admin' AND `email` = 'admin@medilinx.com' AND `role` = 'admin'; 