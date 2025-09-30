<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

if (php_sapi_name() !== 'cli') {
    echo "Ejecuta este archivo desde la línea de comandos.\n";
    exit(0);
}

$db = Database::getInstance();

echo "\n== Semilla de contenido demo ==\n";

function getTableColumns(Database $db, string $table): array {
    try {
        $rows = $db->fetchAll(sprintf('SHOW COLUMNS FROM `%s`', $table));
    } catch (Exception $e) {
        return [];
    }

    $columns = [];
    foreach ($rows as $row) {
        if (isset($row['Field'])) {
            $columns[] = $row['Field'];
        }
    }

    return $columns;
}

function hasColumns(array $available, array $required): bool {
    foreach ($required as $column) {
        if (!in_array($column, $available, true)) {
            return false;
        }
    }

    return true;
}

function ensureUser(Database $db, array $data): int {
    $existing = $db->fetch('SELECT id FROM users WHERE email = ? LIMIT 1', [$data['email']]);
    if ($existing) {
        return (int)$existing['id'];
    }

    $payload = [
        'username' => $data['username'],
        'email' => $data['email'],
        'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'role' => $data['role'] ?? 'user',
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    return (int)$db->insert('users', $payload);
}

function ensureCampaignCategory(Database $db, string $slug, string $name, string $description): int {
    $existing = $db->fetch('SELECT id FROM campaign_categories WHERE slug = ? LIMIT 1', [$slug]);
    if ($existing) {
        return (int)$existing['id'];
    }

    return (int)$db->insert('campaign_categories', [
        'name' => $name,
        'slug' => $slug,
        'description' => $description,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
}

function seedModularCampaign(Database $db, array $campaign, int $ownerId): void {
    $exists = $db->fetch('SELECT id FROM campaigns WHERE slug = ? LIMIT 1', [$campaign['slug']]);
    if ($exists) {
        echo "- Campaña demo '{$campaign['slug']}' ya existe (#{$exists['id']}).\n";
        return;
    }

    $now = date('Y-m-d H:i:s');
    $categoryId = ensureCampaignCategory(
        $db,
        $campaign['category_slug'],
        $campaign['category_name'],
        $campaign['category_description']
    );

    $startDate = date('Y-m-d', strtotime($campaign['start_offset']));
    $endDate = date('Y-m-d', strtotime($campaign['end_offset']));

    $db->beginTransaction();
    try {
        $campaignId = (int)$db->insert('campaigns', [
            'owner_id' => $ownerId,
            'category_id' => $categoryId,
            'title' => $campaign['title'],
            'slug' => $campaign['slug'],
            'summary' => $campaign['summary'],
            'story' => $campaign['story'],
            'goal_amount' => $campaign['goal_amount'],
            'currency' => 'CLP',
            'status' => $campaign['status'],
            'visibility' => 'public',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'published_at' => date('Y-m-d H:i:s', strtotime($campaign['published_offset'])),
            'cover_image_url' => $campaign['cover_image'],
            'video_url' => $campaign['video_url'],
            'ai_assisted' => $campaign['ai_assisted'] ? 1 : 0,
            'featured' => $campaign['featured'] ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now
        ]);

        $db->insert('campaign_details', [
            'campaign_id' => $campaignId,
            'beneficiary_type' => $campaign['beneficiary_type'],
            'beneficiary_name' => $campaign['beneficiary_name'],
            'beneficiary_contact' => $campaign['beneficiary_contact'],
            'location_label' => $campaign['location_label'],
            'impact_summary' => $campaign['impact_summary'],
            'transparency_plan' => $campaign['transparency_plan'],
            'support_channels' => json_encode($campaign['support_channels'], JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
            'updated_at' => $now
        ]);

        $donorCount = $campaign['donor_count'];
        $raisedAmount = $campaign['raised_amount'];
        $averageDonation = $donorCount > 0 ? round($raisedAmount / $donorCount, 2) : 0;

        $db->insert('campaign_metrics', [
            'campaign_id' => $campaignId,
            'raised_amount' => $raisedAmount,
            'donor_count' => $donorCount,
            'follower_count' => $campaign['follower_count'],
            'share_count' => $campaign['share_count'],
            'view_count' => $campaign['view_count'],
            'average_donation' => $averageDonation,
            'last_donation_at' => date('Y-m-d H:i:s', strtotime($campaign['last_donation_offset'])),
            'created_at' => $now,
            'updated_at' => $now
        ]);

        $db->insert('campaign_status_history', [
            'campaign_id' => $campaignId,
            'previous_status' => null,
            'new_status' => $campaign['status'],
            'changed_by' => $ownerId,
            'notes' => 'Campaña demo creada automáticamente',
            'created_at' => $now
        ]);

        $db->commit();
        echo "- Campaña demo '{$campaign['title']}' creada (#{$campaignId}).\n";
    } catch (Exception $e) {
        $db->rollback();
        echo "! Error creando campaña '{$campaign['slug']}': {$e->getMessage()}\n";
    }
}

function ensureNewsCategory(Database $db, string $slug, string $name): int {
    $existing = $db->fetch('SELECT id FROM news_categories WHERE slug = ? LIMIT 1', [$slug]);
    if ($existing) {
        return (int)$existing['id'];
    }

    return (int)$db->insert('news_categories', [
        'name' => $name,
        'slug' => $slug,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
}

function seedNewsArticle(Database $db, array $article, int $authorId, int $categoryId): void {
    $exists = $db->fetch('SELECT id FROM news_articles WHERE slug = ? LIMIT 1', [$article['slug']]);
    if ($exists) {
        echo "- Artículo demo '{$article['slug']}' ya existe (#{$exists['id']}).\n";
        return;
    }

    $payload = [
        'author_id' => $authorId,
        'category_id' => $categoryId,
        'title' => $article['title'],
        'slug' => $article['slug'],
        'summary' => $article['summary'],
        'content' => $article['content'],
        'cover_image' => $article['cover_image'],
        'status' => 'published',
        'published_at' => date('Y-m-d H:i:s', strtotime($article['published_offset'])),
        'meta_title' => $article['meta_title'],
        'meta_description' => $article['meta_description']
    ];

    $articleId = (int)$db->insert('news_articles', $payload);
    echo "- Artículo demo '{$article['title']}' creado (#{$articleId}).\n";
}

$campaignColumns = getTableColumns($db, 'campaigns');
$detailsColumns = getTableColumns($db, 'campaign_details');
$metricsColumns = getTableColumns($db, 'campaign_metrics');

$hasModularCampaigns = hasColumns($campaignColumns, ['summary', 'category_id', 'owner_id', 'visibility'])
    && hasColumns($detailsColumns, ['campaign_id', 'beneficiary_name'])
    && hasColumns($metricsColumns, ['campaign_id', 'raised_amount']);

if ($hasModularCampaigns) {
    echo "\n-- Campañas demo --\n";

    $ownerId = ensureUser($db, [
        'username' => 'semilla_owner',
        'email' => 'semilla.owner@lucaton.cl',
        'password' => 'LucatonDemo123!',
        'first_name' => 'Lucatón',
        'last_name' => 'Demo',
        'role' => 'user'
    ]);

    $campaigns = [
        [
            'title' => 'Red solidaria para la Escuela Rural Los Boldos',
            'slug' => 'escuela-rural-los-boldos',
            'summary' => 'Recaudamos fondos para reparar techos, habilitar un laboratorio digital y asegurar alimentación para 120 niñas y niños de la escuela Los Boldos.',
            'story' => 'La comunidad de Los Boldos lleva años levantando rifas para mantener abierta su escuela rural. Esta campaña busca financiar reparaciones urgentes, habilitar un laboratorio digital con 12 equipos y garantizar alimentación completa durante el invierno. Con el apoyo de Lucatón y aliados municipales, transparentaremos cada compra y visita al establecimiento.',
            'goal_amount' => 15000000,
            'category_slug' => 'education',
            'category_name' => 'Educación',
            'category_description' => 'Apoya iniciativas educativas y de formación',
            'beneficiary_type' => 'community',
            'beneficiary_name' => 'Centro de Padres Escuela Los Boldos',
            'beneficiary_contact' => 'contacto@losboldos.cl',
            'location_label' => 'San Javier, Región del Maule',
            'impact_summary' => '120 estudiantes con infraestructura segura y acceso a tecnología.',
            'transparency_plan' => 'Publicaremos avances quincenales, videos de terreno y actas firmadas por dirección.',
            'support_channels' => [
                ['tipo' => 'WhatsApp', 'valor' => '+569 5555 1212'],
                ['tipo' => 'Correo', 'valor' => 'escuela@losboldos.cl']
            ],
            'raised_amount' => 4200000,
            'donor_count' => 182,
            'follower_count' => 315,
            'share_count' => 980,
            'view_count' => 5240,
            'last_donation_offset' => '-1 day',
            'start_offset' => '-25 days',
            'end_offset' => '+35 days',
            'published_offset' => '-22 days',
            'status' => 'published',
            'ai_assisted' => true,
            'featured' => true,
            'cover_image' => APP_URL . '/public/assets/images/campaigns/escuela-rural.svg',
            'video_url' => APP_URL . '/public/assets/videos/escuela-rural.mp4'
        ],
        [
            'title' => 'Fondo de urgencia para el Centro Comunitario Bocatoma',
            'slug' => 'fondo-urgencia-bocatoma',
            'summary' => 'Activamos un fondo de emergencia para financiar alimentos, calefacción y medicamentos de adultos mayores del centro Bocatoma durante el invierno.',
            'story' => 'El centro comunitario Bocatoma atiende a 60 adultos mayores en situación de vulnerabilidad. El corte de suministro eléctrico en la zona dejó inoperativas sus cocinas y la bodega de medicamentos. Esta campaña financia estufas eléctricas, alimentos no perecibles y kits de salud. El seguimiento se realizará junto a voluntarios certificados y auditorías IA.',
            'goal_amount' => 9000000,
            'category_slug' => 'community',
            'category_name' => 'Comunidad y barrio',
            'category_description' => 'Proyectos colectivos que cambian barrios',
            'beneficiary_type' => 'organization',
            'beneficiary_name' => 'Fundación Bocatoma',
            'beneficiary_contact' => 'fundacion@bocatoma.cl',
            'location_label' => 'Valparaíso, Chile',
            'impact_summary' => '60 adultos mayores con abrigo, alimentación y medicación segura por 3 meses.',
            'transparency_plan' => 'Reportes semanales con facturas escaneadas, visitas registradas y testimonios.',
            'support_channels' => [
                ['tipo' => 'Teléfono', 'valor' => '+5632 222 4455']
            ],
            'raised_amount' => 2750000,
            'donor_count' => 89,
            'follower_count' => 144,
            'share_count' => 420,
            'view_count' => 2780,
            'last_donation_offset' => '-3 hours',
            'start_offset' => '-10 days',
            'end_offset' => '+20 days',
            'published_offset' => '-9 days',
            'status' => 'under_review',
            'ai_assisted' => false,
            'featured' => false,
            'cover_image' => APP_URL . '/public/assets/images/campaigns/centro-bocatoma.jpg',
            'video_url' => null
        ]
    ];

    foreach ($campaigns as $campaign) {
        seedModularCampaign($db, $campaign, $ownerId);
    }
} else {
    echo "\n-- Campañas demo --\n";
    echo "No se detectó el esquema modular de campañas. Se omitió la creación de ejemplos.\n";
}

$newsColumns = getTableColumns($db, 'news_articles');
$newsCategoryColumns = getTableColumns($db, 'news_categories');
$hasNewsTables = hasColumns($newsColumns, ['slug', 'status', 'content']) && hasColumns($newsCategoryColumns, ['slug']);

if ($hasNewsTables) {
    echo "\n-- Noticias demo --\n";

    $authorId = ensureUser($db, [
        'username' => 'semilla_editor',
        'email' => 'semilla.editor@lucaton.cl',
        'password' => 'LucatonDemo123!',
        'first_name' => 'Equipo',
        'last_name' => 'Lucatón',
        'role' => 'admin'
    ]);

    $categoryImpacto = ensureNewsCategory($db, 'impacto-social', 'Impacto Social');
    $categoryTransparencia = ensureNewsCategory($db, 'transparencia', 'Transparencia');

    $articles = [
        [
            'title' => 'Lucatón activa red humana e IA para emergencias invernales',
            'slug' => 'lucaton-red-humana-ia-invierno',
            'summary' => 'Presentamos la nueva célula de respuesta rápida que combina especialistas sociales con copilotos de inteligencia artificial para priorizar campañas invernales.',
            'content' => 'La red solidaria de Lucatón integró a 24 voluntarios, asistentes sociales y analistas de riesgo que trabajan junto a modelos de IA para clasificar urgencias, detectar riesgos y acompañar la rendición en terreno. Durante el primer piloto se levantaron 15 campañas en menos de 72 horas con transparencia certificada.',
            'cover_image' => APP_URL . '/public/assets/images/news/red-solidaria.jpg',
            'meta_title' => 'Red humana e IA de Lucatón responde a emergencias invernales',
            'meta_description' => 'Conoce la nueva célula de Lucatón que combina apoyo humano y tecnología para priorizar campañas urgentes en invierno.',
            'published_offset' => '-3 days',
            'category_id' => $categoryImpacto
        ],
        [
            'title' => 'Transparencia total: así auditamos las campañas demo',
            'slug' => 'transparencia-total-campanas-demo',
            'summary' => 'Explicamos el flujo de rendición, los tableros públicos y las verificaciones con IA que acompañan a las campañas demo creadas para pruebas académicas.',
            'content' => 'Cada campaña demo incluye tablero de auditoría, galería de evidencias y notas públicas. Documentamos paso a paso cómo se valida la información, qué documentos se solicitan y cómo se publican avances para donantes y supervisores.',
            'cover_image' => APP_URL . '/public/assets/images/news/transparencia-demo.jpg',
            'meta_title' => 'Auditoría y rendición de campañas demo en Lucatón',
            'meta_description' => 'Detalle del flujo de transparencia utilizado para las campañas demostrativas de Lucatón.',
            'published_offset' => '-1 day',
            'category_id' => $categoryTransparencia
        ]
    ];

    foreach ($articles as $article) {
        seedNewsArticle($db, $article, $authorId, $article['category_id']);
    }
} else {
    echo "\n-- Noticias demo --\n";
    echo "No se detectaron tablas de noticias compatibles. Se omitió la creación de artículos.\n";
}

echo "\nListo. Puedes ejecutar: php database/seeds/seed_demo_content.php\n";
