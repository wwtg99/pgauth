--app

----------
--tables--
----------

CREATE TABLE public.apps (
  app_id TEXT PRIMARY KEY,
  app_name TEXT NOT NULL UNIQUE,
  descr TEXT,
  app_secret TEXT NOT NULL,
  redirect_uri TEXT,
  params JSONB,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now()
);

-------------
--functions--
-------------

CREATE OR REPLACE FUNCTION public.generate_app_id(in_name TEXT DEFAULT '')
  RETURNS TEXT AS $BODY$
DECLARE
  _now TEXT;
BEGIN
  _now := now()::TEXT;
  RETURN md5(in_name || _now);
END;
$BODY$ LANGUAGE plpgsql
SECURITY DEFINER;

CREATE OR REPLACE FUNCTION public.generate_app_secret(in_name TEXT, in_uri TEXT)
  RETURNS TEXT AS $BODY$
DECLARE
  _now TEXT;
  _rnd TEXT;
  _s TEXT;
BEGIN
  _now := now()::TEXT;
  _rnd := floor(random() * 1000)::TEXT;
  _s := in_name || _now || _rnd;
  IF in_uri IS NOT NULL THEN
    _s := _s || in_uri;
  END IF;
  RETURN md5(_s);
END;
$BODY$ LANGUAGE plpgsql
SECURITY DEFINER;

------------
--triggers--
------------

CREATE OR REPLACE FUNCTION public.tp_change_app() RETURNS TRIGGER AS $BODY$
DECLARE
  _id TEXT;
  _secret TEXT;
  _s BIGINT;
BEGIN
  CASE TG_OP
    WHEN 'INSERT' THEN
      NEW.app_id := generate_app_id(NEW.app_name);
      NEW.app_secret := generate_app_secret(NEW.app_name, NEW.redirect_uri);
      NEW.created_at := now();
      NEW.updated_at := now();
      RETURN NEW;
    WHEN 'UPDATE' THEN
      NEW.app_id := OLD.app_id;
      IF NEW.app_secret != OLD.app_secret THEN
        NEW.app_secret := generate_app_secret(NEW.app_name, NEW.redirect_uri);
      END IF;
      NEW.created_at := OLD.created_at;
      NEW.updated_at := now();
      RETURN NEW;
  END CASE;
END;
$BODY$ LANGUAGE plpgsql
SECURITY DEFINER;

CREATE TRIGGER tg_app BEFORE INSERT OR UPDATE ON public.apps
FOR EACH ROW EXECUTE PROCEDURE public.tp_change_app();
