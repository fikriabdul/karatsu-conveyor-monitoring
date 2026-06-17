# 唐津砕石 Conveyor Monitoring System

Real-time monitoring dashboard for the 唐津砕石 crusher conveyor belt (B1), displaying live throughput, current-based throughput prediction, and trend charts.

---

## Tech Stack

- **Backend:** PHP 8.1+
- **Frontend:** HTML + Chart.js (no framework)
- **Data source:** RT API (`https://niwmd.nglobal.jp/niw2589_rt`)
- **Storage:** Daily CSV logs in `logs/`
- **Scheduling:** Windows Task Scheduler

---

## File Structure

```
├── index.html              # Dashboard UI
├── api.php                 # Main backend — serves latest reading and trend data
├── cron_fetch.php          # Fetches RT API and writes to CSV log (called by Task Scheduler)
├── run_scheduler.bat       # Windows Task Scheduler entry point
├── includes/
│   ├── predict.php         # B1 throughput prediction model
│   ├── log.php             # CSV log read/write functions
│   └── trend.php           # Builds trend timeline from CSV logs
└── logs/                   # Auto-generated daily CSV logs (writable folder required)
```

---

## Server Requirements

- **OS:** Windows Server (any version supporting IIS or XAMPP)
- **PHP:** 8.1 or higher
- **PHP extensions:** `mbstring`
- **`php.ini` settings:**
  ```ini
  allow_url_fopen = On
  date.timezone = Asia/Tokyo
  extension=mbstring
  ```
- **Outbound HTTPS access** to `https://niwmd.nglobal.jp/niw2589_rt` (whitelist if behind firewall)

---

## Deployment Steps

### 1. Copy files
Place all files in the web server root (e.g. `C:\inetpub\wwwroot\belt-monitor\`).

### 2. Set folder permission
Give the web server user (e.g. `IIS_IUSRS`) **write permission** on the `logs/` folder.

### 3. Set up Windows Task Scheduler
1. Open **Task Scheduler** → Create Basic Task
2. **Trigger:** Daily → repeat every **5 minutes** indefinitely
3. **Action:** Start a program → browse to `run_scheduler.bat`
4. Check **"Run whether user is logged on or not"**

Before saving, edit `run_scheduler.bat` and set `PHP_EXE` to the correct PHP path on the server:
```bat
set PHP_EXE=C:\php\php.exe        ← adjust this path
```

---

## Configuration

All configuration is in `api.php` and `cron_fetch.php`.

| Constant | File | Description |
|---|---|---|
| `CURRENT_DATA_VALID` | `api.php`, `cron_fetch.php` | Set to `false` if PLC current calibration needs adjustment. Disables prediction and uses throughput for status detection instead. |
| `OFFLINE_THRESHOLD_MIN` | `api.php` | Minutes without a new reading before the dashboard shows "No Signal" (default: 45) |

---

## B1 Prediction Model

```
ton/h = 36.1997 × (I_A − 20.16) + 48.5640
```

Where `I_A` is the B1 motor current in Amperes (from `b1.current` in the RT API response).

- **R²:** 0.8378
- **RMSE:** 12.67 t/h
- **Idle threshold:** 20.16 A (below this → belt considered idle)

---

## Data Logs

The `logs/` folder contains two types of daily CSV files, kept for 30 days:

| File | Contents |
|---|---|
| `belt_log_YYYYMMDD.csv` | B1 actual, predicted, ampere, error, voltage |
| `raw_b2b5_YYYYMMDD.csv` | B2/B5 raw values (no model yet, logged for observation) |
