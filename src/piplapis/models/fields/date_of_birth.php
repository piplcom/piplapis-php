<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/field.php';

class PiplApi_DOB extends PiplApi_Field
{
    // Date-of-birth of A person.
    // Comes as a PiplApi_DateRange (the exact date is within the range, if the exact
    // date is known the range will simply be with start=end).

    protected $children = array('date_range', 'display');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // `date_range` is A DateRange object (PiplApi_DateRange),
        // the date-of-birth is within this range.


        if (!empty($date_range))
        {
            $this->date_range = $date_range;
        }
        if (!empty($display))
        {
            $this->display = $display;
        }
    }

    public function is_searchable()
    {
        return (!empty($this->date_range));
    }

    public function age()
    {
        // int, the estimated age of the person.

        // Note that A DOB object is based on a date-range and the exact date is
        // usually unknown so for age calculation the the middle of the range is
        // assumed to be the real date-of-birth.

        if (!empty($this->date_range))
        {
            $dob = $this->date_range->middle();
            $today = new DateTime('now', new DateTimeZone('GMT'));

            $diff = $today->format('Y') - $dob->format('Y');

            if ($dob->format('z') > $today->format('z'))
            {
                $diff -= 1;
            }

            return $diff;
        }
        return;
    }

    public function age_range()
    {
        // An array of two ints - the minimum and maximum age of the person.
        if (empty($this->date_range)){
            return array(NULL, NULL);
        }
        if(empty($this->date_range->start) || empty($this->date_range->end)){
            return array($this->age(), $this->age());
        }

        $start_date = new PiplApi_DateRange($this->date_range->start, $this->date_range->start);
        $end_date = new PiplApi_DateRange($this->date_range->end, $this->date_range->end);
        $start_age = new PiplApi_DOB(array('date_range' => $start_date));
        $start_age = $start_age->age();
        $end_age = new PiplApi_DOB(array('date_range' => $end_date));
        $end_age = $end_age->age();

        return (array($end_age, $start_age));
    }

    public static function from_birth_year($birth_year)
    {
        // Take a person's birth year (int) and return a new DOB object
        // suitable for him.
        if (!($birth_year > 0))
        {
            throw new InvalidArgumentException('birth_year must be positive');
        }

        $date_range = PiplApi_DateRange::from_years_range($birth_year, $birth_year);
        return (new PiplApi_DOB(array('date_range' => $date_range)));
    }

    public static function from_birth_date($birth_date)
    {
        // Take a person's birth date (Date) and return a new DOB
        // object suitable for him.
        if (!($birth_date <= new DateTime('now', new DateTimeZone('GMT'))))
        {
            throw new InvalidArgumentException('birth_date can\'t be in the future');
        }

        $date_range = new PiplApi_DateRange($birth_date, $birth_date);
        return (new PiplApi_DOB(array('date_range' => $date_range)));
    }

    public static function from_age($age)
    {
        # Take a person's age (int) and return a new DOB object
        # suitable for him.
        return (PiplApi_DOB::from_age_range($age, $age));
    }

    public static function from_age_range($start_age, $end_age)
    {
        // Take a person's minimal and maximal age and return a new DOB object
        // suitable for him.
        if (!($start_age >= 0 && $end_age >= 0))
        {
            throw new InvalidArgumentException('start_age and end_age can\'t be negative');
        }

        if ($start_age > $end_age)
        {
            $t = $end_age;
            $end_age = $start_age;
            $start_age = $t;
        }

        $start_date = new DateTime('now', new DateTimeZone('GMT'));
        $end_date = new DateTime('now', new DateTimeZone('GMT'));

        $start_date->modify('-' . $end_age . ' year');
        $start_date->modify('-1 year');
        $start_date->modify('+1 day');
        $end_date->modify('-' . $start_age . ' year');

        $date_range = new PiplApi_DateRange($start_date, $end_date);
        return (new PiplApi_DOB(array('date_range' => $date_range)));
    }
}