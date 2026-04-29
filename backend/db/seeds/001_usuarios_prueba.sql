-- =============================================================================
-- DATOS DE PRUEBA PARA SANDBOX SIEST
-- 
-- Ejecutar DESPUÉS de 001_schema_sandbox.sql
-- =============================================================================

-- -----------------------------------------------------------------------------
-- Insertar Roles
-- -----------------------------------------------------------------------------
INSERT INTO public.roles (nombre, descripcion) VALUES
    ('Administrador', 'Acceso total al sistema'),
    ('Profesor', 'Acceso a módulos de profesor'),
    ('Estudiante', 'Acceso básico a módulos de estudiante')
ON CONFLICT (nombre) DO NOTHING;

-- -----------------------------------------------------------------------------
-- Insertar Empleados de Ejemplo
-- -----------------------------------------------------------------------------
INSERT INTO public.empleados (numero_empleado, nombre_completo, email, telefono, area, puesto, activo) VALUES
    ('EMP001', 'Juan Pérez García', 'juan.perez@universidad.edu', '5512345678', 'Administración', 'Director', true),
    ('EMP002', 'María López Hernández', 'maria.lopez@universidad.edu', '5512345679', 'Informática', 'Profesora Titular', true),
    ('EMP003', 'Carlos Ramírez Soto', 'carlos.ramirez@universidad.edu', '5512345680', 'Matemáticas', 'Profesor Asociado', true),
    ('EMP004', 'Ana Martínez Cruz', 'ana.martinez@universidad.edu', '5512345681', 'Física', 'Investigadora', true),
    ('EMP005', 'Pedro Sánchez Díaz', 'pedro.sanchez@universidad.edu', '5512345682', 'Química', 'Profesor Auxiliar', true),
    ('EMP006', 'Laura Torres Vega', 'laura.torres@universidad.edu', '5512345683', 'Biología', 'Profesora Titular', true),
    ('EMP007', 'Roberto Jiménez Luna', 'roberto.jimenez@universidad.edu', '5512345684', 'Administración', 'Coordinador', true),
    ('EMP008', 'Sofia Flores Mora', 'sofia.flores@universidad.edu', '5512345685', 'Informática', 'Ayudante de Investigación', true)
ON CONFLICT (numero_empleado) DO NOTHING;

-- -----------------------------------------------------------------------------
-- Insertar Usuarios de Prueba
-- 
-- CONTRASEÑAS (usando password_hash):
-- - Admin123! -> $2y$10$...
-- - Profesor123! -> $2y$10$...
-- - Alumno123! -> $2y$10$...
-- 
-- Para verificar: password_verify('Admin123!', $hash)
-- -----------------------------------------------------------------------------
INSERT INTO public.usuarios (usuario, contrasena_hash, rol_id, nombre, email, activo) VALUES
    ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Administrador del Sistema', 'admin@universidad.edu', true),
    ('profesor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'Profesor de Ejemplo', 'profesor@universidad.edu', true),
    ('alumno', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 'Estudiante de Ejemplo', 'alumno@universidad.edu', true)
ON CONFLICT (usuario) DO NOTHING;

-- NOTA: Todas las contraseñas de prueba son: password
-- Para cambiar las contraseñas, genera un nuevo hash con:
-- echo password_hash('TuPassword123!', PASSWORD_DEFAULT);

-- -----------------------------------------------------------------------------
-- Verificar datos insertados
-- -----------------------------------------------------------------------------
SELECT 'Roles insertados:' AS mensaje, COUNT(*) AS cantidad FROM public.roles
UNION ALL
SELECT 'Empleados insertados:', COUNT(*) FROM public.empleados
UNION ALL
SELECT 'Usuarios insertados:', COUNT(*) FROM public.usuarios;
