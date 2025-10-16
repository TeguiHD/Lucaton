# 🚀 Guía de Deployment - Lucatón

Esta guía te ayudará a subir tu aplicación Lucatón al servidor de producción de manera sencilla.

## 📋 Pasos Previos (En tu computadora)

### 1. Compilar CSS
```bash
pnpm run build-css
```
Este comando genera el archivo CSS optimizado en `public/assets/css/app.css`

### 2. Verificar que todo funcione localmente
- Prueba en `localhost:8000` o `localhost/Tesis`
- Verifica que no haya errores en la consola
- Comprueba que todas las funcionalidades trabajen correctamente

## 📁 Archivos a Subir al Servidor

### Subir TODOS estos archivos/carpetas a `public_html`:
```
├── app/                    # Lógica de la aplicación
├── assets/                 # CSS fuente (opcional)
├── config/                 # Configuración
├── database/              # Migraciones y seeds
├── public/                # Archivos públicos (CSS, JS, imágenes)
├── storage/               # Archivos de almacenamiento
├── views/                 # Plantillas HTML
├── .htaccess             # Configuración Apache (IMPORTANTE)
├── index.php             # Punto de entrada
└── tailwind.config.js    # Configuración Tailwind (opcional)
```

### ❌ NO subir estos archivos:
```
├── .env                  # Crear nuevo en servidor
├── node_modules/         # No necesario en producción
├── pnpm-lock.yaml       # No necesario en producción
├── package.json         # No necesario en producción
```

## ⚙️ Configuración en el Servidor

### 1. Crear archivo `.env` en el servidor
Copia `.env.example` y renómbralo a `.env`, luego configura:

```env
# === CONFIGURACIÓN DE BASE DE DATOS ===
DB_HOST=localhost                    # O la IP de tu servidor MySQL
DB_PORT=3306
DB_NAME=tu_base_datos_servidor      # Nombre de tu BD en el servidor
DB_USER=tu_usuario_servidor         # Usuario de BD del servidor
DB_PASS=tu_password_servidor        # Password de BD del servidor
DB_CHARSET=utf8mb4

# === CONFIGURACIÓN DE APLICACIÓN ===
APP_NAME="Lucatón - Crowdfunding Social"
APP_ENV=production                   # CAMBIAR A PRODUCTION
APP_DEBUG=false                      # CAMBIAR A FALSE
APP_URL=https://tudominio.com        # TU DOMINIO REAL
APP_TIMEZONE=America/Santiago

# === CONFIGURACIÓN DE IA ===
OPENROUTER_API_KEY=tu_api_key_openrouter_real    # API key real de OpenRouter
OPENROUTER_MODEL=tngtech/deepseek-r1t2-chimera:free
GOOGLE_AI_API_KEYS=clave_google_1,clave_google_2,clave_google_3
GEMINI_API_KEY=tu_gemini_key_real    # API key real de Google Gemini (imágenes)
```

Si necesitas otorgar rol `superadmin` a cuentas iniciales sin exponer correos en el repositorio, configura las variables:

```env
APP_SUPERADMIN_AUTO_PROMOTE=true
SUPERADMIN_SEEDS_ALGO=sha256
SUPERADMIN_SEEDS_SECRET=clave_secreta_larga
SUPERADMIN_SEEDS_HASHED=hash_generado
```

Genera cada hash con `php scripts/generate_superadmin_seed.php correo@ejemplo.com`
siguiendo la guía `docs/security/superadmin-seeds.md`.

### 2. Configurar Base de Datos

#### Opción A: Exportar/Importar BD completa
1. **En tu computadora (phpMyAdmin local):**
   - Ve a tu base de datos `lucaton_db`
   - Clic en "Exportar"
   - Selecciona "Método personalizado"
   - Marca "Agregar declaración CREATE DATABASE"
   - Descarga el archivo `.sql`

2. **En tu servidor (phpMyAdmin del hosting):**
   - Crea una nueva base de datos
   - Importa el archivo `.sql` descargado

#### Opción B: Solo estructura y datos esenciales
1. **Ejecutar migraciones en el servidor:**
   - Sube la carpeta `database/migrations/`
   - Accede a `tudominio.com/database/migrations/run_migrations.php`

2. **Agregar datos de prueba (opcional):**
   - Accede a `tudominio.com/database/seeds/seed_demo_content.php`

### 3. Configurar Permisos de Carpetas
Asegúrate de que estas carpetas tengan permisos de escritura (755 o 777):
```
storage/
storage/logs/
storage/cache/
storage/ai_files/
storage/private/
public/storage/uploads/
```

## 🔧 Configuración del Servidor Web

### Si tu servidor usa Apache (más común):
- El archivo `.htaccess` ya está configurado
- Asegúrate de que `mod_rewrite` esté habilitado

### Si tu servidor usa Nginx:
Necesitarás configurar las reglas de reescritura (contacta a tu proveedor de hosting)

## ✅ Verificación Final

### 1. Probar la aplicación
- Visita `https://tudominio.com`
- Verifica que cargue la página principal
- Prueba el registro/login
- Verifica que las imágenes y CSS se carguen correctamente

### 2. Revisar logs de errores
- Revisa `storage/logs/` para errores
- Consulta los logs del servidor si hay problemas

## 🚨 Solución de Problemas Comunes

### Error 500 - Internal Server Error
1. Verifica permisos de carpetas
2. Revisa el archivo `.env`
3. Consulta `storage/logs/error-[fecha].log`

### CSS no se carga
1. Verifica que `public/assets/css/app.css` exista
2. Comprueba permisos de la carpeta `public/`

### Base de datos no conecta
1. Verifica credenciales en `.env`
2. Confirma que la BD existe en el servidor
3. Verifica que el usuario tenga permisos

### Imágenes no se suben
1. Verifica permisos de `public/storage/uploads/`
2. Comprueba `UPLOAD_MAX_SIZE` en `.env`

## 📞 Contacto de Emergencia

Si tienes problemas:
1. Revisa los logs en `storage/logs/`
2. Verifica la configuración de `.env`
3. Contacta al soporte de tu proveedor de hosting

---

## 🎯 Resumen Rápido

1. `pnpm run build-css`
2. Subir archivos al servidor (excepto `.env`, `node_modules`)
3. Crear `.env` con configuración del servidor
4. Configurar base de datos
5. Verificar permisos de carpetas
6. ¡Probar la aplicación!

**¡Tu aplicación Lucatón estará lista para el mundo! 🌟**
