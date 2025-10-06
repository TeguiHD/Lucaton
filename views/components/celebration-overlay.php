<?php
if (empty($overlayData) || !is_array($overlayData)) {
    return;
}

$overlayId = 'celebration-' . ($overlayData['campaign_id'] ?? uniqid());
$campaignTitle = htmlspecialchars($overlayData['campaign_title'] ?? 'Tu campaña', ENT_QUOTES, 'UTF-8');
$raisedLabel = htmlspecialchars($overlayData['raised_amount'] ?? '—', ENT_QUOTES, 'UTF-8');
$goalLabel = htmlspecialchars($overlayData['goal_amount'] ?? '—', ENT_QUOTES, 'UTF-8');
$publicUrl = htmlspecialchars($overlayData['public_url'] ?? '#', ENT_QUOTES, 'UTF-8');
$manageUrl = !empty($overlayData['manage_url']) ? htmlspecialchars($overlayData['manage_url'], ENT_QUOTES, 'UTF-8') : null;

if (!defined('CELEBRATION_OVERLAY_STYLES')):
    define('CELEBRATION_OVERLAY_STYLES', true);
    ?>
    <style>
        .celebration-overlay {
            position: fixed;
            inset: 0;
            z-index: 70;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.78);
            backdrop-filter: blur(4px);
            opacity: 0;
            transform: scale(0.98);
            transition: opacity .3s ease, transform .3s ease;
        }

        .celebration-overlay.is-visible {
            opacity: 1;
            transform: scale(1);
        }

        .celebration-overlay.is-hiding {
            opacity: 0;
            transform: scale(0.97);
        }

        .celebration-overlay canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .celebration-card {
            position: relative;
            width: 100%;
            max-width: 420px;
            padding: 2.25rem 2rem;
            border-radius: 1.75rem;
            background: linear-gradient(135deg, rgba(15, 118, 110, 0.95), rgba(5, 150, 105, 0.92));
            box-shadow: 0 25px 60px rgba(15, 118, 110, 0.28);
            text-align: center;
            color: #f8fafc;
            overflow: hidden;
        }

        .celebration-card::before {
            content: '';
            position: absolute;
            inset: -60% -20% auto;
            height: 260px;
            background: radial-gradient(circle at center, rgba(255, 255, 255, 0.45), transparent 60%);
            opacity: 0.75;
            pointer-events: none;
        }

        .celebration-card h2 {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }

        .celebration-card p {
            margin: 0.35rem 0;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .celebration-ribbon {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: rgba(244, 249, 247, 0.16);
            color: #ecfdf5;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .celebration-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
            margin-top: 1.75rem;
        }

        .celebration-actions a {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.55rem 1.2rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .celebration-actions a[data-variant="primary"] {
            background: #facc15;
            color: #422006;
            box-shadow: 0 8px 18px rgba(250, 204, 21, 0.35);
        }

        .celebration-actions a[data-variant="primary"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(250, 204, 21, 0.4);
        }

        .celebration-actions a[data-variant="ghost"] {
            background: rgba(15, 118, 110, 0.12);
            color: #ecfdf5;
            border: 1px solid rgba(236, 253, 245, 0.2);
        }

        .celebration-actions a[data-variant="ghost"]:hover {
            transform: translateY(-2px);
            background: rgba(15, 118, 110, 0.22);
        }

        .celebration-dismiss {
            margin-top: 1.75rem;
            background: none;
            border: none;
            color: rgba(236, 253, 245, 0.8);
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .celebration-dismiss:hover {
            color: #ffffff;
        }

        @media (max-width: 640px) {
            .celebration-card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
    <?php
endif;
?>

<div class="celebration-overlay" data-celebration-overlay>
    <canvas aria-hidden="true"></canvas>
    <div class="celebration-card" role="dialog" aria-modal="true" aria-labelledby="<?= htmlspecialchars($overlayId, ENT_QUOTES, 'UTF-8') ?>-title">
        <span class="celebration-ribbon">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 17l-5 3 1.5-5.8L4 10l6-.5L12 4l2 5.5 6 .5-4.5 4.2L17 20z" />
            </svg>
            Meta alcanzada
        </span>
        <h2 id="<?= htmlspecialchars($overlayId, ENT_QUOTES, 'UTF-8') ?>-title">¡Felicitaciones!</h2>
        <p>Tu campaña <strong><?= $campaignTitle ?></strong> alcanzó la meta.</p>
        <p>Recaudaste <?= $raisedLabel ?> de <?= $goalLabel ?>.</p>
        <p>Comparte un mensaje de cierre y organiza los siguientes pasos con tu comunidad.</p>
        <div class="celebration-actions">
            <a data-variant="primary" href="<?= $publicUrl ?>" target="_blank" rel="noopener noreferrer">
                Ver campaña
                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.293 2.293a1 1 0 011.414 0l4 4a1 1 0 01-.707 1.707H15v7a1 1 0 11-2 0V7h-2a1 1 0 01-.707-1.707l4-4z" /><path d="M5 5a2 2 0 00-2 2v8.5A2.5 2.5 0 005.5 18h9a2.5 2.5 0 002.5-2.5V13a1 1 0 112 0v2.5A4.5 4.5 0 0114.5 20h-9A4.5 4.5 0 011 15.5V7a4 4 0 014-4h2a1 1 0 010 2H5z" /></svg>
            </a>
            <?php if ($manageUrl): ?>
                <a data-variant="ghost" href="<?= $manageUrl ?>">
                    Gestionar campaña
                </a>
            <?php endif; ?>
        </div>
        <button type="button" class="celebration-dismiss" data-celebration-dismiss>Cerrar celebración</button>
    </div>
</div>
<?php
if (!defined('CELEBRATION_OVERLAY_SCRIPT')):
    define('CELEBRATION_OVERLAY_SCRIPT', true);
    ?>
    <script>
        (function () {
            var palette = ['#FACC15', '#F97316', '#EF4444', '#22D3EE', '#A855F7', '#2DD4BF'];

            function setupOverlay(overlay) {
                if (!overlay || overlay.__celebrationInitialized) {
                    return;
                }
                overlay.__celebrationInitialized = true;

                var canvas = overlay.querySelector('canvas');
                var ctx = canvas && canvas.getContext ? canvas.getContext('2d') : null;

                if (!ctx) {
                    overlay.classList.add('is-visible');
                    return;
                }

                var width = 0;
                var height = 0;
                var ratio = window.devicePixelRatio || 1;
                var active = true;
                var pieces = [];
                var pieceCount = 220;
                var lastTime = performance.now();

                function resize() {
                    ratio = window.devicePixelRatio || 1;
                    width = overlay.clientWidth;
                    height = overlay.clientHeight;
                    canvas.width = width * ratio;
                    canvas.height = height * ratio;
                    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                }

                function createPiece() {
                    return {
                        x: Math.random() * width,
                        y: height + Math.random() * height * 0.4,
                        size: 6 + Math.random() * 6,
                        speedY: 2 + Math.random() * 3.5,
                        sway: 0.5 + Math.random() * 0.9,
                        color: palette[Math.floor(Math.random() * palette.length)],
                        rotation: Math.random() * 360,
                        rotationSpeed: (Math.random() - 0.5) * 12
                    };
                }

                function drawFrame(timestamp) {
                    if (!active) {
                        return;
                    }

                    var delta = Math.min(32, (timestamp - lastTime) / 16.6667);
                    lastTime = timestamp;

                    ctx.clearRect(0, 0, width, height);

                    for (var i = 0; i < pieces.length; i++) {
                        var piece = pieces[i];
                        piece.y -= piece.speedY * delta;
                        piece.x += Math.sin((timestamp / 420) + piece.y * 0.012) * piece.sway * delta;
                        piece.rotation += piece.rotationSpeed * delta;

                        if (piece.y < -20) {
                            pieces[i] = createPiece();
                            pieces[i].y = height + Math.random() * height * 0.25;
                            continue;
                        }

                        ctx.save();
                        ctx.translate(piece.x, piece.y);
                        ctx.rotate(piece.rotation * Math.PI / 180);
                        var w = piece.size;
                        var h = piece.size * 0.6;
                        ctx.fillStyle = piece.color;
                        ctx.fillRect(-w / 2, -h / 2, w, h);
                        ctx.restore();
                    }

                    requestAnimationFrame(drawFrame);
                }

                function destroyOverlay() {
                    if (!active) {
                        return;
                    }
                    active = false;
                    window.removeEventListener('resize', resize);
                    document.removeEventListener('keydown', handleKeydown);
                    overlay.classList.remove('is-visible');
                    overlay.classList.add('is-hiding');
                    setTimeout(function () {
                        overlay.remove();
                    }, 340);
                }

                function handleKeydown(event) {
                    if (!active) {
                        return;
                    }
                    if (event.key === 'Escape') {
                        destroyOverlay();
                    }
                }

                resize();
                window.addEventListener('resize', resize);

                pieces = new Array(pieceCount).fill(null).map(createPiece);

                requestAnimationFrame(function (time) {
                    lastTime = time;
                    drawFrame(time);
                });

                setTimeout(function () {
                    overlay.classList.add('is-visible');
                }, 40);

                var dismissButton = overlay.querySelector('[data-celebration-dismiss]');
                if (dismissButton) {
                    dismissButton.addEventListener('click', destroyOverlay);
                }

                overlay.addEventListener('click', function (event) {
                    if (event.target === overlay) {
                        destroyOverlay();
                    }
                });

                document.addEventListener('keydown', handleKeydown);

                setTimeout(destroyOverlay, 12000);
            }

            function initOverlays() {
                var overlays = document.querySelectorAll('[data-celebration-overlay]');
                overlays.forEach(setupOverlay);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initOverlays);
            } else {
                initOverlays();
            }
        })();
    </script>
    <?php
endif;
