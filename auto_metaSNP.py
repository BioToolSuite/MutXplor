#!usr/bin/python3

import os
import mechanicalsoup as msp
import sys

def runMetaSNP(fasta, mutation, rndNum):
    os.chdir("/var/www/html/fileUpload/" + rndNum)
    
    with open(fasta, "r") as file1:
        seq = file1.read()

    with open(mutation, "r") as file2:
        muts = file2.read()
        noOfMuts = len(file2.readlines())

    url1 = "https://snps.biofold.org/meta-snp/index.html"
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

        br.download_link(link="output.txt", file="metaSNP")

        with open("outFiles/metaSNP.out", "w") as outFile:
            outFile.write("Mutation,Phenotype,Score\n")
            with open("metaSNP", "r") as snpsFile:
                allres = [line.split() for line in snpsFile.readlines()[7:]]
                for i in range(1, len(allres) - 18, 2):
                    meta = allres[i][0], allres[i][-5], allres[i + 1][-1]
                    outFile.write(','.join(meta) + "\n")

        os.system("rm metaSNP")

    except Exception as e:
        print(f"{str(e)} occurred and metaSNP could not run")