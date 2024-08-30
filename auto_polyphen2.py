#!usr/bin/python3

import os
import re
import mechanicalsoup
import pandas as pd

def runPolyphen2(uniprotId, mutation, rndNum):
    os.chdir("/var/www/html/fileUpload/" + rndNum)
    with open(mutation, "r") as mutFile:
        muts = [x.strip() for x in mutFile.readlines()]

    newRes = [x[-1] for x in muts]
    wildRes = [x[0] for x in muts]
    pos = [''.join(re.findall('\d', x)) for x in muts]
    
    batch = "\n".join([f"{uniprotId} {pos[i]} {wildRes[i]} {newRes[i]}" for i in range(len(pos))])

    proxies = {'https': 'proxy.ibab.ac.in:3128', 'http': 'proxy.ibab.ac.in:3128'}

    try:
        br = mechanicalsoup.StatefulBrowser()
        url1 = "http://genetics.bwh.harvard.edu/pph2/bgi.shtml"
        br.session.proxies = proxies
        br.open(url1)
        br.select_form(nr=0)
        br["_ggi_batch"] = batch
        br["MODELNAME"] = "HumVar"
        br.submit_selected(btnName="_ggi_target_pipeline")

        session = str(br.page.find("input", attrs={"name": "sid"}))
        sID = session.split('value="')[-1][:-3]

        br.select_form(nr=0)
        br.submit_selected()
        br.select_form()
        br.submit_selected()

        checkLink = "/pph2/" + sID
        res = br.links(url_regex=checkLink)

        while not res or checkLink not in str(res[0]):
            br.select_form()
            br.submit_selected()
            res = br.links(url_regex=checkLink)

        resultLink = "/pph2/" + sID + "/./pph2-short.txt"
        logLink = "/pph2/" + sID + "/./pph2-log.txt"

        br.download_link(link=resultLink, file="pph2.out")
        br.download_link(link=logLink, file="logFiles/polyphen2.log")

        out = pd.read_table("pph2.out")
        out.columns = [out.columns[i].strip() for i in range(len(out.columns))]
        newOut = out.dropna(subset=["pos"]).copy()

        newOut["pos"] = newOut["pos"].astype(int)
        newOut = newOut[["pos", "aa1", "aa2", "prediction", "pph2_prob"]]
        newOut["aa1"] = newOut["aa1"].str.strip()
        newOut["aa2"] = newOut["aa2"].str.strip()
        newOut["prediction"] = newOut["prediction"].str.strip()
        newOut["mut"] = newOut["aa1"] + newOut["pos"].astype(str) + newOut["aa2"]
        newOut = newOut[["mut", "prediction", "pph2_prob"]]
        newOut.columns = ["Mutation", "Phenotype", "Score"]

        newOut.to_csv("outFiles/polyphen2.out", index=False)
        os.remove("pph2.out")

    except Exception as e:
        print(f"{str(e)} occurred and PolyPhen-2 could not run")
# runPolyphen2("P34059","mutationList.txt","61")