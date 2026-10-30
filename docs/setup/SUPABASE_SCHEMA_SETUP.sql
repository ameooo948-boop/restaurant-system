-- Run this in Supabase SQL Editor before running Laravel migrations.
-- Keep this schema out of the Data API's exposed schemas.

CREATE SCHEMA IF NOT EXISTS restaurant_app;

REVOKE ALL ON SCHEMA restaurant_app FROM PUBLIC;
REVOKE ALL ON SCHEMA restaurant_app FROM anon, authenticated;

GRANT USAGE, CREATE ON SCHEMA restaurant_app TO postgres;
