<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de seguridad - Lucatón</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f6f8;
            color: #1f2937;
        }
        .wrapper {
            width: 100%;
            background-color: #f5f6f8;
            padding: 40px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 35px rgba(15, 23, 42, 0.08);
        }
        .header {
            background: linear-gradient(135deg, #155e75, #0f172a);
            padding: 28px 32px;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .content {
            padding: 32px;
        }
        .content p {
            font-size: 15px;
            line-height: 1.6;
            margin: 0 0 16px;
        }
        .code-box {
            text-align: center;
            margin: 32px 0;
            padding: 24px;
            border-radius: 12px;
            background-color: #f1f5f9;
            border: 1px solid #d7e0eb;
        }
        .code-box span {
            display: inline-block;
            font-size: 32px;
            letter-spacing: 12px;
            font-weight: 700;
            color: #0f172a;
        }
        .footer {
            padding: 0 32px 32px;
            font-size: 12px;
            color: #64748b;
            line-height: 1.5;
        }
        .footer a {
            color: #0f766e;
            text-decoration: none;
        }
        .cta {
            display: inline-block;
            padding: 12px 20px;
            background-color: #0f766e;
            color: #ffffff;
            border-radius: 9999px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 12px;
        }
        @media (max-width: 640px) {
            .content, .footer { padding: 24px; }
            .code-box span { font-size: 28px; letter-spacing: 10px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>Tu código de seguridad</h1>
            </div>
            <div class="content">
                <p>Hola <strong>{{user_name}}</strong>,</p>
                <p>Recibimos una solicitud para actualizar la contraseña de tu cuenta en Lucatón (prototipo académico). Para confirmar que fuiste tú, ingresa el siguiente código en la pantalla de verificación:</p>
                <div class="code-box">
                    <span>{{code}}</span>
                </div>
                <p>El código vence en <strong>10 minutos</strong>. Si tú no realizaste este cambio, ignora este mensaje y comunícate con nuestro equipo de seguridad.</p>
                <p>Gracias por mantener tu cuenta protegida,<br>Equipo académico de Lucatón</p>
                <a href="{{support_url}}" class="cta">Contactar soporte</a>
            </div>
            <div class="footer">
                <p>Este correo se envió a {{user_email}} porque forma parte del prototipo académico Lucatón. Si no solicitaste este cambio, escríbenos a <a href="mailto:<?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>"><?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?></a>.</p>
                <p><?= htmlspecialchars(PROJECT_OWNER_NAME) ?><br><?= htmlspecialchars(PROJECT_DISCLAIMER) ?></p>
            </div>
        </div>
    </div>
</body>
</html>
