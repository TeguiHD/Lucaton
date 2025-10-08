# Repository Guidelines

## Project Structure & Module Organization
- `app/Controllers`, `app/Models`, `app/Services` hold PHP domain logic; favor existing helpers before adding new classes.
- Public views live in `views/public`; admin and user dashboards live in `views/admin` and `views/user`; extend `views/layouts/base.php` to inherit navigation and meta tags.
- Styles start in `assets/input.css` and Tailwind components, then compile into `public/assets/css`; commit only generated files needed for deployment.
- Database migrations reside in `database/migrations` (run with `php database/migrations/run_migrations.php`); `storage/` separates cache, logs, and AI assets—do not mix user uploads with code.

## Build, Test & Development Commands
- `pnpm install` instala el tooling de Node y la canalización de Tailwind.
- `pnpm run build-css` produce estilos minificados; `pnpm run watch-css` recarga durante el desarrollo local.
- `php -S 127.0.0.1:8000 -t public` sirve la app localmente para QA manual.
- `./vendor/bin/phpunit --testsuite unit` y `--testsuite integration` ejecutan los suites correspondientes; deben pasar antes de hacer push.

## Coding Style & Naming Conventions
- Sigue PSR-12: indentación de cuatro espacios, llaves en nueva línea y espacios después de comas.
- Controladores, modelos, servicios y helpers usan PascalCase (`CampaignController`); vistas permanecen en snake-case (`campaign-summary.php`).
- Prefiere propiedades tipadas, comparaciones estrictas y docblocks concisos para métodos no triviales.

## Testing Guidelines
- Ubica pruebas unitarias en `tests/unit` e integraciones en `tests/integration`; los archivos terminan en `*Test.php`.
- Cubre flujos críticos (ciclo de campañas, servicios de IA, donaciones) y escenarios negativos como fallas de permisos o límites de cuotas.
- Si agregas fixtures, aíslalas en `tests/fixtures` para evitar fugas de datos.

## Commit & Pull Request Guidelines
- Escribe commits en imperativo (a menudo en español) y, cuando ayude, agrega un prefijo de ámbito (`docs:`, `feat:`, etc.).
- Referencia los requisitos relevantes en `requerimientos/` dentro de la descripción del commit o del PR.
- Adjunta evidencia visual en `capturasPantalla/` cuando toques plantillas o estilos y explica cualquier cambio de configuración o migración.
- Actualiza `docs/` y `.env.example` al introducir nuevas claves; nunca subas archivos `.env` reales.

## Security & Configuration Tips
- Encapsula el acceso a archivos privilegiados mediante servicios existentes para mantener `storage/` privado.
- Valida y desinfecta entradas externas antes de pasarlas a servicios de IA o campañas.
- Revisa los headers CSP y los permisos de `/file/ai/{id}` antes de cada despliegue.
