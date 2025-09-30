# Repository Guidelines

Este documento orienta a quienes extienden la plataforma Lucatón y resume los acuerdos vigentes dentro del repositorio.  
Antes de contribuir, revisa también los módulos relevantes en `/requerimientos/` (seguridad, UI, endpoints, etc.) para asegurar consistencia.

## Project Structure & Module Organization
- `app/Controllers`, `app/Models`, `app/Services` concentran la lógica de negocio PHP; reutiliza helpers existentes antes de crear nuevas clases.
- Las vistas públicas viven en `views/public` y los paneles en `views/admin` y `views/user`; deriva layouts desde `views/layouts/base.php` para heredar navegación y metadatos.
- Recursos fuente de estilo residen en `assets/input.css` y componentes Tailwind; los generados se guardan en `public/assets/css`.
- Migra y consulta esquemas dentro de `database/migrations` (usa `run_migrations.php`), mientras que `storage/` separa caché, logs y archivos IA; respeta esta segregación para permisos.

## Build, Test & Development Commands
| Acción                  | Comando |
|-------------------------|-------------------------------------------|
| Instalar dependencias   | `pnpm install` |
| Compilar CSS producción | `pnpm run build-css` |
| CSS en modo watch       | `pnpm run watch-css` |
| Migraciones BD          | `php database/migrations/run_migrations.php` |
| Servidor local          | `php -S 127.0.0.1:8000 -t public` |
| Tests unitarios         | `./vendor/bin/phpunit --testsuite unit` |
| Tests integración       | `./vendor/bin/phpunit --testsuite integration` |

## Coding Style & Naming Conventions
- Sigue PSR-12: indentación de 4 espacios, llaves en nueva línea para clases y métodos, espacios después de comas.
- Nombra controladores y modelos en PascalCase (`CampaignController`), helpers en PascalCase, vistas en snake-case descriptivo (`campaign-summary.php`).
- Prefiere tipos estrictos y propiedades tipadas; documenta métodos complejos con docblocks concisos.
- Mantén componentes reutilizables en `app/Services` o `views/components` y evita lógica pesada en plantillas.

## Testing Guidelines
- Ubica pruebas unitarias en `tests/unit` y de integración en `tests/integration`; nombra archivos `*Test.php`.
- Ejecuta los suites con PHPUnit 10 (`./vendor/bin/phpunit --testsuite unit`) y agrega un bootstrap propio si tu instalación lo requiere.
- Asegura coberturas básicas para controladores críticos (campañas, IA, donaciones) y valida escenarios negativos (permisos, límites).

## Pull Request Guidelines
Checklist antes de abrir una PR:
- [ ] Descripción clara y breve en imperativo.
- [ ] Referencia a requisitos en `/requerimientos/`.
- [ ] Evidencia visual en `capturasPantalla/` si cambiaste UI.
- [ ] Explicar migraciones o cambios de configuración.
- [ ] Actualizar `docs/` o `.env.example` si añadiste variables.
- [ ] Pasar linters, compilación (`pnpm run build-css`) y PHPUnit.

## Security & Configuration Tips
- Nunca subas `.env` reales; documenta nuevas claves en `.env.example` y `config/bootstrap.php`.
- Minimiza el acceso directo a `storage/`; usa servicios existentes para manejar uploads y sanitiza entradas externas.
- En despliegue, verifica headers CSP, control de acceso a `/file/ai/{id}`, y que archivos privados se sirvan sólo vía endpoints autorizados.