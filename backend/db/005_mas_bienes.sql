-- Articulos con claves cortas (VARCHAR(4))
INSERT INTO articulos (clave, nombre, cve_familia) VALUES
('LPDL', 'Laptop Dell Latitude', (SELECT cve_familia FROM familias_articulos WHERE nombre = 'Computo portatil' LIMIT 1)),
('LPHP', 'Laptop HP ProBook', (SELECT cve_familia FROM familias_articulos WHERE nombre = 'Computo portatil' LIMIT 1)),
('LPLE', 'Laptop Lenovo T14', (SELECT cve_familia FROM familias_articulos WHERE nombre = 'Computo portatil' LIMIT 1)),
('LPAP', 'Laptop MacBook Air', (SELECT cve_familia FROM familias_articulos WHERE nombre = 'Computo portatil' LIMIT 1)),
('PRYE', 'Proyector Epson', (SELECT cve_familia FROM familias_articulos WHERE nombre = 'Proyectores' LIMIT 1)),
('TABS', 'Tablet Samsung', (SELECT cve_familia FROM familias_articulos WHERE nombre = 'Tablets' LIMIT 1)),
('SILA', 'Silla escolar', (SELECT cve_familia FROM familias_articulos WHERE nombre = 'Mobiliario escolar' LIMIT 1)),
('MESA', 'Mesa escolar', (SELECT cve_familia FROM familias_articulos WHERE nombre = 'Mobiliario escolar' LIMIT 1)),
('ARCH', 'Archivero', (SELECT cve_familia FROM familias_articulos WHERE nombre = 'Mobiliario de oficina' LIMIT 1)),
('ESCR', 'Escritorio', (SELECT cve_familia FROM familias_articulos WHERE nombre = 'Mobiliario de oficina' LIMIT 1))
ON CONFLICT DO NOTHING;

-- Mas bienes
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Laptop Dell Latitude', 'BC-UTC-000001', 'NFC-000001', 'QR-000001', 'SN-DELL-001', 18500.00,
       (SELECT cve_aula FROM aula WHERE nombre = 'DSM-101' LIMIT 1),
       (SELECT cve_persona FROM persona WHERE nombre LIKE 'Cesar%' LIMIT 1),
       (SELECT cve_articulo FROM articulos WHERE clave = 'LPDL' LIMIT 1),
       'bueno', 'disponible', TRUE, '2024-01-15'
ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Laptop HP ProBook', 'BC-UTC-000002', 'NFC-000002', 'QR-000002', 'SN-HP-001', 16200.00,
       (SELECT cve_aula FROM aula WHERE nombre = 'DSM-101' LIMIT 1),
       (SELECT cve_persona FROM persona WHERE nombre LIKE 'Cesar%' LIMIT 1),
       (SELECT cve_articulo FROM articulos WHERE clave = 'LPHP' LIMIT 1),
       'bueno', 'disponible', TRUE, '2024-01-15'
ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Laptop Lenovo T14', 'BC-UTC-000003', 'NFC-000003', 'QR-000003', 'SN-LEN-001', 17800.00,
       (SELECT cve_aula FROM aula WHERE nombre = 'Contaduria-101' LIMIT 1),
       (SELECT cve_persona FROM persona WHERE nombre LIKE 'Maria%' LIMIT 1),
       (SELECT cve_articulo FROM articulos WHERE clave = 'LPLE' LIMIT 1),
       'bueno', 'disponible', TRUE, '2024-02-01'
ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Laptop MacBook Air', 'BC-UTC-000004', 'NFC-000004', 'QR-000004', 'SN-APL-001', 22500.00,
       (SELECT cve_aula FROM aula WHERE nombre = 'Direccion' LIMIT 1),
       (SELECT cve_persona FROM persona WHERE nombre = 'Director' LIMIT 1),
       (SELECT cve_articulo FROM articulos WHERE clave = 'LPAP' LIMIT 1),
       'bueno', 'disponible', TRUE, '2024-03-01'
ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Proyector Epson', 'BC-UTC-000021', 'NFC-000021', 'QR-000021', 'SN-PROY-001', 25000.00,
       (SELECT cve_aula FROM aula WHERE nombre = 'DSM-101' LIMIT 1),
       (SELECT cve_persona FROM persona WHERE nombre LIKE 'Cesar%' LIMIT 1),
       (SELECT cve_articulo FROM articulos WHERE clave = 'PRYE' LIMIT 1),
       'bueno', 'disponible', TRUE, '2024-01-15'
ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Tablet Samsung', 'BC-UTC-000031', 'NFC-000031', 'QR-000031', 'SN-TAB-001', 5500.00,
       (SELECT cve_aula FROM aula WHERE nombre = 'DSM-101' LIMIT 1),
       (SELECT cve_persona FROM persona WHERE nombre LIKE 'Cesar%' LIMIT 1),
       (SELECT cve_articulo FROM articulos WHERE clave = 'TABS' LIMIT 1),
       'bueno', 'disponible', TRUE, '2024-03-01'
ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Silla escolar', 'BC-UTC-000041', 'NFC-000041', 'QR-000041', 'SN-SILLA-001', 1200.00,
       (SELECT cve_aula FROM aula WHERE nombre = 'DSM-101' LIMIT 1),
       (SELECT cve_persona FROM persona WHERE nombre LIKE 'Cesar%' LIMIT 1),
       (SELECT cve_articulo FROM articulos WHERE clave = 'SILA' LIMIT 1),
       'bueno', 'disponible', TRUE, '2024-01-15'
ON CONFLICT DO NOTHING;

SELECT count(*) as total_bienes FROM bienes;
