-- Tipos enumerados para roles y estatus
CREATE TYPE rol_usuario AS ENUM ('egresado', 'empresa', 'admin');
CREATE TYPE estatus_postulacion AS ENUM ('postulado', 'en_proceso', 'contratado', 
'rechazado');

CREATE TABLE usuarios (
 id_usuario SERIAL PRIMARY KEY,
 username VARCHAR(50) UNIQUE NOT NULL,
 password_hash TEXT NOT NULL,
 rol rol_usuario NOT NULL,
 fecha_registro DATE DEFAULT CURRENT_DATE,
 hora_registro TIME DEFAULT CURRENT_TIME,
 estado BOOLEAN DEFAULT TRUE
);

CREATE TABLE usuario_contacto (
 id_contacto SERIAL PRIMARY KEY,
 id_usuario INTEGER UNIQUE REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
 direccion TEXT,
 email VARCHAR(100),
 telefono VARCHAR(20),
 fecha_registro DATE DEFAULT CURRENT_DATE,
 estado BOOLEAN DEFAULT TRUE
);

CREATE TABLE carreras (
 id_carrera SERIAL PRIMARY KEY,
 nombre_carrera VARCHAR(100) NOT NULL,
 fecha_registro DATE DEFAULT CURRENT_DATE,
 hora_registro TIME DEFAULT CURRENT_TIME,
 estado BOOLEAN DEFAULT TRUE
);

;
CREATE TABLE egresados (
 cve_alumno VARCHAR(20) PRIMARY KEY,
 id_usuario INTEGER UNIQUE REFERENCES usuarios(id_usuario),
 matricula VARCHAR(20) UNIQUE NOT NULL,
 nombre VARCHAR(100) NOT NULL,
 apellido_paterno VARCHAR(100) NOT NULL,
 apellido_materno VARCHAR(100),
 id_carrera INTEGER REFERENCES carreras(id_carrera),
 periodo_ingreso VARCHAR(20),
 url_foto_drive TEXT,
 url_cv_drive TEXT,
 completo_examenes BOOLEAN DEFAULT FALSE,
 fecha_registro DATE DEFAULT CURRENT_DATE,
 hora_registro TIME DEFAULT CURRENT_TIME,
 estado BOOLEAN DEFAULT TRUE
);

CREATE TABLE empresas (
 id_empresa SERIAL PRIMARY KEY,
 id_usuario INTEGER UNIQUE REFERENCES usuarios(id_usuario),
 id_denue VARCHAR(50), 
 razon_social VARCHAR(150) NOT NULL,
 fecha_registro DATE DEFAULT CURRENT_DATE,
 hora_registro TIME DEFAULT CURRENT_TIME,
 estado BOOLEAN DEFAULT TRUE
);

CREATE TABLE convenios (
 id_convenio SERIAL PRIMARY KEY,
 id_empresa INTEGER REFERENCES empresas(id_empresa) ON DELETE CASCADE,
 url_archivo_drive TEXT,
 fecha_inicio DATE,
 fecha_vencimiento DATE,
 estatus VARCHAR(30) DEFAULT 'pendiente', -- pendiente, en_revision, activo, rechazado, vencido
 comentarios TEXT,
 fecha_registro DATE DEFAULT CURRENT_DATE,
 hora_registro TIME DEFAULT CURRENT_TIME,
 estado BOOLEAN DEFAULT TRUE
);
CREATE TABLE vacantes (
 id_vacante SERIAL PRIMARY KEY,
 id_empresa INTEGER REFERENCES empresas(id_empresa) ON DELETE CASCADE,
 titulo_puesto VARCHAR(150) NOT NULL,
 descripcion TEXT,
 -- Perfil Idóneo (Benchmarks Cuantitativos)
 min_psicometrico NUMERIC(5,2),
 min_cognitivo NUMERIC(5,2),
 min_tecnico NUMERIC(5,2),
 min_proyectivo NUMERIC(5,2),
 fecha_registro DATE DEFAULT CURRENT_DATE,
 hora_registro TIME DEFAULT CURRENT_TIME,
 estado BOOLEAN DEFAULT TRUE
);

CREATE TABLE tipos_evaluacion (
 id_tipo SERIAL PRIMARY KEY,
 nombre_tipo VARCHAR(50) NOT NULL, -- Psicométrica, Cognitiva, etc.
 fecha_registro DATE DEFAULT CURRENT_DATE,
 hora_registro TIME DEFAULT CURRENT_TIME,
 estado BOOLEAN DEFAULT TRUE
);
CREATE TABLE resultados_evaluaciones (
 id_resultado SERIAL PRIMARY KEY,
 cve_alumno VARCHAR(20) REFERENCES egresados(cve_alumno) ON DELETE CASCADE,
 id_tipo INTEGER REFERENCES tipos_evaluacion(id_tipo),
 puntaje NUMERIC(5,2) NOT NULL,
 fecha_registro DATE DEFAULT CURRENT_DATE,
 hora_registro TIME DEFAULT CURRENT_TIME,
 estado BOOLEAN DEFAULT TRUE,
 -- Asegura que solo se realice una vez por tipo de examen
 UNIQUE(cve_alumno, id_tipo) 
);
CREATE TABLE postulaciones (
 id_postulacion SERIAL PRIMARY KEY,
 id_vacante INTEGER REFERENCES vacantes(id_vacante) ON DELETE CASCADE,
 cve_alumno VARCHAR(20) REFERENCES egresados(cve_alumno) ON DELETE CASCADE,
 estatus estatus_postulacion DEFAULT 'postulado',
 fecha_registro DATE DEFAULT CURRENT_DATE,
 hora_registro TIME DEFAULT CURRENT_TIME,
 estado BOOLEAN DEFAULT TRUE
);

-- =============================================================================
-- DATOS DE PRUEBA (CONTRASEÑA: password123)
-- =============================================================================

-- 1. Usuarios (Hash para 'password123')
INSERT INTO usuarios (username, password_hash, rol) VALUES 
('egresado_test', '$2a$12$R9h/cIPz0gi.URQHeNVwCOVuxTkFz/1VYmSGYzYrfwj6JJvVPQU7a', 'egresado'),
('empresa_test', '$2a$12$R9h/cIPz0gi.URQHeNVwCOVuxTkFz/1VYmSGYzYrfwj6JJvVPQU7a', 'empresa');

-- 2. Información de Contacto
INSERT INTO usuario_contacto (id_usuario, direccion, email, telefono) VALUES 
(1, 'Calle de las Flores #123, Col. Centro', 'juan.egresado@correo.com', '555-0101'),
(2, 'Parque Industrial Norte, Bodega 4', 'rh@empresa-tech.com', '555-9090');

-- 3. Carreras
INSERT INTO carreras (nombre_carrera) VALUES 
('Ingeniería en Software'),
('Licenciatura en Administración');

-- 4. Egresados (Matrícula, Usuario, Carrera, etc.)
INSERT INTO egresados (cve_alumno, matricula, id_usuario, id_carrera, nombre, apellido_paterno, apellido_materno) VALUES 
('20240001', 'MAT-20240001', 1, 1, 'Juan', 'Pérez', 'Gómez');

-- 5. Empresas (Usuario, DENUE, Razón Social)
INSERT INTO empresas (id_usuario, id_denue, razon_social) VALUES 
(2, '1234567890', 'Tech Innovations S.A. de C.V.');

-- 6. Tipos de Evaluación
INSERT INTO tipos_evaluacion (nombre_tipo) VALUES 
('Psicométrica'),
('Cognitiva'),
('Técnica');


