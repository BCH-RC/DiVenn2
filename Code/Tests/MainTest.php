<?php

use PHPUnit\Framework\TestCase;

class MainTest extends TestCase
{
    public function initialConf(): void
    {
        $_SERVER['REQUEST_METHOD'] = "POST";
        include_once("../config_newdb.inc.php");
        include_once("../dbconnection.php");
        include_once("../main.php");
    }

    public function testInitializationSessionVars(): void
    {
        $this->initialConf();

        initializationSessionVarsAtTheBeginningOfTheSession();
        $this->assertTrue($_SESSION['count_errors'] == -1);
        $this->assertTrue($_SESSION['cutoff'] == 0);
        $this->assertTrue($_SESSION['cutofftype'] == "");
        $this->assertTrue($_SESSION["exp_name"] == array());
        $this->assertTrue($_SESSION['filelist'] == array());
        $this->assertTrue($_SESSION['filenum'] == -1);
        $this->assertTrue($_SESSION['genes_values'] == array()); //1: up-regulated, 2: down-regulated
        $this->assertTrue($_SESSION['genes_IDs'] == array());
        $this->assertTrue($_SESSION['messagelist'] == array());
        $this->assertTrue($_SESSION['mycolor'] == ["#a23388", "#ccffff", "#99cc33", "#ff9900", "#9966cc",
                "#0099cc", "#663300", "#39b5b8", "#550000", "#007323"]);
        $this->assertTrue($_SESSION['mydata'] == array());
        $this->assertTrue($_SESSION['mydata_cutoffed'] == array());
        $this->assertTrue($_SESSION['remaining_data'] == array());
        $this->assertTrue($_SESSION['species'] == "");
        $this->assertTrue($_SESSION['uploadtype'] == "");

        $this->assertSessionVariables_CF();
        $this->assertFalse($_SESSION['filenum'] == 1);
        $this->assertSessionVariables_GU(); //1: up-regulated, 2: down-regulated
        $this->assertSessionVariablesGUPart();
    }

    public function testInitializeFilelist(): void
    {
        $this->initialConf();

        $_FILES["file"] = "exp0.txt";
        initializeFilelist();
        $this->assertTrue($_SESSION['filelist'] == "exp0.txt");

        $this->assertFalse($_SESSION['filelist'] == "");
        $this->assertFalse($_SESSION['filelist'] === null);
    }

    public function testInitializationSessionVarsAfterSubmit(): void
    {
        $this->initialConf();

        $_POST['cutoff'] = 0.0;
        $_POST['cutofftype'] = "CustomAny";
        $_POST['exp_num'] = 1;
        $_POST['referenceDb'] = 'Ensembl';
        $_POST['species'] = "ath";
        $_POST['uploadtype'] = "UploadText";
        initializationSessionVarsAfterSubmit();
        $this->assertTrue(empty($_POST) == false);
        $this->assertTrue($_SESSION['count_errors'] == -1);
        $this->assertTrue($_SESSION['cutoff'] == 0.0);
        $this->assertTrue($_SESSION['cutofftype'] == "CustomAny");
        $this->assertTrue($_SESSION["exp_name"] == array());
        $this->assertTrue($_SESSION['filenum'] == 1);
        $this->assertTrue($_SESSION['genes_values'] == array()); //1: up-regulated, 2: down-regulated
        $this->assertTrue($_SESSION['genes_IDs'] == array());
        $this->assertTrue($_SESSION['messagelist'] == array());
        $this->assertTrue($_SESSION['mydata'] == array());
        $this->assertTrue($_SESSION['mydata_cutoffed'] == array());
        $this->assertTrue($_SESSION['remaining_data'] == array());
        $this->assertTrue($_SESSION['species'] == "ath");
        $this->assertTrue($_SESSION['uploadtype'] == "UploadText");
        $this->assertNotEmpty($_SESSION['mycolor']);

        $this->assertFalse($_POST === null);
        $this->assertSessionVariables_CF();
        $this->assertFalse($_SESSION['filenum'] == 0);
        $this->assertSessionVariables_GU();
    }

    public function testCreateMessage(): void
    {
        $this->initialConf();

        $n = 0;
        $message = createMessageBasedOnFileError($n);
        $this->assertEquals("There is no files", $message);

        $_FILES["file"] = array();
        $_FILES["file"]["error"] = [0 => 0, 1 => 1, 2 => 2, 4 => 4];
        $_FILES["file"]["name"] = [0 => "exp0.txt", 1 => "exp1.txt", 2 => "exp2.txt", 4 => "exp3.txt"];
        $message = createMessageBasedOnFileError($n);
        $this->assertEquals("exp0.txt was uploaded successfully.", $message);

        $n = 1;
        $message = createMessageBasedOnFileError($n);
        $this->assertEquals("Sorry, there was a problem uploading." . $_FILES["file"]["name"][$n], $message);

        $n = 2;
        $message = createMessageBasedOnFileError($n);
        $this->assertEquals("exp2.txt is too big to upload.", $message);

        $n = 4;
        $message = createMessageBasedOnFileError($n);
        $this->assertEquals("No file selected.", $message);
    }

    public function testvalidateAndMoveUploadedFileToTargetDir():void
    {
        $this->initialConf();
        $target_dir = "/var/www/html/divenn/uploads/";
        $nr = 0;
        $extension = 'tsv';
        $_FILES["file"]["error"] = [0 => 0];
        $_FILES["file"]["tmp_name"] = [0 => 'test_divenn_canis.tsv'];
        $_FILES["file"]["name"] = [0 => 'test_divenn_canis.tsv'];
        $extensions = ['csv' => ",", 'tsv' => "\t"];

        $result = validateAndMoveUploadedFileToTargetDir($target_dir, $nr, $extension);
        if ($result == true)
            $data = getFileContent($target_dir . $_FILES["file"]["name"][$nr], $nr, $extensions[$extension]);
        $this->assertEquals("ENSCAFG00845016902", $data[0][0][0]);
        $this->assertEquals("1", $data[0][0][1]);
    }

    public function testFileFailureCheck(): void
    {
        $this->initialConf();
        $error_number = 0;

        $failure = fileFailureCheck($error_number, 0, "");

        $this->assertEquals(false, $failure);

        $error_number = 1;
        $n = 1;
        $message = "";

        $failure = fileFailureCheck($error_number, $n, $message);
        $this->assertEquals(true, $failure);
    }

    public function testDetermineRegulation(): void
    {
        $this->initialConf();
        $this->assertEquals("2", determineRegulation(-1, 2));
        $this->assertEquals("2", determineRegulation(0, 2));
        $this->assertEquals("2", determineRegulation(2, 2));
        $this->assertEquals("1", determineRegulation(3, 2));
        $this->assertEquals("1", determineRegulation(4, 2));
        $this->assertEquals("1", determineRegulation(0.01, 0));
    }

    public function testValidateCutoff(): void
    {
        $this->initialConf();

        $_SESSION['cutoff'] = null;
        $this->assertEquals(false, validateCutoff());

        $_SESSION['cutoff'] = "example_value";
        $this->assertEquals(false, validateCutoff());

        $_SESSION['cutoff'] = 0.5;
        $this->assertEquals(true, validateCutoff());

        $_SESSION['cutoff'] = -1;
        $this->assertEquals(true, validateCutoff());

        $_SESSION['cutoff'] = 0;
        $this->assertEquals(true, validateCutoff());
    }

    public function testArrayHasOnlyPassedValue(): void
    {
        $this->initialConf();

        $this->assertTrue(arrayContainsOnlyPassedValue($_SESSION['mydata_cutoffed'], ''));
        $this->assertTrue(arrayContainsOnlyPassedValue([], ''));

        $this->assertFalse(arrayContainsOnlyPassedValue([0 => "example1", 1 => "example2"], ''));
        $this->assertFalse(arrayContainsOnlyPassedValue([0 => "example1", 1 => "example2"], "example1"));
    }

    public function testValidation(): void
    {
        $this->initialConf();

        $this->assertEquals(1, validation(null, 0, 'text'));
        $data = "AT5G05980	2";
        $this->assertEquals(0, validation($data, 0, 'text'));

        $data = "ENSCAFG00845016902 1";
        $this->assertEquals(1, validation($data, 0, 'text'));

        $data = "AT5G05980	2	F4K2A1	830484	DFB; DHFS-FPGS homolog B	Folate biosynthesis - 
                Arabidopsis thaliana (thale cress)	GO:0009570	chloroplast stroma	C
                -	-	-	-	-	Folate biosynthesis - Arabidopsis thaliana (thale cress)	
                GO:0006730	one-carbon metabolic process	P
                -	-	-	-	-	Folate biosynthesis - Arabidopsis thaliana (thale cress)	
                GO:0004326	tetrahydrofolylpolyglutamate synthase activity	F
                -	-	-	-	-	Metabolic pathways - Arabidopsis thaliana (thale cress)	
                GO:0009570	chloroplast stroma	C
                -	-	-	-	-	Metabolic pathways - Arabidopsis thaliana (thale cress)	
                GO:0006730	one-carbon metabolic process	P
                -	-	-	-	-	Metabolic pathways - Arabidopsis thaliana (thale cress)	
                GO:0004326	tetrahydrofolylpolyglutamate synthase activity	F";

        $this->assertEquals(0, validation($data, 0, 'text'));

        $data = array(0 => [['AT5G05980', '2'], ['ENSCAFG00845016902',  '1']]);
        $this->assertEquals(0, validation($data, 0, 'file'));
    }

    public function testCreateMyDataCutoffed(): void
    {
        $this->initialConf();
        $number = 0;
        $_SESSION['cutoff'] = 0;
        $_SESSION['filenum'] = 1;
        $_SESSION['cutofftype'] = "CutoffAny";
        $_SESSION['mydata'][$number] = "AT5G05980	2";

        $_SESSION['mydata_cutoffed'][$number] = '';
        $_SESSION['mydata_cutoffed'][$number] = htmlspecialchars($_SESSION['mydata'][$number]);
        $_SESSION['mydata_cutoffed'][$number] = substr($_SESSION['mydata_cutoffed'][$number], 0, -2);
        $this->assertTrue($_SESSION['mydata_cutoffed'][$number] == "AT5G05980");

        $_SESSION['cutoff'] = 1;
        $_SESSION['filenum'] = 1;
        $_SESSION['genes_IDs'][$number] = [0 => "AT5G05980", 1 => "AT1G21110"];
        $_SESSION['genes_values'][$number] = [0 => "0.5", 1 => "1.5"];

        $remaining_data_0 = "F4K2A1	830484	DFB; DHFS-FPGS homolog B	Folate biosynthesis - 
        Arabidopsis thaliana (thale cress)	GO:0009570	chloroplast stroma	C
-	-	-	-	-	Folate biosynthesis - Arabidopsis thaliana (thale cress)	
GO:0006730	one-carbon metabolic process	P
-	-	-	-	-	Folate biosynthesis - Arabidopsis thaliana (thale cress)	
GO:0004326	tetrahydrofolylpolyglutamate synthase activity	F
-	-	-	-	-	Metabolic pathways - Arabidopsis thaliana (thale cress)	
GO:0009570	chloroplast stroma	C
-	-	-	-	-	Metabolic pathways - Arabidopsis thaliana (thale cress)	
GO:0006730	one-carbon metabolic process	P
-	-	-	-	-	Metabolic pathways - Arabidopsis thaliana (thale cress)	
GO:0004326	tetrahydrofolylpolyglutamate synthase activity	F";

        $remaining_data_1 = "Q9LPU6	838707	IGMT3; O-methyltransferase family protein	-	GO:0005829	cytosol	C
-	-	-	-	-	-	GO:0008171	O-methyltransferase activity	F
-	-	-	-	-	-	GO:0046983	protein dimerization activity	F";

        $_SESSION['remaining_data'][$number] = [0 => $remaining_data_0, 1 => $remaining_data_1];

        $result = "AT5G05980	0.5	F4K2A1	830484	DFB; DHFS-FPGS homolog B	Folate biosynthesis - 
        Arabidopsis thaliana (thale cress)	GO:0009570	chloroplast stroma	C
-	-	-	-	-	Folate biosynthesis - Arabidopsis thaliana (thale cress)	
GO:0006730	one-carbon metabolic process	P
-	-	-	-	-	Folate biosynthesis - Arabidopsis thaliana (thale cress)	
GO:0004326	tetrahydrofolylpolyglutamate synthase activity	F
-	-	-	-	-	Metabolic pathways - Arabidopsis thaliana (thale cress)	
GO:0009570	chloroplast stroma	C
-	-	-	-	-	Metabolic pathways - Arabidopsis thaliana (thale cress)	
GO:0006730	one-carbon metabolic process	P
-	-	-	-	-	Metabolic pathways - Arabidopsis thaliana (thale cress)	
GO:0004326	tetrahydrofolylpolyglutamate synthase activity	F\r
AT1G21110	1.5	Q9LPU6	838707	IGMT3; O-methyltransferase family protein	-	GO:0005829	cytosol	C
-	-	-	-	-	-	GO:0008171	O-methyltransferase activity	F
-	-	-	-	-	-	GO:0046983	protein dimerization activity	F";
        createMyDataCutoffed($_SESSION['cutofftype']);
        $this->assertEquals($result, $_SESSION['mydata_cutoffed'][$number]);
    }


    #------------------------------------------Support functions------------------------------------------#
    public function assertSessionVariables_CF(): void
    {
        $this->assertFalse($_SESSION['count_errors'] == 1);
        $this->assertFalse($_SESSION['cutoff'] == 1);
        $this->assertFalse($_SESSION['cutofftype'] === null);
        $this->assertFalse($_SESSION["exp_name"] === null);
        $this->assertFalse($_SESSION['filelist'] === null);
    }

    public function assertSessionVariables_GU(): void
    {
        $this->assertFalse($_SESSION['genes_values'] === null); //1: up-regulated, 2: down-regulated
        $this->assertSessionVariablesGUPart();
    }

    public function assertSessionVariablesGUPart(): void
    {
        $this->assertFalse($_SESSION['genes_IDs'] === null);
        $this->assertFalse($_SESSION['messagelist'] === null);
        $this->assertFalse($_SESSION['mycolor'] == array());
        $this->assertFalse($_SESSION['mydata'] === null);
        $this->assertFalse($_SESSION['mydata_cutoffed'] === null);
        $this->assertFalse($_SESSION['remaining_data'] === null);
        $this->assertFalse($_SESSION['species'] === null);
        $this->assertFalse($_SESSION['uploadtype'] === null);
    }
}
