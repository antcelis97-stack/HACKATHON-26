-- Actualizar movimientos para usar bienes del aula DSM-101 (14, 23, 24, 25)
-- Primero eliminar los anteriores
DELETE FROM bitacora_movimientos WHERE cve_persona_accion = 11;

-- Insertar movimientos con bienes correctos del aula
INSERT INTO bitacora_movimientos (cve_bien, cve_motivo, cve_persona_accion, fecha_movimiento, observaciones)
VALUES 
(14, 4, 11, CURRENT_DATE - INTERVAL '3 days', 'Préstamo aprobado - Mesa'),
(23, 4, 11, CURRENT_DATE - INTERVAL '5 days', 'Préstamo Silla'),
(24, 3, 11, CURRENT_DATE - INTERVAL '7 days', 'Revisión estado físico - Computadora'),
(25, 3, 11, CURRENT_DATE - INTERVAL '2 days', 'Verificación inventario - Proyector');