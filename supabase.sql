create table if not exists public.countdown_settings (
  id text primary key check (id = 'main'),
  target timestamptz not null
);

alter table public.countdown_settings enable row level security;
