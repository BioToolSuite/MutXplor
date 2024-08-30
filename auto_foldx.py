 #!/usr/bin/python3

import sys
import os
import re
import shutil
import pandas as pd

def runFoldx(pdbFilename,mutationFilename,fchain,rndNum):
    os.chdir("/var/www/html/fileUpload/" + rndNum)
    shutil.copy2("../../mutXplorScripts/rotabase.txt", "rotabase.txt")
    os.chmod("rotabase.txt", 0o777)
    exFolderFile = ['Foldxout', 'individual_list.txt', 'Unrecognized_molecules.txt']
    b = [os.system(f"rm -rf {i}") for i in exFolderFile if os.path.exists(i) == True]
    pdb=open(pdbFilename,"r")
    pdbFile=pdb.readlines()
    for lines in pdbFile:
        if re.match("^ATOM",lines):
            firstRes=int(re.split('\s+',lines)[5])
            break
    with open(mutationFilename,"r") as mutFile:
        muts = mutFile.readlines()
    muts = [x.strip() for x in muts]
    newRes = [x[-1] for x in muts]
    oldRes = [x[0] for x in muts]
    pos = []
    for x in muts:
        position=int(''.join(re.findall('\d',x)))
        if position < firstRes:
            pos.append(0)
        else:
            pos.append(int(''.join(re.findall('\d',x))))
    os.system("mkdir Foldxout")
    fwrite = open('outFiles/foldX.out', "w")
    fwrite.write('Mutation,Stability,Score\n')
    for i in range(len(pos)):
        if pos[i] == 0:
            pass
        else:
            #Run FoldX in command-line
            mutFile = open("individual_list.txt","w")
            mutFile.write(oldRes[i]+fchain+str(pos[i])+newRes[i]+";\n")
            mutFile.close()
            res = os.system("/var/www/html/mutXplorScripts/foldx --command=BuildModel --pdb="+pdbFilename+" --mutant-file=individual_list.txt --out-pdb=false --screen=false --output-dir=./Foldxout")
            if res == 0:
                file1 = open("Foldxout/Dif_"+pdbFilename.split(".")[0]+".fxout","r")
                lines = file1.readlines()[8:]
                file1.close()
                out = [line.split("\t") for line in lines]
                df = pd.DataFrame(out)
                df.columns = df.iloc[0]
                df = df.drop([0]).reset_index(drop=True)
                if float(df['total energy'][0]) < 0:
                    fwrite.write(f"{oldRes[i]+str(pos[i])+newRes[i]},Stabilizing,{df['total energy'][0]}\n")
                else:
                    fwrite.write(f"{oldRes[i]+str(pos[i])+newRes[i]},Destabilizing,{df['total energy'][0]}\n")
                rmF = [os.remove(f"Foldxout/{i}") for i in os.listdir('Foldxout')]
    fwrite.close()
    os.system("rm -rf Foldxout molecules outFile individual_list.txt Unrecognized_molecules.txt rotabase.txt")
# runFoldx('pdbfile.pdb','mutationList.txt','A','965')