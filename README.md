On mar 10 wee hour
I have reverted two prior commits to return to the final wrap up state of my resume, which I have concluded by adding a kitten animation signifying that I found my job and went hybernating from job hunting....
I tried to update my profile picture of me being hard at work... and to add image hover effects of images being switched between tonyillust gray and dark. I discarded the changes using github left-panel options; finally dumped everyting to the tmp branch for keepsakes.

Then strange things happened... about 40 files of various folders are being modified or deleted... so I checked out HEAD 1 trough 9 and added them to respective branches and downloaded the zip version of remote repo.. 

Now satisfied that I successfully aborted a possible disaster and kept every stage of status changes, I decieded to revert HEAD~2..HEAD so that my previous stable version will remain in the tony branch and other stfuss in tmp (i decided to delete the head$ branches.)

resume
======
$ git push resume
Enumerating objects: 77, done.
Counting objects: 100% (77/77), done.
Delta compression using up to 8 threads
Compressing objects: 100% (74/74), done.
Writing objects: 100% (77/77), 5.75 MiB | 281.00 KiB/s, done.
Total 77 (delta 7), reused 51 (delta 2)
remote: Resolving deltas: 100% (7/7), done.
To https://github.com/aiegoo/resume.git
 * [new branch]      tony -> tony
