DROP VIEW IF EXISTS public.view_user_role, public.view_users, public.view_user_log CASCADE;
DROP FUNCTION IF EXISTS public.get_department_id(TEXT) CASCADE;
DROP FUNCTION IF EXISTS public.get_role_id(TEXT) CASCADE;
DROP FUNCTION IF EXISTS public.get_user_id(TEXT) CASCADE;
DROP FUNCTION IF EXISTS public.add_user_role(TEXT, BIGINT) CASCADE;
DROP FUNCTION IF EXISTS public.delete_user_role(TEXT, BIGINT) CASCADE;
DROP FUNCTION IF EXISTS public.active_user(TEXT) CASCADE;
DROP FUNCTION IF EXISTS public.change_roles(TEXT, JSON) CASCADE;
DROP FUNCTION IF EXISTS public.generate_app_id(TEXT) CASCADE;
DROP FUNCTION IF EXISTS public.generate_app_secret(TEXT, TEXT) CASCADE;
DROP FUNCTION IF EXISTS public.tp_change_department() CASCADE;
DROP FUNCTION IF EXISTS public.tp_change_role() CASCADE;
DROP FUNCTION IF EXISTS public.tp_change_user() CASCADE;
DROP FUNCTION IF EXISTS public.tp_change_app() CASCADE;
DROP TABLE IF EXISTS public.user_log, public.user_role, public.users, public.roles, public.departments, public.apps CASCADE;
DROP SEQUENCE IF EXISTS public.user_id_seq;