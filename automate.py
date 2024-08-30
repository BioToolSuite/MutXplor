#!/usr/bin/python3

from multiprocessing import Process
import sys
import getpass
import re
import mechanicalsoup
import glob
import os
import time
import warnings
import requests
import pandas as pd
from Bio import PDB
from Bio.PDB import PDBParser

# Sequence based tools
import auto_iMutant2
import auto_muPro
import auto_polyphen2
import auto_snpsGO
import auto_metaSNP

# Structure based tools
import auto_duet
import auto_dynamut2
import auto_maestro
import auto_foldx

warnings.filterwarnings("ignore")
proxies = {'https':'http://proxy.ibab.ac.in:3128', 'http':'http://proxy.ibab.ac.in:3128'}

def runAll1(fastaF, mutationF, pdbF, firstChain, randNum):
    auto_iMutant2.runIMutant(fastaF, mutationF, randNum)
    auto_muPro.runMuPro(fastaF, mutationF, randNum)
    auto_foldx.runFoldx(pdbF, mutationF, firstChain, randNum)
    auto_dynamut2.runDynamut(pdbF, mutationF, firstChain, randNum)
    
def runAll2(fastaF, mutationF, uniprotID, randNum):   # Sequence based 1
    auto_metaSNP.runMetaSNP(fastaF, mutationF, randNum)
    # auto_polyphen2.runPolyphen2(uniprotID, mutationF, randNum)

def runAll3(fastaF, mutationF, pdbF, firstChain, randNum):
    auto_duet.runDuet(pdbF, mutationF, firstChain, randNum)
    auto_snpsGO.runSnpsGO(fastaF, mutationF, randNum)
    auto_maestro.runMaestro(pdbF, mutationF, firstChain, randNum)
    
def runAll4(fastaF, mutationF, randNum):   # Sequence based 2
    auto_iMutant2.runIMutant(fastaF, mutationF, randNum)
    auto_snpsGO.runSnpsGO(fastaF, mutationF, randNum)

# fastaFile = "P34059.fasta"
# mutationFile = "mutations_test.txt"
# pdbFile = "4fdi.pdb"
# uniprot = "P34059"

uniprot, rndNum, noFasta, noPDB, chainID, pdbID = sys.argv[1], sys.argv[2], sys.argv[3], sys.argv[4], sys.argv[5], sys.argv[6]
os.chdir(f"/var/www/html/fileUpload/{rndNum}")

# 0 => No FASTA File, 1=> FASTA file is provided. Same as goes for PDB (noPDB variable)
def download_fasta(uniprot_id):
    url = f"https://rest.uniprot.org/uniprotkb/{uniprot_id}.fasta"
    response = requests.get(url, proxies=proxies)
    with open("Seq.fasta", 'w') as file:
        file.write(response.text)
    
def download_pdb(pdbFileName):
    pdb_url = f'https://files.rcsb.org/download/{pdbFileName}.pdb'
    response = requests.get(pdb_url, proxies=proxies)
    with open('pdbfile.pdb', 'wb') as file:
        file.write(response.content)

def get_chain_ids(pdb_file):
    parser = PDBParser(PERMISSIVE=1)
    structure = parser.get_structure('PDB_structure', pdb_file)
    chain_ids = set()
    for model in structure:
        for chain in model:
            chain_ids.add(chain.id)
    return list(chain_ids)

if (noFasta == "0"):
    download_fasta(uniprot)
    
fastaFile = glob.glob("*.fasta")[0]
mutationFile = [f for f in glob.glob("*.txt") if f != 'rotabase.txt'][0]

# Create directories if they don't exist
os.makedirs("outFiles", exist_ok=True)
os.makedirs("logFiles", exist_ok=True)

# Process mutation file
with open(mutationFile, "r") as mutFile:
    muts = [x.strip().upper() for x in mutFile.readlines()]

newMuts = [x for x in muts if x[-1] not in "BJOUXZ" and x[0] not in "BJOUXZ"]
positions = [int(''.join(re.findall('\d', x))) for x in newMuts]

df = pd.DataFrame({"pos": positions, "Mut": newMuts}).sort_values(by=['pos'])
df.to_csv(mutationFile, index=False, header=False, columns=["Mut"])

if noPDB == "1":
    if (pdbID != ""): download_pdb(pdbID)
    pdbFile = glob.glob("*.pdb")[0]
    if chainID in get_chain_ids(pdbFile):
        # Start functions in 3 different processors
        processes = [
            Process(target=runAll1, args=(fastaFile, mutationFile, pdbFile, chainID, rndNum)),
            Process(target=runAll2, args=(fastaFile, mutationFile, uniprot, rndNum)),
            Process(target=runAll3, args=(fastaFile, mutationFile, pdbFile, chainID, rndNum))
        ]
    else:
        sys.exit(0)
else:
    # Start functions in 2 different processors
    processes = [
        Process(target=runAll2, args=(fastaFile, mutationFile, uniprot, rndNum)),
        Process(target=runAll4, args=(fastaFile, mutationFile, rndNum))
    ]

#Start the functions in 3 different processors
print('** Automation in progress.... **')
start_time = time.time()

for p in processes:
    p.start()
for p in processes:
    p.join()
    
end_time = time.time()
execution_time = end_time - start_time
print('** Automation process completed in {:.2f} minutes {:.2f} seconds **'.format(execution_time // 60, execution_time % 60))