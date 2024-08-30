#!/usr/bin/python3

import os
import sys
import mechanicalsoup as msp

def runSnpsGO(fasta, mutation, rndNum):
    os.chdir("/var/www/html/fileUpload/" + rndNum)
    with open(fasta, "r") as file1:
        seq = file1.read()
    with open(mutation, "r") as file2:
        muts = file2.read()

    url1 = "https://snps.biofold.org/snps-and-go/snps-and-go.html"
    proxies = {'https': 'proxy.ibab.ac.in:3128', 'http': 'proxy.ibab.ac.in:3128'}
    
    try:
        br = msp.StatefulBrowser()
        br.session.proxies = proxies
        br.open(url1)
        br.select_form(selector="form")
        br["proteina"] = seq
        br["posizione"] = muts

        res = br.submit_selected()
        br.follow_link("output.html")
        resultLink = br.get_url()

        while "Please wait." in br.page.text:
            br.open(resultLink)

        br.download_link(link="output.txt", file="snpsGO")

        with open("outFiles/snpsGO.out", "w") as outFile:
            outFile.write("Mutation,Phenotype,Score\n")
            with open("snpsGO", "r") as snpsFile:
                with open("logFiles/snpsGO.log", "w") as logFile:
                    for line in snpsFile:
                        if "SNPs&GO" in line and ("*" not in line) and ("SVM" not in line):
                            res = line.split()[:2]
                            res.append(line.split()[-2])
                            outFile.write(','.join(res) + "\n")
                        if "WARNING" in line:
                            logFile.write(line)
        os.system("rm snpsGO")

    except Exception as e:
        print(f"{str(e)} occurred and SNPs&GO could not run")