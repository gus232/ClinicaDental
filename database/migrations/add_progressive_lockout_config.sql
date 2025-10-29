-- Configuración para sistema de bloqueo progresivo
-- Fecha: 2025-10-28

INSERT INTO password_policy_config (setting_name, setting_value, description) VALUES
('progressive_lockout_enabled', '1', 'Habilitar bloqueo progresivo (1=sí, 0=no)'),
('lockout_1st_minutes', '30', 'Duración primer bloqueo en minutos'),
('lockout_2nd_minutes', '120', 'Duración segundo bloqueo en minutos (2 horas)'),
('lockout_3rd_minutes', '1440', 'Duración tercer bloqueo en minutos (24 horas)'),
('lockout_permanent_after', '4', 'Número de bloqueos antes del bloqueo permanente'),
('lockout_reset_days', '30', 'Días sin incidentes para resetear contador (0=nunca resetear automáticamente)');
