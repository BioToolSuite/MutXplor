#!/usr/bin/python3

import mechanicalsoup as msp
import sys
import os

def runDuet(pdbFile, mutation, fChain, rndNum):
    os.chdir("/var/www/html/fileUpload/" + rndNum)
    muts = open(mutation, "r").readlines()
    logFile = open("logFiles/duet.log", "w")

    tools = ['mCSM', 'sdm', 'duet']
    for tool in tools:
        with open(f'outFiles/{tool}.out', "w") as fwrite:
            fwrite.write('Mutation,Stability,Score\n')

    url1 = "http://biosig.unimelb.edu.au/duet/stability"
    proxies = {'https': 'proxy.ibab.ac.in:3128', 'http': 'proxy.ibab.ac.in:3128'}

    try:
        br = msp.StatefulBrowser()
        br.session.proxies = proxies

        for mut in muts:
            br.open(url1)
            form = br.select_form(nr=1)
            # form.set("wild", pdbFile)
            form["wild"] = open(pdbFile, "rb")
            br["mutation"] = mut.strip()
            br["chain"] = fChain
            res = br.submit_selected()

            content = br.page.find('div', attrs={"class": "well"})
            if content:
                stability = content.findAll("font", attrs={"size": "4"})
                for c, s in enumerate(stability):
                    with open(f'outFiles/{tools[c]}.out', "a") as fapnd:
                        fapnd.write(mut.strip() + ',' + s.contents[1].contents[0] + ',' + s.contents[0].lstrip().rstrip().replace(' kcal/mol (', '') + '\n')
            else:
                error = br.page.find("div", attrs={"class": "alert alert-error"})
                if "Error" in error.text:
                    logFile.write(error.text)
        
        logFile.close()

    except Exception as e:
        print(f"{type(e).__name__} occurred and DUET could not run: {e}")

# runDuet("pdbfile.pdb", "mutationList.txt", "A", "775")