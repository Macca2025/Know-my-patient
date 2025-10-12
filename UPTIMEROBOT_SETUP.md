# UptimeRobot Monitoring Setup Guide

**Created:** 12 October 2025  
**NHS DCB0129 Compliance:** Hazard H-004 (System Unavailability During Emergency)  
**Status:** 🔴 Action Required - Set Up Now

---

## 📋 Overview

UptimeRobot is a free uptime monitoring service that will:
- ✅ Monitor your application 24/7
- ✅ Alert you immediately if the site goes down
- ✅ Track response times and uptime percentage
- ✅ Provide status page for users
- ✅ Send notifications via email, SMS, Slack, etc.
- ✅ Free tier: 50 monitors, 5-minute intervals

**NHS DCB0129:** This addresses Hazard H-004 (System Unavailability) by detecting outages within 5 minutes and alerting on-call staff.

---

## 🚀 Quick Setup (10 Minutes)

### Step 1: Create UptimeRobot Account (2 minutes)

1. **Visit:** https://uptimerobot.com
2. **Click:** "Register for FREE" (no credit card required)
3. **Enter:**
   - Email: `your-email@example.com`
   - Password: Strong password (use password manager)
4. **Verify email** (check inbox)
5. **Login** to dashboard

### Step 2: Create Your First Monitor (3 minutes)

Once logged in:

1. **Click:** "+ Add New Monitor" button

2. **Configure Monitor:**
   ```
   Monitor Type:        HTTP(s)
   Friendly Name:       Know My Patient - Production
   URL (or IP):         https://yourdomain.com/health
                        (or http://yourdomain.com/health for testing)
   Monitoring Interval: 5 minutes (free tier)
   Monitor Timeout:     30 seconds
   ```

3. **Click:** "Create Monitor"

### Step 3: Set Up Alert Contacts (3 minutes)

1. **Click:** "My Settings" → "Alert Contacts"

2. **Add Primary Contact:**
   ```
   Type:           Email
   Friendly Name:  Primary Admin Alert
   Email:          admin@knowmypatient.nhs.uk
   ```
   - Click "Add Alert Contact"
   - Verify email (check inbox)

3. **Add Secondary Contact (Recommended):**
   ```
   Type:           SMS (requires paid plan)
   OR
   Type:           Webhook (Slack, Discord, Teams)
   ```

4. **Optional: Add Slack Integration**
   - Type: Webhook
   - URL: Your Slack webhook URL
   - (Setup instructions below)

### Step 4: Configure Notifications (2 minutes)

1. **Edit your monitor**
2. **Scroll to:** "Alert Contacts to Notify"
3. **Select:** Your alert contacts
4. **Set threshold:** Send alert if down for 2 checks (10 minutes)
5. **Save changes**

---

## 📊 Recommended Monitor Setup

### Monitor 1: Health Check Endpoint ⭐ **CRITICAL**

```
Monitor Type:     HTTP(s)
Friendly Name:    Know My Patient - Health Check
URL:              https://yourdomain.com/health
Interval:         5 minutes
Timeout:          30 seconds
Keyword:          "status":"healthy"  (optional but recommended)
Alert When:       Down for 2 consecutive checks (10 min)
```

**Why this is critical:**
- Your `/health` endpoint checks database, disk space, logs
- Returns JSON with status
- If unhealthy, you know there's a system issue

### Monitor 2: Homepage ⭐ **HIGH PRIORITY**

```
Monitor Type:     HTTP(s)
Friendly Name:    Know My Patient - Homepage
URL:              https://yourdomain.com/
Interval:         5 minutes
Timeout:          30 seconds
Keyword:          Know My Patient  (checks content loaded)
Alert When:       Down for 2 consecutive checks
```

### Monitor 3: Login Page ⭐ **MEDIUM PRIORITY**

```
Monitor Type:     HTTP(s)
Friendly Name:    Know My Patient - Login
URL:              https://yourdomain.com/login
Interval:         5 minutes
Timeout:          30 seconds
Keyword:          login  (or your login page title)
Alert When:       Down for 2 consecutive checks
```

### Monitor 4: Response Time Alert (Optional)

```
Monitor Type:     HTTP(s)
Friendly Name:    Know My Patient - Response Time
URL:              https://yourdomain.com/health
Interval:         5 minutes
Alert When:       Response time > 2000ms for 5 consecutive checks
```

---

## 🔔 Alert Configuration Best Practices

### Email Alerts

**Recommended Recipients:**
- Primary: Lead Developer / DevOps Engineer
- Secondary: Backup on-call engineer
- Escalation: CTO / Technical Manager (for persistent outages)

**Subject Line Format:**
```
UptimeRobot Alert: [Monitor Name] is DOWN
```

### Alert Thresholds

| Severity | Threshold | Recipients |
|----------|-----------|------------|
| **Critical** | Down for 10+ minutes | All contacts + SMS |
| **High** | Down for 5-10 minutes | Email + Slack |
| **Warning** | Response time > 2s | Email only |

### Alert Schedule

**Production:**
- Monitor: 24/7
- Alerts: 24/7
- No maintenance windows (unless scheduled)

**Staging/Development:**
- Monitor: 24/7
- Alerts: Business hours only (optional)

---

## 🔗 Advanced Setup Options

### Option 1: Slack Integration (Recommended)

**Setup Slack Webhook:**

1. **Go to:** https://api.slack.com/apps
2. **Click:** "Create New App" → "From scratch"
3. **Name:** "UptimeRobot Alerts"
4. **Select workspace**
5. **Features:** Incoming Webhooks → Activate
6. **Add webhook:** Select channel (#alerts or #monitoring)
7. **Copy webhook URL** (looks like: `https://hooks.slack.com/services/...`)

**Add to UptimeRobot:**

1. **My Settings** → **Alert Contacts**
2. **Type:** Web-Hook
3. **Friendly Name:** Slack - Monitoring Channel
4. **URL:** Your webhook URL
5. **POST Value:**
   ```json
   {
     "text": "🚨 *monitorFriendlyName*: *alertTypeFriendlyName*\n*alertDetails*\nURL: *monitorURL*"
   }
   ```
6. **Save**

### Option 2: Microsoft Teams Integration

**Setup Teams Webhook:**

1. **In Teams:** Go to desired channel
2. **Click:** "..." → Connectors → Incoming Webhook
3. **Configure:** Name it "UptimeRobot"
4. **Copy webhook URL**

**Add to UptimeRobot:**
- Same as Slack but use Teams webhook URL
- POST format:
  ```json
  {
    "@type": "MessageCard",
    "text": "🚨 *monitorFriendlyName* is *alertTypeFriendlyName*"
  }
  ```

### Option 3: Custom Webhook (Your Server)

Create endpoint in your app to receive alerts:

```php
<?php
// src/Application/Actions/MonitoringWebhookController.php

namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class MonitoringWebhookController
{
    private LoggerInterface $logger;
    
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $data = $request->getParsedBody();
        
        // Log the alert
        $this->logger->critical('UptimeRobot Alert', [
            'monitor' => $data['monitorFriendlyName'] ?? 'Unknown',
            'status' => $data['alertTypeFriendlyName'] ?? 'Unknown',
            'url' => $data['monitorURL'] ?? 'Unknown',
            'details' => $data['alertDetails'] ?? 'No details',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
        
        // Optional: Send to Sentry
        if (function_exists('\\Sentry\\captureMessage')) {
            \Sentry\captureMessage(
                "UptimeRobot: {$data['monitorFriendlyName']} is {$data['alertTypeFriendlyName']}",
                \Sentry\Severity::critical()
            );
        }
        
        $response->getBody()->write(json_encode(['status' => 'received']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

---

## 📈 Dashboard Configuration

### Status Page (Public Status Dashboard)

**Create Public Status Page:**

1. **Dashboard:** Click "Public Status Pages"
2. **Add New Status Page:**
   ```
   Name:              Know My Patient Status
   Subdomain:         knowmypatient  
   Full URL:          https://knowmypatient.uptimerobot.com
   Monitors:          Select your monitors
   Custom Domain:     status.yourdomain.com (optional, paid)
   ```

3. **Customize:**
   - Logo: Upload your NHS logo
   - Colors: Match your brand
   - Custom announcement: "All systems operational"

4. **Share URL with:**
   - Users during incidents
   - Support team
   - Embed on your website

**Example Status Page Content:**
```html
<!-- Add to your website footer -->
<a href="https://knowmypatient.uptimerobot.com" target="_blank">
  System Status
</a>
```

---

## 🔍 Monitoring Best Practices

### 1. Monitor Different Components

```
✅ /health endpoint        - System health
✅ Homepage (/)            - User accessibility  
✅ /login                  - Authentication
✅ Database connectivity   - (via /health)
❌ Don't monitor /api/     - May have auth issues
❌ Don't monitor /admin/   - Requires login
```

### 2. Set Realistic Timeouts

```
Fast endpoints (<1s):     15-30 second timeout
Slow endpoints (1-3s):    45-60 second timeout
Very slow (>3s):          Need optimization!
```

### 3. Response Time Monitoring

**Target Response Times:**
- Excellent: < 500ms
- Good: 500ms - 1000ms
- Acceptable: 1000ms - 2000ms
- Poor: > 2000ms (investigate!)
- Critical: > 5000ms (urgent optimization needed)

**Set Alerts:**
- Warning: Response time > 2000ms for 3 checks
- Critical: Response time > 5000ms for 2 checks

### 4. Maintenance Windows

**Schedule maintenance windows:**

1. **Dashboard:** Edit monitor
2. **Maintenance Windows:** Add new
3. **Configure:**
   ```
   Type:     Weekly (e.g., Sunday 2-4 AM)
   OR
   Type:     One-time (for planned upgrades)
   Duration: 2 hours
   ```
4. **Disable alerts** during maintenance

---

## 📊 Understanding UptimeRobot Metrics

### Uptime Percentage

```
99.9%  = 43 minutes downtime per month    (Good)
99.5%  = 3.6 hours downtime per month     (Acceptable)
99.0%  = 7.2 hours downtime per month     (Poor)
95.0%  = 36 hours downtime per month      (Critical)
```

**NHS Target:** 99.5% uptime minimum (< 3.6 hours downtime/month)

### Response Time Stats

**View in Dashboard:**
- Average response time (last 24h, 7d, 30d)
- Response time chart
- Slowest response times
- Geographical response times (paid tier)

### Downtime History

**Access:**
- Dashboard → Select Monitor → "Uptime Logs"
- Shows all incidents with:
  - Duration
  - Reason (if detected)
  - Response codes
  - Recovery time

---

## 🚨 Incident Response Workflow

### When You Receive an Alert

**Step 1: Acknowledge (< 5 minutes)**
```bash
# Check if site is actually down
curl https://yourdomain.com/health

# Check server status
ssh user@server
systemctl status nginx
systemctl status mysql
```

**Step 2: Investigate (< 15 minutes)**
```bash
# Check logs
tail -100 /path/to/logs/error.log

# Check disk space
df -h

# Check database
mysql -u root -p -e "SHOW PROCESSLIST;"

# Check PHP processes
ps aux | grep php
```

**Step 3: Fix or Escalate (< 30 minutes)**
- Quick fix if possible
- Escalate to senior engineer if complex
- Update status page with incident notice

**Step 4: Document (< 24 hours)**
- Add incident to HAZARD_LOG.md
- Post-mortem meeting
- Update runbooks

---

## 📋 UptimeRobot Checklist

### Initial Setup
- [ ] Create UptimeRobot account
- [ ] Add /health monitor (5-minute interval)
- [ ] Add homepage monitor
- [ ] Add login page monitor
- [ ] Configure email alerts (2 contacts minimum)
- [ ] Set up Slack/Teams webhook (optional)
- [ ] Test monitors (wait 5 minutes, check dashboard)
- [ ] Create public status page
- [ ] Document monitor URLs in team wiki

### Weekly Tasks
- [ ] Check uptime percentage (target: >99.5%)
- [ ] Review average response times
- [ ] Check for any missed alerts
- [ ] Verify alert contacts still valid

### Monthly Tasks
- [ ] Review downtime incidents
- [ ] Update alert thresholds if needed
- [ ] Test alert notifications (pause monitor briefly)
- [ ] Check monitor count (free tier: 50 max)
- [ ] Generate uptime report for management

---

## 🔧 Troubleshooting

### Issue 1: False Positive Alerts

**Problem:** Getting alerts but site is actually up

**Solutions:**
```
1. Increase timeout (30s → 60s)
2. Increase alert threshold (1 check → 2 checks)
3. Check if IP blocked (whitelist UptimeRobot IPs)
4. Verify /health endpoint doesn't require auth
5. Check if rate limiting is blocking UptimeRobot
```

**UptimeRobot IP Ranges to Whitelist:**
```
63.143.42.242
46.137.190.132
122.248.234.23
188.172.252.34
114.134.186.12
```

### Issue 2: Not Receiving Alerts

**Problem:** Site is down but no alerts received

**Solutions:**
```
1. Check email spam folder
2. Verify alert contact is verified (green checkmark)
3. Check "Alert Contacts to Notify" is selected
4. Test alert by pausing monitor (sends "up" alert)
5. Check email address is correct
```

### Issue 3: Too Many Alerts

**Problem:** Alert fatigue from frequent alerts

**Solutions:**
```
1. Increase alert threshold (2 checks → 3 checks)
2. Fix underlying instability issues
3. Add maintenance window for known issues
4. Implement graduated alerts (warning → critical)
5. Use alert grouping (paid feature)
```

---

## 💰 Free vs Paid Tiers

### Free Tier (Sufficient for Most)
- ✅ 50 monitors
- ✅ 5-minute check intervals
- ✅ Email + webhook alerts
- ✅ 2 months of logs
- ✅ Public status pages
- ✅ SSL monitoring
- ❌ SMS alerts
- ❌ 1-minute intervals
- ❌ Multi-location checks

### Paid Tier ($7/month - Optional)
- ✅ 1-minute check intervals
- ✅ SMS alerts (limited)
- ✅ Multi-location monitoring
- ✅ Advanced reports
- ✅ Longer log retention
- ✅ White-label status pages

**Recommendation:** Start with free tier, upgrade if:
- Need SMS alerts for critical staff
- Need faster detection (1-min vs 5-min)
- Need geographical monitoring

---

## 📞 Integration with Existing Systems

### Sentry Integration

Already have Sentry? Integrate with UptimeRobot:

```javascript
// In Sentry dashboard
// Settings → Integrations → Webhooks
// Add UptimeRobot webhook to trigger Sentry issues
```

### PagerDuty Integration (Enterprise)

For 24/7 on-call rotation:

1. **PagerDuty:** Create integration key
2. **UptimeRobot:** Add alert contact
3. **Type:** PagerDuty
4. **Integration Key:** Your PagerDuty key

### Custom Dashboard (Advanced)

Pull UptimeRobot data into your own dashboard:

```php
<?php
// Example: Fetch UptimeRobot stats via API

$apiKey = 'your-uptimerobot-api-key';
$url = 'https://api.uptimerobot.com/v2/getMonitors';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'api_key' => $apiKey,
    'format' => 'json',
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$result = curl_exec($ch);
$data = json_decode($result, true);

foreach ($data['monitors'] as $monitor) {
    echo "{$monitor['friendly_name']}: {$monitor['status']}\n";
}
```

---

## 📊 Monthly Report Template

**Send to management monthly:**

```
===========================================
Know My Patient - Monthly Uptime Report
===========================================

Period: October 2025

UPTIME STATISTICS:
- Overall Uptime:      99.8% ✅
- Target Uptime:       99.5%
- Total Downtime:      ~43 minutes
- Number of Incidents: 2

RESPONSE TIME:
- Average:             487ms ✅
- Peak (slowest):      1,234ms
- Target:              < 2000ms

INCIDENTS:
1. Oct 5, 2025 - Database connectivity issue (15 min)
   Resolution: Restarted MySQL service
   
2. Oct 18, 2025 - Disk space full (28 min)
   Resolution: Log rotation implemented

ACTIONS TAKEN:
✅ Implemented log rotation system
✅ Database monitoring improved
□ Plan disk space monitoring (next month)

STATUS: All systems operational ✅
===========================================
```

---

## 🎯 NHS DCB0129 Compliance

### Hazard H-004: System Unavailability

**Mitigation:**
- ✅ 24/7 uptime monitoring
- ✅ Alerts within 5 minutes of downtime
- ✅ Multiple alert channels (email, Slack, SMS)
- ✅ Public status page for transparency
- ✅ Incident tracking and reporting

**Evidence:**
- UptimeRobot dashboard showing 99.5%+ uptime
- Alert configuration screenshots
- Incident response times
- Monthly uptime reports

**Residual Risk:** MEDIUM → LOW (with monitoring)
- Mean time to detect: < 5 minutes
- Mean time to acknowledge: < 15 minutes
- Mean time to resolve: Target < 4 hours

---

## ✅ Quick Reference

### Essential Commands

```bash
# Test your health endpoint
curl https://yourdomain.com/health

# Check if UptimeRobot can reach you
curl -I https://yourdomain.com/health

# View UptimeRobot IP ranges
dig +short uptimerobot.com
```

### Important URLs

```
UptimeRobot Dashboard:  https://uptimerobot.com/dashboard
Status Page:            https://[yourname].uptimerobot.com
API Docs:               https://uptimerobot.com/api/
Support:                support@uptimerobot.com
```

### Monitor Configuration Summary

| Monitor | URL | Interval | Timeout | Keyword |
|---------|-----|----------|---------|---------|
| Health Check | /health | 5 min | 30s | "status":"healthy" |
| Homepage | / | 5 min | 30s | "Know My Patient" |
| Login | /login | 5 min | 30s | "login" |

---

## 📱 Mobile App

**iOS/Android App Available:**
- Download "UptimeRobot" from App Store / Google Play
- Get push notifications on mobile
- Quick incident overview
- Pause monitors on the go

---

## 🔐 Security Best Practices

1. **Use strong password** for UptimeRobot account
2. **Enable 2FA** (Settings → Security)
3. **Whitelist UptimeRobot IPs** in firewall (optional)
4. **Don't monitor sensitive endpoints** (admin panels, API keys)
5. **Use read-only endpoints** for monitoring

---

## 🎓 Next Steps

### Immediate (Today - 10 minutes)
1. ✅ Create UptimeRobot account
2. ✅ Add /health monitor
3. ✅ Configure email alerts
4. ✅ Test by visiting dashboard

### This Week
1. Add additional monitors (homepage, login)
2. Set up Slack webhook
3. Create public status page
4. Document setup in team wiki

### This Month
1. Monitor for false positives
2. Tune alert thresholds
3. Generate first monthly report
4. Review with CSO for NHS compliance

---

**Document Version:** 1.0  
**Last Updated:** 12 October 2025  
**Status:** ✅ Ready to Implement  
**Estimated Setup Time:** 10 minutes  
**Cost:** Free (paid tier optional: $7/month)

**Support:** Contact Macca2025 or review UptimeRobot documentation
