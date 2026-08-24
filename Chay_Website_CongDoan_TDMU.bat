@echo off
title Website Cong Doan TDMU - Automatic Server Launcher
color 0A

:: Lay chinh xac duong dan cua thu muc chua file .bat nay, bat ke user de o dau
cd /d "%~dp0"

echo =================================================================
echo 🚀 DANG KHOI CHAY SERVER CONG DOAN TDMU REAL SAAS ENGINE...
echo =================================================================

echo ⏳ Vui long doi 2 giay de Server khoi dong, trinh duyet se tu dong mo...

:: Mo trinh duyet ngam sau 2 giay de dam bao node.js da khoi dong xong
start cmd /c "timeout /t 2 /nobreak >nul & start http://localhost:3000 & start http://localhost:3000/admin.html"

echo.
echo =================================================================
echo Server dang chay thoi gian thuc tren cong 3000...
echo (De cua so nay de duy tri web, tat cua so de dung server)
echo =================================================================

:: Khoi chay Node.js Server
node server/server.js
pause
