--
-- PostgreSQL database dump
--

\restrict xE8cKPcH5byDjWdO38HTmrsiQop6tttuUbKmjtkbXxSsLjvxLblS0qQ7P2cTLZo

-- Dumped from database version 17.6
-- Dumped by pg_dump version 17.6

-- Started on 2025-11-26 00:28:56

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 242 (class 1255 OID 24577)
-- Name: actualizar_estado_membresias_vencidas(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.actualizar_estado_membresias_vencidas() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Actualiza el estado a 'inactivo' para cualquier registro
    -- en miembros_membresias cuya fecha_fin sea estrictamente menor a la fecha actual
    -- y cuyo estado actual no sea ya 'inactivo' (o 'cancelada', si ese fuera un estado de cierre)
    UPDATE miembros_membresias
    SET estado = 'inactivo'
    WHERE fecha_fin < CURRENT_DATE
      AND estado != 'inactivo'; -- Excluye estados que ya son de cierre o transición
END;
$$;


ALTER FUNCTION public.actualizar_estado_membresias_vencidas() OWNER TO postgres;

--
-- TOC entry 243 (class 1255 OID 49154)
-- Name: actualizar_estado_vencido(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.actualizar_estado_vencido() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF NEW.fecha_fin < CURRENT_DATE THEN
        NEW.estado = 'inactivo';
    END IF;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.actualizar_estado_vencido() OWNER TO postgres;

--
-- TOC entry 244 (class 1255 OID 57359)
-- Name: sp_registrar_asistencia(integer, date, time without time zone); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.sp_registrar_asistencia(IN p_id_miembro integer, IN p_fecha date, IN p_hora time without time zone)
    LANGUAGE plpgsql
    AS $$
BEGIN

    INSERT INTO asistencias (id_miembro, fecha, hora) 
    VALUES (p_id_miembro, p_fecha, p_hora);
END;
$$;


ALTER PROCEDURE public.sp_registrar_asistencia(IN p_id_miembro integer, IN p_fecha date, IN p_hora time without time zone) OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 228 (class 1259 OID 16477)
-- Name: asistencias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.asistencias (
    id_asistencia integer NOT NULL,
    id_miembro integer NOT NULL,
    fecha date DEFAULT CURRENT_DATE NOT NULL,
    hora time without time zone DEFAULT CURRENT_TIME NOT NULL
);


ALTER TABLE public.asistencias OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 16476)
-- Name: asistencias_id_asistencia_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.asistencias_id_asistencia_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.asistencias_id_asistencia_seq OWNER TO postgres;

--
-- TOC entry 5029 (class 0 OID 0)
-- Dependencies: 227
-- Name: asistencias_id_asistencia_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.asistencias_id_asistencia_seq OWNED BY public.asistencias.id_asistencia;


--
-- TOC entry 232 (class 1259 OID 16501)
-- Name: clases; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.clases (
    id_clase integer NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion text,
    duracion_minutos integer NOT NULL,
    capacidad_maxima integer NOT NULL
);


ALTER TABLE public.clases OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 16500)
-- Name: clases_id_clase_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.clases_id_clase_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.clases_id_clase_seq OWNER TO postgres;

--
-- TOC entry 5030 (class 0 OID 0)
-- Dependencies: 231
-- Name: clases_id_clase_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.clases_id_clase_seq OWNED BY public.clases.id_clase;


--
-- TOC entry 234 (class 1259 OID 16510)
-- Name: horarios_clases; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.horarios_clases (
    id_horario_clase integer NOT NULL,
    id_clase integer NOT NULL,
    id_instructor integer NOT NULL,
    dia_semana character varying(15) NOT NULL,
    hora_inicio time without time zone NOT NULL,
    salon character varying(50) DEFAULT 'Salon A'::character varying NOT NULL
);


ALTER TABLE public.horarios_clases OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 16509)
-- Name: horarios_clases_id_horario_clase_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.horarios_clases_id_horario_clase_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.horarios_clases_id_horario_clase_seq OWNER TO postgres;

--
-- TOC entry 5031 (class 0 OID 0)
-- Dependencies: 233
-- Name: horarios_clases_id_horario_clase_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.horarios_clases_id_horario_clase_seq OWNED BY public.horarios_clases.id_horario_clase;


--
-- TOC entry 236 (class 1259 OID 16527)
-- Name: inscripciones_clases; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.inscripciones_clases (
    id_inscripcion integer NOT NULL,
    id_miembro integer NOT NULL,
    id_horario_clase integer NOT NULL,
    fecha_inscripcion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    estado character varying(20) DEFAULT 'confirmada'::character varying NOT NULL,
    es_lista_espera boolean DEFAULT false
);


ALTER TABLE public.inscripciones_clases OWNER TO postgres;

--
-- TOC entry 235 (class 1259 OID 16526)
-- Name: inscripciones_clases_id_inscripcion_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.inscripciones_clases_id_inscripcion_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.inscripciones_clases_id_inscripcion_seq OWNER TO postgres;

--
-- TOC entry 5032 (class 0 OID 0)
-- Dependencies: 235
-- Name: inscripciones_clases_id_inscripcion_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.inscripciones_clases_id_inscripcion_seq OWNED BY public.inscripciones_clases.id_inscripcion;


--
-- TOC entry 230 (class 1259 OID 16491)
-- Name: instructores; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.instructores (
    id_instructor integer NOT NULL,
    nombre character varying(100) NOT NULL,
    apellido character varying(100) NOT NULL,
    telefono character varying(20),
    correo character varying(100),
    especialidad character varying(50),
    fecha_contratacion date DEFAULT CURRENT_DATE NOT NULL
);


ALTER TABLE public.instructores OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 16490)
-- Name: instructores_id_instructor_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.instructores_id_instructor_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.instructores_id_instructor_seq OWNER TO postgres;

--
-- TOC entry 5033 (class 0 OID 0)
-- Dependencies: 229
-- Name: instructores_id_instructor_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.instructores_id_instructor_seq OWNED BY public.instructores.id_instructor;


--
-- TOC entry 240 (class 1259 OID 16558)
-- Name: mantenimiento_maquinas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mantenimiento_maquinas (
    id_mantenimiento integer NOT NULL,
    id_maquina integer NOT NULL,
    id_usuario integer NOT NULL,
    fecha_inicio timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    fecha_fin timestamp without time zone,
    descripcion text,
    tipo character varying(50) NOT NULL
);


ALTER TABLE public.mantenimiento_maquinas OWNER TO postgres;

--
-- TOC entry 239 (class 1259 OID 16557)
-- Name: mantenimiento_maquinas_id_mantenimiento_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mantenimiento_maquinas_id_mantenimiento_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mantenimiento_maquinas_id_mantenimiento_seq OWNER TO postgres;

--
-- TOC entry 5034 (class 0 OID 0)
-- Dependencies: 239
-- Name: mantenimiento_maquinas_id_mantenimiento_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mantenimiento_maquinas_id_mantenimiento_seq OWNED BY public.mantenimiento_maquinas.id_mantenimiento;


--
-- TOC entry 238 (class 1259 OID 16548)
-- Name: maquinas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.maquinas (
    id_maquina integer NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion text,
    fecha_adquisicion date,
    estado character varying(20) DEFAULT 'disponible'::character varying NOT NULL
);


ALTER TABLE public.maquinas OWNER TO postgres;

--
-- TOC entry 237 (class 1259 OID 16547)
-- Name: maquinas_id_maquina_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.maquinas_id_maquina_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.maquinas_id_maquina_seq OWNER TO postgres;

--
-- TOC entry 5035 (class 0 OID 0)
-- Dependencies: 237
-- Name: maquinas_id_maquina_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.maquinas_id_maquina_seq OWNED BY public.maquinas.id_maquina;


--
-- TOC entry 222 (class 1259 OID 16428)
-- Name: membresias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.membresias (
    id_membresia integer NOT NULL,
    nombre character varying(100) NOT NULL,
    precio numeric(10,2) NOT NULL,
    duracion_dias integer NOT NULL,
    estado character varying(20) DEFAULT 'activa'::character varying NOT NULL
);


ALTER TABLE public.membresias OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 16427)
-- Name: membresias_id_membresia_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.membresias_id_membresia_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.membresias_id_membresia_seq OWNER TO postgres;

--
-- TOC entry 5036 (class 0 OID 0)
-- Dependencies: 221
-- Name: membresias_id_membresia_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.membresias_id_membresia_seq OWNED BY public.membresias.id_membresia;


--
-- TOC entry 220 (class 1259 OID 16414)
-- Name: miembros; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.miembros (
    id_miembro integer NOT NULL,
    nombre character varying(100) NOT NULL,
    apellido character varying(100) NOT NULL,
    telefono character varying(20),
    correo character varying(100),
    fecha_registro date DEFAULT CURRENT_DATE NOT NULL,
    codigo_qr character varying(255)
);


ALTER TABLE public.miembros OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 16413)
-- Name: miembros_id_miembro_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.miembros_id_miembro_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.miembros_id_miembro_seq OWNER TO postgres;

--
-- TOC entry 5037 (class 0 OID 0)
-- Dependencies: 219
-- Name: miembros_id_miembro_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.miembros_id_miembro_seq OWNED BY public.miembros.id_miembro;


--
-- TOC entry 224 (class 1259 OID 16436)
-- Name: miembros_membresias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.miembros_membresias (
    id_miembro_membresia integer NOT NULL,
    id_miembro integer NOT NULL,
    id_membresia integer NOT NULL,
    fecha_inicio date DEFAULT CURRENT_DATE NOT NULL,
    fecha_fin date NOT NULL,
    estado character varying(20) NOT NULL
);


ALTER TABLE public.miembros_membresias OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 16435)
-- Name: miembros_membresias_id_miembro_membresia_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.miembros_membresias_id_miembro_membresia_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.miembros_membresias_id_miembro_membresia_seq OWNER TO postgres;

--
-- TOC entry 5038 (class 0 OID 0)
-- Dependencies: 223
-- Name: miembros_membresias_id_miembro_membresia_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.miembros_membresias_id_miembro_membresia_seq OWNED BY public.miembros_membresias.id_miembro_membresia;


--
-- TOC entry 226 (class 1259 OID 16454)
-- Name: pagos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pagos (
    id_pago integer NOT NULL,
    id_miembro integer NOT NULL,
    id_miembro_membresia integer NOT NULL,
    monto numeric(10,2) NOT NULL,
    fecha_pago timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    id_usuario integer NOT NULL
);


ALTER TABLE public.pagos OWNER TO postgres;

--
-- TOC entry 241 (class 1259 OID 32798)
-- Name: movimientos_base; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.movimientos_base AS
 SELECT p.fecha_pago AS fecha_hora,
    'Empleado'::text AS autor_accion,
    'Empleado'::text AS rol_autor,
    'Registro de Pago'::text AS tipo_movimiento,
    (((m.nombre)::text || ' '::text) || (m.apellido)::text) AS persona_o_equipo_afectado,
    ((('Monto: '::text || p.monto) || ' - Membresía: '::text) || (mem.nombre)::text) AS descripcion_accion
   FROM (((public.pagos p
     JOIN public.miembros m ON ((p.id_miembro = m.id_miembro)))
     JOIN public.miembros_membresias mm ON ((p.id_miembro_membresia = mm.id_miembro_membresia)))
     JOIN public.membresias mem ON ((mm.id_membresia = mem.id_membresia)))
UNION ALL
 SELECT mm.fecha_inicio AS fecha_hora,
    'Empleado'::text AS autor_accion,
    'Empleado'::text AS rol_autor,
    'Registro de Mantenimiento'::text AS tipo_movimiento,
    maq.nombre AS persona_o_equipo_afectado,
    ((('Tipo: '::text || (mm.tipo)::text) || ' - Descripción: '::text) || mm.descripcion) AS descripcion_accion
   FROM (public.mantenimiento_maquinas mm
     JOIN public.maquinas maq ON ((mm.id_maquina = maq.id_maquina)))
UNION ALL
 SELECT (a.fecha + a.hora) AS fecha_hora,
    (((m.nombre)::text || ' '::text) || (m.apellido)::text) AS autor_accion,
    'Miembro'::text AS rol_autor,
    'Registro de Asistencia'::text AS tipo_movimiento,
    (((m.nombre)::text || ' '::text) || (m.apellido)::text) AS persona_o_equipo_afectado,
    'Entrada registrada'::text AS descripcion_accion
   FROM (public.asistencias a
     JOIN public.miembros m ON ((a.id_miembro = m.id_miembro)))
UNION ALL
 SELECT ic.fecha_inscripcion AS fecha_hora,
    (((m.nombre)::text || ' '::text) || (m.apellido)::text) AS autor_accion,
    'Miembro'::text AS rol_autor,
    'Inscripción a Clase'::text AS tipo_movimiento,
    (((m.nombre)::text || ' '::text) || (m.apellido)::text) AS persona_o_equipo_afectado,
    ((((('Clase: '::text || (c.nombre)::text) || ' - Horario: '::text) || (hc.dia_semana)::text) || ' '::text) || hc.hora_inicio) AS descripcion_accion
   FROM (((public.inscripciones_clases ic
     JOIN public.miembros m ON ((ic.id_miembro = m.id_miembro)))
     JOIN public.horarios_clases hc ON ((ic.id_horario_clase = hc.id_horario_clase)))
     JOIN public.clases c ON ((hc.id_clase = c.id_clase)))
  ORDER BY 1 DESC;


ALTER VIEW public.movimientos_base OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 16453)
-- Name: pagos_id_pago_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pagos_id_pago_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pagos_id_pago_seq OWNER TO postgres;

--
-- TOC entry 5039 (class 0 OID 0)
-- Dependencies: 225
-- Name: pagos_id_pago_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pagos_id_pago_seq OWNED BY public.pagos.id_pago;


--
-- TOC entry 218 (class 1259 OID 16405)
-- Name: usuarios; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.usuarios (
    id_usuario integer NOT NULL,
    usuario character varying(50) NOT NULL,
    password character varying(255) NOT NULL,
    rol character varying(20) NOT NULL
);


ALTER TABLE public.usuarios OWNER TO postgres;

--
-- TOC entry 217 (class 1259 OID 16404)
-- Name: usuarios_id_usuario_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.usuarios_id_usuario_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.usuarios_id_usuario_seq OWNER TO postgres;

--
-- TOC entry 5040 (class 0 OID 0)
-- Dependencies: 217
-- Name: usuarios_id_usuario_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.usuarios_id_usuario_seq OWNED BY public.usuarios.id_usuario;


--
-- TOC entry 4813 (class 2604 OID 16480)
-- Name: asistencias id_asistencia; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asistencias ALTER COLUMN id_asistencia SET DEFAULT nextval('public.asistencias_id_asistencia_seq'::regclass);


--
-- TOC entry 4818 (class 2604 OID 16504)
-- Name: clases id_clase; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clases ALTER COLUMN id_clase SET DEFAULT nextval('public.clases_id_clase_seq'::regclass);


--
-- TOC entry 4819 (class 2604 OID 16513)
-- Name: horarios_clases id_horario_clase; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.horarios_clases ALTER COLUMN id_horario_clase SET DEFAULT nextval('public.horarios_clases_id_horario_clase_seq'::regclass);


--
-- TOC entry 4821 (class 2604 OID 16530)
-- Name: inscripciones_clases id_inscripcion; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.inscripciones_clases ALTER COLUMN id_inscripcion SET DEFAULT nextval('public.inscripciones_clases_id_inscripcion_seq'::regclass);


--
-- TOC entry 4816 (class 2604 OID 16494)
-- Name: instructores id_instructor; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.instructores ALTER COLUMN id_instructor SET DEFAULT nextval('public.instructores_id_instructor_seq'::regclass);


--
-- TOC entry 4827 (class 2604 OID 16561)
-- Name: mantenimiento_maquinas id_mantenimiento; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mantenimiento_maquinas ALTER COLUMN id_mantenimiento SET DEFAULT nextval('public.mantenimiento_maquinas_id_mantenimiento_seq'::regclass);


--
-- TOC entry 4825 (class 2604 OID 16551)
-- Name: maquinas id_maquina; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.maquinas ALTER COLUMN id_maquina SET DEFAULT nextval('public.maquinas_id_maquina_seq'::regclass);


--
-- TOC entry 4807 (class 2604 OID 16431)
-- Name: membresias id_membresia; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.membresias ALTER COLUMN id_membresia SET DEFAULT nextval('public.membresias_id_membresia_seq'::regclass);


--
-- TOC entry 4805 (class 2604 OID 16417)
-- Name: miembros id_miembro; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.miembros ALTER COLUMN id_miembro SET DEFAULT nextval('public.miembros_id_miembro_seq'::regclass);


--
-- TOC entry 4809 (class 2604 OID 16439)
-- Name: miembros_membresias id_miembro_membresia; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.miembros_membresias ALTER COLUMN id_miembro_membresia SET DEFAULT nextval('public.miembros_membresias_id_miembro_membresia_seq'::regclass);


--
-- TOC entry 4811 (class 2604 OID 16457)
-- Name: pagos id_pago; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos ALTER COLUMN id_pago SET DEFAULT nextval('public.pagos_id_pago_seq'::regclass);


--
-- TOC entry 4804 (class 2604 OID 16408)
-- Name: usuarios id_usuario; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios ALTER COLUMN id_usuario SET DEFAULT nextval('public.usuarios_id_usuario_seq'::regclass);


--
-- TOC entry 4848 (class 2606 OID 16484)
-- Name: asistencias asistencias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asistencias
    ADD CONSTRAINT asistencias_pkey PRIMARY KEY (id_asistencia);


--
-- TOC entry 4854 (class 2606 OID 16508)
-- Name: clases clases_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clases
    ADD CONSTRAINT clases_pkey PRIMARY KEY (id_clase);


--
-- TOC entry 4856 (class 2606 OID 16515)
-- Name: horarios_clases horarios_clases_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.horarios_clases
    ADD CONSTRAINT horarios_clases_pkey PRIMARY KEY (id_horario_clase);


--
-- TOC entry 4858 (class 2606 OID 16536)
-- Name: inscripciones_clases inscripciones_clases_id_miembro_id_horario_clase_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.inscripciones_clases
    ADD CONSTRAINT inscripciones_clases_id_miembro_id_horario_clase_key UNIQUE (id_miembro, id_horario_clase);


--
-- TOC entry 4860 (class 2606 OID 16534)
-- Name: inscripciones_clases inscripciones_clases_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.inscripciones_clases
    ADD CONSTRAINT inscripciones_clases_pkey PRIMARY KEY (id_inscripcion);


--
-- TOC entry 4850 (class 2606 OID 16499)
-- Name: instructores instructores_correo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.instructores
    ADD CONSTRAINT instructores_correo_key UNIQUE (correo);


--
-- TOC entry 4852 (class 2606 OID 16497)
-- Name: instructores instructores_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.instructores
    ADD CONSTRAINT instructores_pkey PRIMARY KEY (id_instructor);


--
-- TOC entry 4864 (class 2606 OID 16566)
-- Name: mantenimiento_maquinas mantenimiento_maquinas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mantenimiento_maquinas
    ADD CONSTRAINT mantenimiento_maquinas_pkey PRIMARY KEY (id_mantenimiento);


--
-- TOC entry 4862 (class 2606 OID 16556)
-- Name: maquinas maquinas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.maquinas
    ADD CONSTRAINT maquinas_pkey PRIMARY KEY (id_maquina);


--
-- TOC entry 4840 (class 2606 OID 16434)
-- Name: membresias membresias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.membresias
    ADD CONSTRAINT membresias_pkey PRIMARY KEY (id_membresia);


--
-- TOC entry 4834 (class 2606 OID 16426)
-- Name: miembros miembros_codigo_qr_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.miembros
    ADD CONSTRAINT miembros_codigo_qr_key UNIQUE (codigo_qr);


--
-- TOC entry 4836 (class 2606 OID 16424)
-- Name: miembros miembros_correo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.miembros
    ADD CONSTRAINT miembros_correo_key UNIQUE (correo);


--
-- TOC entry 4842 (class 2606 OID 16442)
-- Name: miembros_membresias miembros_membresias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.miembros_membresias
    ADD CONSTRAINT miembros_membresias_pkey PRIMARY KEY (id_miembro_membresia);


--
-- TOC entry 4838 (class 2606 OID 16422)
-- Name: miembros miembros_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.miembros
    ADD CONSTRAINT miembros_pkey PRIMARY KEY (id_miembro);


--
-- TOC entry 4846 (class 2606 OID 16460)
-- Name: pagos pagos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT pagos_pkey PRIMARY KEY (id_pago);


--
-- TOC entry 4844 (class 2606 OID 49153)
-- Name: miembros_membresias uk_miembro_unico; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.miembros_membresias
    ADD CONSTRAINT uk_miembro_unico UNIQUE (id_miembro);


--
-- TOC entry 4830 (class 2606 OID 16410)
-- Name: usuarios usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_pkey PRIMARY KEY (id_usuario);


--
-- TOC entry 4832 (class 2606 OID 16412)
-- Name: usuarios usuarios_usuario_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_usuario_key UNIQUE (usuario);


--
-- TOC entry 4877 (class 2620 OID 49155)
-- Name: miembros_membresias trigger_actualizar_vencido; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trigger_actualizar_vencido BEFORE INSERT OR UPDATE ON public.miembros_membresias FOR EACH ROW EXECUTE FUNCTION public.actualizar_estado_vencido();


--
-- TOC entry 4870 (class 2606 OID 16485)
-- Name: asistencias asistencias_id_miembro_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asistencias
    ADD CONSTRAINT asistencias_id_miembro_fkey FOREIGN KEY (id_miembro) REFERENCES public.miembros(id_miembro) ON DELETE CASCADE;


--
-- TOC entry 4871 (class 2606 OID 24583)
-- Name: horarios_clases fk_clase_horario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.horarios_clases
    ADD CONSTRAINT fk_clase_horario FOREIGN KEY (id_clase) REFERENCES public.clases(id_clase) ON DELETE CASCADE;


--
-- TOC entry 4873 (class 2606 OID 57349)
-- Name: inscripciones_clases fk_horario_clase; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.inscripciones_clases
    ADD CONSTRAINT fk_horario_clase FOREIGN KEY (id_horario_clase) REFERENCES public.horarios_clases(id_horario_clase) ON DELETE CASCADE;


--
-- TOC entry 4872 (class 2606 OID 57354)
-- Name: horarios_clases horarios_clases_id_instructor_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.horarios_clases
    ADD CONSTRAINT horarios_clases_id_instructor_fkey FOREIGN KEY (id_instructor) REFERENCES public.instructores(id_instructor) ON DELETE CASCADE;


--
-- TOC entry 4874 (class 2606 OID 16537)
-- Name: inscripciones_clases inscripciones_clases_id_miembro_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.inscripciones_clases
    ADD CONSTRAINT inscripciones_clases_id_miembro_fkey FOREIGN KEY (id_miembro) REFERENCES public.miembros(id_miembro) ON DELETE CASCADE;


--
-- TOC entry 4875 (class 2606 OID 16567)
-- Name: mantenimiento_maquinas mantenimiento_maquinas_id_maquina_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mantenimiento_maquinas
    ADD CONSTRAINT mantenimiento_maquinas_id_maquina_fkey FOREIGN KEY (id_maquina) REFERENCES public.maquinas(id_maquina) ON DELETE CASCADE;


--
-- TOC entry 4876 (class 2606 OID 16572)
-- Name: mantenimiento_maquinas mantenimiento_maquinas_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mantenimiento_maquinas
    ADD CONSTRAINT mantenimiento_maquinas_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario);


--
-- TOC entry 4865 (class 2606 OID 16448)
-- Name: miembros_membresias miembros_membresias_id_membresia_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.miembros_membresias
    ADD CONSTRAINT miembros_membresias_id_membresia_fkey FOREIGN KEY (id_membresia) REFERENCES public.membresias(id_membresia);


--
-- TOC entry 4866 (class 2606 OID 16443)
-- Name: miembros_membresias miembros_membresias_id_miembro_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.miembros_membresias
    ADD CONSTRAINT miembros_membresias_id_miembro_fkey FOREIGN KEY (id_miembro) REFERENCES public.miembros(id_miembro) ON DELETE CASCADE;


--
-- TOC entry 4867 (class 2606 OID 16461)
-- Name: pagos pagos_id_miembro_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT pagos_id_miembro_fkey FOREIGN KEY (id_miembro) REFERENCES public.miembros(id_miembro) ON DELETE CASCADE;


--
-- TOC entry 4868 (class 2606 OID 16466)
-- Name: pagos pagos_id_miembro_membresia_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT pagos_id_miembro_membresia_fkey FOREIGN KEY (id_miembro_membresia) REFERENCES public.miembros_membresias(id_miembro_membresia);


--
-- TOC entry 4869 (class 2606 OID 16471)
-- Name: pagos pagos_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT pagos_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario);


-- Completed on 2025-11-26 00:28:56

--
-- PostgreSQL database dump complete
--

\unrestrict xE8cKPcH5byDjWdO38HTmrsiQop6tttuUbKmjtkbXxSsLjvxLblS0qQ7P2cTLZo

