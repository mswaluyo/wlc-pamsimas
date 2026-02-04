@echo off
title STB SFTP Hub
echo Menghubungkan ke STB Selur...
echo [PENTING] JANGAN TUTUP window ini selama Anda mengedit file di VS Code.
:: Ganti path di bawah ini sesuai lokasi file cloudflared.exe Abang
"C:\cloudflared\cloudflared.exe" access ssh --hostname ssh.selur.my.id --url localhost:2222
pause