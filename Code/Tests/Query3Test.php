<?php


use PHPUnit\Framework\TestCase;

class Query3Test extends TestCase
{
    public function initialConf(): void
    {
        $_SERVER['REQUEST_METHOD'] = "POST";
        include("../config_newdb.inc.php");
        include("../dbconnection.php");
        $_POST['referenceDb'] = "Ensembl";
    }

    public function testDBConnection(): void
    {
        $conn = include("../dbconnection.php");
        $this->assertTrue($conn->connect_error == null);
        $this->assertTrue($conn->connect_errno == 0);
    }

    public function testQuery3Script(): void
    {
        $this->initialConf();
        $number = 0;
        $_SESSION['mydata'][$number] = "AT5G05980	2";

        $_SESSION['mydata_cutoffed'][$number] = '';
        $_SESSION['mydata_cutoffed'][$number] = htmlspecialchars($_SESSION['mydata'][$number]);
        $_SESSION['mydata_cutoffed'][$number] = substr($_SESSION['mydata_cutoffed'][$number], 0, -2);
        $_POST['mdata'] = $_SESSION['mydata_cutoffed'];

        $_POST["species"] = 'ath';

        $result = include("../mysql_query3.php");
        $this->assertTrue($result==1);


        $_SESSION['mydata'][$number] = "ENSCAFG00845004806  1";
        $_SESSION['mydata_cutoffed'][$number] = '';
        $_SESSION['mydata_cutoffed'][$number] = htmlspecialchars($_SESSION['mydata'][$number]);
        $_SESSION['mydata_cutoffed'][$number] = substr($_SESSION['mydata_cutoffed'][$number], 0, -2);
        $_POST['mdata'] = $_SESSION['mydata_cutoffed'];

        $_POST["species"] = 'cfa';

        $result = include("../mysql_query3.php");
        $this->assertTrue($result==1);
    }
}
