-- =============================================================================
-- SEED: Profesores y asignación de aulas
-- =============================================================================

-- 1. Fix: Conectar artículos sin familia (laptops) a "Cómputo portátil"
UPDATE articulos SET cve_familia = 31 WHERE cve_familia IS NULL;

-- 2. Insertar profesores (personas 10, 11, 12)
INSERT INTO profesor (cve_persona, cve_tipo_profesor, activo)
VALUES
    (10, 3, true),   -- César Geovanni Machuca - Tiempo Completo
    (11, 4, true),   -- María Elena López - Asignatura
    (12, 5, true);   -- Roberto Ramírez Soto - Medio Tiempo

-- 3. Asignar aulas a profesores
UPDATE aula SET cve_profesor = 8 WHERE cve_aula = 47;  -- Lab. Cómputo I → César Geovanni
UPDATE aula SET cve_profesor = 9 WHERE cve_aula = 45;  -- DSM-101 → María Elena
UPDATE aula SET cve_profesor = 10 WHERE cve_aula = 46; -- Contaduría-101 → Roberto

-- 4. Crear usuarios para los profesores nuevos
-- (Los hashes se generan con password_hash() de PHP - actualizar con valores reales)
INSERT INTO usuarios (cve_persona, cve_rol, usuario, contrasena_hash, email, activo)
VALUES
    (11, 6, 'mariaelena', '<HASH_BCRYPT_MARIA>', 'maria.lopez@utc.edu.mx', true),
    (12, 6, 'roberto', '<HASH_BCRYPT_ROBERTO>', 'roberto.ramirez@utc.edu.mx', true);

-- 5. Actualizar contraseña del usuario profesor existente
UPDATE usuarios SET contrasena_hash = '<HASH_BCRYPT_PROFESOR>' WHERE usuario = 'profesor';

-- =============================================================================
-- Credenciales de prueba:
-- profesor   / Profesor123!   (César Geovanni - Lab. Cómputo I)
-- mariaelena / Maria123!      (María Elena - DSM-101)
-- roberto    / Roberto123!    (Roberto - Contaduría-101)
-- =============================================================================
