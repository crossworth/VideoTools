## VideoTools - Copy, convert and upload videos to youtube

The goal of this project was to create a simple tool that could copy a movie(s) file from one location,
convert it using `HandBrakeCLI.exe` and then upload it to youtube using Google's API.

Some features include:
- Resume video upload
- Bandwith control

But there is one problem, the resume video upload its not working.
To be honest it works, but the video will always fail to be converted on youtube end.
Probabily we are sending some bytes twice when we try to resume.
