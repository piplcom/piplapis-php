<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/field.php';

class PiplApi_Education extends PiplApi_Field
{
    // Education information of a person.

    protected $children = array('degree', 'school', 'date_range', 'display');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // `degree` and `school` should both be strings.
        // `date_range` is A DateRange object (PiplApi_DateRange),
        // that's the time the person was studying.

        if (!empty($degree))
        {
            $this->degree = $degree;
        }
        if (!empty($school))
        {
            $this->school = $school;
        }
        if (!empty($date_range))
        {
            $this->date_range = $date_range;
        }
        if (!empty($display))
        {
            $this->display = $display;
        }
    }
}