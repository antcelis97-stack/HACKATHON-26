-- Datos de ejemplo para el reporte "Egresados por carrera" (opcional, idempotente).
-- Ejecutar después de bd.sql en la base definida en .env (PG_NAME, etc.).

INSERT INTO carreras (nombre_carrera)
SELECT 'Ingeniería en Software'
WHERE NOT EXISTS (SELECT 1 FROM carreras WHERE nombre_carrera = 'Ingeniería en Software');

INSERT INTO carreras (nombre_carrera)
SELECT 'Licenciatura en Administración'
WHERE NOT EXISTS (SELECT 1 FROM carreras WHERE nombre_carrera = 'Licenciatura en Administración');

INSERT INTO egresados (cve_alumno, matricula, nombre, apellido_paterno, apellido_materno, id_carrera, completo_examenes, estado)
SELECT 'EGR-DEMO-001', 'MAT-DEMO-001', 'María', 'López', 'Ruiz', c.id_carrera, TRUE, TRUE
FROM carreras c WHERE c.nombre_carrera = 'Ingeniería en Software' LIMIT 1
ON CONFLICT (cve_alumno) DO NOTHING;

INSERT INTO egresados (cve_alumno, matricula, nombre, apellido_paterno, apellido_materno, id_carrera, completo_examenes, estado)
SELECT 'EGR-DEMO-002', 'MAT-DEMO-002', 'Carlos', 'Núñez', NULL, c.id_carrera, FALSE, TRUE
FROM carreras c WHERE c.nombre_carrera = 'Ingeniería en Software' LIMIT 1
ON CONFLICT (cve_alumno) DO NOTHING;

INSERT INTO egresados (cve_alumno, matricula, nombre, apellido_paterno, apellido_materno, id_carrera, completo_examenes, estado)
SELECT 'EGR-DEMO-003', 'MAT-DEMO-003', 'Ana', 'García', 'Flores', c.id_carrera, TRUE, TRUE
FROM carreras c WHERE c.nombre_carrera = 'Licenciatura en Administración' LIMIT 1
ON CONFLICT (cve_alumno) DO NOTHING;
