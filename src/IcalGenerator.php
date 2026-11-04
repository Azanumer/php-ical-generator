<?php
/**
 * Dependency-free iCalendar (.ics) generator — RFC 5545.
 * Builds calendars with events, all-day events, recurrence rules and
 * alarms, with correct line folding (75 octets) and text escaping.
 */

class IcalGenerator
{
    /** @var array */
    private $events = array();
    /** @var string */
    private $prodId;

    public function __construct($prodId = '-//Azan Umer//IcalGenerator//EN')
    {
        $this->prodId = $prodId;
    }

    /**
     * Add an event.
     *
     * $event keys: uid (auto if omitted), summary (required), description,
     * location, start (DateTime|string, required), end (DateTime|string),
     * allDay (bool), rrule (string, e.g. 'FREQ=WEEKLY;COUNT=10'),
     * alarmMinutes (int|null — VALARM TRIGGER before start).
     */
    public function addEvent(array $event)
    {
        if (empty($event['summary']) || empty($event['start'])) {
            throw new InvalidArgumentException('Ical event requires summary and start.');
        }
        $event['uid'] = isset($event['uid']) ? $event['uid'] : $this->newUid();
        $this->events[] = $event;
        return $this;
    }

    public function count()
    {
        return count($this->events);
    }

    /** Render the full .ics document. */
    public function render()
    {
        $lines = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:' . $this->prodId,
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        );
        foreach ($this->events as $event) {
            $lines = array_merge($lines, $this->renderEvent($event));
        }
        $lines[] = 'END:VCALENDAR';
        // Fold every line to RFC 5545's 75-octet limit, then CRLF-join.
        $out = '';
        foreach ($lines as $line) {
            $out .= $this->fold($line) . "\r\n";
        }
        return $out;
    }

    private function renderEvent(array $e)
    {
        $allDay = !empty($e['allDay']);
        $start = $this->toDateTime($e['start']);
        $end = isset($e['end']) ? $this->toDateTime($e['end']) : null;
        $lines = array('BEGIN:VEVENT', 'UID:' . $e['uid'], 'DTSTAMP:' . gmdate('Ymd\THis\Z'));
        if ($allDay) {
            $lines[] = 'DTSTART;VALUE=DATE:' . $start->format('Ymd');
            // RFC 5545: DTEND for all-day events is exclusive — +1 day default.
            $lines[] = 'DTEND;VALUE=DATE:' . ($end ? $end->format('Ymd') : $start->modify('+1 day')->format('Ymd'));
        } else {
            $lines[] = 'DTSTART:' . $this->formatUtc($start);
            if ($end) {
                $lines[] = 'DTEND:' . $this->formatUtc($end);
            }
        }
        $lines[] = 'SUMMARY:' . $this->escape($e['summary']);
        if (!empty($e['description'])) {
            $lines[] = 'DESCRIPTION:' . $this->escape($e['description']);
        }
        if (!empty($e['location'])) {
            $lines[] = 'LOCATION:' . $this->escape($e['location']);
        }
        if (!empty($e['rrule'])) {
            $lines[] = 'RRULE:' . $e['rrule'];
        }
        if (isset($e['alarmMinutes'])) {
            $lines[] = 'BEGIN:VALARM';
            $lines[] = 'TRIGGER:-PT' . (int) $e['alarmMinutes'] . 'M';
            $lines[] = 'ACTION:DISPLAY';
            $lines[] = 'DESCRIPTION:' . $this->escape($e['summary']);
            $lines[] = 'END:VALARM';
        }
        $lines[] = 'END:VEVENT';
        return $lines;
    }

    /** RFC 5545 text escaping: backslash, semicolon, comma, newline. */
    private function escape($text)
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(';', '\;', $text);
        $text = str_replace(',', '\,', $text);
        return str_replace(array("\r\n", "\n", "\r"), '\\n', $text);
    }

    /** Fold a line at 75 bytes; continuations start with a single space. */
    private function fold($line)
    {
        if (strlen($line) <= 75) {
            return $line;
        }
        $out = substr($line, 0, 75);
        $rest = substr($line, 75);
        while (strlen($rest) > 74) {
            $out .= "\r\n " . substr($rest, 0, 74);
            $rest = substr($rest, 74);
        }
        return $out . "\r\n " . $rest;
    }

    private function formatUtc(DateTime $dt)
    {
        $utc = clone $dt;
        $utc->setTimezone(new DateTimeZone('UTC'));
        return $utc->format('Ymd\THis\Z');
    }

    private function toDateTime($value)
    {
        if ($value instanceof DateTime) {
            return $value;
        }
        return new DateTime($value);
    }

    private function newUid()
    {
        return bin2hex(random_bytes(16)) . '@ical-generator';
    }
}
