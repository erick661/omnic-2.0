--
-- PostgreSQL database dump
--

\restrict SSbzrBA0C1H4RRUFbHK0SdiD8SOjzLzogfoje8METYCzC7s7cxMk1pildvDW2f9

-- Dumped from database version 18.0
-- Dumped by pg_dump version 18.0

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

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: campaign_sends; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.campaign_sends (
    id bigint NOT NULL,
    campaign_id bigint NOT NULL,
    contact_id bigint NOT NULL,
    email character varying(255) NOT NULL,
    send_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    sent_at timestamp(0) without time zone,
    delivered_at timestamp(0) without time zone,
    opened_at timestamp(0) without time zone,
    clicked_at timestamp(0) without time zone,
    brevo_message_id character varying(255),
    error_message text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT campaign_sends_send_status_check CHECK (((send_status)::text = ANY ((ARRAY['pending'::character varying, 'sent'::character varying, 'delivered'::character varying, 'bounced'::character varying, 'spam'::character varying, 'blocked'::character varying, 'opened'::character varying, 'clicked'::character varying])::text[])))
);


--
-- Name: campaign_sends_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.campaign_sends_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: campaign_sends_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.campaign_sends_id_seq OWNED BY public.campaign_sends.id;


--
-- Name: campaigns; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.campaigns (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT campaigns_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'scheduled'::character varying, 'running'::character varying, 'completed'::character varying, 'paused'::character varying])::text[])))
);


--
-- Name: campaigns_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.campaigns_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: campaigns_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.campaigns_id_seq OWNED BY public.campaigns.id;


--
-- Name: case_metrics; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.case_metrics (
    id bigint NOT NULL,
    case_id bigint NOT NULL,
    email_count integer DEFAULT 0 NOT NULL,
    whatsapp_count integer DEFAULT 0 NOT NULL,
    sms_count integer DEFAULT 0 NOT NULL,
    phone_count integer DEFAULT 0 NOT NULL,
    webchat_count integer DEFAULT 0 NOT NULL,
    avg_email_response_hours numeric(8,2),
    avg_whatsapp_response_hours numeric(8,2),
    avg_sms_response_hours numeric(8,2),
    avg_phone_response_hours numeric(8,2),
    preferred_channel character varying(255),
    preferred_channel_detected_at timestamp(0) without time zone,
    satisfaction_score integer,
    satisfaction_collected_at timestamp(0) without time zone,
    total_interaction_time_hours numeric(10,2),
    escalation_count integer DEFAULT 0 NOT NULL,
    sla_breach boolean DEFAULT false NOT NULL,
    sla_breach_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT case_metrics_preferred_channel_check CHECK (((preferred_channel)::text = ANY ((ARRAY['email'::character varying, 'whatsapp'::character varying, 'sms'::character varying, 'phone'::character varying, 'webchat'::character varying])::text[])))
);


--
-- Name: COLUMN case_metrics.email_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.email_count IS 'Cantidad de comunicaciones por email';


--
-- Name: COLUMN case_metrics.whatsapp_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.whatsapp_count IS 'Cantidad de comunicaciones por WhatsApp';


--
-- Name: COLUMN case_metrics.sms_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.sms_count IS 'Cantidad de comunicaciones por SMS';


--
-- Name: COLUMN case_metrics.phone_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.phone_count IS 'Cantidad de comunicaciones telefónicas';


--
-- Name: COLUMN case_metrics.webchat_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.webchat_count IS 'Cantidad de comunicaciones por chat web';


--
-- Name: COLUMN case_metrics.avg_email_response_hours; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.avg_email_response_hours IS 'Tiempo promedio de respuesta por email';


--
-- Name: COLUMN case_metrics.avg_whatsapp_response_hours; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.avg_whatsapp_response_hours IS 'Tiempo promedio de respuesta por WhatsApp';


--
-- Name: COLUMN case_metrics.avg_sms_response_hours; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.avg_sms_response_hours IS 'Tiempo promedio de respuesta por SMS';


--
-- Name: COLUMN case_metrics.avg_phone_response_hours; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.avg_phone_response_hours IS 'Tiempo promedio para llamadas';


--
-- Name: COLUMN case_metrics.preferred_channel; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.preferred_channel IS 'Canal preferido del empleador';


--
-- Name: COLUMN case_metrics.preferred_channel_detected_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.preferred_channel_detected_at IS 'Cuándo se detectó la preferencia';


--
-- Name: COLUMN case_metrics.satisfaction_score; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.satisfaction_score IS 'Puntuación de satisfacción (1-10)';


--
-- Name: COLUMN case_metrics.satisfaction_collected_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.satisfaction_collected_at IS 'Cuándo se recopiló la satisfacción';


--
-- Name: COLUMN case_metrics.total_interaction_time_hours; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.total_interaction_time_hours IS 'Tiempo total de interacción';


--
-- Name: COLUMN case_metrics.escalation_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.escalation_count IS 'Cantidad de escalaciones al supervisor';


--
-- Name: COLUMN case_metrics.sla_breach; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.sla_breach IS 'Si se incumplió el SLA';


--
-- Name: COLUMN case_metrics.sla_breach_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.case_metrics.sla_breach_at IS 'Cuándo se incumplió el SLA';


--
-- Name: case_metrics_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.case_metrics_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: case_metrics_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.case_metrics_id_seq OWNED BY public.case_metrics.id;


--
-- Name: cases; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cases (
    id bigint NOT NULL,
    case_number character varying(20) NOT NULL,
    employer_rut character varying(8),
    employer_dv character varying(1),
    employer_name character varying(255),
    employer_phone character varying(20),
    employer_email character varying(255),
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    priority character varying(255) DEFAULT 'normal'::character varying NOT NULL,
    assigned_to bigint,
    assigned_by bigint,
    assigned_at timestamp(0) without time zone,
    origin_channel character varying(255) NOT NULL,
    origin_communication_id bigint,
    campaign_id bigint,
    first_response_at timestamp(0) without time zone,
    last_activity_at timestamp(0) without time zone,
    resolved_at timestamp(0) without time zone,
    internal_notes text,
    auto_category character varying(100),
    tags json,
    response_time_hours integer,
    resolution_time_hours integer,
    communication_count integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT cases_origin_channel_check CHECK (((origin_channel)::text = ANY ((ARRAY['email'::character varying, 'whatsapp'::character varying, 'sms'::character varying, 'phone'::character varying, 'webchat'::character varying, 'campaign'::character varying])::text[]))),
    CONSTRAINT cases_priority_check CHECK (((priority)::text = ANY ((ARRAY['low'::character varying, 'normal'::character varying, 'high'::character varying, 'urgent'::character varying])::text[]))),
    CONSTRAINT cases_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'assigned'::character varying, 'in_progress'::character varying, 'pending_closure'::character varying, 'resolved'::character varying, 'spam'::character varying])::text[])))
);


--
-- Name: COLUMN cases.case_number; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.case_number IS 'CASO-YYYY-NNNNNN';


--
-- Name: COLUMN cases.employer_rut; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.employer_rut IS 'RUT del empleador';


--
-- Name: COLUMN cases.employer_dv; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.employer_dv IS 'Dígito verificador';


--
-- Name: COLUMN cases.employer_name; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.employer_name IS 'Nombre o razón social';


--
-- Name: COLUMN cases.employer_phone; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.employer_phone IS 'Teléfono principal';


--
-- Name: COLUMN cases.employer_email; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.employer_email IS 'Email principal';


--
-- Name: COLUMN cases.status; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.status IS 'Estado del caso';


--
-- Name: COLUMN cases.priority; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.priority IS 'Prioridad del caso';


--
-- Name: COLUMN cases.assigned_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.assigned_at IS 'Fecha de asignación';


--
-- Name: COLUMN cases.origin_channel; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.origin_channel IS 'Canal por el que se inició el caso';


--
-- Name: COLUMN cases.origin_communication_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.origin_communication_id IS 'ID de la comunicación original que generó el caso';


--
-- Name: COLUMN cases.campaign_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.campaign_id IS 'Campaña que generó el caso';


--
-- Name: COLUMN cases.first_response_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.first_response_at IS 'Fecha de primera respuesta del ejecutivo';


--
-- Name: COLUMN cases.last_activity_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.last_activity_at IS 'Última actividad en el caso';


--
-- Name: COLUMN cases.resolved_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.resolved_at IS 'Fecha de resolución del caso';


--
-- Name: COLUMN cases.internal_notes; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.internal_notes IS 'Notas internas del supervisor/ejecutivo';


--
-- Name: COLUMN cases.auto_category; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.auto_category IS 'Categorización automática del caso';


--
-- Name: COLUMN cases.tags; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.tags IS 'Etiquetas flexibles para categorización';


--
-- Name: COLUMN cases.response_time_hours; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.response_time_hours IS 'Tiempo hasta primera respuesta en horas';


--
-- Name: COLUMN cases.resolution_time_hours; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.resolution_time_hours IS 'Tiempo total de resolución en horas';


--
-- Name: COLUMN cases.communication_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cases.communication_count IS 'Contador de comunicaciones en el caso';


--
-- Name: cases_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cases_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cases_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cases_id_seq OWNED BY public.cases.id;


--
-- Name: communications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.communications (
    id bigint NOT NULL,
    case_id bigint NOT NULL,
    channel_type character varying(255) NOT NULL,
    direction character varying(255) NOT NULL,
    external_id character varying(255),
    thread_id character varying(255),
    subject character varying(500),
    content_text text,
    content_html text,
    from_contact character varying(255),
    from_name character varying(255),
    to_contact character varying(255),
    cc_contacts json,
    channel_metadata json,
    attachments json,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    received_at timestamp(0) without time zone,
    sent_at timestamp(0) without time zone,
    delivered_at timestamp(0) without time zone,
    read_at timestamp(0) without time zone,
    reference_code character varying(50),
    in_reply_to bigint,
    processed_at timestamp(0) without time zone,
    processed_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT communications_channel_type_check CHECK (((channel_type)::text = ANY ((ARRAY['email'::character varying, 'whatsapp'::character varying, 'sms'::character varying, 'phone'::character varying, 'webchat'::character varying])::text[]))),
    CONSTRAINT communications_direction_check CHECK (((direction)::text = ANY ((ARRAY['inbound'::character varying, 'outbound'::character varying])::text[]))),
    CONSTRAINT communications_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'sent'::character varying, 'delivered'::character varying, 'read'::character varying, 'failed'::character varying])::text[])))
);


--
-- Name: COLUMN communications.channel_type; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.channel_type IS 'Canal de comunicación';


--
-- Name: COLUMN communications.direction; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.direction IS 'Dirección de la comunicación';


--
-- Name: COLUMN communications.external_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.external_id IS 'ID externo del canal (Gmail message_id, WhatsApp message_id, etc.)';


--
-- Name: COLUMN communications.thread_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.thread_id IS 'ID de hilo para agrupar conversaciones del mismo canal';


--
-- Name: COLUMN communications.subject; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.subject IS 'Asunto para email, título para otros canales';


--
-- Name: COLUMN communications.content_text; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.content_text IS 'Contenido en texto plano';


--
-- Name: COLUMN communications.content_html; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.content_html IS 'Contenido en HTML';


--
-- Name: COLUMN communications.from_contact; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.from_contact IS 'Email, teléfono o ID de usuario que envía';


--
-- Name: COLUMN communications.from_name; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.from_name IS 'Nombre del remitente';


--
-- Name: COLUMN communications.to_contact; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.to_contact IS 'Email, teléfono o ID de usuario que recibe';


--
-- Name: COLUMN communications.cc_contacts; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.cc_contacts IS 'Lista de contactos en copia';


--
-- Name: COLUMN communications.channel_metadata; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.channel_metadata IS 'Datos específicos del canal (headers email, metadata WhatsApp, etc.)';


--
-- Name: COLUMN communications.attachments; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.attachments IS 'Lista de adjuntos/multimedia';


--
-- Name: COLUMN communications.status; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.status IS 'Estado de la comunicación';


--
-- Name: COLUMN communications.received_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.received_at IS 'Fecha de recepción';


--
-- Name: COLUMN communications.sent_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.sent_at IS 'Fecha de envío';


--
-- Name: COLUMN communications.delivered_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.delivered_at IS 'Fecha de entrega confirmada';


--
-- Name: COLUMN communications.read_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.read_at IS 'Fecha de lectura confirmada';


--
-- Name: COLUMN communications.reference_code; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.reference_code IS 'Código de referencia para seguimiento';


--
-- Name: COLUMN communications.processed_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.communications.processed_at IS 'Fecha de procesamiento';


--
-- Name: communications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.communications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: communications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.communications_id_seq OWNED BY public.communications.id;


--
-- Name: contact_list_members; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contact_list_members (
    id bigint NOT NULL,
    contact_id bigint NOT NULL,
    contact_list_id bigint NOT NULL,
    added_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: contact_list_members_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contact_list_members_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contact_list_members_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contact_list_members_id_seq OWNED BY public.contact_list_members.id;


--
-- Name: contact_lists; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contact_lists (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    filter_criteria json,
    brevo_list_id bigint,
    created_by bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN contact_lists.filter_criteria; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.contact_lists.filter_criteria IS 'Filtros aplicados para lista dinámica';


--
-- Name: contact_lists_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contact_lists_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contact_lists_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contact_lists_id_seq OWNED BY public.contact_lists.id;


--
-- Name: contacts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contacts (
    id bigint NOT NULL,
    email character varying(255) NOT NULL,
    first_name character varying(255),
    last_name character varying(255),
    company character varying(255),
    rut_empleador character varying(8),
    dv_empleador character varying(1),
    producto character varying(50),
    phone character varying(50),
    attributes json,
    is_active boolean DEFAULT true NOT NULL,
    brevo_contact_id bigint,
    imported_from character varying(100),
    imported_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN contacts.producto; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.contacts.producto IS 'AFP-CAPITAL, etc';


--
-- Name: COLUMN contacts.attributes; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.contacts.attributes IS 'Campos dinámicos del CSV';


--
-- Name: COLUMN contacts.imported_from; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.contacts.imported_from IS 'CSV, Excel, API, etc';


--
-- Name: contacts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contacts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contacts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contacts_id_seq OWNED BY public.contacts.id;


--
-- Name: email_attachments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_attachments (
    id bigint NOT NULL,
    imported_email_id bigint NOT NULL,
    original_filename character varying(255) NOT NULL,
    stored_filename character varying(255) NOT NULL,
    file_path character varying(500) NOT NULL,
    mime_type character varying(100) NOT NULL,
    file_size bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN email_attachments.stored_filename; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.email_attachments.stored_filename IS 'UUID único';


--
-- Name: COLUMN email_attachments.file_path; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.email_attachments.file_path IS 'storage/attachments/';


--
-- Name: COLUMN email_attachments.file_size; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.email_attachments.file_size IS 'bytes';


--
-- Name: email_attachments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.email_attachments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: email_attachments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.email_attachments_id_seq OWNED BY public.email_attachments.id;


--
-- Name: email_campaigns; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_campaigns (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    subject character varying(500) NOT NULL,
    html_content text NOT NULL,
    text_content text,
    from_email character varying(255) NOT NULL,
    from_name character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    scheduled_at timestamp(0) without time zone,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    total_recipients integer DEFAULT 0 NOT NULL,
    sent_count integer DEFAULT 0 NOT NULL,
    delivered_count integer DEFAULT 0 NOT NULL,
    opened_count integer DEFAULT 0 NOT NULL,
    clicked_count integer DEFAULT 0 NOT NULL,
    bounced_count integer DEFAULT 0 NOT NULL,
    brevo_campaign_id bigint,
    created_by bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT email_campaigns_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'scheduled'::character varying, 'sending'::character varying, 'sent'::character varying, 'completed'::character varying, 'failed'::character varying])::text[])))
);


--
-- Name: COLUMN email_campaigns.from_email; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.email_campaigns.from_email IS 'orpro@orpro.cl';


--
-- Name: COLUMN email_campaigns.scheduled_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.email_campaigns.scheduled_at IS 'Dentro de horario legal Chile';


--
-- Name: email_campaigns_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.email_campaigns_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: email_campaigns_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.email_campaigns_id_seq OWNED BY public.email_campaigns.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: gmail_groups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.gmail_groups (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    is_generic boolean DEFAULT false NOT NULL,
    assigned_user_id bigint,
    gmail_label character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN gmail_groups.name; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_groups.name IS 'contactenos@orpro.cl';


--
-- Name: COLUMN gmail_groups.is_generic; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_groups.is_generic IS 'true para contactenos@orpro.cl';


--
-- Name: COLUMN gmail_groups.gmail_label; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_groups.gmail_label IS 'Etiqueta en Gmail';


--
-- Name: gmail_groups_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.gmail_groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: gmail_groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.gmail_groups_id_seq OWNED BY public.gmail_groups.id;


--
-- Name: gmail_metadata; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.gmail_metadata (
    id bigint NOT NULL,
    communication_id bigint NOT NULL,
    gmail_message_id character varying(255) NOT NULL,
    gmail_thread_id character varying(255) NOT NULL,
    gmail_history_id character varying(255),
    gmail_labels json,
    gmail_snippet text,
    size_estimate integer,
    raw_headers json,
    message_references text,
    in_reply_to character varying(255),
    eml_download_url character varying(500),
    eml_backup_path character varying(500),
    attachments_metadata json,
    sync_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    last_sync_at timestamp(0) without time zone,
    sync_error_message text,
    is_backed_up boolean DEFAULT false NOT NULL,
    backup_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT gmail_metadata_sync_status_check CHECK (((sync_status)::text = ANY ((ARRAY['pending'::character varying, 'synced'::character varying, 'error'::character varying])::text[])))
);


--
-- Name: COLUMN gmail_metadata.gmail_message_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.gmail_message_id IS 'ID único del mensaje en Gmail API';


--
-- Name: COLUMN gmail_metadata.gmail_thread_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.gmail_thread_id IS 'ID del hilo de conversación en Gmail';


--
-- Name: COLUMN gmail_metadata.gmail_history_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.gmail_history_id IS 'ID de historial para sincronización incremental';


--
-- Name: COLUMN gmail_metadata.gmail_labels; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.gmail_labels IS 'Etiquetas Gmail (INBOX, SENT, SPAM, etc.)';


--
-- Name: COLUMN gmail_metadata.gmail_snippet; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.gmail_snippet IS 'Vista previa generada por Gmail';


--
-- Name: COLUMN gmail_metadata.size_estimate; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.size_estimate IS 'Tamaño del mensaje en bytes';


--
-- Name: COLUMN gmail_metadata.raw_headers; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.raw_headers IS 'Headers completos del email';


--
-- Name: COLUMN gmail_metadata.message_references; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.message_references IS 'Referencias para hilos de conversación';


--
-- Name: COLUMN gmail_metadata.in_reply_to; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.in_reply_to IS 'ID del mensaje padre';


--
-- Name: COLUMN gmail_metadata.eml_download_url; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.eml_download_url IS 'URL temporal para descargar EML';


--
-- Name: COLUMN gmail_metadata.eml_backup_path; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.eml_backup_path IS 'Ruta del backup local del EML';


--
-- Name: COLUMN gmail_metadata.attachments_metadata; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.attachments_metadata IS 'Info de adjuntos con IDs Gmail';


--
-- Name: COLUMN gmail_metadata.sync_status; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.sync_status IS 'Estado de sincronización con Gmail';


--
-- Name: COLUMN gmail_metadata.last_sync_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.last_sync_at IS 'Última sincronización exitosa';


--
-- Name: COLUMN gmail_metadata.sync_error_message; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.sync_error_message IS 'Mensaje de error de sincronización';


--
-- Name: COLUMN gmail_metadata.is_backed_up; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.is_backed_up IS 'Si el EML está respaldado localmente';


--
-- Name: COLUMN gmail_metadata.backup_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.gmail_metadata.backup_at IS 'Cuándo se respaldó el EML';


--
-- Name: gmail_metadata_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.gmail_metadata_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: gmail_metadata_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.gmail_metadata_id_seq OWNED BY public.gmail_metadata.id;


--
-- Name: imported_emails; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.imported_emails (
    id bigint NOT NULL,
    gmail_message_id character varying(255) NOT NULL,
    gmail_thread_id character varying(255) NOT NULL,
    gmail_group_id bigint NOT NULL,
    subject text NOT NULL,
    from_email character varying(255) NOT NULL,
    from_name character varying(255),
    to_email character varying(255) NOT NULL,
    cc_emails json,
    bcc_emails json,
    body_html text,
    body_text text,
    received_at timestamp(0) without time zone NOT NULL,
    imported_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    has_attachments boolean DEFAULT false NOT NULL,
    priority character varying(255) DEFAULT 'normal'::character varying NOT NULL,
    reference_code_id bigint,
    rut_empleador character varying(8),
    dv_empleador character varying(1),
    assigned_to bigint,
    assigned_by bigint,
    assigned_at timestamp(0) without time zone,
    assignment_notes text,
    case_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    marked_resolved_at timestamp(0) without time zone,
    auto_resolved_at timestamp(0) without time zone,
    spam_marked_by bigint,
    spam_marked_at timestamp(0) without time zone,
    derived_to_supervisor boolean DEFAULT false NOT NULL,
    derivation_notes text,
    derived_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT imported_emails_case_status_check CHECK (((case_status)::text = ANY ((ARRAY['pending'::character varying, 'assigned'::character varying, 'opened'::character varying, 'in_progress'::character varying, 'pending_closure'::character varying, 'resolved'::character varying, 'spam_marked'::character varying])::text[]))),
    CONSTRAINT imported_emails_priority_check CHECK (((priority)::text = ANY ((ARRAY['low'::character varying, 'normal'::character varying, 'high'::character varying])::text[])))
);


--
-- Name: COLUMN imported_emails.gmail_message_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.imported_emails.gmail_message_id IS 'ID de Gmail API';


--
-- Name: COLUMN imported_emails.gmail_thread_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.imported_emails.gmail_thread_id IS 'Hilo de Gmail';


--
-- Name: COLUMN imported_emails.to_email; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.imported_emails.to_email IS 'Alias destino';


--
-- Name: COLUMN imported_emails.rut_empleador; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.imported_emails.rut_empleador IS 'Extraído o asignado por ejecutivo';


--
-- Name: COLUMN imported_emails.assignment_notes; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.imported_emails.assignment_notes IS 'Notas del supervisor';


--
-- Name: COLUMN imported_emails.auto_resolved_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.imported_emails.auto_resolved_at IS 'Auto-resuelto después de 2 días';


--
-- Name: imported_emails_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.imported_emails_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: imported_emails_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.imported_emails_id_seq OWNED BY public.imported_emails.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: oauth_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.oauth_tokens (
    id bigint NOT NULL,
    provider character varying(255) DEFAULT 'gmail'::character varying NOT NULL,
    identifier character varying(255),
    access_token text NOT NULL,
    refresh_token text,
    scopes json,
    expires_at timestamp(0) without time zone,
    metadata json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN oauth_tokens.provider; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.oauth_tokens.provider IS 'gmail, outlook, etc.';


--
-- Name: COLUMN oauth_tokens.identifier; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.oauth_tokens.identifier IS 'email o user_id asociado';


--
-- Name: COLUMN oauth_tokens.access_token; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.oauth_tokens.access_token IS 'Token encriptado';


--
-- Name: COLUMN oauth_tokens.refresh_token; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.oauth_tokens.refresh_token IS 'Refresh token encriptado';


--
-- Name: COLUMN oauth_tokens.scopes; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.oauth_tokens.scopes IS 'Scopes otorgados';


--
-- Name: COLUMN oauth_tokens.expires_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.oauth_tokens.expires_at IS 'Cuándo expira el access token';


--
-- Name: COLUMN oauth_tokens.metadata; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.oauth_tokens.metadata IS 'Info adicional del token';


--
-- Name: oauth_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.oauth_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: oauth_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.oauth_tokens_id_seq OWNED BY public.oauth_tokens.id;


--
-- Name: outbox_attachments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.outbox_attachments (
    id bigint NOT NULL,
    outbox_email_id bigint NOT NULL,
    original_filename character varying(255) NOT NULL,
    stored_filename character varying(255) NOT NULL,
    file_path character varying(500) NOT NULL,
    mime_type character varying(100) NOT NULL,
    file_size bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: outbox_attachments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.outbox_attachments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: outbox_attachments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.outbox_attachments_id_seq OWNED BY public.outbox_attachments.id;


--
-- Name: outbox_emails; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.outbox_emails (
    id bigint NOT NULL,
    imported_email_id bigint,
    from_email character varying(255) NOT NULL,
    from_name character varying(255) NOT NULL,
    to_email character varying(255) NOT NULL,
    cc_emails json,
    bcc_emails json,
    subject text NOT NULL,
    body_html text NOT NULL,
    body_text text,
    send_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    scheduled_at timestamp(0) without time zone,
    sent_at timestamp(0) without time zone,
    error_message text,
    mark_as_resolved boolean DEFAULT false NOT NULL,
    created_by bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT outbox_emails_send_status_check CHECK (((send_status)::text = ANY ((ARRAY['pending'::character varying, 'sending'::character varying, 'sent'::character varying, 'failed'::character varying])::text[])))
);


--
-- Name: COLUMN outbox_emails.from_email; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.outbox_emails.from_email IS 'lucas.munoz@orpro.cl';


--
-- Name: COLUMN outbox_emails.from_name; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.outbox_emails.from_name IS 'Lucas Muñoz';


--
-- Name: COLUMN outbox_emails.subject; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.outbox_emails.subject IS 'Con código de referencia si aplica';


--
-- Name: COLUMN outbox_emails.mark_as_resolved; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.outbox_emails.mark_as_resolved IS 'Checkbox del ejecutivo';


--
-- Name: outbox_emails_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.outbox_emails_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: outbox_emails_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.outbox_emails_id_seq OWNED BY public.outbox_emails.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: phone_communications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.phone_communications (
    id bigint NOT NULL,
    communication_id bigint NOT NULL,
    phone_number character varying(20) NOT NULL,
    call_duration_seconds integer,
    call_type character varying(255) NOT NULL,
    call_status character varying(255) NOT NULL,
    recording_url character varying(500),
    recording_duration_seconds integer,
    call_summary text,
    follow_up_required boolean DEFAULT false NOT NULL,
    follow_up_date date,
    caller_id character varying(255),
    phone_metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT phone_communications_call_status_check CHECK (((call_status)::text = ANY ((ARRAY['completed'::character varying, 'busy'::character varying, 'no_answer'::character varying, 'failed'::character varying])::text[]))),
    CONSTRAINT phone_communications_call_type_check CHECK (((call_type)::text = ANY ((ARRAY['incoming'::character varying, 'outgoing'::character varying])::text[])))
);


--
-- Name: COLUMN phone_communications.phone_number; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.phone_communications.phone_number IS 'Número de teléfono';


--
-- Name: COLUMN phone_communications.call_duration_seconds; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.phone_communications.call_duration_seconds IS 'Duración de la llamada en segundos';


--
-- Name: COLUMN phone_communications.call_type; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.phone_communications.call_type IS 'Tipo de llamada';


--
-- Name: COLUMN phone_communications.call_status; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.phone_communications.call_status IS 'Estado final de la llamada';


--
-- Name: COLUMN phone_communications.recording_url; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.phone_communications.recording_url IS 'URL de la grabación de la llamada';


--
-- Name: COLUMN phone_communications.recording_duration_seconds; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.phone_communications.recording_duration_seconds IS 'Duración de la grabación en segundos';


--
-- Name: COLUMN phone_communications.call_summary; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.phone_communications.call_summary IS 'Resumen de la llamada';


--
-- Name: COLUMN phone_communications.follow_up_required; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.phone_communications.follow_up_required IS 'Si requiere seguimiento';


--
-- Name: COLUMN phone_communications.follow_up_date; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.phone_communications.follow_up_date IS 'Fecha sugerida para seguimiento';


--
-- Name: COLUMN phone_communications.caller_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.phone_communications.caller_id IS 'Identificador del sistema telefónico';


--
-- Name: COLUMN phone_communications.phone_metadata; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.phone_communications.phone_metadata IS 'Metadatos específicos del sistema telefónico';


--
-- Name: phone_communications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.phone_communications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: phone_communications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.phone_communications_id_seq OWNED BY public.phone_communications.id;


--
-- Name: reference_codes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reference_codes (
    id bigint NOT NULL,
    rut_empleador character varying(8) NOT NULL,
    dv_empleador character varying(1) NOT NULL,
    producto character varying(50) NOT NULL,
    code_hash character varying(255) NOT NULL,
    assigned_user_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    case_id bigint,
    channel_type character varying(255),
    channel_metadata json,
    usage_count integer DEFAULT 0 NOT NULL,
    last_used_at timestamp(0) without time zone,
    CONSTRAINT reference_codes_channel_type_check CHECK (((channel_type)::text = ANY ((ARRAY['email'::character varying, 'whatsapp'::character varying, 'sms'::character varying, 'phone'::character varying, 'webchat'::character varying, 'campaign'::character varying])::text[])))
);


--
-- Name: COLUMN reference_codes.rut_empleador; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.reference_codes.rut_empleador IS 'Sin puntos ni guión';


--
-- Name: COLUMN reference_codes.dv_empleador; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.reference_codes.dv_empleador IS 'Dígito verificador';


--
-- Name: COLUMN reference_codes.producto; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.reference_codes.producto IS 'AFP-CAPITAL, etc';


--
-- Name: COLUMN reference_codes.code_hash; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.reference_codes.code_hash IS 'Código codificado';


--
-- Name: COLUMN reference_codes.channel_type; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.reference_codes.channel_type IS 'Canal que generó este código de referencia';


--
-- Name: COLUMN reference_codes.channel_metadata; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.reference_codes.channel_metadata IS 'Datos específicos del canal al generar el código';


--
-- Name: COLUMN reference_codes.usage_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.reference_codes.usage_count IS 'Cantidad de veces que se ha usado este código';


--
-- Name: COLUMN reference_codes.last_used_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.reference_codes.last_used_at IS 'Última vez que se usó el código';


--
-- Name: reference_codes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reference_codes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reference_codes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reference_codes_id_seq OWNED BY public.reference_codes.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: system_config; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.system_config (
    id bigint NOT NULL,
    key character varying(255) NOT NULL,
    value text,
    description text,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: system_config_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.system_config_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: system_config_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.system_config_id_seq OWNED BY public.system_config.id;


--
-- Name: user_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_roles (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    role character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT user_roles_role_check CHECK (((role)::text = ANY ((ARRAY['administrador'::character varying, 'supervisor'::character varying, 'ejecutivo'::character varying, 'masivo'::character varying])::text[])))
);


--
-- Name: user_roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_roles_id_seq OWNED BY public.user_roles.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    role character varying(255) DEFAULT 'ejecutivo'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    email_alias character varying(255),
    nickname character varying(255),
    CONSTRAINT users_role_check CHECK (((role)::text = ANY ((ARRAY['administrador'::character varying, 'supervisor'::character varying, 'ejecutivo'::character varying, 'masivo'::character varying])::text[])))
);


--
-- Name: COLUMN users.email_alias; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.users.email_alias IS 'lucas.munoz@orpro.cl';


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: campaign_sends id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaign_sends ALTER COLUMN id SET DEFAULT nextval('public.campaign_sends_id_seq'::regclass);


--
-- Name: campaigns id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaigns ALTER COLUMN id SET DEFAULT nextval('public.campaigns_id_seq'::regclass);


--
-- Name: case_metrics id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.case_metrics ALTER COLUMN id SET DEFAULT nextval('public.case_metrics_id_seq'::regclass);


--
-- Name: cases id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cases ALTER COLUMN id SET DEFAULT nextval('public.cases_id_seq'::regclass);


--
-- Name: communications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.communications ALTER COLUMN id SET DEFAULT nextval('public.communications_id_seq'::regclass);


--
-- Name: contact_list_members id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_list_members ALTER COLUMN id SET DEFAULT nextval('public.contact_list_members_id_seq'::regclass);


--
-- Name: contact_lists id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_lists ALTER COLUMN id SET DEFAULT nextval('public.contact_lists_id_seq'::regclass);


--
-- Name: contacts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts ALTER COLUMN id SET DEFAULT nextval('public.contacts_id_seq'::regclass);


--
-- Name: email_attachments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_attachments ALTER COLUMN id SET DEFAULT nextval('public.email_attachments_id_seq'::regclass);


--
-- Name: email_campaigns id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_campaigns ALTER COLUMN id SET DEFAULT nextval('public.email_campaigns_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: gmail_groups id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gmail_groups ALTER COLUMN id SET DEFAULT nextval('public.gmail_groups_id_seq'::regclass);


--
-- Name: gmail_metadata id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gmail_metadata ALTER COLUMN id SET DEFAULT nextval('public.gmail_metadata_id_seq'::regclass);


--
-- Name: imported_emails id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.imported_emails ALTER COLUMN id SET DEFAULT nextval('public.imported_emails_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: oauth_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_tokens ALTER COLUMN id SET DEFAULT nextval('public.oauth_tokens_id_seq'::regclass);


--
-- Name: outbox_attachments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.outbox_attachments ALTER COLUMN id SET DEFAULT nextval('public.outbox_attachments_id_seq'::regclass);


--
-- Name: outbox_emails id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.outbox_emails ALTER COLUMN id SET DEFAULT nextval('public.outbox_emails_id_seq'::regclass);


--
-- Name: phone_communications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.phone_communications ALTER COLUMN id SET DEFAULT nextval('public.phone_communications_id_seq'::regclass);


--
-- Name: reference_codes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reference_codes ALTER COLUMN id SET DEFAULT nextval('public.reference_codes_id_seq'::regclass);


--
-- Name: system_config id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_config ALTER COLUMN id SET DEFAULT nextval('public.system_config_id_seq'::regclass);


--
-- Name: user_roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles ALTER COLUMN id SET DEFAULT nextval('public.user_roles_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: campaign_sends campaign_sends_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaign_sends
    ADD CONSTRAINT campaign_sends_pkey PRIMARY KEY (id);


--
-- Name: campaigns campaigns_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaigns
    ADD CONSTRAINT campaigns_pkey PRIMARY KEY (id);


--
-- Name: case_metrics case_metrics_case_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.case_metrics
    ADD CONSTRAINT case_metrics_case_id_unique UNIQUE (case_id);


--
-- Name: case_metrics case_metrics_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.case_metrics
    ADD CONSTRAINT case_metrics_pkey PRIMARY KEY (id);


--
-- Name: cases cases_case_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cases
    ADD CONSTRAINT cases_case_number_unique UNIQUE (case_number);


--
-- Name: cases cases_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cases
    ADD CONSTRAINT cases_pkey PRIMARY KEY (id);


--
-- Name: communications communications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.communications
    ADD CONSTRAINT communications_pkey PRIMARY KEY (id);


--
-- Name: contact_list_members contact_list_members_contact_id_contact_list_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_list_members
    ADD CONSTRAINT contact_list_members_contact_id_contact_list_id_unique UNIQUE (contact_id, contact_list_id);


--
-- Name: contact_list_members contact_list_members_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_list_members
    ADD CONSTRAINT contact_list_members_pkey PRIMARY KEY (id);


--
-- Name: contact_lists contact_lists_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_lists
    ADD CONSTRAINT contact_lists_pkey PRIMARY KEY (id);


--
-- Name: contacts contacts_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_email_unique UNIQUE (email);


--
-- Name: contacts contacts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_pkey PRIMARY KEY (id);


--
-- Name: email_attachments email_attachments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_attachments
    ADD CONSTRAINT email_attachments_pkey PRIMARY KEY (id);


--
-- Name: email_campaigns email_campaigns_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_campaigns
    ADD CONSTRAINT email_campaigns_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: gmail_groups gmail_groups_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gmail_groups
    ADD CONSTRAINT gmail_groups_email_unique UNIQUE (email);


--
-- Name: gmail_groups gmail_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gmail_groups
    ADD CONSTRAINT gmail_groups_pkey PRIMARY KEY (id);


--
-- Name: gmail_metadata gmail_metadata_communication_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gmail_metadata
    ADD CONSTRAINT gmail_metadata_communication_id_unique UNIQUE (communication_id);


--
-- Name: gmail_metadata gmail_metadata_gmail_message_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gmail_metadata
    ADD CONSTRAINT gmail_metadata_gmail_message_id_unique UNIQUE (gmail_message_id);


--
-- Name: gmail_metadata gmail_metadata_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gmail_metadata
    ADD CONSTRAINT gmail_metadata_pkey PRIMARY KEY (id);


--
-- Name: imported_emails imported_emails_gmail_message_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.imported_emails
    ADD CONSTRAINT imported_emails_gmail_message_id_unique UNIQUE (gmail_message_id);


--
-- Name: imported_emails imported_emails_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.imported_emails
    ADD CONSTRAINT imported_emails_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: oauth_tokens oauth_provider_identifier_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_tokens
    ADD CONSTRAINT oauth_provider_identifier_unique UNIQUE (provider, identifier);


--
-- Name: oauth_tokens oauth_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_tokens
    ADD CONSTRAINT oauth_tokens_pkey PRIMARY KEY (id);


--
-- Name: outbox_attachments outbox_attachments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.outbox_attachments
    ADD CONSTRAINT outbox_attachments_pkey PRIMARY KEY (id);


--
-- Name: outbox_emails outbox_emails_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.outbox_emails
    ADD CONSTRAINT outbox_emails_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: phone_communications phone_communications_communication_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.phone_communications
    ADD CONSTRAINT phone_communications_communication_id_unique UNIQUE (communication_id);


--
-- Name: phone_communications phone_communications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.phone_communications
    ADD CONSTRAINT phone_communications_pkey PRIMARY KEY (id);


--
-- Name: reference_codes reference_codes_code_hash_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reference_codes
    ADD CONSTRAINT reference_codes_code_hash_unique UNIQUE (code_hash);


--
-- Name: reference_codes reference_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reference_codes
    ADD CONSTRAINT reference_codes_pkey PRIMARY KEY (id);


--
-- Name: reference_codes reference_codes_rut_empleador_dv_empleador_producto_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reference_codes
    ADD CONSTRAINT reference_codes_rut_empleador_dv_empleador_producto_unique UNIQUE (rut_empleador, dv_empleador, producto);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: system_config system_config_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_config
    ADD CONSTRAINT system_config_key_unique UNIQUE (key);


--
-- Name: system_config system_config_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_config
    ADD CONSTRAINT system_config_pkey PRIMARY KEY (id);


--
-- Name: user_roles user_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_pkey PRIMARY KEY (id);


--
-- Name: user_roles user_roles_user_id_role_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_user_id_role_unique UNIQUE (user_id, role);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: campaign_sends_campaign_id_contact_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX campaign_sends_campaign_id_contact_id_index ON public.campaign_sends USING btree (campaign_id, contact_id);


--
-- Name: campaign_sends_send_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX campaign_sends_send_status_index ON public.campaign_sends USING btree (send_status);


--
-- Name: campaign_sends_sent_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX campaign_sends_sent_at_index ON public.campaign_sends USING btree (sent_at);


--
-- Name: case_metrics_case_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX case_metrics_case_id_index ON public.case_metrics USING btree (case_id);


--
-- Name: case_metrics_preferred_channel_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX case_metrics_preferred_channel_index ON public.case_metrics USING btree (preferred_channel);


--
-- Name: case_metrics_satisfaction_score_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX case_metrics_satisfaction_score_index ON public.case_metrics USING btree (satisfaction_score);


--
-- Name: case_metrics_sla_breach_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX case_metrics_sla_breach_index ON public.case_metrics USING btree (sla_breach);


--
-- Name: case_metrics_updated_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX case_metrics_updated_at_index ON public.case_metrics USING btree (updated_at);


--
-- Name: cases_assigned_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cases_assigned_at_index ON public.cases USING btree (assigned_at);


--
-- Name: cases_assigned_to_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cases_assigned_to_index ON public.cases USING btree (assigned_to);


--
-- Name: cases_case_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cases_case_number_index ON public.cases USING btree (case_number);


--
-- Name: cases_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cases_created_at_index ON public.cases USING btree (created_at);


--
-- Name: cases_employer_rut_employer_dv_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cases_employer_rut_employer_dv_index ON public.cases USING btree (employer_rut, employer_dv);


--
-- Name: cases_last_activity_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cases_last_activity_at_index ON public.cases USING btree (last_activity_at);


--
-- Name: cases_origin_channel_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cases_origin_channel_index ON public.cases USING btree (origin_channel);


--
-- Name: cases_priority_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cases_priority_index ON public.cases USING btree (priority);


--
-- Name: cases_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cases_status_index ON public.cases USING btree (status);


--
-- Name: communications_case_id_channel_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX communications_case_id_channel_type_index ON public.communications USING btree (case_id, channel_type);


--
-- Name: communications_case_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX communications_case_id_index ON public.communications USING btree (case_id);


--
-- Name: communications_case_id_received_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX communications_case_id_received_at_index ON public.communications USING btree (case_id, received_at);


--
-- Name: communications_channel_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX communications_channel_type_index ON public.communications USING btree (channel_type);


--
-- Name: communications_direction_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX communications_direction_index ON public.communications USING btree (direction);


--
-- Name: communications_external_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX communications_external_id_index ON public.communications USING btree (external_id);


--
-- Name: communications_received_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX communications_received_at_index ON public.communications USING btree (received_at);


--
-- Name: communications_reference_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX communications_reference_code_index ON public.communications USING btree (reference_code);


--
-- Name: communications_sent_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX communications_sent_at_index ON public.communications USING btree (sent_at);


--
-- Name: communications_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX communications_status_index ON public.communications USING btree (status);


--
-- Name: communications_thread_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX communications_thread_id_index ON public.communications USING btree (thread_id);


--
-- Name: contact_list_members_contact_list_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contact_list_members_contact_list_id_index ON public.contact_list_members USING btree (contact_list_id);


--
-- Name: contact_lists_created_by_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contact_lists_created_by_index ON public.contact_lists USING btree (created_by);


--
-- Name: contacts_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_email_index ON public.contacts USING btree (email);


--
-- Name: contacts_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_is_active_index ON public.contacts USING btree (is_active);


--
-- Name: contacts_rut_empleador_dv_empleador_producto_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_rut_empleador_dv_empleador_producto_index ON public.contacts USING btree (rut_empleador, dv_empleador, producto);


--
-- Name: email_attachments_imported_email_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_attachments_imported_email_id_index ON public.email_attachments USING btree (imported_email_id);


--
-- Name: email_campaigns_created_by_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_campaigns_created_by_index ON public.email_campaigns USING btree (created_by);


--
-- Name: email_campaigns_status_scheduled_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_campaigns_status_scheduled_at_index ON public.email_campaigns USING btree (status, scheduled_at);


--
-- Name: gmail_groups_is_active_is_generic_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX gmail_groups_is_active_is_generic_index ON public.gmail_groups USING btree (is_active, is_generic);


--
-- Name: gmail_metadata_communication_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX gmail_metadata_communication_id_index ON public.gmail_metadata USING btree (communication_id);


--
-- Name: gmail_metadata_gmail_message_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX gmail_metadata_gmail_message_id_index ON public.gmail_metadata USING btree (gmail_message_id);


--
-- Name: gmail_metadata_gmail_thread_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX gmail_metadata_gmail_thread_id_index ON public.gmail_metadata USING btree (gmail_thread_id);


--
-- Name: gmail_metadata_is_backed_up_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX gmail_metadata_is_backed_up_index ON public.gmail_metadata USING btree (is_backed_up);


--
-- Name: gmail_metadata_last_sync_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX gmail_metadata_last_sync_at_index ON public.gmail_metadata USING btree (last_sync_at);


--
-- Name: gmail_metadata_sync_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX gmail_metadata_sync_status_index ON public.gmail_metadata USING btree (sync_status);


--
-- Name: imported_emails_assigned_to_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX imported_emails_assigned_to_index ON public.imported_emails USING btree (assigned_to);


--
-- Name: imported_emails_case_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX imported_emails_case_status_index ON public.imported_emails USING btree (case_status);


--
-- Name: imported_emails_gmail_message_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX imported_emails_gmail_message_id_index ON public.imported_emails USING btree (gmail_message_id);


--
-- Name: imported_emails_gmail_thread_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX imported_emails_gmail_thread_id_index ON public.imported_emails USING btree (gmail_thread_id);


--
-- Name: imported_emails_imported_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX imported_emails_imported_at_index ON public.imported_emails USING btree (imported_at);


--
-- Name: imported_emails_received_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX imported_emails_received_at_index ON public.imported_emails USING btree (received_at);


--
-- Name: imported_emails_rut_empleador_dv_empleador_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX imported_emails_rut_empleador_dv_empleador_index ON public.imported_emails USING btree (rut_empleador, dv_empleador);


--
-- Name: imported_emails_to_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX imported_emails_to_email_index ON public.imported_emails USING btree (to_email);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: oauth_tokens_provider_identifier_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX oauth_tokens_provider_identifier_index ON public.oauth_tokens USING btree (provider, identifier);


--
-- Name: oauth_tokens_provider_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX oauth_tokens_provider_is_active_index ON public.oauth_tokens USING btree (provider, is_active);


--
-- Name: outbox_attachments_outbox_email_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX outbox_attachments_outbox_email_id_index ON public.outbox_attachments USING btree (outbox_email_id);


--
-- Name: outbox_emails_created_by_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX outbox_emails_created_by_index ON public.outbox_emails USING btree (created_by);


--
-- Name: outbox_emails_imported_email_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX outbox_emails_imported_email_id_index ON public.outbox_emails USING btree (imported_email_id);


--
-- Name: outbox_emails_scheduled_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX outbox_emails_scheduled_at_index ON public.outbox_emails USING btree (scheduled_at);


--
-- Name: outbox_emails_send_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX outbox_emails_send_status_index ON public.outbox_emails USING btree (send_status);


--
-- Name: phone_communications_call_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX phone_communications_call_status_index ON public.phone_communications USING btree (call_status);


--
-- Name: phone_communications_call_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX phone_communications_call_type_index ON public.phone_communications USING btree (call_type);


--
-- Name: phone_communications_communication_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX phone_communications_communication_id_index ON public.phone_communications USING btree (communication_id);


--
-- Name: phone_communications_follow_up_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX phone_communications_follow_up_date_index ON public.phone_communications USING btree (follow_up_date);


--
-- Name: phone_communications_follow_up_required_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX phone_communications_follow_up_required_index ON public.phone_communications USING btree (follow_up_required);


--
-- Name: phone_communications_phone_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX phone_communications_phone_number_index ON public.phone_communications USING btree (phone_number);


--
-- Name: reference_codes_assigned_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reference_codes_assigned_user_id_index ON public.reference_codes USING btree (assigned_user_id);


--
-- Name: reference_codes_case_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reference_codes_case_id_index ON public.reference_codes USING btree (case_id);


--
-- Name: reference_codes_channel_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reference_codes_channel_type_index ON public.reference_codes USING btree (channel_type);


--
-- Name: reference_codes_code_hash_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reference_codes_code_hash_index ON public.reference_codes USING btree (code_hash);


--
-- Name: reference_codes_last_used_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reference_codes_last_used_at_index ON public.reference_codes USING btree (last_used_at);


--
-- Name: reference_codes_usage_count_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reference_codes_usage_count_index ON public.reference_codes USING btree (usage_count);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: system_config_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_config_key_index ON public.system_config USING btree (key);


--
-- Name: user_roles_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_roles_user_id_index ON public.user_roles USING btree (user_id);


--
-- Name: users_nickname_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_nickname_index ON public.users USING btree (nickname);


--
-- Name: campaign_sends campaign_sends_campaign_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaign_sends
    ADD CONSTRAINT campaign_sends_campaign_id_foreign FOREIGN KEY (campaign_id) REFERENCES public.email_campaigns(id) ON DELETE CASCADE;


--
-- Name: campaign_sends campaign_sends_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaign_sends
    ADD CONSTRAINT campaign_sends_contact_id_foreign FOREIGN KEY (contact_id) REFERENCES public.contacts(id) ON DELETE CASCADE;


--
-- Name: case_metrics case_metrics_case_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.case_metrics
    ADD CONSTRAINT case_metrics_case_id_foreign FOREIGN KEY (case_id) REFERENCES public.cases(id) ON DELETE CASCADE;


--
-- Name: cases cases_assigned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cases
    ADD CONSTRAINT cases_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: cases cases_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cases
    ADD CONSTRAINT cases_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: communications communications_case_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.communications
    ADD CONSTRAINT communications_case_id_foreign FOREIGN KEY (case_id) REFERENCES public.cases(id) ON DELETE CASCADE;


--
-- Name: communications communications_in_reply_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.communications
    ADD CONSTRAINT communications_in_reply_to_foreign FOREIGN KEY (in_reply_to) REFERENCES public.communications(id) ON DELETE SET NULL;


--
-- Name: communications communications_processed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.communications
    ADD CONSTRAINT communications_processed_by_foreign FOREIGN KEY (processed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contact_list_members contact_list_members_contact_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_list_members
    ADD CONSTRAINT contact_list_members_contact_id_foreign FOREIGN KEY (contact_id) REFERENCES public.contacts(id) ON DELETE CASCADE;


--
-- Name: contact_list_members contact_list_members_contact_list_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_list_members
    ADD CONSTRAINT contact_list_members_contact_list_id_foreign FOREIGN KEY (contact_list_id) REFERENCES public.contact_lists(id) ON DELETE CASCADE;


--
-- Name: contact_lists contact_lists_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_lists
    ADD CONSTRAINT contact_lists_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: email_attachments email_attachments_imported_email_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_attachments
    ADD CONSTRAINT email_attachments_imported_email_id_foreign FOREIGN KEY (imported_email_id) REFERENCES public.imported_emails(id) ON DELETE CASCADE;


--
-- Name: email_campaigns email_campaigns_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_campaigns
    ADD CONSTRAINT email_campaigns_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: gmail_groups gmail_groups_assigned_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gmail_groups
    ADD CONSTRAINT gmail_groups_assigned_user_id_foreign FOREIGN KEY (assigned_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: gmail_metadata gmail_metadata_communication_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gmail_metadata
    ADD CONSTRAINT gmail_metadata_communication_id_foreign FOREIGN KEY (communication_id) REFERENCES public.communications(id) ON DELETE CASCADE;


--
-- Name: imported_emails imported_emails_assigned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.imported_emails
    ADD CONSTRAINT imported_emails_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: imported_emails imported_emails_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.imported_emails
    ADD CONSTRAINT imported_emails_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: imported_emails imported_emails_gmail_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.imported_emails
    ADD CONSTRAINT imported_emails_gmail_group_id_foreign FOREIGN KEY (gmail_group_id) REFERENCES public.gmail_groups(id) ON DELETE CASCADE;


--
-- Name: imported_emails imported_emails_reference_code_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.imported_emails
    ADD CONSTRAINT imported_emails_reference_code_id_foreign FOREIGN KEY (reference_code_id) REFERENCES public.reference_codes(id) ON DELETE SET NULL;


--
-- Name: imported_emails imported_emails_spam_marked_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.imported_emails
    ADD CONSTRAINT imported_emails_spam_marked_by_foreign FOREIGN KEY (spam_marked_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: outbox_attachments outbox_attachments_outbox_email_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.outbox_attachments
    ADD CONSTRAINT outbox_attachments_outbox_email_id_foreign FOREIGN KEY (outbox_email_id) REFERENCES public.outbox_emails(id) ON DELETE CASCADE;


--
-- Name: outbox_emails outbox_emails_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.outbox_emails
    ADD CONSTRAINT outbox_emails_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: outbox_emails outbox_emails_imported_email_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.outbox_emails
    ADD CONSTRAINT outbox_emails_imported_email_id_foreign FOREIGN KEY (imported_email_id) REFERENCES public.imported_emails(id) ON DELETE SET NULL;


--
-- Name: phone_communications phone_communications_communication_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.phone_communications
    ADD CONSTRAINT phone_communications_communication_id_foreign FOREIGN KEY (communication_id) REFERENCES public.communications(id) ON DELETE CASCADE;


--
-- Name: reference_codes reference_codes_assigned_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reference_codes
    ADD CONSTRAINT reference_codes_assigned_user_id_foreign FOREIGN KEY (assigned_user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: reference_codes reference_codes_case_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reference_codes
    ADD CONSTRAINT reference_codes_case_id_foreign FOREIGN KEY (case_id) REFERENCES public.cases(id) ON DELETE SET NULL;


--
-- Name: user_roles user_roles_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict SSbzrBA0C1H4RRUFbHK0SdiD8SOjzLzogfoje8METYCzC7s7cxMk1pildvDW2f9

