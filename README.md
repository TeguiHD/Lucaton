# Lucatón - Plataforma de Crowdfunding Social con IA

**Autor**: Nicoholas Lopetegui  
**Contacto**: nlopetegui@pregrado.ubo.cl  
**Universidad**: Universidad Bernardo O'Higgins  

Plataforma de crowdfunding social con asistencia de inteligencia artificial para mitigar la brecha digital en Chile. Proyecto de tesis que combina propósito social, tecnología robusta y rigor académico.

## 🎯 Características Principales

- **IA Ética**: Asistencia para creación de campañas con transparencia obligatoria
- **Seguridad Robusta**: Implementación OWASP, CSP, rate limiting
- **Mobile-First**: Diseño responsive con paleta chilena
- **Accesibilidad**: Cumplimiento WCAG AA
- **Investigación**: Métricas académicas y evaluación A/B

## 🏗️ Arquitectura Técnica

### Stack Principal
- **Backend**: PHP 8.2+ con Apache, MariaDB 10.6+
- **Frontend**: HTML5/JS vanilla, Tailwind CSS
- **IA**: OpenAI (texto) + Gemini (imagen) server-side
- **Seguridad**: CSP, CSRF, Argon2id, headers seguros

### Estructura de Carpetas

```
Tesis/
├── 📁 app/                     # Lógica de aplicación
│   ├── 📁 Controllers/         # Controladores MVC
│   ├── 📁 Models/             # Modelos de datos
│   ├── 📁 Middleware/         # Middleware de autenticación/autorización
│   ├── 📁 Services/           # Servicios (IA, uploads, etc.)
│   ├── 📁 Helpers/            # Utilidades (Router, Session, etc.)
│   └── 📁 Views/              # Vistas de la aplicación
├── 📁 assets/                 # Archivos CSS de entrada
│   └── input.css              # CSS de Tailwind sin compilar
├── 📁 capturasPantalla/       # Capturas de pantalla del proyecto
├── 📁 config/                 # Configuraciones
│   ├── bootstrap.php          # Carga de configuración y autoloader
│   └── routes.php             # Definición de rutas
├── 📁 database/               # Base de datos
│   ├── 📁 migrations/         # Scripts de migración SQL
│   └── 📁 seeds/              # Datos de prueba
├── 📁 docs/                   # Documentación
│   ├── 📁 api/                # Documentación de API
│   ├── 📁 architecture/       # Diagramas y arquitectura
│   └── 📁 database/           # Documentación de BD
├── 📁 public/                 # Archivos públicos accesibles
│   ├── 📁 assets/             # CSS, JS, imágenes estáticas
│   │   ├── 📁 css/           # Tailwind compilado
│   │   ├── 📁 js/            # JavaScript vanilla
│   │   └── 📁 images/        # Imágenes del sitio
│   ├── 📁 storage/           # Uploads públicos
│   │   ├── 📁 avatars/       # Avatares de usuarios
│   │   └── 📁 uploads/       # Archivos subidos por usuarios
│   ├── .htaccess             # Configuración Apache para public
│   └── index.php             # Punto de entrada público
├── 📁 requerimientos/         # Especificaciones del proyecto
├── 📁 scripts/                # Scripts de utilidad
│   └── clean-css.js           # Script de limpieza CSS
├── 📁 storage/                # Archivos privados
│   ├── 📁 ai_files/          # Archivos generados por IA
│   ├── 📁 cache/             # Cache temporal
│   ├── 📁 logs/              # Logs de aplicación
│   └── 📁 private/           # Archivos no accesibles vía web
├── 📁 tests/                  # Pruebas automatizadas
│   ├── 📁 integration/        # Pruebas de integración
│   └── 📁 unit/              # Pruebas unitarias
├── 📁 views/                  # Plantillas PHP
│   ├── 📁 admin/             # Vistas de administración
│   ├── 📁 auth/              # Vistas de autenticación
│   ├── 📁 components/        # Componentes reutilizables
│   ├── 📁 emails/            # Plantillas de email
│   ├── 📁 errors/            # Páginas de error
│   ├── 📁 layouts/           # Layouts base
│   ├── 📁 pages/             # Páginas principales
│   ├── 📁 public/            # Vistas públicas
│   └── 📁 user/              # Vistas de usuario
├── 📄 .env.example            # Plantilla de variables de entorno
├── 📄 .gitignore              # Archivos ignorados por Git
├── 📄 .htaccess               # Configuración Apache principal
├── 📄 composer.json           # Dependencias PHP
├── 📄 composer.lock           # Lock de dependencias PHP
├── 📄 index.php               # Punto de entrada principal
├── 📄 package.json            # Dependencias Node.js
├── 📄 phpunit.xml             # Configuración PHPUnit
├── 📄 pnpm-lock.yaml          # Lock de dependencias pnpm
├── 📄 tailwind.config.js      # Configuración Tailwind CSS
└── 📄 README.md               # Este archivo
```

## 🚀 Instalación y Configuración

### Prerrequisitos
- PHP 8.2+
- Apache con mod_rewrite y mod_headers
- MariaDB 10.6+
- Node.js 18+ (para Tailwind CSS)
- pnpm (gestor de paquetes)

### Pasos de Instalación

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/TeguiHD/Lucaton.git Tesis
   cd Tesis
   ```

2. **Configurar variables de entorno**
   ```bash
   cp .env.example .env
   # Editar .env con tus configuraciones
   ```

3. **Instalar dependencias de Tailwind**
   ```bash
   pnpm install
   ```

4. **Compilar CSS**
   ```bash
   pnpm run build-css
   # o para desarrollo:
   pnpm run watch-css
   ```

5. **Configurar base de datos**
   ```bash
   mysql -u root -p < database/migrations/schema.sql
   ```

6. **Configurar permisos**
   ```bash
   chmod 755 storage/
   chmod 755 storage/private/
   chmod 755 storage/logs/
   chmod 755 public/storage/uploads/
   ```

### Variables de Entorno Importantes

```env
# Base de datos
DB_HOST=localhost
DB_NAME=lucaton_db
DB_USER=tu_usuario
DB_PASS=tu_password

# IA (requeridas para funcionalidad completa)
OPENAI_API_KEY=tu_openai_key
GEMINI_API_KEY=tu_gemini_key

# Seguridad
RATE_LIMIT_LOGIN=5
RATE_LIMIT_AI_REQUESTS=10
SESSION_LIFETIME=7200
```

## 📋 Funcionalidades

### Públicas
- ✅ Página de inicio con campañas destacadas
- ✅ Listado de campañas con filtros y paginación
- ✅ Detalle de campañas con información completa y evidencias
- ✅ FAQ con preguntas frecuentes y búsqueda
- ✅ Términos de servicio y políticas de privacidad
- ✅ Página de contacto con formulario
- ✅ Centro de ayuda y soporte
- ✅ Código de conducta y reportar problemas

### Autenticación
- ✅ Registro de usuarios con verificación de email
- ✅ Login seguro con validación y recuperación de contraseña
- ✅ Logout con limpieza de sesión
- ✅ Recuperación de contraseña con tokens seguros
- ✅ Sesiones con timeout y regeneración de ID
- ✅ Rate limiting para prevenir ataques de fuerza bruta
- ✅ Middleware de autenticación para rutas protegidas

### Usuario Autenticado
- ✅ Dashboard personal con resumen de campañas y actividad
- ✅ Panel "Mis campañas" con gestión completa (CRUD)
- ✅ Crear campañas con asistencia de IA opcional
- ✅ Editar campañas en estado borrador
- ✅ Perfil de usuario con edición de datos y avatar
- ✅ Sistema de apelaciones para campañas rechazadas
- ✅ Historial de actividad y notificaciones
- ✅ Configuración de cuenta y preferencias

### Administración
- ✅ Dashboard administrativo con métricas del sistema
- ✅ Moderación de campañas (aprobar, rechazar, comentar)
- ✅ Gestión completa de usuarios (ver, editar, suspender, eliminar)
- ✅ Sistema de reportes y estadísticas avanzadas
- ✅ Logs de auditoría y actividad del sistema
- ✅ Configuración de parámetros del sistema
- ✅ Herramientas de moderación de contenido
- ✅ Gestión de noticias y notificaciones

### IA y Archivos
- ✅ Integración OpenAI para generación de texto ético
- ✅ Integración Gemini para análisis de imágenes
- ✅ Generación de títulos y descripciones persuasivas
- ✅ Análisis y moderación automática de contenido
- ✅ Transparencia obligatoria en contenido asistido por IA
- ✅ Sistema de archivos privados y públicos
- ✅ Upload seguro con validación de tipos y tamaños
- ✅ Rate limiting específico para requests de IA

## 🔒 Seguridad

### Medidas Implementadas
- ✅ **Autenticación robusta**: Sesiones seguras con regeneración de ID y timeout
- ✅ **Validación de entrada**: Sanitización y validación de todos los datos de usuario
- ✅ **Protección CSRF**: Tokens CSRF en todos los formularios
- ✅ **Rate Limiting**: Límites de requests por IP y usuario
- ✅ **Encriptación**: Contraseñas hasheadas con bcrypt y datos sensibles encriptados
- ✅ **Middleware de seguridad**: Headers de seguridad y protección XSS
- ✅ **Validación de archivos**: Tipos permitidos, tamaños máximos y escaneo de malware
- ✅ **Logs de auditoría**: Registro completo de actividades críticas
- ✅ **Configuración segura**: Variables de entorno y configuración de producción
- ✅ **Protección de rutas**: Middleware de autenticación y autorización
- ✅ **Sanitización de contenido**: Limpieza de HTML y prevención de XSS
- ✅ **Gestión de sesiones**: Timeout automático y limpieza de sesiones expiradas

### Archivos Protegidos
- ✅ `/config/` - Configuraciones del sistema
- ✅ `/logs/` - Archivos de registro
- ✅ `/uploads/private/` - Archivos privados de usuarios
- ✅ `/database/` - Esquemas y migraciones
- ✅ `.env` - Variables de entorno sensibles
- ✅ `/admin/` - Panel administrativo con autenticación adicional

## 🧪 Desarrollo y Testing

### Comandos Útiles
```bash
# Desarrollo con Tailwind
pnpm run watch-css

# Build para producción
pnpm run build-css

# Ejecutar tests (cuando estén implementados)
php tests/run.php
```

### Estructura de Desarrollo
- **MVC Pattern**: Separación clara de responsabilidades
- **Middleware**: Autenticación y autorización
- **Services**: Lógica de negocio (IA, uploads)
- **Helpers**: Utilidades reutilizables

## 📊 Investigación Académica

### Hipótesis
1. **Brecha Digital**: IA reduce barreras de creación de campañas
2. **Persuasión Ética**: IA mejora calidad sin manipulación
3. **Transparencia**: Disclosure de IA aumenta confianza

### Métricas
- **Usabilidad**: SUS, UEQ, tiempos de tarea
- **Percepción**: Escalas Likert, autoeficacia
- **Calidad**: Legibilidad, completitud, engagement

### Evaluación
- **Diseño**: A/B testing con 10-15 participantes
- **Control**: Grupo sin asistencia IA
- **Tratamiento**: Grupo con asistencia IA completa

## 🗓️ Roadmap de Desarrollo

### ✅ Semana 1-2: Fundamentos
- ✅ Configuración del entorno de desarrollo
- ✅ Estructura base del proyecto MVC
- ✅ Sistema de autenticación y sesiones
- ✅ Base de datos y modelos principales
- ✅ Middleware de seguridad básico

### ✅ Semana 3-4: Funcionalidades Core
- ✅ CRUD completo de campañas
- ✅ Sistema de usuarios y perfiles
- ✅ Panel administrativo básico
- ✅ Validaciones y sanitización
- ✅ Sistema de archivos y uploads

### ✅ Semana 5-6: Integraciones IA
- ✅ Integración con OpenAI para texto
- ✅ Integración con Gemini para imágenes
- ✅ Sistema de moderación automática
- ✅ Políticas de uso ético de IA
- ✅ Rate limiting para APIs

### 🔄 Semana 7-8: APIs y Endpoints
- 🔄 API REST completa
- 🔄 Documentación de endpoints
- 🔄 Autenticación API con tokens
- 🔄 Versionado de API
- 🔄 Testing de endpoints

### 📋 Semana 9-10: Seguridad Avanzada
- 📋 Implementación completa OWASP Top 10
- 📋 Auditoría de seguridad
- 📋 Penetration testing
- 📋 Configuración de producción
- 📋 Monitoreo y alertas

### 📋 Semana 11-12: Testing y Optimización
- 📋 Testing unitario y de integración
- 📋 Testing de performance
- 📋 Optimización de consultas
- 📋 Caching y CDN
- 📋 Documentación final

## 📞 Contacto

**Autor**: Nicoholas Lopetegui  
**Email**: nlopetegui@pregrado.ubo.cl  
**Universidad**: Universidad Bernardo O'Higgins  

Para preguntas sobre el proyecto:
- **Documentación**: Ver carpeta `requerimientos/`
- **Arquitectura**: Ver `docs/architecture/`
- **API**: Ver `docs/api/`

## 📄 Licencia

Proyecto académico - Tesis de grado  
Universidad Bernardo O'Higgins - 2025