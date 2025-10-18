<?php
require_once __DIR__ . '/../components/forms.php';
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$categories = $categories ?? [];
$categoryOptions = array_filter($categories, static function ($key) {
    return $key !== '';
}, ARRAY_FILTER_USE_KEY);
$old = $_SESSION['old_campaign_form'] ?? [];
unset($_SESSION['old_campaign_form']);

$formErrors = $_SESSION['campaign_form_errors'] ?? [];
unset($_SESSION['campaign_form_errors']);

$draft_media = $draft_media ?? ['cover' => null, 'gallery' => [], 'attachments' => []];
$existing_cover_url = !empty($draft_media['cover']) ? CampaignMediaUploadService::normalizePublicUrl($draft_media['cover']) : null;
$existing_gallery_media = $draft_media['gallery'] ?? [];
$existing_attachments_media = $draft_media['attachments'] ?? [];

$page_title = $page_title ?? 'Crear nueva campaña';
$meta_description = $meta_description ?? 'Crea una campaña en Lucatón con verificación, asistencia de IA y herramientas de transparencia.';
$meta_robots = 'noindex, nofollow';
$current_page = $current_page ?? 'create-campaign';
$ai_improve_endpoint = parse_url(Router::url('api/ai/improve-text'), PHP_URL_PATH) ?: '/api/ai/improve-text';

$goalAmountRaw = $old['goal_amount_input'] ?? '';
if ($goalAmountRaw === '' && !empty($old['goal_amount'])) {
    $goalAmountRaw = (string)$old['goal_amount'];
}
$goalAmountFormatted = $goalAmountRaw !== '' ? number_format((int)$goalAmountRaw, 0, ',', '.') : '';

$userProfile = $userProfile ?? [];
$beneficiaryPhone = $old['beneficiary_phone'] ?? '';
$profilePhone = trim((string)($userProfile['phone'] ?? ''));
if ($beneficiaryPhone === '' && $profilePhone !== '') {
    $beneficiaryPhone = $profilePhone;
}
$beneficiaryEmail = $old['beneficiary_email'] ?? '';
$campaignLocation = $old['location'] ?? '';
if (($campaignLocation === '' || $campaignLocation === null) && !empty($userProfile['location'])) {
    $campaignLocation = $userProfile['location'];
}
$endDatePrefill = $old['end_date_input'] ?? '';
if ($endDatePrefill === '' && !empty($old['end_date'])) {
    $timestampPrefill = strtotime((string)$old['end_date']);
    if ($timestampPrefill !== false) {
        $endDatePrefill = date('Y-m-d\TH:i', $timestampPrefill);
    }
}
$endDateMinimumDateTime = new DateTime('+1 hour');
$endDateMinimum = $endDateMinimumDateTime->format('Y-m-d\TH:i');
$endDateMinDate = $endDateMinimumDateTime->format('Y-m-d');
$endDateMinTime = $endDateMinimumDateTime->format('H:i');
$endDateDateValue = '';
$endDateTimeValue = '';
if ($endDatePrefill !== '') {
    $prefillTimestamp = strtotime($endDatePrefill);
    if ($prefillTimestamp !== false) {
        $endDateDateValue = date('Y-m-d', $prefillTimestamp);
        $endDateTimeValue = date('H:i', $prefillTimestamp);
    }
}
if ($endDateDateValue === '') {
    $endDateDateValue = $endDateMinDate;
}
if ($endDateTimeValue === '') {
    $endDateTimeValue = $endDateMinTime;
}
$endDateHiddenValue = $endDatePrefill !== '' ? $endDatePrefill : ($endDateDateValue . 'T' . $endDateTimeValue);
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

$wizard_steps = [
    1 => [
        'title' => 'Fundamentos',
        'summary' => 'Cuenta el propósito de tu campaña.'
    ],
    2 => [
        'title' => 'Meta y plazos',
        'summary' => 'Define montos y fechas realistas.'
    ],
    3 => [
        'title' => 'Beneficiarios',
        'summary' => 'Señala a quién apoyas y cómo validar.'
    ],
    4 => [
        'title' => 'Evidencias',
        'summary' => 'Sube una imagen y recursos de apoyo.'
    ],
];

$additional_head = ($additional_head ?? '') . <<<'HTML'
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
HTML;

$additional_scripts = ($additional_scripts ?? '') . <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-wizard-form]');
    if (!form) return;

    const panels = Array.from(form.querySelectorAll('[data-step]'));
    const totalSteps = panels.length;
    const currentInput = form.querySelector('[data-wizard-current]');
    const prevButton = form.querySelector('[data-wizard="prev"]');
    const nextButton = form.querySelector('[data-wizard="next"]');
    const submitButton = form.querySelector('[data-wizard="submit"]');
    const progressButtons = Array.from(document.querySelectorAll('[data-wizard-step]'));
    const summary = document.querySelector('[data-wizard-summary]');
    const indicator = document.querySelector('[data-wizard-indicator]');
    const anchor = document.querySelector('[data-wizard-anchor]');
    const aiEndpoint = form.dataset.aiEndpoint || '';
    const aiEndpointUrl = aiEndpoint ? new URL(aiEndpoint, window.location.origin).toString() : '';
    const aiButton = form.querySelector('[data-ai-enhance]');
    const aiStatus = form.querySelector('[data-ai-status]');
    const aiFlagInput = form.querySelector('[data-ai-flag]');
    const aiFields = {
        title: form.querySelector('input[name="title"]'),
        short_description: form.querySelector('textarea[name="short_description"]'),
        description: form.querySelector('textarea[name="description"]'),
    };
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const csrfFieldName = form.dataset.csrfName || 'csrf_token';
    let aiButtonOriginalHtml = '';

    function setAiStatus(message, type = 'info') {
        if (!aiStatus) {
            return;
        }

        if (!message) {
            aiStatus.textContent = '';
            aiStatus.classList.add('hidden');
            aiStatus.classList.remove('text-green-600', 'text-red-600', 'text-amber-600', 'text-gray-600');
            return;
        }

        aiStatus.textContent = message;
        aiStatus.classList.remove('hidden');
        aiStatus.classList.remove('text-green-600', 'text-red-600', 'text-amber-600', 'text-gray-600');

        const typeClassMap = {
            success: 'text-green-600',
            error: 'text-red-600',
            warning: 'text-amber-600',
            info: 'text-gray-600',
        };

        const className = typeClassMap[type] || typeClassMap.info;
        aiStatus.classList.add(className);
    }

    function setAiLoading(isLoading) {
        if (!aiButton) {
            return;
        }

        if (!aiButtonOriginalHtml) {
            aiButtonOriginalHtml = aiButton.innerHTML;
        }

        aiButton.disabled = isLoading;
        aiButton.classList.toggle('opacity-60', isLoading);
        aiButton.setAttribute('aria-busy', isLoading ? 'true' : 'false');

        if (isLoading) {
            aiButton.innerHTML = '<span class="inline-flex items-center gap-2"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle><path d="M4 12a8 8 0 018-8" stroke-linecap="round" stroke-opacity="0.75"></path></svg><span>Mejorando…</span></span>';
        } else {
            aiButton.innerHTML = aiButtonOriginalHtml;
        }
    }

    async function handleImproveContent() {
        if (!aiEndpointUrl) {
            setAiStatus('No hay proveedor configurado para mejorar el contenido.', 'error');
            return;
        }

        const payload = {
            title: aiFields.title?.value.trim() || '',
            short_description: aiFields.short_description?.value.trim() || '',
            description: aiFields.description?.value.trim() || '',
        };

        if (!payload.title && !payload.short_description && !payload.description) {
            setAiStatus('Escribe contenido en el título, descripción breve o historia antes de pedir ayuda a la IA.', 'error');
            return;
        }

        setAiLoading(true);
        setAiStatus('Estamos analizando tus textos…', 'info');

        try {
            const requestPayload = {
                ...payload,
                ...(csrfToken ? { [csrfFieldName]: csrfToken } : {}),
            };

            const response = await fetch(aiEndpointUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                body: JSON.stringify(requestPayload),
                credentials: 'same-origin',
            });

            const data = await response.json().catch(() => null);

            if (!response.ok || !data) {
                const errorMessage = data?.error || 'No pudimos conectar con la IA en este momento. Intenta nuevamente.';
                setAiStatus(errorMessage, 'error');
                return;
            }

            if (!data.success || !data.fields) {
                const fallbackMessage = data.error || 'No recibimos sugerencias en esta ocasión.';
                setAiStatus(fallbackMessage, 'warning');
                return;
            }

            const updates = data.fields;

            if (typeof updates.title === 'string' && aiFields.title) {
                aiFields.title.value = updates.title.trim();
                aiFields.title.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (typeof updates.short_description === 'string' && aiFields.short_description) {
                aiFields.short_description.value = updates.short_description.trim();
                aiFields.short_description.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (typeof updates.description === 'string' && aiFields.description) {
                aiFields.description.value = updates.description.trim();
                aiFields.description.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (aiFlagInput) {
                aiFlagInput.checked = true;
                aiFlagInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            setAiStatus('Revisa y ajusta los textos sugeridos antes de enviar tu campaña.', 'success');
        } catch (error) {
            setAiStatus('No pudimos conectar con la IA en este momento. Intenta nuevamente.', 'error');
        } finally {
            setAiLoading(false);
        }
    }

    if (aiButton) {
        aiButton.addEventListener('click', (event) => {
            event.preventDefault();
            handleImproveContent();
        });
    }

    let current = parseInt(currentInput?.value || '1', 10) || 1;

    function scrollIntoView() {
        if (!anchor) return;
        const top = anchor.getBoundingClientRect().top + window.scrollY - 80;
        window.scrollTo({ top, behavior: 'smooth' });
    }

    function validateStep(stepNumber) {
        const panel = panels[stepNumber - 1];
        if (!panel) return true;
        const fields = Array.from(panel.querySelectorAll('input, textarea, select')).filter((el) => !el.disabled);
        for (const field of fields) {
            if (!field.checkValidity()) {
                field.reportValidity();
                return false;
            }
        }
        return true;
    }

    const fileInput = form.querySelector('[data-file-input]');
    const galleryInput = form.querySelector('[data-gallery-input]');
    const galleryList = form.querySelector('[data-gallery-list]');
    const attachmentInput = form.querySelector('[data-attachment-input]');
    const attachmentList = form.querySelector('[data-attachment-list]');

    let coverFile = null;
    let galleryFiles = [];
    let attachmentFiles = [];

    function restoreFileInputs() {
        restoreCoverFile();
        restoreMultipleFiles(galleryInput, galleryFiles, galleryList);
        restoreMultipleFiles(attachmentInput, attachmentFiles, attachmentList);
    }

    function updateUI() {
        panels.forEach((panel, index) => {
            if (index + 1 === current) {
                panel.classList.remove('hidden');
            } else {
                panel.classList.add('hidden');
            }
        });

        if (currentInput) currentInput.value = String(current);

        prevButton?.classList.toggle('hidden', current === 1);
        nextButton?.classList.toggle('hidden', current === totalSteps);
        submitButton?.classList.toggle('hidden', current !== totalSteps);

        progressButtons.forEach((btn, index) => {
            if (index + 1 === current) {
                btn.classList.add('is-active');
                btn.classList.remove('is-complete');
                btn.setAttribute('aria-current', 'step');
            } else if (index + 1 < current) {
                btn.classList.add('is-complete');
                btn.classList.remove('is-active');
                btn.removeAttribute('aria-current');
            } else {
                btn.classList.remove('is-active', 'is-complete');
                btn.removeAttribute('aria-current');
            }
        });

        const activePanel = panels[current - 1];
        if (summary && activePanel) {
            summary.textContent = activePanel.dataset.stepSummary || '';
        }
        if (indicator) {
            indicator.textContent = `Paso ${current} de ${totalSteps}`;
        }

        restoreFileInputs();
    }

    function moveToStep(target) {
        if (target < 1 || target > totalSteps || target === current) return;

        if (target > current) {
            for (let step = current; step < target; step += 1) {
                if (!validateStep(step)) {
                    updateUI();
                    return;
                }
                current = step + 1;
            }
        } else {
            current = target;
        }

        updateUI();
        scrollIntoView();
    }

    prevButton?.addEventListener('click', () => moveToStep(current - 1));
    nextButton?.addEventListener('click', () => {
        if (!validateStep(current)) return;
        moveToStep(current + 1);
    });
    progressButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = parseInt(btn.dataset.wizardStep || '0', 10);
            if (!Number.isNaN(target)) {
                moveToStep(target);
            }
        });
    });

    const currencyInput = form.querySelector('[data-currency-input]');
    const currencyHidden = form.querySelector('[data-currency-hidden]');
    const formatter = new Intl.NumberFormat('es-CL');

    function syncCurrency(digits) {
        if (!currencyInput || !currencyHidden) return;
        if (!digits) {
            currencyInput.value = '';
            currencyHidden.value = '';
            return;
        }
        const numeric = parseInt(digits, 10);
        currencyHidden.value = String(numeric);
        currencyInput.value = formatter.format(numeric);
    }

    currencyInput?.addEventListener('input', (event) => {
        const digits = event.target.value.replace(/[^0-9]/g, '');
        syncCurrency(digits);
    });

    currencyInput?.addEventListener('blur', () => {
        const digits = currencyHidden?.value || '';
        syncCurrency(digits);
    });

    if (currencyHidden && currencyHidden.value) {
        syncCurrency(currencyHidden.value);
    }

    form.addEventListener('submit', () => {
        restoreFileInputs();
        if (!currencyInput || !currencyHidden) return;
        const digits = currencyHidden.value || currencyInput.value.replace(/[^0-9]/g, '');
        syncCurrency(digits);
    });

    const fileTrigger = form.querySelector('[data-file-trigger]');
    const filePreview = form.querySelector('[data-file-preview]');
    const filePreviewImg = form.querySelector('[data-file-preview-img]');
    const filePreviewName = form.querySelector('[data-file-preview-name]');
    const fileReset = form.querySelector('[data-file-reset]');

    const existingCoverContainer = form.querySelector('[data-existing-cover]');
    const existingCoverInput = form.querySelector('[data-existing-cover-input]');
    const removeExistingCoverInput = form.querySelector('[data-remove-existing-cover]');
    const existingCoverRemove = form.querySelector('[data-existing-cover-remove]');

    const existingGalleryList = form.querySelector('[data-existing-gallery-list]');
    const existingAttachmentList = form.querySelector('[data-existing-attachment-list]');

    const GALLERY_MAX_FILES = 5;
    const ATTACHMENT_MAX_FILES = 5;

    let coverPreviewUrl = null;
    const coverState = {
        initialValue: existingCoverInput?.value || '',
        currentValue: existingCoverInput?.value || '',
        explicitlyRemoved: false,
    };

    const galleryRemovalMap = new Map();
    const attachmentRemovalMap = new Map();

    const supportsDataTransfer = (() => {
        if (typeof DataTransfer === 'undefined') {
            return false;
        }
        try {
            new DataTransfer();
            return true;
        } catch (error) {
            return false;
        }
    })();

    function ensureRemovalInput(name, value, cache) {
        if (!value) return null;
        if (cache.has(value)) {
            return cache.get(value);
        }
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        input.setAttribute('data-dynamic-remove', name);
        form.appendChild(input);
        cache.set(value, input);
        return input;
    }

    function updateExistingListVisibility(listEl, selector) {
        if (!listEl) return;
        const hasItems = listEl.querySelector(selector) !== null;
        listEl.classList.toggle('hidden', !hasItems);
    }

    function setRemoveExistingCoverFlag(value) {
        if (!removeExistingCoverInput) return;
        removeExistingCoverInput.value = value ? '1' : '0';
    }

    function setExistingCoverValue(value) {
        if (existingCoverInput) {
            existingCoverInput.value = value;
        }
        coverState.currentValue = value;
    }

    function hideExistingCover(options = {}) {
        const { explicit = false } = options;
        if (existingCoverContainer) {
            existingCoverContainer.classList.add('hidden');
        }
        setExistingCoverValue('');

        if (explicit) {
            coverState.explicitlyRemoved = true;
            setRemoveExistingCoverFlag(true);
            return;
        }

        if (coverState.explicitlyRemoved) {
            setRemoveExistingCoverFlag(true);
            return;
        }

        if (coverState.initialValue) {
            setRemoveExistingCoverFlag(true);
        } else {
            setRemoveExistingCoverFlag(false);
        }
    }

    function restoreExistingCoverIfAvailable() {
        if (!coverState.initialValue) {
            if (!coverState.explicitlyRemoved) {
                setRemoveExistingCoverFlag(false);
            }
            return;
        }

        if (existingCoverContainer) {
            existingCoverContainer.classList.remove('hidden');
        }
        setExistingCoverValue(coverState.initialValue);
        coverState.explicitlyRemoved = false;
        setRemoveExistingCoverFlag(false);
    }

    function getExistingGalleryCount() {
        if (!existingGalleryList) {
            return 0;
        }
        return existingGalleryList.querySelectorAll('[data-existing-gallery-item]').length;
    }

    function getExistingAttachmentCount() {
        if (!existingAttachmentList) {
            return 0;
        }
        return existingAttachmentList.querySelectorAll('[data-existing-attachment-item]').length;
    }

    function assignFilesToInput(inputEl, files) {
        if (!inputEl) {
            return;
        }
        if (files.length === 0) {
            inputEl.value = '';
            return;
        }
        if (!supportsDataTransfer) {
            return;
        }
        const dt = new DataTransfer();
        files.forEach((file) => dt.items.add(file));
        inputEl.files = dt.files;
    }

    function revokeCoverPreview() {
        if (coverPreviewUrl) {
            URL.revokeObjectURL(coverPreviewUrl);
            coverPreviewUrl = null;
        }
    }

    function updateCoverPreview() {
        if (!filePreview || !filePreviewImg || !filePreviewName) {
            return;
        }

        if (coverFile) {
            revokeCoverPreview();
            coverPreviewUrl = URL.createObjectURL(coverFile);
            filePreviewImg.src = coverPreviewUrl;
            filePreviewName.textContent = coverFile.name;
            filePreview.classList.remove('hidden');
        } else {
            filePreview.classList.add('hidden');
            filePreviewImg.src = '';
            filePreviewName.textContent = '';
        }
    }

    function restoreCoverFile() {
        if (!coverFile || !fileInput || (fileInput.files && fileInput.files.length > 0)) {
            updateCoverPreview();
            return;
        }

        if (!supportsDataTransfer) {
            updateCoverPreview();
            return;
        }

        const dt = new DataTransfer();
        dt.items.add(coverFile);
        fileInput.files = dt.files;
        updateCoverPreview();
    }

    function renderFileList(inputEl, listEl) {
        if (!listEl) return;
        const files = Array.from(inputEl?.files || []);
        if (files.length === 0) {
            listEl.classList.add('hidden');
            listEl.innerHTML = '';
            return;
        }

        const formatSize = (bytes) => {
            if (bytes >= 1_048_576) {
                return (bytes / 1_048_576).toFixed(1) + ' MB';
            }
            return (bytes / 1024).toFixed(1) + ' KB';
        };

        listEl.innerHTML = '';
        files.forEach((file) => {
            const item = document.createElement('li');
            item.textContent = `${file.name} — ${formatSize(file.size)}`;
            listEl.appendChild(item);
        });
        listEl.classList.remove('hidden');
    }

    function restoreMultipleFiles(inputEl, storedFiles, listEl) {
        if (!inputEl || storedFiles.length === 0 || (inputEl.files && inputEl.files.length > 0)) {
            renderFileList(inputEl, listEl);
            return;
        }
        assignFilesToInput(inputEl, storedFiles);
        renderFileList(inputEl, listEl);
    }

    fileTrigger?.addEventListener('click', () => fileInput?.click());

    fileInput?.addEventListener('change', () => {
        if (fileInput.files && fileInput.files[0]) {
            coverFile = fileInput.files[0];
            hideExistingCover();
        } else {
            coverFile = null;
            if (!coverState.explicitlyRemoved) {
                restoreExistingCoverIfAvailable();
            }
        }
        updateCoverPreview();
    });

    existingCoverRemove?.addEventListener('click', (event) => {
        event.preventDefault();
        coverFile = null;
        if (fileInput) {
            fileInput.value = '';
        }
        hideExistingCover({ explicit: true });
        revokeCoverPreview();
        updateCoverPreview();
    });

    fileReset?.addEventListener('click', (event) => {
        event.preventDefault();
        coverFile = null;
        if (fileInput) {
            fileInput.value = '';
        }
        revokeCoverPreview();
        updateCoverPreview();
        if (coverState.explicitlyRemoved) {
            setRemoveExistingCoverFlag(true);
        } else {
            restoreExistingCoverIfAvailable();
        }
    });

    function wireExistingGalleryItems() {
        if (!existingGalleryList) return;
        existingGalleryList.querySelectorAll('[data-existing-gallery-item]').forEach((item) => {
            const removeBtn = item.querySelector('[data-existing-gallery-remove]');
            if (!removeBtn) return;
            removeBtn.addEventListener('click', (event) => {
                event.preventDefault();
                const url = item.dataset.galleryUrl || '';
                item.remove();
                updateExistingListVisibility(existingGalleryList, '[data-existing-gallery-item]');
                if (url) {
                    ensureRemovalInput('remove_gallery[]', url, galleryRemovalMap);
                }
            });
        });
    }

    function wireExistingAttachmentItems() {
        if (!existingAttachmentList) return;
        existingAttachmentList.querySelectorAll('[data-existing-attachment-item]').forEach((item) => {
            const removeBtn = item.querySelector('[data-existing-attachment-remove]');
            if (!removeBtn) return;
            removeBtn.addEventListener('click', (event) => {
                event.preventDefault();
                const path = item.dataset.attachmentPath || '';
                item.remove();
                updateExistingListVisibility(existingAttachmentList, '[data-existing-attachment-item]');
                if (path) {
                    ensureRemovalInput('remove_attachments[]', path, attachmentRemovalMap);
                }
            });
        });
    }

    wireExistingGalleryItems();
    wireExistingAttachmentItems();

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

    galleryInput?.addEventListener('change', () => {
        const existingCount = getExistingGalleryCount();
        const availableSlots = Math.max(0, GALLERY_MAX_FILES - existingCount);
        galleryFiles = Array.from(galleryInput.files || []);

        if (availableSlots <= 0) {
            if (galleryFiles.length > 0) {
                alert('Ya alcanzaste el máximo de 5 imágenes en la galería. Elimina alguna existente para subir otra.');
            }
            galleryFiles = [];
            if (galleryInput) {
                galleryInput.value = '';
            }
            renderFileList(galleryInput, galleryList);
            return;
        }

        if (galleryFiles.length > availableSlots) {
            const message = availableSlots === 1
                ? 'Solo puedes agregar 1 imagen adicional para completar la galería.'
                : `Puedes agregar hasta ${availableSlots} imágenes adicionales (máximo total de ${GALLERY_MAX_FILES}).`;
            alert(message);
            galleryFiles = galleryFiles.slice(0, availableSlots);
            if (supportsDataTransfer) {
                assignFilesToInput(galleryInput, galleryFiles);
            } else if (galleryInput) {
                galleryInput.value = '';
            }
        } else if (supportsDataTransfer) {
            assignFilesToInput(galleryInput, galleryFiles);
        }

        if (!supportsDataTransfer && galleryInput && galleryInput.value === '' && galleryFiles.length > 0) {
            // No podemos re-asignar archivos sin DataTransfer, así que vaciamos selección para evitar inconsistencias.
            galleryFiles = [];
        }

        renderFileList(galleryInput, galleryList);
    });

    attachmentInput?.addEventListener('change', () => {
        const existingCount = getExistingAttachmentCount();
        const availableSlots = Math.max(0, ATTACHMENT_MAX_FILES - existingCount);
        attachmentFiles = Array.from(attachmentInput.files || []);

        if (availableSlots <= 0) {
            if (attachmentFiles.length > 0) {
                alert('Ya tienes 5 documentos de respaldo cargados. Elimina alguno para agregar uno nuevo.');
            }
            attachmentFiles = [];
            if (attachmentInput) {
                attachmentInput.value = '';
            }
            renderFileList(attachmentInput, attachmentList);
            return;
        }

        if (attachmentFiles.length > availableSlots) {
            const message = availableSlots === 1
                ? 'Solo puedes agregar 1 documento adicional para llegar al máximo permitido.'
                : `Puedes agregar hasta ${availableSlots} documentos adicionales (máximo total de ${ATTACHMENT_MAX_FILES}).`;
            alert(message);
            attachmentFiles = attachmentFiles.slice(0, availableSlots);
            if (supportsDataTransfer) {
                assignFilesToInput(attachmentInput, attachmentFiles);
            } else if (attachmentInput) {
                attachmentInput.value = '';
            }
        } else if (supportsDataTransfer) {
            assignFilesToInput(attachmentInput, attachmentFiles);
        }

        if (!supportsDataTransfer && attachmentInput && attachmentInput.value === '' && attachmentFiles.length > 0) {
            attachmentFiles = [];
        }

        renderFileList(attachmentInput, attachmentList);
    });

    updateExistingListVisibility(existingGalleryList, '[data-existing-gallery-item]');
    updateExistingListVisibility(existingAttachmentList, '[data-existing-attachment-item]');

    updateUI();
});
</script>

HTML;
?>

<?php ob_start(); ?>
<div class="relative isolate overflow-visible bg-[var(--wizard-soft)] pb-16">
    <section class="relative z-10 mx-auto max-w-4xl px-4 pt-10 sm:px-6 lg:px-8" data-wizard-anchor>
        <div class="rounded-3xl border border-white/60 bg-white/95 p-8 shadow-xl shadow-slate-900/10" data-animate="fade-up">
            <span class="inline-flex items-center gap-2 rounded-full bg-[rgba(16,60,93,0.08)] px-4 py-1 text-xs font-semibold uppercase tracking-wide text-[var(--wizard-primary)]">
                Crear campaña
            </span>
            <h1 class="mt-4 text-3xl font-bold text-[var(--wizard-primary)] sm:text-4xl">
                Lanza tu campaña en cuatro pasos guiados
            </h1>
            <p class="mt-3 max-w-2xl text-sm text-gray-600 sm:text-base">
                Completa cada sección, guarda sin perder avances y deja que el equipo académico revise tus antecedentes antes de publicarla.
            </p>
            <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                <li class="rounded-2xl border border-gray-100 bg-white/90 px-4 py-3 text-sm text-gray-700">Formulario mobile-first: avanza o retrocede sin perder datos.</li>
                <li class="rounded-2xl border border-gray-100 bg-white/90 px-4 py-3 text-sm text-gray-700">Sube tu imagen principal directamente y agrega datos privados para verificación.</li>
            </ul>
        </div>

        <div class="mt-10 space-y-3" data-animate="fade-up" data-animate-delay="150">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--wizard-primary)]" data-wizard-indicator>Paso 1 de 4</span>
                <p class="text-sm text-gray-600" data-wizard-summary><?= htmlspecialchars($wizard_steps[1]['summary']) ?></p>
            </div>
            <ol class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <?php foreach ($wizard_steps as $index => $data): ?>
                    <li>
                        <button type="button"
                                class="wizard-step<?= $index === 1 ? ' is-active' : '' ?>"
                                data-wizard-step="<?= $index ?>"<?= $index === 1 ? ' aria-current="step"' : '' ?>>
                            <span class="wizard-step__badge"><?= $index ?></span>
                            <span class="flex flex-col">
                                <span class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($data['title']) ?></span>
                                <span class="text-xs text-gray-500"><?= htmlspecialchars($data['summary']) ?></span>
                            </span>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </section>

    <section class="relative z-10 mx-auto mt-10 max-w-5xl px-4 sm:px-6 lg:px-8" data-animate="fade-up" data-animate-delay="300">
        <form method="POST" action="<?= Router::url('campana/crear') ?>" class="space-y-10" data-wizard-form data-ai-endpoint="<?= htmlspecialchars($ai_improve_endpoint) ?>" data-csrf-name="<?= CSRF_TOKEN_NAME ?>" enctype="multipart/form-data">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
            <input type="hidden" name="campaign_step" value="1" data-wizard-current>

            <?php include_flash_messages(); ?>

            <section class="wizard-panel rounded-3xl border border-gray-100 bg-white/95 p-6 shadow-xl shadow-gray-900/5 backdrop-blur-sm sm:p-8"
                     data-step="1"
                     data-step-summary="<?= htmlspecialchars($wizard_steps[1]['summary']) ?>">
                <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex-1">
                        <h2 class="text-xl font-semibold text-gray-900">Fundamentos de la campaña</h2>
                        <p class="mt-1 text-sm text-gray-600">Explica el problema, la causa y el impacto que buscas.</p>
                    </div>
                    <div class="flex flex-col items-start gap-2 sm:items-end">
                        <span class="inline-flex items-center rounded-full bg-[rgba(238,99,82,0.12)] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[var(--wizard-accent)]">Paso 1</span>
                        <button type="button"
                                class="inline-flex items-center gap-2 rounded-full border border-[rgba(16,60,93,0.12)] bg-white px-3 py-1.5 text-xs font-semibold text-[var(--wizard-primary)] shadow-sm transition hover:border-[var(--wizard-accent)] hover:text-[var(--wizard-accent)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--wizard-accent)]"
                                data-ai-enhance
                                aria-describedby="ai-helper-status">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 3v3m6.364-2.364-2.122 2.122M21 12h-3m2.364 6.364-2.122-2.122M12 21v-3m-6.364 2.364 2.122-2.122M3 12h3M4.636 5.636l2.122 2.122" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <span>Mejorar con IA</span>
                        </button>
                        <p id="ai-helper-status" data-ai-status class="hidden text-xs font-medium text-gray-600"></p>
                    </div>
                </header>
                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div class="lg:col-span-2">
                        <?php echo render_text_input([
                            'name' => 'title',
                            'label' => 'Título de la campaña',
                            'required' => true,
                            'placeholder' => 'Ej: Reconstrucción de escuela rural en Alto Biobío',
                            'value' => htmlspecialchars($old['title'] ?? ''),
                            'error' => $formErrors['title'] ?? '',
                            'attributes' => ['maxlength' => 120]
                        ]); ?>
                    </div>
                    <div class="lg:col-span-2">
                        <?php echo render_textarea([
                            'name' => 'short_description',
                            'label' => 'Descripción breve',
                            'required' => true,
                            'rows' => 3,
                            'placeholder' => 'Resume la necesidad y a quién beneficia.',
                            'value' => htmlspecialchars($old['short_description'] ?? ''),
                            'error' => $formErrors['short_description'] ?? ''
                        ]); ?>
                    </div>
                    <div class="lg:col-span-2">
                        <?php echo render_textarea([
                            'name' => 'description',
                            'label' => 'Historia completa',
                            'required' => true,
                            'rows' => 8,
                            'placeholder' => 'Describe el contexto, los aliados y cómo se usará cada aporte.',
                            'value' => htmlspecialchars($old['description'] ?? ''),
                            'error' => $formErrors['description'] ?? ''
                        ]); ?>
                    </div>
                </div>
            </section>

            <section class="wizard-panel hidden rounded-3xl border border-gray-100 bg-white/95 p-6 shadow-xl shadow-gray-900/5 backdrop-blur-sm sm:p-8"
                     data-step="2"
                     data-step-summary="<?= htmlspecialchars($wizard_steps[2]['summary']) ?>">
                <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Meta y plazos</h2>
                        <p class="mt-1 text-sm text-gray-600">Define cuánto necesitas y hasta cuándo reunir aportes.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-[rgba(16,60,93,0.12)] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[var(--wizard-primary)]">Paso 2</span>
                </header>
                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="goal_amount_display" class="block text-sm font-medium text-gray-700">Meta económica (CLP) <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input type="text"
                                   id="goal_amount_display"
                                   class="form-input block w-full rounded-md border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm"
                                   inputmode="numeric"
                                   placeholder="Ej: 120.000"
                                   data-currency-input
                                   value="<?= htmlspecialchars($goalAmountFormatted) ?>"
                                   required>
                            <input type="hidden" name="goal_amount" data-currency-hidden value="<?= htmlspecialchars($goalAmountRaw) ?>">
                        </div>
                        <?php if (!empty($formErrors['goal_amount'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($formErrors['goal_amount']) ?></p>
                        <?php endif; ?>
                        <p class="mt-1 text-xs text-gray-500">Aplicaremos comisión 0% durante la revisión académica.</p>
                    </div>
                    <div class="md:col-span-2 space-y-2" data-end-datetime data-min-datetime="<?= htmlspecialchars($endDateMinimum); ?>" data-minute-step="<?= $timeStepMinutes; ?>">
                        <label class="block text-sm font-medium text-gray-700">Fecha y hora de término <span class="text-red-500">*</span></label>
                        <input
                            id="end_date_date"
                            name="end_date_date"
                            type="hidden"
                            value="<?= htmlspecialchars($endDateDateValue); ?>"
                            min="<?= htmlspecialchars($endDateMinDate); ?>"
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
                        <p class="text-xs text-gray-500">Elige la fecha en que finaliza tu campaña y confirma la hora exacta antes de guardar.</p>
                        <input type="hidden" name="end_date" value="<?= htmlspecialchars($endDateHiddenValue); ?>" data-end-datetime-hidden>
                        <?php if (!empty($formErrors['end_date'])): ?>
                            <p class="text-sm text-red-600"><?= htmlspecialchars($formErrors['end_date']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php echo render_select([
                            'name' => 'category',
                            'label' => 'Categoría',
                            'options' => $categoryOptions,
                            'required' => true,
                            'value' => $old['category'] ?? '',
                            'error' => $formErrors['category'] ?? ''
                        ]); ?>
                    </div>
                    <div>
                        <?php echo render_text_input([
                            'name' => 'location',
                            'label' => 'Ubicación beneficiada',
                            'placeholder' => 'Ciudad o región',
                            'value' => htmlspecialchars($campaignLocation ?? ''),
                            'error' => $formErrors['location'] ?? ''
                        ]); ?>
                    </div>
                </div>
            </section>

            <section class="wizard-panel hidden rounded-3xl border border-gray-100 bg-white/95 p-6 shadow-xl shadow-gray-900/5 backdrop-blur-sm sm:p-8"
                     data-step="3"
                     data-step-summary="<?= htmlspecialchars($wizard_steps[3]['summary']) ?>">
                <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Beneficiarios</h2>
                        <p class="mt-1 text-sm text-gray-600">Estos datos son privados y nos permiten auditar la campaña.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-[rgba(238,99,82,0.12)] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[var(--wizard-accent)]">Paso 3</span>
                </header>
                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <div>
                        <?php echo render_select([
                            'name' => 'beneficiary_type',
                            'label' => 'Tipo de beneficiario',
                            'options' => [
                                ['value' => 'individual', 'label' => 'Persona'],
                                ['value' => 'organization', 'label' => 'Organización'],
                                ['value' => 'community', 'label' => 'Comunidad']
                            ],
                            'value' => $old['beneficiary_type'] ?? 'individual',
                            'error' => $formErrors['beneficiary_type'] ?? ''
                        ]); ?>
                    </div>
                    <div>
                        <?php echo render_text_input([
                            'name' => 'beneficiary_name',
                            'label' => 'Nombre del beneficiario',
                            'required' => true,
                            'placeholder' => 'Nombre completo o institucional',
                            'value' => htmlspecialchars($old['beneficiary_name'] ?? ''),
                            'error' => $formErrors['beneficiary_name'] ?? ''
                        ]); ?>
                    </div>
                    <div>
                        <?php echo render_text_input([
                            'name' => 'beneficiary_phone',
                            'label' => 'Teléfono del beneficiario (privado)',
                            'placeholder' => '+56 9 1234 5678',
                            'value' => htmlspecialchars($beneficiaryPhone),
                            'error' => $formErrors['beneficiary_phone'] ?? '',
                            'attributes' => ['inputmode' => 'tel', 'autocomplete' => 'tel']
                        ]); ?>
                    </div>
                    <div>
                        <?php echo render_text_input([
                            'name' => 'beneficiary_email',
                            'label' => 'Correo del beneficiario (privado)',
                            'placeholder' => 'contacto@ejemplo.cl',
                            'value' => htmlspecialchars($beneficiaryEmail),
                            'error' => $formErrors['beneficiary_email'] ?? '',
                            'attributes' => ['autocomplete' => 'email']
                        ]); ?>
                    </div>
                </div>
                <?php if (!empty($formErrors['beneficiary_contact'])): ?>
                    <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($formErrors['beneficiary_contact']) ?></p>
                <?php else: ?>
                    <p class="mt-2 text-xs text-gray-500">Los datos anteriores no se publican; el equipo del proyecto los usa para verificación.</p>
                <?php endif; ?>
            </section>

            <section class="wizard-panel hidden rounded-3xl border border-gray-100 bg-white/95 p-6 shadow-xl shadow-gray-900/5 backdrop-blur-sm sm:p-8"
                     data-step="4"
                     data-step-summary="<?= htmlspecialchars($wizard_steps[4]['summary']) ?>">
                <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Evidencias y transparencia</h2>
                        <p class="mt-1 text-sm text-gray-600">Sube una imagen principal y agrega recursos opcionales.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-[rgba(16,60,93,0.12)] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[var(--wizard-primary)]">Paso 4</span>
                </header>
                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <div class="md:col-span-2 space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-gray-700" for="featured_image">Imagen principal <span class="text-red-500">*</span></label>
                        </div>
                        <?php if ($existing_cover_url): ?>
                            <div class="rounded-2xl border border-gray-200 bg-white/90 p-3 text-left" data-existing-cover>
                                <img src="<?= htmlspecialchars($existing_cover_url) ?>" alt="Portada actual" class="h-40 w-full rounded-xl object-cover">
                                <div class="mt-3 flex items-center justify-between text-xs text-gray-600">
                                    <span><?= htmlspecialchars(basename($draft_media['cover'])) ?></span>
                                    <button type="button" class="text-[var(--wizard-accent)] hover:underline" data-existing-cover-remove>Quitar portada</button>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="rounded-2xl border-2 border-dashed border-gray-300 bg-white/70 p-4 text-center">
                            <input type="file" name="featured_image" id="featured_image" accept="image/jpeg,image/png,image/webp" class="sr-only" data-file-input>
                            <div class="flex flex-col items-center gap-2">
                                <button type="button" class="btn-primary px-4 py-2 text-sm" data-file-trigger>Seleccionar imagen</button>
                                <p class="text-xs text-gray-500">Formatos permitidos: JPG, PNG o WebP. Máximo 5&nbsp;MB.</p>
                            </div>
                        </div>
                        <?php if (!empty($formErrors['featured_image'])): ?>
                            <p class="text-sm text-red-600"><?= htmlspecialchars($formErrors['featured_image']) ?></p>
                        <?php endif; ?>
                        <figure class="hidden rounded-2xl border border-gray-200 bg-white/80 p-3 text-left" data-file-preview>
                            <img src="" alt="Vista previa de la imagen" class="h-32 w-full rounded-lg object-cover" data-file-preview-img>
                            <figcaption class="mt-2 flex items-center justify-between text-xs text-gray-600">
                                <span data-file-preview-name></span>
                                <button class="text-[var(--wizard-accent)] hover:underline" data-file-reset>Quitar</button>
                            </figcaption>
                        </figure>
                        <input type="hidden" name="featured_image_existing" value="<?= htmlspecialchars($draft_media['cover'] ?? '') ?>" data-existing-cover-input>
                        <input type="hidden" name="remove_existing_cover" value="0" data-remove-existing-cover>
                    </div>

                    <div class="md:col-span-2 space-y-3">
                        <label class="block text-sm font-medium text-gray-700" for="gallery_images">Galería (opcional)</label>
                        <ul data-existing-gallery-list class="space-y-2 text-xs text-gray-600 <?= empty($existing_gallery_media) ? 'hidden' : '' ?>">
                            <?php foreach ($existing_gallery_media as $galleryItem): ?>
                                <?php $galleryUrl = CampaignMediaUploadService::normalizePublicUrl($galleryItem['url']); ?>
                                <li class="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-3 py-2" data-existing-gallery-item data-gallery-url="<?= htmlspecialchars($galleryItem['url']) ?>">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-700"><?= htmlspecialchars($galleryItem['filename'] ?? basename($galleryItem['url'])) ?></span>
                                        <span class="text-[11px] text-gray-500"><?= number_format((int)($galleryItem['size'] ?? 0) / 1024, 1) ?> KB</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="<?= htmlspecialchars($galleryUrl) ?>" target="_blank" rel="noopener" class="text-xs text-copihue-600 hover:underline">Ver</a>
                                        <button type="button" class="text-xs text-red-600 hover:underline" data-existing-gallery-remove>Quitar</button>
                                    </div>
                                    <input type="hidden" name="existing_gallery[]" value="<?= htmlspecialchars($galleryItem['url']) ?>" data-existing-gallery-value>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <input type="file" name="gallery_images[]" id="gallery_images" accept="image/jpeg,image/png,image/webp" multiple data-gallery-input class="block w-full text-sm text-gray-600">
                        <p class="text-xs text-gray-500">Puedes agregar hasta cinco imágenes complementarias para mostrar contexto o avances iniciales.</p>
                        <?php if (!empty($formErrors['gallery_images'])): ?>
                            <p class="text-sm text-red-600"><?= htmlspecialchars($formErrors['gallery_images']) ?></p>
                        <?php endif; ?>
                        <ul data-gallery-list class="hidden space-y-1 text-xs text-gray-600"></ul>
                    </div>

                    <div class="md:col-span-2 space-y-3">
                        <label class="block text-sm font-medium text-gray-700" for="supporting_files">Documentos de respaldo (privados)</label>
                        <ul data-existing-attachment-list class="space-y-2 text-xs text-gray-600 <?= empty($existing_attachments_media) ? 'hidden' : '' ?>">
                            <?php foreach ($existing_attachments_media as $attachment): ?>
                                <li class="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-3 py-2" data-existing-attachment-item data-attachment-path="<?= htmlspecialchars($attachment['path']) ?>">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-700"><?= htmlspecialchars($attachment['filename'] ?? basename($attachment['path'])) ?></span>
                                        <span class="text-[11px] text-gray-500"><?= number_format((int)($attachment['size'] ?? 0) / 1024, 1) ?> KB — <?= htmlspecialchars($attachment['mime'] ?? 'archivo') ?></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-400">Privado</span>
                                        <button type="button" class="text-xs text-red-600 hover:underline" data-existing-attachment-remove>Quitar</button>
                                    </div>
                                    <input type="hidden" name="existing_attachments[]" value="<?= htmlspecialchars($attachment['path']) ?>" data-existing-attachment-value>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <input type="file" name="supporting_files[]" id="supporting_files" accept="application/pdf,image/jpeg,image/png,image/webp" multiple data-attachment-input class="block w-full text-sm text-gray-600">
                        <p class="text-xs text-gray-500">Adjunta hasta cinco presupuestos, cotizaciones u otros respaldos en PDF o imagen. Estos archivos solo los revisa el equipo académico.</p>
                        <?php if (!empty($formErrors['supporting_files'])): ?>
                            <p class="text-sm text-red-600"><?= htmlspecialchars($formErrors['supporting_files']) ?></p>
                        <?php endif; ?>
                        <ul data-attachment-list class="hidden space-y-1 text-xs text-gray-600"></ul>
                    </div>

                    <div>
                        <?php echo render_text_input([
                            'name' => 'video_url',
                            'label' => 'Video de apoyo (opcional)',
                            'placeholder' => 'https://www.youtube.com/watch?v=...',
                            'value' => htmlspecialchars($old['video_url'] ?? ''),
                            'error' => $formErrors['video_url'] ?? ''
                        ]); ?>
                    </div>
                </div>
                <label class="mt-5 inline-flex items-center gap-3 rounded-2xl border border-dashed border-[var(--wizard-primary)] bg-[rgba(16,60,93,0.05)] px-4 py-3 text-sm text-[var(--wizard-primary)]">
                    <input type="checkbox" name="ai_generated" value="1" <?= !empty($old['ai_generated']) ? 'checked' : '' ?> class="rounded border-[var(--wizard-primary)] text-[var(--wizard-accent)] focus:ring-[var(--wizard-accent)]" data-ai-flag>
                    <span>Parte del contenido fue asistido por IA y está documentado en actualizaciones.</span>
                </label>

            </section>

            <div class="sticky bottom-4 left-0 right-0 z-40" data-animate="fade-up" data-animate-delay="450">
                <div class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-2xl shadow-gray-900/10 backdrop-blur sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3 text-sm text-gray-600">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[rgba(16,60,93,0.1)] text-[var(--wizard-primary)]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                            </svg>
                        </div>
                        <p class="text-sm">Puedes revisar y editar cada paso antes de enviar a revisión.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <?= render_button([
                            'text' => 'Cancelar',
                            'type' => 'ghost',
                            'size' => 'sm',
                            'href' => Router::url('panel')
                        ]) ?>
                        <?= render_button([
                            'text' => 'Paso anterior',
                            'type' => 'secondary',
                            'size' => 'sm',
                            'form_type' => 'button',
                            'attributes' => ['data-wizard' => 'prev']
                        ]) ?>
                        <?= render_button([
                            'text' => 'Siguiente paso',
                            'type' => 'primary',
                            'size' => 'sm',
                            'form_type' => 'button',
                            'attributes' => ['data-wizard' => 'next']
                        ]) ?>
                        <?= render_button([
                            'text' => 'Guardar campaña',
                            'type' => 'primary',
                            'size' => 'sm',
                            'attributes' => ['data-wizard' => 'submit'],
                            'form_type' => 'submit'
                        ]) ?>
                    </div>
                </div>
            </div>
        </form>

        <details class="mt-10 rounded-3xl border border-gray-200 bg-white/95 p-6 shadow-xl shadow-gray-900/5 backdrop-blur-sm" data-animate="fade-up" data-animate-delay="450">
            <summary class="cursor-pointer text-sm font-semibold text-[var(--wizard-primary)]">Checklist de revisión y tips</summary>
            <div class="mt-4 grid gap-6 sm:grid-cols-2">
                <ul class="space-y-3 text-sm text-gray-700">
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex h-6 w-6 items-center justify-center rounded-full bg-[rgba(238,99,82,0.15)] text-[var(--wizard-accent)]">1</span>
                        Tu historia incluye hitos verificables y datos locales.
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex h-6 w-6 items-center justify-center rounded-full bg-[rgba(238,99,82,0.15)] text-[var(--wizard-accent)]">2</span>
                        Adjuntaste referencias de costos (cotizaciones, presupuestos, boletas previas).
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex h-6 w-6 items-center justify-center rounded-full bg-[rgba(238,99,82,0.15)] text-[var(--wizard-accent)]">3</span>
                        El beneficiario confirmó que conoce la campaña y autoriza su publicación.
                    </li>
                </ul>
                <div class="space-y-3 text-sm text-gray-700">
                    <p>¿Dudas? Escríbenos a <a class="font-semibold text-[var(--wizard-primary)] underline decoration-[var(--wizard-accent)] decoration-2 underline-offset-4" href="mailto:<?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>"><?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?></a>. <?= htmlspecialchars(PROJECT_DISCLAIMER) ?></p>
                    <a class="inline-flex items-center gap-2 text-[var(--wizard-primary)] hover:text-[var(--wizard-accent)]" href="<?= Router::url('ayuda') ?>">
                        Revisar guías del centro de ayuda
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </details>
    </section>
</div>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/main.php';
?>
