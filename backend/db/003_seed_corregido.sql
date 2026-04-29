-- ============================================================================
-- CORRECCIÓN DE SEED - PostgreSQL 16
-- Inserta datos faltantes usando IDs existentes en la BD
-- ============================================================================

-- 1. EDIFICIOS (tabla vacía)
INSERT INTO edificio (nombre) VALUES
    ('ED-I - Edificio Administrativo'),
    ('ED-II - Edificio Académico'),
    ('ED-III - Laboratorios'),
    ('ED-IV - Talleres'),
    ('ED-V - Biblioteca')
ON CONFLICT DO NOTHING;

-- 2. TIPO_AULA (tabla vacía, verificar si existe)
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'tipo_aula') THEN
        INSERT INTO tipo_aula (nombre) VALUES
            ('Aula teórica'),
            ('Laboratorio'),
            ('Taller'),
            ('Sala de cómputo')
        ON CONFLICT DO NOTHING;
    END IF;
END $$;

-- 3. AULAS (tabla vacía, usar IDs reales de edificio)
INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'DSM-101', e.cve_edificio, 1 FROM edificio e WHERE e.nombre LIKE '%Académico%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'DSM-102', e.cve_edificio, 1 FROM edificio e WHERE e.nombre LIKE '%Académico%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Contaduría-101', e.cve_edificio, 1 FROM edificio e WHERE e.nombre LIKE '%Académico%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Contaduría-102', e.cve_edificio, 1 FROM edificio e WHERE e.nombre LIKE '%Académico%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Gastronomía-Lab', e.cve_edificio, 3 FROM edificio e WHERE e.nombre LIKE '%Talleres%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Turismo-101', e.cve_edificio, 1 FROM edificio e WHERE e.nombre LIKE '%Académico%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Mercadotecnia-101', e.cve_edificio, 1 FROM edificio e WHERE e.nombre LIKE '%Académico%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Lab. Cómputo I', e.cve_edificio, 2 FROM edificio e WHERE e.nombre LIKE '%Laboratorios%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Lab. Cómputo II', e.cve_edificio, 2 FROM edificio e WHERE e.nombre LIKE '%Laboratorios%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Sala de Juntas', e.cve_edificio, 4 FROM edificio e WHERE e.nombre LIKE '%Administrativo%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Dirección', e.cve_edificio, 4 FROM edificio e WHERE e.nombre LIKE '%Administrativo%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Cafetería', e.cve_edificio, 4 FROM edificio e WHERE e.nombre LIKE '%Administrativo%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Auditorio', e.cve_edificio, 4 FROM edificio e WHERE e.nombre LIKE '%Administrativo%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Biblioteca-General', e.cve_edificio, 4 FROM edificio e WHERE e.nombre LIKE '%Biblioteca%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Sala de Estudio', e.cve_edificio, 4 FROM edificio e WHERE e.nombre LIKE '%Biblioteca%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Cubículo DSM', e.cve_edificio, 4 FROM edificio e WHERE e.nombre LIKE '%Académico%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Cubículo Contaduría', e.cve_edificio, 4 FROM edificio e WHERE e.nombre LIKE '%Académico%' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO aula (nombre, cve_edificio, cve_tipo_aula)
SELECT 'Almacén', e.cve_edificio, 4 FROM edificio e WHERE e.nombre LIKE '%Administrativo%' LIMIT 1
ON CONFLICT DO NOTHING;

-- 4. MODELOS (usar IDs reales de marcas)
INSERT INTO modelos (nombre_modelo, descripcion, cve_marca)
SELECT 'Latitude 5420', 'Laptop empresarial 14"', m.cve_marca FROM marcas m WHERE m.nombre_marca = 'Dell' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO modelos (nombre_modelo, descripcion, cve_marca)
SELECT 'ProBook 450 G8', 'Laptop profesional 15.6"', m.cve_marca FROM marcas m WHERE m.nombre_marca = 'HP' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO modelos (nombre_modelo, descripcion, cve_marca)
SELECT 'ThinkPad T14', 'Laptop empresarial robusta', m.cve_marca FROM marcas m WHERE m.nombre_marca = 'Lenovo' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO modelos (nombre_modelo, descripcion, cve_marca)
SELECT 'MacBook Air M1', 'Laptop ultraligera Apple', m.cve_marca FROM marcas m WHERE m.nombre_marca = 'Apple' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO modelos (nombre_modelo, descripcion, cve_marca)
SELECT 'Galaxy Tab A8', 'Tablet Android 10.5"', m.cve_marca FROM marcas m WHERE m.nombre_marca = 'Samsung' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO modelos (nombre_modelo, descripcion, cve_marca)
SELECT 'PowerLite 2250U', 'Proyector WXGA 5000 lumens', m.cve_marca FROM marcas m WHERE m.nombre_marca = 'Epson' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO modelos (nombre_modelo, descripcion, cve_marca)
SELECT 'MH7631', 'Proyector Full HD 3600 lumens', m.cve_marca FROM marcas m WHERE m.nombre_marca = 'BenQ' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO modelos (nombre_modelo, descripcion, cve_marca)
SELECT 'OptiPlex 3090', 'Desktop compacto empresarial', m.cve_marca FROM marcas m WHERE m.nombre_marca = 'Dell' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO modelos (nombre_modelo, descripcion, cve_marca)
SELECT 'EliteDesk 400 G7', 'Desktop compacto profesional', m.cve_marca FROM marcas m WHERE m.nombre_marca = 'HP' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO modelos (nombre_modelo, descripcion, cve_marca)
SELECT 'ThinkCentre M70q', 'Desktop ultra compacto', m.cve_marca FROM marcas m WHERE m.nombre_marca = 'Lenovo' LIMIT 1
ON CONFLICT DO NOTHING;

-- 5. FAMILIAS_ARTICULOS (usar IDs reales de tipos_bien)
INSERT INTO familias_articulos (nombre, cve_tipo)
SELECT 'Cómputo portátil', t.cve_tipo FROM tipos_bien t WHERE t.nombre = 'Cómputo y Tecnologías' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO familias_articulos (nombre, cve_tipo)
SELECT 'Cómputo de escritorio', t.cve_tipo FROM tipos_bien t WHERE t.nombre = 'Cómputo y Tecnologías' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO familias_articulos (nombre, cve_tipo)
SELECT 'Impresoras', t.cve_tipo FROM tipos_bien t WHERE t.nombre = 'Cómputo y Tecnologías' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO familias_articulos (nombre, cve_tipo)
SELECT 'Proyectores', t.cve_tipo FROM tipos_bien t WHERE t.nombre = 'Cómputo y Tecnologías' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO familias_articulos (nombre, cve_tipo)
SELECT 'Tablets', t.cve_tipo FROM tipos_bien t WHERE t.nombre = 'Cómputo y Tecnologías' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO familias_articulos (nombre, cve_tipo)
SELECT 'Mobiliario escolar', t.cve_tipo FROM tipos_bien t WHERE t.nombre = 'Mobiliario' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO familias_articulos (nombre, cve_tipo)
SELECT 'Mobiliario de oficina', t.cve_tipo FROM tipos_bien t WHERE t.nombre = 'Mobiliario' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO familias_articulos (nombre, cve_tipo)
SELECT 'Equipos de cocina', t.cve_tipo FROM tipos_bien t WHERE t.nombre = 'Equipos' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO familias_articulos (nombre, cve_tipo)
SELECT 'Audio y video', t.cve_tipo FROM tipos_bien t WHERE t.nombre = 'Audio y Video' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO familias_articulos (nombre, cve_tipo)
SELECT 'Herramientas', t.cve_tipo FROM tipos_bien t WHERE t.nombre = 'Herramientas' LIMIT 1
ON CONFLICT DO NOTHING;

-- 6. ARTICULOS (usar IDs reales de familias y modelos)
-- Laptops
INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Laptop Dell Latitude 5420', fa.cve_familia, mo.cve_modelo
FROM familias_articulos fa, modelos mo
WHERE fa.nombre = 'Cómputo portátil' AND mo.nombre_modelo = 'Latitude 5420'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Laptop HP ProBook 450 G8', fa.cve_familia, mo.cve_modelo
FROM familias_articulos fa, modelos mo
WHERE fa.nombre = 'Cómputo portátil' AND mo.nombre_modelo = 'ProBook 450 G8'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Laptop Lenovo ThinkPad T14', fa.cve_familia, mo.cve_modelo
FROM familias_articulos fa, modelos mo
WHERE fa.nombre = 'Cómputo portátil' AND mo.nombre_modelo = 'ThinkPad T14'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Laptop Apple MacBook Air M1', fa.cve_familia, mo.cve_modelo
FROM familias_articulos fa, modelos mo
WHERE fa.nombre = 'Cómputo portátil' AND mo.nombre_modelo = 'MacBook Air M1'
LIMIT 1 ON CONFLICT DO NOTHING;

-- Desktops
INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Desktop Dell OptiPlex 3090', fa.cve_familia, mo.cve_modelo
FROM familias_articulos fa, modelos mo
WHERE fa.nombre = 'Cómputo de escritorio' AND mo.nombre_modelo = 'OptiPlex 3090'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Desktop HP EliteDesk 400 G7', fa.cve_familia, mo.cve_modelo
FROM familias_articulos fa, modelos mo
WHERE fa.nombre = 'Cómputo de escritorio' AND mo.nombre_modelo = 'EliteDesk 400 G7'
LIMIT 1 ON CONFLICT DO NOTHING;

-- Proyectores
INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Proyector Epson PowerLite 2250U', fa.cve_familia, mo.cve_modelo
FROM familias_articulos fa, modelos mo
WHERE fa.nombre = 'Proyectores' AND mo.nombre_modelo = 'PowerLite 2250U'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Proyector BenQ MH7631', fa.cve_familia, mo.cve_modelo
FROM familias_articulos fa, modelos mo
WHERE fa.nombre = 'Proyectores' AND mo.nombre_modelo = 'MH7631'
LIMIT 1 ON CONFLICT DO NOTHING;

-- Tablets
INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Tablet Samsung Galaxy Tab A8', fa.cve_familia, mo.cve_modelo
FROM familias_articulos fa, modelos mo
WHERE fa.nombre = 'Tablets' AND mo.nombre_modelo = 'Galaxy Tab A8'
LIMIT 1 ON CONFLICT DO NOTHING;

-- Mobiliario
INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Silla escolar con paleta', fa.cve_familia, NULL
FROM familias_articulos fa WHERE fa.nombre = 'Mobiliario escolar'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Mesa para 2 personas', fa.cve_familia, NULL
FROM familias_articulos fa WHERE fa.nombre = 'Mobiliario escolar'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Archivero metálico 4 cajones', fa.cve_familia, NULL
FROM familias_articulos fa WHERE fa.nombre = 'Mobiliario de oficina'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Escritorio ejecutivo', fa.cve_familia, NULL
FROM familias_articulos fa WHERE fa.nombre = 'Mobiliario de oficina'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Silla ergonómica de oficina', fa.cve_familia, NULL
FROM familias_articulos fa WHERE fa.nombre = 'Mobiliario de oficina'
LIMIT 1 ON CONFLICT DO NOTHING;

-- Impresoras
INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Impresora multifuncional HP', fa.cve_familia, NULL
FROM familias_articulos fa WHERE fa.nombre = 'Impresoras'
LIMIT 1 ON CONFLICT DO NOTHING;

-- Audio/Video
INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Bocina Bluetooth JBL', fa.cve_familia, NULL
FROM familias_articulos fa WHERE fa.nombre = 'Audio y video'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Cámara de video Sony', fa.cve_familia, NULL
FROM familias_articulos fa WHERE fa.nombre = 'Audio y video'
LIMIT 1 ON CONFLICT DO NOTHING;

-- Herramientas
INSERT INTO articulos (nombre, cve_familia, cve_modelo)
SELECT 'Kit de herramientas básico', fa.cve_familia, NULL
FROM familias_articulos fa WHERE fa.nombre = 'Herramientas'
LIMIT 1 ON CONFLICT DO NOTHING;

-- 7. PROFESOR (usar IDs reales de persona y tipo_profesor)
INSERT INTO profesor (cve_persona, cve_tipo_profesor, cve_area)
SELECT p.cve_persona, tp.cve_tipo, a.cve_area
FROM persona p, tipo_profesor tp, area a
WHERE p.nombre = 'César Geovanni' AND tp.nombre = 'Asignatura' AND a.abreviatura = 'SIST'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO profesor (cve_persona, cve_tipo_profesor, cve_area)
SELECT p.cve_persona, tp.cve_tipo, a.cve_area
FROM persona p, tipo_profesor tp, area a
WHERE p.nombre = 'María Elena' AND tp.nombre = 'Tiempo Completo' AND a.abreviatura = 'DIR'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO profesor (cve_persona, cve_tipo_profesor, cve_area)
SELECT p.cve_persona, tp.cve_tipo, a.cve_area
FROM persona p, tipo_profesor tp, area a
WHERE p.nombre = 'Roberto' AND tp.nombre = 'Asignatura' AND a.abreviatura = 'DIR'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO profesor (cve_persona, cve_tipo_profesor, cve_area)
SELECT p.cve_persona, tp.cve_tipo, a.cve_area
FROM persona p, tipo_profesor tp, area a
WHERE p.nombre = 'Ana Patricia' AND tp.nombre = 'Asignatura' AND a.abreviatura = 'DIR'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO profesor (cve_persona, cve_tipo_profesor, cve_area)
SELECT p.cve_persona, tp.cve_tipo, a.cve_area
FROM persona p, tipo_profesor tp, area a
WHERE p.nombre = 'Luis Fernando' AND tp.nombre = 'Tiempo Completo' AND a.abreviatura = 'DIR'
LIMIT 1 ON CONFLICT DO NOTHING;

-- 8. ALUMNOS (usar IDs reales de persona)
INSERT INTO alumnos (cve_persona, matricula, fecha_inscrito, inscrito)
SELECT p.cve_persona, '2023DSM001', '2023-08-15', TRUE
FROM persona p WHERE p.nombre = 'Sofia' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO alumnos (cve_persona, matricula, fecha_inscrito, inscrito)
SELECT p.cve_persona, '2023DSM002', '2023-08-15', TRUE
FROM persona p WHERE p.nombre = 'Carlos' AND p.apellido_paterno = 'Jiménez' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO alumnos (cve_persona, matricula, fecha_inscrito, inscrito)
SELECT p.cve_persona, '2023CON001', '2023-08-15', TRUE
FROM persona p WHERE p.nombre = 'Laura' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO alumnos (cve_persona, matricula, fecha_inscrito, inscrito)
SELECT p.cve_persona, '2024GAS001', '2024-01-10', TRUE
FROM persona p WHERE p.nombre = 'Pedro' LIMIT 1
ON CONFLICT DO NOTHING;

INSERT INTO alumnos (cve_persona, matricula, fecha_inscrito, inscrito)
SELECT p.cve_persona, '2024TUR001', '2024-01-10', TRUE
FROM persona p WHERE p.nombre = 'Valentina' LIMIT 1
ON CONFLICT DO NOTHING;

-- 9. USUARIOS (usar IDs reales de persona y roles)
INSERT INTO usuarios (cve_persona, usuario, contrasena_hash, cve_rol, activo)
SELECT p.cve_persona, 'admin',
       '$2b$10$X7Ea1zQQ0BsFQX8vF8qBp.5yVlZqY0qF6qF6qF6qF6qF6qF6qF6qF',
       r.cve_rol, TRUE
FROM persona p, roles r
WHERE p.nombre = 'Administrador' AND r.nombre = 'Administrador'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO usuarios (cve_persona, usuario, contrasena_hash, cve_rol, activo)
SELECT p.cve_persona, 'director',
       '$2b$10$X7Ea1zQQ0BsFQX8vF8qBp.5yVlZqY0qF6qF6qF6qF6qF6qF6qF6qF',
       r.cve_rol, TRUE
FROM persona p, roles r
WHERE p.nombre = 'Director' AND r.nombre = 'Director'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO usuarios (cve_persona, usuario, contrasena_hash, cve_rol, activo)
SELECT p.cve_persona, 'profesor',
       '$2b$10$X7Ea1zQQ0BsFQX8vF8qBp.5yVlZqY0qF6qF6qF6qF6qF6qF6qF6qF',
       r.cve_rol, TRUE
FROM persona p, roles r
WHERE p.nombre = 'César Geovanni' AND r.nombre = 'Profesor'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO usuarios (cve_persona, usuario, contrasena_hash, cve_rol, activo)
SELECT p.cve_persona, 'alumno',
       '$2b$10$X7Ea1zQQ0BsFQX8vF8qBp.5yVlZqY0qF6qF6qF6qF6qF6qF6qF6qF',
       r.cve_rol, TRUE
FROM persona p, roles r
WHERE p.nombre = 'Sofia' AND r.nombre = 'Estudiante'
LIMIT 1 ON CONFLICT DO NOTHING;

-- 10. BIENES (usar IDs reales de aula, articulo, modelo, marca, persona)
-- Laptops
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Laptop Dell Latitude 5420', 'BC-UTC-000001', 'NFC-000001', 'QR-000001', 'SN-DELL-001', 18500.00,
       a.cve_aula, p.cve_persona, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p
WHERE a.nombre = 'DSM-101' AND p.nombre = 'César Geovanni'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Laptop HP ProBook 450 G8', 'BC-UTC-000002', 'NFC-000002', 'QR-000002', 'SN-HP-001', 16200.00,
       a.cve_aula, p.cve_persona, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p
WHERE a.nombre = 'DSM-101' AND p.nombre = 'César Geovanni'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Laptop Lenovo ThinkPad T14', 'BC-UTC-000003', 'NFC-000003', 'QR-000003', 'SN-LEN-001', 17800.00,
       a.cve_aula, p.cve_persona, 'bueno', 'disponible', TRUE, '2024-02-01'
FROM aula a, persona p
WHERE a.nombre = 'Contaduría-101' AND p.nombre = 'María Elena'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Laptop Apple MacBook Air M1', 'BC-UTC-000004', 'NFC-000004', 'QR-000004', 'SN-APL-001', 22500.00,
       a.cve_aula, p.cve_persona, 'bueno', 'disponible', TRUE, '2024-03-01'
FROM aula a, persona p
WHERE a.nombre = 'Dirección' AND p.nombre = 'Director'
LIMIT 1 ON CONFLICT DO NOTHING;

-- Desktops
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Desktop Dell OptiPlex 3090', 'BC-UTC-000011', 'NFC-000011', 'QR-000011', 'SN-DESK-001', 12500.00,
       a.cve_aula, p.cve_persona, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p
WHERE a.nombre = 'Lab. Cómputo I' AND p.nombre = 'César Geovanni'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Desktop HP EliteDesk 400 G7', 'BC-UTC-000012', 'NFC-000012', 'QR-000012', 'SN-DESK-002', 11800.00,
       a.cve_aula, p.cve_persona, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p
WHERE a.nombre = 'Lab. Cómputo I' AND p.nombre = 'César Geovanni'
LIMIT 1 ON CONFLICT DO NOTHING;

-- Proyectores
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Proyector Epson PowerLite 2250U', 'BC-UTC-000021', 'NFC-000021', 'QR-000021', 'SN-PROY-001', 25000.00,
       a.cve_aula, p.cve_persona, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p
WHERE a.nombre = 'DSM-101' AND p.nombre = 'César Geovanni'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Proyector BenQ MH7631', 'BC-UTC-000022', 'NFC-000022', 'QR-000022', 'SN-PROY-002', 18500.00,
       a.cve_aula, p.cve_persona, 'bueno', 'disponible', TRUE, '2024-02-01'
FROM aula a, persona p
WHERE a.nombre = 'Contaduría-101' AND p.nombre = 'María Elena'
LIMIT 1 ON CONFLICT DO NOTHING;

-- Tablets
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Tablet Samsung Galaxy Tab A8', 'BC-UTC-000031', 'NFC-000031', 'QR-000031', 'SN-TAB-001', 5500.00,
       a.cve_aula, p.cve_persona, 'bueno', 'disponible', TRUE, '2024-03-01'
FROM aula a, persona p
WHERE a.nombre = 'DSM-101' AND p.nombre = 'César Geovanni'
LIMIT 1 ON CONFLICT DO NOTHING;

-- Mobiliario
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Silla escolar con paleta', 'BC-UTC-000041', 'NFC-000041', 'QR-000041', 'SN-SILLA-001', 1200.00,
       a.cve_aula, p.cve_persona, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p
WHERE a.nombre = 'DSM-101' AND p.nombre = 'César Geovanni'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Mesa para 2 personas', 'BC-UTC-000042', 'NFC-000042', 'QR-000042', 'SN-MESA-001', 2500.00,
       a.cve_aula, p.cve_persona, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p
WHERE a.nombre = 'DSM-101' AND p.nombre = 'César Geovanni'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Archivero metálico 4 cajones', 'BC-UTC-000043', 'NFC-000043', 'QR-000043', 'SN-ARCH-001', 3800.00,
       a.cve_aula, p.cve_persona, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p
WHERE a.nombre = 'Dirección' AND p.nombre = 'Director'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Escritorio ejecutivo', 'BC-UTC-000044', 'NFC-000044', 'QR-000044', 'SN-ESC-001', 5500.00,
       a.cve_aula, p.cve_persona, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p
WHERE a.nombre = 'Dirección' AND p.nombre = 'Director'
LIMIT 1 ON CONFLICT DO NOTHING;

INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo, activo, fecha_registro)
SELECT 'Silla ergonómica de oficina', 'BC-UTC-000045', 'NFC-000045', 'QR-000045', 'SN-SILLAO-001', 4200.00,
       a.cve_aula, p.cve_persona, 'bueno', 'disponible', TRUE, '2024-01-15'
FROM aula a, persona p
WHERE a.nombre = 'Dirección' AND p.nombre = 'Director'
LIMIT 1 ON CONFLICT DO NOTHING;

-- 11. PRÉSTAMOS (usar IDs reales de bienes y persona)
-- Préstamo pendiente: Sofia pide Laptop Dell
INSERT INTO prestamos (cve_bien, cve_persona_solicita, fecha_solicitud, fecha_devolucion_pactada, estado_prestamo, observaciones)
SELECT b.cve_bien, p.cve_persona, '2026-04-20 10:00:00', '2026-04-25 10:00:00', 'pendiente',
       'Necesito la laptop para proyecto de DSM'
FROM bienes b, persona p
WHERE b.nombre = 'Laptop Dell Latitude 5420' AND p.nombre = 'Sofia'
LIMIT 1 ON CONFLICT DO NOTHING;

-- Actualizar estado del bien prestado
UPDATE bienes SET estado_prestamo = 'prestado'
WHERE nombre = 'Laptop Dell Latitude 5420'
AND EXISTS (SELECT 1 FROM prestamos WHERE cve_bien = bienes.cve_bien AND estado_prestamo IN ('pendiente', 'aprobado'));

-- 12. Resetear secuencias
SELECT setval('roles_cve_rol_seq', (SELECT MAX(cve_rol) FROM roles));
SELECT setval('persona_cve_persona_seq', (SELECT MAX(cve_persona) FROM persona));
SELECT setval('edificio_cve_edificio_seq', (SELECT MAX(cve_edificio) FROM edificio));
SELECT setval('aula_cve_aula_seq', (SELECT MAX(cve_aula) FROM aula));
SELECT setval('tipos_bien_cve_tipo_seq', (SELECT MAX(cve_tipo) FROM tipos_bien));
SELECT setval('marcas_cve_marca_seq', (SELECT MAX(cve_marca) FROM marcas));
SELECT setval('modelos_cve_modelo_seq', (SELECT MAX(cve_modelo) FROM modelos));
SELECT setval('familias_articulos_cve_familia_seq', (SELECT MAX(cve_familia) FROM familias_articulos));
SELECT setval('articulos_cve_articulo_seq', (SELECT MAX(cve_articulo) FROM articulos));
SELECT setval('bienes_cve_bien_seq', (SELECT MAX(cve_bien) FROM bienes));
SELECT setval('prestamos_cve_prestamo_seq', (SELECT MAX(cve_prestamo) FROM prestamos));

-- 13. Verificación
SELECT 'roles' as tbl, count(*) FROM roles
UNION ALL SELECT 'persona', count(*) FROM persona
UNION ALL SELECT 'edificio', count(*) FROM edificio
UNION ALL SELECT 'aula', count(*) FROM aula
UNION ALL SELECT 'tipos_bien', count(*) FROM tipos_bien
UNION ALL SELECT 'marcas', count(*) FROM marcas
UNION ALL SELECT 'modelos', count(*) FROM modelos
UNION ALL SELECT 'familias_articulos', count(*) FROM familias_articulos
UNION ALL SELECT 'articulos', count(*) FROM articulos
UNION ALL SELECT 'bienes', count(*) FROM bienes
UNION ALL SELECT 'prestamos', count(*) FROM prestamos
UNION ALL SELECT 'profesor', count(*) FROM profesor
UNION ALL SELECT 'alumnos', count(*) FROM alumnos
UNION ALL SELECT 'usuarios', count(*) FROM usuarios;
