-- =============================================================================
-- SCHEMA MÍNIMO PARA SANDBOX SIEST
-- 
-- Este script crea las tablas mínimas necesarias para el sandbox.
-- Los alumnos pueden expandirlas según sus necesidades.
--
-- IMPORTANTE: Usa el schema 'public' de PostgreSQL
-- =============================================================================

-- -----------------------------------------------------------------------------
-- Tabla de Roles
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.roles (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------------------------------
-- Tabla de Empleados (ejemplo de módulo)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.empleados (
    id SERIAL PRIMARY KEY,
    numero_empleado VARCHAR(20) UNIQUE NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    email VARCHAR(255),
    telefono VARCHAR(20),
    area VARCHAR(100),
    puesto VARCHAR(100),
    activo BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------------------------------
-- Tabla de Usuarios (simplificada para auth)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.usuarios (
    id SERIAL PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    contrasena_hash VARCHAR(255) NOT NULL,
    rol_id INTEGER REFERENCES public.roles(id),
    nombre VARCHAR(100),
    email VARCHAR(255),
    activo BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------------------------------
-- Tabla de Logs del Sistema
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.logs (
    id SERIAL PRIMARY KEY,
    nivel VARCHAR(10) DEFAULT 'INFO',
    mensaje TEXT NOT NULL,
    contexto JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------------------------------
-- Tabla de Auditoría (para CRUD)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.audit_log (
    id SERIAL PRIMARY KEY,
    user_id INTEGER,
    accion VARCHAR(50) NOT NULL,
    tabla VARCHAR(100),
    registro_id INTEGER,
    datos_anteriores JSONB,
    datos_nuevos JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------------------------------
-- Tabla de Tokens de Refresco (para JWT)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.refresh_tokens (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER REFERENCES public.usuarios(id),
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------------------------------
-- Índices para mejorar rendimiento
-- -----------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_empleados_numero ON public.empleados(numero_empleado);
CREATE INDEX IF NOT EXISTS idx_empleados_area ON public.empleados(area);
CREATE INDEX IF NOT EXISTS idx_empleados_activo ON public.empleados(activo);
CREATE INDEX IF NOT EXISTS idx_logs_nivel ON public.logs(nivel);
CREATE INDEX IF NOT EXISTS idx_logs_fecha ON public.logs(created_at);
CREATE INDEX IF NOT EXISTS idx_usuarios_usuario ON public.usuarios(usuario);
CREATE INDEX IF NOT EXISTS idx_audit_tabla ON public.audit_log(tabla);
CREATE INDEX IF NOT EXISTS idx_audit_fecha ON public.audit_log(created_at);
CREATE INDEX IF NOT EXISTS idx_refresh_tokens_usuario ON public.refresh_tokens(usuario_id);
CREATE INDEX IF NOT EXISTS idx_refresh_tokens_expira ON public.refresh_tokens(expires_at);

-- -----------------------------------------------------------------------------
-- Comentarios para documentación
-- -----------------------------------------------------------------------------
COMMENT ON TABLE public.roles IS 'Roles del sistema (Administrador, Profesor, Estudiante)';
COMMENT ON TABLE public.empleados IS 'Catálogo de empleados - Ejemplo de módulo CRUD';
COMMENT ON TABLE public.usuarios IS 'Usuarios del sistema para autenticación';
COMMENT ON TABLE public.logs IS 'Logs del sistema para debugging y monitoreo';
COMMENT ON TABLE public.audit_log IS 'Auditoría de cambios en tablas críticas';
COMMENT ON TABLE public.refresh_tokens IS 'Tokens de refresco para autenticación JWT';
