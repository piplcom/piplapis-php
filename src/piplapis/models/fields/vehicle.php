<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/field.php';

class PiplApi_Vehicle extends PiplApi_Field
{
    protected $children = array('vin', 'year', 'make', 'model', 'color', 'vehicle_type', 'display');
    protected $attributes = array('is_vin_valid');

    function __construct($params=array())
    {
        extract($params);
        parent::__construct($params);

        // vin
        // year
        // make
        // model
        // color
        // vehicle_type
        // is_vin_valid


        if (!empty($vin))
        {
            $this->vin = $vin;
        }
        
        if (!empty($year))
        {
            
            $this->year = $year;
        }
        
        if (!empty($make))
        {
            $this->make = $make;
        }
        
        if (!empty($model))
        {
            $this->model = $model;
        }
        
        if (!empty($color))
        {
            $this->color = $color;
        }
        
        if (!empty($vehicle_type))
        {
            $this->vehicle_type = $vehicle_type;
        }
        
        if (!empty($is_vin_valid))
        {
            $this->is_vin_valid = $is_vin_valid;
        }

        $this->display = $this->get_display();
    }

    protected function get_display(){
        $attr_list = array('year', 'make', 'model', 'vehicle_type', 'color');
        $display = array();
        
        foreach ($attr_list as $attr){
            if (empty($this->$attr)){
                continue;
            }
            
            $display[] = ucwords(strtolower($this->$attr));
        }

        if (!empty($display)){
            $display[] = '-';
        }


        $display[] = 'VIN';

        if (!empty($this->vin)){
            $display[] = $this->vin;
        }


        return implode(' ', $display);
    }


    public static function validate_vin_checksum($vin){
        $vin = strtolower($vin);

        $check_digit = $vin[8];
        $replace_map = (object)[
            "1" => ["a", "j"],
            "2" => ["b", "k", "s"],
            "3" => ["c", "l", "t"],
            "4" => ["d", "m", "u"],
            "5" => ["e", "n", "v"],
            "6" => ["f", "w"],
            "7" => ["g", "p", "x"],
            "8" => ["h", "y"],
            "9" => ["r", "z"],
        ];

        $positional_weights = array(8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2);

        foreach ($replace_map as $key => $value) {
            foreach ($value as $letter) {
                $vin = str_replace($letter, $key, $vin);
            }
        }

        $checksum = 0;

        for ($i = 0; $i < strlen($vin); $i++){
            $number = $vin[$i];

            if ($i === 8){
                continue;
            }

            $result = intval($number) * $positional_weights[$i];
            $checksum += $result;
        }
        
        $checksum %= 11;
        
        if ($checksum === 10){

            $checksum = 'x';
        }
        
        return strval($checksum) === $check_digit;
    }

    public static function is_vin_valid($vin){
        $condition = (
            !empty($vin) &&
            strlen($vin) === 17 &&
            !PiplApi_Utils::in_string(array('i', 'o', 'q'), strtolower($vin)) &&
            !in_array(strtolower($vin[9]), array('u', 'z', '0')) &&
            ctype_alnum($vin) &&
            self::validate_vin_checksum($vin)
        );
        
        return $condition;
    }

    public function is_searchable()
    {
        return $this->is_vin_valid($this->vin);
    }

    public function __toString(){
        return $this->content;
    }
}