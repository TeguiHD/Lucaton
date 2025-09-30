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
OPENAI_API_KEY=tu_api_key_openai
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_MODEL=gpt-4o-mini
GEMINI_API_KEY=tu_api_key_gemini
GEMINI_IMAGE_MODEL=models/gemini-2.5-flash-image-preview

# Límites y seguridad
AI_MAX_REQ_PER_HOUR=10
LOGIN_MAX_ATTEMPTS=5
COOKIE_SECURE=false
CSP_ENABLE=true
APP_TIMEZONE=America/Santiago
```
