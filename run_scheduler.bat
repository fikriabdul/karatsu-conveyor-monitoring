@echo off
REM ============================================================
REM  Belt Monitor — Windows Task Scheduler entry point
REM  Runs cron_fetch.php every 5 minutes via PHP CLI.
REM
REM  Setup in Task Scheduler:
REM    Action  : Start a program
REM    Program : C:\path\to\run_scheduler.bat
REM    Trigger : Daily, repeat every 5 minutes indefinitely
REM    Options : Run whether user is logged on or not
REM ============================================================

REM Edit this path to match your PHP installation
set PHP_EXE=C:\php\php.exe

"%PHP_EXE%" "%~dp0cron_fetch.php"
