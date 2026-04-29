-- Agregar bienes al aula DSM-101 para tener 4+
INSERT INTO bienes (nombre, codigo_barras, nfc, no_serie, costo_unitario, cve_aula, estado_fisico, estado_prestamo, activo)
VALUES 
('Computadora Desktop HP', 'INV-DSM-001', 'NFC-DSM-001', 'SN-DSM-001', 15000, 45, 'Bueno', 'disponible', true),
('Proyector Epson', 'INV-DSM-002', 'NFC-DSM-002', 'SN-DSM-002', 8000, 45, 'Bueno', 'disponible', true);