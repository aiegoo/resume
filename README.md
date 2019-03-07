resume

On Mar 7, I broke for lunch and got back to work, and found that about 80% of files were deleted physically (I mean in the folder those are nowhere to find). Here's the solution.
git reset --  //unstaged cahnges after reset
git checkout --force //restore the deleted files to the working directory.


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
