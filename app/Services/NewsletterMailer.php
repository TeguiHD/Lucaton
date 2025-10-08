<?php

class NewsletterMailer
{
    private bool $mailEnabled;
    private string $previewDirectory;

    public function __construct()
    {
        $this->mailEnabled = $this->resolveMailEnabled();
        $this->previewDirectory = ROOT_PATH . '/storage/logs/mail-previews/newsletter';
    }

    public function send(array $campaign, array $subscriber, array $options = []): array
    {
        $unsubscribeUrl = $options['unsubscribe_url'] ?? '#';
        $templateKey = $campaign['template_key'] ?? 'general_update';
        $subject = $campaign['subject'] ?? 'Novedades Lucatón';

        $content = $this->renderTemplate($templateKey, [
            'subject' => $subject,
            'message' => $campaign['message'] ?? '',
            'cta_label' => $campaign['cta_label'] ?? null,
            'cta_url' => $campaign['cta_url'] ?? null,
            'unsubscribe_url' => $unsubscribeUrl,
            'subscriber' => $subscriber,
        ]);

        $payload = [
            'event_key' => 'newsletter_' . $templateKey,
            'delivery_mode' => $this->mailEnabled ? 'smtp_ready' : 'preview',
            'subject' => $subject,
            'to' => [
                [
                    'email' => $subscriber['email'],
                    'name' => $this->resolveSubscriberName($subscriber)
                ]
            ],
            'body_html' => $content['html'],
            'body_text' => $content['text'],
            'template_key' => $templateKey,
            'meta' => [
                'cta_label' => $campaign['cta_label'] ?? null,
                'cta_url' => $campaign['cta_url'] ?? null,
            ]
        ];

        $previewPath = $this->writePreview($payload, $templateKey);

        Logger::info('Newsletter preparado', [
            'template' => $templateKey,
            'mode' => $payload['delivery_mode'],
            'to' => $subscriber['email'],
            'preview_path' => $previewPath
        ]);

        return [
            'payload' => $payload,
            'preview_path' => $previewPath
        ];
    }

    private function renderTemplate(string $templateKey, array $data): array
    {
        $subject = $data['subject'];
        $message = $data['message'] ?? '';
        $ctaLabel = $data['cta_label'] ?? null;
        $ctaUrl = $data['cta_url'] ?? null;
        $unsubscribeUrl = $data['unsubscribe_url'] ?? '#';
        $subscriberName = $this->resolveSubscriberName($data['subscriber'] ?? []);

        $intro = $this->buildIntro($templateKey, $subscriberName);
        $bodyHtml = $this->formatMessageHtml($message);
        $bodyText = $this->formatMessageText($message);

        $ctaHtml = '';
        $ctaText = '';
        if ($ctaLabel && $ctaUrl) {
            $ctaHtml = sprintf(
                '<p style="text-align:center;margin:30px 0;"><a href="%s" style="display:inline-block;background:#dc2626;color:#fff;padding:12px 24px;border-radius:999px;text-decoration:none;font-weight:600;">%s</a></p>',
                htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($ctaLabel, ENT_QUOTES, 'UTF-8')
            );
            $ctaText = sprintf("%s -> %s\n\n", $ctaLabel, $ctaUrl);
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>{$this->escape($subject)}</title>
</head>
<body style="font-family:'Inter',Arial,sans-serif;background-color:#f8fafc;margin:0;padding:0;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:32px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 20px 35px rgba(15,23,42,0.08);">
          <tr>
            <td style="background:#dc2626;color:#fff;padding:32px 40px;">
              <h1 style="margin:0;font-size:26px;">{$this->escape($subject)}</h1>
              <p style="margin:8px 0 0;font-size:14px;opacity:0.85;">Lucatón · Plataforma de crowdfunding académico</p>
            </td>
          </tr>
          <tr>
            <td style="padding:32px 40px;color:#0f172a;font-size:15px;line-height:1.6;">
              {$intro}
              {$bodyHtml}
              {$ctaHtml}
              <hr style="border:none;border-top:1px solid #e2e8f0;margin:32px 0;" />
              <p style="margin:0;font-size:13px;color:#475569;">Gracias por ser parte de la comunidad Lucatón. Seguimos construyendo oportunidades solidarias.</p>
            </td>
          </tr>
          <tr>
            <td style="background:#f1f5f9;padding:24px 40px;color:#64748b;font-size:12px;text-align:center;">
              <p style="margin:0 0 8px;">Recibes este correo porque te suscribiste a las novedades de Lucatón.</p>
              <p style="margin:0;">
                <a href="{$this->escape($unsubscribeUrl)}" style="color:#dc2626;text-decoration:none;font-weight:600;">Dejar de recibir correos</a>
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

        $text = "{$intro}\n\n{$bodyText}\n\n{$ctaText}Si no quieres recibir más correos de Lucatón, desuscríbete aquí: {$unsubscribeUrl}";

        return ['html' => $html, 'text' => $text];
    }

    private function buildIntro(string $templateKey, string $subscriberName): string
    {
        $saludo = $subscriberName !== '' ? "Hola {$this->escape($subscriberName)}," : 'Hola,';

        switch ($templateKey) {
            case 'impact_story':
                $copy = 'Tenemos una historia inspiradora para compartir contigo. Así estamos conectando a más personas con causas solidarias.';
                break;
            case 'platform_update':
                $copy = 'Preparamos un resumen con las últimas mejoras de la plataforma. Queremos que aproveches al máximo las nuevas herramientas.';
                break;
            default:
                $copy = 'Gracias por seguir las novedades de la comunidad Lucatón. Esto es lo más reciente:';
                break;
        }

        return sprintf('<p style="margin:0 0 16px;font-size:15px;color:#0f172a;font-weight:600;">%s</p><p style="margin:0 0 24px;color:#475569;font-size:14px;">%s</p>', $saludo, $this->escape($copy));
    }

    private function formatMessageHtml(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return '<p style="margin:0 0 16px;color:#475569;">(Sin contenido adicional)</p>';
        }

        $parts = preg_split('/\r?\n\r?\n/', $message);
        $html = '';
        foreach ($parts as $part) {
            $clean = trim($part);
            if ($clean === '') {
                continue;
            }
            $html .= '<p style="margin:0 0 16px;color:#475569;">' . nl2br($this->escape($clean)) . '</p>';
        }

        return $html;
    }

    private function formatMessageText(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return '';
        }

        return trim(preg_replace('/\r?\n\r?\n/', "\n\n", $message));
    }

    private function resolveSubscriberName(array $subscriber): string
    {
        if (empty($subscriber)) {
            return '';
        }

        $name = trim(($subscriber['name'] ?? '') ?: (($subscriber['first_name'] ?? '') . ' ' . ($subscriber['last_name'] ?? '')));
        return trim($name);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function writePreview(array $payload, string $templateKey): ?string
    {
        try {
            if (!is_dir($this->previewDirectory)) {
                mkdir($this->previewDirectory, 0775, true);
            }

            $filename = sprintf(
                '%s/%s-newsletter-%s.json',
                $this->previewDirectory,
                date('Ymd_His'),
                $templateKey
            );

            file_put_contents($filename, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $filename;
        } catch (Throwable $exception) {
            Logger::warning('No se pudo generar el preview del newsletter', [
                'error' => $exception->getMessage()
            ]);
            return null;
        }
    }

    private function resolveMailEnabled(): bool
    {
        $host = env('MAIL_HOST', '');
        $username = env('MAIL_USERNAME', '');
        $password = env('MAIL_PASSWORD', '');
        $from = env('MAIL_FROM_ADDRESS', '');

        return $host !== '' && $username !== '' && $password !== '' && $from !== '';
    }
}
