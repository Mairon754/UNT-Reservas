-- schema.sql: Esquema de la base de datos para UNTGestión

-- Table: public.usrs

-- DROP TABLE IF EXISTS public.usrs;

CREATE TABLE IF NOT EXISTS public.usrs
(
    id integer NOT NULL DEFAULT nextval('usrs_id_seq'::regclass),
    email character varying(255) COLLATE pg_catalog."default" NOT NULL,
    password character varying(255) COLLATE pg_catalog."default" NOT NULL,
    name character varying(100) COLLATE pg_catalog."default",
    role character varying(50) COLLATE pg_catalog."default" DEFAULT 'user'::character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT usrs_pkey PRIMARY KEY (id),
    CONSTRAINT usrs_email_key UNIQUE (email)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.usrs
    OWNER to postgres;

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.usrs
    OWNER to postgres;

-- Crear tabla de recursos
-- Table: public.resources

-- DROP TABLE IF EXISTS public.resources;

CREATE TABLE IF NOT EXISTS public.resources
(
    id integer NOT NULL DEFAULT nextval('resources_id_seq'::regclass),
    name character varying(100) COLLATE pg_catalog."default" NOT NULL,
    type character varying(50) COLLATE pg_catalog."default" NOT NULL,
    status character varying(50) COLLATE pg_catalog."default" NOT NULL DEFAULT 'disponible'::character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT resources_pkey PRIMARY KEY (id)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.resources
    OWNER to postgres;

-- Crear tabla de reservas
-- Table: public.reservations

-- DROP TABLE IF EXISTS public.reservations;

CREATE TABLE IF NOT EXISTS public.reservations
(
    id integer NOT NULL DEFAULT nextval('reservations_id_seq'::regclass),
    resource_id integer NOT NULL,
    responsible_person character varying(100) COLLATE pg_catalog."default" NOT NULL,
    reservation_date date NOT NULL,
    reservation_time time without time zone NOT NULL,
    observations text COLLATE pg_catalog."default",
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    end_date date,
    end_time time without time zone,
    user_id integer,
    created_by integer NOT NULL,
    CONSTRAINT reservations_pkey PRIMARY KEY (id),
    CONSTRAINT reservations_resource_id_fkey FOREIGN KEY (resource_id)
        REFERENCES public.resources (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.reservations
    OWNER to postgres;

-- Crear tabla de mantenimiento
-- Table: public.maintenance_requests

-- DROP TABLE IF EXISTS public.maintenance_requests;

CREATE TABLE IF NOT EXISTS public.maintenance_requests
(
    id integer NOT NULL DEFAULT nextval('maintenance_requests_id_seq'::regclass),
    resource_id integer NOT NULL,
    description text COLLATE pg_catalog."default" NOT NULL,
    status character varying(50) COLLATE pg_catalog."default" NOT NULL DEFAULT 'reportado'::character varying,
    priority character varying(50) COLLATE pg_catalog."default" NOT NULL DEFAULT 'media'::character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT maintenance_requests_pkey PRIMARY KEY (id),
    CONSTRAINT maintenance_requests_resource_id_fkey FOREIGN KEY (resource_id)
        REFERENCES public.resources (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.maintenance_requests
    OWNER to postgres;

-- Crear tabla de configuración del sistema
-- Table: public.system_settings

-- DROP TABLE IF EXISTS public.system_settings;

CREATE TABLE IF NOT EXISTS public.system_settings
(
    id integer NOT NULL DEFAULT nextval('system_settings_id_seq'::regclass),
    name character varying(255) COLLATE pg_catalog."default" NOT NULL,
    email character varying(255) COLLATE pg_catalog."default" NOT NULL,
    CONSTRAINT system_settings_pkey PRIMARY KEY (id)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.system_settings
    OWNER to postgres;
-- Insertar un valor por defecto
INSERT INTO system_settings (name, email) VALUES ('Sistema de Gestión de Recursos', 'admin@untgestion.com') 
ON CONFLICT (id) DO NOTHING;
-------------user_roles-------------------------

CREATE TABLE IF NOT EXISTS public.user_roles
(
    id integer NOT NULL DEFAULT nextval('user_roles_id_seq'::regclass),
    user_id integer NOT NULL,
    role_id integer NOT NULL,
    assigned_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT user_roles_pkey PRIMARY KEY (id),
    CONSTRAINT user_roles_user_id_role_id_key UNIQUE (user_id, role_id),
    CONSTRAINT user_roles_role_id_fkey FOREIGN KEY (role_id)
        REFERENCES public.roles (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT user_roles_user_id_fkey FOREIGN KEY (user_id)
        REFERENCES public.usrs (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE
)

TABLESPACE pg_default;
------------------------roles-----------
ALTER TABLE IF EXISTS public.user_roles
    OWNER to postgres;

-- Table: public.roles

-- DROP TABLE IF EXISTS public.roles;

-- Table: public.user_roles

-- DROP TABLE IF EXISTS public.user_roles;

CREATE TABLE IF NOT EXISTS public.user_roles
(
    id integer NOT NULL DEFAULT nextval('user_roles_id_seq'::regclass),
    user_id integer NOT NULL,
    role_id integer NOT NULL,
    assigned_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT user_roles_pkey PRIMARY KEY (id),
    CONSTRAINT user_roles_user_id_role_id_key UNIQUE (user_id, role_id),
    CONSTRAINT user_roles_role_id_fkey FOREIGN KEY (role_id)
        REFERENCES public.roles (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT user_roles_user_id_fkey FOREIGN KEY (user_id)
        REFERENCES public.usrs (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.user_roles
    OWNER to postgres;