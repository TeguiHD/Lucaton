-- Migración: Actualizar proveedores IA para soportar OpenRouter y Google AI
-- Fecha: 2025-10-15
-- Descripción: Ajusta el ENUM de provider en ai_generations para incluir nuevos servicios

ALTER TABLE ai_generations
    MODIFY provider ENUM(
        'openrouter',
        'google_ai',
        'gemini',
        'stability',
        'anthropic',
        'openai'
    ) NOT NULL DEFAULT 'openrouter';
