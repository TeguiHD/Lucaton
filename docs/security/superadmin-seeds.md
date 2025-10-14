# Protección de promociones automáticas a Superadmin

Este documento explica cómo configurar el mecanismo seguro para otorgar
el rol `superadmin` sin exponer correos electrónicos sensibles en el repositorio.

## Variables de entorno

Configura las siguientes claves en `.env` (ver ejemplos en `.env.example`):

```
APP_SUPERADMIN_AUTO_PROMOTE=true            # Habilita el chequeo automático
SUPERADMIN_SEEDS_ALGO=sha256                # Algoritmo soportado por hash_algos()
SUPERADMIN_SEEDS_SECRET=clave_unica_larga   # Recomendado: 32+ bytes aleatorios
SUPERADMIN_SEEDS_HASHED=hash1,hash2         # Lista separada por comas
```

- `SUPERADMIN_SEEDS_SECRET` añade un factor secreto al hash. Mantén este valor
  fuera del control de versiones y cámbialo si sospechas de filtración.
- `SUPERADMIN_SEEDS_HASHED` debe contener hashes en minúsculas generados con el
  algoritmo y secreto configurados.

## Generar un hash autorizado

Utiliza el script de soporte:

```bash
php scripts/generate_superadmin_seed.php correo@ejemplo.com
```

El comando imprime el hash que debes copiar en `SUPERADMIN_SEEDS_HASHED`.
Puedes añadir múltiples hashes separándolos con comas.

## Flujo de promoción

1. La cuenta se registra o inicia sesión.
2. `User::authenticate()` valida credenciales y, si `APP_SUPERADMIN_AUTO_PROMOTE`
   está activo, compara el correo normalizado con los hashes configurados.
3. Si hay coincidencia, se actualizan `role_id`/`role_signature` y se registra
   un evento `superadmin_auto_promote` en los logs de auditoría.
4. Si la actualización de base de datos falla, se registra un warning y **no**
   se eleva el rol.

## Buenas prácticas

- Deshabilita `APP_SUPERADMIN_AUTO_PROMOTE` en producción una vez creadas las
  cuentas iniciales y gestiona ascensos posteriores desde un panel admin seguro
  o un comando CLI controlado.
- Reemplaza hashes consumidos para evitar su reutilización indefinida.
- Audita periódicamente los logs de `superadmin_auto_promote` y revoca
  credenciales que no correspondan.
