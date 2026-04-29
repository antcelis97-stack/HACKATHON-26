-- Prestamos para María Elena (cve_persona = 11) usando bienes existentes
INSERT INTO prestamos (cve_bien, cve_persona_solicita, fecha_solicitud, fecha_devolucion_pactada, estado_prestamo, observaciones)
VALUES 
(14, 11, CURRENT_DATE - INTERVAL '3 days', CURRENT_DATE + INTERVAL '4 days', 'aprobado', 'Préstamo Mesa laboratorio'),
(15, 11, CURRENT_DATE - INTERVAL '5 days', CURRENT_DATE + INTERVAL '2 days', 'aprobado', 'Préstamo Archivero'),
(16, 11, CURRENT_DATE - INTERVAL '7 days', CURRENT_DATE, 'aprobado', 'Préstamo Escritorio'),
(19, 11, CURRENT_DATE - INTERVAL '2 days', CURRENT_DATE + INTERVAL '5 days', 'pendiente', 'Solicitud Laptop');

-- Movimientos para bitácora usando bienes existentes y motivos correctos
INSERT INTO bitacora_movimientos (cve_bien, cve_motivo, cve_persona_accion, fecha_movimiento, observaciones)
VALUES 
(14, 4, 11, CURRENT_DATE - INTERVAL '3 days', 'Préstamo aprobado - Mesa'),
(15, 4, 11, CURRENT_DATE - INTERVAL '5 days', 'Préstamo Archivero'),
(16, 3, 11, CURRENT_DATE - INTERVAL '7 days', 'Revisión estado físico - Escritorio'),
(19, 3, 11, CURRENT_DATE - INTERVAL '2 days', 'Verificación inventario - Laptop');