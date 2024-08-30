#!/usr/bin/python3

import os
import re
import sys

def runMuPro(fasta,mutation,rndNum):
	os.chdir("/var/www/html/fileUpload/" + rndNum)
	#To create inputfile as required by MuPRO
	sequence = ""
	with open(fasta,"r") as f1:
		header = f1.readline().split()[0]
		f1.seek(0)
		seq = f1.readlines()[1:]
		for line in seq:
			sequence = sequence + line.split('\n')[0]
	f1.close()
	outFile = open("outFiles/muPro.out","w")
	outFile.write("Mutation,Stability,Score\n")
	outFile.close()
	logFile = open("logFiles/muPro.log","w")
	logFile.close()
	with open(mutation,"r") as mutFile:
		muts = mutFile.readlines()
	mutFile.close()
	muts = [x.strip() for x in muts]
	oldRes = [x[0] for x in muts]
	newRes = [x[-1] for x in muts]
	pos = []
	for x in muts:
		pos.append(''.join(re.findall('\d',x)))
	try:
		# os.chdir("/var/www/html/mutXplorScripts")
		for i in range(len(pos)):
			with open("seqFile","w") as inFile:
				inFile.write(header+'\n'+sequence+'\n'+pos[i]+'\n'+oldRes[i]+'\n'+newRes[i])
			inFile.close()
			#Run MUpro in command-line
			os.system("/var/www/html/mutXplorScripts/mupro1.1/bin/predict_class.sh seqFile > out.txt")
			#Parse the result if destabilizing or stabilizing
			resFile = open("out.txt","r")
			if "error" in resFile.read():
				resFile.seek(0)
				with open("logFiles/muPro.log","a") as logFile:		
					logFile.write(resFile.read())
				logFile.close()
			else:
				#Get the required results from output
				resFile.seek(0)
				stability = (resFile.readlines()[0]).split()[2]
				resFile.seek(0)
				score = (resFile.readlines()[1]).split()[2]
				with open("outFiles/muPro.out","a") as finalOut:
					finalOut.write(oldRes[i]+pos[i]+newRes[i]+','+stability+','+score+'\n')
				finalOut.close()
			resFile.close()
		# os.chdir("/var/www/html/mutXplorScripts")
		os.system("rm out.txt")
	except:
		print(str(sys.exc_info()[0])+" occured and muPRO could not run")
# runMuPro('Seq.fasta','mutationList.txt','965')