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
- ✅ Detalle de campañas con información completa
- ✅ FAQ, términos y políticas de privacidad

### Autenticación
- ✅ Registro y login seguros
- ✅ Sesiones con timeout y regeneración de ID
- ✅ Rate limiting para prevenir ataques

### Usuario Autenticado
- ✅ Panel de usuario con campañas propias
- ✅ Crear campañas con asistencia IA
- ✅ Editar campañas en estado borrador
- ✅ Sistema de apelaciones

### Administración
- ✅ Dashboard con estadísticas
- ✅ Moderación de campañas
- ✅ Gestión de usuarios
- ✅ Logs de auditoría

### IA y Archivos
- ✅ Generación de texto ético y persuasivo
- ✅ Generación de imágenes temáticas
- ✅ Moderación automática de contenido
- ✅ Sistema de archivos privados/públicos

## 🔒 Seguridad

### Medidas Implementadas
- **OWASP Compliance**: Protección contra Top 10
- **CSP**: Content Security Policy estricta
- **CSRF**: Tokens en todos los formularios
- **Rate Limiting**: Por sesión y acción
- **Headers Seguros**: X-Frame-Options, nosniff, etc.
- **Uploads Seguros**: Validación de tipos y tamaños
- **Sesiones Robustas**: HTTPOnly, Secure, regeneración

### Archivos Protegidos
- `.env` - Variables de entorno
- `storage/private/` - Archivos privados
- `storage/logs/` - Logs de aplicación
- `config/` - Configuraciones
- `app/` - Código fuente

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

## 🚀 Roadmap de Desarrollo

### Semana 1: Fundación
- [x] Estructura de carpetas
- [x] Configuración básica
- [ ] Sistema de autenticación
- [ ] Base de datos

### Semana 2: Core
- [ ] CRUD de campañas
- [ ] Panel de usuario
- [ ] Sistema de uploads

### Semana 3: IA
- [ ] Integración OpenAI/Gemini
- [ ] Generación de contenido
- [ ] Moderación automática

### Semana 4: Administración
- [ ] Panel de admin
- [ ] Moderación manual
- [ ] Sistema de aprobación

### Semana 5: Seguridad y UX
- [ ] Implementación OWASP
- [ ] Optimización mobile
- [ ] Accesibilidad WCAG

### Semana 6: Evaluación
- [ ] Métricas académicas
- [ ] A/B testing
- [ ] Documentación final

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