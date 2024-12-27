<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Number Validation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 300px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .result {
            margin-top: 20px;
            font-size: 1.2em;
            padding: 10px;
            border-radius: 4px;
        }
        .saudi {
            color: green;
            font-weight: bold;
            /*background-color: #e6ffe6;*/
        }
        .international {
            color: black;
            font-weight: bold;
            /*background-color: #f0f0f0;*/
        }
        .invalid {
            color: red;
            font-weight: bold;
            /*background-color: #ffe6e6;*/
        }
    </style>
</head>

<body>
    <?php
    /**
     * Class MobileValidation
     *
     * This class handles the validation of mobile numbers, determining if they are local (Saudi) or international.
     */
    class MobileValidation 
    {
        /**
         * @var string $mobileNumber The mobile number to be validated.
         */
        public $mobileNumber;

        /**
         * @var string $countryCode The country code of the mobile number.
         */
        public $countryCode;

        /**
         * @var int $validLength The valid length of the mobile number.
         */
        public $validLength;

        /**
         * @var string $mobilePattern The pattern used to validate the mobile number.
         */
        public $mobilePattern;

        /**
         * @var array $countryTerritory The list of country territories.
         */
        private $countryTerritory = [];

        /**
         * @var string $file_path The path to the XML file containing international number metadata.
         */
        private $file_path = 'International_Numbers_List_Metadata.xml';

        /**
         * @var SimpleXMLElement $xml The XML object loaded from the metadata file.
         */
        private $xml;

        /**
         * MobileValidation constructor.
         *
         * @param string $mobileNumber The mobile number to be validated.
         * @throws Exception If the XML file cannot be loaded.
         */
        public function __construct($mobileNumber) {
            $this->mobileNumber = $mobileNumber;
            if (file_exists($this->file_path)) {
                $this->xml = simplexml_load_file($this->file_path);
                if ($this->xml === false) {
                    throw new Exception("Error: Cannot create object from XML file");
                }
            } else {
                throw new Exception("Error: XML file not found");
            }
        }

        /**
         * Validate whether the number is local (Saudi) or international.
         *
         * @return string|false "local" if the number is local, "international" if it is international, false otherwise.
         */
        public function validatingMobile() {
            $validNumber = $this->getValidNumber();
            if ($this->validateUserInput($validNumber)) {
                if ($this->isItSaudiNumber()) {
                    return "local";
                } elseif ($this->isItInternationalNumber()) {
                    return "international";
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        /**
         * Check whether the number matches Saudi numbers standard.
         *
         * @return bool True if the number matches Saudi standards, false otherwise.
         */
        public function isItSaudiNumber() {
            $phone = $this->getValidNumber();
            $saudiPattern = '/^(5\d{8}|9665\d{8})$/';
            $this->validLength = 9;
            $this->countryCode = "966";
            $this->mobilePattern = $saudiPattern;
            return preg_match($saudiPattern, $phone);
        }

        /**
         * Check all possible lengths and patterns for the phone number.
         *
         * @return bool True if the number matches any international pattern, false otherwise.
         */
        public function isItInternationalNumber() {
            $valid_number = $this->getValidNumber();
            $mobileElements = $this->getMobileElement($this->getAllTerritory());
            foreach ($mobileElements as $mobileElement) {
                $lengths = (string) $mobileElement->possibleLengths['national'];
                $parsedLengths = $this->parsePossibleLengths($lengths);
                $numberPattern = (string) $mobileElement->nationalNumberPattern;
                $numberPattern = str_replace(' ', '', $numberPattern);
                $numberPattern = preg_replace('/\s+/', '', $numberPattern);
                foreach ($parsedLengths as $length) {
                    $countryCode = $this->getCountryCode();
                    $local_number = substr($valid_number, strlen($countryCode));
                    if (strlen($local_number) == $length && preg_match("/$numberPattern/", $local_number)) {
                        $this->validLength = $length;
                        $this->countryCode = $countryCode;
                        $this->mobilePattern = $numberPattern;
                        return true;
                    }
                }
            }
            return false;
        }

        /**
         * Get the country ID for the mobile number.
         *
         * @return string The country ID or "NoCountryID!" if not found.
         */
        public function getCountryID() {
            $valid_number = $this->getValidNumber();
            foreach ($this->xml->territories->territory as $territory) {
                $CountryID = (string) $territory['id'];
                $countryCode = (string) $territory['countryCode'];
                if (strpos($valid_number, $countryCode) === 0) {
                    return $CountryID;
                }
            }
            return "NoCountryID!";
        }

        /**
         * Get all territories matching the country code of the mobile number.
         *
         * @return array The list of matching territories.
         */
        public function getAllTerritory() {
            $valid_number = $this->getValidNumber();
            foreach ($this->xml->territories->territory as $territory) {
                $countryCode = (string) $territory['countryCode'];
                if (strpos($valid_number, $countryCode) === 0) {
                    $this->countryTerritory[] = $territory;
                }
            }
            return $this->countryTerritory;
        }

        /**
         * Get the mobile elements related to the mobile number.
         *
         * @param array $territoriesArray The array of territories.
         * @return array The array of mobile elements.
         */
        public function getMobileElement($territoriesArray) {
            $mobileElementsArray = [];
            foreach ($territoriesArray as $territory) {
                if (isset($territory->mobile)) {
                    $mobileElementsArray[] = $territory->mobile;
                }
            }
            return $mobileElementsArray;
        }

        /**
         * Get the lengths of the mobile elements.
         *
         * @param array $mobileElements The array of mobile elements.
         * @return array The parsed lengths.
         */
        public function getLengths($mobileElements) {
            foreach ($mobileElements as $mobileElement) {
                $lengths = (string) $mobileElement->possibleLengths['national'];
                $parsedLengths = $this->parsePossibleLengths($lengths);
            }
            return $parsedLengths;
        }

        /**
         * Get the mobile pattern for the mobile number.
         */
        public function getMobilePattern() {
            $mobileElements = $this->getMobileElement($this->getAllTerritory());
            foreach ($mobileElements as $mobileElement) {
                $numberPattern = (string) $mobileElement->nationalNumberPattern;
                echo $numberPattern . "<br>";
            }
        }

        /**
         * Get the mobile number.
         *
         * @return string The mobile number.
         */
        public function getMobileNumber() {
            return $this->mobileNumber;
        }

        /**
         * Validate the user input.
         *
         * @param string $number The number to validate.
         * @return bool True if the number is valid, false otherwise.
         */
        public function validateUserInput($number) {
            $pattern = '/^(0|\+?\d[\d\s-]{0,19}\d)$/';
            return preg_match($pattern, $number);
        }

        /**
         * Get the valid number after cleaning and validation.
         *
         * @return string|null The valid number or null if invalid.
         */
        public function getValidNumber() {
            $cleanedNumber = $this->plusAndZeroClean($this->mobileNumber);
            if ($this->validateUserInput($cleanedNumber)) {
                return $cleanedNumber;
            }
            return null;
        }

        /**
         * Check the country code of the phone number.
         *
         * @param string $phone The phone number.
         * @return SimpleXMLElement|null The territory if found, null otherwise.
         */
        public function checkCountryCode($phone) {
            foreach ($this->xml->territories->territory as $territory) {
                $this->countryCode = (string) $territory->attributes()->countryCode;
                if (substr($phone, 0, strlen($this->countryCode)) == $this->countryCode) {
                    return $territory;
                }
            }
            return null;
        }

        /**
         * Get the country code for the mobile number.
         *
         * @return string|false The country code or false if not found.
         */
        public function getCountryCode() {
            $valid_number = $this->getValidNumber();
            foreach ($this->xml->territories->territory as $territory) {
                $countryCode = (string) $territory['countryCode'];
                if (strpos($valid_number, $countryCode) === 0) {
                    return $countryCode;
                }
            }
            return false;
        }

        /**
         * Clean the phone number by removing spaces, hyphens, leading zeros, and plus signs.
         *
         * @param string $phone The phone number to clean.
         * @return string The cleaned phone number.
         */
        private function plusAndZeroClean($phone) {
            $phone = preg_replace('/[\s-]+/', '', $phone);
            return preg_replace('/^\+|^0{1,2}/', '', $phone);
        }

        /**
         * Parse the possible lengths of the mobile number.
         */
        private function parsePossibleLengths($length) 
        {   
            $numberLength = [];
            $numberLength = explode(',', $length);
            if(strpos($length, '-') !== false) 
            {
                $length = trim($length, '[]');
                list($start, $end) = explode('-', $length);
                for ($i = (int)$start; $i <= (int)$end; $i++)
                {
                    $numberLength[] = $i;
                }
            }
            else
            {
                $numberLength[] = (int)$length;
            }
            return $numberLength;
        }
    }

    ?>
        <div class="container">
        <h1>Phone Number Validation</h1>
        <form method="post" action="">
            <label for="phone">Enter your phone number:</label>
            <input type="text" id="phone" name="phone" required>
            <button type="submit">Validate</button>
        </form>

        <div class="result">
            <?php
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $phone = $_POST['phone'];

                // Assuming MobileValidation class is already included
                $validate = new MobileValidation($phone);
                $validNumber = $validate->getValidNumber();

                if ($validate->validatingMobile() == "local") {
                    $validNumber = (substr($validNumber, 0, 3) === '966') ? $validNumber : '966' . $validNumber; // add 966 if it is not added 
                    echo "<span class='saudi'>It is Saudi Number $validNumber</span><br>";
                    //echo "Country Code is: $validate->countryCode<br>Length of Number is: $validate->validLength<br>Pattern of number is: $validate->mobilePattern<br>";
                } elseif ($validate->validatingMobile() == "international") {
                    echo "<span class='international'>It is International Number $validNumber</span><br>";
                    //echo "Country Code is: $validate->countryCode<br>Length of Number is: $validate->validLength<br>Pattern of number is: $validate->mobilePattern<br>";
                } else {
                    echo "<span class='invalid'>Invalid phone format! $phone</span><br>";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>