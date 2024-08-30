<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $allToolResult = [];
    $warningVar = $randNum = $upload_dir = $uniprotID = "";

    // Capitalize the tools header name
    function capiFrstChar($char){
        if($char == 'mCSM'){
            return $char;
        }elseif($char == 'sdm'){
            return strtoupper($char);
        }elseif($char == 'snpsGO'){
            return "SNPs&Go";
        }else{
            return ucfirst($char);
        }
    }

    // During the pipeline processing user can reset the entire page. 
    if (!empty($_POST["reset_randNum"])){
        $unipRandNum =  explode(", ", $_POST["reset_randNum"]);
        $process_name = "automate.py {$unipRandNum[0]} {$unipRandNum[1]}";
        exec("pgrep -f '$process_name'", $output);
        // array_pop($output);
        foreach($output as $key){
            exec("kill -9 $key");
        }
        rrmdir("./fileUpload/".$unipRandNum[1]."/");
        header("Location: mutXplor.php");
        exit;
    }

    // remove entire directory
    function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects=scandir($dir);
            foreach ($objects as $object) {
                if ($object !="."&& $object !="..") {
                    if (filetype($dir."/".$object)=="dir") rrmdir($dir."/".$object);
                    else unlink($dir."/".$object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    // Create Random Folder 
    function makedDirectory($randNum, $upload_dir){
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777);
        } else {
            while (true) {
                global $randNum, $upload_dir;
                $randNum = rand(1, 1000);
                $upload_dir = "./fileUpload/".$randNum."/";
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777);
                    break;
                }
            }
        }
    }

    function download_fastaFile($uniprotID, $randNum){
        $command = "python3 mutXplorScripts/download_fastaFile.py $uniprotID $randNum".' 2>&1 & echo $!; ';
        exec($command);
    }

    function fastaHeader($sequence){
        $seqCount = 0;
        $lines = array_filter(explode("\n", $sequence));
        foreach($lines as $val){
            if (preg_match("/^>/", $val[0])){
                $seqCount++;
            }
        }

        if (strpos($lines[0], ">") !== false and $seqCount == 1) {
            return true;
        } else {
            global $warningVar; 
            $warningVar = "Please provide appropriate fasta sequence";
        }
    }

    function validateFasta($fastaFileID) {
        // 0 => False, 1 and 2 => True
        if (!empty($_FILES[$fastaFileID]['name'])) {
            return fastaHeader(file_get_contents($_FILES[$fastaFileID]['tmp_name'])) ? 1 : 0;
        } else {
            return fastaHeader($_POST["writeFastaSeq"]) ? 2 : 0;
        }
    }

    // Uploading the file like mutation_file, pdb file, fasta file
    function file_upload_function($ID, $fileName, $upload_dir, $fileformat) {
        if ($ID == "write-mutation-list" || $ID == "writeFastaSeq"){
            $fileID = $_POST[$ID];
            $filePath = "$upload_dir/$fileName.$fileformat";
            $file = fopen($filePath, "w") or die("Unable to open file!");
            fwrite($file, $fileID);
            fclose($file);
        } else {
            $filePath = "$upload_dir/$fileName.$fileformat";
            move_uploaded_file($_FILES[$ID]['tmp_name'], $filePath);
        }
    }

    // Uploading the mutation list file, FASTA file and PDB File
    function mutationLst_pdb_fasta_fileUpload($mutationID, $mutationFileName, $fastaFileName, $uploadPDBID, $pdbFileName, $upload_dir) {

        // Write mutation file 
        if ($mutationID != ""){
            file_upload_function($mutationID, $mutationFileName, $upload_dir, "txt");
        }

        // Write FASTA File or Upload FASTA File
        if (!empty($_POST["writeFastaSeq"])){
            file_upload_function("writeFastaSeq", $fastaFileName, $upload_dir, "fasta");
        } else if ($_FILES['fastaFile']['error'] === UPLOAD_ERR_OK and $_FILES['fastaFile']['size'] > 0) {
            file_upload_function('fastaFile', $fastaFileName, $upload_dir, "fasta");
        }

        // Upload PDB File
        if ($_POST["pdb-toggle"] == "yes") {
            if (empty($_POST["pdbID"])) {
                file_upload_function($uploadPDBID, $pdbFileName, $upload_dir, "pdb");
            }
        }
    }

    // Function to split FASTA sequence and header
    function createMutationFile($upload_dir, $mutType, $fastaContent, $resiNumber, $errorNumber) {
        $aminoAcid = array("A", "C", "D", "E", "F", "G", "H", "I", "K", "L", "M", "N", "P", "Q", "R", "S", "T", "V", "W", "Y");
        $lines = preg_split('/\r\n|\r|\n/', $fastaContent, 2);
        $fasSequence = array_intersect(str_split($lines[1]), $aminoAcid);
        if ($errorNumber == 1){
            $mutation = "{$fasSequence[$resiNumber+1]}$resiNumber$mutType";
        } else {
            $mutation = "{$fasSequence[$resiNumber]}$resiNumber$mutType";
        }
        
        file_put_contents("{$upload_dir}mutationList.txt", $mutation) or die("Unable to write to file!");
    }

    function upload_and_preformAnalysis($chain_name, $mutation_upload_write_nothing_ID, $upload_dir, $uniprotID, $randNum, $fasta_OR_not, $pdb_OR_not, $pdbID){
        // Create empty directory
        makedDirectory($randNum, $upload_dir);

        if ($fasta_OR_not == 0){
            download_fastaFile($uniprotID, $randNum);
        }

        // Single Mutation
        if ($mutation_upload_write_nothing_ID == ""){
            if (!empty($_POST["writeFastaSeq"])){
                createMutationFile($upload_dir, $_POST["mutation"], $_POST["writeFastaSeq"], $_POST["residue-number"], 1);
            } else if ($_FILES['fastaFile']['error'] === UPLOAD_ERR_OK and $_FILES['fastaFile']['size'] > 0) {
                createMutationFile($upload_dir, $_POST["mutation"], file_get_contents($_FILES['fastaFile']['tmp_name']), $_POST["residue-number"], 0);
            } else {
                createMutationFile($upload_dir, $_POST["mutation"], file_get_contents("fileUpload/$randNum/Seq.fasta"), $_POST["residue-number"], 0);
            }
        }

        // Uploading Mutliple Mutation, PDB File, Fasta File
        mutationLst_pdb_fasta_fileUpload($mutation_upload_write_nothing_ID, "mutationList", "Seq", "pdbFile", "pdbfile", $upload_dir);
        // $command = "python3 mutXplorScripts/automate.py $uniprotID $randNum".' > /dev/null 2>&1 & echo $!; ';
        
        if ($fasta_OR_not == 1 and $pdb_OR_not == 1) {
            if ($pdbID != "") {
                $command = "python3 mutXplorScripts/automate.py $uniprotID $randNum 1 1 $chain_name $pdbID".' > /dev/null 2>&1 & echo $!; ';
            } else {
                $command = "python3 mutXplorScripts/automate.py $uniprotID $randNum 1 1 $chain_name ''".' > /dev/null 2>&1 & echo $!; ';
            }
        } else if ($fasta_OR_not == 0 and $pdb_OR_not == 1) {
            if ($pdbID != "") {
                $command = "python3 mutXplorScripts/automate.py $uniprotID $randNum 0 1 $chain_name $pdbID".' > /dev/null 2>&1 & echo $!; ';
            } else {
                $command = "python3 mutXplorScripts/automate.py $uniprotID $randNum 0 1 $chain_name ''".' > /dev/null 2>&1 & echo $!; ';
            }
        } else if ($fasta_OR_not == 1 and $pdb_OR_not == 0) {
            $command = "python3 mutXplorScripts/automate.py $uniprotID $randNum 1 0 $chain_name ''".' > /dev/null 2>&1 & echo $!; ';
        } else {
            $command = "python3 mutXplorScripts/automate.py $uniprotID $randNum 0 0 $chain_name ''".' > /dev/null 2>&1 & echo $!; ';
        }

        exec($command, $output);
        return $output;
    }

    function check_mutation_type($chain_name, $mutationType, $mutation_list_type, $upload_dir, $uniprotID, $randNum, $fasta_OR_not, $pdb_OR_not, $pdbID){
        if ($mutationType == "multiple") {
            $analysis_output = upload_and_preformAnalysis($chain_name, $mutation_list_type, $upload_dir, $uniprotID, $randNum, $fasta_OR_not, $pdb_OR_not, $pdbID);
        } else {
            $analysis_output = upload_and_preformAnalysis($chain_name, "", $upload_dir, $uniprotID, $randNum, $fasta_OR_not, $pdb_OR_not, $pdbID);
        }

        return $analysis_output;
    }

    // Chain ID, FASTA Sequence and PDB File or IDs
    // 1 => FASTA and PDB file uploaded or Inputted by the user
    // 0 => No FASTA or PDB has been provided by the user
    function chain_fasta_pdb($chain_name, $mutation_list_type, $randNum, $upload_dir, $mutationType){
        if (!empty($chain_name)){
            global $uniprotID;
            $uniprotID = $_POST["uniprot-ID"];
            if ($uniprotID != ""){

                // The user has provided the FASTA and PDB files by uploading or inputting them
                if ($_POST["fasta-toggle"] == "yes" and $_POST["pdb-toggle"] == "yes") {

                    if (validateFasta("fastaFile") == 1 and pathinfo($_FILES["pdbFile"]["name"], PATHINFO_EXTENSION) == "pdb" and substr(file_get_contents($_FILES['pdbFile']['tmp_name']), 0, 6) == "HEADER") {
                        // this if condition is uploaded fasta sequence and uploaded pdb file
                        return check_mutation_type($chain_name, $mutationType, $mutation_list_type, $upload_dir, $uniprotID, $randNum, 1, 1, "");
        
                    } elseif (validateFasta("fastaFile") == 1 and !empty($_POST["pdbID"])) {

                        // this elseif condition is uploaded file sequence and inputted pdb accession ID
                        return check_mutation_type($chain_name, $mutationType, $mutation_list_type, $upload_dir, $uniprotID, $randNum, 1, 1, $_POST["pdbID"]);

        
                    } elseif (validateFasta("writeFastaSeq") == 2 and pathinfo($_FILES["pdbFile"]["name"], PATHINFO_EXTENSION) == "pdb" and substr(file_get_contents($_FILES['pdbFile']['tmp_name']), 0, 6) == "HEADER"){
                       
                        // this elseif condition is inputted fasta sequence and uploaded pdb file
                        return check_mutation_type($chain_name, $mutationType,$mutation_list_type, $upload_dir, $uniprotID, $randNum, 1, 1, "");
        
                    } elseif (validateFasta("writeFastaSeq") == 2 and !empty($_POST["pdbID"])) {

                        // this else condition is inputted fasta sequence and inputted pdb accession ID
                        return check_mutation_type($chain_name, $mutationType, $mutation_list_type, $upload_dir, $uniprotID, $randNum, 1, 1, $_POST["pdbID"]);
                    } else {

                        global $warningVar;
                        $warningVar = "Provided FASTA file or PDB File is inappropriate";
                    }

                } else if ($_POST["fasta-toggle"] == "no" and $_POST["pdb-toggle"] == "yes") {
                    
                    if (pathinfo($_FILES["pdbFile"]["name"], PATHINFO_EXTENSION) == "pdb" and substr(file_get_contents($_FILES['pdbFile']['tmp_name']), 0, 6) == "HEADER") {

                        // Uploaded PDB file
                        return check_mutation_type($chain_name, $mutationType, $mutation_list_type, $upload_dir, $uniprotID, $randNum, 0, 1, "");
                    } else {

                        // Inputted PDB ID
                        return check_mutation_type($chain_name, $mutationType, $mutation_list_type, $upload_dir, $uniprotID, $randNum, 0, 1, $_POST["pdbID"]);
                    }

                } else if ($_POST["fasta-toggle"] == "yes" and $_POST["pdb-toggle"] == "no") {

                    if (validateFasta("fastaFile") == 1) {
                        
                        // Uploaded FASTA file
                        return check_mutation_type($chain_name, $mutationType, $mutation_list_type, $upload_dir, $uniprotID, $randNum, 1, 0, "");
                    } else if (validateFasta("writeFastaSeq") == 2) {

                        // Inputted FASTA file
                        return check_mutation_type($chain_name, $mutationType, $mutation_list_type, $upload_dir, $uniprotID, $randNum, 1, 0, "");
                    } else {

                        global $warningVar;
                        $warningVar = "Provided FASTA file is inappropriate";
                    }

                } else {
                    
                    // Directly run automation script without any validation except mutation file
                    return check_mutation_type($chain_name, $mutationType, $mutation_list_type, $upload_dir, $uniprotID, $randNum, 0, 0, "");
                }
                
            } else {
                global $warningVar; 
                $warningVar = "Please provide the uniprot ID";
            }
        } else {
            global $warningVar; 
            $warningVar = "Please provide appropriate chain number";
        }
    }

    $randNum = rand(1, 1000);
    $upload_dir = "./fileUpload/".$randNum."/";

    // Validate the entire mutations list. Even if its a single mutation
    function validate_mutation_list($lst){
        $amino_acid = ["A", "C", "D", "E", "F", "G", "H", "I", "K", "L", "M", "N", "P", "Q", "R", "S", "T", "V", "W", "Y"];
        $parts = preg_split('/\r\n|\n|\r/', $lst);
        if (empty(end($parts))){
            array_pop($parts);
        }
        foreach($parts as $key => $val){
            if (!empty($val) || !preg_match('/^[A-Z0-9]+$/', $string)) {
                preg_match('/([A-Za-z]+)([0-9]+)([A-Za-z]+)/', $val, $matches);
                if (in_array($matches[1], $amino_acid) and in_array($matches[3], $amino_acid)) {
                    return true;
                } else {
                    return false;
                    break;
                }
            } else {
                return false;
                break;
            }
        }
    }

    if (!empty($_POST["process_id"])){
        if (isset($_POST["mutAnalysis"])){
            if ($_POST["mutationType"] == "single"){
                // Single Mutation
                if (!empty($_POST["mutation"])){
                    // Residue number
                    if (!empty($_POST["residue-number"])) {
                        // Chain ID
                        if (!empty($_POST["chain-name"])) {
                            // echo "Provided chain ID is correct";

                            $fun_output = chain_fasta_pdb($_POST["chain-name"], "", $randNum, $upload_dir, "single");
                            if (count($fun_output) > 1){
                                $warningVar = $fun_output[1];
                            } else {
                                global $pid; 
                                $pid = (int)$fun_output[0];
                                // header("refresh: 10;");
                            }

                        } else {
                            global $warningVar; 
                            $warningVar = "Please provide appropriate chain number";
                        }
                    } else {
                        global $warningVar; 
                        $warningVar = "Please provide appropriate residue number";
                    }
                } else {
                    global $warningVar; 
                    $warningVar = "Please provide appropriate mutation";
                }
            } else {
                // Multiple mutation
                // abort_running_process();

                function handleMutationList($mutationList, $upload_write_ID, $randNum, $upload_dir) {
                    $validate_output = validate_mutation_list($mutationList);
                    if ($validate_output == 1) {
                        $fun_output = chain_fasta_pdb($_POST["chain-name"], $upload_write_ID, $randNum, $upload_dir, "multiple");
                        if (count($fun_output) > 1){
                            $warningVar = $fun_output[1];
                        } else {
                            global $pid; 
                            $pid = (int)$fun_output[0];
                            // $ranPFupdir = $randNum.",".$PFName;
                            // header("refresh: 10;");
                        }
                    } else {
                        global $warningVar;
                        $warningVar = "Please provide an appropriate mutation list";
                    }
                }
                
                if (!empty($_FILES['upload-mutation-list']['name'])) {
                    handleMutationList(file_get_contents($_FILES['upload-mutation-list']['tmp_name']), "upload-mutation-list", $randNum, $upload_dir);
                } elseif (!empty($_POST["write-mutation-list"])) {
                    handleMutationList($_POST["write-mutation-list"], "write-mutation-list", $randNum, $upload_dir);
                } else {
                    global $warningVar;
                    $warningVar = "Please provide a mutation list";
                }
            }
        } else {
            global $warningVar;
            $warningVar = "Please provide the all inputs";
        }
    } else {
        $pid = (int)$_POST["jobID"];
        if (posix_kill($pid, 0)) {
            // echo "The process is running wait until process is finished";
        } else {
            // echo "This is working";
            $randNum = $_POST["randNum"];
            $outputFiles = scandir("fileUpload/$randNum/outFiles");
            array_splice($outputFiles, 0, 2);
            foreach($outputFiles as $fVal){
                $file = "fileUpload/$randNum/outFiles/$fVal";
                $toolName = explode(".", $fVal)[0];
                $fh = fopen($file, "r");
                while(($line = fgetcsv($fh)) !== false){
                    if (!empty($line)){
                        $allToolResult[$toolName][] = $line;
                    }
                }
            }
        }
    }

    include "header.php";
?>

<link type="text/css" rel="stylesheet" href="css/dataTables.min.css">
<link type="text/css" rel="stylesheet" href="css/jquery.dataTables.min.css">
<link type="text/css" rel="stylesheet" href="css/buttons.dataTables.min.css">

<script src="js/dataTables.min.js"></script>
<script src="js/dataTables.buttons.min.js"></script>
<script src="js/buttons.html5.min.js"></script>

<style>
    .toolsTble{
        border-collapse: separate;
    }

    .spToolTD{
        padding: 2px 12px 12px 12px;
        border-collapse: collapse;
    }

    .toolNm{
        font-size: 13px; 
        margin: 0px; 
        text-decoration: underline;
    }

    .table-primary>th, .table-primary>td {
        border-top: 1px solid #00CCCC;
    }

    table.dataTable.no-footer{
        border-bottom: 1px solid #00CCCC;
    }
</style>

<p></p>
<div class="mb-3">
    <h2 align="center" style="font-size: 18px;"><strong>MutXplor</strong></h2>
</div>

<div class="alert alert-warning" role="alert" style="display: none;">
    <label class="m-0"><?= $warningVar; ?></label>
</div>

<div class="midPage m-3">
    <h6 align="center" class="mb-3" style="font-size: 14px"><strong>Job is Running</strong></h6>
    <table class="timerD" style="font-size: 12px">
        <tr style="border: 1px solid;">
            <td style="width: 40%;">Status</td>
            <td style="width: 48%;">
                <div style="padding-left: 15px;">Running</div>
            </td>
        </tr>
        <tr style="border: 1px solid;">
            <td style="width: 40%;">Submitted at</td>
            <td style="width: 48%;">
                <div class="currDTDays" id="submitQueryTime" style="padding-left: 15px;"></div>
            </td>
        </tr>
        <tr style="border: 1px solid;">
            <td style="width: 40%;">Time since submission</td>
            <td style="width: 48%;">
                <div id="timer" style="padding-left: 15px;"></div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center; border: 1px solid;">
                <div class="lds-ellipsis">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="border: 1px solid;">This page will be automatically updated in <strong
                    id="reloder">5</strong> seconds</td>
        </tr>
        <tr>
            <td colspan="2" style="border: 1px solid;">
                <strong>*Note: Please keep this window open until the job is finished, which will take approximately 10 to 20
                    minutes.</strong>
            </td>
        </tr>
    </table>
</div>

<div class="main-result-table" style="display: none;">
    <?php
        if (!empty($allToolResult)){
            ?>
                <table class="toolsTble" width="95%" align="center">
                    <?php
                        $c = 0;
                        $t = 1;
                        foreach($allToolResult as $key => $sTool){
                            $header = array_shift($sTool);
                            if (!empty($sTool)){
                                if($t % 3 == 1){
                                    echo "<tr>";
                                    
                                }
                                ?>
                                    <td class="spToolTD" align="center">
                                        <label class="toolNm" align="center"><p><strong><?= capiFrstChar($key); ?></strong></p></label>
                                        <table border="1" cellspacing="0" cellpadding="4" bordercolor="#00cccc" class="spToolTble table table-striped">
                                            <thead>
                                                <tr class="table-primary">
                                                    <?php
                                                        foreach($header as $key){
                                                            ?>
                                                                <th scope="col"><strong><?= $key; ?></strong></th>
                                                            <?php
                                                        }
                                                    ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    foreach($sTool as $rowKey){
                                                        ?>
                                                            <tr>
                                                                <td><?= $rowKey[0]; ?></td>
                                                                <td><?= ucfirst(strtolower($rowKey[1])); ?></td>
                                                                <td><?= $rowKey[2]; ?></td>
                                                            </tr>
                                                        <?php
                                                    }
                                                ?>
                                            </tbody>
                                        </table>
                                    </td>
                                <?php
                                if($t % 3 == 0){
                                    echo "</tr>";
                                }
                                $t++;
                            }
                            $c++;

                        }
                    ?>
                </table>
            <?php
        } else {
            echo "There is no file to show";
        }
    ?>
</div>

<!-- This form is self submit to store the process ID and randomNumber in the session storage  -->
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="passID" name="passID" style="display: none;">
    <input type='hidden' name='jobID' id='processID' value="" />
    <input type='hidden' name='randNum' id='randomNumber' value="" />
    <input type="submit" value="Submit">
</form>

<!-- Reset the page while processing the query pass by the user and back to homepage -->
<!-- This will work only onclicking the submit button of this form to self page -->
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="mt-3" align="center" style="text-align: center;" id="reset-button-normal">
    <input type="hidden" name="reset_randNum" id="resetRandomNumber" value="" />
    <input class="btn btn_smt resetVal" type="submit" name="reset" value="Reset" id="resetValues" />
</form>

<div class="mt-3" align="center" style="text-align: center; display: none" id="error-reset-button">
    <input class="btn btn_smt resetVal" type="submit" name="reset" value="Back to home" id="ResetToHome"/>
</div>

<p></p>     <!-- Create some bottom margin in the end -->

<script>
    $(document).ready(function() {
        $("#ResetToHome").click(function (e){
            sessionStorage.clear();
            window.location.href = "mutXplor.php";
        });

        // Check the pID and random number in the first reload after 10sec this won't work
        <?php if (!empty($pid) && !empty($randNum)): ?>
        var pID = <?= json_encode($pid); ?>;
        var ranNum = <?= json_encode($randNum); ?>;
        <?php if (!empty($_POST["uniprot-ID"])): ?> var uniprotID = <?= json_encode($_POST["uniprot-ID"]); ?>; <?php endif; ?>
        <?php endif; ?>
        // end of if condition

        // Reset entire page
        $("#resetValues").click(function (e){
            sessionStorage.clear();
        });

        var rstArray = Object.keys(<?= json_encode($allToolResult); ?>).length;
        var warningVar = <?= json_encode($warningVar); ?>;
        
        if (rstArray > 0){
            $(".midPage").hide();
            $(".main-result-table").show();
            $("#reset-button-normal").hide();
            $("#error-reset-button").show();

            $('.spToolTble').DataTable({
                dom: 'Bfrtp',
                buttons: [{
                    extend: 'csv',
                    title: 'Download',
                    text: 'Download'
                }]
            });

            sessionStorage.clear();

        } else {
            // if (sessionStorage.getItem('timerValue') != null || sessionStorage.getItem('dateTime') != null) {
            //     sessionStorage.clear();
            // }

            if (warningVar == "") {
                // processID is pID and randomNumber is the location of the folder name in the fileUpload folder where files are stored
                if (sessionStorage.getItem("processID") || sessionStorage.getItem("randomNumber")){
                    document.getElementById("processID").value = sessionStorage.getItem("processID");
                    document.getElementById("randomNumber").value = sessionStorage.getItem("randomNumber");
                    document.getElementById("resetRandomNumber").value = sessionStorage.getItem("resetRandomNumber");
                } else {
                    document.getElementById("processID").value = pID;
                    sessionStorage.setItem('processID', pID);

                    document.getElementById("randomNumber").value = ranNum;
                    sessionStorage.setItem('randomNumber', ranNum);

                    document.getElementById("resetRandomNumber").value = `${uniprotID}, ${ranNum}`;
                    sessionStorage.setItem('resetRandomNumber', `${uniprotID}, ${ranNum}`);
                }

                function submitForm() {
                    document.getElementById("passID").submit();
                }
                setTimeout(submitForm, 10000); // 10000 milliseconds = 10 seconds

                $(".midPage").show();

                // Add Timer 
                function contiTimer() {
                    if (sessionStorage.getItem("timerValue") || sessionStorage.getItem("dateTime")) {
                        var timerValue = parseInt(sessionStorage.getItem("timerValue"));
                        document.getElementById("submitQueryTime").innerHTML = sessionStorage.getItem('dateTime').slice(0, 24);
                    } else {
                        var timerValue = 0;
                        sessionStorage.setItem('dateTime', new Date().toString());
                        document.getElementById("submitQueryTime").innerHTML = new Date().toString().slice(0, 24)
                    }

                    function updateTimer() {
                        var timerElement = document.getElementById("timer");
                        timerElement.textContent = formatTime(timerValue);
                        timerValue++;
                    }

                    function formatTime(time) {
                        var hours = Math.floor(time / 3600);
                        var minutes = Math.floor((time % 3600) / 60);
                        var seconds = time % 60;

                        return (
                            padZero(hours) + ":" +
                            padZero(minutes) + ":" +
                            padZero(seconds)
                        );
                    }

                    function padZero(number) {
                        return (number < 10 ? "0" : "") + number;
                    }
                    setInterval(updateTimer, 1000);

                    function storeTimerValue() {
                        sessionStorage.setItem("timerValue", timerValue);
                    }
                    window.addEventListener("beforeunload", storeTimerValue);
                }
                contiTimer()

                // Counter timer
                var countdown = 10; // Timer value in seconds
                var timerElem = document.getElementById("reloder");

                function updateTi() {
                    timerElem.textContent = countdown;
                    countdown--;
                    setTimeout(updateTi, 1000);
                }
                updateTi();

            } else {
                $(".alert").show();
                sessionStorage.clear();
            }
        }

    });

    
</script>

<?php
    include_once "footer.php";
?>