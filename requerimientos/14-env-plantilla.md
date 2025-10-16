## Plantilla .env

```
APP_ENV=local
BASE_URL=http://localhost:8000
DB_HOST=localhost
DB_NAME=lucaton
DB_USER=lucaton_app
DB_PASS=secret
UPLOAD_DIR=storage/uploads
LOG_DIR=storage/logs
UPLOAD_DIR_PRIVATE=../storage/private
UPLOAD_DIR_PUBLIC=public/storage

# IA (opcional, habilita funciones)
OPENROUTER_API_KEY=tu_api_key_openrouter
OPENROUTER_BASE_URL=https://openrouter.ai/api/v1
OPENROUTER_MODEL=tngtech/deepseek-r1t2-chimera:free
GOOGLE_AI_API_KEYS=clave_google_1,clave_google_2,clave_google_3
GOOGLE_AI_TEXT_MODEL=gemini-1.5-flash
GOOGLE_AI_API_BASE_URL=https://generativelanguage.googleapis.com/v1
GOOGLE_AI_USE_THINKING=false
GOOGLE_AI_THINKING_BUDGET=0
GEMINI_API_KEY=tu_api_key_gemini
GEMINI_IMAGE_MODEL=models/gemini-2.5-flash-image-preview

# Límites y seguridad
AI_MAX_REQ_PER_HOUR=10
LOGIN_MAX_ATTEMPTS=5
COOKIE_SECURE=false
CSP_ENABLE=true
APP_TIMEZONE=America/Santiago
```
