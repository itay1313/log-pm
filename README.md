# לוג ניהול פרויקטים — אתר

WordPress מותאם, בלי בילדרים. שני חלקים:

| נתיב | מה זה |
|---|---|
| `wp-content/plugins/log-core` | מודל התוכן: פרויקטים, משרות, מרכז ידע, צוות, לקוחות; מדרגת היקף אוטומטית; `GET /wp-json/log/v1/map` ו-`/stats`; סכמה (Organization, JobPosting); Setup בלחיצה אחת; ייבוא מ-ArcGIS (כלים → ייבוא מ-ArcGIS). |
| `wp-content/themes/log` | תצוגה בלבד. תבניות לכל נתיב באפיון, שני הכפתורים הצמודים, גופנים עצמיים, Vite. |
| `.github/workflows/deploy.yml` | כל פוש ל-`main` (מיזוג PR) בונה, אורז את התבנית והתוסף ושולח ב-HTTPS ל-`/wp-json/log/v1/deploy`. התוסף מתקין רק עם טוקן חתום של GitHub מהריפו הזה (`log-core/inc/deploy.php`). |
| `tools/deploy.sh` | גיבוי: פריסה ב-FTP מהמחשב, רק כשה-IP מאושר בפאנל של uPress (host ו-user מ-`.env`, סיסמה מה-Keychain `log-pm-ftp`). |
| `tools/dev.sh` | WordPress מקומי ב-Playground על :8881, עם התיקיות מחוברות חי. |
| `docs/` | מיפוי ArcGIS וה-spike. האפיונים והצעת המחיר (PDF) נשמרים מקומית בלבד, לא בגיט: הריפו ציבורי. |

## עבודה יומיומית

```bash
./tools/dev.sh            # מקומי: http://127.0.0.1:8881
cd wp-content/themes/log && npm run dev   # Vite עם HMR (הגדירו LOG_VITE_DEV ב-wp-config)
```

לשרת: פותחים PR וממזגים ל-`main`, והפריסה רצה לבד. כללי העבודה עם גיט ב-`CLAUDE.md`.

אחרי פריסה ראשונה: wp-admin → תוספים → הפעלת LOG Core, עיצוב → הפעלת LOG, כלים → LOG Setup.

## החלטות

- **שדות:** ACF Pro. הקבוצות ב-`log-core/acf-json`, בגרסה. לא עורכים שדות ב-UI בלי לסנכרן חזרה.
- **מפה:** Mapbox GL v3. הטוקן ב"הגדרות לוג" ולא בקוד.
- **מדרגת היקף:** מחושבת בשמירה (`inc/scale.php`), לא ידנית.
- **פרסום:** פרויקט מופיע באתר ובמפה רק עם "מאושר לפרסום".
- **אינדוקס:** חסום עד ההשקה (הגדרות → קריאה).
