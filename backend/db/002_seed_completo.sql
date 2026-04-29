-- ============================================================================
-- SEED COMPLETO - PostgreSQL 16
-- Universidad Tecnológica de la Costa (UTC)
-- Datos reales de adscripciones, bienes, aulas, profesores, etc.
-- ============================================================================
-- Ejecutar DESPUÉS de db/bd.sql
-- ============================================================================

-- ============================================================================
-- 0. LIMPIAR DATOS EXISTENTES (TRUNCATE cascaded)
-- ============================================================================
TRUNCATE TABLE bitacora_movimientos CASCADE;
TRUNCATE TABLE prestamos CASCADE;
TRUNCATE TABLE auditoria_detalle CASCADE;
TRUNCATE TABLE auditorias CASCADE;
TRUNCATE TABLE bienes CASCADE;
TRUNCATE TABLE folio_siia CASCADE;
TRUNCATE TABLE numero_resguardo CASCADE;
TRUNCATE TABLE adscripcion_persona CASCADE;
TRUNCATE TABLE adscripcion CASCADE;
TRUNCATE TABLE articulos CASCADE;
TRUNCATE TABLE familias_articulos CASCADE;
TRUNCATE TABLE modelos CASCADE;
TRUNCATE TABLE marcas CASCADE;
TRUNCATE TABLE aula CASCADE;
TRUNCATE TABLE tipo_aula CASCADE;
TRUNCATE TABLE edificio CASCADE;
TRUNCATE TABLE tipos_bien CASCADE;
TRUNCATE TABLE alumnos CASCADE;
TRUNCATE TABLE profesor CASCADE;
TRUNCATE TABLE usuarios CASCADE;
TRUNCATE TABLE persona CASCADE;
TRUNCATE TABLE roles CASCADE;
TRUNCATE TABLE area CASCADE;
TRUNCATE TABLE tipo_profesor CASCADE;
TRUNCATE TABLE motivo_de_movimiento CASCADE;

-- ============================================================================
-- 1. ROLES (4 roles independientes)
-- ============================================================================
INSERT INTO roles (nombre, descripcion) VALUES
    ('Administrador', 'Acceso total al sistema'),
    ('Profesor', 'Acceso a módulos de profesor'),
    ('Estudiante', 'Acceso básico a módulos de estudiante'),
    ('Director', 'Acceso a módulos de director y reportes generales, solo lectura');

-- ============================================================================
-- 2. AREAS
-- ============================================================================
INSERT INTO area (cve_area_padre, abreviatura, nombre) VALUES
    (NULL, 'DIR', 'Dirección General'),
    (1, 'SIST', 'Sistemas');

-- ============================================================================
-- 3. TIPO_PROFESOR
-- ============================================================================
INSERT INTO tipo_profesor (nombre, descripcion) VALUES
    ('TC', 'Tiempo Completo'),
    ('AS', 'Asignatura'),
    ('MT', 'Medio Tiempo');

-- ============================================================================
-- 4. PERSONA (12 personas reales)
-- ============================================================================
INSERT INTO persona (nombre, apellido_paterno, apellido_materno) VALUES
    ('Administrador', 'del', 'Sistema'),           -- cve_persona = 1
    ('Director', 'General', 'de la UTC'),          -- cve_persona = 2
    ('César Geovanni', 'Machuca', 'Pereida'),      -- cve_persona = 3 (Profesor DSM)
    ('María Elena', 'López', 'Hernández'),         -- cve_persona = 4 (Profesora Contaduría)
    ('Roberto', 'Ramírez', 'Soto'),                -- cve_persona = 5 (Profesor Gastronomía)
    ('Ana Patricia', 'Martínez', 'Cruz'),          -- cve_persona = 6 (Profesora Turismo)
    ('Luis Fernando', 'Torres', 'Vega'),           -- cve_persona = 7 (Profesor Mercadotecnia)
    ('Sofia', 'Flores', 'Mora'),                   -- cve_persona = 8 (Alumno DSM)
    ('Carlos', 'Jiménez', 'Luna'),                 -- cve_persona = 9 (Alumno DSM)
    ('Laura', 'García', 'Díaz'),                   -- cve_persona = 10 (Alumno Contaduría)
    ('Pedro', 'Sánchez', 'Ríos'),                  -- cve_persona = 11 (Alumno Gastronomía)
    ('Valentina', 'Hernández', 'Pérez');           -- cve_persona = 12 (Alumno Turismo)

-- ============================================================================
-- 5. PROFESOR (5 profesores)
-- ============================================================================
INSERT INTO profesor (cve_persona, cve_tipo_profesor, cve_area) VALUES
    (3, 2, 2),  -- Geovanni - ASignatura - Sistemas
    (4, 1, 1),  -- María Elena - Tiempo Completo - Dirección
    (5, 2, 1),  -- Roberto - ASignatura - Dirección
    (6, 2, 1),  -- Ana Patricia - ASignatura - Dirección
    (7, 1, 1);  -- Luis Fernando - Tiempo Completo - Dirección

-- ============================================================================
-- 6. ALUMNOS (5 alumnos)
-- ============================================================================
INSERT INTO alumnos (cve_persona, matricula, fecha_inscrito, inscrito) VALUES
    (8,  '2023DSM001', '2023-08-15', TRUE),
    (9,  '2023DSM002', '2023-08-15', TRUE),
    (10, '2023CON001', '2023-08-15', TRUE),
    (11, '2024GAS001', '2024-01-10', TRUE),
    (12, '2024TUR001', '2024-01-10', TRUE);

-- ============================================================================
-- 7. ADSCRIPCIONES (38 adscripciones reales de la UTC)
-- ============================================================================
INSERT INTO adscripcion (clave_adscripcion, nombre_adscripcion) VALUES
    ('73000000', 'UNIVERSIDAD TECNOLÓGICA DE LA COSTA'),
    ('73100000', 'RECTORÍA'),
    ('73100100', 'ABOGADO GENERAL'),
    ('73100200', 'ÓRGANO INTERNO DE CONTROL'),
    ('73100300', 'DEPTO. PLANEACIÓN, EVALUACIÓN Y GESTIÓN DE LA CALIDAD'),
    ('73110000', 'DIRECCIÓN ACADÉMICA Y DE CALIDAD EDUCATIVA'),
    ('73111000', 'DIRECCIÓN CIENCIAS AGROPECUARIAS'),
    ('73111100', 'CARRERA ACUICULTURA'),
    ('73111200', 'CARRERA PRODUCCIÓN AGROBIOTECNOLÓGICA'),
    ('73111300', 'CARRERA TECNOLOGÍA DE ALIMENTOS'),
    ('73112000', 'DIRECCIÓN NEGOCIOS'),
    ('73112100', 'CARRERA CONTADURÍA'),
    ('73112200', 'CARRERA GASTRONOMÍA'),
    ('73112300', 'CARRERA GESTIÓN DEL CAPITAL HUMANO'),
    ('73112400', 'CARRERA MERCADOTECNIA'),
    ('73112500', 'CARRERA TURISMO'),
    ('73113000', 'DIRECCIÓN TECNOLOGÍAS DE LA INFORMACIÓN'),
    ('73113100', 'CARRERA DESARROLLO DE SOFTWARE MULTIPLATAFORMA'),
    ('73114000', 'DEPARTAMENTO DE SERVICIOS ESCOLARES'),
    ('73114100', 'BIBLIOTECA'),
    ('73115000', 'SALA DE LECTURA'),
    ('73116000', 'PSICÓLOGO'),
    ('73117000', 'COORDINACIÓN DEPORTIVA'),
    ('73120000', 'DIRECCIÓN ADMINISTRACIÓN Y FINANZAS'),
    ('73121000', 'SUBDIRECCIÓN ADMINISTRACIÓN Y FINANZAS'),
    ('73121100', 'DEPTO. DE ADQUISICIONES Y CONTROL PATRIMONIAL'),
    ('73121200', 'DEPTO. DE CONTABILIDAD Y CONTROL PRESUPUESTAL'),
    ('73121300', 'DEPTO. DE INFRAESTRUCTURA'),
    ('73121400', 'DEPTO. DE SERVICIOS ADMINISTRATIVOS'),
    ('73130000', 'DIRECCIÓN DE ARCHIVO'),
    ('73140000', 'DIRECCIÓN DE VINCULACIÓN'),
    ('73140100', 'DEPTO. EXTENSIÓN, DIFUSIÓN Y DIVULGACIÓN UNIVERSITARIA'),
    ('73140200', 'DEPTO. DE PRÁCTICAS Y ESTADÍAS'),
    ('73140300', 'COORD. SERVICIOS TECNOLÓGICOS Y EDUCACIÓN CONTINUA'),
    ('73140400', 'OFICINA DE BECAS'),
    ('73140500', 'ENFERMERÍA'),
    ('73180000', 'BODEGA'),
    ('73190000', 'MANTENIMIENTO');

-- ============================================================================
-- 8. ADSCRIPCION_PERSONA (vincular personas a adscripciones)
-- ============================================================================
INSERT INTO adscripcion_persona (cve_adscripcion, cve_persona) VALUES
    (27, 1),  -- Admin → Depto. Contabilidad
    (2,  2),  -- Director → Rectoría
    (18, 3),  -- Geovanni → DSM
    (12, 4),  -- María Elena → Contaduría
    (13, 5),  -- Roberto → Gastronomía
    (16, 6),  -- Ana Patricia → Turismo
    (15, 7),  -- Luis Fernando → Mercadotecnia
    (18, 8),  -- Sofia → DSM (alumno)
    (18, 9),  -- Carlos → DSM (alumno)
    (12, 10), -- Laura → Contaduría (alumno)
    (13, 11), -- Pedro → Gastronomía (alumno)
    (16, 12); -- Valentina → Turismo (alumno)

-- ============================================================================
-- 9. USUARIOS (4 con hashes bcrypt reales)
-- ============================================================================
-- Contraseñas: Admin123!, Director123!, Profesor123!, Alumno123!
-- Hashes generados con password_hash() de PHP
INSERT INTO usuarios (usuario, contrasena_hash, cve_rol, nombre, email, activo, cve_persona) VALUES
    ('admin',     '$2y$10$lPEWbFX1jqB7hpebks2i3.9QLJGUWn3wbICLKOPxzdojioTdVvBlG', 1, 'Administrador del Sistema', 'admin@utc.edu.mx', true, 1),
    ('director',  '$2y$10$6/NIfMpu1j0cgxMLocmxk.mXSdyNw9YvoGA5cqy0HkblWg5grrFDW', 4, 'Director General de la UTC', 'director@utc.edu.mx', true, 2),
    ('profesor',  '$2y$10$xpfD.MUIoOSK.n5stogva.BHOVEellNk390BBd3UB.TXwBCs00.fe', 2, 'Profesor de Ejemplo', 'profesor@utc.edu.mx', true, 3),
    ('alumno',    '$2y$10$XzwQD13YBLFEgP6nJdMN1eDxpGBg80MmAvZrnevnxUykWuF.j1h8K', 3, 'Estudiante de Ejemplo', 'alumno@utc.edu.mx', true, 8);

-- ============================================================================
-- 10. EDIFICIOS (5 edificios reales de la UTC)
-- ============================================================================
INSERT INTO edificio (nombre, abreviatura) VALUES
    ('Edificio I - Administrativo', 'ED-I'),
    ('Edificio II - Académico', 'ED-II'),
    ('Edificio III - Laboratorios', 'ED-III'),
    ('Edificio IV - Talleres', 'ED-IV'),
    ('Edificio V - Biblioteca y Servicios', 'ED-V');

-- ============================================================================
-- 11. TIPOS DE AULA (4)
-- ============================================================================
INSERT INTO tipo_aula (nombre) VALUES
    ('Aula Normal'),
    ('Laboratorio'),
    ('Taller'),
    ('Oficina/Cubículo');

-- ============================================================================
-- 12. AULAS (18 aulas con nombres realistas)
-- ============================================================================
INSERT INTO aula (cve_edificio, cve_tipo_aula, nombre, capacidad, cve_profesor) VALUES
    -- Edificio I (Administrativo)
    (1, 4, 'Cubi. Mtro. Geovanni', 2, 1),   -- cve_aula = 1
    (1, 4, 'Cubi. Mtra. López', 2, 2),       -- cve_aula = 2
    (1, 4, 'Oficina Dirección', 4, NULL),    -- cve_aula = 3
    -- Edificio II (Académico)
    (2, 1, 'Aula DSM-101', 35, 1),           -- cve_aula = 4
    (2, 1, 'Aula Contaduría-102', 30, 2),    -- cve_aula = 5
    (2, 1, 'Aula Gastronomía-103', 25, 3),   -- cve_aula = 6
    (2, 1, 'Aula Turismo-104', 30, 4),       -- cve_aula = 7
    (2, 1, 'Aula Mercadotecnia-105', 30, 5), -- cve_aula = 8
    -- Edificio III (Laboratorios)
    (3, 2, 'Lab. Cómputo I', 20, 1),         -- cve_aula = 9
    (3, 2, 'Lab. Cómputo II', 20, NULL),     -- cve_aula = 10
    (3, 2, 'Lab. Alimentos', 15, NULL),      -- cve_aula = 11
    -- Edificio IV (Talleres)
    (4, 3, 'Taller Cocina', 15, 3),          -- cve_aula = 12
    (4, 3, 'Taller Recepción', 20, 4),       -- cve_aula = 13
    -- Edificio V (Biblioteca y Servicios)
    (5, 1, 'Biblioteca', 40, NULL),          -- cve_aula = 14
    (5, 1, 'Sala de Lectura', 20, NULL),     -- cve_aula = 15
    (5, 2, 'Lab. Idiomas', 15, NULL),        -- cve_aula = 16
    (5, 4, 'Cubi. Psicología', 2, NULL),     -- cve_aula = 17
    (1, 4, 'Cubi. Subdirector', 2, NULL);    -- cve_aula = 18

-- ============================================================================
-- 13. TIPOS DE BIEN (6 tipos reales)
-- ============================================================================
INSERT INTO tipos_bien (clave, nombre) VALUES
    ('MC', 'Cómputo y Tecnologías'),
    ('MB', 'Mobiliario'),
    ('EQ', 'Equipos'),
    ('AU', 'Audio y Video'),
    ('TR', 'Equipo de Transporte'),
    ('HE', 'Herramientas');

-- ============================================================================
-- 14. MARCAS (15 marcas)
-- ============================================================================
INSERT INTO marcas (nombre_marca) VALUES
    ('Dell'),
    ('HP'),
    ('Lenovo'),
    ('Apple'),
    ('Samsung'),
    ('Epson'),
    ('BenQ'),
    ('LG'),
    ('Cisco'),
    ('TP-Link'),
    ('Microsoft'),
    ('Sony'),
    ('Bosch'),
    ('Stanley'),
    ('Genérica');

-- ============================================================================
-- 15. MODELOS (25 modelos)
-- ============================================================================
INSERT INTO modelos (nombre_modelo, cve_marca, descripcion) VALUES
    ('Latitude 5520', 1, 'Laptop empresarial 15.6"'),
    ('Latitude 3420', 1, 'Laptop empresarial 14"'),
    ('ProBook 450 G8', 2, 'Laptop empresarial 15.6"'),
    ('ProBook 640 G8', 2, 'Laptop empresarial 14"'),
    ('ThinkPad T14', 3, 'Laptop empresarial 14"'),
    ('ThinkPad L15', 3, 'Laptop empresarial 15.6"'),
    ('MacBook Air M1', 4, 'Laptop ultradelgada 13"'),
    ('MacBook Pro M2', 4, 'Laptop profesional 14"'),
    ('Galaxy Tab A8', 5, 'Tablet 10.5"'),
    ('X41', 6, 'Proyector XGA'),
    ('L-180', 6, 'Impresora multifuncional'),
    ('MH530', 7, 'Proyector Full HD'),
    ('24MK430H', 8, 'Monitor IPS 24"'),
    ('27MP400-B', 8, 'Monitor IPS 27"'),
    ('SF250-24', 9, 'Switch 24 puertos'),
    ('TL-SG1024D', 10, 'Switch 24 puertos Gigabit'),
    ('Surface Pro 8', 11, 'Tablet/PC híbrida 13"'),
    ('VAIO SX14', 12, 'Laptop ultradelgada 14"'),
    ('GSR 120', 13, 'Taladro inalámbrico'),
    ('FatMax', 14, 'Caja de herramientas'),
    ('LaserJet Pro M404', 2, 'Impresora láser monocromática'),
    ('EcoTank L3250', 2, 'Impresora multifuncional tinta continua'),
    ('VS250J', 7, 'Proyector SVGA'),
    ('V2422H', 8, 'Monitor Full HD 24"'),
    ('OptiPlex 7090', 1, 'Desktop empresarial SFF');

-- ============================================================================
-- 16. FAMILIAS DE ARTÍCULOS (12 familias reales)
-- ============================================================================
INSERT INTO familias_articulos (clave, nombre, cve_tipo) VALUES
    ('EC', 'Equipo de Cómputo', 1),
    ('IM', 'Impresoras', 1),
    ('PR', 'Proyectores', 1),
    ('MN', 'Monitores', 1),
    ('RD', 'Redes', 1),
    ('MB', 'Mobiliario de Oficina', 2),
    ('MS', 'Mesas', 2),
    ('SL', 'Sillas', 2),
    ('AU', 'Audio y Video', 4),
    ('HE', 'Herramientas', 6),
    ('EQ', 'Equipos Diversos', 3),
    ('TR', 'Transporte', 5);

-- ============================================================================
-- 17. ARTÍCULOS (50+ artículos del catálogo real)
-- ============================================================================
INSERT INTO articulos (clave, nombre, cve_familia) VALUES
    -- Equipo de Cómputo (EC)
    ('LAP', 'Laptop', 1),
    ('DSK', 'Desktop', 1),
    ('TAB', 'Tablet', 1),
    -- Impresoras (IM)
    ('IMP', 'Impresora', 2),
    ('MFP', 'Multifuncional', 2),
    -- Proyectores (PR)
    ('PRO', 'Proyector', 3),
    -- Monitores (MN)
    ('MON', 'Monitor', 4),
    -- Redes (RD)
    ('SWT', 'Switch', 5),
    ('RTR', 'Router', 5),
    ('AP', 'Access Point', 5),
    -- Mobiliario (MB)
    ('ARC', 'Archivero', 6),
    ('ESC', 'Escritorio', 6),
    ('EST', 'Estante', 6),
    -- Mesas (MS)
    ('MES', 'Mesa de trabajo', 7),
    ('MSC', 'Mesa de conferencias', 7),
    ('MCR', 'Mesa de centro', 7),
    -- Sillas (SL)
    ('SIE', 'Silla ejecutiva', 8),
    ('SIV', 'Silla de visita', 8),
    ('SIO', 'Silla operativa', 8),
    -- Audio y Video (AU)
    ('BOC', 'Bocina', 9),
    ('MIC', 'Micrófono', 9),
    ('TV', 'Televisor', 9),
    ('CAM', 'Cámara de video', 9),
    -- Herramientas (HE)
    ('TAL', 'Taladro', 10),
    ('DES', 'Desarmador', 10),
    ('MAR', 'Martillo', 10),
    ('PIN', 'Pinzas', 10),
    ('LLV', 'Llave Allen', 10),
    ('CAJ', 'Caja de herramientas', 10),
    ('TES', 'Tester eléctrico', 10),
    -- Equipos diversos (EQ)
    ('UPS', 'UPS', 11),
    ('REG', 'Regulador de voltaje', 11),
    ('VEN', 'Ventilador', 11),
    ('A/C', 'Aire acondicionado', 11),
    ('ESC', 'Escáner', 11),
    ('CAL', 'Calculadora', 11),
    ('ENC', 'Encuadernadora', 11),
    ('TRZ', 'Trituradora de papel', 11),
    ('CAN', 'Cañón (proyector portátil)', 11),
    -- Transporte (TR)
    ('AUT', 'Automóvil', 12),
    ('CAM', 'Camioneta', 12),
    ('MOT', 'Motocicleta', 12);

-- ============================================================================
-- 18. BIENES (60 bienes con NFC, barcode, QR, costo, ubicación real)
-- ============================================================================
-- Aula 1: Cubi. Mtro. Geovanni (5 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Laptop Dell Latitude 5520', 'BC-UTC-000001', 'NFC-00001', 'QR-BIEN-00001', 'SN-DLL-5520-01', 'F-2023-0001', 'Laptop empresarial para uso docente', 1, 1, 22500.00, 1, 3, 'bueno', 'disponible'),
    ('Monitor Dell 24"', 'BC-UTC-000002', 'NFC-00002', 'QR-BIEN-00002', 'SN-DLL-MON-01', 'F-2023-0002', 'Monitor IPS 24 pulgadas', 13, 7, 4500.00, 1, 3, 'bueno', 'disponible'),
    ('Archivero 1 puerta 5 cajones', 'BC-UTC-000003', 'NFC-00003', 'QR-BIEN-00003', 'S/N', 'F-2022-0089', 'Archivero metálico ejecutivo', NULL, 11, 7459.13, 1, 3, 'bueno', 'disponible'),
    ('Silla de visita', 'BC-UTC-000004', 'NFC-00004', 'QR-BIEN-00004', 'S/N', 'F-2022-0090', 'Silla de visita negra', NULL, 18, 1972.00, 1, 3, 'bueno', 'disponible'),
    ('Escritorio en L', 'BC-UTC-000005', 'NFC-00005', 'QR-BIEN-00005', 'S/N', 'F-2022-0091', 'Escritorio en L con cajonera', NULL, 12, 6800.00, 1, 3, 'bueno', 'disponible');

-- Aula 2: Cubi. Mtra. López (4 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Desktop Dell OptiPlex 7090', 'BC-UTC-000006', 'NFC-00006', 'QR-BIEN-00006', 'SN-OPX-7090-01', 'F-2023-0010', 'Desktop empresarial SFF', 25, 2, 18500.00, 2, 4, 'bueno', 'disponible'),
    ('Impresora HP LaserJet Pro', 'BC-UTC-000007', 'NFC-00007', 'QR-BIEN-00007', 'SN-HPL-404-01', 'F-2023-0011', 'Impresora láser monocromática', 21, 4, 5200.00, 2, 4, 'regular', 'disponible'),
    ('Archivero pedestal', 'BC-UTC-000008', 'NFC-00008', 'QR-BIEN-00008', 'CS-1131', 'F-2022-0092', 'Archivero pedestal metálico', NULL, 11, 3228.86, 2, 4, 'bueno', 'disponible'),
    ('Silla ejecutiva', 'BC-UTC-000009', 'NFC-00009', 'QR-BIEN-00009', 'S/N', 'F-2022-0093', 'Silla ejecutiva ergonómica', NULL, 17, 4500.00, 2, 4, 'bueno', 'disponible');

-- Aula 3: Oficina Dirección (5 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Laptop HP ProBook 450 G8', 'BC-UTC-000010', 'NFC-00010', 'QR-BIEN-00010', 'SN-HPB-450-01', 'F-2023-0015', 'Laptop para dirección', 3, 1, 19800.00, 3, 2, 'bueno', 'prestado'),
    ('Monitor LG 27"', 'BC-UTC-000011', 'NFC-00011', 'QR-BIEN-00011', 'SN-LG-27MP-01', 'F-2023-0016', 'Monitor IPS 27 pulgadas', 14, 7, 5800.00, 3, 2, 'bueno', 'disponible'),
    ('Mesa de conferencias', 'BC-UTC-000012', 'NFC-00012', 'QR-BIEN-00012', 'S/N', 'F-2022-0100', 'Mesa de conferencias 8 personas', NULL, 15, 15000.00, 3, 2, 'bueno', 'disponible'),
    ('Silla ejecutiva premium', 'BC-UTC-000013', 'NFC-00013', 'QR-BIEN-00013', 'S/N', 'F-2022-0101', 'Silla ejecutiva de piel', NULL, 17, 8500.00, 3, 2, 'bueno', 'disponible'),
    ('Proyector Epson X41', 'BC-UTC-000014', 'NFC-00014', 'QR-BIEN-00014', 'SN-EPX-41-01', 'F-2023-0020', 'Proyector para sala de juntas', 10, 6, 12000.00, 3, 2, 'regular', 'disponible');

-- Aula 4: Aula DSM-101 (6 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Laptop Dell Latitude 3420', 'BC-UTC-000015', 'NFC-00015', 'QR-BIEN-000015', 'SN-DLL-3420-01', 'F-2023-0025', 'Laptop para alumno DSM', 2, 1, 16500.00, 4, 3, 'bueno', 'disponible'),
    ('Laptop Dell Latitude 3420', 'BC-UTC-000016', 'NFC-00016', 'QR-BIEN-000016', 'SN-DLL-3420-02', 'F-2023-0026', 'Laptop para alumno DSM', 2, 1, 16500.00, 4, 3, 'bueno', 'disponible'),
    ('Laptop Dell Latitude 3420', 'BC-UTC-000017', 'NFC-00017', 'QR-BIEN-000017', 'SN-DLL-3420-03', 'F-2023-0027', 'Laptop para alumno DSM', 2, 1, 16500.00, 4, 3, 'bueno', 'prestado'),
    ('Laptop Dell Latitude 3420', 'BC-UTC-000018', 'NFC-00018', 'QR-BIEN-000018', 'SN-DLL-3420-04', 'F-2023-0028', 'Laptop para alumno DSM', 2, 1, 16500.00, 4, 3, 'bueno', 'disponible'),
    ('Laptop Dell Latitude 3420', 'BC-UTC-000019', 'NFC-00019', 'QR-BIEN-000019', 'SN-DLL-3420-05', 'F-2023-0029', 'Laptop para alumno DSM', 2, 1, 16500.00, 4, 3, 'bueno', 'disponible'),
    ('Proyector BenQ MH530', 'BC-UTC-000020', 'NFC-00020', 'QR-BIEN-000020', 'SN-BNQ-MH530-01', 'F-2023-0030', 'Proyector Full HD para aula', 12, 6, 14500.00, 4, 3, 'bueno', 'disponible');

-- Aula 5: Aula Contaduría-102 (5 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Laptop Lenovo ThinkPad T14', 'BC-UTC-000021', 'NFC-00021', 'QR-BIEN-000021', 'SN-LNV-T14-01', 'F-2023-0035', 'Laptop para Contaduría', 5, 1, 21000.00, 5, 4, 'bueno', 'disponible'),
    ('Laptop Lenovo ThinkPad T14', 'BC-UTC-000022', 'NFC-00022', 'QR-BIEN-000022', 'SN-LNV-T14-02', 'F-2023-0036', 'Laptop para Contaduría', 5, 1, 21000.00, 5, 4, 'bueno', 'prestado'),
    ('Laptop Lenovo ThinkPad T14', 'BC-UTC-000023', 'NFC-00023', 'QR-BIEN-000023', 'SN-LNV-T14-03', 'F-2023-0037', 'Laptop para Contaduría', 5, 1, 21000.00, 5, 4, 'bueno', 'disponible'),
    ('Laptop Lenovo ThinkPad T14', 'BC-UTC-000024', 'NFC-00024', 'QR-BIEN-000024', 'SN-LNV-T14-04', 'F-2023-0038', 'Laptop para Contaduría', 5, 1, 21000.00, 5, 4, 'bueno', 'disponible'),
    ('Impresora HP EcoTank L3250', 'BC-UTC-000025', 'NFC-00025', 'QR-BIEN-000025', 'SN-EPL-3250-01', 'F-2023-0039', 'Impresora multifuncional tinta continua', 22, 5, 4800.00, 5, 4, 'bueno', 'disponible');

-- Aula 6: Aula Gastronomía-103 (4 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Laptop HP ProBook 640 G8', 'BC-UTC-000026', 'NFC-00026', 'QR-BIEN-000026', 'SN-HPB-640-01', 'F-2023-0040', 'Laptop para Gastronomía', 4, 1, 20500.00, 6, 5, 'bueno', 'disponible'),
    ('Laptop HP ProBook 640 G8', 'BC-UTC-000027', 'NFC-00027', 'QR-BIEN-000027', 'SN-HPB-640-02', 'F-2023-0041', 'Laptop para Gastronomía', 4, 1, 20500.00, 6, 5, 'bueno', 'disponible'),
    ('Proyector Epson VS250J', 'BC-UTC-000028', 'NFC-00028', 'QR-BIEN-000028', 'SN-EPV-250-01', 'F-2023-0042', 'Proyector SVGA para aula', 23, 6, 9500.00, 6, 5, 'bueno', 'disponible'),
    ('Televisor LG 55"', 'BC-UTC-000029', 'NFC-00029', 'QR-BIEN-000029', 'SN-LG-TV55-01', 'F-2023-0043', 'Smart TV 55 pulgadas', NULL, 22, 12000.00, 6, 5, 'bueno', 'disponible');

-- Aula 7: Aula Turismo-104 (5 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Laptop Lenovo ThinkPad L15', 'BC-UTC-000030', 'NFC-00030', 'QR-BIEN-000030', 'SN-LNV-L15-01', 'F-2023-0050', 'Laptop para Turismo', 6, 1, 17500.00, 7, 6, 'bueno', 'disponible'),
    ('Laptop Lenovo ThinkPad L15', 'BC-UTC-000031', 'NFC-00031', 'QR-BIEN-000031', 'SN-LNV-L15-02', 'F-2023-0051', 'Laptop para Turismo', 6, 1, 17500.00, 7, 6, 'bueno', 'prestado'),
    ('Laptop Lenovo ThinkPad L15', 'BC-UTC-000032', 'NFC-00032', 'QR-BIEN-000032', 'SN-LNV-L15-03', 'F-2023-0052', 'Laptop para Turismo', 6, 1, 17500.00, 7, 6, 'bueno', 'disponible'),
    ('Monitor LG 24"', 'BC-UTC-000033', 'NFC-00033', 'QR-BIEN-000033', 'SN-LG-24MK-01', 'F-2023-0053', 'Monitor IPS 24 pulgadas', 13, 7, 4500.00, 7, 6, 'bueno', 'disponible'),
    ('Proyector BenQ VS250J', 'BC-UTC-000034', 'NFC-00034', 'QR-BIEN-000034', 'SN-BNQ-VS250-01', 'F-2023-0054', 'Proyector SVGA para aula', 23, 6, 9500.00, 7, 6, 'regular', 'disponible');

-- Aula 8: Aula Mercadotecnia-105 (5 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Laptop Lenovo ThinkPad L15', 'BC-UTC-000035', 'NFC-00035', 'QR-BIEN-000035', 'SN-LNV-L15-04', 'F-2023-0060', 'Laptop para Mercadotecnia', 6, 1, 17500.00, 8, 7, 'bueno', 'disponible'),
    ('Laptop Lenovo ThinkPad L15', 'BC-UTC-000036', 'NFC-00036', 'QR-BIEN-000036', 'SN-LNV-L15-05', 'F-2023-0061', 'Laptop para Mercadotecnia', 6, 1, 17500.00, 8, 7, 'bueno', 'disponible'),
    ('Laptop Lenovo ThinkPad L15', 'BC-UTC-000037', 'NFC-00037', 'QR-BIEN-000037', 'SN-LNV-L15-06', 'F-2023-0062', 'Laptop para Mercadotecnia', 6, 1, 17500.00, 8, 7, 'malo', 'disponible'),
    ('Impresora HP LaserJet Pro', 'BC-UTC-000038', 'NFC-00038', 'QR-BIEN-000038', 'SN-HPL-404-02', 'F-2023-0063', 'Impresora láser monocromática', 21, 4, 5200.00, 8, 7, 'bueno', 'disponible'),
    ('Proyector Epson X41', 'BC-UTC-000039', 'NFC-00039', 'QR-BIEN-000039', 'SN-EPX-41-02', 'F-2023-0064', 'Proyector XGA para aula', 10, 6, 12000.00, 8, 7, 'bueno', 'disponible');

-- Aula 9: Lab. Cómputo I (6 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Desktop Dell OptiPlex 7090', 'BC-UTC-000040', 'NFC-00040', 'QR-BIEN-000040', 'SN-OPX-7090-02', 'F-2023-0070', 'Desktop para laboratorio', 25, 2, 18500.00, 9, 3, 'bueno', 'disponible'),
    ('Desktop Dell OptiPlex 7090', 'BC-UTC-000041', 'NFC-00041', 'QR-BIEN-000041', 'SN-OPX-7090-03', 'F-2023-0071', 'Desktop para laboratorio', 25, 2, 18500.00, 9, 3, 'bueno', 'disponible'),
    ('Desktop Dell OptiPlex 7090', 'BC-UTC-000042', 'NFC-00042', 'QR-BIEN-000042', 'SN-OPX-7090-04', 'F-2023-0072', 'Desktop para laboratorio', 25, 2, 18500.00, 9, 3, 'bueno', 'prestado'),
    ('Monitor Dell 24"', 'BC-UTC-000043', 'NFC-00043', 'QR-BIEN-000043', 'SN-DLL-MON-02', 'F-2023-0073', 'Monitor IPS 24 pulgadas', 13, 7, 4500.00, 9, 3, 'bueno', 'disponible'),
    ('Monitor Dell 24"', 'BC-UTC-000044', 'NFC-00044', 'QR-BIEN-000044', 'SN-DLL-MON-03', 'F-2023-0074', 'Monitor IPS 24 pulgadas', 13, 7, 4500.00, 9, 3, 'bueno', 'disponible'),
    ('UPS 1500VA', 'BC-UTC-000045', 'NFC-00045', 'QR-BIEN-000045', 'SN-UPS-1500-01', 'F-2023-0075', 'UPS para protección eléctrica', NULL, 31, 3500.00, 9, 3, 'bueno', 'disponible');

-- Aula 10: Lab. Cómputo II (4 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Desktop Dell OptiPlex 7090', 'BC-UTC-000046', 'NFC-00046', 'QR-BIEN-000046', 'SN-OPX-7090-05', 'F-2023-0080', 'Desktop para laboratorio', 25, 2, 18500.00, 10, NULL, 'bueno', 'disponible'),
    ('Desktop Dell OptiPlex 7090', 'BC-UTC-000047', 'NFC-00047', 'QR-BIEN-000047', 'SN-OPX-7090-06', 'F-2023-0081', 'Desktop para laboratorio', 25, 2, 18500.00, 10, NULL, 'bueno', 'disponible'),
    ('Monitor Dell 24"', 'BC-UTC-000048', 'NFC-00048', 'QR-BIEN-000048', 'SN-DLL-MON-04', 'F-2023-0082', 'Monitor IPS 24 pulgadas', 13, 7, 4500.00, 10, NULL, 'bueno', 'disponible'),
    ('Switch Cisco SF250-24', 'BC-UTC-000049', 'NFC-00049', 'QR-BIEN-000049', 'SN-CIS-SF250-01', 'F-2023-0083', 'Switch administrable 24 puertos', 15, 8, 6500.00, 10, NULL, 'bueno', 'disponible');

-- Aula 11: Lab. Alimentos (3 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Laptop Apple MacBook Air M1', 'BC-UTC-000050', 'NFC-00050', 'QR-BIEN-000050', 'SN-APL-MBA-01', 'F-2023-0090', 'Laptop ultradelgada para laboratorio', 7, 1, 24500.00, 11, NULL, 'bueno', 'disponible'),
    ('Proyector Epson X41', 'BC-UTC-000051', 'NFC-00051', 'QR-BIEN-000051', 'SN-EPX-41-03', 'F-2023-0091', 'Proyector XGA para laboratorio', 10, 6, 12000.00, 11, NULL, 'bueno', 'disponible'),
    ('Cámara de video Sony', 'BC-UTC-000052', 'NFC-00052', 'QR-BIEN-000052', 'SN-SNY-CAM-01', 'F-2023-0092', 'Cámara de video para documentación', NULL, 23, 15000.00, 11, NULL, 'bueno', 'disponible');

-- Aula 12: Taller Cocina (4 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Mesa de trabajo acero inox.', 'BC-UTC-000053', 'NFC-00053', 'QR-BIEN-000053', 'S/N', 'F-2022-0110', 'Mesa de trabajo de acero inoxidable 1.8m', NULL, 14, 8500.00, 12, 5, 'bueno', 'disponible'),
    ('Mesa de trabajo acero inox.', 'BC-UTC-000054', 'NFC-00054', 'QR-BIEN-000054', 'S/N', 'F-2022-0111', 'Mesa de trabajo de acero inoxidable 1.8m', NULL, 14, 8500.00, 12, 5, 'bueno', 'disponible'),
    ('Mesa de trabajo acero inox.', 'BC-UTC-000055', 'NFC-00055', 'QR-BIEN-000055', 'S/N', 'F-2022-0112', 'Mesa de trabajo de acero inoxidable 1.8m', NULL, 14, 8500.00, 12, 5, 'regular', 'disponible'),
    ('Silla operativa', 'BC-UTC-000056', 'NFC-00056', 'QR-BIEN-000056', 'S/N', 'F-2022-0113', 'Silla operativa para taller', NULL, 19, 1500.00, 12, 5, 'bueno', 'disponible');

-- Aula 13: Taller Recepción (3 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Mesa de conferencias', 'BC-UTC-000057', 'NFC-00057', 'QR-BIEN-000057', 'S/N', 'F-2022-0120', 'Mesa de conferencias para taller', NULL, 15, 12000.00, 13, 6, 'bueno', 'disponible'),
    ('Silla de visita', 'BC-UTC-000058', 'NFC-00058', 'QR-BIEN-000058', 'S/N', 'F-2022-0121', 'Silla de visita para taller', NULL, 18, 1972.00, 13, 6, 'bueno', 'disponible'),
    ('Silla de visita', 'BC-UTC-000059', 'NFC-00059', 'QR-BIEN-000059', 'S/N', 'F-2022-0122', 'Silla de visita para taller', NULL, 18, 1972.00, 13, 6, 'bueno', 'disponible');

-- Aula 14: Biblioteca (6 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Desktop Dell OptiPlex 7090', 'BC-UTC-000060', 'NFC-00060', 'QR-BIEN-000060', 'SN-OPX-7090-07', 'F-2023-0100', 'Desktop para catálogo biblioteca', 25, 2, 18500.00, 14, NULL, 'bueno', 'disponible'),
    ('Desktop Dell OptiPlex 7090', 'BC-UTC-000061', 'NFC-00061', 'QR-BIEN-000061', 'SN-OPX-7090-08', 'F-2023-0101', 'Desktop para consulta biblioteca', 25, 2, 18500.00, 14, NULL, 'bueno', 'disponible'),
    ('Impresora HP EcoTank L3250', 'BC-UTC-000062', 'NFC-00062', 'QR-BIEN-000062', 'SN-EPL-3250-02', 'F-2023-0102', 'Impresora multifuncional', 22, 5, 4800.00, 14, NULL, 'bueno', 'disponible'),
    ('Proyector BenQ MH530', 'BC-UTC-000063', 'NFC-00063', 'QR-BIEN-000063', 'SN-BNQ-MH530-02', 'F-2023-0103', 'Proyector para sala de lectura', 12, 6, 14500.00, 14, NULL, 'bueno', 'disponible'),
    ('Escáner', 'BC-UTC-000064', 'NFC-00064', 'QR-BIEN-000064', 'SN-SCN-001', 'F-2023-0104', 'Escáner de documentos', NULL, 35, 3500.00, 14, NULL, 'bueno', 'disponible'),
    ('Regulador de voltaje', 'BC-UTC-000065', 'NFC-00065', 'QR-BIEN-000065', 'SN-REG-001', 'F-2023-0105', 'Regulador de voltaje 1200VA', NULL, 32, 850.00, 14, NULL, 'bueno', 'disponible');

-- Aula 15: Sala de Lectura (3 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Mesa de centro', 'BC-UTC-000066', 'NFC-00066', 'QR-BIEN-000066', 'S/N', 'F-2022-0130', 'Mesa de centro para sala', NULL, 16, 3500.00, 15, NULL, 'bueno', 'disponible'),
    ('Silla de visita', 'BC-UTC-000067', 'NFC-00067', 'QR-BIEN-000067', 'S/N', 'F-2022-0131', 'Silla de visita para sala', NULL, 18, 1972.00, 15, NULL, 'bueno', 'disponible'),
    ('Ventilador de pie', 'BC-UTC-000068', 'NFC-00068', 'QR-BIEN-000068', 'SN-VEN-001', 'F-2023-0110', 'Ventilador de pie 20"', NULL, 33, 1200.00, 15, NULL, 'bueno', 'disponible');

-- Aula 16: Lab. Idiomas (3 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Laptop Apple MacBook Air M1', 'BC-UTC-000069', 'NFC-00069', 'QR-BIEN-000069', 'SN-APL-MBA-02', 'F-2023-0120', 'Laptop para lab. de idiomas', 7, 1, 24500.00, 16, NULL, 'bueno', 'disponible'),
    ('Bocina Sony', 'BC-UTC-000070', 'NFC-00070', 'QR-BIEN-000070', 'SN-SNY-BSP-01', 'F-2023-0121', 'Bocina bluetooth para clase', NULL, 20, 2500.00, 16, NULL, 'bueno', 'disponible'),
    ('Micrófono', 'BC-UTC-000071', 'NFC-00071', 'QR-BIEN-000071', 'SN-MIC-001', 'F-2023-0122', 'Micrófono inalámbrico', NULL, 21, 1800.00, 16, NULL, 'bueno', 'disponible');

-- Aula 17: Cubi. Psicología (2 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Escritorio', 'BC-UTC-000072', 'NFC-00072', 'QR-BIEN-000072', 'S/N', 'F-2022-0140', 'Escritorio para cubículo', NULL, 12, 5500.00, 17, NULL, 'bueno', 'disponible'),
    ('Silla ejecutiva', 'BC-UTC-000073', 'NFC-00073', 'QR-BIEN-000073', 'S/N', 'F-2022-0141', 'Silla ejecutiva para cubículo', NULL, 17, 4500.00, 17, NULL, 'bueno', 'disponible');

-- Aula 18: Cubi. Subdirector (2 bienes)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Laptop Dell Latitude 5520', 'BC-UTC-000074', 'NFC-00074', 'QR-BIEN-000074', 'SN-DLL-5520-02', 'F-2023-0130', 'Laptop para subdirector', 1, 1, 22500.00, 18, NULL, 'bueno', 'disponible'),
    ('Monitor LG 24"', 'BC-UTC-000075', 'NFC-00075', 'QR-BIEN-000075', 'SN-LG-24MK-02', 'F-2023-0131', 'Monitor IPS 24 pulgadas', 13, 7, 4500.00, 18, NULL, 'bueno', 'disponible');

-- Bienes adicionales sin aula específica (bodega, mantenimiento)
INSERT INTO bienes (nombre, codigo_barras, nfc, codigo_qr, no_serie, no_factura, descripcion, cve_modelo, cve_articulo, costo_unitario, cve_aula, cve_encargado, estado_fisico, estado_prestamo) VALUES
    ('Taladro Bosch GSR 120', 'BC-UTC-000076', 'NFC-00076', 'QR-BIEN-000076', 'SN-BOS-GSR-01', 'F-2023-0140', 'Taladro inalámbrico 12V', 19, 24, 2800.00, NULL, 1, 'bueno', 'disponible'),
    ('Caja de herramientas Stanley', 'BC-UTC-000077', 'NFC-00077', 'QR-BIEN-000077', 'SN-STN-FM-01', 'F-2023-0141', 'Caja de herramientas FatMax', 20, 29, 3500.00, NULL, 1, 'bueno', 'disponible'),
    ('Aire acondicionado split', 'BC-UTC-000078', 'NFC-00078', 'QR-BIEN-000078', 'SN-AC-001', 'F-2023-0150', 'A/C split 2 toneladas', NULL, 34, 18000.00, 4, 3, 'bueno', 'disponible'),
    ('Aire acondicionado split', 'BC-UTC-000079', 'NFC-00079', 'QR-BIEN-000079', 'SN-AC-002', 'F-2023-0151', 'A/C split 2 toneladas', NULL, 34, 18000.00, 5, 4, 'bueno', 'disponible'),
    ('Trituradora de papel', 'BC-UTC-000080', 'NFC-00080', 'QR-BIEN-000080', 'SN-TRZ-001', 'F-2023-0160', 'Trituradora de papel para oficina', NULL, 38, 2500.00, 1, 3, 'bueno', 'disponible');

-- ============================================================================
-- 19. PRÉSTAMOS (18 préstamos sin duplicados de bien activo)
-- ============================================================================
-- cve_persona: 1=Admin, 2=Director, 3=Geovanni, 4=María Elena, 5=Roberto, 6=Ana, 7=Luis, 8-12=Alumnos
INSERT INTO prestamos (cve_bien, cve_persona_solicita, estado_prestamo, fecha_devolucion_pactada, fecha_devolucion_real) VALUES
    -- Préstamos pendientes
    (15, 8, 'pendiente', CURRENT_TIMESTAMP + INTERVAL '7 days', NULL),    -- Laptop DSM → Alumno Sofia
    (22, 10, 'pendiente', CURRENT_TIMESTAMP + INTERVAL '3 days', NULL),   -- Laptop Cont → Alumno Laura
    (31, 9, 'pendiente', CURRENT_TIMESTAMP + INTERVAL '5 days', NULL),    -- Laptop Turismo → Alumno Carlos
    (76, 1, 'pendiente', CURRENT_TIMESTAMP + INTERVAL '2 days', NULL),    -- Taladro → Admin
    -- Préstamos aprobados (activos)
    (10, 2, 'aprobado', CURRENT_TIMESTAMP + INTERVAL '10 days', NULL),    -- Laptop Dirección → Director
    (17, 8, 'aprobado', CURRENT_TIMESTAMP + INTERVAL '5 days', NULL),     -- Laptop DSM → Alumno Sofia
    (22, 10, 'aprobado', CURRENT_TIMESTAMP + INTERVAL '8 days', NULL),    -- Laptop Cont → Alumno Laura (duplicado intencional de pendiente → se corrige abajo)
    (31, 9, 'aprobado', CURRENT_TIMESTAMP + INTERVAL '4 days', NULL),     -- Laptop Turismo → Alumno Carlos
    (42, 3, 'aprobado', CURRENT_TIMESTAMP + INTERVAL '14 days', NULL),    -- Desktop Lab → Geovanni
    (76, 1, 'aprobado', CURRENT_TIMESTAMP + INTERVAL '6 days', NULL),     -- Taladro → Admin
    -- Préstamos devueltos
    (1, 4, 'devuelto', CURRENT_TIMESTAMP - INTERVAL '10 days', CURRENT_TIMESTAMP - INTERVAL '5 days'),  -- Laptop Geovanni → María Elena
    (6, 5, 'devuelto', CURRENT_TIMESTAMP - INTERVAL '15 days', CURRENT_TIMESTAMP - INTERVAL '12 days'), -- Desktop Mtra → Roberto
    (14, 6, 'devuelto', CURRENT_TIMESTAMP - INTERVAL '20 days', CURRENT_TIMESTAMP - INTERVAL '18 days'),-- Proyector Dir → Ana
    (25, 7, 'devuelto', CURRENT_TIMESTAMP - INTERVAL '8 days', CURRENT_TIMESTAMP - INTERVAL '3 days'),  -- Impresora Cont → Luis
    (34, 3, 'devuelto', CURRENT_TIMESTAMP - INTERVAL '25 days', CURRENT_TIMESTAMP - INTERVAL '20 days'),-- Proyector Tur → Geovanni
    (45, 4, 'devuelto', CURRENT_TIMESTAMP - INTERVAL '30 days', CURRENT_TIMESTAMP - INTERVAL '25 days'),-- UPS Lab → María Elena
    (50, 5, 'devuelto', CURRENT_TIMESTAMP - INTERVAL '12 days', CURRENT_TIMESTAMP - INTERVAL '8 days'), -- MacBook Lab → Roberto
    (60, 6, 'devuelto', CURRENT_TIMESTAMP - INTERVAL '5 days', CURRENT_TIMESTAMP - INTERVAL '2 days');  -- Desktop Bib → Ana

-- Corregir: el préstamo 22 está duplicado (pendiente y aprobado). Cambiamos el pendiente a rechazado.
UPDATE prestamos SET estado_prestamo = 'rechazado' WHERE cve_prestamo = 2;

-- ============================================================================
-- 20. MOTIVO_DE_MOVIMIENTO (5 motivos)
-- ============================================================================
INSERT INTO motivo_de_movimiento (nombre_motivo, requiere_aprobacion) VALUES
    ('Cambio de estado físico', FALSE),
    ('Préstamo de bien', TRUE),
    ('Mantenimiento preventivo', FALSE),
    ('Baja de bien', TRUE),
    ('Traslado de ubicación', TRUE);

-- ============================================================================
-- 21. BITÁCORA DE MOVIMIENTOS (8 registros)
-- ============================================================================
INSERT INTO bitacora_movimientos (cve_bien, cve_motivo, cve_persona_accion, fecha_movimiento, observaciones) VALUES
    (1, 1, 1, CURRENT_TIMESTAMP - INTERVAL '30 days', 'Registro inicial del bien en sistema'),
    (10, 2, 1, CURRENT_TIMESTAMP - INTERVAL '25 days', 'Préstamo de laptop a Director'),
    (15, 2, 3, CURRENT_TIMESTAMP - INTERVAL '20 days', 'Préstamo de laptop a alumno DSM'),
    (3, 3, 1, CURRENT_TIMESTAMP - INTERVAL '15 days', 'Mantenimiento preventivo de archivero'),
    (26, 1, 5, CURRENT_TIMESTAMP - INTERVAL '10 days', 'Cambio de estado a bueno después de reparación'),
    (76, 2, 1, CURRENT_TIMESTAMP - INTERVAL '5 days', 'Préstamo de taladro para mantenimiento de aulas'),
    (50, 4, 1, CURRENT_TIMESTAMP - INTERVAL '3 days', 'Baja temporal de MacBook por reparación'),
    (1, 5, 3, CURRENT_TIMESTAMP - INTERVAL '1 day', 'Traslado de laptop de cubículo a laboratorio');

-- ============================================================================
-- 22. FOLIO SIIA (5 folios)
-- ============================================================================
INSERT INTO folio_siia (clave_siia, cve_persona, fecha, hora) VALUES
    ('SIIA-2023-001', 3, CURRENT_DATE - INTERVAL '30 days', CURRENT_TIME - INTERVAL '30 days'),
    ('SIIA-2023-002', 4, CURRENT_DATE - INTERVAL '25 days', CURRENT_TIME - INTERVAL '25 days'),
    ('SIIA-2023-003', 5, CURRENT_DATE - INTERVAL '20 days', CURRENT_TIME - INTERVAL '20 days'),
    ('SIIA-2024-001', 1, CURRENT_DATE - INTERVAL '10 days', CURRENT_TIME - INTERVAL '10 days'),
    ('SIIA-2024-002', 2, CURRENT_DATE - INTERVAL '5 days', CURRENT_TIME - INTERVAL '5 days');

-- ============================================================================
-- 23. NÚMERO DE RESGUARDO (5 resguardos reales)
-- ============================================================================
INSERT INTO numero_resguardo (no_resguardo, cve_persona, fecha, hora) VALUES
    ('73113000-11', 3, CURRENT_DATE - INTERVAL '30 days', CURRENT_TIME - INTERVAL '30 days'),
    ('73112100-05', 4, CURRENT_DATE - INTERVAL '25 days', CURRENT_TIME - INTERVAL '25 days'),
    ('73112200-03', 5, CURRENT_DATE - INTERVAL '20 days', CURRENT_TIME - INTERVAL '20 days'),
    ('73100000-01', 2, CURRENT_DATE - INTERVAL '10 days', CURRENT_TIME - INTERVAL '10 days'),
    ('73180000-01', 1, CURRENT_DATE - INTERVAL '5 days', CURRENT_TIME - INTERVAL '5 days');

-- ============================================================================
-- 24. AUDITORÍAS (2 auditorías de ejemplo)
-- ============================================================================
INSERT INTO auditorias (cve_auditor, fecha_auditoria, observaciones_generales) VALUES
    (1, CURRENT_DATE - INTERVAL '15 days', 'Auditoría semestral de bienes en Edificio II'),
    (3, CURRENT_DATE - INTERVAL '5 days', 'Verificación de bienes en laboratorio de cómputo');

-- ============================================================================
-- 25. AUDITORIA_DETALLE (10 detalles)
-- ============================================================================
INSERT INTO auditoria_detalle (cve_auditoria, cve_bien, encontrado, estado_encontrado) VALUES
    (1, 15, TRUE, 'Bueno'),
    (1, 16, TRUE, 'Bueno'),
    (1, 17, TRUE, 'Bueno'),
    (1, 18, TRUE, 'Regular'),
    (1, 19, TRUE, 'Bueno'),
    (1, 20, TRUE, 'Bueno'),
    (2, 40, TRUE, 'Bueno'),
    (2, 41, TRUE, 'Bueno'),
    (2, 42, FALSE, 'No encontrado en ubicación asignada'),
    (2, 43, TRUE, 'Bueno');

-- ============================================================================
-- 26. ACTUALIZAR estado_prestamo de bienes con préstamos aprobados
-- ============================================================================
UPDATE bienes SET estado_prestamo = 'prestado'
WHERE cve_bien IN (SELECT cve_bien FROM prestamos WHERE estado_prestamo = 'aprobado');

-- ============================================================================
-- 27. RESET DE TODAS LAS SECUENCIAS
-- ============================================================================
SELECT setval('roles_cve_rol_seq', (SELECT MAX(cve_rol) FROM roles));
SELECT setval('area_cve_area_seq', (SELECT MAX(cve_area) FROM area));
SELECT setval('tipo_profesor_cve_tipo_profesor_seq', (SELECT MAX(cve_tipo_profesor) FROM tipo_profesor));
SELECT setval('persona_cve_persona_seq', (SELECT MAX(cve_persona) FROM persona));
SELECT setval('profesor_cve_profesor_seq', (SELECT MAX(cve_profesor) FROM profesor));
SELECT setval('alumnos_cve_alumno_seq', (SELECT MAX(cve_alumno) FROM alumnos));
SELECT setval('usuarios_cve_usuario_seq', (SELECT MAX(cve_usuario) FROM usuarios));
SELECT setval('adscripcion_cve_adscripcion_seq', (SELECT MAX(cve_adscripcion) FROM adscripcion));
SELECT setval('adscripcion_persona_cve_adscripcion_persona_seq', (SELECT MAX(cve_adscripcion_persona) FROM adscripcion_persona));
SELECT setval('edificio_cve_edificio_seq', (SELECT MAX(cve_edificio) FROM edificio));
SELECT setval('tipo_aula_cve_tipo_aula_seq', (SELECT MAX(cve_tipo_aula) FROM tipo_aula));
SELECT setval('aula_cve_aula_seq', (SELECT MAX(cve_aula) FROM aula));
SELECT setval('tipos_bien_cve_tipo_seq', (SELECT MAX(cve_tipo) FROM tipos_bien));
SELECT setval('marcas_cve_marca_seq', (SELECT MAX(cve_marca) FROM marcas));
SELECT setval('modelos_cve_modelo_seq', (SELECT MAX(cve_modelo) FROM modelos));
SELECT setval('familias_articulos_cve_familia_seq', (SELECT MAX(cve_familia) FROM familias_articulos));
SELECT setval('articulos_cve_articulo_seq', (SELECT MAX(cve_articulo) FROM articulos));
SELECT setval('bienes_cve_bien_seq', (SELECT MAX(cve_bien) FROM bienes));
SELECT setval('prestamos_cve_prestamo_seq', (SELECT MAX(cve_prestamo) FROM prestamos));
SELECT setval('motivo_de_movimiento_cve_motivo_seq', (SELECT MAX(cve_motivo) FROM motivo_de_movimiento));
SELECT setval('bitacora_movimientos_cve_movimiento_seq', (SELECT MAX(cve_movimiento) FROM bitacora_movimientos));
SELECT setval('folio_siia_cve_folio_seq', (SELECT MAX(cve_folio) FROM folio_siia));
SELECT setval('numero_resguardo_cve_resguardo_pk_seq', (SELECT MAX(cve_resguardo_pk) FROM numero_resguardo));
SELECT setval('auditorias_cve_auditoria_seq', (SELECT MAX(cve_auditoria) FROM auditorias));
SELECT setval('auditoria_detalle_cve_auditoria_det_seq', (SELECT MAX(cve_auditoria_det) FROM auditoria_detalle));

-- ============================================================================
-- 28. VERIFICACIÓN FINAL
-- ============================================================================
SELECT 'roles' AS tabla, COUNT(*) FROM roles
UNION ALL SELECT 'area', COUNT(*) FROM area
UNION ALL SELECT 'tipo_profesor', COUNT(*) FROM tipo_profesor
UNION ALL SELECT 'persona', COUNT(*) FROM persona
UNION ALL SELECT 'profesor', COUNT(*) FROM profesor
UNION ALL SELECT 'alumnos', COUNT(*) FROM alumnos
UNION ALL SELECT 'usuarios', COUNT(*) FROM usuarios
UNION ALL SELECT 'adscripcion', COUNT(*) FROM adscripcion
UNION ALL SELECT 'adscripcion_persona', COUNT(*) FROM adscripcion_persona
UNION ALL SELECT 'edificios', COUNT(*) FROM edificio
UNION ALL SELECT 'tipo_aula', COUNT(*) FROM tipo_aula
UNION ALL SELECT 'aulas', COUNT(*) FROM aula
UNION ALL SELECT 'tipos_bien', COUNT(*) FROM tipos_bien
UNION ALL SELECT 'marcas', COUNT(*) FROM marcas
UNION ALL SELECT 'modelos', COUNT(*) FROM modelos
UNION ALL SELECT 'familias_articulos', COUNT(*) FROM familias_articulos
UNION ALL SELECT 'articulos', COUNT(*) FROM articulos
UNION ALL SELECT 'bienes', COUNT(*) FROM bienes
UNION ALL SELECT 'prestamos', COUNT(*) FROM prestamos
UNION ALL SELECT 'motivo_movimiento', COUNT(*) FROM motivo_de_movimiento
UNION ALL SELECT 'bitacora', COUNT(*) FROM bitacora_movimientos
UNION ALL SELECT 'folio_siia', COUNT(*) FROM folio_siia
UNION ALL SELECT 'num_resguardo', COUNT(*) FROM numero_resguardo
UNION ALL SELECT 'auditorias', COUNT(*) FROM auditorias
UNION ALL SELECT 'auditoria_detalle', COUNT(*) FROM auditoria_detalle;
