-- ============================================================================
-- SEED MÍNIMO FUNCIONAL - Módulo Alumno
-- Inserta solo los datos necesarios para que el módulo alumno funcione
-- ============================================================================

-- 1. TIPO_AULA (necesario para aulas)
INSERT INTO tipo_aula (nombre) VALUES
    ('Aula teórica'),
    ('Laboratorio'),
    ('Taller'),
    ('Sala de cómputo')
ON CONFLICT DO NOTHING;

-- 2. EDIFICIOS (ya insertados, verificar)
-- Ya existen 5 edificios del seed anterior

-- 3. AULAS (usar IDs reales de edificio y tipo_aula)
INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'DSM-101', e.cve_edificio, ta.cve_tipo_aula
FROM edificio e, tipo_aula ta
WHERE e.nombre LIKE '%Académico%' AND ta.nombre = 'Aula teórica'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Contaduría-101', e.cve_edificio, ta.cve_tipo_aula
FROM edificio e, tipo_aula ta
WHERE e.nombre LIKE '%Académico%' AND ta.nombre = 'Aula teórica'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Lab. Cómputo I', e.cve_edificio, ta.cve_tipo_aula
FROM edificio e, tipo_aula ta
WHERE e.nombre LIKE '%Laboratorios%' AND ta.nombre = 'Laboratorio'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Dirección', e.cve_edificio, ta.cve_tipo_aula
FROM edificio e, tipo_aula ta
WHERE e.nombre LIKE '%Administrativo%' AND ta.nombre = 'Sala de cómputo'
LIMIT 1 ON CONFLICT DO NOTHING;

-- 4. FAMILIAS_ARTICULOS (con columna clave)
INSERT INTO familias_articulos (clave, nombre, cve_tipo)
SELECT 'CP', 'Cómputo portátil', t.cve_tipo
FROM tipos_bien t WHERE t.nombre = 'Cómputo y Tecnologías'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO familias_articulos (clave, nombre, cve_tipo)
SELECT 'CE', 'Cómputo de escritorio', t.cve_tipo
FROM tipos_bien t WHERE t.nombre = 'Cómputo y Tecnologías'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO familias_articulos (clave, nombre, cve_tipo)
SELECT 'PR', 'Proyectores', t.cve_tipo
FROM tipos_bien t WHERE t.nombre = 'Cómputo y Tecnologías'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO familias_articulos (clave, nombre, cve_tipo)
SELECT 'TB', 'Tablets', t.cve_tipo
FROM tipos_bien t WHERE t.nombre = 'Cómputo y Tecnologías'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO familias_articulos (clave, nombre, cve_tipo)
SELECT 'ME', 'Mobiliario escolar', t.cve_tipo
FROM tipos_bien t WHERE t.nombre = 'Mobiliario'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO familias_articulos (clave, nombre, cve_tipo)
SELECT 'MO', 'Mobiliario de oficina', t.cve_tipo
FROM tipos_bien t WHERE t.nombre = 'Mobiliario'
LIMIT 1 ON CONFLICT DO NOTHING;

-- 5. ARTICULOS (sin cve_modelo, solo cve_familia)
INSERT INTO articulos (clave, nombre, cve_familia)
SELECT 'LAP-DELL', 'Laptop Dell Latitude 5420', f.cve_familia
FROM familias_articulos f WHERE f.nombre = 'Cómputo portátil'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (clave, nombre, cve_familia)
SELECT 'LAP-HP', 'Laptop HP ProBook 450 G8', f.cve_familia
FROM familias_articulos f WHERE f.nombre = 'Cómputo portátil'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (clave, nombre, cve_familia)
SELECT 'LAP-LEN', 'Laptop Lenovo ThinkPad T14', f.cve_familia
FROM familias_articulos f WHERE f.nombre = 'Cómputo portátil'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (clave, nombre, cve_familia)
SELECT 'LAP-APL', 'Laptop Apple MacBook Air M1', f.cve_familia
FROM familias_articulos f WHERE f.nombre = 'Cómputo portátil'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (clave, nombre, cve_familia)
SELECT 'PROY-EPS', 'Proyector Epson PowerLite', f.cve_familia
FROM familias_articulos f WHERE f.nombre = 'Proyectores'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (clave, nombre, cve_familia)
SELECT 'TAB-SAM', 'Tablet Samsung Galaxy Tab', f.cve_familia
FROM familias_articulos f WHERE f.nombre = 'Tablets'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (clave, nombre, cve_familia)
SELECT 'SILLA', 'Silla escolar con paleta', f.cve_familia
FROM familias_articulos f WHERE f.nombre = 'Mobiliario escolar'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (clave, nombre, cve_familia)
SELECT 'MESA', 'Mesa para 2 personas', f.cve_familia
FROM familias_articulos f WHERE f.nombre = 'Mobiliario escolar'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (clave, nombre, cve_familia)
SELECT 'ARCH', 'Archivero metálico 4 cajones', f.cve_familia
FROM familias_articulos f WHERE f.nombre = 'Mobiliario de oficina'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (clave, nombre, cve_familia)
SELECT 'ESC', 'Escritorio ejecutivo', f.cve_familia
FROM familias_articulos f WHERE f.nombre = 'Mobiliario de oficina'
LIMIT 1 ON CONFLICT DO NOTHING;

-- 6. PROFESOR (usar cve_tipo_profesor correcto)
INSERT INTO profesor (cve_persona, cve_tipo_profesor, cve_area)
SELECT p.cve_persona, tp.cve_tipo_profesor, a.cve_area
FROM persona p, tipo_profesor tp, area a
WHERE p.nombre = 'César Geovanni' AND tp.nombre = 'Asignatura' AND a.abreviatura = 'SIST'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO profesor (cve_persona, cve_tipo_profesor, cve_area)
SELECT p.cve_persona, tp.cve_tipo_profesor, a.cve_area
FROM persona p, tipo_profesor tp, area a
WHERE p.nombre = 'María Elena' AND tp.nombre = 'Tiempo Completo' AND a.abreviatura = 'DIR'
LIMIT 1 ON CONFLICT DO NOTHING;

-- 7. BIENES (usar IDs reales de aula y articulo)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Laptop Dell Latitude 5420', 'BC-UTC-000001', 'NFC-000001', 'QR-000001', 'SN-DELL-001', 18500.00,
       a.cve_aula, p.cve_persona, art.cve_articulo, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p, articulos art
WHERE a.nombre = 'DSM-101' AND p.nombre = 'César Geovanni' AND art.clave = 'LAP-DELL'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Laptop HP ProBook 450 G8', 'BC-UTC-000002', 'NFC-000002', 'QR-000002', 'SN-HP-001', 16200.00,
       a.cve_aula, p.cve_persona, art.cve_articulo, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p, articulos art
WHERE a.nombre = 'DSM-101' AND p.nombre = 'César Geovanni' AND art.clave = 'LAP-HP'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Laptop Lenovo ThinkPad T14', 'BC-UTC-000003', 'NFC-000003', 'QR-000003', 'SN-LEN-001', 17800.00,
       a.cve_aula, p.cve_persona, art.cve_articulo, 'bueno', 'disponible', TRUE, '2024-02-01'
FROM aula a, persona p, articulos art
WHERE a.nombre = 'Contaduría-101' AND p.nombre = 'María Elena' AND art.clave = 'LAP-LEN'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Laptop Apple MacBook Air M1', 'BC-UTC-000004', 'NFC-000004', 'QR-000004', 'SN-APL-001', 22500.00,
       a.cve_aula, p.cve_persona, art.cve_articulo, 'bueno', 'disponible', TRUE, '2024-03-01'
FROM aula a, persona p, articulos art
WHERE a.nombre = 'Dirección' AND p.nombre = 'Director' AND art.clave = 'LAP-APL'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Proyector Epson PowerLite', 'BC-UTC-000021', 'NFC-000021', 'QR-000021', 'SN-PROY-001', 25000.00,
       a.cve_aula, p.cve_persona, art.cve_articulo, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p, articulos art
WHERE a.nombre = 'DSM-101' AND p.nombre = 'César Geovanni' AND art.clave = 'PROY-EPS'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Tablet Samsung Galaxy Tab', 'BC-UTC-000031', 'NFC-000031', 'QR-000031', 'SN-TAB-001', 5500.00,
       a.cve_aula, p.cve_persona, art.cve_articulo, 'bueno', 'disponible', TRUE, '2024-03-01'
FROM aula a, persona p, articulos art
WHERE a.nombre = 'DSM-101' AND p.nombre = 'César Geovanni' AND art.clave = 'TAB-SAM'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Silla escolar con paleta', 'BC-UTC-000041', 'NFC-000041', 'QR-000041', 'SN-SILLA-001', 1200.00,
       a.cve_aula, p.cve_persona, art.cve_articulo, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p, articulos art
WHERE a.nombre = 'DSM-101' AND p.nombre = 'César Geovanni' AND art.clave = 'SILLA'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Mesa para 2 personas', 'BC-UTC-000042', 'NFC-000042', 'QR-000042', 'SN-MESA-001', 2500.00,
       a.cve_aula, p.cve_persona, art.cve_articulo, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p, articulos art
WHERE a.nombre = 'DSM-101' AND p.nombre = 'César Geovanni' AND art.clave = 'MESA'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Archivero metálico 4 cajones', 'BC-UTC-000043', 'NFC-000043', 'QR-000043', 'SN-ARCH-001', 3800.00,
       a.cve_aula, p.cve_persona, art.cve_articulo, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p, articulos art
WHERE a.nombre = 'Dirección' AND p.nombre = 'Director' AND art.clave = 'ARCH'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, cve_articulo, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Escritorio ejecutivo', 'BC-UTC-000044', 'NFC-000044', 'QR-000044', 'SN-ESC-001', 5500.00,
       a.cve_aula, p.cve_persona, art.cve_articulo, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p, articulos art
WHERE a.nombre = 'Dirección' AND p.nombre = 'Director' AND art.clave = 'ESC'
LIMIT 1 ON CONFLICT DO NOTHING;

-- 8. Resetear secuencias
SELECT setval('bienes_cve_bien_seq', (SELECT COALESCE(MAX(cve_bien), 1) FROM bienes));
SELECT setval('aula_cve_aula_seq', (SELECT COALESCE(MAX(cve_aula), 1) FROM aula));
SELECT setval('articulos_cve_articulo_seq', (SELECT COALESCE(MAX(cve_articulo), 1) FROM articulos));
SELECT setval('familias_articulos_cve_familia_seq', (SELECT COALESCE(MAX(cve_familia), 1) FROM familias_articulos));

-- 9. Verificación
SELECT 'edificio' as tbl, count(*) FROM edificio
UNION ALL SELECT 'tipo_aula', count(*) FROM tipo_aula
UNION ALL SELECT 'aula', count(*) FROM aula
UNION ALL SELECT 'familias_articulos', count(*) FROM familias_articulos
UNION ALL SELECT 'articulos', count(*) FROM articulos
UNION ALL SELECT 'bienes', count(*) FROM bienes
UNION ALL SELECT 'profesor', count(*) FROM profesor
UNION ALL SELECT 'alumnos', count(*) FROM alumnos
UNION ALL SELECT 'usuarios', count(*) FROM usuarios;
