import os,shutil
import re

pathDest = "./dest" #要备份的文件目录
pathSrc = "./src"  #备份到什么目录


extension = "txt" #要备份的文件后缀 * 备份所有

files = os.listdir(pathSrc)

for f in files:

    filesrc = pathSrc + "/" + f
    
    if os.path.isdir(filesrc):

        continue

    elif extension == "*" or re.search("\." + extension + "$",f):
    

        shutil.copyfile(filesrc,pathDest + "/" + f)

print("success")
    




