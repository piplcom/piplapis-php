<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/field.php';



class PiplApi_DateRange
{
    // A time intervel represented as a range of two dates.
    // DateRange objects are used inside DOB, Job and Education objects.

    public $start;
    public $end;

    function __construct($start, $end)
    {
        // `start` and `end` are DateTime objects, at least one is required.

        // For creating a DateRange object for an exact date (like if exact
        // date-of-birth is known) just pass the same value for `start` and `end`.

        if (!empty($start))
        {
            $this->start = $start;
        }
        if (!empty($end))
        {
            $this->end = $end;
        }

        if (empty($this->start) && empty($this->end))
        {
            throw new InvalidArgumentException('Start/End parameters missing');
        }

        if (($this->start && $this->end) && ($this->start > $this->end))
        {
            $t = $this->end;
            $this->end = $this->start;
            $this->start = $t;
        }
    }

    public function __toString()
    {
        // Return a representation of the object.
        if($this->start && $this->end) {
            return sprintf('%s - %s', PiplApi_Utils::piplapi_date_to_str($this->start),
                PiplApi_Utils::piplapi_date_to_str($this->end));
        } elseif($this->start) {
            return PiplApi_Utils::piplapi_date_to_str($this->start);
        }
        return PiplApi_Utils::piplapi_date_to_str($this->end);
    }

    public function is_exact()
    {
        // True if the object holds an exact date (start=end),
        // False otherwise.
        return ($this->start == $this->end);
    }

    public function middle()
    {
        // The middle of the date range (a DateTime object).
        if($this->start && $this->end) {
            $diff = ($this->end->format('U') - $this->start->format('U')) / 2;
            $newts = $this->start->format('U') + $diff;
            $newdate = new DateTime('@' . $newts, new DateTimeZone('GMT'));
            return $newdate;
        }
        return $this->start ? $this->start : $this->end;
    }

    public function years_range()
    {
        // A tuple of two ints - the year of the start date and the year of the
        // end date.
        if(!($this->start && $this->end)){
            return NULL;
        }
        return array($this->start->format('Y'), $this->end->format('Y'));
    }

    public static function from_years_range($start_year, $end_year)
    {
        // Transform a range of years (two ints) to a DateRange object.
        $newstart = new DateTime($start_year . '-01-01', new DateTimeZone('GMT'));
        $newend = new DateTime($end_year . '-12-31', new DateTimeZone('GMT'));
        return new PiplApi_DateRange($newstart, $newend);
    }

    public static function from_array($d)
    {
        // Transform the dict to a DateRange object.
        $newstart = !empty($d['start']) ? $d['start'] : NULL;
        $newend = !empty($d['end']) ? $d['end'] : NULL;
        if($newstart) {
            $newstart = PiplApi_Utils::piplapi_str_to_date($newstart);
        }
        if($newend){
            $newend = PiplApi_Utils::piplapi_str_to_date($newend);
        }
        return new PiplApi_DateRange($newstart, $newend);
    }

    public function to_array()
    {
        // Transform the date-range to a dict.
        $d = array();
        if($this->start) {
            $d['start'] = PiplApi_Utils::piplapi_date_to_str($this->start);
        }
        if($this->end){
            $d['end'] = PiplApi_Utils::piplapi_date_to_str($this->end);
        }
        return $d;
    }
}