#!/usr/bin/python3

import os
import re
import sys
import pandas as pd

def runIMutant(fasta,mutations,rndNum):
	os.chdir("/var/www/html/fileUpload/" + rndNum)
	try:
		#To edit the sequence file as required(without header and line wrap)
		with open(fasta,"r") as f1:
			seq = f1.readlines()[1:]
		sequence = ""
		file1 = open("outFiles/iMutant.out","w")
		file1.write("Position,Wild-Type,Mutant,Score,pH,Temp,Phenotype\n")
		file1.close()
		for line in seq:
			sequence = sequence + line.split('\n')[0]
		f1.close()
		with open("/var/www/html/mutXplorScripts/I-Mutant2.0.7/seqfile","w") as f2:
			f2.write(sequence)
		f2.close()
		#To edit the input file according to the requirements
		with open(mutations,"r") as mutFile:
			muts = mutFile.readlines()
		muts = [x.strip() for x in muts]
		newRes = [x[-1] for x in muts]
		pos = []
		for x in muts:
			pos.append(''.join(re.findall('\d',x)))
		os.chdir("/var/www/html/mutXplorScripts/I-Mutant2.0.7")
		cmd = """/bin/echo -e "SEQ\nV\nseqfile\n\n\n\n\nY" > inputfile"""
		os.system(cmd)
		for i in range(len(pos)):
			with open("inputfile","r") as inFile:
				content = inFile.readlines()
			content[3] = pos[i]+"\n"
			content[4] = newRes[i]+"\n"
			inFile.close()
			with open("inputfile","w") as file:
				file.writelines(content)
			file.close()
			#Run I-Mutant script
			os.system("python2 -O I-Mutant2.0.py < inputfile > result.txt")
			#Parse the result to get only the result column
			with open("result.txt","r") as resFile:
				res = (resFile.readlines()[25:26])[0].strip().split()
				if float(res[-3]) > 0:
					res.append("Stabilizing")
				elif float(res[-3]) < 0:
					res.append("Destabilizing")
			resFile.close()
			#Append the result column to a csv file
			with open("/var/www/html/fileUpload/" + rndNum + "/outFiles/iMutant.out","a") as finalOut:
				for out in res:
					finalOut.write(out+',')
				finalOut.write('\n')
			finalOut.close()
		#To get only the required columns
		os.chdir("/var/www/html/fileUpload/" + rndNum)
		df = pd.read_csv("outFiles/iMutant.out",index_col=False)
		df["Mutation"]=df["Wild-Type"]+df["Position"].astype(str)+df["Mutant"]
		df=df.filter(["Mutation","Phenotype","Score"],axis=1)
		df.to_csv("outFiles/iMutant.out",index=False)
	except:
		print(str(sys.exc_info()[0])+" occured and iMutant2.0 could not run")
