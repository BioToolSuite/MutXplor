#!/usr/bin/python3

import sys
import os
import re
import pandas as pd

def runMaestro(pdbF,mutationF,fChain,rndNum):
    os.chdir("/var/www/html/fileUpload/" + rndNum)
    chain = fChain
    pdb=open(pdbF,"r")
    pdbFile=pdb.readlines()
    #Getting the first residue
    for lines in pdbFile:
        if re.match("^ATOM",lines):
            firstRes=int(re.split('\s+',lines)[5])
            break
    with open(mutationF,"r") as mutFile:
        muts = mutFile.readlines()
    muts = [x.strip() for x in muts]
    newRes = [x[-1] for x in muts]
    oldRes = [x[0] for x in muts]
    pos = []
    #Remove mutations not in PDB file
    for x in muts:
        position=int(''.join(re.findall('\d',x)))
        if position < firstRes:
            pos.append(0)
        else:
            pos.append(int(''.join(re.findall('\d',x))))
    mutFile = open("MutMaestro","w")
    mutants = []
    #Creating input file for MAESTRO
    for i in range(len(pos)):
        if pos[i] == 0:
            pass
        else:
            mutFile.write(oldRes[i]+str(pos[i])+"."+chain+"{"+newRes[i]+"}\n")
            mutants.append(muts[i])
    mutFile.close()
    mutFile=open("MutMaestro","r")
    muts=mutFile.readlines()
    muts = [x.strip() for x in muts]
    mutFile.close()
    try:
        # Run MAESTRO in command-line
        open("result","w").close()
        for i in range(len(muts)):
            os.system("/var/www/html/mutXplorScripts/MAESTRO_linux_x64/maestro /var/www/html/mutXplorScripts/MAESTRO_linux_x64/config.xml "+pdbF+","+chain+" --evalmut="+muts[i]+" >> result")
            # os.system("maestro /var/www/html/mutXplorScripts/MAESTRO_linux_x64/config.xml /var/www/html/mutXplorScripts/"+pdbF+","+chain+" --evalmut="+muts[i]+" >> result")
        df = pd.read_csv("result",sep='\t',index_col=False)
        df = df.rename(columns={"#structure":"structure"})
        i=df[df.structure == "#structure"].index
        df=df.drop(i)
        i=df[df.mutation=="wildtype"].index
        df=df.drop(i)
        Score=[]
        for i in range(len(df)):
            Score.append(float(df.iloc[i,-2]))
        df["Score"]=Score
        stability=[]
        #Create stability values based on Score
        for i in range(len(df)):
            if float(df.iloc[i,-2]) > 0:
                stability.append("Destabilizing")
            else:
                stability.append("Stabilizing")
        df["Stability"]=stability
        df["Mutation"] = mutants
        out = df.filter(["Mutation","Stability","Score"],axis=1)
        out.to_csv("outFiles/maestro.out",index=False)
        os.system("rm MutMaestro result")
    except:
        print(str(sys.exc_info()[0])+" occured and MAESTRO could not run")
# runMaestro('pdbfile.pdb','mutationList.txt','A','82')