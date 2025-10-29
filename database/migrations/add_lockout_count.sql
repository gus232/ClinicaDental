-- Agregar campos para sistema de bloqueo progresivo
-- Fecha: 2025-10-28

-- Agregar campo para contar bloqueos
ALTER TABLE users ADD COLUMN lockout_count INT DEFAULT 0 AFTER failed_login_attempts;

-- Agregar campo para última fecha de bloqueo (para reseteo gradual opcional)
ALTER TABLE users ADD COLUMN last_lockout_date DATETIME NULL AFTER lockout_count;
