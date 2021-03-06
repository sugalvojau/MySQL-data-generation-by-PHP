<?php

die('The script is dangerous to your database, because it will be filled with many random rows. If You accept that then remove this code line and go to the random mass of data.');


class RandomValue {

    private $Pdo = null;

    public function __construct(\PDO $Pdo)
    {
        $this->Pdo = $Pdo;
    }

    public function getTableValueDatetime($size, $fieldData, $unixFrom = 1, $unixTo = 2899849848) {
        $timestamp = mt_rand($unixFrom, $unixTo);

        return date("Y-m-d H:i:s", $timestamp);

    }

    public function getTableValueChar($size, $fieldData) {

        $size = mt_rand(0,$size); // char could be smaller

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $size; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function getTableValueVarchar($size, $fieldData) {
        return $this->getTableValueChar($size, $fieldData);
    }

    function unichr($i)
    {
        return iconv('UCS-4LE', 'UTF-8', pack('V', $i));
    }

    public function getTableValueText($size, $fieldData, $defaultSize = 65535) {

        $size = isset($size) && ($size !== '') ? $size : $defaultSize;

        $size = mt_rand(0,$size); // char could be smaller

        $characters = array('','`','ą','č','ę','ė','į','š','ų','ū','9','0','-','ž','\\','q','w','e','r','t','y','u','i','o','p','[',']','a','s','d','f','g','h','j','k','l',';','\'','`','z','x','c','v','b','n','m','','','.','/','7','8','9','4','5','6','1','2','3','0','','','~','Ą','Č','Ę','Ė','Į','Š','Ų','Ū','(',')','_','Ž','|','Q','W','E','R','T','Y','U','I','O','P','{','}','A','S','D','F','G','H','J','K','L',':','"','Z','X','C','V','B','N','M','<','>','?');

        $charactersLength = count($characters);
        $randomString = '';

        if((rand(0,10) == 0)) { // Every about 10th line let`s be empty
            return '';
        }

        for ($i = 0; $i < $size; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)] . ((rand(0,45) == 0) ? ' ' : '') . ((rand(0,45*200) == 0) ? "\n" : ''); // + Add space every about 45 letters as the longest word in englis is 45 letters (Pneumonoultramicroscopicsilicovolcanoconiosis)
        }
        return $randomString;


    }

    public function getTableValueMediumtext($size, $fieldData) {
        return $this->getTableValueText($size, $fieldData, $defaultSize = 98765); // The real value of 16777215 is to much for 'for'
    }

    public function getTableValueTinyint($size, $fieldData) {
        if($this->fieldIsSigned($fieldData)) {
            return mt_rand(-128,127);
        } else {
            return mt_rand(0,255);
        }
    }

    public function getTableValueSmallint($size, $fieldData) {
        if($this->fieldIsSigned($fieldData)) {
            return mt_rand(-32768,32767);
        } else {
            return mt_rand(0,65535);
        }
    }

    public function getTableValueMediumint($size, $fieldData) {
        if($this->fieldIsSigned($fieldData)) {
            return mt_rand(-8388608,8388607);
        } else {
            return mt_rand(0,16777215);
        }
    }

    public function getTableValueInt($size, $fieldData) {
        if($this->fieldIsSigned($fieldData)) {
            return mt_rand(-2147483648,2147483647);
        } else {
            return mt_rand(0,4294967295);
        }
    }

    public function getTableValueBigint($size, $fieldData) {
        if($this->fieldIsSigned($fieldData)) {
            return mt_rand(-9223372036854775808,9223372036854775807);
        } else {
            return mt_rand(0,18446744073709551615);
        }
    }

    protected function fieldIsSigned($fieldData) {

        if(!$fieldData) {
            return false;
        }

        return true;
    }

}

class PopulateDb {

    private $Pdo = null;
    private $RandomValue = null;

    public function __construct(\PDO $Pdo, \RandomValue $RandomValue)
    {
        $this->Pdo = $Pdo;
        $this->randomValueObj = $RandomValue;
    }

    public function getAllTablesOfDb($dbName) {

        $PdoStatement = $this->Pdo->prepare('SELECT GROUP_CONCAT(table_name) FROM information_schema.tables WHERE table_schema=:dbName');
        $PdoStatement->bindParam(':dbName', $dbName, PDO::PARAM_STR);
        $PdoStatement->execute();
        $tablesStr = @reset($PdoStatement->fetch(PDO::FETCH_ASSOC));
        $tables = explode(',', $tablesStr);

        return $tables;
    }

    private function extractDataType($typePlainText) {

        if(!$typePlainText) {
            return null;
        }

        // @TODO: Take care of all datatypes and modifications of MySQL
        $matches = null;
        preg_match('/^(.*)(\\(\\d+\\))|(.*)/', $typePlainText, $matches);

        return !empty($matches[1]) ? $matches[1] : $matches[0];

    }

    private function extractDataTypeSize($typePlainText) {

        if(!$typePlainText) {
            return null;
        }

        // @TODO: Take care of all datatypes and modifications of MySQL
        $matches = null;
        preg_match('/^(.*)(\\(\\d+\\))|(.*)/', $typePlainText, $matches);

        if(!isset($matches[2])) {
            return null;
        }

        $returnValue = trim($matches[2], '()');

        return isset($returnValue) ? $returnValue : null;

    }


    /**
     * @param stdClass $fieldData Row of 'DESCRIBE <table_name>' by MySQL
     * @return null
     */
    public function getGeneratedFieldValue(\stdClass $fieldData) {

        $type = $this->extractDataType($fieldData->Type);
        $typeSize = $this->extractDataTypeSize($fieldData->Type);

        if(!$type) {
            return null;
        }

        $methodName = 'getTableValue' . ucfirst(strtolower($type));

        $generatedValue = null;
        if(method_exists($this->randomValueObj, $methodName)) {
            $generatedValue = $this->randomValueObj->$methodName($typeSize, $fieldData);
        } else {
            die('No method: ' . $methodName);
        }

        return $generatedValue;

    }







    public function populateTable($tableName, $entriesCount = 100) {
        if(!$tableName) {
            return;
        }

        $tblDescription = $this->getTableDescription($tableName);

        $template = 'INSERT INTO `' . $tableName . '` ({{COLUMNS}}) VALUES ({{VALUES}});';

        for($i = 0; $i <= $entriesCount; $i++) {

            $columns = array();
            $valuesForBind = array();
            $values = array();

            foreach($tblDescription as $fieldData) {
                if($fieldData->Extra == 'auto_increment') {
                    continue;
                }
                $columns[$fieldData->Field] = '`' . $fieldData->Field . '`';
                $valuesForBind[$fieldData->Field] = ':' . $fieldData->Field;
                //$values[$fieldData->Field] = '\'' . $this->getGeneratedFieldValue($fieldData) . '\'';

            }

            $q = strtr($template, array(
                '{{COLUMNS}}' => implode(',', $columns),
                '{{VALUES}}' => implode(',', $valuesForBind)
            ));

            $sth = $this->Pdo->prepare($q);

            foreach($tblDescription as $fieldData) {
                if(!isset($valuesForBind[$fieldData->Field])) {
                    continue;
                }
                $sth->bindValue($valuesForBind[$fieldData->Field], $this->getGeneratedFieldValue($fieldData), PDO::PARAM_STR);
            }

            //$PdoStatement =

            //print_r($q); die();

            $sth->execute();
            //$PdoStatement->bindParam(':dbName', $dbName, PDO::PARAM_STR);
            //$PdoStatement->execute();
            //$tablesStr = @reset($PdoStatement->fetch(PDO::FETCH_ASSOC));
            //$tables = explode(',', $tablesStr);

        }


    }

    public function getTableDescription($tableName) {
        if(!$tableName) {
            return;
        }

        $PdoStatement = $this->Pdo->prepare('DESCRIBE ' . $tableName);
        $PdoStatement->execute();

        return $PdoStatement->fetchAll(PDO::FETCH_OBJ);

    }


}

ini_set('max_execution_time', 3600);

$dbHost = "localhost";
$dbName = "pdarbais";
$dbUsername = "pdarbais";
$dbPassword = "pdarbais";
$tables = array();
$tablesToSkip = array('acctypes', 'cats', 'fbcache', 'settings');
$randomRowsPerTableCount = 1000;

$Pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=UTF8",$dbUsername,$dbPassword);
$Pdb = new PopulateDb($Pdo, new RandomValue($Pdo));


// Get tables
if(empty($tables)) { // Get all tables of $dbName
    $tables = $Pdb->getAllTablesOfDb($dbName);
}

foreach($tables as $tableName) {
    if(in_array($tableName, $tablesToSkip)) {
        continue;
    }
    $Pdb->populateTable($tableName, $randomRowsPerTableCount);
}



// Foreach


var_dump($tables);


#INSERT INTO `pdarbais`.`nee` (`nee_nee`, `nee_cat`, `deleted`) VALUES (1, 2, 0);