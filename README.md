# PHP iCalendar Generator

Dependency-free RFC 5545 `.ics` generator — build calendar files any calendar app (Google Calendar, Outlook, Apple Calendar) can import, with zero dependencies.

## Features

- Timed events with automatic UTC conversion from any timezone
- All-day events (with the RFC 5545 exclusive-`DTEND` rule handled for you)
- Recurrence rules (`RRULE`, e.g. `FREQ=WEEKLY;COUNT=10`)
- `VALARM` reminders (`alarmMinutes` before start)
- Correct RFC 5545 text escaping (`\`, `;`, `,`, newlines) and 75-octet line folding
- Auto-generated UIDs when you don't supply one

## Usage

```php
require 'src/IcalGenerator.php';

$cal = new IcalGenerator();
$cal->addEvent([
    'summary'      => 'Team meeting',
    'start'        => new DateTime('2026-11-10 10:00', new DateTimeZone('Asia/Karachi')),
    'end'          => new DateTime('2026-11-10 11:00', new DateTimeZone('Asia/Karachi')),
    'alarmMinutes' => 15,
    'rrule'        => 'FREQ=WEEKLY;COUNT=4',
]);

header('Content-Type: text/calendar');
echo $cal->render();
```

See `examples/ical-example.php` for a complete downloadable-calendar example.

## License

MIT
