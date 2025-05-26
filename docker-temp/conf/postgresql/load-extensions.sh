#!/usr/bin/env bash

echo "enabling pg_trgm on database $POSTGRES_DB"
psql -U $POSTGRES_USER --dbname="$POSTGRES_DB" <<-'EOSQL'
  create extension if not exists pg_trgm;
EOSQL
echo "finished with exit code $?"
