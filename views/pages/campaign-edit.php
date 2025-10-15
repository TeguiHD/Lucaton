<?php
require_once __DIR__ . '/../components/forms.php';
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../components/navigation.php';
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$campaignId = (int)($campaign['id'] ?? 0);
$formErrors = $formErrors ?? [];
$formOld = $formOld ?? [];
$categories = $categories ?? [];
$mediaManifest = $mediaManifest ?? [];

$campaignEndTimestamp = null;
if (!empty($campaign['end_date'])) {
    $timestampCandidate = strtotime((string)$campaign['end_date']);
    if ($timestampCandidate !== false) {
        $campaignEndTimestamp = $timestampCandidate;
    }
}

$baseOld = [
    'title' => $campaign['title'] ?? '',
    'short_description' => $campaign['summary'] ?? '',
    'description' => $campaign['story'] ?? '',
    'goal_amount_input' => isset($campaign['goal_amount']) ? (string)(int)$campaign['goal_amount'] : '',
    'end_date' => $campaignEndTimestamp ? date('Y-m-d H:i:s', $campaignEndTimestamp) : '',
    'end_date_input' => $campaignEndTimestamp ? date('Y-m-d\TH:i', $campaignEndTimestamp) : '',
    'category' => $campaign['category_slug'] ?? ($campaign['category'] ?? ''),
    'beneficiary_type' => $campaign['beneficiary_type'] ?? 'individual',
    'beneficiary_name' => $campaign['beneficiary_name'] ?? '',
    'beneficiary_contact_text' => $campaign['beneficiary_contact'] ?? '',
    'location' => $campaign['location_label'] ?? ($campaign['location'] ?? ''),
    'video_url' => $campaign['video_url'] ?? '',
    'ai_generated' => !empty($campaign['ai_assisted']) ? '1' : '0',
];

$old = array_merge($baseOld, $formOld);
$amountValue = $old['goal_amount_input'] !== '' ? $old['goal_amount_input'] : ($baseOld['goal_amount_input'] ?? '');
$beneficiaryContactValue = $old['beneficiary_contact_text'] ?? '';
$isAiChecked = !empty($old['ai_generated']);

$categoryOptions = array_filter(
    $categories,
    static fn ($label, $slug) => $slug !== '',
    ARRAY_FILTER_USE_BOTH
);

$coverPreview = $mediaManifest['cover_image'] ?? ($campaign['cover_image_url'] ?? $campaign['image_url'] ?? null);
$currentEndDateValue = $old['end_date_input'] ?? '';
if ($currentEndDateValue === '' && !empty($old['end_date'])) {
    $timestampCandidate = strtotime((string)$old['end_date']);
    if ($timestampCandidate !== false) {
        $currentEndDateValue = date('Y-m-d\TH:i', $timestampCandidate);
    }
}
$endDateMinimumDateTime = new DateTime('+1 hour');
$endDateMinimum = $endDateMinimumDateTime->format('Y-m-d\TH:i');
$endDateMinDate = $endDateMinimumDateTime->format('Y-m-d');
$endDateMinTime = $endDateMinimumDateTime->format('H:i');
$endDateDateValue = '';
$endDateTimeValue = '';
if ($currentEndDateValue !== '') {
    $currentTimestamp = strtotime($currentEndDateValue);
    if ($currentTimestamp !== false) {
        $endDateDateValue = date('Y-m-d', $currentTimestamp);
        $endDateTimeValue = date('H:i', $currentTimestamp);
    }
}
if ($endDateDateValue === '') {
    $endDateDateValue = $endDateMinDate;
}
if ($endDateTimeValue === '') {
    $endDateTimeValue = ($endDateDateValue === $endDateMinDate) ? $endDateMinTime : '12:00';
}
$endDateHiddenValue = $currentEndDateValue !== '' ? $currentEndDateValue : ($endDateDateValue . 'T' . $endDateTimeValue);
$endDateDateMinAttribute = $endDateDateValue < $endDateMinDate ? $endDateDateValue : $endDateMinDate;
$endDateTimeLabel = 'Selecciona hora';
$endDateHourDigital = '12';
$endDateMinuteDigital = '00';
$endDateMeridiemDigital = 'AM';
$endDateDisplayLabel = 'Selecciona fecha y hora';
if ($endDateTimeValue !== '') {
    $timeLabelInstance = DateTime::createFromFormat('H:i', $endDateTimeValue);
    if ($timeLabelInstance instanceof DateTime) {
        $endDateTimeLabel = $timeLabelInstance->format('g:i A');
        $endDateHourDigital = $timeLabelInstance->format('h');
        $endDateMinuteDigital = $timeLabelInstance->format('i');
        $endDateMeridiemDigital = $timeLabelInstance->format('A');
    }
}
$combinedForDisplay = DateTime::createFromFormat('Y-m-d H:i', $endDateDateValue . ' ' . $endDateTimeValue);
if ($combinedForDisplay instanceof DateTime) {
    $endDateDisplayLabel = $combinedForDisplay->format('d/m/Y · g:i A');
}
$endDateHourActive = (int)ltrim($endDateHourDigital, '0');
if ($endDateHourActive <= 0) {
    $endDateHourActive = 12;
}
$endDateMinuteActive = max(0, min(59, (int)$endDateMinuteDigital));
$hourPickerOptions = range(1, 12);
$timeStepSeconds = 300;
$timeStepMinutes = max(1, (int)round($timeStepSeconds / 60));
$minutePickerOptions = [];
for ($minuteOption = 0; $minuteOption < 60; $minuteOption += $timeStepMinutes) {
    $minutePickerOptions[] = $minuteOption;
}
$normalizePublicUrl = static function (?string $path) {
    return CampaignMediaUploadService::normalizePublicUrl($path);
};
$coverPreview = $normalizePublicUrl($coverPreview);
$galleryItems = array_map(static function (array $item) use ($normalizePublicUrl) {
    if (!isset($item['url'])) {
        return $item;
    }

    $item['url'] = $normalizePublicUrl($item['url']) ?? $item['url'];
    return $item;
}, $mediaManifest['gallery'] ?? []);
$attachmentItems = $mediaManifest['attachments'] ?? [];

$page_title = $page_title ?? 'Editar campaña';
$page_description = 'Actualiza los datos de tu campaña para que el equipo pueda revisarla nuevamente.';
$current_page = 'my_campaigns';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= asset_url('images/favicon.svg') ?>">
    <link href="<?= asset_url('css/app.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/aliases.css') ?>" rel="stylesheet">
    <script defer src="<?= asset_url('js/app.js') ?>"></script>
    <template data-datetime-modal-template>
        <div class="datetime-overlay" data-datetime-overlay hidden>
            <div class="datetime-modal" data-modal tabindex="-1" role="dialog" aria-modal="true" aria-label="Definir fecha y hora de término">
                <div class="datetime-modal__header">
                    <h2 class="datetime-modal__title">Define fecha y hora de término</h2>
                    <button type="button" class="datetime-modal__close" data-datetime-close aria-label="Cerrar selector">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12m0-12L6 18" /></svg>
                    </button>
                </div>
                <div class="datetime-modal__content">
                    <div class="datetime-modal__date">
                        <span class="datetime-calendar__label" id="modal-date-label">Fecha</span>
                        <div class="datetime-calendar" data-calendar aria-labelledby="modal-date-label">
                            <div class="datetime-calendar__header">
                                <button type="button" class="datetime-calendar__nav" data-calendar-prev aria-label="Mes anterior">&lsaquo;</button>
                                <div class="datetime-calendar__current">
                                    <span class="datetime-calendar__month" data-calendar-month>---</span>
                                    <span class="datetime-calendar__year" data-calendar-year>----</span>
                                </div>
                                <button type="button" class="datetime-calendar__nav" data-calendar-next aria-label="Mes siguiente">&rsaquo;</button>
                            </div>
                            <div class="datetime-calendar__weekdays" aria-hidden="true">
                                <span>Lun</span>
                                <span>Mar</span>
                                <span>Mié</span>
                                <span>Jue</span>
                                <span>Vie</span>
                                <span>Sáb</span>
                                <span>Dom</span>
                            </div>
                            <div class="datetime-calendar__grid" data-calendar-grid role="radiogroup" aria-label="Selecciona día del mes"></div>
                        </div>
                        <input type="hidden" data-modal-date id="modal-date-field">
                    </div>
                    <div class="datetime-modal__time">
                        <p class="datetime-modal__time-heading">Hora</p>
                        <div class="time-clock" data-clock data-phase="hour">
                            <span class="time-clock__hand time-clock__hand--hour" data-clock-hand="hour"></span>
                            <span class="time-clock__hand time-clock__hand--minute" data-clock-hand="minute"></span>
                            <div class="time-clock__dial time-clock__dial--hour" data-modal-hours>
                                                        <button type="button" class="time-picker-option" data-hour-option="1">1</button>
                                <button type="button" class="time-picker-option" data-hour-option="2">2</button>
                                <button type="button" class="time-picker-option" data-hour-option="3">3</button>
                                <button type="button" class="time-picker-option" data-hour-option="4">4</button>
                                <button type="button" class="time-picker-option" data-hour-option="5">5</button>
                                <button type="button" class="time-picker-option" data-hour-option="6">6</button>
                                <button type="button" class="time-picker-option" data-hour-option="7">7</button>
                                <button type="button" class="time-picker-option" data-hour-option="8">8</button>
                                <button type="button" class="time-picker-option" data-hour-option="9">9</button>
                                <button type="button" class="time-picker-option" data-hour-option="10">10</button>
                                <button type="button" class="time-picker-option" data-hour-option="11">11</button>
                                <button type="button" class="time-picker-option" data-hour-option="12">12</button>
                            </div>
                            <div class="time-clock__dial time-clock__dial--minute" data-modal-minutes>
                                                        <button type="button" class="time-picker-option" data-minute-option="0">:00</button>
                                <button type="button" class="time-picker-option" data-minute-option="5">:05</button>
                                <button type="button" class="time-picker-option" data-minute-option="10">:10</button>
                                <button type="button" class="time-picker-option" data-minute-option="15">:15</button>
                                <button type="button" class="time-picker-option" data-minute-option="20">:20</button>
                                <button type="button" class="time-picker-option" data-minute-option="25">:25</button>
                                <button type="button" class="time-picker-option" data-minute-option="30">:30</button>
                                <button type="button" class="time-picker-option" data-minute-option="35">:35</button>
                                <button type="button" class="time-picker-option" data-minute-option="40">:40</button>
                                <button type="button" class="time-picker-option" data-minute-option="45">:45</button>
                                <button type="button" class="time-picker-option" data-minute-option="50">:50</button>
                                <button type="button" class="time-picker-option" data-minute-option="55">:55</button>
                            </div>
                        </div>
                        <div class="time-clock__panel">
                            <div class="time-clock__digital" role="group" aria-label="Hora seleccionada">
                                <button type="button" class="time-clock__digit is-active" data-phase-trigger="hour" aria-pressed="true"><span data-digital-hour>12</span></button>
                                <span class="time-clock__colon">:</span>
                                <button type="button" class="time-clock__digit" data-phase-trigger="minute" aria-pressed="false"><span data-digital-minute>00</span></button>
                                <span class="time-clock__meridiem-text" data-digital-meridiem>AM</span>
                            </div>
                            <div class="time-clock__meridiem">
                                <button type="button" class="time-clock__period-button is-active" data-modal-meridiem="AM">AM</button>
                                <button type="button" class="time-clock__period-button" data-modal-meridiem="PM">PM</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="datetime-modal__status">
                    <p data-modal-summary>Selecciona fecha y hora para continuar.</p>
                    <p class="datetime-modal__warning hidden" data-modal-warning></p>
                </div>
                <div class="datetime-modal__footer">
                    <button type="button" data-datetime-cancel>Cancelar</button>
                    <button type="button" data-datetime-apply>Aplicar</button>
                </div>
            </div>
        </div>
    </template>
    <style>
:root {
    --wizard-primary: #103c5d;
    --wizard-accent: #ee6352;
    --wizard-soft: #f3f5f9;
}
@keyframes fade-up {
    from { opacity: 0; transform: translate3d(0, 24px, 0); }
    to { opacity: 1; transform: translate3d(0, 0, 0); }
}
[data-animate="fade-up"] { animation: fade-up .55s ease-out both; }
[data-animate-delay="150"] { animation-delay: .15s; }
[data-animate-delay="300"] { animation-delay: .3s; }
[data-animate-delay="450"] { animation-delay: .45s; }

.wizard-step {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    border-radius: 1rem;
    border: 1px solid rgba(16, 60, 93, 0.18);
    background: rgba(255, 255, 255, 0.95);
    padding: 0.85rem 1rem;
    transition: all .25s ease;
    text-align: left;
}
.wizard-step:hover { border-color: rgba(16, 60, 93, 0.35); box-shadow: 0 8px 22px -14px rgba(16, 60, 93, .45); }
.wizard-step:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(238, 99, 82, 0.35); }
.wizard-step__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 999px;
    background: rgba(16, 60, 93, 0.12);
    color: var(--wizard-primary);
    font-weight: 600;
    transition: all .25s ease;
}
.wizard-step.is-active {
    border-color: rgba(238, 99, 82, 0.65);
    background: linear-gradient(135deg, rgba(238, 99, 82, 0.12), rgba(16, 60, 93, 0.05));
}
.wizard-step.is-complete {
    border-color: rgba(16, 60, 93, 0.28);
    background: rgba(16, 60, 93, 0.08);
}
.wizard-step.is-active .wizard-step__badge {
    background: var(--wizard-accent);
    color: #fff;
}
.wizard-step.is-complete .wizard-step__badge {
    background: var(--wizard-primary);
    color: #fff;
}
.wizard-panel { animation: fade-up .45s ease-out both; }
.wizard-panel.hidden { display: none; }

body.datetime-modal-open { overflow: hidden; }
[data-end-datetime] { position: relative; }
[data-time-picker] { position: relative; z-index: 200; }

.datetime-trigger {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    width: 100%;
    border-radius: 1.1rem;
    border: 1px solid rgba(16, 60, 93, 0.2);
    background: #ffffff;
    padding: 1rem 1.15rem;
    text-align: left;
    transition: all .2s ease;
    box-shadow: 0 14px 30px -22px rgba(16, 60, 93, 0.45);
}
.datetime-trigger:hover { border-color: rgba(16, 60, 93, 0.45); box-shadow: 0 18px 42px -24px rgba(16, 60, 93, 0.55); }
.datetime-trigger:focus-visible { outline: 2px solid rgba(16, 60, 93, 0.55); outline-offset: 3px; }
.datetime-trigger__label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    color: #64748b;
    font-weight: 600;
}
.datetime-trigger__value {
    font-size: 1rem;
    font-weight: 600;
    color: #103c5d;
}

.datetime-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    opacity: 0;
    pointer-events: none;
    transition: opacity .2s ease;
    z-index: 5000;
}
.datetime-overlay.is-open { opacity: 1; pointer-events: auto; }

.datetime-modal {
    width: min(640px, 100%);
    max-height: 90vh;
    overflow-y: auto;
    background: #ffffff;
    border-radius: 1.6rem;
    padding: 1.75rem;
    box-shadow: 0 28px 70px -28px rgba(15, 23, 42, 0.55);
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.datetime-modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}
.datetime-modal__title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #103c5d;
}
.datetime-modal__close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 999px;
    border: 1px solid rgba(148, 163, 184, 0.4);
    background: #fff;
    color: #475569;
    transition: all .2s ease;
}
.datetime-modal__close:hover { border-color: rgba(16, 60, 93, 0.45); color: #103c5d; }
.datetime-modal__close:focus-visible { outline: 2px solid rgba(16, 60, 93, 0.55); outline-offset: 3px; }

.datetime-modal__content {
    display: flex;
    flex-wrap: wrap;
    gap: 1.75rem;
}
.datetime-modal__date {
    flex: 1 1 220px;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}
.datetime-modal__date label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #0f172a;
}
.datetime-modal__date input {
    border-radius: 0.9rem;
    border: 1px solid rgba(148, 163, 184, 0.55);
    padding: 0.65rem 0.85rem;
    font-size: 0.95rem;
    color: #0f172a;
    background: #fff;
    outline: none;
    transition: all .2s ease;
}
.datetime-modal__date input:focus { border-color: rgba(16, 60, 93, 0.6); box-shadow: 0 0 0 3px rgba(16, 60, 93, 0.1); }

.datetime-calendar {
    border-radius: 1.25rem;
    border: 1px solid rgba(148, 163, 184, 0.45);
    background: #ffffff;
    box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.12), 0 20px 45px -32px rgba(15, 23, 42, 0.45);
    padding: 1rem 1.1rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}
.datetime-calendar__label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 0.5rem;
}
.datetime-calendar__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}
.datetime-calendar__current {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    text-transform: capitalize;
}
.datetime-calendar__month {
    font-size: 1rem;
    font-weight: 700;
    color: #103c5d;
}
.datetime-calendar__year {
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    color: #64748b;
}
.datetime-calendar__nav {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.2rem;
    height: 2.2rem;
    border-radius: 0.8rem;
    border: 1px solid rgba(148, 163, 184, 0.45);
    background: rgba(248, 250, 252, 0.9);
    color: #0f172a;
    font-size: 1.2rem;
    font-weight: 600;
    transition: all .2s ease;
}
.datetime-calendar__nav:hover { border-color: rgba(16, 60, 93, 0.45); color: #103c5d; }
.datetime-calendar__nav:focus-visible { outline: 2px solid rgba(16, 60, 93, 0.55); outline-offset: 2px; }
.datetime-calendar__nav:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}
.datetime-calendar__weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.35rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    text-align: center;
}
.datetime-calendar__grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.35rem;
}
.datetime-calendar__day {
    display: inline-flex;
    width: 2.35rem;
    height: 2.35rem;
    align-items: center;
    justify-content: center;
    border-radius: 0.85rem;
    border: 1px solid transparent;
    background: rgba(255, 255, 255, 0.96);
    color: #0f172a;
    font-size: 0.95rem;
    font-weight: 600;
    transition: all .2s ease;
    cursor: pointer;
}
.datetime-calendar__day:hover:not(:disabled) { border-color: rgba(16, 60, 93, 0.35); box-shadow: 0 18px 35px -28px rgba(16, 60, 93, 0.45); }
.datetime-calendar__day:focus-visible { outline: 2px solid rgba(16, 60, 93, 0.55); outline-offset: 2px; }
.datetime-calendar__day.is-muted { color: #94a3b8; }
.datetime-calendar__day.is-disabled { color: #cbd5f5; opacity: 0.35; cursor: not-allowed; }
.datetime-calendar__day.is-today { border-color: rgba(16, 60, 93, 0.35); }
.datetime-calendar__day.is-selected {
    background: rgba(16, 60, 93, 0.92);
    color: #ffffff;
    border-color: rgba(16, 60, 93, 0.92);
    box-shadow: 0 20px 36px -24px rgba(15, 23, 42, 0.6);
}

.datetime-modal__time {
    flex: 1 1 320px;
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
}
.datetime-modal__time-heading {
    font-size: 0.85rem;
    font-weight: 600;
    color: #103c5d;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.time-clock {
    position: relative;
    --clock-size: min(15rem, calc(100vw - 6rem));
    --hour-radius: calc(var(--clock-size) / 2 - 2.4rem);
    --minute-radius: calc(var(--clock-size) / 2 - 1.55rem);
    width: var(--clock-size);
    height: var(--clock-size);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-inline: auto;
    isolation: isolate;
}
.time-clock::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: radial-gradient(circle at 50% 45%, rgba(16, 60, 93, 0.12), rgba(16, 60, 93, 0.05) 55%, rgba(255, 255, 255, 0.96));
    box-shadow: inset 0 0 0 1px rgba(16, 60, 93, 0.12), 0 28px 65px -30px rgba(16, 60, 93, 0.45);
    z-index: -1;
}
.time-clock::after {
    content: '';
    position: absolute;
    width: 0.85rem;
    height: 0.85rem;
    border-radius: 50%;
    background: #103c5d;
    box-shadow: 0 0 0 5px rgba(16, 60, 93, 0.08);
    z-index: 10;
}
.time-clock__hand {
    position: absolute;
    top: 50%;
    left: 50%;
    border-radius: 999px;
    transform-origin: 50% 100%;
    transform: translate(-50%, -100%) rotate(var(--pointer-angle, 0deg));
    transition: transform .25s ease;
    background: linear-gradient(180deg, rgba(16, 60, 93, 0.95), rgba(38, 88, 130, 0.95));
    box-shadow: inset 0 -6px 10px -12px rgba(15, 23, 42, 0.6);
}
.time-clock__hand--hour {
    width: 0.36rem;
    height: calc(var(--clock-size) / 2 - 1.8rem);
    z-index: 6;
}
.time-clock__hand--minute {
    width: 0.28rem;
    height: calc(var(--clock-size) / 2 - 2.6rem);
    z-index: 8;
    background: linear-gradient(180deg, rgba(238, 99, 82, 0.9), rgba(238, 99, 82, 0.75));
}
.time-clock__dial {
    position: absolute;
    inset: 0;
    transition: opacity .18s ease;
}
.time-clock__dial--minute { opacity: 0; pointer-events: none; }
.time-clock[data-phase="minute"] .time-clock__dial--minute { opacity: 1; pointer-events: auto; }
.time-clock[data-phase="minute"] .time-clock__dial--hour { opacity: 0; pointer-events: none; }

.time-picker-option,
.time-clock__marker {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    border: 1px solid rgba(16, 60, 93, 0.18);
    background: rgba(255, 255, 255, 0.98);
    color: #0f172a;
    font-size: 0.9rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    box-shadow: 0 14px 32px -22px rgba(16, 60, 93, 0.35);
    transition: all .2s ease;
}
.time-clock__marker {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 2.45rem;
    height: 2.45rem;
    transform-origin: center;
    --marker-radius: var(--hour-radius);
    transform: translate(-50%, -50%) rotate(var(--marker-angle, 0deg)) translateY(calc(-1 * var(--marker-radius))) rotate(calc(var(--marker-angle, 0deg) * -1));
    z-index: 2;
}
.time-clock__marker--minute { width: 1.95rem; height: 1.95rem; font-size: 0.8rem; --marker-radius: var(--minute-radius); }
.time-picker-option:hover,
.time-clock__marker:hover { border-color: rgba(16, 60, 93, 0.45); box-shadow: 0 20px 40px -24px rgba(16, 60, 93, 0.55); }
.time-picker-option.is-active,
.time-clock__marker.is-active {
    background: linear-gradient(140deg, rgba(16, 60, 93, 0.95), rgba(38, 88, 130, 0.95));
    color: #ffffff;
    border-color: transparent;
    box-shadow: 0 22px 40px -24px rgba(16, 60, 93, 0.65);
}
.time-picker-option:focus-visible,
.time-clock__marker:focus-visible { outline: 2px solid rgba(16, 60, 93, 0.55); outline-offset: 2px; }

.time-clock__panel {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    align-items: center;
}
.time-clock__digital {
    display: inline-flex;
    align-items: baseline;
    gap: 0.55rem;
}
.time-clock__digit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 3.25rem;
    padding: 0.35rem 0.65rem;
    border-radius: 0.75rem;
    border: 1px solid transparent;
    background: rgba(16, 60, 93, 0.08);
    font-size: 1.75rem;
    font-weight: 700;
    color: #103c5d;
    transition: all .2s ease;
}
.time-clock__digit.is-active,
.time-clock__digit:focus-visible { background: rgba(16, 60, 93, 0.16); border-color: rgba(16, 60, 93, 0.35); box-shadow: 0 12px 28px -24px rgba(16, 60, 93, 0.45); outline: none; }
.time-clock__colon { font-size: 1.6rem; font-weight: 600; color: rgba(16, 60, 93, 0.45); margin-top: 0.15rem; }
.time-clock__meridiem-text {
    align-self: center;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    background: rgba(16, 60, 93, 0.12);
    color: #103c5d;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.time-clock__meridiem { display: inline-flex; gap: 0.5rem; }
.time-clock__period-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.75rem;
    padding: 0.45rem 0.8rem;
    border-radius: 0.9rem;
    border: 1px solid rgba(16, 60, 93, 0.18);
    background: rgba(255, 255, 255, 0.95);
    color: #0f172a;
    font-weight: 600;
    transition: all .2s ease;
}
.time-clock__period-button:hover { border-color: rgba(16, 60, 93, 0.45); box-shadow: 0 12px 25px -20px rgba(16, 60, 93, 0.45); }
.time-clock__period-button.is-active { background: linear-gradient(140deg, rgba(16, 60, 93, 0.95), rgba(38, 88, 130, 0.95)); color: #ffffff; border-color: transparent; box-shadow: 0 22px 40px -24px rgba(16, 60, 93, 0.65); }
.time-clock__period-button:focus-visible { outline: 2px solid rgba(16, 60, 93, 0.55); outline-offset: 2px; }

.datetime-modal__status {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    font-size: 0.78rem;
    color: #475569;
}
.datetime-modal__warning { color: #dc2626; font-weight: 600; }
.datetime-modal__warning.hidden { display: none; }

.datetime-modal__footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}
.datetime-modal__footer button {
    border-radius: 0.9rem;
    padding: 0.6rem 1.1rem;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all .2s ease;
}
[data-datetime-cancel] {
    border: 1px solid rgba(148, 163, 184, 0.45);
    background: #fff;
    color: #475569;
}
[data-datetime-cancel]:hover { border-color: rgba(16, 60, 93, 0.55); color: #103c5d; }
[data-datetime-apply] {
    border: none;
    background: linear-gradient(135deg, rgba(16, 60, 93, 0.95), rgba(38, 88, 130, 0.95));
    color: #fff;
    box-shadow: 0 18px 40px -20px rgba(16, 60, 93, 0.6);
}
[data-datetime-apply]:hover { filter: brightness(1.05); }
[data-datetime-apply]:disabled { background: rgba(148, 163, 184, 0.55); box-shadow: none; cursor: not-allowed; }

@media (max-width: 640px) {
    .datetime-modal__content { flex-direction: column; }
}
@media (max-width: 480px) {
    .time-clock { --clock-size: min(15rem, calc(100vw - 2.4rem)); --hour-radius: calc(var(--clock-size) / 2 - 2.4rem); --minute-radius: calc(var(--clock-size) / 2 - 1.55rem); }
    .time-clock__hand--hour { height: calc(var(--clock-size) / 2 - 1.65rem); }
    .time-clock__hand--minute { height: calc(var(--clock-size) / 2 - 2.35rem); }
    .time-clock__marker { width: 2.05rem; height: 2.05rem; font-size: 0.82rem; }
    .time-clock__marker--minute { width: 1.85rem; height: 1.85rem; font-size: 0.76rem; }
    .datetime-calendar { padding: 0.9rem; }
    .datetime-calendar__day { width: 2.1rem; height: 2.1rem; font-size: 0.85rem; }
}
</style>
</head>
<body class="bg-gray-50 min-h-screen">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <?php include VIEWS_PATH . '/layouts/partials/header.php'; ?>

    <main id="main-content" class="max-w-5xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <?php include_flash_messages(); ?>

        <div class="mb-8">
            <?= render_breadcrumb([
                ['name' => 'Inicio', 'href' => Router::url('/')],
                ['name' => 'Mi Panel', 'href' => Router::url('panel')],
                ['name' => 'Mis campañas', 'href' => Router::url('mis-campanas')],
                ['name' => 'Editar campaña', 'href' => Router::url('campana/' . $campaignId . '/editar')],
            ]); ?>

            <h1 class="mt-4 text-3xl font-bold text-gray-900">Editar campaña</h1>
            <p class="mt-2 text-sm text-gray-600">Ajusta la información presentada a tu comunidad y al equipo académico. Los cambios se revisarán antes de volver a publicar la campaña.</p>
        </div>

        <form method="POST" action="<?= Router::url('campana/' . $campaignId . '/editar') ?>" enctype="multipart/form-data" class="space-y-8" novalidate>
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">

            <section class="bg-white shadow-soft rounded-3xl p-6 space-y-6">
                <header class="space-y-2">
                    <h2 class="text-xl font-semibold text-gray-900">Detalles principales</h2>
                    <p class="text-sm text-gray-600">Esta información aparecerá en la ficha pública de tu campaña.</p>
                </header>
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Título de la campaña</label>
                        <input id="title" name="title" type="text" maxlength="120" required value="<?= htmlspecialchars($old['title'] ?? '') ?>" class="mt-1 w-full rounded-md border <?= isset($formErrors['title']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                        <?php if (isset($formErrors['title'])): ?>
                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['title']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="goal_amount" class="block text-sm font-medium text-gray-700">Meta económica (CLP)</label>
                        <input id="goal_amount" name="goal_amount" type="number" min="1000" step="500" required value="<?= htmlspecialchars($amountValue) ?>" class="mt-1 w-full rounded-md border <?= isset($formErrors['goal_amount']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                        <?php if (isset($formErrors['goal_amount'])): ?>
                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['goal_amount']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700">Categoría</label>
                        <select id="category" name="category" required class="mt-1 w-full rounded-md border <?= isset($formErrors['category']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                            <option value="">Selecciona una categoría</option>
                            <?php foreach ($categoryOptions as $slug => $label): ?>
                                <option value="<?= htmlspecialchars($slug) ?>" <?= ($old['category'] ?? '') === $slug ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($formErrors['category'])): ?>
                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['category']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="md:col-span-2 space-y-2" data-end-datetime data-min-datetime="<?= htmlspecialchars($endDateMinimum); ?>" data-minute-step="<?= $timeStepMinutes; ?>">
                        <label class="block text-sm font-medium text-gray-700">Fecha y hora de término <span class="text-red-500">*</span></label>
                        <input
                            id="end_date_date"
                            name="end_date_date"
                            type="hidden"
                            value="<?= htmlspecialchars($endDateDateValue); ?>"
                            min="<?= htmlspecialchars($endDateDateMinAttribute); ?>"
                            data-end-date-picker
                            data-min-date="<?= htmlspecialchars($endDateMinDate); ?>"
                            data-min-time="<?= htmlspecialchars($endDateMinTime); ?>"
                        >
                        <input
                            id="end_date_time"
                            name="end_date_time"
                            type="hidden"
                            value="<?= htmlspecialchars($endDateTimeValue); ?>"
                            step="<?= $timeStepSeconds; ?>"
                            data-end-time-picker
                            data-default-time="<?= htmlspecialchars($endDateTimeValue); ?>"
                            data-min-time="<?= htmlspecialchars($endDateMinTime); ?>"
                        >
                        <button type="button" class="datetime-trigger" data-datetime-trigger>
                            <span class="datetime-trigger__label">Haz clic para seleccionar</span>
                            <span class="datetime-trigger__value" data-datetime-display><?= htmlspecialchars($endDateDisplayLabel); ?></span>
                        </button>
                        <p class="text-xs text-gray-500">Combina fecha y hora exactas antes de guardar los cambios.</p>
                        <input type="hidden" name="end_date" value="<?= htmlspecialchars($endDateHiddenValue); ?>" data-end-datetime-hidden>
                        <?php if (!empty($formErrors['end_date'])): ?>
                            <p class="text-sm text-red-600"><?= htmlspecialchars($formErrors['end_date']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <label for="short_description" class="block text-sm font-medium text-gray-700">Descripción breve</label>
                    <textarea id="short_description" name="short_description" rows="3" maxlength="400" required class="mt-1 w-full rounded-md border <?= isset($formErrors['short_description']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm"><?= htmlspecialchars($old['short_description'] ?? '') ?></textarea>
                    <?php if (isset($formErrors['short_description'])): ?>
                        <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['short_description']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Historia completa</label>
                    <textarea id="description" name="description" rows="8" required class="mt-1 w-full rounded-md border <?= isset($formErrors['description']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm"><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                    <?php if (isset($formErrors['description'])): ?>
                        <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['description']) ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="bg-white shadow-soft rounded-3xl p-6 space-y-6">
                <header class="space-y-2">
                    <h2 class="text-xl font-semibold text-gray-900">Beneficiarios y contacto</h2>
                    <p class="text-sm text-gray-600">Ayúdanos a validar la campaña proporcionando información clara del beneficiario.</p>
                </header>
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="beneficiary_name" class="block text-sm font-medium text-gray-700">Nombre del beneficiario</label>
                        <input id="beneficiary_name" name="beneficiary_name" type="text" required value="<?= htmlspecialchars($old['beneficiary_name'] ?? '') ?>" class="mt-1 w-full rounded-md border <?= isset($formErrors['beneficiary_name']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                        <?php if (isset($formErrors['beneficiary_name'])): ?>
                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['beneficiary_name']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="beneficiary_type" class="block text-sm font-medium text-gray-700">Tipo de beneficiario</label>
                        <select id="beneficiary_type" name="beneficiary_type" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500">
                            <option value="individual" <?= ($old['beneficiary_type'] ?? 'individual') === 'individual' ? 'selected' : '' ?>>Persona o familia</option>
                            <option value="organization" <?= ($old['beneficiary_type'] ?? 'individual') === 'organization' ? 'selected' : '' ?>>Organización</option>
                            <option value="community" <?= ($old['beneficiary_type'] ?? 'individual') === 'community' ? 'selected' : '' ?>>Comunidad</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="beneficiary_contact_text" class="block text-sm font-medium text-gray-700">Datos de contacto del beneficiario</label>
                    <textarea id="beneficiary_contact_text" name="beneficiary_contact_text" rows="3" placeholder="Teléfono, correo o datos de referencia" class="mt-1 w-full rounded-md border <?= isset($formErrors['beneficiary_contact']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm"><?= htmlspecialchars($beneficiaryContactValue) ?></textarea>
                    <?php if (isset($formErrors['beneficiary_contact'])): ?>
                        <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['beneficiary_contact']) ?></p>
                    <?php else: ?>
                        <p class="mt-1 text-xs text-gray-500">Esta información la revisará el equipo académico; no se mostrará públicamente.</p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700">Ciudad o comuna</label>
                    <input id="location" name="location" type="text" value="<?= htmlspecialchars($old['location'] ?? '') ?>" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500" placeholder="Ejemplo: Santiago, Región Metropolitana">
                </div>
            </section>

            <section class="bg-white shadow-soft rounded-3xl p-6 space-y-6">
                <header class="space-y-2">
                    <h2 class="text-xl font-semibold text-gray-900">Material de apoyo</h2>
                    <p class="text-sm text-gray-600">Actualiza la imagen principal o agrega evidencia adicional para reforzar la transparencia.</p>
                </header>
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="featured_image" class="block text-sm font-medium text-gray-700">Imagen principal</label>
                        <?php if ($coverPreview): ?>
                            <div class="mt-2 overflow-hidden rounded-lg border border-gray-200">
                                <img src="<?= htmlspecialchars($coverPreview) ?>" alt="Portada actual" class="h-40 w-full object-cover">
                            </div>
                        <?php endif; ?>
                        <input id="featured_image" name="featured_image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-copihue-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-copihue-700">
                        <?php if (isset($formErrors['featured_image'])): ?>
                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['featured_image']) ?></p>
                        <?php else: ?>
                            <p class="mt-1 text-xs text-gray-500">Formatos permitidos: JPG, PNG o WEBP. Máximo 5 MB.</p>
                        <?php endif; ?>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">Galería de imágenes</label>
                        <?php if (!empty($galleryItems)): ?>
                            <div class="grid grid-cols-3 gap-2">
                                <?php foreach ($galleryItems as $item): ?>
                                    <img src="<?= htmlspecialchars($item['url'] ?? '') ?>" alt="Imagen de galería" class="h-20 w-full rounded-md object-cover">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <input name="gallery_images[]" type="file" multiple accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-white file:px-4 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-100">
                        <?php if (isset($formErrors['gallery_images'])): ?>
                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['gallery_images']) ?></p>
                        <?php else: ?>
                            <p class="mt-1 text-xs text-gray-500">Puedes agregar hasta 5 imágenes adicionales (máx. 6 MB cada una).</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Documentos de respaldo</label>
                    <?php if (!empty($attachmentItems)): ?>
                        <ul class="mt-2 space-y-1 text-sm text-gray-600">
                            <?php foreach ($attachmentItems as $attachment): ?>
                                <li class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 11h10M7 15h6" />
                                    </svg>
                                    <span><?= htmlspecialchars($attachment['filename'] ?? basename($attachment['path'] ?? 'documento')) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <input name="supporting_files[]" type="file" multiple accept="application/pdf,image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-white file:px-4 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-100">
                    <?php if (isset($formErrors['supporting_files'])): ?>
                        <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['supporting_files']) ?></p>
                    <?php else: ?>
                        <p class="mt-1 text-xs text-gray-500">PDF o imágenes (hasta 8 MB). Adjunta presupuestos, cotizaciones u otros respaldos.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="video_url" class="block text-sm font-medium text-gray-700">Video de la campaña (opcional)</label>
                    <input id="video_url" name="video_url" type="url" value="<?= htmlspecialchars($old['video_url'] ?? '') ?>" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500" placeholder="https://www.youtube.com/watch?v=...">
                </div>

                <div class="flex items-center gap-2">
                    <input id="ai_generated" name="ai_generated" type="checkbox" value="1" <?= $isAiChecked ? 'checked' : '' ?> class="h-4 w-4 rounded border-gray-300 text-copihue-600 focus:ring-copihue-500">
                    <label for="ai_generated" class="text-sm text-gray-700">Marcar como campaña asistida por IA (para fines de transparencia interna)</label>
                </div>
            </section>

            <div class="flex items-center justify-between">
                <a href="<?= Router::url('mis-campanas') ?>" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:border-gray-400 hover:text-gray-900">Cancelar</a>
                <button type="submit" class="inline-flex items-center rounded-md bg-copihue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-copihue-700">
                    Guardar cambios
                </button>
            </div>
        </form>
    </main>

    <?php include VIEWS_PATH . '/layouts/partials/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.lucatonInitializeEndDatePickers) {
                window.lucatonInitializeEndDatePickers = function () {
                    const modalManager = window.__lucatonDatetimeModalManager || (window.__lucatonDatetimeModalManager = { instance: null });
                    const modalTemplate = document.querySelector('template[data-datetime-modal-template]');
                    if (!modalTemplate) {
                        console.warn('[Lucatón] No se encontró la plantilla del selector de fecha y hora. Se utilizará el selector nativo del navegador como respaldo.');
                    }

                    const closeActiveModal = (options = {}) => {
                        if (modalManager.instance && typeof modalManager.instance.close === 'function') {
                            modalManager.instance.close({ restoreFocus: false, ...options });
                        }
                    };

                    document.querySelectorAll('[data-end-datetime]').forEach((wrapper) => {
                        const hidden = wrapper.querySelector('[data-end-datetime-hidden]');
                        const dateInput = wrapper.querySelector('[data-end-date-picker]');
                        const timeInput = wrapper.querySelector('[data-end-time-picker]');
                        const trigger = wrapper.querySelector('[data-datetime-trigger]');
                        const display = wrapper.querySelector('[data-datetime-display]');

                        if (!hidden || !dateInput || !timeInput || !trigger) {
                            return;
                        }

                        const stepSource = parseInt(timeInput.getAttribute('step') || wrapper.getAttribute('data-minute-step') || '300', 10);
                        const timeStepSeconds = Number.isNaN(stepSource) ? 300 : stepSource;
                        const minuteStep = Math.max(1, Math.round(timeStepSeconds / 60));
                        const minDate = dateInput.dataset.minDate || dateInput.getAttribute('min') || '';
                        const minTime = timeInput.dataset.minTime || timeInput.getAttribute('min') || '';
                        const defaultTime = timeInput.dataset.defaultTime || timeInput.value || minTime || '12:00';

                        const pad = (value) => String(value).padStart(2, '0');
                        const formatTimeValue = (hour24, minute) => `${pad(Math.min(23, Math.max(0, hour24)))}:${pad(Math.min(59, Math.max(0, minute)))}`;
                        const convert24ToState = (hour24, minute) => {
                            const safeHour = Math.max(0, Math.min(23, hour24));
                            const safeMinute = Math.max(0, Math.min(59, minute));
                            let hour12 = safeHour % 12;
                            if (hour12 === 0) {
                                hour12 = 12;
                            }
                            return {
                                hour12,
                                minute: safeMinute,
                                meridiem: safeHour >= 12 ? 'PM' : 'AM',
                            };
                        };
                        const convertStateTo24 = (current) => {
                            const minuteValue = Math.max(0, Math.min(59, current.minute));
                            let hour24 = current.hour12 % 12;
                            if (current.meridiem === 'PM') {
                                hour24 += 12;
                            }
                            if (current.meridiem === 'AM' && current.hour12 === 12) {
                                hour24 = 0;
                            }
                            if (current.meridiem === 'PM' && current.hour12 === 12) {
                                hour24 = 12;
                            }
                            return { hour24, minute: minuteValue };
                        };
                        const parseTimeValue = (value) => {
                            const match = /^(\d{1,2}):([0-5]\d)$/.exec(value || '');
                            let hourCandidate;
                            let minuteCandidate;
                            if (match) {
                                hourCandidate = Math.min(23, Math.max(0, parseInt(match[1], 10)));
                                minuteCandidate = Math.min(59, Math.max(0, parseInt(match[2], 10)));
                            } else {
                                const [fallbackHour, fallbackMinute] = defaultTime.split(':').map((part) => parseInt(part, 10) || 0);
                                hourCandidate = Math.min(23, Math.max(0, fallbackHour));
                                minuteCandidate = Math.min(59, Math.max(0, fallbackMinute));
                            }
                            if (minuteStep > 1) {
                                minuteCandidate = Math.round(minuteCandidate / minuteStep) * minuteStep;
                                if (minuteCandidate >= 60) {
                                    minuteCandidate = Math.max(0, 60 - minuteStep);
                                }
                            }
                            return convert24ToState(hourCandidate, minuteCandidate);
                        };
                        const formatLabel = (current) => `${Math.max(1, Math.min(12, current.hour12))}:${pad(current.minute)} ${current.meridiem}`;
                        const compareTimes = (timeA, timeB) => {
                            const parse = (value) => {
                                const [h, m] = (value || '').split(':').map((part) => parseInt(part, 10) || 0);
                                return h * 60 + m;
                            };
                            return parse(timeA) - parse(timeB);
                        };
                        const formatDateHuman = (value) => {
                            if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
                                return '';
                            }
                            const [year, month, day] = value.split('-');
                            return `${day}/${month}/${year}`;
                        };
                        const formatDisplay = (dateValue, timeValue) => {
                            if (!dateValue || !timeValue) {
                                return 'Selecciona fecha y hora';
                            }
                            const [hour, minute] = timeValue.split(':').map((part) => parseInt(part, 10) || 0);
                            const state = convert24ToState(hour, minute);
                            return `${formatDateHuman(dateValue)} · ${formatLabel(state)}`;
                        };

                        const ensureTimeWithinBounds = () => {
                            if (!minDate || !minTime) {
                                timeInput.min = '00:00';
                                return;
                            }
                            if (dateInput.value === minDate) {
                                timeInput.min = minTime;
                                if (timeInput.value && compareTimes(timeInput.value, minTime) < 0) {
                                    timeInput.value = minTime;
                                }
                            } else {
                                timeInput.min = '00:00';
                            }
                        };

                        const syncHidden = (options = {}) => {
                            const { silent = false } = options;
                            if (!dateInput.value) {
                                hidden.value = '';
                            } else {
                                const timeValue = timeInput.value || defaultTime;
                                hidden.value = `${dateInput.value}T${timeValue}`;
                            }
                            if (!silent) {
                                hidden.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        };

                        const updateDisplayText = () => {
                            if (!display) {
                                return;
                            }
                            if (!dateInput.value || !timeInput.value) {
                                display.textContent = 'Selecciona fecha y hora';
                                return;
                            }
                            display.textContent = formatDisplay(dateInput.value, timeInput.value);
                        };

                        ensureTimeWithinBounds();
                        syncHidden({ silent: true });
                        updateDisplayText();

                        const fallbackNativePicker = () => {
                            const input = document.createElement('input');
                            input.type = 'datetime-local';
                            input.style.position = 'fixed';
                            input.style.opacity = '0';
                            input.style.pointerEvents = 'none';
                            input.style.width = '0';
                            input.style.height = '0';
                            const initialValue = hidden.value || (dateInput.value && timeInput.value ? `${dateInput.value}T${timeInput.value}` : '');
                            if (initialValue) {
                                input.value = initialValue;
                            }
                            if (minDate) {
                                input.min = `${minDate}T${minTime || '00:00'}`;
                            }
                            document.body.appendChild(input);
                            const cleanup = () => {
                                input.remove();
                            };
                            input.addEventListener('change', () => {
                                if (input.value) {
                                    const [datePart, timePartRaw] = input.value.split('T');
                                    if (datePart) {
                                        dateInput.value = datePart;
                                    }
                                    if (timePartRaw) {
                                        timeInput.value = timePartRaw.slice(0, 5);
                                    }
                                    ensureTimeWithinBounds();
                                    syncHidden();
                                    updateDisplayText();
                                    dateInput.dispatchEvent(new Event('change', { bubbles: true }));
                                    timeInput.dispatchEvent(new Event('change', { bubbles: true }));
                                }
                                cleanup();
                            }, { once: true });
                            input.addEventListener('blur', cleanup, { once: true });
                            input.focus({ preventScroll: true });
                            if (typeof input.showPicker === 'function') {
                                input.showPicker();
                            } else {
                                input.click();
                            }
                        };

                        const openModal = () => {
                            if (!modalTemplate) {
                                fallbackNativePicker();
                                return;
                            }
                            const fragment = modalTemplate.content.cloneNode(true);
                            const overlay = fragment.querySelector('[data-datetime-overlay]');
                            if (!overlay) {
                                fallbackNativePicker();
                                return;
                            }
                            overlay.hidden = false;
                            overlay.removeAttribute('hidden');
                            const modal = overlay.querySelector('[data-modal]');
                            const dateField = overlay.querySelector('[data-modal-date]');
                            if (!modal || !dateField) {
                                fallbackNativePicker();
                                return;
                            }

                            const uniqueId = `datetime-modal-date-${Math.random().toString(36).slice(2, 10)}`;
                            dateField.id = uniqueId;
                            const hourDial = overlay.querySelector('[data-modal-hours]');
                            const minuteDial = overlay.querySelector('[data-modal-minutes]');
                            const hourButtons = Array.from(overlay.querySelectorAll('[data-hour-option]'));
                            const minuteButtons = Array.from(overlay.querySelectorAll('[data-minute-option]'));
                            const meridiemButtons = Array.from(overlay.querySelectorAll('[data-modal-meridiem]'));
                            const phaseTriggers = Array.from(overlay.querySelectorAll('[data-phase-trigger]'));
                            const digitalHour = overlay.querySelector('[data-digital-hour]');
                            const digitalMinute = overlay.querySelector('[data-digital-minute]');
                            const digitalMeridiem = overlay.querySelector('[data-digital-meridiem]');
                            const clock = overlay.querySelector('[data-clock]');
                            const hourHand = overlay.querySelector('[data-clock-hand="hour"]');
                            const minuteHand = overlay.querySelector('[data-clock-hand="minute"]');
                            const calendarMonthLabel = overlay.querySelector('[data-calendar-month]');
                            const calendarYearLabel = overlay.querySelector('[data-calendar-year]');
                            const calendarPrev = overlay.querySelector('[data-calendar-prev]');
                            const calendarNext = overlay.querySelector('[data-calendar-next]');
                            const calendarGrid = overlay.querySelector('[data-calendar-grid]');
                            const summaryEl = overlay.querySelector('[data-modal-summary]');
                            const warningEl = overlay.querySelector('[data-modal-warning]');
                            const applyButton = overlay.querySelector('[data-datetime-apply]');
                            const cancelButton = overlay.querySelector('[data-datetime-cancel]');
                            const closeButton = overlay.querySelector('[data-datetime-close]');
                            const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

                            closeActiveModal({ restoreFocus: false });

                            const parseISODate = (value) => {
                                if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
                                    return null;
                                }
                                const [yyyy, mm, dd] = value.split('-').map((part) => parseInt(part, 10));
                                const candidate = new Date(yyyy, (mm || 1) - 1, dd || 1);
                                if (Number.isNaN(candidate.getTime())) {
                                    return null;
                                }
                                return candidate;
                            };
                            const formatISODate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
                            const normalizeDate = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate());
                            const isSameDay = (dateA, dateB) => {
                                if (!(dateA instanceof Date) || !(dateB instanceof Date)) {
                                    return false;
                                }
                                return dateA.getFullYear() === dateB.getFullYear()
                                    && dateA.getMonth() === dateB.getMonth()
                                    && dateA.getDate() === dateB.getDate();
                            };
                            const capitalize = (text) => (!text ? '' : text.charAt(0).toUpperCase() + text.slice(1));
                            const monthNames = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

                            if (minDate) {
                                dateField.min = minDate;
                            }
                            const maxDateAttr = dateInput.getAttribute('max');
                            if (maxDateAttr) {
                                dateField.max = maxDateAttr;
                            }

                            let initialDate = dateInput.value || '';
                            if (!initialDate && hidden.value) {
                                initialDate = hidden.value.split('T')[0] || '';
                            }
                            if (!initialDate && minDate) {
                                initialDate = minDate;
                            }
                            if (initialDate) {
                                dateField.value = initialDate;
                            }

                            const minDateObj = minDate ? parseISODate(minDate) : null;
                            const today = normalizeDate(new Date());

                            let selectedDate = parseISODate(dateField.value) || (minDateObj ? new Date(minDateObj) : today);
                            if (!(selectedDate instanceof Date) || Number.isNaN(selectedDate.getTime())) {
                                selectedDate = today;
                            }
                            selectedDate = normalizeDate(selectedDate);
                            if (minDateObj && selectedDate < minDateObj) {
                                selectedDate = new Date(minDateObj);
                                dateField.value = formatISODate(selectedDate);
                            }

                            let viewYear = selectedDate.getFullYear();
                            let viewMonth = selectedDate.getMonth();

                            const focusSelectedDay = () => {
                                if (!calendarGrid) {
                                    return;
                                }
                                requestAnimationFrame(() => {
                                    const target = calendarGrid.querySelector('.datetime-calendar__day.is-selected');
                                    if (target instanceof HTMLElement) {
                                        target.focus({ preventScroll: true });
                                    }
                                });
                            };

                            const renderCalendar = () => {
                                if (!calendarGrid) {
                                    return;
                                }
                                const currentMonthName = capitalize(monthNames[viewMonth] || '');
                                if (calendarMonthLabel) {
                                    calendarMonthLabel.textContent = currentMonthName;
                                }
                                if (calendarYearLabel) {
                                    calendarYearLabel.textContent = String(viewYear);
                                }
                                const fragment = document.createDocumentFragment();
                                const startOfMonth = new Date(viewYear, viewMonth, 1);
                                const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
                                const daysInPreviousMonth = new Date(viewYear, viewMonth, 0).getDate();
                                const startOffset = (startOfMonth.getDay() + 6) % 7;
                                const totalCells = 42;
                                for (let cell = 0; cell < totalCells; cell += 1) {
                                    const dayNumber = cell - startOffset + 1;
                                    let cellDate;
                                    let isCurrentMonth = true;
                                    if (dayNumber < 1) {
                                        cellDate = new Date(viewYear, viewMonth - 1, daysInPreviousMonth + dayNumber);
                                        isCurrentMonth = false;
                                    } else if (dayNumber > daysInMonth) {
                                        cellDate = new Date(viewYear, viewMonth, dayNumber);
                                        isCurrentMonth = false;
                                    } else {
                                        cellDate = new Date(viewYear, viewMonth, dayNumber);
                                    }
                                    const button = document.createElement('button');
                                    button.type = 'button';
                                    button.className = 'datetime-calendar__day';
                                    const iso = formatISODate(cellDate);
                                    button.textContent = String(cellDate.getDate());
                                    button.setAttribute('data-calendar-day', iso);
                                    button.setAttribute('role', 'radio');
                                    if (!isCurrentMonth) {
                                        button.classList.add('is-muted');
                                    }
                                    if (isSameDay(cellDate, today)) {
                                        button.classList.add('is-today');
                                    }
                                    const isBeforeMin = minDateObj && cellDate < minDateObj;
                                    if (isBeforeMin) {
                                        button.disabled = true;
                                        button.classList.add('is-disabled');
                                    }
                                    if (isSameDay(cellDate, selectedDate)) {
                                        button.classList.add('is-selected');
                                        button.setAttribute('aria-checked', 'true');
                                        button.tabIndex = 0;
                                    } else {
                                        button.setAttribute('aria-checked', 'false');
                                        button.tabIndex = -1;
                                    }
                                    fragment.appendChild(button);
                                }
                                calendarGrid.innerHTML = '';
                                calendarGrid.appendChild(fragment);
                                if (calendarPrev) {
                                    const prevMonthLast = new Date(viewYear, viewMonth, 0);
                                    calendarPrev.disabled = !!minDateObj && prevMonthLast < minDateObj;
                                }
                            };

                            const updateDateField = (date, options = {}) => {
                                const normalized = normalizeDate(date);
                                if (minDateObj && normalized < minDateObj) {
                                    return;
                                }
                                selectedDate = normalized;
                                dateField.value = formatISODate(selectedDate);
                                if (!options.keepView) {
                                    viewYear = selectedDate.getFullYear();
                                    viewMonth = selectedDate.getMonth();
                                }
                                renderCalendar();
                                if (options.emit !== false) {
                                    dateField.dispatchEvent(new Event('input', { bubbles: true }));
                                    dateField.dispatchEvent(new Event('change', { bubbles: true }));
                                }
                                if (options.focusSelected) {
                                    focusSelectedDay();
                                }
                            };

                            const changeMonth = (delta) => {
                                const tentative = new Date(viewYear, viewMonth + delta, 1);
                                if (minDateObj) {
                                    const tentativeLast = new Date(tentative.getFullYear(), tentative.getMonth() + 1, 0);
                                    if (tentativeLast < minDateObj) {
                                        viewYear = minDateObj.getFullYear();
                                        viewMonth = minDateObj.getMonth();
                                        renderCalendar();
                                        focusSelectedDay();
                                        return;
                                    }
                                }
                                viewYear = tentative.getFullYear();
                                viewMonth = tentative.getMonth();
                                renderCalendar();
                                focusSelectedDay();
                            };

                            const decorateClockMarkers = () => {
                                hourButtons.forEach((button) => {
                                    const value = parseInt(button.dataset.hourOption || '0', 10) || 0;
                                    const normalized = value % 12;
                                    const angle = normalized * 30;
                                    button.classList.add('time-clock__marker', 'time-clock__marker--hour');
                                    button.style.setProperty('--marker-angle', `${angle}deg`);
                                    button.setAttribute('tabindex', '0');
                                    button.setAttribute('role', 'radio');
                                    button.setAttribute('aria-checked', 'false');
                                    button.setAttribute('aria-label', `${value === 12 ? 12 : normalized || 12} en punto`);
                                });
                                minuteButtons.forEach((button) => {
                                    const value = parseInt(button.dataset.minuteOption || '0', 10);
                                    if (Number.isNaN(value)) {
                                        return;
                                    }
                                    const angle = (value % 60) * 6;
                                    button.classList.add('time-clock__marker', 'time-clock__marker--minute');
                                    button.style.setProperty('--marker-angle', `${angle}deg`);
                                    button.setAttribute('tabindex', '0');
                                    button.setAttribute('role', 'radio');
                                    button.setAttribute('aria-checked', 'false');
                                    const labelValue = value === 0 ? '00' : pad(value);
                                    button.setAttribute('aria-label', `${labelValue} minutos`);
                                });
                                hourDial?.setAttribute('role', 'radiogroup');
                                hourDial?.setAttribute('aria-label', 'Selecciona hora');
                                minuteDial?.setAttribute('role', 'radiogroup');
                                minuteDial?.setAttribute('aria-label', 'Selecciona minutos');
                            };

                            let state = parseTimeValue(timeInput.value || defaultTime);
                            let currentPhase = 'hour';
                            const defaultSummary = summaryEl ? summaryEl.textContent : 'Selecciona fecha y hora para continuar.';
                            const previouslyFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
                            let closing = false;

                            decorateClockMarkers();
                            renderCalendar();

                            calendarPrev?.addEventListener('click', (event) => {
                                event.preventDefault();
                                changeMonth(-1);
                            });
                            calendarNext?.addEventListener('click', (event) => {
                                event.preventDefault();
                                changeMonth(1);
                            });
                            if (calendarGrid) {
                                calendarGrid.addEventListener('click', (event) => {
                                    const target = event.target instanceof HTMLElement ? event.target.closest('[data-calendar-day]') : null;
                                    if (!target || target.disabled) {
                                        return;
                                    }
                                    const iso = target.getAttribute('data-calendar-day');
                                    const parsed = parseISODate(iso);
                                    if (!parsed) {
                                        return;
                                    }
                                    updateDateField(parsed, { focusSelected: true });
                                });
                                calendarGrid.addEventListener('keydown', (event) => {
                                    const key = event.key;
                                    if (!['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(key)) {
                                        return;
                                    }
                                    const active = event.target instanceof HTMLElement ? event.target.closest('[data-calendar-day]') : null;
                                    if (!active) {
                                        return;
                                    }
                                    event.preventDefault();
                                    const iso = active.getAttribute('data-calendar-day');
                                    const base = iso ? parseISODate(iso) : null;
                                    if (!base) {
                                        return;
                                    }
                                    let offset = 0;
                                    if (key === 'ArrowUp') offset = -7;
                                    if (key === 'ArrowDown') offset = 7;
                                    if (key === 'ArrowLeft') offset = -1;
                                    if (key === 'ArrowRight') offset = 1;
                                    const nextDate = new Date(base.getFullYear(), base.getMonth(), base.getDate() + offset);
                                    if (minDateObj && nextDate < minDateObj) {
                                        updateDateField(minDateObj, { focusSelected: true });
                                        return;
                                    }
                                    updateDateField(nextDate, { focusSelected: true });
                                });
                            }

                            const updateDigitalDisplay = () => {
                                if (digitalHour) {
                                    digitalHour.textContent = pad(state.hour12);
                                }
                                if (digitalMinute) {
                                    digitalMinute.textContent = pad(state.minute);
                                }
                                if (digitalMeridiem) {
                                    digitalMeridiem.textContent = state.meridiem;
                                }
                            };

                            const updateClockHands = () => {
                                const minuteAngle = (state.minute % 60) * 6;
                                const hourAngle = ((state.hour12 % 12) + (state.minute / 60)) * 30;
                                if (minuteHand) {
                                    minuteHand.style.setProperty('--pointer-angle', `${minuteAngle}deg`);
                                }
                                if (hourHand) {
                                    hourHand.style.setProperty('--pointer-angle', `${hourAngle}deg`);
                                }
                            };

                            const updateOptionHighlights = () => {
                                hourButtons.forEach((button) => {
                                    const buttonHour = parseInt(button.dataset.hourOption || '0', 10);
                                    const isActive = buttonHour === state.hour12;
                                    button.classList.toggle('is-active', isActive);
                                    button.setAttribute('aria-checked', isActive ? 'true' : 'false');
                                });
                                minuteButtons.forEach((button) => {
                                    const buttonMinute = parseInt(button.dataset.minuteOption || '0', 10);
                                    const isActive = buttonMinute === state.minute;
                                    button.classList.toggle('is-active', isActive);
                                    button.setAttribute('aria-checked', isActive ? 'true' : 'false');
                                });
                                meridiemButtons.forEach((button) => {
                                    const value = (button.dataset.modalMeridiem || '').toUpperCase();
                                    const isActive = value === state.meridiem;
                                    button.classList.toggle('is-active', isActive);
                                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                                });
                                updateDigitalDisplay();
                                updateClockHands();
                            };

                            const setPhase = (phase, options = {}) => {
                                if (phase !== 'hour' && phase !== 'minute') {
                                    return;
                                }
                                const { focus = false } = options;
                                currentPhase = phase;
                                if (clock) {
                                    clock.setAttribute('data-phase', phase);
                                }
                                phaseTriggers.forEach((trigger) => {
                                    const triggerPhase = (trigger.dataset.phaseTrigger || '').toLowerCase() === 'minute' ? 'minute' : 'hour';
                                    const isActive = triggerPhase === phase;
                                    trigger.classList.toggle('is-active', isActive);
                                    trigger.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                                });
                                if (focus) {
                                    const candidates = phase === 'minute' ? minuteButtons : hourButtons;
                                    const active = candidates.find((button) => button.classList.contains('is-active')) || candidates[0];
                                    active?.focus({ preventScroll: true });
                                }
                                updateClockHands();
                            };

                            const updateSummaryAndValidation = () => {
                                const dateValue = dateField.value;
                                const { hour24, minute } = convertStateTo24(state);
                                const timeValue = formatTimeValue(hour24, minute);
                                if (summaryEl) {
                                    if (dateValue) {
                                        summaryEl.textContent = `Termina el ${formatDateHuman(dateValue)} · ${formatLabel(state)}.`;
                                    } else {
                                        summaryEl.textContent = defaultSummary;
                                    }
                                }
                                let warningMessage = '';
                                let valid = !!dateValue;
                                if (!dateValue) {
                                    warningMessage = 'Selecciona una fecha válida.';
                                    valid = false;
                                } else if (minDate && dateValue < minDate) {
                                    warningMessage = `Debe ser el ${formatDateHuman(minDate)} o posterior.`;
                                    valid = false;
                                } else if (minDate && dateValue === minDate && minTime && compareTimes(timeValue, minTime) < 0) {
                                    const [minHour, minMinute] = minTime.split(':').map((part) => parseInt(part, 10) || 0);
                                    warningMessage = `Debe ser igual o posterior a ${formatLabel(convert24ToState(minHour, minMinute))}.`;
                                    valid = false;
                                }
                                if (warningEl) {
                                    if (warningMessage) {
                                        warningEl.textContent = warningMessage;
                                        warningEl.classList.remove('hidden');
                                    } else {
                                        warningEl.textContent = '';
                                        warningEl.classList.add('hidden');
                                    }
                                }
                                if (applyButton) {
                                    applyButton.disabled = !valid;
                                }
                            };

                            const getFocusableElements = () => Array.from(overlay.querySelectorAll(focusableSelector)).filter((element) => {
                                if (element.hasAttribute('disabled')) {
                                    return false;
                                }
                                if (element.getAttribute('tabindex') === '-1') {
                                    return false;
                                }
                                if (element.closest('[hidden]')) {
                                    return false;
                                }
                                return true;
                            });

                            const handleKeydown = (event) => {
                                if (event.key === 'Escape') {
                                    event.preventDefault();
                                    closeModal();
                                    return;
                                }
                                if (event.key !== 'Tab') {
                                    return;
                                }
                                const focusable = getFocusableElements();
                                if (focusable.length === 0) {
                                    return;
                                }
                                const first = focusable[0];
                                const last = focusable[focusable.length - 1];
                                if (event.shiftKey) {
                                    if (document.activeElement === first) {
                                        event.preventDefault();
                                        last.focus();
                                    }
                                } else if (document.activeElement === last) {
                                    event.preventDefault();
                                    first.focus();
                                }
                            };

                            const handleOverlayPointer = (event) => {
                                if (event.target === overlay) {
                                    closeModal();
                                }
                            };

                            function closeModal(options = {}) {
                                if (closing) {
                                    return;
                                }
                                closing = true;
                                const { restoreFocus = true } = options;
                                overlay.removeEventListener('keydown', handleKeydown);
                                overlay.removeEventListener('pointerdown', handleOverlayPointer);
                                overlay.classList.remove('is-open');
                                const finalize = () => {
                                    overlay.removeEventListener('transitionend', finalize);
                                    overlay.remove();
                                };
                                overlay.addEventListener('transitionend', finalize, { once: true });
                                setTimeout(finalize, 240);
                                document.body.classList.remove('datetime-modal-open');
                                if (modalManager.instance && modalManager.instance.overlay === overlay) {
                                    modalManager.instance = null;
                                }
                                trigger.setAttribute('aria-expanded', 'false');
                                if (restoreFocus && previouslyFocused) {
                                    previouslyFocused.focus({ preventScroll: true });
                                }
                            }

                            hourButtons.forEach((button) => {
                                button.addEventListener('click', () => {
                                    const hourCandidate = parseInt(button.dataset.hourOption || '0', 10);
                                    if (Number.isNaN(hourCandidate) || hourCandidate < 1 || hourCandidate > 12) {
                                        return;
                                    }
                                    state = { ...state, hour12: hourCandidate };
                                    updateOptionHighlights();
                                    updateSummaryAndValidation();
                                    setPhase('minute', { focus: true });
                                });
                            });

                            minuteButtons.forEach((button) => {
                                button.addEventListener('click', () => {
                                    const minuteCandidate = parseInt(button.dataset.minuteOption || '0', 10);
                                    if (Number.isNaN(minuteCandidate)) {
                                        return;
                                    }
                                    state = { ...state, minute: Math.max(0, Math.min(59, minuteCandidate)) };
                                    updateOptionHighlights();
                                    updateSummaryAndValidation();
                                });
                            });

                            meridiemButtons.forEach((button) => {
                                button.addEventListener('click', () => {
                                    const meridiemCandidate = (button.dataset.modalMeridiem || '').toUpperCase();
                                    if (meridiemCandidate !== 'AM' && meridiemCandidate !== 'PM') {
                                        return;
                                    }
                                    state = { ...state, meridiem: meridiemCandidate };
                                    updateOptionHighlights();
                                    updateSummaryAndValidation();
                                });
                            });

                            phaseTriggers.forEach((triggerButton) => {
                                triggerButton.addEventListener('click', (event) => {
                                    event.preventDefault();
                                    const phase = (triggerButton.dataset.phaseTrigger || '').toLowerCase() === 'minute' ? 'minute' : 'hour';
                                    setPhase(phase, { focus: true });
                                });
                            });

                            const handleDateInput = () => updateSummaryAndValidation();
                            dateField.addEventListener('change', handleDateInput);
                            dateField.addEventListener('input', handleDateInput);

                            overlay.addEventListener('keydown', handleKeydown);
                            overlay.addEventListener('pointerdown', handleOverlayPointer);

                            cancelButton?.addEventListener('click', (event) => {
                                event.preventDefault();
                                closeModal();
                            });
                            closeButton?.addEventListener('click', (event) => {
                                event.preventDefault();
                                closeModal();
                            });

                            if (applyButton) {
                                applyButton.addEventListener('click', () => {
                                    if (applyButton.disabled) {
                                        return;
                                    }
                                    const dateValue = dateField.value;
                                    const { hour24, minute } = convertStateTo24(state);
                                    const timeValue = formatTimeValue(hour24, minute);
                                    dateInput.value = dateValue;
                                    timeInput.value = timeValue;
                                    ensureTimeWithinBounds();
                                    syncHidden();
                                    updateDisplayText();
                                    dateInput.dispatchEvent(new Event('change', { bubbles: true }));
                                    timeInput.dispatchEvent(new Event('change', { bubbles: true }));
                                    closeModal();
                                });
                            }

                            document.body.appendChild(overlay);
                            document.body.classList.add('datetime-modal-open');
                            trigger.setAttribute('aria-expanded', 'true');
                            requestAnimationFrame(() => overlay.classList.add('is-open'));
                            modal.focus({ preventScroll: true });
                            setPhase('hour');
                            updateOptionHighlights();
                            updateSummaryAndValidation();
                            focusSelectedDay();
                            modalManager.instance = { overlay, close: closeModal };
                        };

                        trigger.setAttribute('aria-haspopup', 'dialog');
                        trigger.setAttribute('aria-expanded', 'false');
                        trigger.addEventListener('click', (event) => {
                            event.preventDefault();
                            openModal();
                        });

                        dateInput.addEventListener('change', () => {
                            ensureTimeWithinBounds();
                            syncHidden();
                            updateDisplayText();
                        });
                        dateInput.addEventListener('input', () => {
                            ensureTimeWithinBounds();
                            syncHidden({ silent: true });
                            updateDisplayText();
                        });
                        timeInput.addEventListener('change', () => {
                            syncHidden();
                            updateDisplayText();
                        });
                        timeInput.addEventListener('input', () => {
                            syncHidden({ silent: true });
                            updateDisplayText();
                        });
                        timeInput.addEventListener('blur', () => syncHidden({ silent: true }));
                    });
                };
            }
            window.lucatonInitializeEndDatePickers();
        });
    </script>
</body>
</html>
