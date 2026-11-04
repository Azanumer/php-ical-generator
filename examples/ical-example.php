<?php
require __DIR__ . '/../src/IcalGenerator.php';

$cal = new IcalGenerator();

// Timed event with a 15-minute reminder.
$cal->addEvent(array(
    'summary' => 'Team meeting: Q4 planning, bring notes',
    'description' => "Agenda:\n- Review Q4 targets\n- Assign owners",
    'location' => 'Main Office, Room 3',
    'start' => new DateTime('2026-11-10 10:00:00', new DateTimeZone('Asia/Karachi')),
    'end' => new DateTime('2026-11-10 11:00:00', new DateTimeZone('Asia/Karachi')),
    'alarmMinutes' => 15,
));

// All-day recurring event (DTEND is exclusive, handled internally).
$cal->addEvent(array(
    'summary' => 'Company holiday',
    'start' => '2026-12-25',
    'allDay' => true,
    'rrule' => 'FREQ=YEARLY;COUNT=3',
));

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="calendar.ics"');
echo $cal->render();
