<?php


use PHPUnit\Framework\TestCase;

class Query1Test extends TestCase
{
    public function testWhichQueryReturnsRows(): void
    {
        $_POST['GeneID'] = 'ENSDARG00000042458';
        $_POST['referenceDb'] = "Ensembl";
        $result = include("../mysql_query1.php");
        $this->assertEquals(1, $result);
    }
}
