<?php
    include "connect.php";

    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);

    function random_strings($length_of_string){
        $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        return substr(str_shuffle($str_result), 0, $length_of_string);
    }

    include_once "header.php";
?>

<script src="js/select2_min.js"></script>
<link type="text/css" rel="stylesheet" href="css/select2_min.css">
<link type="text/css" rel="stylesheet" href="css/fontawesome_5.8.1.css">
<link type="text/css" rel="stylesheet" href="mutXplorStyle.css">

<style>
</style>

<!-- Body -->
<p></p>
<div class="annoHead hideDiv">
    <h2 align="center" style="font-size: 15px;"><strong>MutXplor</strong></h2>
</div> 

<div class="d-flex my-4">
    <!-- <form action="" enctype="multipart/form-data" method="post" class="ml-2 autoForm hideDiv" onsubmit="return validate_entire_form()" autocomplete="off" novalidate> -->
        <div class="main-content">

            <!-- Job name -->
            <div class="col-12 mb-3">
                <label class="pr-4 job-name-label"><strong>Enter Job name:</strong></label>
                <input type="text" class="pr-1 pb-1 resetThis" name="job-name" id="jobID" placeholder="Job Name" oninput="validateInput(event)">
            </div>

            <!-- Mutation type selection -->
            <div class="mb-3 col-3 mutation-selection LabelInputdiff">
                <label class="mt-1"><b>Mutation details:</b></label>
                <div class="main-selection">
                    <select id="select-analyse-type" name="mutationType" class="select-analyse-type-class" required=true>
                        <option value="single">Sequence</option>
                        <option value="multiple">Structure</option>
                        <option value="multiple">Sequence and Structure</option>
                    </select>
                </div>
            </div>

            <!-- Fasta File -->
            <div class="col-6 mb-3">
                <label class><Strong>Fasta File: </Strong></label>
                <span class="fasta-radio ml-5"> 
                    <span>
                        <input class="input-radio" type="radio" name="fasta-toggle-yes" value="yes" id="fasta-radio-yes" checked onclick="fastaRadio(this)">
                        <label class="input-radio-text"> Yes </label>
                    </span>
                    <span class="ml-4">
                        <input class="input-radio" type="radio" name="fasta-toggle-no" value="no" id="fasta-radio-no" onclick="fastaRadio(this)">
                        <label class="input-radio-text"> No </label>
                    </span>
                    <input type="hidden" name="fasta-toggle" id="fastaToggle" val="">
                </span>

                <div class="LabelInputdiff" id="Upload-Write-fasta">
                    <div class="uploadDiv">
                        <div class="upload-pdb-file LabelInputdiff">
                            <label for="file-input-fasta" class="file-uplaod-Icon"><i
                                    class="fa fa-upload upload-Icon">Upload</i></label>
                            <input class="file-upload" id="file-input-fasta" name="fastaFile" type="file" accept=".fasta"
                                onchange="displayFileName(this.id)" />
                            <div class="ml-2 file-name-class" id="file-name-fasta">P34059.fasta</div>
                        </div>
                        <label class="example">Submit a molecule in <a
                                href="https://www.ncbi.nlm.nih.gov/genbank/fastaformat/">FASTA format</a></label>
                    </div>

                    <div class="diff-write-upload-fasta">
                        <label> OR </label>
                    </div>

                    <div class="input-fasta-file-div">
                        <textarea class="input-fasta-file resetThis" name="writeFastaSeq" id="wrtFasta" rows="5"></textarea>
                    </div>
                </div>
            </div>

            <!-- PDB ID  Details-->
            <div class="col-6 mb-2">
                <label><b>Wild-type structure:</b></label>
                <span class="fasta-radio ml-5"> 
                    <span>
                        <input class="input-radio" type="radio" name="pdb-toggle-yes" value="yes" id="pdb-radio-yes"  checked onclick="pdbRadio(this)">
                        <label class="input-radio-text"> Yes </label>
                    </span>
                    <span class="ml-4">
                        <input class="input-radio" type="radio" name="pdb-toggle-no" value="no" id="pdb-radio-no" onclick="pdbRadio(this)">
                        <label class="input-radio-text"> No </label>
                    </span>
                    <input type="hidden" name="pdb-toggle" id="pdbToggle" val="">
                </span>
                <div class="LabelInputdiff" id="Upload-Write-PDB">
                    <div class="uploadDiv">
                        <div class="upload-pdb-file LabelInputdiff">
                            <label for="file-input-pdb" class="file-uplaod-Icon"><i
                                    class="fa fa-upload upload-Icon">Upload</i></label>
                            <input class="file-upload" name="pdbFile" id="file-input-pdb" type="file" accept=".pdb"
                                onchange="displayFileName(this.id)" />
                            <div class="ml-2 file-name-class" id="file-name-pdb">1H59</div>
                        </div>
                        <label class="example">Submit a molecule in <a
                                href="https://www.wwpdb.org/documentation/file-format">PDB format</a></label>
                    </div>
                    <div class="diff-write-upload">
                        <label style="padding-top: .7rem;"> OR </label>
                    </div>

                    <div class="input-pdbID mt-2">
                        <label class="custom-field">
                            <input class="resetThis" type="text" name="pdbID" id="pdb-accession-number" required=""
                                oninvalid="this.setCustomValidity('Provide PBD Accession ID')" oninput="setCustomValidity('')" />
                            <span class="placeholder" id="pdb-placeholder">PDB Accession</span>
                        </label>
                        <label class="example">Example: 1GAG</label>
                    </div>
                </div>
            </div>

            <!-- Mutation type selection -->
            <div class="mb-3 col-3 mutation-selection LabelInputdiff">
                <label class="mt-1"><b>Mutation details:</b></label>
                <div class="main-selection">
                    <select id="select-mutation-ID" name="mutationType" class="select-mutation-class" required=true>
                        <option value="single">Single Mutation</option>
                        <option value="multiple">Mutation List</option>
                    </select>
                </div>
            </div>

            <!-- Mutation Details-->
            <div class="col-6 mb-3">

                <!-- Option 1: Single Mutation -->
                <div class="pl-4 LabelInputdiff" id="single_mutation">
                    <div class="pr-5 mr-3">
                        <label class="custom-field two">
                            <input class="resetThis" type="text" name="residue-number" id="residue_ID" required />
                            <span class="placeholder">Residue Number</span>
                        </label>
                        <label class="example">Example: 65</label>
                    </div>

                    <div>
                        <label class="custom-field">
                            <input class="resetThis" type="text" name="mutation" id="mutationID" required />
                            <span class="placeholder">Mutation</span>
                        </label>
                        <label class="example">Example: W</label>
                    </div>
                </div>

                <!-- Option 2: Multiple Mutation -->
                <div class="LabelInputdiff" id="mutliple_mutation">
                    <div class="uploadDiv">
                        <div class="upload-pdb-file LabelInputdiff">
                            <label for="file-input-mutatuion-list-upload" class="file-uplaod-Icon"><i
                                    class="fa fa-upload upload-Icon">Upload</i></label>
                            <input class="file-upload" id="file-input-mutatuion-list-upload" type="file" accept=".txt"
                                name="upload-mutation-list" onchange="displayFileName(this.id)" />
                            <div class="ml-2 file-name-class" id="file-name-ID">Sample.txt</div>
                        </div>
                        <label class="example">Submit a file in <a
                                href="mutXplorScripts/Sample_files/Sample.txt" download>Text format</a></label>
                    </div>

                    <div class="diff-write-upload-fasta">
                        <label> OR </label>
                    </div>

                    <div class="input-fasta-file-div">
                        <textarea class="input-fasta-file resetThis" name="write-mutation-list"
                            id="write-mutation_listID" rows="5"></textarea>
                    </div>
                </div>

                <!-- Chain ID -->
                <div class="pl-4">
                    <label class="custom-field">
                        <input class="resetThis" type="text" name="chain-name" id="chainID" required=""
                            oninvalid="this.setCustomValidity('Provide the chain ID')" oninput="setCustomValidity('')" />
                        <span class="placeholder">Chain ID</span>
                    </label>
                    <label class="example">Example: A</label>
                </div>

                <!-- uniprot ID -->
                <div class="pl-4">
                    <label class="custom-field">
                        <input class="resetThis" type="text" name="uniprot-ID" id="uniprotID" required=""
                            oninvalid="this.setCustomValidity('Provide the Uniprot ID')" oninput="setCustomValidity('')" />
                        <span class="placeholder" id="uniprot-placeholder">Uniprot ID</span>
                    </label>
                    <label class="example">Example: P34059</label>
                </div>
            </div>

            <!-- Note -->
            <div class="note" style="padding-left: 0.8rem; display: none;" id="mutationNote">
                <label><b>*Note: Mutations are evaluated one at a time</b></label>
            </div>
        </div>

        <input type="hidden" name="process_id" value=<?= random_strings(15); ?>>
        <div class="exanRebtn">
            <input class="btn btn_smt rnEG" onclick="location.href='example.php'" type="button" value="Run Example">
            <button type="submit" class="btn btn_smt analbtn" name="mutAnalysis">Analyze</button>
            <input class="btn btn_smt resetVal" type="reset" value="Reset" onclick="emptyValue()">
        </div>
    <!-- </form> -->

    <div class="desc hideDiv">
        <label>
            <strong>MutXplor</strong> is an automated web server designed to interface with multiple existing
            computational tools that predict phenotype outcomes based on protein sequence and structure. Sequence-based
            tools leverage annotated databases and closely related sequences to infer phenotypic consequences, analyzing
            features such as amino acid conversation, physiochemical properties, and functional annotation. Conversely,
            structure-based tools focus on factors that impact the mutation on residue contacts, interatomic
            interaction, and structural properties.
        </label>
        <label>
            If you find this tool to be useful and if the result lead to publication, please cite the following:
            <strong>Aman Vishwakarma, Swetha S and S. Thiyagarajan.</strong>
        </label>
        <label>
            MutXplor: It is an automated standalone web server that interfaces with existing phenotypic prediction tools
            based on protein structure and sequence.
        </label>
        <label>This work is funded by <strong>Indian Council of Medical Research (ICMR)</strong>.</label>
    </div>
</div>

<div class="mb-3 note" style="padding-left: 20px">
    <label>
        <strong>*Note: For better experience, we recommend using Google Chrome</strong>
    </label>
</div>

<script>
    if (sessionStorage.getItem('timerValue') != null || sessionStorage.getItem('dateTime') != null) {
        sessionStorage.clear();
    }
</script>


<?php
    include_once "footer.php";
?>