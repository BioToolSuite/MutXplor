from Bio import PDB
import os
import requests
import sys
proxies = {'https':'http://proxy.ibab.ac.in:3128', 'http':'http://proxy.ibab.ac.in:3128'}

uniprotID = sys.argv[1]
rndNum = sys.argv[2]
    
def uniprot(uniprot_id, rndNum):
    os.chdir("/var/www/html/fileUpload/" + rndNum)
    url = f"https://rest.uniprot.org/uniprotkb/{uniprot_id}.fasta"
    response = requests.get(url, proxies=proxies)
    with open("Seq.fasta", 'w') as file:
        file.write(response.text)
uniprot(uniprotID, rndNum)
